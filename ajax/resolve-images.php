<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Looks up pictures for the "Fans also love" cards that had none stored when the page rendered.
// Safe by design: it only works on entities the page itself already queued in the images table (so it cannot be
// used to look up arbitrary names), resolves at most 4 per request, and at most one request every 3 seconds per
// server, because each lookup calls third-party image sources (bounded by the timeouts in inc/images.php).
$body  = json_decode((string) file_get_contents('php://input'), true);
$items = is_array($body['items'] ?? null) ? array_slice($body['items'], 0, 8) : [];

$gate = rtrim(sys_get_temp_dir(), '/') . '/so_resolve_images_gate';
$last = is_file($gate) ? (int) @file_get_contents($gate) : 0;
$canResolve = (time() - $last) >= 3;
if ($canResolve) { @file_put_contents($gate, (string) time()); }

$out = []; $resolved = 0;
foreach ($items as $item) {
    $name = trim((string) ($item['name'] ?? ''));
    $type = in_array($item['type'] ?? '', ['artist', 'team', 'festival'], true) ? $item['type'] : 'artist';
    $url  = '';
    if ($name !== '') {
        $row = imageRecordGet(imageEntityKey($type, $name));
        if ($row && in_array($row['status'], ['ok', 'manual'], true) && $row['url'] !== '') {
            $url = (string) $row['url'];
        } elseif ($row && $canResolve && $resolved < 4 && ($row['status'] === 'pending' || (!empty($row['expires_at']) && strtotime($row['expires_at']) <= time()))) {
            $resolved++;
            try {
                $r = resolveEntityImage($type, $name, [], (string) ($row['url'] ?? ''));
                if ($r && in_array($r['status'] ?? '', ['ok', 'manual'], true)) { $url = (string) $r['url']; }
            } catch (\Throwable $e) {
                error_log('resolve-images failed (' . $type . '/' . $name . '): ' . $e->getMessage());
            }
        }
    }
    $out[] = $url;
}
echo json_encode(['images' => $out]);
