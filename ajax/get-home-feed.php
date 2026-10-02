<?php
/**
 * Homepage rows: "Trending events" and "Last-minute tickets".
 *
 *   GET ?kind=trending|lastminute[&lat=..&lng=..]
 *
 * With a location (lat/lng, set by the visitor's address lookup or their choice) the rows are events within 50 miles;
 * without one, or when nothing is found nearby, they fall back to the whole country and say so ('scope' => 'national'),
 * so the heading never claims "near you" for events that are not.
 *
 * Last-minute = events in the next 7 days that have tickets listed, soonest first. It makes no discount claim: the
 * price shown is the lowest listed price today, and resale prices can be above or below face value.
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');

$kindIn = $_GET['kind'] ?? '';
$kind = in_array($kindIn, ['lastminute', 'near'], true) ? $kindIn : 'trending';
$lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float) $_GET['lng'] : null;
if ($lat !== null && ($lat < -90 || $lat > 90)) $lat = null;
if ($lng !== null && ($lng < -180 || $lng > 180)) $lng = null;
$hasGeo = $lat !== null && $lng !== null;
// One decimal is about 7 miles: plenty for a 50-mile radius, and it lets nearby visitors share one cached answer.
if ($hasGeo) { $lat = round($lat, 1); $lng = round($lng, 1); }


/* ---- "Explore ... near you" on the listing hubs: one category, one date window, paged (See more) ---- */
if ($kind === 'near') {
    $catMap = ['concerts' => TN_CATEGORY_PATH_CONCERTS, 'sports' => TN_CATEGORY_PATH_SPORTS, 'theatre' => TN_CATEGORY_PATH_THEATER, 'festival' => TN_CATEGORY_PATH_FESTIVAL, 'all' => ''];
    $cat = $_GET['cat'] ?? 'all';
    $catPath = $catMap[$cat] ?? '';
    $whenIn = $_GET['when'] ?? '';
    $when = isset(LISTING_WHEN[$whenIn]) ? $whenIn : '';
    $page = max(1, min(20, (int) ($_GET['page'] ?? 1)));
    if (!$hasGeo) {
        echo json_encode(['scope' => 'none', 'events' => [], 'hasMore' => false]);
        exit;
    }
    $nearKey = 'near_' . ($cat) . '_' . $when . '_' . $page . '_' . $lat . '_' . $lng;
    $cachedNear = cache_get('home_feed_' . $nearKey, 600);
    if ($cachedNear !== false) {
        header('Cache-Control: public, max-age=300');
        echo json_encode($cachedNear);
        exit;
    }
    try {
        $params = categoryListingParams($catPath, 12, $page, $when, 'popular');
        $params['geoFilter'] = sprintf('nearby(%F,%F,50mi)', $lat, $lng);
        $data = tnRequest('/catalog/v2/events/', $params);
        $total = (int) ($data['totalCount'] ?? 0);
        $out = ['scope' => 'near', 'events' => soHomeFeedFormat($data['results'] ?? [], 12), 'total' => $total, 'hasMore' => $page * 12 < $total];
        if ($out['events'] || $page > 1) cache_set('home_feed_' . $nearKey, $out);
        header('Cache-Control: public, max-age=300');
        echo json_encode($out);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['scope' => 'near', 'events' => [], 'hasMore' => false, 'error' => 'Failed to load events']);
    }
    exit;
}

$cacheKey = 'home_feed_' . $kind . '_' . ($hasGeo ? $lat . '_' . $lng : 'us');
$cached = cache_get($cacheKey, $kind === 'lastminute' ? 600 : 900);
if ($cached !== false) {
    header('Cache-Control: public, max-age=300');
    echo json_encode($cached);
    exit;
}

function soHomeFeedFetch(string $kind, ?float $lat, ?float $lng): array {
    $today = date('Y-m-d');
    $end = $kind === 'lastminute' ? date('Y-m-d', strtotime('+7 days')) : date('Y-m-d', strtotime('+90 days'));
    $params = [
        'filter' => "date/date ge $today and date/date le $end and _metadata/hasTickets eq true and country/alphaCode eq 'US'",
        'perPage' => 12,
    ];
    if ($kind === 'lastminute') {
        $params['sort'] = 'date/date';
    } else {
        $params['sort'] = '-salesRank';
        $params['salesRankOptions'] = '{"interval":"day","metric":"orderVolume"}';
    }
    if ($lat !== null && $lng !== null) {
        $params['geoFilter'] = sprintf('nearby(%F,%F,50mi)', $lat, $lng);
    }
    $data = tnRequest('/catalog/v2/events/', $params);
    return $data['results'] ?? [];
}

/** Only what the image lookup needs (path, depth, names), not the API's link blocks. */
function soHomeFeedCategory(array $c): array {
    if (!$c) return [];
    $ancestors = [];
    foreach ($c['ancestors'] ?? [] as $a) {
        $ancestors[] = ['path' => $a['path'] ?? '', 'depth' => $a['depth'] ?? 0, 'text' => $a['text'] ?? []];
    }
    return ['path' => $c['path'] ?? '', 'depth' => $c['depth'] ?? 0, 'text' => $c['text'] ?? [], 'ancestors' => $ancestors];
}

function soHomeFeedFormat(array $events, int $max = 10): array {
    $out = [];
    $seen = [];
    foreach ($events as $event) {
        $id = (int) ($event['id'] ?? 0);
        $name = $event['text']['name'] ?? '';
        if ($id === 0 || $name === '' || isset($seen[$id])) continue;
        $seen[$id] = true;
        $raw = $event['date']['date'] ?? '';
        $ts = $raw !== '' ? strtotime($raw) : false;
        $path = $event['defaultCategory']['path'] ?? '';
        $tab = strpos($path, '.1988.') !== false ? 'sports' : (strpos($path, '.1989.') !== false ? 'theatre' : 'concerts');
        $city = $event['city']['text']['name'] ?? '';
        $state = $event['stateProvince']['text']['abbr'] ?? '';
        $out[] = [
            'id' => $id,
            'name' => $name,
            'iso' => $ts ? date('Y-m-d', $ts) : '',
            'date' => $ts ? date('D, M j', $ts) . (($event['date']['text']['time'] ?? '') !== '' ? ' - ' . $event['date']['text']['time'] : '') : '',
            'venue' => $event['venue']['text']['name'] ?? '',
            'loc' => trim($city . ', ' . $state, ', '),
            'price' => $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '',
            'performer' => $event['performers'][0]['name'] ?? '',
            'tab' => $tab,
            'defaultCategory' => soHomeFeedCategory($event['defaultCategory'] ?? []),
            'placeholder' => getCategoryFallbackImage($event['defaultCategory'] ?? [], $tab),
        ];
        if (count($out) >= $max) break;
    }
    return $out;
}

try {
    $events = soHomeFeedFetch($kind, $hasGeo ? $lat : null, $hasGeo ? $lng : null);
    $scope = $hasGeo ? 'near' : 'national';
    if ($hasGeo && count($events) < 4) {
        // Too thin nearby to fill a row: show the country instead of a nearly empty "near you" row.
        $events = soHomeFeedFetch($kind, null, null);
        $scope = 'national';
    }
    $result = ['scope' => $scope, 'events' => soHomeFeedFormat($events)];
    if ($result['events']) {
        cache_set($cacheKey, $result);
    }
    header('Cache-Control: public, max-age=300');
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['scope' => 'national', 'events' => [], 'error' => 'Failed to load events']);
}
