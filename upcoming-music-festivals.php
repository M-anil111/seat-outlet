<?php
require_once 'functions.php';
require_once __DIR__ . '/inc/directories.php';
// SEO: this page previously rendered with no <title>/canonical at all.
$pageMetaTitle       = 'Festival Tickets | Seat Outlet';
$pageMetaDescription = 'Buy festival tickets for upcoming music and cultural festivals. Compare prices and book securely on Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/upcoming-music-festivals';
[$when, $sort, $isFiltered] = listingRequestState();
$maxPrice = soListingMaxPrice();
if ($maxPrice > 0) { $isFiltered = true; }
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // filtered/sorted variants: canonical page stays the indexed one
$perPage = 20;
$params = categoryListingParams(LOCATION_CATEGORY_PATHS['festivals'], $perPage, 1, $when, $sort, $maxPrice);
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];
// Upcoming events as an ItemList of Event nodes (the same rows the page lists below).
$pageJsonLdNodes = array_merge($pageJsonLdNodes ?? [], [soEventItemList($events, $pageCanonicalUrl ?? '')]);
include 'header.php';

soRenderListingPage([
	'h1'       => 'Upcoming Music Festivals',
	'crumbs'   => [['label' => 'Home', 'url' => '/'], ['label' => 'Festivals']],
	'eyebrow'  => 'Festivals',
	'lead_text' => 'Music festival passes and single-day tickets across the US, with a 100% guarantee on every order.',
	'total'    => $total_count,
	'basePath' => '/upcoming-music-festivals',
	'when'     => $when,
	'sort'     => $sort,
	'max'      => $maxPrice,
	'body'     => [
		'events'  => $events,
		'perPage' => $perPage,
		'params'  => $params,
		'empty'   => ['basePath' => '/upcoming-music-festivals', 'noun' => 'festivals', 'when' => $when, 'max' => $maxPrice, 'fragment' => soCategoryFragment(LOCATION_CATEGORY_PATHS['festivals']), 'kind' => 'category', 'id' => 0, 'name' => 'Festivals', 'alts' => soListingHubAlts('/upcoming-music-festivals')],
	],
	'lead'     => ['source' => 'listing', 'title' => 'Get alerts when new festivals are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => 0, 'interest_name' => 'Festivals', 'names' => true],
	'afterRow' => function () use ($events) { renderCategoryCityLinksBlock($events, 'festivals-city', 'Festival'); },
]);
?>

<?php soSeoCopy('upcoming-music-festivals'); ?>
<?php echo soDirectoryLinksHtml('.1859.1986.1877.', 7); ?>
<?php include 'footer.php'; ?>
