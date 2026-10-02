<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

// Coordinates only (never pasted into the API filter as free text), snapped to a 0.1 degree grid for a small cache key space.
$snap = soSnapGeo(soQs('solt'), soQs('solg'));
$nearbyVenues = $snap ? getNearbyVenues($snap[0], $snap[1], 6) : [];

$output = [];
if(!empty($nearbyVenues)) {
    foreach ($nearbyVenues as $venue) { 
        $output[] = [
            'slug'  => strtolower($venue['uriComponent'] ?? ''),
            'name'  => $venue['text']['name'] ?? '',
            'city'  => $venue['city']['text']['name'] ?? '',
            'state' => $venue['stateProvince']['text']['abbr'] ?? '',
            'image' => "/images/venue-480.webp"
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
echo json_encode($output);
exit;