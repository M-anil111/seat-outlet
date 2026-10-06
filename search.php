<?php
require_once 'functions.php';

$searchInput = soStringParams(array_merge($_GET, $_POST));
if (($searchInput['keywordHeader'] ?? '') === '' && trim((string) ($searchInput['q'] ?? '')) !== '') { $searchInput['keywordHeader'] = trim((string) $searchInput['q']); }   // ?q= is the common name for the same search

/*
|--------------------------------------------------------------------------
| CLEAN URL
|--------------------------------------------------------------------------
| The header search form submits every field, filled or not
| (/search?latHeader=&lngHeader=&locationInputHeader=&startInputHeader=&endInputHeader=&keywordHeader=adele). Send the same
| search to the short address instead, so the shared and bookmarked URL only carries what the visitor chose.
*/
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !empty($_GET)) {
	$cleanQuery = [];
	foreach ($_GET as $k => $v) {
		if (is_array($v)) { continue; }
		if (trim((string) $v) !== '') { $cleanQuery[$k] = (string) $v; }
	}
	if (count($cleanQuery) !== count($_GET)) {
		header('Location: /search' . ($cleanQuery ? '?' . http_build_query($cleanQuery) : ''), true, 301);
		exit;
	}
}

$params = [];
$filterParts = [];
$artistData = [];
$venueData = [];
$keywordHeader = '';

/*
|--------------------------------------------------------------------------
| GEO FILTER
|--------------------------------------------------------------------------
*/
if (
    isset($searchInput['latHeader'], $searchInput['lngHeader'], $searchInput['locationInputHeader']) &&
    $searchInput['latHeader'] !== '' && $searchInput['lngHeader'] !== '' &&
	$searchInput['locationInputHeader'] !== ''
) {
	$lat = floatval($searchInput['latHeader']);
    $lng = floatval($searchInput['lngHeader']);
    $params['geoFilter'] = sprintf('nearby(%F, %F, 50mi)', $lat, $lng);
}

/*
|--------------------------------------------------------------------------
| KEYWORD FILTER
|--------------------------------------------------------------------------
*/

if (
	isset($searchInput['keywordHeader']) &&
	$searchInput['keywordHeader'] !== ''
) {
	$keywordHeader = trim((string) $searchInput['keywordHeader']);
	$params['q'] = $keywordHeader;
}else{
	$params['q'] = "*";
}

/*
|--------------------------------------------------------------------------
| QUICK FILTERS (date window, price, sort): plain links, so every state has an address
|--------------------------------------------------------------------------
*/
const SEARCH_SORT = ['' => 'Best match', 'soonest' => 'Soonest', 'price' => 'Lowest price'];
$searchWhen = isset($_GET['when']) && isset(LISTING_WHEN[$_GET['when']]) ? $_GET['when'] : '';
$searchSort = isset($_GET['sort']) && isset(SEARCH_SORT[$_GET['sort']]) ? $_GET['sort'] : '';
$searchMax  = soListingMaxPrice();

/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/
$hasHeaderRange = false;
if (
	isset($searchInput['startInputHeader'], $searchInput['endInputHeader']) &&
	$searchInput['startInputHeader'] !== '' &&
	$searchInput['endInputHeader'] !== ''
) {
	$startTimestamp = strtotime($searchInput['startInputHeader']);
    $endTimestamp   = strtotime($searchInput['endInputHeader']);

    if ($startTimestamp && $endTimestamp) {
        $startDate = date('Y-m-d', $startTimestamp);
        $endDate   = date('Y-m-d', $endTimestamp);
        $filterParts[] = "date/date ge $startDate and date/date le $endDate";
        $hasHeaderRange = true;
    }
}
if (!$hasHeaderRange) {
	$range = listingDateRange($searchWhen);
	$currDate = $range ? $range[0] : date('Y-m-d');
	$filterParts[] = "date/date ge $currDate" . ($range ? " and date/date le {$range[1]}" : '');
}
if ($searchMax > 0) {
	$filterParts[] = 'pricingInfo/lowPrice/value le ' . $searchMax;
}

