<?php 

require_once '../functions.php';

$q = strtolower(trim($_GET['q'] ?? ''));

// Basic validation
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$cacheKey  = 'kw_' . $q;

$cachedData = cache_get($cacheKey);
if ($cachedData !== false) {

    $data = json_decode($cachedData, true);

    header('Content-Type: application/json');
    header('Cache-Control: public, max-age=86400');
    header('X-Cache: HIT');

    echo json_encode($data);
    exit;
}

try {
    $response = getKeywordSearchSuggestions($q);
} catch (Throwable $e) {
    echo json_encode([]);
    exit;
}

cache_set($cacheKey, json_encode($response), 86400);

header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400');
header('X-Cache: MISS');
echo json_encode($response);
exit;
