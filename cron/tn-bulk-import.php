<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Fills tn_bulk_events (migration 0050) from TicketNetwork's GET /catalog/v2/events/bulk.
 *
 *   php cron/tn-bulk-import.php [auto|full|incremental] [--file=page.json] [--dry-run] [--max-pages=N]
 *
 *   auto         (default) a full refresh when there has never been one, or when the last one is over a day old and it is 07:00 to
 *                10:59 UTC; otherwise the changes pull. Run this every hour from cron.
 *   full         every event, then deletes rows the catalog no longer returns (only after a run that reached the end, see tnBulkMayDelete()).
 *                Progress is stored after every page, so a run stopped by a throttle, a breaker or a timeout resumes on the next call.
 *   incremental  events whose data or _metadata changed since the last good run (metadataInclusiveUpdatedAt ge, 10 minutes of overlap).
 *   --file       reads one saved response ({"results":[...]}) instead of calling the API: checks the mapping against a real answer.
 *   --dry-run    with --file, maps and counts without writing.
 *
 * Off unless TN_BULK_ENABLED=1 (the endpoint needs a role TicketNetwork grants on request). One request at a time, never in parallel
 * (TicketNetwork's rule for this endpoint); each call takes a token from the shared call budget; a throttle opens the events circuit
 * breaker and ends the run. Nothing on the site reads the table yet.
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/tn-bulk.php';

const TN_BULK_PATH = '/catalog/v2/events/bulk';
const TN_BULK_MAX_PAGES = 3000;        // guard against a runaway loop (200 a page would still cover 600,000 events)
const TN_BULK_RESUME_SECONDS = 21600;  // a full run left half done is resumed for up to 6 hours, then started again
const TN_BULK_MAX_RUN_SECONDS = 3000;  // stop before the next hourly run would find the lock still held

$mode = 'auto'; $file = null; $dry = false; $maxPages = TN_BULK_MAX_PAGES;
foreach (array_slice($argv, 1) as $a) {
    if (in_array($a, ['auto', 'full', 'incremental'], true)) $mode = $a;
    elseif (strpos($a, '--file=') === 0) $file = substr($a, 7);
    elseif ($a === '--dry-run') $dry = true;
    elseif (strpos($a, '--max-pages=') === 0) $maxPages = max(1, (int) substr($a, 12));
    else { fwrite(STDERR, "Unknown argument: $a\n"); exit(2); }
}

function tbLog(string $m): void { echo gmdate('H:i:s') . ' ' . $m . "\n"; }

function tbStateGet(string $name): ?string {
    $stmt = MYSQLI->prepare('SELECT value FROM tn_bulk_state WHERE name = ?');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->bind_result($v);
    $found = $stmt->fetch();
    $stmt->close();
    return $found ? (string) $v : null;
}

function tbStateSet(string $name, string $value): void {
    $stmt = MYSQLI->prepare('INSERT INTO tn_bulk_state (name, value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()');
    $stmt->bind_param('ss', $name, $value);
    $stmt->execute();
    $stmt->close();
}

function tbStateClear(string ...$names): void {
    foreach ($names as $n) {
        $stmt = MYSQLI->prepare('DELETE FROM tn_bulk_state WHERE name = ?');
        $stmt->bind_param('s', $n);
        $stmt->execute();
        $stmt->close();
    }
}

/** Upserts the mapped rows of one page in one transaction; returns how many rows were written. */
function tbWriteRows(array $events, string $seenAt): int {
    $stmt = MYSQLI->prepare(tnBulkUpsertSql());
    if (!$stmt) throw new RuntimeException('prepare failed: ' . MYSQLI->error);
    $types = str_repeat('s', count(TN_BULK_COLUMNS) + 1);
    MYSQLI->begin_transaction();
    $n = 0;
    try {
        foreach ($events as $e) {
            $row = tnBulkMapEvent($e);
            if ($row === null) continue;
            $values = array_map(fn($c) => $row[$c], TN_BULK_COLUMNS);
            $values[] = $seenAt;
            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) throw new RuntimeException('insert failed: ' . $stmt->error);
            $n++;
        }
        MYSQLI->commit();
        soEventRememberMany($events, 'bulk');   // the removed-event redirect knows the performer and category of every event in the catalog
    } catch (Throwable $e) {
        MYSQLI->rollback();
        throw $e;
    } finally {
        $stmt->close();
    }
    return $n;
}