/*
|--------------------------------------------------------------------------
| COMBINE FILTERS
|--------------------------------------------------------------------------
| Events with tickets listed come first: the search asks for those only, and falls back to the whole list (shown with
| "No tickets listed yet" and a "View Event" button) only when nothing is on sale for the search.
*/
$inStockFilterParts = array_merge($filterParts, ['_metadata/hasTickets eq true']);
$params['filter'] = implode(' and ', $inStockFilterParts);
$perPage = 20;
$params['page'] = 1;
$params['perPage'] = $perPage;
$params['includeTotalCount'] = 'true';
// Best match keeps the API's own relevance order (exact names first); a keyword-less browse and the other sorts are by date or price.
if ($searchSort === 'soonest' || ($searchSort === '' && $keywordHeader === '')) {
	$params['sort'] = 'date/date';
} elseif ($searchSort === 'price') {
	$params['sort'] = 'pricingInfo/lowPrice/value';
}

$isFilteredSearch = $searchWhen !== '' || $searchMax > 0 || $searchSort !== '';

// Search results are user-specific, near-infinite URL variants; keep them
// out of the index. The tab title is set below, once the results are known.
$pageCanonicalUrl    = HOME_URL . '/search';
$pageRobots          = 'noindex, follow';
$pageMetaDescription = 'Search concert, sports, theater and festival tickets by performer, city or venue on Seat Outlet.';

$results = getHeaderSearchEvents($params);

// Zero results for a typed keyword: TicketNetwork search is an exact/prefix
// matcher ("adelle" and "carot top" return nothing). Correct confident typos
// automatically ("Showing results for Adele") and offer close names otherwise.
require_once __DIR__ . '/inc/smart.php';
$correctedFrom = '';
$didYouMean = [];
$noTicketsFallback = false;
if (empty($results['results']) && $keywordHeader !== '') {
    $auto = smartAutoCorrection($keywordHeader);
    if ($auto !== null) {
        $retryParams = $params;
        $retryParams['q'] = $auto;
        $retry = getHeaderSearchEvents($retryParams);
        if (!empty($retry['results'])) {
            $results = $retry;
            $correctedFrom = $keywordHeader;
            $keywordHeader = $auto;
            $params = $retryParams;          // load-more must continue the corrected search
        }
    }
    if (empty($results['results'])) {
        // Nothing on sale: show events with no tickets listed (they can still be opened and watched) before giving up.
        $anyParams = $params;
        $anyParams['filter'] = implode(' and ', $filterParts);
        $any = getHeaderSearchEvents($anyParams);
        if (!empty($any['results'])) {
            $results = $any;
            $params = $anyParams;
            $noTicketsFallback = true;
        }
    }
    if (empty($results['results'])) {
        $didYouMean = smartDidYouMean($keywordHeader, 4);
    }
}
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];
// Exact name first, tribute and similar acts last, in-stock before the rest (Best match only; a chosen sort is respected).
if ($searchSort === '' && $keywordHeader !== '') {
    $events = soRankSearchEvents($events, $keywordHeader);
}
// The visitor's spelling is kept ("AC/DC", not "Ac/dc"); all-lower-case input takes the performer's own spelling or Title Case.
$displayName = $keywordHeader !== '' ? soSearchDisplayName($keywordHeader, $events) : '';
$artistName = $displayName !== '' ? $displayName : 'Event';
$faqs = getFaqs('search');

$pageMetaTitle = $displayName !== ''
    ? soTitle("$displayName Tickets Search Results", "$displayName Tickets")
    : soTitle("Search Event Tickets for Concerts, Sports and Shows", 'Search Event Tickets');
$pageMetaDescription = $displayName !== ''
    ? soMetaFit("$displayName tickets: every matching event, date and venue on Seat Outlet. Compare seats and prices. Orders carry the TicketNetwork guarantee.", 'Live seat maps and secure checkout.')
    : soMetaFit('Search event tickets by artist, team, show, venue or city. Compare seats and prices from many sellers. Orders carry the TicketNetwork guarantee.', 'Live seat maps and secure checkout.');
$pageFocusKeyword = $displayName !== '' ? $displayName . ' Tickets' : 'Search Event Tickets';
$pageCrumbLabel = 'Search';

include 'header.php';

