<?php 
require_once 'functions.php';

$searchInput = soStringParams(array_merge($_GET, $_POST));

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
	$keywordHeader = $searchInput['keywordHeader'];
	$keywordTitle = ucfirst(strtolower($keywordHeader));
	$params['q'] = $keywordHeader;
}else{
	$params['q'] = "*";
}

/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/
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
    }
}else{
	$currDate = date('Y-m-d');
	$filterParts[] = "date/date ge $currDate";
}


/*
|--------------------------------------------------------------------------
| COMBINE FILTERS
|--------------------------------------------------------------------------
*/
if (!empty($filterParts)) {
    $params['filter'] = implode(' and ', $filterParts);
}
$perPage = 20;
$params['page'] = 1;
$params['perPage'] = $perPage;
$params['sort'] = 'date/date';

// Search results are user-specific, near-infinite URL variants; keep them
// out of the index but give the tab a real title (there was none).
if ($keywordHeader !== '') { $pageFocusKeyword = ucwords(strtolower($keywordHeader)) . ' Tickets'; }
$pageMetaTitle       = ($keywordHeader !== '' ? ucwords(strtolower($keywordHeader)) . ' Tickets - Search Results' : 'Search Tickets') . ' | Seat Outlet';
$pageMetaDescription = 'Search concert, sports, theater and festival tickets by performer, city or venue on Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/search';
$pageRobots          = 'noindex, follow';

include 'header.php'; 

$year = date('Y');
$results = getHeaderSearchEvents($params);

// Zero results for a typed keyword: TicketNetwork search is an exact/prefix
// matcher ("adelle" and "carot top" return nothing). Correct confident typos
// automatically ("Showing results for Adele") and offer close names otherwise.
require_once __DIR__ . '/inc/smart.php';
$correctedFrom = '';
$didYouMean = [];
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
        $didYouMean = smartDidYouMean($keywordHeader, 4);
    }
}
$total_count = $results['totalCount'] ?? 0;
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
$events = $results['results'] ?? [];
$count = count($events);
$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
$faqs = getFaqs('search');
?>

<!-- Hero Section -->
<section class="search-hero-section section-padding">
	<div class="container">		
		<!-- Hero Content -->
		<div class="row justify-content-center align-items-center">
			<div class="col-lg-7 col-md-8">
				<h1 class="hero-title text-center">
					<span class="hero-title-white">Search Tickets Online</span>
				</h1>
			</div>
		</div>
	</div>
</section>

