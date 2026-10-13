<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the durable event snapshot (migration 0048): the table exists, the lookup falls back to it, saves are throttled and
 * never break a page, and the nightly backup covers the database that holds it. Run: php tools/test-tn-snapshot.php
 */
$fails = 0;
function tsCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$mig = file_get_contents(__DIR__ . '/../db/migrations/0048_tn_event_snapshot.sql');
tsCheck('migration 0048 creates tn_event_snapshot with a primary key on event_id', strpos($mig, 'CREATE TABLE IF NOT EXISTS `tn_event_snapshot`') !== false && strpos($mig, 'PRIMARY KEY (`event_id`)') !== false);
tsCheck('migration 0048 is add-only (no DROP, no ALTER of existing tables)', stripos($mig, 'DROP ') === false && stripos($mig, 'ALTER TABLE') === false);

$fn = file_get_contents(__DIR__ . '/../functions.php');
tsCheck('a live event answer is saved to the snapshot', strpos($fn, "tnSnapshotSave(\$eventId, \$event);   // a live answer") !== false);
tsCheck('the fallback order is file copy, then snapshot, then the honest failure', (function () use ($fn) {
    $a = strpos($fn, "cache_get('tn_event_copy_' . \$eventId, TN_LISTING_COPY_TTL)");
    $b = strpos($fn, 'tnSnapshotGet($eventId, TN_LISTING_COPY_TTL)');
    $c = strpos($fn, 'return $event;', $b === false ? 0 : $b);
    return $a !== false && $b !== false && $c !== false && $a < $b && $b < $c;
})());
tsCheck('snapshot writes are throttled to one an hour per event, storing a value the reader accepts', strpos($fn, "cache_get(\$throttle, 3600) !== false") !== false && strpos($fn, "cache_set(\$throttle, ['seen' => 1])") !== false);
$body = function (string $sig) use ($fn) {   // one function's source, from its signature to the closing brace in column 0
    $s = strpos($fn, $sig);
    $e = $s === false ? false : strpos($fn, "\n}\n", $s);
    return ($s === false || $e === false) ? '' : substr($fn, $s, $e - $s);
};
tsCheck('tnSnapshotSave catches its own errors (a database fault never breaks a page)', substr_count($body('function tnSnapshotSave'), 'catch (Throwable $e)') === 1);
tsCheck('tnSnapshotGet catches its own errors (a database fault never breaks a page)', substr_count($body('function tnSnapshotGet'), 'catch (Throwable $e)') === 1);
tsCheck('snapshot checks the database handle before using it', strpos($fn, 'function tnSnapshotDb(): bool {') !== false && strpos($fn, 'return defined(\'MYSQLI\') && MYSQLI instanceof mysqli;') !== false);
tsCheck('listing answers also refresh the snapshot', strpos($fn, '        tnSnapshotSave($id, $row);') !== false);
tsCheck('a snapshot older than the copy window is not used', strpos($fn, 'AND seen_at >= NOW() - INTERVAL ? SECOND') !== false);
tsCheck('a confirmed "not found" deletes the stored copies (file and snapshot)', strpos($fn, 'function tnForgetEventCopies') !== false && strpos($fn, 'tnEntityDefinitelyMissing($event)) { tnForgetEventCopies($eventId); return $event; }') !== false && strpos($fn, "DELETE FROM tn_event_snapshot WHERE event_id = ?") !== false);

$cron = file_get_contents(__DIR__ . '/../cron/warm-event-details.php');
tsCheck('the warm-up job prunes snapshots unseen for 180 days', strpos($cron, 'INTERVAL 180 DAY') !== false);

$bk = file_get_contents(__DIR__ . '/../cron/backup-db.php');
tsCheck('the nightly backup dumps the whole database (the snapshot is in it)', strpos($bk, 'mysqldump') !== false && strpos($bk, 'tn_event_snapshot') === false);

echo $fails ? "\n$fails failed\n" : "\ntn snapshot: all passed\n";
exit($fails ? 1 : 0);