/** Waits (up to two minutes) for a token from the shared call budget, so the import never starves visitors or goes over the limit. */
function tbWaitForBudget(): bool {
    $deadline = microtime(true) + 120;
    do {
        if (tnBucketTake(false, tnCallsPerSecond(), cache_dir() . 'tn_bucket.json')) return true;
        usleep(250000);
    } while (microtime(true) < $deadline);
    return false;
}

/** One bulk request. A 401 gets one new token and one retry, as in tnFetchLive(). @return array{ok:bool,data:array,error:string,throttled:bool} */
function tbFetchPage(array $params): array {
    $call = function (string $token) use ($params) {
        $ch = tnBuildHandle(TN_BULK_PATH, $params, 'GET', $token);
        curl_setopt_array($ch, [CURLOPT_TIMEOUT => 180, CURLOPT_CONNECTTIMEOUT => 10]);   // 10,000 events take about 20 s, sometimes more
        return tnParseResult($ch, curl_exec($ch));
    };
    $GLOBALS['tn_retry_after'] = null;
    $token = getTnAccessToken();
    $r = $call($token);
    if (!empty($r['auth'])) {
        $token = getTnAccessToken($token);
        if ($token !== '') $r = $call($token);
    }
    $http = (int) ($r['http'] ?? 0);
    $ok = !empty($r['ok']) && $http === 200 && isset($r['data']['results']) && is_array($r['data']['results']);
    $error = (string) ($r['error'] ?? '');
    if (!$ok && $error === '') {
        $error = $http === 403 ? 'HTTP 403 (the application has no bulk role yet?)' : 'HTTP ' . $http . ' ' . substr((string) ($r['data']['Message'] ?? $r['data']['message'] ?? 'no results array'), 0, 120);
    }
    return ['ok' => $ok, 'data' => (array) ($r['data'] ?? []), 'error' => $error, 'throttled' => !empty($r['throttled'])];
}

// --- Saved-response check (no API, no state) ---------------------------------------------------------------------------------------
if ($file !== null) {
    $json = json_decode((string) @file_get_contents($file), true);
    if (!is_array($json) || !isset($json['results']) || !is_array($json['results'])) { fwrite(STDERR, "Not a bulk response: {$file}\n"); exit(1); }
    $mapped = 0;
    foreach ($json['results'] as $e) { if (tnBulkMapEvent($e) !== null) $mapped++; }
    tbLog('Events in file: ' . count($json['results']) . '; mapped: ' . $mapped);
    if (!$dry) {
        if (!tnSnapshotDb()) { fwrite(STDERR, "No database connection.\n"); exit(1); }
        tbLog('Rows written: ' . tbWriteRows($json['results'], gmdate('Y-m-d H:i:s')));
    }
    exit(0);
}

// --- Live run ----------------------------------------------------------------------------------------------------------------------
if (!tnBulkEnabled()) { tbLog('Bulk import is off (set TN_BULK_ENABLED=1 once TicketNetwork has granted the bulk role).'); exit(0); }
if (!tnSnapshotDb()) { fwrite(STDERR, "No database connection.\n"); exit(1); }
if (tnBreakerOpen('events')) { tbLog('TicketNetwork events circuit breaker is open; skipped.'); exit(0); }

$lockFh = @fopen(cache_dir() . 'tn_bulk.lock', 'c');
if (!$lockFh || !flock($lockFh, LOCK_EX | LOCK_NB)) { tbLog('Another import is running; skipped.'); exit(0); }

$now = time();
$lastFull = tbStateGet('last_full_ok');
if ($mode === 'auto') $mode = tnBulkChooseMode($lastFull !== null ? (int) $lastFull : null, $now);
$perPage = tnBulkPerPage();

