<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Refresh the event details behind the listed events in small batches, so a visitor rarely waits on a live TicketNetwork call
 * and an event page does not show "temporarily unavailable" for an event the site already lists.
 *
 *   php cron/warm-event-details.php [batch]     default 15, at most 40. Run every 15 minutes.
 *
 * It does nothing while the TicketNetwork circuit breaker is open, so it never adds load to a throttled API.
 */
require_once __DIR__ . '/../functions.php';

if (tnBreakerOpen()) {
    echo "TicketNetwork circuit breaker is open; skipped.\n";
    exit(0);
}

$batch = max(1, min(40, (int) ($argv[1] ?? 15)));

// Event ids from the same listings the site shows (cached, so this costs no extra call).
$ids = [];
foreach (['', TN_CATEGORY_PATH_CONCERTS, TN_CATEGORY_PATH_SPORTS, TN_CATEGORY_PATH_THEATER, TN_CATEGORY_PATH_FESTIVAL] as $path) {
    foreach (tnListRows(getCategoryListingEvents($path, 20, 1)) as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) $ids[$id] = true;
    }
}

$ok = 0;
$failed = 0;
$allIds = array_keys($ids);
$start = $allIds ? ((int) floor(time() / 900) * $batch) % count($allIds) : 0;   // another window each 15-minute run
$window = $allIds ? array_slice(array_merge(array_slice($allIds, $start), $allIds), 0, min($batch, count($allIds))) : [];
foreach ($window as $id) {
    $event = getTnEventById($id);   // a fresh cache answers without a call; a stale one is refreshed
    if (!tnEntityUnavailable($event)) $ok++; else $failed++;
    usleep(500000);   // half a second between calls
    if (tnBreakerOpen()) break;
}

echo 'Listed events: ' . count($ids) . "; checked: " . ($ok + $failed) . "; ok: $ok; unavailable: $failed\n";

// Snapshots of events nobody has seen for 180 days are pruned (their event is long past). Failure here is harmless.
if (tnSnapshotDb()) {
    try {
        MYSQLI->query('DELETE FROM tn_event_snapshot WHERE seen_at < NOW() - INTERVAL 180 DAY');
    } catch (Throwable $e) {
        echo "snapshot prune skipped\n";
    }
}
