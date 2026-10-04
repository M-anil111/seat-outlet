<?php
require_once 'functions.php';

// Sanitize and normalize pagination.
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;

// Extract performer ID from slug; strict (digits only, at most 2147483647), so junk never reaches the API.
$slug = (string) ($_GET['slug'] ?? '');
$id   = soSlugTrailingId($slug);

if ($id === null) {
	renderNotFoundPage('Performer');
}

// Performer and its first page of events are independent: fetch together.
tnRequestMulti([['/catalog/v2/performers/' . $id, []], performerPageEventsSpec($id, $perPage)]);
$performer = getTnPerformerById($id);

if (tnEntityMissing($performer) || empty($performer['defaultCategory'])) {
	renderNotFoundPage('Performer', $performer);
}

$artistName = (string) $performer['text']['name'];
soRedirectToCanonicalSlug('artist', $slug, soEntitySlug($artistName, $id));   // 301 when the name part is wrong

$today = date('Y-m-d');
[$params, $results] = getPerformerPageEvents($id, $perPage);
$total_count = (int) ($results['totalCount'] ?? 0);
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
$events = $results['results'] ?? [];
$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
$priceSnapshot = performerPriceSnapshot($events);
// Cheapest date among the listed events (only meaningful with 2+ priced dates).
$cheapestEventId = 0; $pricedDates = 0; $cheapestValue = null;
foreach ($events as $ev) {
    $lv = $ev['pricingInfo']['lowPrice']['value'] ?? null;
    if ($lv === null || (float) $lv <= 0) continue;
    $pricedDates++;
    if ($cheapestValue === null || (float) $lv < $cheapestValue) { $cheapestValue = (float) $lv; $cheapestEventId = (int) $ev['id']; }
}
if ($pricedDates < 2) { $cheapestEventId = 0; }
$nextEvent = $events[0] ?? null;
// Multi-day festival at one site: "Weekend 1 / Weekend 2" chips (only when every date is already on the page).
$weekendGroups = ($total_count <= count($events)) ? soWeekendGroups($events) : [];
$weekendOf = [];
foreach ($weekendGroups as $wi => $wg) { foreach ($wg['ids'] as $wid) { $weekendOf[$wid] = $wi + 1; } }

$sep = '<span class="separator"><strong> / </strong></span>';
$breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);
$relatedPerformersResponse = getRelatedPerformers($performer['defaultCategory']['path'], $id);
$relatedPerformers = $relatedPerformersResponse;
$year = date('Y');
$performer_bio = getArtistBio($artistName, $id);
$faqs = getFaqs('performer');
$imageType = imageEntityTypeForPerformer($performer['defaultCategory'] ?? []);
// Serve-only: the stored, licensed picture or an initials tile. The lookup is queued for cron / the background worker and
// never runs inside this request (a first visit used to wait on up to a minute of third-party calls).
$performerImg    = getEntityImage($imageType, $artistName, ['category' => $performer['defaultCategory'] ?? [], 'resolve' => false]);
$performer_image = $performerImg['url'];
$hasRealImage    = soImageIsReal($performerImg);
$pageOgImage     = $hasRealImage ? $performer_image : null;

// An outage is not an empty page (503 via the shared guard); a real zero-event artist is noindex,follow and left out of the sitemap.
if ($total_count === 0 && !$events && soApiDegraded()) { renderUnavailablePage('Performer'); }
if ($total_count === 0) { $pageRobots = 'noindex, follow'; }
soZeroPageNote('/artist/' . soEntitySlug($artistName, $id), $total_count === 0);