$filterHref = function ($param, $value) use ($searchInput) {
	$q = [];
	foreach (['keywordHeader', 'latHeader', 'lngHeader', 'locationInputHeader', 'startInputHeader', 'endInputHeader', 'when', 'sort', 'max'] as $k) {
		$v = $k === 'when' || $k === 'sort' || $k === 'max' ? ($_GET[$k] ?? '') : ($searchInput[$k] ?? '');
		if (is_string($v) && trim($v) !== '') { $q[$k] = $v; }
	}
	if ($value === '' || $value === '0') { unset($q[$param]); } else { $q[$param] = $value; }
	return '/search' . ($q ? '?' . http_build_query($q) : '');
};
$icCal = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>';
$icPrice = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18M16 7.5c0-1.7-1.8-3-4-3s-4 1.1-4 3 1.8 2.6 4 3.2 4 1.3 4 3.2-1.8 3-4 3-4-1.3-4-3"/></svg>';
$icSort = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4v16M3 16l4 4 4-4M17 20V4M13 8l4-4 4 4"/></svg>';
?>

<?php
$soSearchTitle = $keywordHeader === '' ? 'Search Tickets Online' : ($displayName !== '' ? $displayName : 'Search results');
soPageHero([
	'crumbs'  => $keywordHeader === '' ? [['label' => 'Home', 'url' => '/'], ['label' => 'Search']] : [['label' => 'Home', 'url' => '/'], ['label' => 'Search', 'url' => '/search'], ['label' => $soSearchTitle]],
	'icon'    => 'bi-search',
	'name'    => 'Search',
	'eyebrow' => $keywordHeader === '' ? 'Search' : 'Search results',
	'title'   => $soSearchTitle,
	'stats'   => $keywordHeader === '' ? [] : ['<span id="results_count">' . number_format((int) $total_count) . ' ' . ((int) $total_count === 1 ? 'result' : 'results') . '</span>'],
	'lead'    => $keywordHeader === '' ? 'Find tickets by artist, team, venue or city. Every order is backed by a 100% guarantee.' : '',
	'note'    => $keywordHeader !== '',
]);
?>

