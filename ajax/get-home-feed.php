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

// How many best sellers are fetched at once, listed by date as one set and paged from (see soHomeFeedByDate()).
const SO_HOME_FEED_WIDE = 48;

header('Content-Type: application/json; charset=UTF-8');

// "Near" means within this many miles; the nearest-first search itself reaches across the country.
define('SO_NEAR_MILES', 50);
define('SO_NEAR_MAX_MILES', 3000);
// Beyond this the nearest event is not "near": the grid becomes a nationwide popular list instead.
define('SO_NEAR_FAR_MILES', 250);

$kindIn = soQs('kind');
$kind = in_array($kindIn, ['lastminute', 'near', 'popweekend'], true) ? $kindIn : 'trending';
// Snapped to a 0.1 degree grid (about 7 miles) and limited to the area we sell in: every distinct coordinate used to create its own
// cache file and its own live API call, so the key space was unbounded. A 50-mile search stays honest at this precision.
$snap = soSnapGeo($_GET['lat'] ?? null, $_GET['lng'] ?? null);
$lat = $snap ? $snap[0] : null;
$lng = $snap ? $snap[1] : null;
$hasGeo = $snap !== null && $kind !== 'popweekend';   // "Popular this weekend" is the same nationwide list for everyone

/** Live (uncached) feed builds are limited per visitor address, so one client cannot drive unlimited API calls. */
function soHomeFeedLiveAllowed() {
    if (soRateHit('feed-live', soIpHash(soClientIp()), 90, 600)) return true;
    http_response_code(429);
    header('Retry-After: 60');
    header('Cache-Control: no-store');
    echo json_encode(['scope' => 'none', 'events' => [], 'hasMore' => false, 'error' => 'Too many requests']);
    exit;
}


