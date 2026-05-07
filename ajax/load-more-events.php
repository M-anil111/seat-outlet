<?php

require_once '../functions.php';

header('Content-Type: application/json; charset=UTF-8');

$page       = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage    = isset($_GET['perPage']) ? max(1, min(100, (int) $_GET['perPage'])) : 20;
$type       = isset($_GET['type']) ? $_GET['type'] : '';

try {
    $params = [];
    if (!empty($_GET['params'])) {
        $params = json_decode($_GET['params'], true) ?? [];
    }
    $params['page']    = $page;
    $params['perPage'] = $perPage;
    $params['includeTotalCount'] = 'true';
    if($type === 'search') {
        $response = getHeaderSearchEvents($params);
    }else{
        $response = getLoadMoreEvents($params);
    }       
    
    $total_count = $response['totalCount'];
    $total_pages = $perPage > 0 ? (int) ceil($total_count / $perPage) : 0;

    $results = $response['results'] ?? [];   
    $hasMore = ($page < $total_pages);

    echo json_encode([
        'events'      => $results,
        'totalCount'  => $total_count,
        'currentPage' => $page,
        'nextPage'    => $hasMore ? ($page + 1) : null,
        'hasMore'     => $hasMore
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'events'      => [],
        'totalCount'  => 0,
        'currentPage' => $page,
        'nextPage'    => null,
        'hasMore'     => false,
        'error'       => 'Failed to load events'
    ]);
}