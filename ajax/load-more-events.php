<?php

require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');

$page       = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage    = isset($_GET['perPage']) ? max(1, min(100, (int) $_GET['perPage'])) : 20;
$type       = isset($_GET['type']) ? $_GET['type'] : '';

// The `params` query param is the exact JSON the server itself handed the
// browser earlier (see the data-params attribute this pairs with in the
// front-end templates) so a normal "load more" click can ask for the next
// page of the same filtered result set. But it's still client-supplied, and
// without an allow-list any key here goes straight into the upstream
// TicketNetwork API query string (http_build_query() in getHeaderSearchEvents()/
// getLoadMoreEvents()) - a tampered request could inject arbitrary filter/sort
// values. page/perPage/includeTotalCount are already server-controlled below
// regardless of what's in here, so only the filter-shaped keys actually used
// elsewhere in this app need to survive.
const LOAD_MORE_ALLOWED_PARAM_KEYS = ['filter', 'geoFilter', 'performerFilter', 'sort', 'salesRankOptions'];

try {
    $params = [];
    if (!empty($_GET['params'])) {
        $decoded = json_decode($_GET['params'], true);
        if (is_array($decoded)) {
            $params = array_intersect_key($decoded, array_flip(LOAD_MORE_ALLOWED_PARAM_KEYS));
        }
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