/* ---- "Explore ... near you" on the listing hubs: one category, one date window, paged (See more) ---- */
if ($kind === 'near') {
    $catMap = ['concerts' => TN_CATEGORY_PATH_CONCERTS, 'sports' => TN_CATEGORY_PATH_SPORTS, 'theatre' => TN_CATEGORY_PATH_THEATER, 'festival' => TN_CATEGORY_PATH_FESTIVAL, 'all' => ''];
    $cat = soQs('cat', 'all');
    $catPath = $catMap[$cat] ?? '';
    // A single category page (for example /category/basketball-1865) passes its own TicketNetwork category id.
    $catId = soQsInt('catid', 0, 0, 2147483647);
    $whenIn = soQs('when');
    $when = isset(LISTING_WHEN[$whenIn]) ? $whenIn : '';
    $page = soQsInt('page', 1, 1, 20);
    $sortIn = soQs('sort', 'distance');
    $nearSort = in_array($sortIn, ['soonest', 'popular', 'price'], true) ? $sortIn : 'distance';
    $radiusIn = soQsInt('radius');
    $radius = in_array($radiusIn, [25, 50, 100, 250], true) ? $radiusIn : 0;   // 0 = no limit: nearest anywhere
    $maxIn = soQsInt('max');
    $maxPrice = isset(LISTING_PRICE[$maxIn]) ? $maxIn : 0;                      // "under $X" on the lowest listed price
    $nationwide = !empty($_GET['nw']);   // set by the page once page 1 said the closest event is too far to call "near"
    // No usable location (none given, or outside the area we sell in): the whole country's popular events, labelled as such, never an empty list.
    if (!$hasGeo) { $nationwide = true; }
    $nearKey = 'near_' . ($catId ?: $cat) . '_' . $when . '_' . $nearSort . '_' . $radius . '_' . $maxPrice . '_' . ($nationwide ? 'nw' : 'geo') . '_' . $page . '_' . $lat . '_' . $lng;
    $cachedNear = cache_get('home_feed_' . $nearKey, 600);
    if ($cachedNear !== false) {
        header('Cache-Control: public, max-age=300');
        echo json_encode($cachedNear);
        exit;
    }
    soHomeFeedLiveAllowed();
    try {
        // Always nearest first. The search is not cut off at 50 miles: when nothing is close, the closest events anywhere
        // in the country come back (San Antonio, Houston, Dallas for a Hill Country visitor) and the page says so.
        $buildParams = function ($useGeo, $wide = false) use ($catId, $catPath, $page, $when, $maxPrice, $nearSort, $radius, $lat, $lng) {
            $params = $catId > 0
                ? locationListingParams("country/alphaCode eq 'US' and contains(defaultCategory/path, '." . $catId . ".')", 12, $page, $when, 'popular', $maxPrice)
                : categoryListingParams($catPath, 12, $page, $when, 'popular', $maxPrice);
            if ($useGeo) {
                $params['geoFilter'] = sprintf('nearby(%F,%F,%dmi)', $lat, $lng, $radius ?: SO_NEAR_MAX_MILES);
                if ($nearSort === 'distance') $params['sort'] = 'distance';
            }
            if ($nearSort === 'soonest') { $params['sort'] = 'date/date'; unset($params['salesRankOptions']); }
            elseif ($nearSort === 'price') { $params['sort'] = 'pricingInfo/lowPrice/value'; unset($params['salesRankOptions']); }
            elseif (!$useGeo || $nearSort === 'popular') { $params['sort'] = '-salesRank'; $params['salesRankOptions'] = '{"interval":"day","metric":"orderVolume"}'; }
            if (strpos((string) ($params['filter'] ?? ''), 'alphaCode') === false) {
                $params['filter'] = trim(($params['filter'] ?? '') . " and country/alphaCode eq 'US'", ' and');
            }
            // Best sellers: the top 48 in one request, so they can be listed by date as one set and paged from it ("See more" then continues the
            // same date order instead of starting a second, separately ordered batch).
            if ($wide) { $params['perPage'] = SO_HOME_FEED_WIDE; $params['page'] = 1; }
            return $params;
        };
        $wideNow = ($nearSort === 'popular' || $nationwide);
        $data = tnRequest('/catalog/v2/events/', $buildParams(!$nationwide, $wideNow));
        $total = (int) ($data['totalCount'] ?? 0);
        $wideAll = null;   // the whole date-ordered best-seller set, when this answer is one
        if ($wideNow) {
            $wideAll = soHomeFeedByDate(soHomeFeedFormat($data['results'] ?? [], SO_HOME_FEED_WIDE));
            $events = array_slice($wideAll, ($page - 1) * 12, 12);
        } else {
            $events = soHomeFeedFormat($data['results'] ?? [], 12);
            if ($nearSort === 'distance') $events = soHomeFeedImageFirst($events, function ($e) { return $e['dist']; });
            elseif ($nearSort === 'soonest') $events = soHomeFeedImageFirst($events, function ($e) { return $e['iso'] !== '' ? $e['iso'] : null; });
        }
        $dists = array_filter(array_column($events, 'dist'), function ($d) { return $d !== null; });
        $closest = $dists ? min($dists) : null;
        $scope = !$events ? ($radius ? 'empty' : 'near') : ($radius === 0 && $closest !== null && $closest > SO_NEAR_MILES ? 'nearest' : 'near');
        // Nothing within 250 miles: "closest" would mean a 1,000 mile trip, so show what is popular across the country instead.
        if ($page === 1 && !$nationwide && $nearSort === 'distance' && $radius === 0 && $events && $closest !== null && $closest > SO_NEAR_FAR_MILES) {
            $nw = tnRequest('/catalog/v2/events/', $buildParams(false, true));
            $nwAll = soHomeFeedByDate(soHomeFeedFormat($nw['results'] ?? [], SO_HOME_FEED_WIDE));
            if ($nwAll) {
                $wideAll = $nwAll; $events = array_slice($nwAll, 0, 12); $total = (int) ($nw['totalCount'] ?? 0); $scope = 'nationwide'; $nationwide = true;
            }
        } elseif ($nationwide) {
            $scope = 'nationwide';
        }
        $out = [
            'scope' => $scope,
            'radius' => $radius ?: SO_NEAR_MILES,
            'limited' => $radius,
            'closest' => $closest,
            'events' => $events,
            'total' => $total,
            'hasMore' => $wideAll !== null ? $page * 12 < count($wideAll) : $page * 12 < $total,
        ];
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

soHomeFeedLiveAllowed();

function soHomeFeedFetch(string $kind, ?float $lat, ?float $lng): array {
    $today = date('Y-m-d');
    $end = $kind === 'lastminute' ? date('Y-m-d', strtotime('+7 days')) : date('Y-m-d', strtotime('+90 days'));
    if ($kind === 'popweekend') {
        [$today, $end] = listingDateRange('weekend');
    }
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
            'slug' => soEventSlug($event),
            'iso' => $ts ? date('Y-m-d', $ts) : '',
            'time' => (string)($event['date']['text']['time'] ?? ''),
            'date' => $ts ? date('D, M j', $ts) . (($event['date']['text']['time'] ?? '') !== '' ? ', ' . $event['date']['text']['time'] : '') : '',
            'venue' => $event['venue']['text']['name'] ?? '',
            'loc' => trim($city . ', ' . $state, ', '),
            'dist' => isset($event['geoLocation']['distance']['distance']) ? (int) round($event['geoLocation']['distance']['distance']) : null,
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

/** Pictures first for one home-feed row (see soImageFirst): $primary keeps the row's own order, pictures only break ties. */
/**
 * The best sellers (the API's sales-rank order) listed by date. The three best sellers, picture-bearing ones first, carry the "Popular"
 * flag; events on the same date keep their sales-rank order (taken before any picture ordering); undated events go last.
 */
function soHomeFeedByDate(array $ranked): array {
    foreach ($ranked as $i => $e) { $ranked[$i]['_i'] = $i; }
    $top = [];
    foreach (array_slice(soHomeFeedImageFirst(array_slice($ranked, 0, 12)), 0, 3) as $e) { $top[$e['id']] = true; }
    foreach ($ranked as $i => $e) { $ranked[$i]['top'] = isset($top[$e['id']]); }
    usort($ranked, function ($a, $b) {
        $x = (string) ($a['iso'] ?? ''); $y = (string) ($b['iso'] ?? '');
        if ($x === $y) return $a['_i'] <=> $b['_i'];
        if ($x === '') return 1;
        if ($y === '') return -1;
        return strcmp($x, $y);
    });
    foreach ($ranked as $i => $e) { unset($ranked[$i]['_i']); }
    return array_values($ranked);
}

function soHomeFeedImageFirst(array $events, ?callable $primary = null): array {
    return soImageFirst($events, function ($ev) {
        return [imageEntityTypeForPerformer($ev['defaultCategory'] ?? []), (string) (($ev['performer'] ?? '') !== '' ? $ev['performer'] : $ev['name']), $ev['defaultCategory'] ?? []];
    }, $primary);
}

try {
    $events = soHomeFeedFetch($kind, $hasGeo ? $lat : null, $hasGeo ? $lng : null);
    $scope = $hasGeo ? 'near' : 'national';
    if ($hasGeo && count($events) < 4) {
        // Too thin nearby to fill a row: show the country instead of a nearly empty "near you" row.
        $events = soHomeFeedFetch($kind, null, null);
        $scope = 'national';
    }
    $formatted = soHomeFeedFormat($events);
    if ($kind === 'lastminute') $formatted = soHomeFeedImageFirst($formatted, function ($e) { return $e['iso'] !== '' ? $e['iso'] : null; });   // same day: pictures first
    elseif ($kind === 'trending') $formatted = soHomeFeedImageFirst($formatted);   // top sellers: the same set, pictures first
    $result = ['scope' => $scope, 'events' => $formatted];
    if ($result['events']) {
        cache_set($cacheKey, $result);
    }
    header('Cache-Control: public, max-age=300');
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['scope' => 'national', 'events' => [], 'error' => 'Failed to load events']);
}
