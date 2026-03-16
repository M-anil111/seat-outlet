<?php
require_once '../functions.php';

$nearbyVenues = getNearbyVenues();

$result = [];
if(!empty($nearbyVenues)) {
    foreach ($nearbyVenues as $venue) { 

        $venuename = $venue['text']['name'];
        $imageUrl = getWikimediaImage($venuename);
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
}else{
    $nearbyVenuesFallback = getNearbyVenuesFallback();
    if(!empty($nearbyVenuesFallback)) {
        foreach ($nearbyVenuesFallback as $fvenue) { 
    
            $venuename = $fvenue['text']['name'];
            $imageUrl = getWikimediaImage($venuename);
            if($imageUrl) {
                $result[] = [
                    'slug'  => strtolower($fvenue['uriComponent'] ?? ''),
                    'name'  => $fvenue['text']['name'] ?? '',
                    'city'  => $fvenue['city']['text']['name'] ?? '',
                    'state' => $fvenue['stateProvince']['text']['abbr'] ?? '',
                    'image' => $imageUrl
                ];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($result);
exit;