// --- SEO: computed before including header.php, same convention as the
// artist-city/concerts-city/etc. pages - see functions.php. This page was
// previously rendering with no <title> and no canonical tag at all. ---
$soCatPath = (string) ($performer['defaultCategory']['path'] ?? '');
$soNoun  = strpos($soCatPath, TN_CATEGORY_PATH_SPORTS) === 0 ? ['games', 'Game', 'schedule'] : (strpos($soCatPath, TN_CATEGORY_PATH_THEATER) === 0 ? ['shows', 'Show', 'dates'] : ['concerts', 'Concert', 'tour dates']);
$soDatesWord = $soNoun[0] === 'games' ? 'Schedule' : ($soNoun[0] === 'shows' ? 'Show Dates' : 'Tour Dates');
$pageMetaTitle       = soTitle("$artistName Tickets\u{2014}$year $soDatesWord & Prices", "$artistName Tickets\u{2014}$year $soDatesWord", "$artistName Tickets\u{2014}$year", "$artistName Tickets");
$pageMetaDescription = soMetaFit("Buy $artistName tickets for every upcoming $artistName {$soNoun[0]}. Compare seats on live seat maps and buy with our 100% buyer guarantee.", 'Secure checkout and on time delivery.');
$pageCanonicalUrl    = HOME_URL . '/artist/' . soEntitySlug($artistName, $id);   // the same slug every internal link uses
if ($priceSnapshot['from'] !== '' && $total_count > 0) {
    $pageMetaDescription = soMetaFit("$artistName tickets from {$priceSnapshot['from']} for $total_count upcoming " . ($total_count === 1 ? rtrim($soNoun[0], 's') : $soNoun[0]) . ". Compare seats on live seat maps and buy with our 100% buyer guarantee.", 'Prices from many sellers in one place.', 'Secure checkout and on time delivery.');
}
// BreadcrumbList + an Event node per listed date (the page emitted only the
// site-wide Organization/WebSite graph before).
$soFaqs = [];
$soNext = $events[0] ?? null;
if ($priceSnapshot['from'] !== '' && $total_count > 0) {
    $soFaqs[] = ['question' => "How much are $artistName tickets?", 'answer' => "$artistName tickets start from {$priceSnapshot['from']} on Seat Outlet across $total_count upcoming " . ($total_count === 1 ? 'date' : 'dates') . ". Prices are set by sellers, change with demand and can be above or below face value, so compare seats and sections before you buy."];
}
if ($soNext) {
    $soNextWhen = date('l, F j, Y', strtotime($soNext['date']['date'] ?? 'now'));
    $soFaqs[] = ['question' => "When is the next $artistName {$soNoun[1]}?", 'answer' => "The next $artistName date listed is $soNextWhen at " . ($soNext['venue']['text']['name'] ?? 'the venue') . ' in ' . trim(($soNext['city']['text']['name'] ?? '') . ', ' . ($soNext['stateProvince']['text']['abbr'] ?? ''), ', ') . '. Dates can change, so check the event page before you travel.'];
}
$soFaqs[] = ['question' => "How do I buy $artistName tickets?", 'answer' => "Pick a $artistName date above, choose how many tickets you need, compare sections and prices on the seat map and check out securely. Your tickets are delivered before the event."];
$soFaqs[] = ['question' => "Are $artistName tickets on Seat Outlet legit?", 'answer' => "Yes. Every order is covered by our 100% guarantee: valid tickets, delivery before the event, and a refund if the event is canceled and not rescheduled. Seat Outlet is a resale marketplace, so prices may be above or below face value."];
$faqs = array_merge($soFaqs, array_map(function ($q) use ($artistName) {
    return ['question' => str_replace('[artist_name]', $artistName, (string) $q['question']), 'answer' => str_replace('[artist_name]', $artistName, (string) $q['answer'])];
}, is_array($faqs) ? $faqs : []));
$soEntityFacts = soEntityFacts($artistName, 'performer');   // Wikidata / Wikipedia / official site, [] until the background lookup has run
$pageJsonLdNodes = buildPerformerPageJsonLd($artistName, (int) $id, $events, $breadcrumbs, $pageOgImage ?? '', $soCatPath, $soEntityFacts['sameAs'] ?? []);
$pageMainEntity  = HOME_URL . '/artist/' . createSlug($artistName, (int) $id) . '#performer';
if ($faqNode = buildFaqPageSchema($faqs)) { $pageJsonLdNodes[] = $faqNode; }

$pagePreloadImage = $hasRealImage ? $performer_image : '/images/event-so.webp';
include 'header.php';
?>

