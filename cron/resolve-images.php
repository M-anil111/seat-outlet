<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Resolve entity images off the request path.
 *
 *   1. Pre-warm: queue every performer/venue in the homepage caches
 *      (top_performers, home_events_*, top_venues) so the pages that get the
 *      most traffic never wait on a third-party lookup.
 *   2. Work the queue: rows with status=pending, then fallbacks whose
 *      expires_at has passed, oldest first, in a bounded batch with a pause
 *      between entities so we stay far under Wikimedia's 200 req/min and
 *      TheSportsDB's 30 req/min free-tier limits.
 *
 * Schedule every 10 minutes. If it is never scheduled, the site still works the queue itself in small batches after
 * page responses (imageWorkerMaybeRun in inc/images.php), just much more slowly. Safe to run concurrently with page traffic;
 * a page that needs an unresolved image serves the fallback and queues it.
 *
 *   php cron/resolve-images.php            default batch (60)
 *   php cron/resolve-images.php 60         larger batch
 */
require_once __DIR__ . '/../functions.php';

$batch = isset($argv[1]) ? max(1, min(200, (int) $argv[1])) : (isset($_GET['batch']) ? max(1, min(100, (int) $_GET['batch'])) : 60);
$pauseMicros = 1500000; // 1.5s between entities (each may cost 2-3 requests)

/* ---- 1. pre-warm from the homepage feeds ---- */
$queued = 0;
$top = cache_get('top_performers', 30 * 86400) ?: [];
foreach (['concerts' => 'artist', 'sports' => 'team', 'theater' => 'artist'] as $bucket => $type) {
    foreach ($top[$bucket] ?? [] as $p) {
        if (!empty($p['name']) && imageQueue($type, $p['name'], imageFallbackUrl($type, [], $bucket))) $queued++;
    }
}
foreach (['concerts', 'sports', 'theatre', 'festival'] as $tab) {
    foreach (cache_get('home_events_' . $tab, 30 * 86400) ?: [] as $ev) {
        if (empty($ev['performer'])) continue;
        $type = imageEntityTypeForPerformer($ev['defaultCategory'] ?? []);
        if (imageQueue($type, $ev['performer'], $ev['placeholder'] ?? imageFallbackUrl($type, $ev['defaultCategory'] ?? [], $tab))) $queued++;
    }
}
foreach (cache_get('top_venues', 30 * 86400) ?: [] as $v) {
    if (!empty($v['name']) && imageQueue('venue', $v['name'], imageFallbackUrl('venue'))) $queued++;
}

/* ---- 2. work the queue ---- */
$r = imageWorkQueue($batch, $pauseMicros);
if ($r['rateLimited'] >= 3) echo "rate limited by a source, stopping early\n";

printf("images: queued %d new, processed %d (resolved %d, no image %d, rate-limited %d) on %s\n",
    $queued, $r['processed'], $r['resolved'], $r['miss'], $r['rateLimited'], date('Y-m-d H:i:s'));
