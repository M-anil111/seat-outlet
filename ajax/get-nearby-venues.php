<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

$solt = $_GET['solt'] ?? '';
$solg = $_GET['solg'] ?? '';

$nearbyVenues = getNearbyVenues($solt, $solg, 6);

$output = [];
if(!empty($nearbyVenues)) {
    foreach ($nearbyVenues as $venue) { 
        $output[] = [
            'slug'  => strtolower($venue['uriComponent'] ?? ''),
            'name'  => $venue['text']['name'] ?? '',
            'city'  => $venue['city']['text']['name'] ?? '',
            'state' => $venue['stateProvince']['text']['abbr'] ?? '',
            'image' => "/images/venue.webp"
        ]; 
    }
}

if (empty($output)) {

    $fallbackKey = "top_venues";
    $fallbackCache = cache_get($fallbackKey);

    if ($fallbackCache !== false) {
        header('Cache-Control: public, max-age=86400');
        header('X-Cache-Fallback: HIT');
        echo json_encode($fallbackCache);
        exit;
    }

}

header('Cache-Control: public, max-age=86400');
header('X-Cache-Status: HIT');
echo json_encode($output);
exit;