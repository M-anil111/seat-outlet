<?php

require_once __DIR__ . '/../inc/request-guard.php';

/** The visitor's address. Forwarding headers are believed only when the request really came through Cloudflare (see soClientIp()). */
function get_client_ip() {
    $ip = soClientIp();
    return $ip === '0.0.0.0' ? 'UNKNOWN' : $ip;
}


require_once __DIR__ . '/../inc/geoip.php';

header('Content-Type: application/json');
header('Cache-Control: private, max-age=86400');

$ip = get_client_ip();
$geo = geoIpLookup($ip);
if (!$geo || $geo['city'] === '') {
    echo json_encode([]);
    exit;
}
echo json_encode(['city' => $geo['city'], 'state' => $geo['state'], 'lat' => $geo['lat'], 'lng' => $geo['lng']]);
exit;