<section>
	<div class="container">
		
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
						<div class="d-flex justify-content-between align-items-center results-header">
							<div class="results-title">
								<span class="active-indicator"></span>
								<h2>
									EVENTS <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $total_count; ?>
										<?php echo $total_count > 1 ? 'RESULTS' : 'RESULT'; ?>
									</span>
								</h2>							
							</div>
						</div>
					</div>
					<div class="list-category-bg pb-3">
						<?php if (!empty($events)) { ?>
							<div id="eventsSection" class="section-artist-content event-row-all">
								<?php foreach ($events as $event) { 
									$eventDateRaw = $event['date']['date'];
									$timestamp    = strtotime($eventDateRaw);
									$evtPerformers = $event['performers'] ?? [];
									$names = array_map(function ($performer) {
										return $performer['name'] ?? null;
									}, $evtPerformers);
									$performerSlugs = array_map(function ($performer) {
										$name = $performer['name'] ?? '';
										$id   = $performer['id'] ?? '';
									
										$slug = strtolower(trim($name));
										$slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
										$slug = trim($slug, '-');
									
										return $slug && $id ? $slug . '-' . $id : null;
									}, $evtPerformers);
									$dataPerformers = implode('|', array_filter($names));	
									$dataPerformerSlugs  = implode('|', array_filter($performerSlugs));
									$slug = createSlug($event['text']['name'], $event['id']);
									$city = $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr'];
									$citySlug = createSlug($city, $event['city']['id']);
									$venueSlug = createSlug($event['venue']['text']['name'], $event['venue']['id']);
								?>
									<div class="d-flex align-items-center justify-content-between performer-event-item">
										<div class="date-box text-center me-3">
											<div class="month">
												<?php echo strtoupper(date('M', $timestamp)); ?>
											</div>
											<div class="day">
												<?php echo date('d', $timestamp); ?>
											</div>
											<?php if(date('Y', $timestamp) > $year) { ?>
												<div class="month">
													<?php echo date('Y', $timestamp); ?>
												</div>
											<?php } ?>
										</div>
										<div class="flex-grow-1 w-50">
											<div class="d-flex align-items-center gap-2">
												<span class="fw-semibold day-weeks">
													<?php echo date('D', $timestamp); ?>
												</span>
												<span class="dot">·</span>
												<span class="time-clock">
													<?php echo $event['date']['text']['time']; ?>
												</span>
												<i
													class="bi bi-info-circle text-muted icon-i"
													data-bs-toggle="offcanvas"
													data-bs-target="#offcanvasRight"
													aria-controls="offcanvasRight"
													data-id="<?php echo (int) ($event['id'] ?? 0); ?>"
													data-date="<?php echo date('D, M d', $timestamp); ?>"
													data-venue="<?php echo $event['venue']['text']['name']; ?>"
													data-venueSlug="<?php echo $venueSlug; ?>"
													data-location="<?php echo $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']; ?>"
													data-title="<?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-performers="<?php echo htmlspecialchars($dataPerformers); ?>"
													data-performer-slugs="<?php echo htmlspecialchars($dataPerformerSlugs); ?>"
												></i>
											</div>
											<div class="ev-venue"><a href="/venue/<?php echo $venueSlug; ?>"><?php echo $event['venue']['text']['name']; ?></a></div>
<div class="ev-place"><a href="/city/<?php echo $citySlug; ?>"><?php echo $city; ?></a></div>
											<div class="ev-name">
												<a href="/event/<?php echo $slug; ?>"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?><span class="visually-hidden"> tickets, <?php echo htmlspecialchars(date('M j', $timestamp) . ' at ' . ($event['venue']['text']['name'] ?? '') . ', ' . $city, ENT_QUOTES, 'UTF-8'); ?></span></a>
											</div>
										</div>
										<div class="ms-3">
											<?php renderEventPriceTag($event); ?>
											<a href="/event/<?php echo $slug; ?>" class="btn btn-primary d-flex align-items-center gap-2" aria-label="Find tickets for <?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
												<span class="d-none d-md-inline">
													Buy Tickets
												</span>
												<i class="bi bi-chevron-right"></i>
											</a>
										</div>
									</div>
								<?php } ?>
							</div>
							<?php if ($total_pages > 1) { ?>
								<div class="load-more-wrapper text-center mt-5">
									<div class="load-progress mx-auto mb-3">
										<div class="small mb-2">
											Loaded <strong id="loadedCount"><?php echo $count; ?></strong> out of <strong id="totalCount"><?php echo $total_count; ?></strong> events
										</div>
										<div class="progress progress-thin">
											<div class="progress-bar" id="progressBar" style="width: <?php echo $percent; ?>%;"></div>
										</div>
									</div>
									<button
										class="btn more-events-btn d-inline-flex align-items-center gap-2"
										id="loadMoreBtn"
										data-total="<?php echo (int) $total_count; ?>"
										data-type="search"
										data-page="2"
										data-params="<?php echo htmlspecialchars(json_encode($params), ENT_QUOTES, 'UTF-8'); ?>"
										data-perpage="<?php echo (int) $perPage; ?>">
										<span class="btn-text">More Events</span>
										<span class="btnSpinner spinner-border spinner-border-sm d-none"></span>
										<i class="bi bi-chevron-down"></i>
									</button>
									<button class="btn more-events-btn d-inline-flex align-items-center gap-2 d-none" id="backToTopJs">
										<span class="btn-text">Back to Top</span>
										<span class="btnSpinner spinner-border spinner-border-sm d-none"></span>
										<i class="bi bi-chevron-up"></i>
									</button>
								</div>
							<?php } ?>
						<?php } else { ?>
							<div class="text-center no-events-found">
								<h4>No Upcoming Events</h4>
								<?php if (!empty($didYouMean)) { ?>
									<p class="did-you-mean mb-2">Did you mean
										<?php foreach ($didYouMean as $i => $dym) { ?><?php echo $i ? ', ' : ''; ?><a href="<?php echo htmlspecialchars($dym['url'], ENT_QUOTES, 'UTF-8'); ?>"><strong><?php echo htmlspecialchars($dym['name'], ENT_QUOTES, 'UTF-8'); ?></strong></a><?php } ?>?
									</p>
								<?php } ?>
								<p>We're sorry, but we couldn't find any upcoming events for "<?php echo htmlspecialchars($keywordHeader, ENT_QUOTES, 'UTF-8'); ?>". Please try updating your location, date range or searching for something else.</p>
							
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
													<a href="/event/<?php echo htmlspecialchars(createSlug($popEv['name'], $popEv['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($popEv['name'], ENT_QUOTES, 'UTF-8'); ?></a>
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
	
					<div class="tab-section content-section-detail mb-0" id="promocode">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">Exclusive Discounts on Event Tickets</h2>
						<p>Have a promo code? Enter it in the promo code field at checkout when one is offered. Codes apply only where the checkout accepts them, and savings vary by event.</p>
						<div class="row g-3 mt-2">
							<div class="col-md-6">
								<div class="offer-pill d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center">
										<div class="offer-icon me-3 d-flex align-items-center justify-content-center">
											<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
												<path d="M3 12.5V5.8A1.8 1.8 0 0 1 4.8 4h6.7L21 13.5l-6.4 6.4L3 12.5Z"
													stroke="white" stroke-width="1.6" stroke-linejoin="round"></path>
												<circle cx="8.2" cy="8.2" r="1.1" fill="white"></circle>
											</svg>
										</div>
										<div class="offer-text">
											<div class="offer-title">5% OFF</div>
											<div class="offer-subtitle">TAKE5</div>
										</div>
									</div>
									<div class="offer-copy text-end">
										<button type="button"
											class="btn btn-primary text-white offer-copy-btn btn-sm px-4 rounded-pill"
											data-code="TAKE5">
											Copy
										</button>
									</div>
								</div>
							</div>
							<div class="col-md-6">
								<div class="offer-pill d-flex align-items-center justify-content-between">
									<div class="d-flex align-items-center">
										<div class="offer-icon me-3 d-flex align-items-center justify-content-center">
											<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
												<path d="M3 12.5V5.8A1.8 1.8 0 0 1 4.8 4h6.7L21 13.5l-6.4 6.4L3 12.5Z"
													stroke="white" stroke-width="1.6" stroke-linejoin="round"></path>
												<circle cx="8.2" cy="8.2" r="1.1" fill="white"></circle>
											</svg>
										</div>
										<div class="offer-text">
											<div class="offer-title">10% OFF</div>
											<div class="offer-subtitle">TAKE10</div>
										</div>
									</div>
									<div class="offer-copy text-end">
										<button type="button"
											class="btn btn-primary text-white offer-copy-btn btn-sm px-4 rounded-pill"
											data-code="TAKE10">
											Copy
										</button>
									</div>
								</div>
							</div>
						</div>				
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
				<h2 class="so-heading fw-bold fs-4 mb-4 text-black">FAQs about <?php echo $artistName; ?> Events</h2>
				<div class="accordion" id="faqAccordion">
					<?php foreach ($faqs as $index => $faq) {
						$collapseId = 'collapse' . $index;
						$headingId  = 'heading' . $index;
						$question = str_replace('[artist_name]', $artistName, $faq['question']);
						$answer   = str_replace('[artist_name]', $artistName, $faq['answer']);
						$isFirst = ($index === 0);
					?>
						<div class="accordion-item">
							<h2 class="accordion-header" id="<?php echo $headingId; ?>">
								<button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>" 
										type="button"
										data-bs-toggle="collapse"
										data-bs-target="#<?php echo $collapseId; ?>"
										aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>"
										aria-controls="<?php echo $collapseId; ?>">
									<?php echo $question; ?>
								</button>
							</h2>
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
