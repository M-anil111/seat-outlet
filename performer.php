<?php
require_once 'functions.php';

// Sanitize and normalize pagination.
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;

// Extract performer ID from slug; expect a trailing numeric ID.
$slug  = $_GET['slug'] ?? '';
$parts = explode('-', (string) $slug);
$id    = (int) end($parts);

if ($id <= 0) {
	renderNotFoundPage('Performer');
}

// Performer and its first page of events are independent: fetch together.
tnRequestMulti([['/catalog/v2/performers/' . $id, []], performerPageEventsSpec($id, $perPage)]);
$performer = getTnPerformerById($id);

if (tnEntityMissing($performer) || empty($performer['defaultCategory'])) {
	renderNotFoundPage('Performer', $performer);
}

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

$sep = '<span class="separator"><strong> / </strong></span>';
$breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);
$relatedPerformersResponse = getRelatedPerformers($performer['defaultCategory']['path'], $id);
$relatedPerformers = $relatedPerformersResponse;
$year = date('Y');
$artistName = $performer['text']['name'];
$performer_bio = getArtistBio($artistName, $id);
$faqs = getFaqs('performer');
$imageType = imageEntityTypeForPerformer($performer['defaultCategory'] ?? []);
// Single-entity page: resolve synchronously (bounded by inc/images.php's
// timeouts) so the first visitor gets a real image; the result is stored.
$performerImg    = getEntityImage($imageType, $artistName, ['category' => $performer['defaultCategory'] ?? [], 'resolve' => true]);
$performer_image = $performerImg['url'];
$pageOgImage     = $performerImg['status'] !== 'fallback' ? $performer_image : null;

// --- SEO: computed before including header.php, same convention as the
// artist-city/concerts-city/etc. pages - see functions.php. This page was
// previously rendering with no <title> and no canonical tag at all. ---
$pageMetaTitle       = "$artistName Tickets | Seat Outlet";
$pageMetaDescription = "Buy verified $artistName tickets. Compare prices across sellers and find upcoming $artistName shows near you on Seat Outlet.";
$pageCanonicalUrl    = HOME_URL . '/artist/' . strtolower($performer['uriComponent'] ?? createSlug($artistName, $id));
if ($priceSnapshot['from'] !== '' && $total_count > 0) {
    $pageMetaDescription = "$artistName tickets from {$priceSnapshot['from']}. $total_count upcoming " . ($total_count === 1 ? 'event' : 'events') . ". Compare prices across sellers and find $artistName shows near you on Seat Outlet.";
}
// BreadcrumbList + an Event node per listed date (the page emitted only the
// site-wide Organization/WebSite graph before).
$pageJsonLdNodes = buildPerformerPageJsonLd($artistName, (int) $id, $events, $breadcrumbs, $pageOgImage ?? '');

$pagePreloadImage = ($performerImg['status'] ?? '') !== 'fallback' ? $performer_image : '/images/event-so.webp';
include 'header.php';
?>

