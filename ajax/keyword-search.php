<?php 

require_once '../functions.php';

$q = strtolower(trim($_GET['q'] ?? ''));

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

