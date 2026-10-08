<?php
require_once 'functions.php';

// /city/{name}-{state}. Markup in soRenderEntityListing() (inc/entity-listing.php).
$perPage = 20;
$slug = (string) ($_GET['slug'] ?? '');
[$id] = soSlugResolve('city', $slug);   // stored slug, or the old name-and-id form: a made-up slug never reaches the API
if ($id === null) {
	renderNotFoundPage('City');
}

$city = getTnCityById($id);
if (tnEntityMissing($city)) {
	renderNotFoundPage('City', $city);   // 404 when the API says not found, 503 when it failed
}

$cityName  = (string) ($city['text']['name'] ?? '');
$cityLabel = trim($cityName . ', ' . (string) ($city['stateProvince']['text']['abbr'] ?? ''), ', ');
$canonSlug = soSlug('city', $cityLabel, $id);
soRedirectToCanonicalSlug('city', $slug, $canonSlug);

[$when, $sort, $isFiltered] = listingRequestState('soonest');
$params = locationListingParams("city/id eq $id", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);
$events = $eventsResponse['results'] ?? [];

$stateId   = (int) ($city['stateProvince']['id'] ?? 0);
$stateName = (string) ($city['stateProvince']['text']['name'] ?? '');
$trail = $stateId > 0 && $stateName !== '' ? [['label' => $stateName, 'url' => HOME_URL . '/state/' . soSlug('state', $stateName, $stateId)]] : [];

soRenderEntityListing([
	'kind' => 'city', 'id' => $id, 'name' => $cityLabel, 'label' => $cityLabel, 'path' => '/city/' . $canonSlug,
	'events' => $events, 'total' => (int) ($eventsResponse['totalCount'] ?? 0), 'count' => (int) ($eventsResponse['count'] ?? count($events)),
	'perPage' => $perPage, 'params' => $params, 'when' => $when, 'sort' => $sort, 'defaultSort' => 'soonest', 'isFiltered' => $isFiltered,
	'trail' => $trail, 'entity' => $city, 'cityId' => $id, 'cityLabel' => $cityLabel,
	'image' => getEntityImage('city', $cityLabel, ['resolve' => false]),   // serve-only; never blocks the page
	'parent' => ($soCounty = soCountyOfCity((int) $id)) ? ['url' => '/county/' . soSlug('county', $soCounty[1], $soCounty[0]), 'text' => 'Events in ' . $soCounty[1]] : null,
	'stateParent' => $stateId > 0 && $stateName !== '' ? ['url' => '/state/' . soSlug('state', $stateName, $stateId), 'text' => "Events in $stateName"] : null,
]);