if ($mode === 'incremental') {
    // From the later of the last good changes pull and the last good full refresh, so a new full refresh restarts the clock.
    $base = max((int) tbStateGet('last_incremental_start'), (int) tbStateGet('last_full_start'));
    if ($base < 1) { tbLog('No full refresh has finished yet; run "full" first.'); exit(0); }
    $since = tnBulkSinceIso($base);
    $runStart = $now;
    $page = 1;
} else {
    $since = null;
    $resume = tbStateGet('full_run_start');
    if ($resume !== null && $now - (int) $resume < TN_BULK_RESUME_SECONDS) {
        $runStart = (int) $resume;
        $page = max(1, (int) (tbStateGet('full_next_page') ?? 1));
        tbLog("Resuming the full refresh at page {$page}.");
    } else {
        $runStart = $now;
        $page = 1;
        tbStateSet('full_run_start', (string) $runStart);
        tbStateSet('full_next_page', '1');
    }
}
$seenAt = gmdate('Y-m-d H:i:s', $runStart);
tbLog("Mode: {$mode}; perPage {$perPage}" . ($since ? "; changes since {$since}" : '') . "; first page {$page}.");

$reachedEnd = false; $written = 0; $pages = 0; $error = '';
while ($pages < $maxPages && time() - $now < TN_BULK_MAX_RUN_SECONDS) {
    if (!tbWaitForBudget()) { $error = 'no call budget within two minutes'; break; }
    $t0 = microtime(true);
    $r = tbFetchPage(tnBulkParams($page, $perPage, $since));
    if (!$r['ok']) {
        $error = $r['error'];
        if ($r['throttled']) {
            tnCount('throttled');
            tnBreakerTrip(TN_BULK_PATH . ' ' . $error, tnThrottleWait($GLOBALS['tn_retry_after'] ?? null, TN_BREAKER_THROTTLE_SECONDS), 'events');
        }
        break;
    }
    $results = $r['data']['results'];
    $pages++;
    if (!$results) { $reachedEnd = true; break; }    // an empty page is the end; a short page is not (the server may cap perPage)
    $written += tbWriteRows($results, $seenAt);
    tbLog(sprintf('Page %d: %d events in %.1f s (written so far: %d).', $page, count($results), microtime(true) - $t0, $written));
    $page++;
    if ($mode === 'full') tbStateSet('full_next_page', (string) $page);
}
if (!$reachedEnd && $error === '') $error = 'stopped at the page or time limit; the next run continues';

if (!$reachedEnd) {
    tbStateSet('last_error', gmdate('c') . ' ' . $error);
    tbLog('Stopped: ' . $error . '. Progress is saved.');
    exit(0);   // a throttle or a missing role is a state to report, not a cron failure
}

if ($mode === 'full') {
    $stmt = MYSQLI->prepare('SELECT COUNT(*) FROM tn_bulk_events WHERE seen_at >= ?');
    $stmt->bind_param('s', $seenAt);
    $stmt->execute();
    $stmt->bind_result($seen);
    $stmt->fetch();
    $stmt->close();
    $prev = tbStateGet('last_full_count');
    if (tnBulkMayDelete(true, (int) $seen, $prev !== null ? (int) $prev : null)) {
        $stmt = MYSQLI->prepare('DELETE FROM tn_bulk_events WHERE seen_at < ?');
        $stmt->bind_param('s', $seenAt);
        $stmt->execute();
        tbLog('Removed ' . $stmt->affected_rows . ' events the catalog no longer returns.');
        $stmt->close();
        tbStateSet('last_full_count', (string) $seen);
        tbStateSet('last_full_ok', (string) $now);
        tbStateSet('last_full_start', (string) $runStart);
    } else {
        tbLog("Full run saw {$seen} events against " . ($prev ?? 'no') . ' before: old rows were NOT deleted and the run is not recorded as good.');
        tbStateSet('last_error', gmdate('c') . " full run saw only {$seen} events (previous {$prev}); nothing deleted");
        exit(0);
    }
    tbStateClear('full_run_start', 'full_next_page');
} else {
    tbStateSet('last_incremental_ok', (string) $now);
    tbStateSet('last_incremental_start', (string) $runStart);
}
tbStateClear('last_error');
tbLog("Done: {$mode} run wrote {$written} rows in {$pages} requests.");
