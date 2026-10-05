<?php
require_once 'functions.php';

// /country/{name}. Markup in soRenderEntityListing() (inc/entity-listing.php).
$perPage = 20;
$slug = (string) ($_GET['slug'] ?? '');
$code = parseLocationSlug('country', $slug);   // two-letter alpha code, upper case
if ($code === null) {
	renderNotFoundPage('Country');
}

$country = getLocationDisplayInfo('country', $code, $raw);
if (empty($country)) {
	renderNotFoundPage('Country', $raw);   // 404 when the API says not found, 503 when it failed
}

$countryLabel = $country['label'];
$canonSlug    = soSlug('country', $countryLabel, $code);
soRedirectToCanonicalSlug('country', $slug, $canonSlug);

[$when, $sort, $isFiltered] = listingRequestState('popular');
$params = locationListingParams("country/alphaCode eq '" . tnEscapeFilterValue($code) . "'", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);
$events = $eventsResponse['results'] ?? [];

soRenderEntityListing([
	'kind' => 'country', 'id' => $code, 'name' => $countryLabel, 'label' => $countryLabel, 'path' => '/country/' . $canonSlug,
	'events' => $events, 'total' => (int) ($eventsResponse['totalCount'] ?? 0), 'count' => (int) ($eventsResponse['count'] ?? count($events)),
	'perPage' => $perPage, 'params' => $params, 'when' => $when, 'sort' => $sort, 'defaultSort' => 'popular', 'isFiltered' => $isFiltered,
	'trail' => [], 'entity' => $raw, 'cityId' => 0, 'cityLabel' => '', 'image' => null, 'parent' => null, 'stateParent' => null,
]);
