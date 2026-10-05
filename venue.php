<?php
require_once 'functions.php';

// /venue/{name}. All markup lives in soRenderEntityListing() (inc/entity-listing.php); this file validates the URL,
// fetches the venue and its first page of events, and decides 404 / 503 / 301.
$perPage = 20;
$slug = (string) ($_GET['slug'] ?? '');
[$id] = soSlugResolve('venue', $slug);   // stored slug, or the old name-and-id form: a made-up slug never reaches the API
if ($id === null) {
	renderNotFoundPage('Venue');
}

$venue = getTnVenueById($id);
if (tnEntityMissing($venue)) {
	renderNotFoundPage('Venue', $venue);   // 404 when the API says not found, 503 when it failed
}

$venueName  = (string) ($venue['text']['name'] ?? '');
$cityName   = (string) ($venue['city']['text']['name'] ?? '');
$cityId     = (int) ($venue['city']['id'] ?? 0);
$cityLabel  = soPlaceLabel($venue);
$canonSlug  = soVenueSlug($venueName, $id, $cityLabel);
soRedirectToCanonicalSlug('venue', $slug, $canonSlug);

[$when, $sort, $isFiltered] = listingRequestState('soonest');
$params = locationListingParams("venue/id eq $id", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);
$events = $eventsResponse['results'] ?? [];

$stateId   = (int) ($venue['stateProvince']['id'] ?? 0);
$stateName = (string) ($venue['stateProvince']['text']['name'] ?? '');
$trail = [];
if ($stateId > 0 && $stateName !== '') { $trail[] = ['label' => $stateName, 'url' => HOME_URL . '/state/' . soSlug('state', $stateName, $stateId)]; }
$cityUrl = $cityId > 0 && $cityLabel !== '' ? '/city/' . soSlug('city', $cityLabel, $cityId) : '';
if ($cityUrl !== '') { $trail[] = ['label' => $cityLabel, 'url' => HOME_URL . $cityUrl]; }

soRenderEntityListing([
	'kind' => 'venue', 'id' => $id, 'name' => $venueName, 'label' => $venueName, 'path' => '/venue/' . $canonSlug,
	'events' => $events, 'total' => (int) ($eventsResponse['totalCount'] ?? 0), 'count' => (int) ($eventsResponse['count'] ?? count($events)),
	'perPage' => $perPage, 'params' => $params, 'when' => $when, 'sort' => $sort, 'defaultSort' => 'soonest', 'isFiltered' => $isFiltered,
	'trail' => $trail, 'entity' => $venue, 'cityId' => $cityId, 'cityLabel' => $cityLabel, 'cityUrl' => $cityUrl,
	'image' => getEntityImage('venue', $venueName, ['resolve' => false]),   // serve-only: initials tile until a licensed photo is stored
	'parent' => $cityUrl !== '' ? ['url' => $cityUrl, 'text' => "All events in $cityLabel"] : null,
	'stateParent' => $stateId > 0 && $stateName !== '' ? ['url' => '/state/' . soSlug('state', $stateName, $stateId), 'text' => "Events in $stateName"] : null,
]);
