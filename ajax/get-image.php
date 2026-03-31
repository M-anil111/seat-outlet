<?php
require_once '../functions.php';

header('Content-Type: application/json');

$event     = trim($_GET['event'] ?? '');
$artist    = trim($_GET['artist'] ?? '');
$tab       = trim($_GET['tab'] ?? '');
$category  = $_GET['category'] ?? '';

if ($event === '' || $tab === '') {
    echo json_encode([
        'success' => false,
        'image'   => ''
    ]);
    exit;
}

$defaultCategory = [];

if (!empty($category)) {
    $decoded = json_decode($category, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $defaultCategory = $decoded;
    }
}

// 🔥 Add cache BEFORE calling heavy function
$imageCacheKey = 'so_img_' . md5($tab . '|' . $event . '|' . $artist);

$cachedImage = cache_get($imageCacheKey);

if ($cachedImage !== false) {
    echo json_encode([
        'success' => true,
        'image'   => $cachedImage
    ]);
    exit;
}

// ❌ Not cached → generate
$imageUrl = getEventImage($artist, $defaultCategory, $event, $tab);

// fallback safety
if (!$imageUrl) {
    $imageUrl = getCategoryFallbackImage($defaultCategory, $tab);
}

// 💾 SAVE CACHE
cache_set($imageCacheKey, $imageUrl);

// ✅ return
echo json_encode([
    'success' => true,
    'image'   => $imageUrl
]);
exit;