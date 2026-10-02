<?php
/**
 * The homepage "top performers" lists (built hourly by cron/home-top-performers.php). The pages used to fetch
 * /cache/top_performers.json directly, which forced the whole cache/ folder to stay downloadable; this endpoint serves the
 * same data so the web server can block /cache/.
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');
$data = cache_get('top_performers', 30 * 86400);
if ($data === false) {
    header('Cache-Control: no-store');
    echo '{}';
    exit;
}
header('Cache-Control: public, max-age=900');
echo json_encode($data);
