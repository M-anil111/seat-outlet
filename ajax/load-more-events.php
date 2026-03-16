<?php

require_once '../functions.php';

header('Content-Type: application/json; charset=UTF-8');

$page       = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage    = isset($_GET['perPage']) ? max(1, min(100, (int) $_GET['perPage'])) : 2;
$performerId= isset($_GET['performerId']) ? max(0, (int) $_GET['performerId']) : 0;

try {
    if($performerId) {
        $response = getTnPerformerEvents($performerId, [
            'page'    => $page,
            'perPage' => $perPage,
        ]);
        $total_count = getTnPerformerEventsCount($performerId);
        $total_pages = $perPage > 0 ? (int) ceil($total_count / $perPage) : 0;
    }else{
        $params = [];

        if (!empty($_GET['params'])) {
            $params = json_decode($_GET['params'], true) ?? [];
        }

        $params['page']    = $page;
        $params['perPage'] = $perPage;

        //echo json_encode($params);

        $response = getHeaderSearchEvents($params);
        $total_count = $response['totalCount'];
        $total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
    }

    $results = $response['results'] ?? [];
    $count   = $response['count'] ?? count($results);

    echo json_encode([
        'events'   => $results,
        'hasMore'  => ($page < $total_pages),
        'nextPage' => $page + 1,
        'count'    => $count,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'events'   => [],
        'hasMore'  => false,
        'nextPage' => $page,
        'error'    => 'Failed to load events',
    ]);
}