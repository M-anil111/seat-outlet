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
	renderNotFoundPage('Venue');
}

$venue = getTnVenueById($id);

if (tnEntityMissing($venue)) {
	renderNotFoundPage('Venue', $venue);
}

$today = date('Y-m-d');
[$when, $sort, $isFiltered] = listingRequestState('soonest');
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // canonical page stays the indexed one
$params = locationListingParams("venue/id eq $id", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);

// totalCount rides on the list response (includeTotalCount) - this used to
// be a second, separate API call per page view.
$total_count = (int) ($eventsResponse['totalCount'] ?? 0);
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;

$events = $eventsResponse['results'] ?? [];
$count  = $eventsResponse['count'] ?? count($events);

$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
$year = date('Y');

$venueName  = $venue['text']['name'] ?? '';
$venueCityLabel = trim(($venue['city']['text']['name'] ?? '') . ', ' . ($venue['stateProvince']['text']['abbr'] ?? ''), ', ');

// --- SEO: computed before including header.php, same convention as the
// artist-city/concerts-city/etc. pages - see functions.php. This page was
// previously rendering with no <title> and no canonical tag at all. ---
$pageMetaTitle       = "$venueName Tickets - $venueCityLabel | Seat Outlet";
$pageMetaDescription = "Buy tickets to upcoming events at $venueName in $venueCityLabel. Compare prices and book securely on Seat Outlet.";
$pageCanonicalUrl    = HOME_URL . '/venue/' . createSlug($venueName, $id);
$pageJsonLdNodes     = [buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL], ['label' => 'Events', 'url' => HOME_URL . '/tickets']], $venueName)];

include 'header.php';
?>

<section>
	<div class="container">
		<div class="tab-section section-performer-content" id="default">
			<div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
				<div class="col-sm-12 col-md-8 left-bar">
					<div class="mb-3 mb-md-4 mb-lg-4">
						<div class="d-flex justify-content-between align-items-center results-header">
							<div class="results-title">
								<span class="active-indicator"></span>
								<h1>
									EVENTS at <?php echo $venue['text']['name'] . ' - ' . $venue['city']['text']['name'] . ', ' . $venue['stateProvince']['text']['abbr']; ?> <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $total_count; ?>
										<?php echo $total_count > 1 ? 'RESULTS' : 'RESULT'; ?>
									</span>
								</h1>							
							</div>
						</div>
					</div>
					<?php renderListingFilters('/venue/' . createSlug($venueName, $id), $when, $sort, $total_count, 'soonest'); ?>
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
											<div class="fw-semibold location-venue-name">
												<a href="/city/<?php echo $citySlug; ?>"><?php echo $city; ?></a>
												·
												<a href="/venue/<?php echo $venueSlug; ?>"><?php echo $event['venue']['text']['name']; ?></a>
											</div>
											<div class="text-muted small">
												<a href="/event/<?php echo $slug; ?>"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
											</div>
										</div>
										<div class="ms-3">
											<?php renderEventPriceTag($event); ?>
											<a href="/event/<?php echo $slug; ?>" class="btn btn-primary d-flex align-items-center gap-2" aria-label="Find tickets for <?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
												<span class="d-none d-md-inline">
													Find Tickets
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
										data-page="2"
										data-params="<?php echo htmlspecialchars(json_encode($params), ENT_QUOTES, 'UTF-8'); ?>"
										data-perpage="<?php echo (int) $perPage; ?>">
										<span class="btn-text">More Events</span>
										<span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
										<i class="bi bi-chevron-down"></i>
									</button>
									<button class="btn more-events-btn d-inline-flex align-items-center gap-2 d-none" id="backToTopJs">
										<span class="btn-text">Back to Top</span>
										<span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
										<i class="bi bi-chevron-up"></i>
									</button>
								</div>
							<?php } ?>
						<?php } else { ?>
							<h3 style="padding: 20px; font-size: 1.25rem; font-weight: 400;">
								No Events found!
							</h3>
						<?php } ?>
					</div>	
	
					<?php renderLocationCategoryLinks('venue', $id, $venueName); ?>
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
	</div>
</section>

	
<?php include 'footer.php'; ?>
