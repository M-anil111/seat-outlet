<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=600');

// Serve-only endpoint for cards on the homepage / search suggestions.
// It never calls a third-party API inside the request: it returns the
// stored image when one exists, otherwise the category/venue fallback, and
// queues the entity for cron/resolve-images.php. (Previously each card
// could trigger Wikipedia + Knowledge Graph + S3 + GD work inline, and a
// transient failure was cached as the fallback forever.)

$artist   = trim($_GET['artist'] ?? '');
$venue    = trim($_GET['venue'] ?? '');
$tab      = trim($_GET['tab'] ?? '');
$category = $_GET['category'] ?? '';

$defaultCategory = [];
if (!empty($category)) {
    $decoded = json_decode($category, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $defaultCategory = $decoded;
    }
}

if ($venue !== '') {
    $img = getEntityImage('venue', $venue);
} elseif ($artist !== '') {
    // Event cards pass event + artist; the artist is the only thing with a
    // findable image (an event name like "X vs. Y" never has one).
    $type = imageEntityTypeForPerformer($defaultCategory);
    $img  = getEntityImage($type, $artist, ['category' => $defaultCategory, 'tab' => $tab]);
} else {
    $fallback = $tab !== '' ? getCategoryFallbackImage($defaultCategory, $tab) : '';
    echo json_encode(['success' => $fallback !== '', 'image' => $fallback, 'credit' => '']);
    exit;
}

echo json_encode([
    'success' => $img['url'] !== '',
    'image'   => $img['url'],
    'credit'  => $img['credit'],
]);
exit;
