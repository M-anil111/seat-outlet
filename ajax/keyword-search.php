<?php 

require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$q = strtolower(mb_substr(soQs('q'), 0, 100));

// Basic validation
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $response = getKeywordSearchSuggestions($q);
    if (!$response && soApiDegraded()) throw new RuntimeException('Feed unavailable');
} catch (Throwable $e) {
    http_response_code(503);
    header('Cache-Control: no-store');
    echo json_encode(['error' => 'Search temporarily unavailable']);
    exit;
}

echo json_encode($response);
exit;

