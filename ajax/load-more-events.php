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

$total_count = getTnPerformerEventsCount($performerId);
$total_pages = ceil($total_count / $perPage);

echo json_encode([
    'events'  => $results,
    'hasMore' => ($page < $total_pages),
    'nextPage'=> $page + 1
]);