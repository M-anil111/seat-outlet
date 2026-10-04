<?php
require_once 'functions.php';
// SEO: this page previously rendered with no <title>/canonical at all.
$pageMetaTitle       = 'Sports Tickets | Seat Outlet';
$pageMetaDescription = 'Buy sports tickets for upcoming games and matchups. Compare prices and book securely on Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/game-day-tickets';
[$when, $sort, $isFiltered] = listingRequestState();
$maxPrice = soListingMaxPrice();
if ($maxPrice > 0) { $isFiltered = true; }
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // filtered/sorted variants: canonical page stays the indexed one
$perPage = 20;
$params = categoryListingParams(TN_CATEGORY_PATH_SPORTS, $perPage, 1, $when, $sort, $maxPrice);
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];
// Upcoming events as an ItemList of Event nodes (the same rows the page lists below).
$pageJsonLdNodes = array_merge($pageJsonLdNodes ?? [], [soEventItemList($events, $pageCanonicalUrl ?? '')]);
include 'header.php';

soRenderListingPage([
	'h1'       => 'Game Day Tickets',
	'crumbs'   => [['label' => 'Home', 'url' => '/'], ['label' => 'Sports']],
	'eyebrow'  => 'Sports',
	'lead_text' => 'Tickets for NFL, NBA, MLB, NHL, MLS and college games, with live seat maps and a 100% guarantee on every order.',
	'total'    => $total_count,
	'basePath' => '/game-day-tickets',
	'when'     => $when,
	'sort'     => $sort,
	'max'      => $maxPrice,
	'body'     => [
		'events'  => $events,
		'perPage' => $perPage,
		'params'  => $params,
		'empty'   => ['basePath' => '/game-day-tickets', 'noun' => 'games', 'when' => $when, 'max' => $maxPrice, 'fragment' => soCategoryFragment(TN_CATEGORY_PATH_SPORTS), 'kind' => 'category', 'id' => 0, 'name' => 'Sports', 'alts' => soListingHubAlts('/game-day-tickets')],
	],
	'lead'     => ['source' => 'listing', 'title' => 'Get alerts when new games are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => 0, 'interest_name' => 'Sports', 'names' => true],
	'afterRow' => function () use ($events) { renderCategoryCityLinksBlock($events, 'sports-city', 'Sports'); },
]);
?>

<?php soSeoCopy('game-day-tickets'); ?>
<?php include 'footer.php'; ?>
