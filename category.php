<?php
require_once 'functions.php';
require_once __DIR__ . '/inc/genre-pages.php';
require_once __DIR__ . '/inc/seo-category.php';
require_once __DIR__ . '/inc/genre-focus.php';

// Sanitize and normalize pagination.
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;

// Extract performer ID from slug; expect a trailing numeric ID.
$slug  = $_GET['slug'] ?? '';
$parts = explode('-', (string) $slug);
$id    = (int) end($parts);

if ($id <= 0) {
	renderNotFoundPage('Category');
}

$cat = getTnCatById($id);
$catRaw  = trim($cat['results'][0]['text']['name'] ?? '');
// Title Case a name that arrives all upper or all lower case, but leave mixed-case and short acronyms (NBA, UFC) as they are.
$catName = (strlen($catRaw) > 4 && ($catRaw === strtoupper($catRaw) || $catRaw === strtolower($catRaw))) ? ucwords(strtolower($catRaw)) : $catRaw;

if ($catName === '') {
	renderNotFoundPage('Category');
}

// Clean URL for the big genres and leagues: /category/rap-hip-hop-1906 -> /hip-hop-tickets (301), and /hip-hop-tickets is served
// by a small stub that includes this file.
$soGenre = isset($soGenreSlug) ? soGenreBySlug($soGenreSlug) : soGenreById($id);
$catBasePath = $soGenre ? '/' . $soGenre['slug'] : '/category/' . $slug;
if ($soGenre && !isset($soGenreSlug)) {
	parse_str($_SERVER['QUERY_STRING'] ?? '', $soQs);
	unset($soQs['slug']);   // the server rewrite passes the old /category/<name>-<id> path as ?slug=, which must not leak into the clean URL
	$qs = http_build_query($soQs);
	header('Location: ' . $catBasePath . ($qs !== '' ? '?' . $qs : ''), true, 301);
	exit;
}
$catLabel = $soGenre['label'] ?? $catName;
$soCatCfg = ['id' => $id, 'label' => $catLabel, 'long' => $soGenre['long'] ?? strtolower($catName), 'kind' => $soGenre['kind'] ?? 'other', 'profile' => $soGenre['profile'] ?? null, 'slug' => $soGenre['slug'] ?? ''];
$soCatData = soCategoryData($id);
$soCatSeo = soCategorySeo($soCatCfg, $soCatData);
$soCatHero = soCategoryHero($id, $soCatData['path'] ?? '');
$year = date('Y');
// Which hub (concerts, sports, theater) this category sits under, from its category path: used to mark the right tab and show its sub-categories.
$soCatPath = (string) ($cat['results'][0]['path'] ?? ($soCatData['path'] ?? ''));   // the category's own place in the tree (events are not needed)
$soFamily = strpos($soCatPath, '.1988.') !== false ? '/game-day-tickets' : (strpos($soCatPath, '.1989.') !== false ? '/buy-broadway-tickets' : (strpos($soCatPath, '.1986.') !== false ? '/concert-tickets-for-sale' : ''));
if ($id === 1872) $soFamily = '/buy-broadway-tickets';   // comedy is listed with the shows (Theater tab), although the API files it under concerts
if ($soFamily === '' && isset($soGenre['kind'])) { $soFamily = $soGenre['kind'] === 'sports' ? '/game-day-tickets' : ($soGenre['kind'] === 'concerts' ? '/concert-tickets-for-sale' : '/buy-broadway-tickets'); }

// --- SEO: computed before including header.php, same convention as the other listing pages - see functions.php. ---
$soCatDates          = ($soGenre['kind'] ?? '') === 'sports' ? 'Schedule & Prices' : 'Dates & Prices';   // sports searches are for the schedule ("phillies schedule", "nba tickets")
$pageMetaTitle       = soTitle("$catLabel Tickets $year $soCatDates", "$catLabel Tickets $year", "$catLabel Tickets");
$pageMetaDescription = soMetaFit($soCatSeo['description'], 'Live seat maps and secure checkout.', 'Prices from many sellers in one place.');
$soFocusMeta = !empty($soGenre['slug']) ? soGenreFocusMeta($soGenre['slug']) : null;   // keyword-targeted title, H1 and description (inc/genre-focus.php)
if ($soFocusMeta) { $pageMetaTitle = soTitle($soFocusMeta['title'], str_replace(' Schedule & Prices', '', str_replace(' Dates and Prices', '', $soFocusMeta['title'])), $soFocusMeta['h1']); $pageMetaDescription = $soFocusMeta['desc']; }
$pageCanonicalUrl    = HOME_URL . $catBasePath;
$pageJsonLdNodes     = array_values(array_filter([
	buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL . '/'], soFamilyCrumb($soFamily)], "$catLabel Tickets"),
	buildFaqPageSchema($soCatSeo['faqs']),
]));

[$when, $sort, $isFiltered] = listingRequestState('popular');
$maxPrice = soListingMaxPrice();
if ($maxPrice > 0) { $isFiltered = true; }
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // canonical page stays the indexed one


// US events only, the same set the near-you grid, the page copy and the "in the USA" heading describe.
$catFragment = "contains(defaultCategory/path, '.$id.') and country/alphaCode eq 'US'";
$params = locationListingParams($catFragment, $perPage, 1, $when, $sort, $maxPrice);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);

// totalCount rides on the list response (includeTotalCount) - this used to
// be a second, separate API call per page view.
$total_count = (int) ($eventsResponse['totalCount'] ?? 0);
$events = $eventsResponse['results'] ?? [];
// A failed feed is a 503 (retry), never a "0 results" page that looks real; a category that truly has no events stays out of the index.
if ($total_count === 0 && !$events && soApiDegraded()) { renderUnavailablePage($catLabel . ' tickets'); }
// Upcoming events as an ItemList of Event nodes (the same rows the page lists below).
$pageJsonLdNodes = array_merge($pageJsonLdNodes ?? [], [soEventItemList($events, $pageCanonicalUrl ?? '')]);
include 'header.php';
$catInline = soListingInline($catLabel);   // "hip hop", but NBA / MLB / R&B keep their capitals

soRenderListingPage([
	'h1'          => $soFocusMeta['h1'] ?? ($catLabel . ' Tickets'),
	'crumbs'      => [['label' => 'Home', 'url' => '/'], soFamilyCrumb($soFamily), ['label' => $catLabel . ' Tickets']],
	'eyebrow'     => soFamilyCrumb($soFamily)['label'],
	'eyebrowUrl'  => soFamilyCrumb($soFamily)['url'],
	'total'       => $total_count,
	'basePath'    => $catBasePath,
	'when'        => $when,
	'sort'        => $sort,
	'max'         => $maxPrice,
	'explore'     => ['catId' => $id, 'noun' => $catInline . ' events', 'hero' => $soCatHero, 'family' => $soFamily, 'label' => $catLabel],
	'body'        => [
		'events'  => $events,
		'perPage' => $perPage,
		'params'  => $params,
		'empty'   => [
			'basePath' => $catBasePath, 'noun' => $catInline . ' events', 'when' => $when, 'max' => $maxPrice, 'fragment' => $catFragment,
			'kind' => 'category', 'id' => $id, 'name' => $catLabel, 'alts' => soListingAltCategories($id),
		],
	],
	'lead'        => ['source' => 'listing', 'title' => 'Get alerts when new ' . $catInline . ' events are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => $id, 'interest_name' => $catLabel, 'names' => true],
]);
?>
<?php echo $soCatSeo['html']; ?>
<?php include 'footer.php'; ?>
