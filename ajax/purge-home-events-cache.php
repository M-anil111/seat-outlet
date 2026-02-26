<?php
require_once '../functions.php';

$token = $_GET['token'] ?? '';

if ($token !== 'YOUR_SECRET_TOKEN') {
    http_response_code(403);
    exit('Forbidden');
}

$files = glob(__DIR__ . '/../cache/' . md5('home_events_') . '*.json');

foreach (glob(__DIR__ . '/../cache/*.json') as $file) {
    $json = file_get_contents($file);
    if ($json === false) continue;

    // optional: keep it simple, just wipe all cache files
    unlink($file);
}

echo "home events cache cleared";
exit;