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


header('Content-Type: application/json');

$ip = get_client_ip();

// Was a bare file_get_contents() with no timeout - if ip-api.com is slow or
// unreachable, the whole page load waited on this third-party geolocation
// call (used for the homepage's "detect my location" feature) for however
// long PHP's default socket timeout happens to be. A short explicit
// timeout bounds the worst case instead of stalling the page.
// Note: ip-api.com's free tier only supports HTTP, not HTTPS - that's a
// paid-plan feature on their end, not something to silently "fix" here.
$context = stream_context_create(['http' => ['timeout' => 3]]);
$ip_response = @file_get_contents("http://ip-api.com/json/{$ip}", false, $context);

if ($ip_response === false) {
    echo json_encode([]);
    exit;
}

$ipData = json_decode($ip_response, true);

if (empty($ipData) || $ipData['status'] !== 'success') {
    echo json_encode([]);
    exit;
}

echo json_encode([
    'city'  => $ipData['city'] ?? '',
    'state' => $ipData['region'] ?? '',
    'lat'  => $ipData['lat'] ?? '',
    'lng' => $ipData['lon'] ?? '',
]);

exit;