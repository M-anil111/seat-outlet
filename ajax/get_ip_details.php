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

// When the address cannot be placed (not in the database, no data) the answer says so: no invented city. The page then shows nationwide events
// and a "Choose location" prompt. 'source' and 'fallback' let the page and monitoring tell a real lookup from a failed one.
$unknown = ['city' => '', 'state' => '', 'lat' => '', 'lng' => '', 'source' => 'unknown', 'fallback' => true];
$ip = get_client_ip();
$geo = geoIpLookup($ip);
if (!$geo || $geo['city'] === '') {
    header('Cache-Control: no-store');   // a failed lookup must not be remembered
    echo json_encode($unknown);
    exit;
}
echo json_encode(['city' => $geo['city'], 'state' => $geo['state'], 'lat' => $geo['lat'], 'lng' => $geo['lng'], 'source' => 'maxmind', 'fallback' => false]);
exit;