<section>
	<div class="container so-search-main">
		
		<?php if(!empty($keywordHeader)) { ?>
			<div class="section-suggestions new-slider py-md-5 py-4">
				<h2 class="fw-bold fs-4 mb-4">Top Suggestions</h2>
				<div class="suggestion-slider"></div>
			</div>
		<?php } ?>

		<div class="tab-section section-performer-content" id="default">
			<div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
				<div class="col-sm-12 col-md-8 left-bar">
					<div class="mb-3 mb-md-4 mb-lg-4">
						<?php if ($correctedFrom !== '') { ?>
							<p class="search-corrected mb-2">Showing results for <strong><?php echo htmlspecialchars($keywordHeader, ENT_QUOTES, 'UTF-8'); ?></strong>. No results for &ldquo;<?php echo htmlspecialchars($correctedFrom, ENT_QUOTES, 'UTF-8'); ?>&rdquo;.</p>
						<?php } ?>
					</div>
					<?php
					// Compact filter bar, same chips as the listing pages: date window, price cap, sort.
					soRenderFilterBar([
						['label' => 'Dates', 'icon' => $icCal, 'param' => 'when', 'options' => ['' => 'All dates'] + LISTING_WHEN, 'current' => $hasHeaderRange ? '' : $searchWhen],
						['label' => 'Price', 'icon' => $icPrice, 'param' => 'max', 'options' => ['0' => 'Any price'] + array_map('strval', LISTING_PRICE), 'current' => (string) $searchMax],
						['label' => 'Sort', 'icon' => $icSort, 'param' => 'sort', 'options' => SEARCH_SORT, 'current' => $searchSort],
					], $filterHref, 'Filter and sort search results');
					if ($noTicketsFallback) { ?>
						<p class="search-note">No tickets are listed for <strong><?php echo htmlspecialchars($keywordHeader, ENT_QUOTES, 'UTF-8'); ?></strong> right now. These dates can still be opened.</p>
					<?php } ?>
					<div class="list-category-bg pb-3">
						<?php if (!empty($events)) {
							soRenderListingBody([
								'events'  => $events,
								'total'   => $total_count,
								'perPage' => $perPage,
								'params'  => $params,
								'type'    => 'search',
							]);
						} else { ?>
							<div class="text-center no-events-found">
								<h4><?php echo $isFilteredSearch ? 'No events match these filters' : 'No Upcoming Events'; ?></h4>
								<?php if (!empty($didYouMean)) { ?>
									<p class="did-you-mean mb-2">Did you mean
										<?php foreach ($didYouMean as $i => $dym) { ?><?php echo $i ? ', ' : ''; ?><a href="<?php echo htmlspecialchars($dym['url'], ENT_QUOTES, 'UTF-8'); ?>"><strong><?php echo htmlspecialchars($dym['name'], ENT_QUOTES, 'UTF-8'); ?></strong></a><?php } ?>?
									</p>
								<?php } ?>
								<?php if ($isFilteredSearch) { ?>
									<p><a href="<?php echo htmlspecialchars($keywordHeader !== '' ? '/search?' . http_build_query(['keywordHeader' => $keywordHeader]) : '/search', ENT_QUOTES, 'UTF-8'); ?>">Clear filters and show everything</a></p>
								<?php } else { ?>
									<p>We're sorry, but we couldn't find any upcoming events for "<?php echo htmlspecialchars($keywordHeader, ENT_QUOTES, 'UTF-8'); ?>". Please try updating your location, date range or searching for something else.</p>
								<?php } ?>
								<?php if ($keywordHeader !== '') {
									echo soLeadForm([
										'source' => 'search-empty',
										'title' => 'Tell me when ' . $keywordHeader . ' tickets are added',
										'text' => 'We will email you when events matching your search go on sale. No spam.',
										'button' => 'Notify me',
										'interest_type' => 'performer',
										'interest_id' => 0,
										'interest_name' => $keywordHeader,
										'names' => true,
										'class' => 'so-nl--empty text-start',
									]);
								} ?>
								<?php
								$popular = [];
								foreach (['concerts', 'sports', 'theatre', 'festival'] as $popTab) {
									foreach (array_slice(cache_get('home_events_' . $popTab, 30 * 86400) ?: [], 0, 2) as $popEv) { $popular[] = $popEv; }
								}
								if (!empty($popular)) { ?>
									<div class="search-popular text-start mt-4">
										<h5 class="fw-bold mb-3">Popular right now</h5>
										<ul class="list-unstyled mb-0">
											<?php foreach (array_slice($popular, 0, 6) as $popEv) { ?>
												<li class="mb-2">
													<a href="/event/<?php echo htmlspecialchars(soEventSlug($popEv), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($popEv['name'], ENT_QUOTES, 'UTF-8'); ?></a>
													<span class="small text-muted"> · <?php echo htmlspecialchars(trim(($popEv['date'] ?? '') . ' · ' . ($popEv['loc'] ?? ''), ' ·'), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($popEv['price']) ? ' · from ' . htmlspecialchars($popEv['price'], ENT_QUOTES, 'UTF-8') : ''; ?></span>
												</li>
											<?php } ?>
										</ul>
										<p class="mt-3 mb-0"><a href="/buy-tickets-online">Browse all tickets</a> · <a href="/concert-tickets-for-sale">Concerts</a> · <a href="/game-day-tickets">Sports</a> · <a href="/buy-broadway-tickets">Theater</a></p>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				</div>
				<div id="secondary" class="sidebar col-sm-12 col-md-4">
					<div class="sticky-top sidebar-inner">
						<div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
							<div class="guarantee">
								<strong>Shop Tickets Worry Free</strong><br>
								<span>With Our 100% Guarantee</span>
							</div>
							<div class="guarantee-icon">
								<i class="bi bi-shield-check"></i>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if (!empty($faqs)) { ?>
			<div class="tab-section content-section-detail" id="faqs">
				<h2 class="so-heading fw-bold fs-4 mb-4 text-black"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> tickets FAQ</h2>
				<div class="accordion" id="faqAccordion">
					<?php foreach ($faqs as $index => $faq) {
						$collapseId = 'collapse' . $index;
						$headingId  = 'heading' . $index;
						$question = str_replace('[artist_name]', htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'), $faq['question']);
						$answer   = str_replace('[artist_name]', htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'), $faq['answer']);
						$isFirst = ($index === 0);
					?>
						<div class="accordion-item">
							<h3 class="accordion-header" id="<?php echo $headingId; ?>">
								<button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>"
										type="button"
										data-bs-toggle="collapse"
										data-bs-target="#<?php echo $collapseId; ?>"
										aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
										aria-controls="<?php echo $collapseId; ?>">
									<?php echo $question; ?>
								</button>
							</h3>
							<div id="<?php echo $collapseId; ?>"
								class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>"
								aria-labelledby="<?php echo $headingId; ?>"
								data-bs-parent="#faqAccordion">
								<div class="accordion-body">
									<?php echo nl2br($answer); ?>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	</div>
</section>


<?php include 'footer.php'; ?>
