<?php
require_once 'functions.php';
// SEO: this page previously rendered with no <title>/canonical at all.
$pageMetaTitle       = 'Concert Tickets | Seat Outlet';
$pageMetaDescription = 'Buy concert tickets for upcoming shows and tours. Compare prices and book securely on Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/concert-tickets-for-sale';
[$when, $sort, $isFiltered] = listingRequestState();
$maxPrice = soListingMaxPrice();
if ($maxPrice > 0) { $isFiltered = true; }
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // filtered/sorted variants: canonical page stays the indexed one
$perPage = 20;
$params = categoryListingParams(TN_CATEGORY_PATH_CONCERTS, $perPage, 1, $when, $sort, $maxPrice);
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];
// Upcoming events as an ItemList of Event nodes (the same rows the page lists below).
$pageJsonLdNodes = array_merge($pageJsonLdNodes ?? [], [soEventItemList($events, $pageCanonicalUrl ?? '')]);
include 'header.php';

soRenderListingPage([
	'h1'       => 'Concert Tickets for Sale',
	'crumbs'   => [['label' => 'Home', 'url' => '/'], ['label' => 'Concerts']],
	'eyebrow'  => 'Concerts',
	'lead_text' => 'Compare seats and prices for concerts across the US, from arenas to small clubs, with a 100% guarantee on every order.',
	'total'    => $total_count,
	'basePath' => '/concert-tickets-for-sale',
	'when'     => $when,
	'sort'     => $sort,
	'max'      => $maxPrice,
	'body'     => [
		'events'  => $events,
		'perPage' => $perPage,
		'params'  => $params,
		'empty'   => ['basePath' => '/concert-tickets-for-sale', 'noun' => 'concerts', 'when' => $when, 'max' => $maxPrice, 'fragment' => soCategoryFragment(TN_CATEGORY_PATH_CONCERTS), 'kind' => 'category', 'id' => 0, 'name' => 'Concerts', 'alts' => soListingHubAlts('/concert-tickets-for-sale')],
	],
	'lead'     => ['source' => 'listing', 'title' => 'Get alerts when new concerts are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => 0, 'interest_name' => 'Concerts', 'names' => true],
	'afterRow' => function () use ($events) { renderCategoryCityLinksBlock($events, 'concerts-city', 'Concert'); },
]);
?>

<?php soSeoCopy('concert-tickets-for-sale'); ?>
<?php include 'footer.php'; ?>
