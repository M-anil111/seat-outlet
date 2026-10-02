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
$whenIn    = soQs('when');
$sortIn    = soQs('sort');          // '' (nearest first with a location, else soonest) | 'soonest' | 'price'

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
// A quick "when" chip (today, weekend, 7 days, 30 days) becomes a date range here, so the browser only sends a word.
if (($startDate === '' || $endDate === '') && isset(LISTING_WHEN[$whenIn]) && ($whenRange = listingDateRange($whenIn))) {
    [$startDate, $endDate] = $whenRange;
}
if ($startDate !== '' && $endDate !== '') {
    $filters[] = "date/date ge $startDate";
    $filters[] = "date/date le $endDate";
} else {
    $filters[] = "country/alphaCode eq 'US'";
    $filters[] = "date/date ge $today";
}

// Nearest first, and never cut off at a radius: a visitor in Bee Cave, TX who is looking at a New York act sees the closest
// dates first and is told how far they are, instead of "no events". Without a location the order is unchanged.
define('SO_NEAR_MILES', 50);
if ($latVal !== null && $lngVal !== null) {
    $params['geoFilter'] = sprintf('nearby(%F,%F,3000mi)', $latVal, $lngVal);
    $params['sort'] = 'distance';
}
// A visitor-chosen order wins over nearest-first. Price order only makes sense for dates that have tickets listed.
if ($sortIn === 'soonest') {
    $params['sort'] = 'date/date';
} elseif ($sortIn === 'price') {
    $params['sort'] = 'pricingInfo/lowPrice/value';
    $filters[] = '_metadata/hasTickets eq true';
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
    $dists = [];
    foreach ($results as $r) {
        if (isset($r['geoLocation']['distance']['distance'])) $dists[] = (float) $r['geoLocation']['distance']['distance'];
    }
    $closest = $dists ? (int) round(min($dists)) : null;
    echo json_encode([
        'events'      => $results,
        'html'        => soRenderListingRows($results),
        'closest'     => $closest,
        'scope'       => $latVal === null ? 'all' : ($closest !== null && $closest > SO_NEAR_MILES ? 'nearest' : 'near'),
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


