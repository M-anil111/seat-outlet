<?php
require_once '../functions.php';

header('Content-Type: application/json');

$event     = trim($_GET['event'] ?? '');
$artist    = trim($_GET['artist'] ?? '');
$venue     = trim($_GET['venue'] ?? '');
$tab       = trim($_GET['tab'] ?? '');
$category  = $_GET['category'] ?? '';

$defaultCategory = [];

if (!empty($category)) {
    $decoded = json_decode($category, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $defaultCategory = $decoded;
    }
}

$type = '';
$cacheKeyBase = '';

if (!empty($event) && !empty($tab)) {
    $type = 'event';
    $cacheKeyBase = $tab . '|' . $event . '|' . $artist;

} elseif (!empty($venue)) {
    $type = 'venue';
    $cacheKeyBase = 'venue|' . $venue;

} else {
    echo json_encode([
        'success' => false,
        'image'   => ''
    ]);
    exit;
}

$imageCacheKey = 'so_img_' . md5($cacheKeyBase);

$cachedImage = cache_get($imageCacheKey);

if ($cachedImage !== false) {
    echo json_encode([
        'success' => true,
        'image'   => $cachedImage
    ]);
    exit;
}

$imageUrl = '';

switch ($type) {

    case 'event':
        $imageUrl = getEventImage($artist, $defaultCategory, $event, $tab);
        if (!$imageUrl) {
            $imageUrl = AWS_CDN_URL . 'categories/' . strtolower($tab) . '.jpg';
        }
        break;

    case 'venue':
        $imageUrl = getVenueImage($venue);
        if (!$imageUrl) {
            $imageUrl = AWS_CDN_URL . 'images/venue.webp';
        }
        break;
}

cache_set($imageCacheKey, $imageUrl);

echo json_encode([
    'success' => true,
    'image'   => $imageUrl
]);
exit;