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
} catch (Throwable $e) {
    echo json_encode([]);
    exit;
}

echo json_encode($response);
exit;

