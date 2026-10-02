<?php

require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300');

// Raw inputs
$lat       = soQs('lat');
$lng       = soQs('lng');
$startDate = soQs('startDate');
$endDate   = soQs('endDate');
$pid       = soQsInt('pid', 0, 0, 2147483647);

// Snapped to a 0.1 degree grid: visitors near each other share one cached answer and the cache key space stays small.
$snap = soSnapGeo($lat, $lng);
$latVal = $snap ? $snap[0] : null;
$lngVal = $snap ? $snap[1] : null;

$datePattern = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($datePattern, $startDate)) $startDate = '';
if (!preg_match($datePattern, $endDate))   $endDate   = '';

$perPage = 20;
$page = 1;
$params = [
    'perPage' => $perPage,
    'page'    => $page
];


$filters = [];
$today = date('Y-m-d');
if ($startDate !== '' && $endDate !== '') {
    $filters[] = "date/date ge $startDate";
    $filters[] = "date/date le $endDate";
} else {
    $filters[] = "country/alphaCode eq 'US'";
    $filters[] = "date/date ge $today";
}

if ($latVal !== null && $lngVal !== null) {
    $params['geoFilter'] = sprintf('nearby(%F, %F, 50mi)', $latVal, $lngVal);
}

if($pid > 0) {
    $params['performerFilter'] = "id eq " . (int) $pid;
}

$params['filter'] = implode(' and ', $filters);
$params['includeTotalCount'] = 'true';

try {
    $response = getTnEvents($params);
    $total_count = (int) ($response['totalCount'] ?? 0);
    $total_pages = $perPage > 0 ? (int) ceil($total_count / $perPage) : 0;
    $results = $response['results'] ?? [];   
    $hasMore = ($page < $total_pages);
    echo json_encode([
        'events'      => $results,
        'params'      => $params,
        'totalCount'  => $total_count,
        'currentPage' => $page,
        'nextPage'    => $hasMore ? ($page + 1) : null,
        'hasMore'     => $hasMore
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'events'      => [],
        'params'      => [],
        'totalCount'  => 0,
        'currentPage' => 1,
        'hasMore'     => false,
        'error'       => 'Failed to load events'
    ]);
}


