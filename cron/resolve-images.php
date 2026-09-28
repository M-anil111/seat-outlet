<?php
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
 * Schedule every 10-15 minutes. Safe to run concurrently with page traffic;
 * a page that needs an unresolved image serves the fallback and queues it.
 *
 *   php cron/resolve-images.php            default batch (25)
 *   php cron/resolve-images.php 60         larger batch
 */
require_once __DIR__ . '/../functions.php';

$batch = isset($argv[1]) ? max(1, min(200, (int) $argv[1])) : (isset($_GET['batch']) ? max(1, min(100, (int) $_GET['batch'])) : 25);
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
$mysqli = MYSQLI;
$res = $mysqli->query("
    SELECT imgkey, entity_type, entity_name, url, status
      FROM images
     WHERE entity_type IS NOT NULL AND entity_name IS NOT NULL
       AND (status = 'pending' OR (status = 'fallback' AND (expires_at IS NULL OR expires_at <= NOW())))
     ORDER BY (status = 'pending') DESC, updated_at ASC
     LIMIT " . (int) $batch
);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$ok = 0; $miss = 0; $rateLimited = 0;
foreach ($rows as $i => $row) {
    if ($i > 0) usleep($pauseMicros);
    $before = imageRecordGet($row['imgkey']);
    $result = resolveEntityImage($row['entity_type'], $row['entity_name'], [], $row['url'] ?: imageFallbackUrl($row['entity_type']));
    if ($result) { $ok++; continue; }
    $after = imageRecordGet($row['imgkey']);
    // A short expiry means the chain saw a 429; stop hammering for this run.
    if ($after && !empty($after['expires_at']) && strtotime($after['expires_at']) - time() < IMAGE_ERROR_TTL_MINUTES * 60 + 60) {
        $rateLimited++;
        if ($rateLimited >= 3) { echo "rate limited by a source, stopping early\n"; break; }
    }
    $miss++;
}

printf("images: queued %d new, processed %d (resolved %d, no image %d, rate-limited %d) on %s\n",
    $queued, count($rows), $ok, $miss, $rateLimited, date('Y-m-d H:i:s'));
