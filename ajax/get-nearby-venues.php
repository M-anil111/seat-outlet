<?php
require_once '../functions.php';

header('Content-Type: application/json');

$lt = $_COOKIE['so_lat'];
$lg = $_COOKIE['so_lng'];
if(!empty($lt) && !empty($lg)) {
    $loc1Key = strtolower(str_replace(['.', ' '], ['', '_'], $lt));
    $loc2Key = strtolower(str_replace('.', '', $lg));
    $cacheKey = "venues_{$loc1Key}_{$loc2Key}";    
}else{
    $cacheKey = "top_venues";
}

$nearbyVenues = getNearbyVenues();

$result = [];
if(!empty($nearbyVenues)) {
    foreach ($nearbyVenues as $venue) { 

        $venuename = $venue['text']['name'];
        $imageUrl = getVenueImage($venuename);
        if($imageUrl) {
            $result[] = [
                'slug'  => strtolower($venue['uriComponent'] ?? ''),
                'name'  => $venue['text']['name'] ?? '',
                'city'  => $venue['city']['text']['name'] ?? '',
                'state' => $venue['stateProvince']['text']['abbr'] ?? '',
                'image' => $imageUrl
            ];
        }
    }
}

if (empty($result)) {

    $fallbackKey = "top_venues";

    $fallbackCache = cache_get($fallbackKey);

    if ($fallbackCache !== false) {
        header('Cache-Control: public, max-age=86400');
        header('X-Cache-Fallback: HIT');
        echo json_encode($fallbackCache);
        exit;
    }

    /* ==============================
       FETCH TOP VENUES
    ============================== */

    $topVenues = getTopVenues(); // make sure this function exists

    $fallbackResult = [];

    if (!empty($topVenues)) {
        foreach ($topVenues as $venue) {

            $venuename = $venue['text']['name'] ?? '';
            $imageUrl = getVenueImage($venuename);

            if ($imageUrl) {
                $fallbackResult[] = [
                    'slug'  => strtolower($venue['uriComponent'] ?? ''),
                    'name'  => $venuename,
                    'city'  => $venue['city']['text']['name'] ?? '',
                    'state' => $venue['stateProvince']['text']['abbr'] ?? '',
                    'image' => $imageUrl
                ];
            }
        }
    }

    cache_set($fallbackKey, $fallbackResult);

    header('Cache-Control: public, max-age=86400');
    header('X-Cache-Fallback: MISS');
    echo json_encode($fallbackResult);
    exit;
}

cache_set($cacheKey, $result);
header('Cache-Control: public, max-age=86400');
header('X-Cache-Status: HIT');
echo json_encode($result);
exit;