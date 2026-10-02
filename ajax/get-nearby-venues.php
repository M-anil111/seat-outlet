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
            'image' => "/images/venue-480.webp"
        ]; 
    }
}

// Venues near the visitor: cache them. Nothing nearby: fall back to the saved top-venue list (said so in X-So-Venue-Scope, so the
// page does not call them "near you"). Nothing at all: answer an empty list that is NEVER cached (the page hides the block),
// so a missing top-venue cache or a failed lookup cannot be kept for a day by the browser or a CDN.
if (empty($output)) {
    $fallbackCache = cache_get("top_venues");
    if (is_array($fallbackCache) && !empty($fallbackCache)) {
        header('Cache-Control: public, max-age=3600');
        header('X-So-Venue-Scope: top');
        header('X-Cache-Fallback: HIT');
        echo json_encode($fallbackCache);
        exit;
    }
    header('Cache-Control: no-store');
    header('X-So-Venue-Scope: none');
    echo json_encode([]);
    exit;
}

header('Cache-Control: public, max-age=86400');
header('X-So-Venue-Scope: near');
echo json_encode($output);
exit;
