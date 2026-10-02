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
include 'header.php';
$perPage = 20;
$params = categoryListingParams(TN_CATEGORY_PATH_SPORTS, $perPage, 1, $when, $sort, $maxPrice);
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];

soRenderListingPage([
	'h1'       => 'GAME DAY TICKETS',
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
	'lead'     => ['source' => 'listing', 'title' => 'Get alerts when new games are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => 0, 'interest_name' => 'Sports', 'names' => false],
	'afterRow' => function () use ($events) { renderCategoryCityLinksBlock($events, 'sports-city', 'Sports'); },
]);
?>

<?php soSeoCopy('game-day-tickets'); ?>
<?php include 'footer.php'; ?>
