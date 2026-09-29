<?php
require_once 'functions.php';

// Sanitize and normalize pagination.
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;

$slug = $_GET['slug'] ?? '';
$code = parseLocationSlug('country', $slug);

if ($code === null) {
	include 'header.php';
	echo '<div class="container"><p>Invalid country.</p></div>';
	include 'footer.php';
	exit;
}

$country = getLocationDisplayInfo('country', $code);

if (empty($country)) {
	include 'header.php';
	echo '<div class="container"><p>Country not found.</p></div>';
	include 'footer.php';
	exit;
}

$today = date('Y-m-d');
[$when, $sort, $isFiltered] = listingRequestState('popular');
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // canonical page stays the indexed one
$params = locationListingParams("country/alphaCode eq '" . tnEscapeFilterValue($code) . "'", $perPage, 1, $when, $sort);
$eventsResponse = tnRequest('/catalog/v2/events/', $params);

// totalCount rides on the list response (includeTotalCount) - this used to
// be a second, separate API call per page view.
$total_count = (int) ($eventsResponse['totalCount'] ?? 0);
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;

$events = $eventsResponse['results'] ?? [];
$count  = $eventsResponse['count'] ?? count($events);

$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
$year = date('Y');

$countryLabel = $country['label'];

// --- SEO: computed before including header.php, same convention as the
// artist-country/concert-country/etc. pages - see functions.php.
$pageMetaTitle       = "Events in $countryLabel Tickets | Seat Outlet";
$pageMetaDescription = "Find concert, sports, and event tickets in $countryLabel. Compare prices and book securely on Seat Outlet.";
$pageCanonicalUrl    = HOME_URL . '/country/' . createSlug($countryLabel, $code);

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
								<h2>
									EVENTS in <?php echo htmlspecialchars($countryLabel, ENT_QUOTES, 'UTF-8'); ?> <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $total_count; ?>
										<?php echo $total_count > 1 ? 'RESULTS' : 'RESULT'; ?>
									</span>
								</h2>
							</div>
						</div>
					</div>
					<?php renderListingFilters('/country/' . createSlug($countryLabel, $code), $when, $sort, $total_count, 'popular'); ?>
					<div class="list-category-bg pb-3">
						<?php if (!empty($events)) { ?>
							<div id="eventsSection" class="section-artist-content event-row-all">
								<?php foreach ($events as $event) {
									$eventDateRaw = $event['date']['date'] ?? '';
									$timestamp    = $eventDateRaw ? strtotime($eventDateRaw) : false;
									$evtPerformers = $event['performers'] ?? [];
									$names = array_map(function ($performer) {
										return $performer['name'] ?? null;
									}, $evtPerformers);
									$dataPerformers = implode('|', array_filter($names));
									$eventSlug = createSlug($event['text']['name'] ?? '', $event['id'] ?? 0);
									$evtCityLabel = trim(($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
									$evtCitySlug  = !empty($event['city']['id']) ? createSlug($evtCityLabel, $event['city']['id']) : null;
									$evtVenueSlug = !empty($event['venue']['id']) ? createSlug($event['venue']['text']['name'] ?? '', $event['venue']['id']) : null;
								?>
									<div class="d-flex align-items-center justify-content-between performer-event-item">
										<div class="date-box text-center me-3">
											<div class="month"><?php echo $timestamp ? htmlspecialchars(strtoupper(date('M', $timestamp)), ENT_QUOTES, 'UTF-8') : ''; ?></div>
											<div class="day"><?php echo $timestamp ? htmlspecialchars(date('d', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?></div>
											<?php if ($timestamp && date('Y', $timestamp) > $year) { ?>
												<div class="month"><?php echo htmlspecialchars(date('Y', $timestamp), ENT_QUOTES, 'UTF-8'); ?></div>
											<?php } ?>
										</div>
										<div class="flex-grow-1 w-50">
											<div class="d-flex align-items-center gap-2">
												<span class="fw-semibold day-weeks"><?php echo $timestamp ? htmlspecialchars(date('D', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?></span>
												<span class="dot">·</span>
												<span class="time-clock"><?php echo htmlspecialchars($event['date']['text']['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
												<i
													class="bi bi-info-circle text-muted icon-i"
													data-bs-toggle="offcanvas"
													data-bs-target="#offcanvasRight"
													aria-controls="offcanvasRight"
													data-id="<?php echo (int) ($event['id'] ?? 0); ?>"
													data-date="<?php echo $timestamp ? htmlspecialchars(date('D, M d', $timestamp), ENT_QUOTES, 'UTF-8') : ''; ?>"
													data-venue="<?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-location="<?php echo htmlspecialchars($evtCityLabel, ENT_QUOTES, 'UTF-8'); ?>"
													data-title="<?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-performers="<?php echo htmlspecialchars($dataPerformers, ENT_QUOTES, 'UTF-8'); ?>"
												></i>
											</div>
											<div class="fw-semibold location-venue-name">
												<a href="<?php echo $evtCitySlug ? '/city/' . htmlspecialchars($evtCitySlug, ENT_QUOTES, 'UTF-8') : '#'; ?>"><?php echo htmlspecialchars($evtCityLabel, ENT_QUOTES, 'UTF-8'); ?></a>
												·
												<a href="<?php echo $evtVenueSlug ? '/venue/' . htmlspecialchars($evtVenueSlug, ENT_QUOTES, 'UTF-8') : '#'; ?>"><?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
											</div>
											<div class="text-muted small">
												<a href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
											</div>
										</div>
										<div class="ms-3">
											<?php renderEventPriceTag($event); ?>
											<a href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary d-flex align-items-center gap-2">
												<span class="d-none d-md-inline">Find Tickets</span>
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
								No events found in <?php echo htmlspecialchars($countryLabel, ENT_QUOTES, 'UTF-8'); ?> right now.
							</h3>
						<?php } ?>
					</div>
					<?php renderLocationCategoryLinks('country', $code, $countryLabel); ?>
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
