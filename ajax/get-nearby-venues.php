<?php
require_once '../functions.php';

$lat = 33.6973;
$lng = -117.9087;

$nearbyVenues = getNearbyVenues($lat, $lng);

$result = [];

foreach ($nearbyVenues as $venue) {

    $result[] = [
        'slug'  => strtolower($venue['uriComponent'] ?? ''),
        'name'  => $venue['text']['name'] ?? '',
        'city'  => $venue['city']['text']['name'] ?? '',
        'state' => $venue['stateProvince']['text']['abbr'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode($result);
exit;