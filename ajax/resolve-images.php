<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Looks up pictures for the "Fans also love" cards that had none stored when the page rendered.
// Safe by design: it only reads entities that already have a row in the images table, resolves at most 4 per request,
// and each visitor (by IP) may trigger a lookup at most once every 3 seconds, with a ceiling of 20 lookups a minute across
// the whole site, because each lookup calls third-party image sources (bounded by the timeouts in inc/images.php). The cron /
// background worker is the main path; this only speeds up the first view. Answers the picture AND its credit (licence, source)
// so the card can show the attribution the licence requires.
$body  = json_decode((string) file_get_contents('php://input'), true);
$items = is_array($body['items'] ?? null) ? array_slice($body['items'], 0, 8) : [];

$tmp  = rtrim(sys_get_temp_dir(), '/');
$ip   = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$gate = $tmp . '/so_resolve_images_gate_' . md5($ip);
$last = is_file($gate) ? (int) @file_get_contents($gate) : 0;
$canResolve = (time() - $last) >= 3;
if ($canResolve) {
    // site-wide ceiling: one counter file per minute
    $minute = $tmp . '/so_resolve_images_min_' . date('YmdHi');
    $n = is_file($minute) ? (int) @file_get_contents($minute) : 0;
    if ($n >= 20) { $canResolve = false; } else { @file_put_contents($minute, (string) ($n + 1)); @file_put_contents($gate, (string) time()); }
}

$out = []; $credits = []; $resolved = 0;
foreach ($items as $item) {
    if (!is_array($item)) { $out[] = ''; continue; }
    $name = is_string($item['name'] ?? null) ? trim($item['name']) : '';
    $type = in_array($item['type'] ?? '', ['artist', 'team', 'festival', 'venue'], true) ? $item['type'] : 'artist';
    $url  = ''; $credit = null;
    if ($name !== '') {
        $row = imageRecordGet(imageEntityKey($type, $name));
        if ($row && in_array($row['status'], ['ok', 'manual'], true) && $row['url'] !== '') {
            $url = (string) $row['url']; $credit = imageRowToResult($row);
        } elseif ($row && $canResolve && $resolved < 4 && ($row['status'] === 'pending' || (!empty($row['expires_at']) && strtotime($row['expires_at']) <= time()))) {
            $resolved++;
            try {
                $r = resolveEntityImage($type, $name, [], (string) ($row['url'] ?? ''));
                if ($r && in_array($r['status'] ?? '', ['ok', 'manual'], true)) { $url = (string) $r['url']; $credit = imageRowToResult($r); }
            } catch (\Throwable $e) {
                error_log('resolve-images failed (' . $type . '/' . $name . '): ' . $e->getMessage());
            }
        }
    }
    $out[] = $url;
    $credits[] = $credit ? ['text' => imageCreditShort($credit), 'full' => (string) $credit['credit'], 'license' => (string) $credit['license'], 'source' => (string) $credit['source_url']] : null;
}
echo json_encode(['images' => $out, 'credits' => $credits]);