<section class="section-featured-header text-sm-center text-md-start">
	<div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
		style="background-image: url('<?php echo htmlspecialchars($hasRealImage ? $performer_image : '/images/event-so.webp', ENT_QUOTES, 'UTF-8'); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
		<div class="container mx-xl-5 mx-lg-5 mx-md-3">
			<div class="row">
				<div class="col-12 mb-4">
					<div class="section-content">
						<nav class="breadcrumb justify-content-sm-center justify-content-md-start">
							<?php foreach ($breadcrumbs as $index => $item) { ?>
								<?php if ($index > 0) { ?>
									<?php echo $sep; ?>
								<?php } ?>
								<a href="<?php echo htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">
									<?php echo htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
								</a>
							<?php } ?>
							<?php echo $sep; ?>
							<span class="current">
								<?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?>
							</span>
						</nav>
					</div>
				</div>
				<div class="col-12">
					<div class="row align-items-center text-center text-md-start so-hero-row">
						<div class="col-md-3">
							<div class="img-artist">
								<?php if ($hasRealImage) { ?>
									<img src="<?php echo htmlspecialchars($performer_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded artist-img" fetchpriority="high" width="300" height="300" />
									<?php renderImageCredit($performerImg, 'img-credit d-none d-md-block'); ?>
								<?php } else { echo soTileHtml($artistName, 'so-tile so-tile--hero'); } ?>
							</div>
						</div>
						<div class="col-md-9 text-white">
							<div class="artist-heading text-center text-md-start text-lg-start text-xl-start text-xxl-start">
								<?php 
									$lastBreadcrumb = end($breadcrumbs);
									$categoryLabel  = $lastBreadcrumb['label'] ?? '';
								?>
								<div class="artist-category">
									<a href="<?php echo htmlspecialchars((string) ($lastBreadcrumb['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>">
										<?php echo htmlspecialchars((string) $categoryLabel, ENT_QUOTES, 'UTF-8'); ?>
									</a>
								</div>
								<h1 class="artist-title">
								<?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Tickets
								</h1>
								<?php if ($total_count > 0) { ?>
								<p class="artist-snapshot mb-0">
									<?php echo (int) $total_count; ?> upcoming <?php echo $total_count === 1 ? 'event' : 'events'; ?>
									<?php if ($priceSnapshot['from'] !== '') { ?>
										<span class="dot">·</span> Tickets from <strong><?php echo htmlspecialchars($priceSnapshot['from'], ENT_QUOTES, 'UTF-8'); ?></strong>
									<?php } ?>
									<?php if ($nextEvent) { ?>
										<span class="dot">·</span> Next: <?php echo htmlspecialchars(date('M j', strtotime($nextEvent['date']['date'] ?? 'now')) . ' in ' . ($nextEvent['city']['text']['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
									<?php } ?>
								</p>
								<?php } ?>
								<p class="so-resale-note mb-0">Resale marketplace. Prices are set by sellers and may be above or below face value.</p>
								<?php if ($hasRealImage) { renderImageCredit($performerImg, 'img-credit so-hero-credit d-md-none'); } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="so-tabs sticky-tabs">
	<div class="artist-tabs tabs-wrapper">
		<ul class="nav nav-tabs artist-tabs-nav" id="artistTabs">
			<li class="nav-item">
				<button class="nav-link active" type="button" data-target="default" onclick="scrollToElement('default')">
					<?php echo htmlspecialchars((string) ($breadcrumbs[1]['label'] ?? 'Events'), ENT_QUOTES, 'UTF-8'); ?>
				</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="promocode" onclick="scrollToElement('promocode')">Promocode</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="about" onclick="scrollToElement('about')">About</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button"  data-target="faqs" onclick="scrollToElement('faqs')">FAQs</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button"  data-target="fans" onclick="scrollToElement('fans')">
				Fans Also Love
				</button>
			</li>
		</ul>
		<span class="active-underline"></span>
	</div>
</section>

<section>
	<div class="container">
		<div class="tab-section section-performer-content" id="default">
			<div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
				<div class="col-sm-12 col-md-8 left-bar">
					<div class="mb-3 mb-md-4 mb-lg-4">
						<div class="d-flex justify-content-between align-items-center results-header">
							<div class="results-title">
								<span class="active-indicator"></span>
								<h2>
									<?php echo htmlspecialchars(strtoupper($artistName), ENT_QUOTES, 'UTF-8'); ?> TICKETS FOR SALE <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $total_count; ?>
										<?php echo $total_count === 1 ? 'RESULT' : 'RESULTS'; ?>
									</span>
								</h2>
							</div>
						</div>
					</div>
					<div class="list-category-bg pb-3">
						<div class="filter-bar">
							<input type="hidden" id="latEvent" value="">
							<input type="hidden" id="lngEvent" value="">
							<input type="hidden" id="sdateEvent" value="">
							<input type="hidden" id="edateEvent" value="">
							<input type="hidden" id="pidEvent" value="<?php echo $id; ?>" data-name="<?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?>">
							<div class="so-chips so-chips--compact" role="group" aria-label="Filter and sort dates">
								<div class="so-chip-wrap">
									<button type="button" class="so-chip" data-so-loc-toggle aria-haspopup="dialog" aria-expanded="false">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
										<span data-so-loc-label>Location</span>
										<svg class="so-chip__caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
									</button>
									<div class="so-pop so-pop--loc" data-so-loc-pop role="dialog" aria-label="Choose a location" hidden>
										<label class="so-pop__label" for="locationInput">City or ZIP code</label>
										<div class="filter-input so-pop__field">
											<input type="text" class="form-control" placeholder="For example Austin, TX" id="locationInput" autocomplete="off">
											<button type="button" id="locationInputReset" class="d-none so-close-octagon" aria-label="Clear location"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 4l8 8M12 4l-8 8" stroke-linecap="round"/></svg></button>
											<div id="locationResults" class="tn-dropdown-menu dropdown"></div>
										</div>
									</div>
								</div>
								<details class="so-dd so-dd--near" data-so-dd="when">
									<summary class="so-chip" aria-label="Dates"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span data-so-dd-label data-default="Date">Date</span><svg class="so-chip__caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
									<div class="so-dd__menu so-pop">
										<button type="button" class="so-pop__row is-active" data-so-when="" data-label="Date">All dates</button>
										<?php foreach (LISTING_WHEN as $wk => $wl) { ?><button type="button" class="so-pop__row" data-so-when="<?php echo $wk; ?>"><?php echo htmlspecialchars($wl, ENT_QUOTES, 'UTF-8'); ?></button><?php } ?>
									</div>
								</details>
								<details class="so-dd so-dd--near" data-so-dd="sort">
									<summary class="so-chip" aria-label="Sort"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M17 20V4M17 4l-3 3M17 4l3 3"/></svg><span data-so-dd-label data-default="Sort">Sort</span><svg class="so-chip__caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary>
									<div class="so-dd__menu so-pop">
										<button type="button" class="so-pop__row is-active" data-so-sort="" data-label="Sort">Best match</button>
										<button type="button" class="so-pop__row" data-so-sort="soonest">Soonest</button>
										<button type="button" class="so-pop__row" data-so-sort="price">Lowest price</button>
									</div>
								</details>
							</div>
							<h3 id="locationHeading" class="mt-4 fs-5"></h3>
							<div id="location-no-results" class="text-center no-location"></div>
						</div>
						<?php if (!empty($events)) { ?>
							<?php if ($weekendGroups) { ?>
								<div class="so-weekends" data-so-weekends role="group" aria-label="Choose a weekend">
									<?php foreach ($weekendGroups as $wi => $wg) { ?>
										<button type="button" class="so-weekend<?php echo $wi === 0 ? ' is-active' : ''; ?>" data-wk="<?php echo $wi + 1; ?>" aria-pressed="<?php echo $wi === 0 ? 'true' : 'false'; ?>">
											<span class="so-weekend__name"><?php echo htmlspecialchars($wg['label'], ENT_QUOTES, 'UTF-8'); ?></span>
											<span class="so-weekend__range"><?php echo htmlspecialchars($wg['range'], ENT_QUOTES, 'UTF-8'); ?></span>
										</button>
									<?php } ?>
								</div>
							<?php } ?>
							<div id="eventsSection" class="section-artist-content event-row-all">
								<?php foreach ($events as $event) { 
									$eventDateRaw = $event['date']['date'];
									$timestamp    = strtotime($eventDateRaw);
									$evtPerformers = $event['performers'] ?? [];
									$names = array_map(function ($performer) {
										return $performer['name'] ?? null;
									}, $evtPerformers);
									$performerSlugs = array_map(function ($performer) {
										$pn = (string) ($performer['name'] ?? '');
										$pid = $performer['id'] ?? '';
										return $pn !== '' && $pid ? soEntitySlug($pn, $pid) : null;   // the one slug rule
									}, $evtPerformers);
									$dataPerformers = implode('|', array_filter($names));	
									$dataPerformerSlugs  = implode('|', array_filter($performerSlugs));
									$slug = soEntitySlug($event['text']['name'] ?? '', $event['id'] ?? 0);
									$city = soPlaceLabel($event);
									$citySlug = soEntitySlug($city, $event['city']['id'] ?? 0);
									$venueSlug = soEntitySlug($event['venue']['text']['name'] ?? '', $event['venue']['id'] ?? 0);
								?>
									<div class="d-flex align-items-center justify-content-between performer-event-item"<?php echo isset($weekendOf[(int) ($event['id'] ?? 0)]) ? ' data-wk="' . (int) $weekendOf[(int) $event['id']] . '"' : ''; ?>>
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
													<?php echo htmlspecialchars((string) ($event['date']['text']['time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
												</span>
												<i
													class="bi bi-info-circle text-muted icon-i"
													data-bs-toggle="offcanvas"
													data-bs-target="#offcanvasRight"
													aria-controls="offcanvasRight"
													data-id="<?php echo (int) ($event['id'] ?? 0); ?>"
													data-date="<?php echo date('D, M d', $timestamp) ?>"
													data-venue="<?php echo htmlspecialchars((string) ($event['venue']['text']['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
													data-venueSlug="<?php echo htmlspecialchars($venueSlug, ENT_QUOTES, 'UTF-8'); ?>"
													data-location="<?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>"
													data-title="<?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-performers="<?php echo htmlspecialchars($dataPerformers, ENT_QUOTES, 'UTF-8'); ?>"
													data-performer-slugs="<?php echo htmlspecialchars($dataPerformerSlugs, ENT_QUOTES, 'UTF-8'); ?>"
												></i>
											</div>
											<div class="ev-venue"><a href="/venue/<?php echo htmlspecialchars($venueSlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
											<div class="ev-place"><a href="/city/<?php echo htmlspecialchars($citySlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?></a></div>
											<div class="ev-name">
												<a href="/event/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?><span class="visually-hidden"> tickets, <?php echo htmlspecialchars(date('M j', $timestamp) . ' at ' . ($event['venue']['text']['name'] ?? '') . ', ' . $city, ENT_QUOTES, 'UTF-8'); ?></span></a>
											</div>
										</div>
										<div class="ms-3">
											<?php if (!empty($cheapestEventId) && (int) ($event['id'] ?? 0) === $cheapestEventId) { ?><div class="so-cheapest-row"><span class="event-cheapest-badge">Cheapest date</span></div><?php } ?>
											<?php renderEventPriceTag($event); $evHasPrice = eventFromPrice($event) !== ''; ?>
											<a href="/event/<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" class="btn <?php echo $evHasPrice ? 'btn-primary' : 'btn-outline-primary'; ?> d-flex align-items-center gap-2" aria-label="<?php echo $evHasPrice ? 'Buy tickets for' : 'View'; ?> <?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
												<span><?php echo $evHasPrice ? 'Buy Tickets' : 'View Event'; ?></span>
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
											Loaded <strong id="loadedCount"><?php echo $perPage; ?></strong> out of <strong id="totalCount"><?php echo $total_count; ?></strong> events
										</div>
										<div class="progress progress-thin">
											<div class="progress-bar" id="progressBar" style="width: <?php echo $percent; ?>%;"></div>
										</div>
									</div>
									<button
										class="btn more-events-btn d-inline-flex align-items-center gap-2"
										id="loadMoreBtn"
										data-total="<?php echo (int) $total_count; ?>"
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
							<div class="so-empty" role="status">
								<h3 class="so-empty__title">No <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> dates are on sale right now</h3>
								<p>Tour dates are added as they are announced. Leave your email and we will tell you when <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> announces dates.</p>
								<?php echo soLeadForm(['source' => 'artist-empty', 'class' => 'so-nl--compact', 'title' => 'Get alerts when ' . $artistName . ' announces dates', 'text' => 'One email when new dates go on sale. No spam.', 'button' => 'Alert me', 'interest_type' => 'performer', 'interest_id' => (int) $id, 'interest_name' => $artistName, 'names' => false]); ?>
								<?php soRenderEntityAlternatives(['parent' => ['url' => (string) ($breadcrumbs[1]['url'] ?? '/buy-tickets-online'), 'text' => 'More ' . strtolower((string) ($breadcrumbs[1]['label'] ?? 'event')) . ' tickets']], []); ?>
							</div>
						<?php } ?>
					</div>	

					<div class="tab-section content-section-detail mb-0" id="promocode">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Ticket Promo Codes</h2>
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
						<?php if ($nextEvent) {
							$nextSlug = createSlug($nextEvent['text']['name'] ?? '', $nextEvent['id'] ?? 0);
							$nextTs   = strtotime($nextEvent['date']['date'] ?? 'now');
							$nextDeal = eventDealInfo($nextEvent);
						?>
						<?php
							$nvCity  = trim(($nextEvent['city']['text']['name'] ?? '') . ', ' . ($nextEvent['stateProvince']['text']['abbr'] ?? ''), ', ');
							$nvVenue = (string) ($nextEvent['venue']['text']['name'] ?? '');
							$nvCityUrl  = !empty($nextEvent['city']['id'])  && $nvCity  !== '' ? '/city/'  . createSlug($nvCity,  $nextEvent['city']['id'])  : '';
							$nvVenueUrl = !empty($nextEvent['venue']['id']) && $nvVenue !== '' ? '/venue/' . createSlug($nvVenue, $nextEvent['venue']['id']) : '';
							$nvLink = function ($url, $text) { $t = htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); return $url !== '' ? '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . $t . '</a>' : $t; };
						?>
						<div class="next-event-card mb-3">
							<div class="next-event-label">Next event</div>
							<div class="next-event-when"><?php echo date('D, M j, Y', $nextTs); ?><span><?php echo htmlspecialchars($nextEvent['date']['text']['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span></div>
							<a class="next-event-name" href="/event/<?php echo htmlspecialchars($nextSlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($nextEvent['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
							<div class="next-event-where"><?php echo $nvLink($nvVenueUrl, $nvVenue); ?><?php if ($nvVenue !== '' && $nvCity !== '') { ?><i aria-hidden="true">·</i><?php } ?><?php echo $nvLink($nvCityUrl, $nvCity); ?></div>
							<?php if ($nextDeal['from'] === '') { ?>
								<div class="next-event-price next-event-price--none">No tickets listed yet. Check the event page for updates.</div>
							<?php } else { ?>
								<div class="next-event-price"><span class="from">From</span><strong><?php echo htmlspecialchars($nextDeal['from'], ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($nextDeal['tickets'] > 0) { ?><span class="listed"><?php echo (int) $nextDeal['tickets']; ?> tickets listed</span><?php } ?></div>
							<?php } ?>
							<a href="/event/<?php echo htmlspecialchars($nextSlug, ENT_QUOTES, 'UTF-8'); ?>" class="next-event-cta"><?php echo $nextDeal['from'] !== '' ? 'Buy Tickets' : 'View Event'; ?></a>
						</div>
						<?php } ?>
						<div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
							<div class="guarantee">
								<strong>Shop Tickets Worry Free</strong><br>
								<span>With Our 100% Guarantee</span>
							</div>
							<div class="guarantee-icon">
								<i class="bi bi-shield-check"></i>
							</div>
						</div>
						<?php if ($priceSnapshot['priced'] > 1) { ?>
						<div class="price-snapshot-card mt-3">
							<div class="next-event-label">Price guide</div>
							<p class="mb-1">Cheapest listed <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> tickets on the dates shown range from <strong><?php echo htmlspecialchars($priceSnapshot['from'], ENT_QUOTES, 'UTF-8'); ?></strong> to <strong><?php echo htmlspecialchars($priceSnapshot['to'], ENT_QUOTES, 'UTF-8'); ?></strong> per ticket.</p>
							<p class="mb-0 small text-muted">Prices are the lowest listing per event and change as sellers update inventory.</p>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<?php if (!empty($events)) { ?>
		<div class="tab-section content-section-detail so-tourtable" id="dates">
			<h2 class="so-heading fw-bold fs-4 mb-3 text-black"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($soNoun[2], ENT_QUOTES, 'UTF-8'); ?> and ticket prices</h2>
			<p class="so-tourtable__lead">Every upcoming <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars(strtolower($soNoun[1]), ENT_QUOTES, 'UTF-8'); ?> on Seat Outlet, with the lowest price listed today. Pick a date to compare seats.</p>
			<div class="so-table-wrap">
				<table>
					<thead><tr><th>Date</th><th>City</th><th>Venue</th><th>From</th><th><span class="visually-hidden">Tickets</span></th></tr></thead>
					<tbody>
					<?php
					$tourRows = array_slice($events, 0, 12);
					// Cheapest listed date per city (only labelled when the city has 2+ priced dates in the table).
					$cityMin = []; $cityPriced = [];
					foreach ($tourRows as $te) {
						$tv = $te['pricingInfo']['lowPrice']['value'] ?? null; $tc = (int) ($te['city']['id'] ?? 0);
						if ($tv === null || (float) $tv <= 0 || $tc <= 0) continue;
						$cityPriced[$tc] = ($cityPriced[$tc] ?? 0) + 1;
						if (!isset($cityMin[$tc]) || (float) $tv < $cityMin[$tc]) { $cityMin[$tc] = (float) $tv; }
					}
					$artistSlugForLinks = soEntitySlug($artistName, $id);
					foreach ($tourRows as $te) {
						$tts = strtotime($te['date']['date'] ?? 'now');
						$tcity = soPlaceLabel($te);
						$tcid = (int) ($te['city']['id'] ?? 0);
						$tven = (string) ($te['venue']['text']['name'] ?? '');
						$tlow = $te['pricingInfo']['lowPrice']['text']['formatted'] ?? '';
						$tval = $te['pricingInfo']['lowPrice']['value'] ?? null;
						$lowestInCity = $tval !== null && (float) $tval > 0 && $tcid > 0 && ($cityPriced[$tcid] ?? 0) > 1 && (float) $tval === ($cityMin[$tcid] ?? -1.0);
					?>
						<tr>
							<td><?php echo htmlspecialchars(date('D, M j, Y', $tts), ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php if ($tcid > 0 && $tcity !== '') { ?><a href="/artist-city/<?php echo htmlspecialchars($artistSlugForLinks . '/' . soEntitySlug($tcity, $tcid), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tcity, ENT_QUOTES, 'UTF-8'); ?></a><?php } else { echo htmlspecialchars($tcity, ENT_QUOTES, 'UTF-8'); } ?></td>
							<td><?php echo htmlspecialchars($tven, ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo $tlow !== '' ? htmlspecialchars($tlow, ENT_QUOTES, 'UTF-8') : 'Not listed'; ?><?php if ($lowestInCity) { ?> <span class="so-tag-low">Lowest in city</span><?php } ?></td>
							<td><a href="/event/<?php echo htmlspecialchars(soEntitySlug($te['text']['name'] ?? '', $te['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> tickets</a></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
			<?php echo soLeadForm(['source' => 'artist-follow', 'class' => 'so-nl--compact', 'title' => 'Get alerts when ' . $artistName . ' announces dates', 'text' => 'One email when new dates go on sale. No spam.', 'button' => 'Alert me', 'interest_type' => 'performer', 'interest_id' => (int) $id, 'interest_name' => $artistName, 'names' => false]); ?>
			<p class="so-tourtable__more">Looking for something else? Browse <a href="<?php echo htmlspecialchars($breadcrumbs[1]['url'] ?? '/buy-tickets-online', ENT_QUOTES, 'UTF-8'); ?>">more <?php echo htmlspecialchars(strtolower($breadcrumbs[1]['label'] ?? 'event'), ENT_QUOTES, 'UTF-8'); ?> tickets</a>, see <a href="/city-events">events by city</a>, or read how our <a href="/worry-free-guarantee">100% guarantee</a> and <a href="/ticket-buyer-protection">buyer protection</a> work.</p>
		</div>
		<?php } ?>
		<div class="tab-section content-section-detail" id="about">
			<div class="row">
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="me-0 me-md-3 me-lg-3 me-xl-3 me-xxl-3">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">About <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?></h2>
						<?php renderBioBlock($performer_bio); ?>
						<?php if (!empty($performer_bio)) { ?>
						<p class="small text-muted mb-0 bio-source">Biography adapted from <a href="<?php echo htmlspecialchars($soEntityFacts['wikipedia'] ?? ('https://en.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $artistName))), ENT_QUOTES, 'UTF-8'); ?>" rel="nofollow noopener" target="_blank">Wikipedia</a>, <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="nofollow noopener" target="_blank">CC BY-SA</a>.</p>
						<?php } ?>
						<?php echo soFactsSourcesHtml($soEntityFacts, $artistName); ?>
					</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0">
						<?php if ($hasRealImage) { ?>
							<img src="<?php echo htmlspecialchars($performer_image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?>" class="img-about img-fluid rounded" loading="lazy" width="600" height="450" />
							<?php renderImageCredit($performerImg, 'img-credit'); ?>
						<?php } else { echo soTileHtml($artistName, 'so-tile so-tile--about'); } ?>
					</div>
				</div>				
			</div>
		</div>
		<?php if (!empty($faqs)) { ?>
			<?php $faqVisible = 5; // FAQs shown before "Show more" ?>
			<div class="tab-section content-section-detail" id="faqs">
				<h2 class="so-heading fw-bold fs-4 mb-4 text-black">FAQs about <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Events</h2>
				<div class="accordion" id="faqAccordion">
					<?php foreach ($faqs as $index => $faq) {
						$collapseId = 'collapse' . $index;
						$headingId  = 'heading' . $index;
						$artistHtml = htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8');   // the name is data, the FAQ text around it is our own markup
						$question = str_replace('[artist_name]', $artistHtml, htmlspecialchars((string) $faq['question'], ENT_QUOTES, 'UTF-8', false));
						$answer   = str_replace('[artist_name]', $artistHtml, (string) $faq['answer']);
						$isFirst = ($index === 0);
						$isExtra = ($index >= $faqVisible);
					?>
						<div class="accordion-item<?php echo $isExtra ? ' faq-extra d-none' : ''; ?>">
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
				<?php if (count($faqs) > $faqVisible) { ?>
					<div class="text-center mt-4">
						<button type="button" class="btn more-events-btn d-inline-flex align-items-center gap-2" id="faqToggleBtn" aria-expanded="false" aria-controls="faqAccordion">
							<span class="btn-text">Show more</span>
							<i class="bi bi-chevron-down"></i>
						</button>
					</div>
					<script>
					(function () {
						var btn = document.getElementById('faqToggleBtn');
						var section = document.getElementById('faqs');
						btn.addEventListener('click', function () {
							var expand = btn.getAttribute('aria-expanded') !== 'true';
							section.querySelectorAll('.faq-extra').forEach(function (item) {
								item.classList.toggle('d-none', !expand);
							});
							btn.setAttribute('aria-expanded', expand ? 'true' : 'false');
							btn.querySelector('.btn-text').textContent = expand ? 'Show less' : 'Show more';
							btn.querySelector('.bi').style.transform = expand ? 'rotate(180deg)' : '';
							// After collapsing, keep the visitor at the FAQs instead of
							// wherever the longer list had scrolled them to.
							if (!expand && section.getBoundingClientRect().top < 0) {
								section.scrollIntoView({ behavior: 'smooth', block: 'start' });
							}
						});
					})();
					</script>
				<?php } ?>
			</div>
		<?php } ?>
		<?php renderPerformerWhere($artistName, (int) $id, $events ?? [], (int) $total_count); ?>
		<?php if (!empty($relatedPerformers)) { $i = 0; ?>
			<div class="tab-section content-section-detail" id="fans">
				<h2 class="so-heading fw-bold fs-4 mb-3 text-black"><?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Fans Also Love</h2>
				<?php renderRelatedPerformersGrid($relatedPerformers, 8); ?>
			</div>
		<?php } ?>
	</div>
</section>

	
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.soLocal) {
    window.soLocal.addPerformer({
      id: <?php echo json_encode((string) $id); ?>,
      name: <?php echo json_encode($artistName, JSON_HEX_TAG | JSON_HEX_AMP); ?>,
      slug: <?php echo json_encode(soEntitySlug($artistName, $id)); ?>,
      img: <?php echo json_encode($hasRealImage ? $performer_image : ''); ?>
    });
  }
});
</script>
<?php include 'footer.php'; ?>
