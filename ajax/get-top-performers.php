<?php
/**
 * The home page "top performers" cards (built by inc/top-performers.php): five performers per category with a picture when one is stored
 * (otherwise the page shows an initials tile) and the lowest listed price per category. Served from cache/ through this endpoint so the
 * web server can block /cache/.
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');
$data = cache_get('top_performers', 30 * 86400);
if ($data === false) {
    header('Cache-Control: no-store');
    echo '{}';
    exit;
}
foreach (['concerts' => 'artist', 'sports' => 'team', 'theater' => 'artist'] as $key => $type) {
    foreach ($data[$key] ?? [] as $i => $p) {
        $img = ['status' => ''];
        try { $img = getEntityImage($type, (string) ($p['name'] ?? ''), ['resolve' => false]); } catch (Throwable $e) { /* decoration only */ }
        $data[$key][$i]['img'] = soImageIsReal($img) ? (string) $img['url'] : '';
    }
}
header('Cache-Control: public, max-age=900');
echo json_encode($data);