<section class="section-featured-header text-sm-center text-md-start">
	<div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
		style="background-image: url('<?php echo htmlspecialchars(($performerImg['status'] ?? '') !== 'fallback' ? $performer_image : '/images/event-so.webp', ENT_QUOTES, 'UTF-8'); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
		<div class="container mx-xl-5 mx-lg-5 mx-md-3">
			<div class="row">
				<div class="col-12 mb-4">
					<div class="section-content">
						<nav class="breadcrumb justify-content-sm-center justify-content-md-start">
							<?php foreach ($breadcrumbs as $index => $item) { ?>
								<?php if ($index > 0) { ?>
									<?php echo $sep; ?>
								<?php } ?>
								<?php if ($index < count($breadcrumbs)) { ?>
									<a href="<?php echo $item['url']; ?>">
										<?php echo $item['label']; ?>
									</a>
								<?php } ?>
							<?php } ?>
							<?php echo $sep; ?>
							<span class="current">
								<?php echo $artistName; ?>
							</span>
						</nav>
					</div>
				</div>
				<div class="col-12">
					<div class="row align-items-center text-center text-md-start">
						<div class="col-md-3">
							<div class="img-artist">
								<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" class="img-fluid rounded artist-img" fetchpriority="high" width="300" height="300" />
								<?php renderImageCredit($performerImg, 'img-credit'); ?>
							</div>
						</div>
						<div class="col-md-9 text-white">
							<div class="artist-heading text-center text-md-start text-lg-start text-xl-start text-xxl-start">
								<?php 
									$lastBreadcrumb = end($breadcrumbs);
									$categoryLabel  = $lastBreadcrumb['label'] ?? '';
								?>
								<div class="artist-category">
									<a href="<?php echo $lastBreadcrumb['url']; ?>">
										<?php echo $categoryLabel; ?>
									</a>
								</div>
								<h1 class="artist-title">
								<?php echo $artistName; ?> Tickets
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
					<?php echo $breadcrumbs[1]['label']; ?>
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
									<?php echo strtoupper($artistName); ?> <?php echo strtoupper($breadcrumbs[1]['label']); ?> IN US <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $total_count; ?>
										<?php echo $total_count > 1 ? 'RESULTS' : 'RESULT'; ?>
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
							<input type="hidden" id="pidEvent" value="<?php echo $id; ?>">
							<div class="row">
								<div class="col-md-6">
									<label class="filter-label">Location</label>
									<div class="filter-input">
										<i class="bi bi-geo-alt"></i>
										<input type="text" class="form-control" placeholder="City or Zip Code" id="locationInput" autocomplete="off">
										<button type="button" id="locationInputReset" class="d-none so-close-octagon">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-octagon" viewBox="0 0 16 16">
											<path d="M4.54.146A.5.5 0 0 1 4.893 0h6.214a.5.5 0 0 1 .353.146l4.394 4.394a.5.5 0 0 1 .146.353v6.214a.5.5 0 0 1-.146.353l-4.394 4.394a.5.5 0 0 1-.353.146H4.893a.5.5 0 0 1-.353-.146L.146 11.46A.5.5 0 0 1 0 11.107V4.893a.5.5 0 0 1 .146-.353zM5.1 1 1 5.1v5.8L5.1 15h5.8l4.1-4.1V5.1L10.9 1z"/>
											<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
											</svg>
										</button>
										<div id="locationResults" class="tn-dropdown-menu dropdown"></div>
									</div>
								</div>
								<div class="col-md-6">
									<label class="filter-label">Dates</label>
									<div class="filter-input">
										<i class="bi bi-calendar3"></i>
										<input type="text" id="performerDatePicker" placeholder="Select Date Range" class="form-control" autocomplete="off" readonly value="<?php echo !empty($dateTitle) ? $dateTitle : ''; ?>">  
										<div class="filter-arrow"><i id="dateArrow" class="bi bi-chevron-down"></i></div>
									</div>
								</div>
							</div>
							<h3 id="locationHeading" class="mt-4 fs-5"></h3>
							<div id="location-no-results" class="text-center no-location"></div>
						</div>
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
													data-date="<?php echo date('D, M d', $timestamp) ?>"
													data-venue="<?php echo $event['venue']['text']['name']; ?>"
													data-venueSlug="<?php echo $venueSlug; ?>"
													data-location="<?php echo $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']; ?>"
													data-title="<?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-performers="<?php echo htmlspecialchars($dataPerformers); ?>"
													data-performer-slugs="<?php echo htmlspecialchars($dataPerformerSlugs); ?>"
												></i>
											</div>
											<div class="ev-venue"><a href="/venue/<?php echo $venueSlug; ?>"><?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a></div>
											<div class="ev-place"><a href="/city/<?php echo $citySlug; ?>"><?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?></a></div>
											<div class="ev-name">
												<a href="/event/<?php echo $slug; ?>"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
											</div>
										</div>
										<div class="ms-3">
											<?php if (!empty($cheapestEventId) && (int) ($event['id'] ?? 0) === $cheapestEventId) { ?><span class="event-cheapest-badge">Cheapest date</span><?php } ?>
											<?php renderEventPriceTag($event); ?>
											<a href="/event/<?php echo $slug; ?>" class="btn btn-primary d-flex align-items-center gap-2" aria-label="Find tickets for <?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
												<span>Buy Tickets</span>
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
							<h3 style="padding: 20px; font-size: 1.25rem; font-weight: 400;">
								No <?php echo strtoupper($breadcrumbs[1]['label']); ?> found!
							</h3>
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
							<?php if ($nextDeal['from'] !== '') { ?>
								<div class="next-event-price"><span class="from">From</span><strong><?php echo htmlspecialchars($nextDeal['from'], ENT_QUOTES, 'UTF-8'); ?></strong><?php if ($nextDeal['tickets'] > 0) { ?><span class="listed"><?php echo (int) $nextDeal['tickets']; ?> tickets listed</span><?php } ?></div>
							<?php } ?>
							<a href="/event/<?php echo htmlspecialchars($nextSlug, ENT_QUOTES, 'UTF-8'); ?>" class="next-event-cta">Buy Tickets</a>
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
		<div class="tab-section content-section-detail" id="about">
			<div class="row">
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="me-0 me-md-3 me-lg-3 me-xl-3 me-xxl-3">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">About <?php echo $artistName; ?></h2>
						<p><?php echo $performer_bio; ?></p>
						<?php if (!empty($performer_bio)) { ?>
						<p class="small text-muted mb-0 bio-source">Biography adapted from <a href="https://en.wikipedia.org/wiki/<?php echo rawurlencode(str_replace(' ', '_', $artistName)); ?>" rel="nofollow noopener" target="_blank">Wikipedia</a>, <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="nofollow noopener" target="_blank">CC BY-SA</a>.</p>
						<?php } ?>
					</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0">
						<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" class="img-about img-fluid rounded" loading="lazy" />
					</div>
				</div>				
			</div>
		</div>
		<?php if (!empty($faqs)) { ?>
			<?php $faqVisible = 5; // FAQs shown before "Show more" ?>
			<div class="tab-section content-section-detail" id="faqs">
				<h2 class="so-heading fw-bold fs-4 mb-4 text-black">FAQs about <?php echo $artistName; ?> Events</h2>
				<div class="accordion" id="faqAccordion">
					<?php foreach ($faqs as $index => $faq) {
						$collapseId = 'collapse' . $index;
						$headingId  = 'heading' . $index;
						$question = str_replace('[artist_name]', $artistName, $faq['question']);
						$answer   = str_replace('[artist_name]', $artistName, $faq['answer']);
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
      name: <?php echo json_encode($artistName); ?>,
      slug: <?php echo json_encode(strtolower(createSlug($artistName, $id))); ?>,
      img: <?php echo json_encode(($performerImg['status'] ?? '') !== 'fallback' ? $performer_image : ''); ?>
    });
  }
});
</script>
<?php include 'footer.php'; ?>
