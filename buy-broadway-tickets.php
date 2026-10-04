<?php
require_once 'functions.php';
// SEO: this page previously rendered with no <title>/canonical at all.
$pageMetaTitle       = 'Theater Tickets | Seat Outlet';
$pageMetaDescription = 'Buy theater tickets for upcoming shows and performances. Compare prices and book securely on Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/buy-broadway-tickets';
[$when, $sort, $isFiltered] = listingRequestState();
$maxPrice = soListingMaxPrice();
if ($maxPrice > 0) { $isFiltered = true; }
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // filtered/sorted variants: canonical page stays the indexed one
$perPage = 20;
$params = categoryListingParams(TN_CATEGORY_PATH_THEATER, $perPage, 1, $when, $sort, $maxPrice);
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];
// Upcoming events as an ItemList of Event nodes (the same rows the page lists below).
$pageJsonLdNodes = array_merge($pageJsonLdNodes ?? [], [soEventItemList($events, $pageCanonicalUrl ?? '')]);
include 'header.php';

soRenderListingPage([
	'h1'       => 'BUY BROADWAY TICKETS',
	'total'    => $total_count,
	'basePath' => '/buy-broadway-tickets',
	'when'     => $when,
	'sort'     => $sort,
	'max'      => $maxPrice,
	'body'     => [
		'events'  => $events,
		'perPage' => $perPage,
		'params'  => $params,
		'empty'   => ['basePath' => '/buy-broadway-tickets', 'noun' => 'shows', 'when' => $when, 'max' => $maxPrice, 'fragment' => soCategoryFragment(TN_CATEGORY_PATH_THEATER), 'kind' => 'category', 'id' => 0, 'name' => 'Theater', 'alts' => soListingHubAlts('/buy-broadway-tickets')],
	],
	'lead'     => ['source' => 'listing', 'title' => 'Get alerts when new theater shows are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => 0, 'interest_name' => 'Theater', 'names' => true],
	'afterRow' => function () use ($events) { renderCategoryCityLinksBlock($events, 'theater-city', 'Theater'); },
]);
?>

<?php soSeoCopy('buy-broadway-tickets'); ?>
<?php include 'footer.php'; ?>
