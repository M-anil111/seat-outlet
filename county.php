<?php
require_once 'functions.php';

// /county/{name}-{state}, United States only (inc/counties.php). Markup in soRenderEntityListing() (inc/entity-listing.php).
$perPage = 20;
$slug = (string) ($_GET['slug'] ?? '');
[$id] = soSlugResolve('county', $slug);   // stored slug: a made-up slug never reaches the API
if ($id === null) {
	renderNotFoundPage('County');
}
$info = soCountyInfo((int) $id);
if (!$info) {
	renderNotFoundPage('County');
}
[$countyLabel, $cityIds] = $info;
$canonSlug = soSlug('county', $countyLabel, (int) $id);
soRedirectToCanonicalSlug('county', $slug, $canonSlug);

[$when, $sort, $isFiltered] = listingRequestState('popular');
$params = locationListingParams(soCountyFilter($cityIds), $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);
$events = $eventsResponse['results'] ?? [];

// Parent link: the state (taken from the county label, "Travis County, TX").
$abbr = preg_match('/,\s*([A-Z]{2})$/', $countyLabel, $m) ? $m[1] : '';
$trail = [];

soRenderEntityListing([
	'kind' => 'county', 'id' => (int) $id, 'name' => $countyLabel, 'label' => $countyLabel, 'path' => '/county/' . $canonSlug,
	'events' => $events, 'total' => (int) ($eventsResponse['totalCount'] ?? 0), 'count' => (int) ($eventsResponse['count'] ?? count($events)),
	'perPage' => $perPage, 'params' => $params, 'when' => $when, 'sort' => $sort, 'defaultSort' => 'popular', 'isFiltered' => $isFiltered,
	'trail' => $trail, 'entity' => [], 'cityId' => 0, 'cityLabel' => '',
	'image' => null,
	'parent' => null, 'stateParent' => null,
]);
