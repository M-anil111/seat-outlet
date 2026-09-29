<?php

function get_client_ip() {
    $ipaddress = '';

    // Check for Cloudflare specific header
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $ipaddress = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    // Check if the user is from a shared internet service
    elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check if the user is behind a proxy
    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR can contain a comma-separated list of IPs,
        // the client's IP is typically the first one.
        $forward_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ipaddress = trim($forward_list[0]);
    }
    // Fallback to the most reliable address provided by the web server
    else {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    }
    
    // Optional: Validate the IP address for correct format
    if (filter_var($ipaddress, FILTER_VALIDATE_IP) === false) {
        return "UNKNOWN";
    }

    return $ipaddress;
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
