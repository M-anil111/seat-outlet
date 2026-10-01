<?php
require_once 'functions.php';
// SEO: this page previously rendered with no <title>/canonical at all.
$pageMetaTitle       = 'Sports Tickets | Seat Outlet';
$pageMetaDescription = 'Buy sports tickets for upcoming games and matchups. Compare prices and book securely on Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/sports';
[$when, $sort, $isFiltered] = listingRequestState();
if ($isFiltered) { $pageRobots = 'noindex, follow'; }   // filtered/sorted variants: canonical page stays the indexed one
include 'header.php';
$perPage = 20;
$params = categoryListingParams(TN_CATEGORY_PATH_SPORTS, $perPage, 1, $when, $sort);
$year = date('Y');
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = $results['totalCount'] ?? 0;
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
$events = $results['results'] ?? [];
$count = count($events);
$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
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
									SPORTS TICKETS <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $total_count; ?>
										<?php echo $total_count > 1 ? 'RESULTS' : 'RESULT'; ?>
									</span>
								</h1>							
							</div>
						</div>
					</div>
					<?php renderListingFilters('/sports', $when, $sort, $total_count); ?>
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
			<?php renderCategoryCityLinksBlock($events, 'sports-city', 'Sports'); ?>
		</div>
	</div>
</section>


<?php include 'footer.php'; ?>
