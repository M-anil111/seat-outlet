<?php
require_once '../functions.php';

$cacheDir = '../cache/';
$files = scandir($cacheDir);

foreach ($files as $file) {

    if ($file === '.' || $file === '..') continue;

    // Only venue files
    if (strpos($file, 'venues_') === false) continue;

    $filePath = $cacheDir . $file;

    // ⏱ Skip if recently updated (1 hour)
    if (time() - filemtime($filePath) < 3600) continue;

    echo "Processing Venue File: $file\n";

    $name = str_replace('.json', '', $file);
    $parts = explode('_', $name);

    // Extract lat/lng
    $latRaw = $parts[1] ?? '';
    $lngRaw = $parts[2] ?? '';

    $lat = convertToFloat($latRaw);
    $lng = convertToFloat($lngRaw);

    //echo "Lat: $lat | Lng: $lng\n";exit;

    // API URL
    $url = HOME_URL . "/ajax/get-nearby-venues.php?solt={$lat}&solg={$lng}";

    $response = file_get_contents($url);

    if ($response) {
        file_put_contents($filePath, $response);
        echo "Updated: $file\n";
    } else {
        echo "Failed: $file\n";
    }

    echo "--------------------------\n";
}