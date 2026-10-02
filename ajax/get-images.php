<?php
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json');

// Batch version of get-image.php: one request for every card in a slider
// instead of one request per card, loaded one after another (the homepage
// fired up to 24 sequential requests per tab). Serve-only, same as the
// single endpoint: stored image or fallback, unknown entities are queued.
//
// Input (POST body, JSON): {"items":[{"artist":"...","venue":"...","tab":"...","category":{...}}, ...]}
// Output: {"images":[{"image":"...","credit":"..."}, ...]} aligned with the input order.

$raw = file_get_contents('php://input');
$body = json_decode((string) $raw, true);
$items = is_array($body['items'] ?? null) ? array_slice($body['items'], 0, 40) : [];

$out = [];
foreach ($items as $item) {
    $artist = trim((string) ($item['artist'] ?? ''));
    $venue  = trim((string) ($item['venue'] ?? ''));
    $tab    = trim((string) ($item['tab'] ?? ''));
    $category = is_array($item['category'] ?? null) ? $item['category'] : [];

    $type = 'artist';
    if ($artist !== '') {
        $type = imageEntityTypeForPerformer($category);
        $img = getEntityImage($type, $artist, ['category' => $category, 'tab' => $tab]);
    } elseif ($venue !== '') {
        $type = 'venue';
        $img = getEntityImage('venue', $venue);
    } else {
        $img = ['url' => $tab !== '' ? getCategoryFallbackImage($category, $tab) : '', 'credit' => '', 'status' => 'fallback'];
    }
    // 'real' tells the page whether this is the entity's own picture or only the shared category stock photo; the page
    // then shows an initials tile (never the same stock photo on every card) and asks for the real picture later.
    $out[] = ['image' => $img['url'], 'credit' => $img['credit'], 'real' => in_array($img['status'] ?? '', ['ok', 'manual'], true) && $img['url'] !== '', 'type' => $type];
}

echo json_encode(['images' => $out]);
exit;
