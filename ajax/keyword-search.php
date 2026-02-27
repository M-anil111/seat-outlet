<?php 

require_once '../functions.php';

$q = trim($_GET['q'] ?? '');

// Basic validation / normalization
if ($q === '' || mb_strlen($q) > 50) {
    echo json_encode([]);
    exit;
}

// Restrict allowed characters to avoid malformed filters.
$q = preg_replace('/[^a-zA-Z0-9\s,\-]/u', '', $q);

try {
    $response = getKeywordSearchSuggestions($q);
} catch (Throwable $e) {
    echo json_encode([]);
    exit;
}

echo json_encode($response);
exit;
