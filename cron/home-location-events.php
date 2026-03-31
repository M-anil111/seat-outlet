<?php
require_once '../functions.php';

$cacheDir = '../cache/';
$files = scandir($cacheDir);

foreach ($files as $file) {

    if ($file === '.' || $file === '..') continue;

    // Only event files
    if (strpos($file, 'home_loc_events_') === false) continue;

    $filePath = $cacheDir . $file;

    // ⏱ Skip if recently updated (1 hour)
    if (time() - filemtime($filePath) < 3600) continue;

    echo "Processing Event File: $file\n";

    $name = str_replace('.json', '', $file);
    $parts = explode('_', $name);

    // Extract values
    $tab  = $parts[3] ?? '';
    $type = $parts[4] ?? '';

    $latRaw = $parts[count($parts) - 2];
    $lngRaw = $parts[count($parts) - 1];

    $lat = convertToFloat($latRaw);
    $lng = convertToFloat($lngRaw);

//    echo "Tab: $tab | Type: $type | Lat: $lat | Lng: $lng\n";

    // API URL
    $url = HOME_URL . "/ajax/get-location-category-events.php?tab={$tab}&type={$type}&loc1={$lat}&loc2={$lng}";

    $response = file_get_contents($url);

    if ($response) {
        file_put_contents($filePath, $response);
        echo "Updated: $file\n";
    } else {
        echo "Failed: $file\n";
    }

    echo "--------------------------\n";
}