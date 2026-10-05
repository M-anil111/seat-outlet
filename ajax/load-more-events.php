<?php

require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=300');

$page       = soQsInt('page', 1, 1, 500);
$perPage    = soQsInt('perPage', 20, 1, 100);
$type       = soQs('type');

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
const LOAD_MORE_ALLOWED_PARAM_KEYS = ['q', 'filter', 'geoFilter', 'performerFilter', 'sort', 'salesRankOptions'];

// Values that are not free-form filters are pinned to the ones the site
// itself generates, so a tampered request cannot ask the upstream API for an
// arbitrary sort or ranking window.
const LOAD_MORE_ALLOWED_SORTS = ['distance', 'date/date', '-date/date', '-salesRank', 'salesRank', 'pricingInfo/lowPrice/value', '-pricingInfo/lowPrice/value'];
const LOAD_MORE_ALLOWED_RANK_OPTIONS = [
    '{"interval":"day","metric":"orderVolume"}',
    '{"interval":"day","metric":"ticketVolume"}',
    '{"interval":"week","metric":"ticketVolume"}',
];

try {
    $params = [];
    if (soQs('params') !== '' && strlen(soQs('params')) <= 4000) {
        $decoded = json_decode(soQs('params'), true);
        if (is_array($decoded)) {
            $params = array_intersect_key($decoded, array_flip(LOAD_MORE_ALLOWED_PARAM_KEYS));
            foreach ($params as $k => $v) {
                if (!is_string($v)) { unset($params[$k]); }
            }
            if (isset($params['sort']) && !in_array($params['sort'], LOAD_MORE_ALLOWED_SORTS, true)) { unset($params['sort']); }
            if (isset($params['salesRankOptions']) && !in_array($params['salesRankOptions'], LOAD_MORE_ALLOWED_RANK_OPTIONS, true)) { unset($params['salesRankOptions']); }
            if (isset($params['q'])) { $params['q'] = mb_substr(trim($params['q']), 0, 100); }
            // The filter strings the site itself builds only use these characters (field paths, quotes, dates, parentheses, comparison words).
            // Anything else, or a very long one, is dropped instead of being forwarded to the API.
            foreach (['filter', 'geoFilter', 'performerFilter'] as $fk) {
                if (isset($params[$fk]) && (strlen($params[$fk]) > 700 || !preg_match("#^[A-Za-z0-9_ /().,'\\-:%+]*$#", $params[$fk]))) { unset($params[$fk]); }
            }
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
    
    $total_count = (int) ($response['totalCount'] ?? 0);
    $total_pages = $perPage > 0 ? (int) ceil($total_count / $perPage) : 0;

    $results = $response['results'] ?? [];   
    $hasMore = ($page < $total_pages);
    // A page with no events (throttled or degraded API) must not be kept by a browser or CDN for five minutes.
    if (empty($results)) { header('Cache-Control: no-store'); }

    // Rows as finished HTML: the same renderer as the first page (escaping, data attributes, festival cards, button text).
    $rankQuery = ($type === 'search' && !isset($params['sort']) && isset($params['q']) && $params['q'] !== '*') ? (string) $params['q'] : '';
    $rows = $rankQuery !== '' ? soRankSearchEvents($results, $rankQuery) : $results;

    soSlugWarmEvents($results);
    foreach ($results as $i => $r) { $results[$i]['so'] = soEventSlugBundle($r); }
    echo json_encode([
        'events'      => $results,
        'html'        => soRenderListingRows($rows),
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