<?php
require_once 'functions.php';

// /state/{name}. Markup in soRenderEntityListing() (inc/entity-listing.php).
$perPage = 20;
$slug = (string) ($_GET['slug'] ?? '');
$id   = parseLocationSlug('state', $slug);   // stored slug, or the old name-and-id form
if ($id === null) {
	renderNotFoundPage('State');
}

$state = getLocationDisplayInfo('state', $id, $raw);
if (empty($state)) {
	renderNotFoundPage('State', $raw);   // 404 when the API says not found, 503 when it failed
}

$stateLabel = $state['label'];
$canonSlug  = soSlug('state', $stateLabel, $id);
soRedirectToCanonicalSlug('state', $slug, $canonSlug);

[$when, $sort, $isFiltered] = listingRequestState('popular');
$params = locationListingParams("stateProvince/id eq $id", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);
$events = $eventsResponse['results'] ?? [];

soRenderEntityListing([
	'kind' => 'state', 'id' => $id, 'name' => $stateLabel, 'label' => $stateLabel, 'path' => '/state/' . $canonSlug,
	'events' => $events, 'total' => (int) ($eventsResponse['totalCount'] ?? 0), 'count' => (int) ($eventsResponse['count'] ?? count($events)),
	'perPage' => $perPage, 'params' => $params, 'when' => $when, 'sort' => $sort, 'defaultSort' => 'popular', 'isFiltered' => $isFiltered,
	'trail' => [], 'entity' => $raw, 'cityId' => 0, 'cityLabel' => '', 'image' => null, 'parent' => null, 'stateParent' => null,
]);
