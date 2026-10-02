<?php
/**
 * Receives the page-speed measurements js/vitals.js sends from real visitors (navigator.sendBeacon).
 *
 * Stores only: page type, metric, value, rating, device class and connection type. No IP, cookie or URL is
 * kept (db/migrations/0009_web_vitals.sql). Everything is validated against allow-lists and ranges, the body is
 * capped, and a visitor can send a limited number of reports per minute. It always answers 204 with no body, so
 * a missing table (migrations not applied yet) or a rejected report never shows up as an error in the browser.
 */
require_once __DIR__ . '/../db/config.php';

header('Cache-Control: no-store');
http_response_code(204);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    return;
}

const VITALS_METRICS = ['LCP' => [0, 120000], 'CLS' => [0, 10], 'INP' => [0, 60000], 'FCP' => [0, 120000], 'TTFB' => [0, 120000]];
const VITALS_PAGE_TYPES = ['home', 'tickets', 'concerts', 'sports', 'theater', 'festival', 'artist', 'event', 'city', 'venue',
    'location', 'category', 'search', 'checkout', 'confirmation', 'blog', 'static'];
const VITALS_RATINGS = ['good', 'needs-improvement', 'poor'];
const VITALS_MAX_PER_MINUTE = 20;

$raw = file_get_contents('php://input', false, null, 0, 2049);
if ($raw === false || $raw === '' || strlen($raw) > 2048) {
    return;
}
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['m']) || !is_array($data['m'])) {
    return;
}

// Per-visitor throttle without storing the address: a counter file keyed by a salted hash that changes every minute.
require_once __DIR__ . '/../inc/request-guard.php';
$ip = soClientIp();   // CF-Connecting-IP only when the request came from Cloudflare, so the files below cannot be multiplied by forged headers
if (mt_rand(1, 100) === 1) {   // counters only matter for the current minute: drop the old ones
    foreach (glob(sys_get_temp_dir() . '/so_vitals_*') ?: [] as $oldBucket) { if (time() - (int) @filemtime($oldBucket) > 180) @unlink($oldBucket); }
}
$bucket = sys_get_temp_dir() . '/so_vitals_' . substr(hash('sha256', $ip . '|' . gmdate('YmdHi') . '|' . DB_NAME), 0, 24);
$count = is_file($bucket) ? (int) @file_get_contents($bucket) : 0;
if ($count >= VITALS_MAX_PER_MINUTE) {
    return;
}
@file_put_contents($bucket, (string) ($count + 1), LOCK_EX);

$pageType = (string) ($data['p'] ?? '');
$device = ($data['d'] ?? '') === 'desktop' ? 'desktop' : 'mobile';
$conn = preg_match('/^(slow-2g|2g|3g|4g)$/', (string) ($data['c'] ?? '')) ? (string) $data['c'] : '';
if (!in_array($pageType, VITALS_PAGE_TYPES, true)) {
    return;
}

$rows = [];
foreach (array_slice($data['m'], 0, 6, true) as $name => $m) {
    if (!isset(VITALS_METRICS[$name]) || !is_array($m)) continue;
    $value = $m['v'] ?? null;
    $rating = (string) ($m['r'] ?? '');
    if (!is_numeric($value) || !in_array($rating, VITALS_RATINGS, true)) continue;
    [$min, $max] = VITALS_METRICS[$name];
    if ($value < $min || $value > $max) continue;
    $rows[] = [$name, (float) $value, $rating];
}
if (!$rows) {
    return;
}

try {
    $stmt = $mysqli->prepare('INSERT INTO web_vitals (page_type, metric, metric_value, rating, device, connection_type) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($rows as [$metric, $value, $rating]) {
        $stmt->bind_param('ssdsss', $pageType, $metric, $value, $rating, $device, $conn);
        $stmt->execute();
    }
    $stmt->close();
} catch (Throwable $e) {
    // table missing or database busy: dropping a measurement is fine
}
