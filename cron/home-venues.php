<?php
require_once '../functions.php';

$topVenues = getTopVenues(6);

$output = [];    

if (!empty($topVenues)) {
    foreach ($topVenues as $venue) {        
        $output[] = [
            'slug'  => strtolower($venue['uriComponent'] ?? ''),
            'name'  => $venue['text']['name'] ?? '',
            'city'  => $venue['city']['text']['name'] ?? '',
            'state' => $venue['stateProvince']['text']['abbr'] ?? '',
            'image' => "/assets/venue.webp"
        ];       
    }
}

$cacheKey = "top_venues";
cache_set($cacheKey, $output);
    
echo "home venues cache refreshed on " . date('Y-m-d H:i:s');