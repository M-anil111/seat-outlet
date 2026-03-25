<?php
require_once '../functions.php';

$topVenues = getTopVenues();

$output = [];    

if (!empty($topVenues)) {
    foreach ($topVenues as $venue) {
        
        $venuename = $venue['text']['name'] ?? '';
        $imageUrl = getVenueImage($venuename);

        if ($imageUrl) {
            $output[] = [
                'slug'  => strtolower($venue['uriComponent'] ?? ''),
                'name'  => $venuename,
                'city'  => $venue['city']['text']['name'] ?? '',
                'state' => $venue['stateProvince']['text']['abbr'] ?? '',
                'image' => $imageUrl
            ];
        }
    }
}

$cacheKey = "top_venues";
cache_set($cacheKey, $output);
    
echo "home venues cache refreshed on " . date('Y-m-d H:i:s');