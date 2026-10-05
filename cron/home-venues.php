<?php
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../functions.php';

$topVenues = getTopVenues(6);

$output = [];    

if (!empty($topVenues)) {
    foreach ($topVenues as $venue) {        
        $output[] = [
            'slug'  => soVenueSlug((string) ($venue['text']['name'] ?? ''), (int) ($venue['id'] ?? 0), trim((string) ($venue['city']['text']['name'] ?? '') . ', ' . (string) ($venue['stateProvince']['text']['abbr'] ?? ''), ', ')),
            'name'  => $venue['text']['name'] ?? '',
            'city'  => $venue['city']['text']['name'] ?? '',
            'state' => $venue['stateProvince']['text']['abbr'] ?? '',
            'image' => "/images/venue-480.webp"
        ];       
    }
}

$cacheKey = "top_venues";
cache_set($cacheKey, $output);
    
echo "home venues cache refreshed on " . date('Y-m-d H:i:s');