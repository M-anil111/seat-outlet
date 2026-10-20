<?php
/**
 * Read-only status page for the people running the site: which commit is live, whether the database schema is current, and how the
 * picture queue is doing. Counts and yes/no only: no names, no keys, no visitor data.
 *
 *   GET /ajax/health.php
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/tn-bulk.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$out = ['ok' => true, 'time' => gmdate('c')];
$stamp = @file_get_contents(__DIR__ . '/../.deployed-commit');
$out['commit'] = $stamp !== false ? substr(trim($stamp), 0, 8) : null;
$out['commitAgeSeconds'] = $stamp !== false ? max(0, time() - (int) @filemtime(__DIR__ . '/../.deployed-commit')) : null;   // null commit = the deploy marker is not in this folder: the deploy job writes somewhere else or has never run

try {
    $files = glob(__DIR__ . '/../db/migrations/*.sql') ?: [];
    $names = array_map(function ($f) { return basename($f); }, $files);
    $applied = [];
    $res = @MYSQLI->query('SELECT migration FROM schema_migrations');
    if ($res) { while ($r = $res->fetch_row()) { $applied[$r[0]] = true; } }
    $pending = array_values(array_filter($names, function ($n) use ($applied) { return !isset($applied[$n]); }));
    $out['migrations'] = ['files' => count($names), 'applied' => count($applied), 'pending' => count($pending), 'pendingNames' => array_slice($pending, 0, 10)];
    if ($pending) $out['ok'] = false;
    $ml = json_decode((string) @file_get_contents(__DIR__ . '/../cache/migrate_last.json'), true);
    if (is_array($ml)) $out['migrations']['lastAutoRun'] = ['secondsAgo' => time() - (int) ($ml['at'] ?? 0), 'applied' => $ml['applied'] ?? [], 'error' => $ml['error'] ?? null];
    $out['migrations']['autoMigrate'] = soAutoMigrateEnabled();
} catch (Throwable $e) {
    $out['migrations'] = ['error' => 'could not read'];
    $out['ok'] = false;
}

// Today's TicketNetwork outcomes from visitor requests (counts only): live calls, stale answers, event copies, no-data pages, trips.
$tnStats = tnStatsToday();   // read under a shared lock, so it never catches tnCount() mid-rewrite
// The live call rate this server uses (TN_CALLS_PER_SECOND or the default) and whether each family's circuit is open right now.
$out['ticketnetwork'] = [
    'today' => $tnStats,
    'callsPerSecond' => tnCallsPerSecond(),
    'circuitOpen' => ['events' => tnBreakerOpen('events'), 'reference' => tnBreakerOpen('reference')],
];
// Per-page shares for today: pages that got no TicketNetwork answer, pages that kept a stored list over an empty live answer, and
// whether either is worth a look (see tnStatsSummary()).
$tnSummary = tnStatsSummary($tnStats);
$out['ticketnetwork']['pages'] = $tnSummary['pages'];
$out['ticketnetwork']['noDataRate'] = $tnSummary['pages'] > 0 ? $tnSummary['rate'] : null;
// The same rate for visitors only (crawlers and beta-address hits left out): this is the one the alert uses, because a crawler held to its
// share of the call budget is expected to get a stored copy or a 503.
$out['ticketnetwork']['visitorPages'] = $tnSummary['visitorPages'];
$out['ticketnetwork']['visitorNoDataRate'] = $tnSummary['visitorPages'] > 0 ? $tnSummary['visitorRate'] : null;
// Of the visitor pages, those from requests that look like a browser (Accept-Language plus Sec-Fetch-Mode or a cookie) against those that
// do not (probably scripts the crawler list does not know). likelyHumanNoDataRate is the one about real people.
$out['ticketnetwork']['likelyHumanPages'] = $tnSummary['likelyHumanPages'];
$out['ticketnetwork']['likelyHumanNoDataRate'] = $tnSummary['likelyHumanPages'] > 0 ? $tnSummary['likelyHumanRate'] : null;
$out['ticketnetwork']['nonBrowserPages'] = $tnSummary['nonBrowserPages'];
$out['ticketnetwork']['nonBrowserLiveCalls'] = $tnSummary['nonBrowserLiveCalls'];
$out['ticketnetwork']['refresh'] = $tnSummary['refresh'];   // stale-while-revalidate refreshes: made (took a budget token) and skipped (no token)
$out['ticketnetwork']['emptySuspectRate'] = $tnSummary['pages'] > 0 ? $tnSummary['suspectRate'] : null;
// Per call: 429s from TicketNetwork against calls our own TN_CALLS_PER_SECOND budget held back. A high overBudget with few throttled
// means the local rate is the bottleneck; the reverse means the API's limit is lower than the rate we send.
$out['ticketnetwork']['throttled'] = $tnSummary['throttled'];
$out['ticketnetwork']['overBudget'] = $tnSummary['overBudget'];
// Who the demand is: crawlers and hits on the beta address (not through the live Worker) as shares of today's pages, live calls and
// no-data pages, and whether THIS request came through the Worker (curl the health URL on each hostname to see the difference).
$out['ticketnetwork']['demand'] = $tnSummary['demand'];
// Why pages had no data today, for visitors and for crawlers: breaker (circuit open), budget (our own call limit), throttled (HTTP 429),
// api_error (timeout or 5xx), coalesced (waited on another request's call and got nothing), unknown.
$out['ticketnetwork']['noDataWhy'] = $tnSummary['noDataWhy'];
$out['ticketnetwork']['viaProxy'] = soViaLiveProxy();
$out['ticketnetwork']['alert'] = $tnSummary['alert'];
if ($tnSummary['alert']) $out['ok'] = false;
// The local event catalog filled by cron/tn-bulk-import.php (off until TN_BULK_ENABLED=1): how old the last good full refresh and
// changes pull are, how many events the last full refresh saw, and why the last run stopped. Information only; it never fails the check.
try {
    $bulk = ['enabled' => tnBulkEnabled()];
    if ($bulk['enabled']) {
        $st = [];
        $res = @MYSQLI->query('SELECT name, value FROM tn_bulk_state');
        if ($res) { while ($r = $res->fetch_row()) { $st[$r[0]] = $r[1]; } }
        $age = function ($k) use ($st) { return isset($st[$k]) ? max(0, time() - (int) $st[$k]) : null; };
        $bulk['fullAgeSeconds'] = $age('last_full_ok');
        $bulk['changesAgeSeconds'] = $age('last_incremental_ok');
        $bulk['events'] = isset($st['last_full_count']) ? (int) $st['last_full_count'] : null;
        $bulk['lastError'] = isset($st['last_error']) ? substr($st['last_error'], 0, 160) : null;
    }
    $out['ticketnetwork']['bulk'] = $bulk;
} catch (Throwable $e) {
    $out['ticketnetwork']['bulk'] = ['error' => 'could not read'];
}

try {
    $img = ['byStatus' => []];
    $res = @MYSQLI->query('SELECT status, COUNT(*) FROM images GROUP BY status');
    if ($res) { while ($r = $res->fetch_row()) { $img['byStatus'][$r[0]] = (int) $r[1]; } }
    else { $img['error'] = 'images table not readable'; }
    $stampFile = __DIR__ . '/../cache/image_worker.stamp';
    $img['workerLastRunSecondsAgo'] = is_file($stampFile) ? time() - (int) filemtime($stampFile) : null;
    $last = json_decode((string) @file_get_contents(__DIR__ . '/../cache/image_worker_last.json'), true);
    if (is_array($last)) $img['lastRun'] = $last + ['secondsAgo' => time() - (int) ($last['at'] ?? 0)];
    $img['cacheWritable'] = is_writable(__DIR__ . '/../cache');
    $img['fastcgi'] = function_exists('fastcgi_finish_request');
    $img['webWorkerEnabled'] = getenv('IMAGE_WEB_WORKER') !== '0';
    $out['images'] = $img;
} catch (Throwable $e) {
    $out['images'] = ['error' => 'could not read'];
}
echo json_encode($out, JSON_UNESCAPED_SLASHES);
