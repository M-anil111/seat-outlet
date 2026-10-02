<?php
require_once 'functions.php';

// /city/{name}-{state}-{id}. Markup in soRenderEntityListing() (inc/entity-listing.php).
$perPage = 20;
$slug = (string) ($_GET['slug'] ?? '');
$id   = soSlugTrailingId($slug);   // strict id, at most 2147483647: junk never reaches the API
if ($id === null) {
	renderNotFoundPage('City');
}

$city = getTnCityById($id);
if (tnEntityMissing($city)) {
	renderNotFoundPage('City', $city);   // 404 when the API says not found, 503 when it failed
}

$cityName  = (string) ($city['text']['name'] ?? '');
$cityLabel = trim($cityName . ', ' . (string) ($city['stateProvince']['text']['abbr'] ?? ''), ', ');
$canonSlug = soEntitySlug($cityLabel, $id);
soRedirectToCanonicalSlug('city', $slug, $canonSlug);

[$when, $sort, $isFiltered] = listingRequestState('popular');
$params = locationListingParams("city/id eq $id", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);
$events = $eventsResponse['results'] ?? [];

$stateId   = (int) ($city['stateProvince']['id'] ?? 0);
$stateName = (string) ($city['stateProvince']['text']['name'] ?? '');
$trail = $stateId > 0 && $stateName !== '' ? [['label' => $stateName, 'url' => HOME_URL . '/state/' . soEntitySlug($stateName, $stateId)]] : [];

soRenderEntityListing([
	'kind' => 'city', 'id' => $id, 'name' => $cityLabel, 'label' => $cityLabel, 'path' => '/city/' . $canonSlug,
	'events' => $events, 'total' => (int) ($eventsResponse['totalCount'] ?? 0), 'count' => (int) ($eventsResponse['count'] ?? count($events)),
	'perPage' => $perPage, 'params' => $params, 'when' => $when, 'sort' => $sort, 'defaultSort' => 'popular', 'isFiltered' => $isFiltered,
	'trail' => $trail, 'entity' => $city, 'cityId' => $id, 'cityLabel' => $cityLabel,
	'image' => getEntityImage('city', $cityLabel, ['resolve' => false]),   // serve-only; never blocks the page
	'parent' => null,
	'stateParent' => $stateId > 0 && $stateName !== '' ? ['url' => '/state/' . soEntitySlug($stateName, $stateId), 'text' => "Events in $stateName"] : null,
]);
