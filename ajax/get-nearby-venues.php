<?php
require_once '../functions.php';

header('Content-Type: application/json');

$solt = $_GET['solt'];
$solg = $_GET['solg'];
if(!empty($solt) && !empty($solg)) {
    $loc1Key = str_replace('.', '-', $solt);
    $loc2Key = str_replace('.', '-', $solg);
    $cacheKey = "venues_{$loc1Key}_{$loc2Key}";    
}else{
    $cacheKey = "top_venues";
}

$cached = cache_get($cacheKey);

if ($cached) {
    header('Cache-Control: public, max-age=86400');
    header('X-Cache: HIT');
    echo json_encode($cached);
    exit;
}

$nearbyVenues = getNearbyVenues($solt, $solg, 8);

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

}

cache_set($cacheKey, $result);
header('Cache-Control: public, max-age=86400');
header('X-Cache-Status: HIT');
echo json_encode($result);
exit;