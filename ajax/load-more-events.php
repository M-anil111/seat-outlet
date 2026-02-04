<?php

require_once '../functions.php';

$page       = (int)($_GET['page'] ?? 1);
$perPage    = (int)($_GET['perPage'] ?? 2);
$performerId= (int)($_GET['performerId'] ?? 0);

$response = getTnPerformerEvents($performerId, [
    'page'    => $page,
    'perPage' => $perPage
]);

$results = $response['results'] ?? [];
$count   = $response['count'] ?? count($results);

echo json_encode([
    'events'  => $results,
    'hasMore' => ($count === $perPage),
    'nextPage'=> $page + 1
]);