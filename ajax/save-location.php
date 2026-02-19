<?php

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Read JSON body
$data = json_decode(file_get_contents('php://input'), true);

if (
    empty($data['lat']) ||
    empty($data['lng']) ||
    !is_numeric($data['lat']) ||
    !is_numeric($data['lng'])
) {
    http_response_code(400);
    exit;
}

// Sanitize & cast
$latitude  = (float) $data['lat'];
$longitude = (float) $data['lng'];

// Basic validation range check
if (
    $latitude < -90 || $latitude > 90 ||
    $longitude < -180 || $longitude > 180
) {
    http_response_code(400);
    exit;
}

/*
 OPTIONAL:
 Reverse geocode to get city/state
 You can use your existing getLocationFromInput()
 or OpenStreetMap reverse API here.
*/

// Example: just store lat/lng only
$locationData = [
    'latitude'  => $latitude,
    'longitude' => $longitude
];

// Encode & store in cookie
setcookie(
    'so_location',
    base64_encode(json_encode($locationData)),
    time() + (60 * 60 * 24 * 30), // 30 days
    '/',
    '',
    isset($_SERVER['HTTPS']),
    true
);

echo json_encode(['status' => 'success']);
exit;