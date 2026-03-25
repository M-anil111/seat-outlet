<?php
include 'header.php';

// Sanitize and normalize pagination.
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;

// Extract performer ID from slug; expect a trailing numeric ID.
$slug  = $_GET['slug'] ?? '';
$parts = explode('-', (string) $slug);
$id    = (int) end($parts);

if ($id <= 0) {
	echo '<div class="container"><p>Invalid performer.</p></div>';
	include 'footer.php';
	exit;
}

$performer = getTnPerformerById($id);

// Ensure we have the expected structure before proceeding.
if (empty($performer) || empty($performer['defaultCategory'])) {
	echo '<div class="container"><p>Performer not found.</p></div>';
	include 'footer.php';
	exit;
}

$eventsResponse = getTnPerformerEvents($id, [
	'page'    => $page,
	'perPage' => $perPage
]);

$total_count = getTnPerformerEventsCount($id);
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;

$events = $eventsResponse['results'] ?? [];
$count  = $eventsResponse['count'] ?? count($events);

$sep = '<span class="separator"><strong> / </strong></span>';
$breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);
$relatedPerformersResponse = getRelatedPerformers($performer['defaultCategory']['path'], $id);
$relatedPerformers = $relatedPerformersResponse;
$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
$year = date('Y');
$artistName = $performer['text']['name'];
$performer_bio = getArtistBio($artistName);
$performer_image = getArtistImage($artistName);

$faqs = getFaqs($mysqli, 'performer');

?>

<section class="section-featured-header text-sm-center text-md-start">
	<div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
		style="background-image: url('<?php echo HOME_URL; ?>/assets/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
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
									<a href="#">
										<?php echo htmlspecialchars($item['label']); ?>
									</a>
								<?php } ?>
							<?php } ?>
							<?php echo $sep; ?>
							<span class="current">
								<?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
							</span>
						</nav>
					</div>
				</div>
				<div class="col-12">
					<div class="row align-items-center text-center text-md-start">
						<div class="col-md-3">
							<div class="img-artist">
								<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" class="img-fluid rounded artist-img" />	
							</div>	
						</div>
						<div class="col-md-9 text-white">
							<div class="artist-heading text-center text-md-start text-lg-start text-xl-start text-xxl-start">
								<?php
								$lastBreadcrumb = end($breadcrumbs);
								$categoryLabel  = $lastBreadcrumb['label'] ?? '';
								$categorySlug   = sanitize_title($categoryLabel);
								?>
								<div class="artist-category">
									<a href="<?php echo htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8'); ?>">
										<?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?>
									</a>
								</div>
								<h1 class="artist-title">
									<?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> at {Venue Name} – {City}, {State}
								</h1>
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
					<?php echo htmlspecialchars($breadcrumbs[1]['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
				</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="promocode" onclick="scrollToElement('promocode')">promocode</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="about" onclick="scrollToElement('about')">About</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="faqs" onclick="scrollToElement('faqs')">FAQs</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="country" onclick="scrollToElement('country')">Location</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" data-target="map" onclick="scrollToElement('map')">Map</button>
			</li>
			<!-- <li class="nav-item">
				<button class="nav-link" type="button"  data-target="promo" onclick="scrollToElement('promo')">Promo Codes</button>
			</li> -->
			<li class="nav-item">
				<button class="nav-link" type="button"  data-target="fans" onclick="scrollToElement('fans')">
					Top Artists
				</button>
			</li>
			<li class="nav-item">
			<button class="nav-link" type="button"  data-target="state" onclick="scrollToElement('state')">More</button>
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
									GET <?php echo strtoupper(htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8')); ?> <?php //echo htmlspecialchars(strtoupper($breadcrumbs[1]['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> TICKETS FOR {Venue} SHOW IN {City}, {State} <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $count; ?>
										<?php echo $count > 1 ? 'RESULTS' : 'RESULT'; ?>
									</span>
								</h2>
							</div>
						</div>
					</div>
					<div class="list-category-bg pb-3">
						<div class="filter-bar">
							<div class="row">
								<div class="col-md-6">
									<label class="filter-label">Location</label>
									<div class="filter-input">
										<i class="bi bi-geo-alt"></i>
										<input
											type="text"
											class="form-control"
											placeholder="City or Zip Code"
											id="locationInput"
											autocomplete="off"
											data-cpid="<?php echo (int) $id; ?>"
											data-dcat="<?php echo htmlspecialchars(strtolower($breadcrumbs[1]['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
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
										<!-- <input type="text" id="dateRange" class="form-control" placeholder="All Dates" readonly> -->
										<input type="text" id="parformerDatePicker" placeholder="Select Date Range" class="form-control" autocomplete="off" readonly value="<?php echo !empty($dateTitle) ? $dateTitle : ''; ?>">
										<div class="filter-arrow">
											<i id="dateArrow" class="bi bi-chevron-down"></i>
										</div>
									</div>
									<div class="date-picker-wrapper">
										<div id="datePickerSection" class="opacity-zero picker-wrapper">
											<div class="row g-3 d-none">
												<div class="col">
													<label class="form-label">Start Date</label>
													<input type="text" id="startInput" class="form-control date-input" placeholder="MM/DD/YYYY" readonly>
												</div>
												<div class="col">
													<label class="form-label">End Date</label>
													<input type="text" id="endInput" class="form-control date-input" placeholder="MM/DD/YYYY" readonly>
												</div>
											</div>
											<div class="calendar-wrapper">
												<div id="calendar"></div>
											</div>
											<div class="footer-actions">
												<span class="reset-link" id="resetDates">Reset</span>
												<div class="d-flex gap-2">
													<button class="btn btn-outline-secondary" id="cancelDates">Cancel</button>
													<button class="btn btn-primary" id="applyDates">Apply</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<h3 id="locationHeading" class="mt-2 fs-5"></h3>
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
									$dataPerformers = implode('|', array_filter($names));
								?>
									<div class="d-flex align-items-center justify-content-between performer-event-item">
										<div class="date-box text-center me-3">
											<div class="month">
												<?php echo htmlspecialchars(strtoupper(date('M', $timestamp)), ENT_QUOTES, 'UTF-8'); ?>
											</div>
											<div class="day">
												<?php echo htmlspecialchars(date('d', $timestamp), ENT_QUOTES, 'UTF-8'); ?>
											</div>
											<?php if (date('Y', $timestamp) > $year) { ?>
												<div class="month">
													<?php echo htmlspecialchars(date('Y', $timestamp), ENT_QUOTES, 'UTF-8'); ?>
												</div>
											<?php } ?>
										</div>
										<div class="flex-grow-1 w-50">
											<div class="d-flex align-items-center gap-2">
												<span class="fw-semibold day-weeks">
													<?php echo htmlspecialchars(date('D', $timestamp), ENT_QUOTES, 'UTF-8'); ?>
												</span>
												<span class="dot">·</span>
												<span class="time-clock">
													<?php echo htmlspecialchars($event['date']['text']['time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
												</span>
												<i
													class="bi bi-info-circle text-muted icon-i"
													data-bs-toggle="offcanvas"
													data-bs-target="#offcanvasRight"
													aria-controls="offcanvasRight"
													data-id="<?php echo (int) ($event['id'] ?? 0); ?>"
													data-date="<?php echo htmlspecialchars(date('D, M d', $timestamp), ENT_QUOTES, 'UTF-8'); ?>"
													data-venue="<?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-location="<?php echo htmlspecialchars(($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
													data-title="<?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
													data-performers="<?php echo htmlspecialchars($dataPerformers, ENT_QUOTES, 'UTF-8'); ?>"></i>
											</div>
											<div class="fw-semibold location-venue-name">
												<a href="#">
													<?php echo htmlspecialchars($event['city']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>,
													<?php echo htmlspecialchars($event['stateProvince']['text']['abbr'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
												</a>
												·
												<a href="#"><?php echo htmlspecialchars($event['venue']['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
											</div>
											<div class="text-muted small">
												<?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
											</div>
										</div>
										<div class="ms-3">
											<a href="/event.php?id=<?php echo (int) ($event['id'] ?? 0); ?>" class="btn btn-primary d-flex align-items-center gap-2">
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
										data-performer="<?php echo (int) $id; ?>"
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
							<h4 style="padding: 20px;">
								No <?php echo htmlspecialchars(strtoupper($breadcrumbs[1]['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> found!
							</h4>
						<?php } ?>
					</div>
					<div class="ad-container-left my-4 mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
						<img src="<?php echo HOME_URL; ?>/assets/adsense.webp" alt="Sponsored advertisement" class="ad-image-left" />
					</div>
					<div class="tab-section content-section-detail mb-3" id="promocode">
						<div class="row">
							<div class="" id="promo">
								<h2 class="mb-4 fw-bold text-black so-heading fs-4"><?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> Promo Codes for {Venue} event</h2>
								<p>Apply verified {Artist Name} ticket promo codes and save instantly on your concert tickets at checkout.</p>
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
					</div>
				</div>
				<div id="secondary" class="sidebar col-sm-12 col-md-4">
					<div class="sticky-top sidebar-inner">
						<div class="ad-container mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
							<div class="mt-3 mt-md-3 mt-lg-0">
								<img src="<?php echo HOME_URL; ?>/assets/6233961956292020331.jpg" alt="Sponsored advertisement" class="ad-image" />
							</div>
						</div>
						<div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
							<div class="guarantee">
								<strong>Shop Tickets Worry Free</strong><br>
								<span>With Our 100% Guarantee</span>
							</div>
							<div class="guarantee-icon">
								<i class="bi bi-shield-check"></i>
							</div>
						</div>
						<div class="ad-container mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
							<div class="mt-3 mt-md-3 mt-lg-0">
								<img src="<?php echo HOME_URL; ?>/assets/6233961956292020331.jpg" alt="Sponsored advertisement" class="ad-image" />
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="tab-section content-section-detail" id="about">
				<div class="row">
					<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
						<div class="me-0 me-md-3 me-lg-3 me-xl-3 me-xxl-3">
							<h2 class="mb-4 fw-bold text-black so-heading fs-4">About <?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> Perfoming at {Venue}</h2>
							<p><?php echo getArtistBioFromWikipedia($artistName); ?></p>
							<!-- <p><?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> brings a unique blend of trip hop, hip hop, and cinematic soundscapes to live audiences across the country. Fans can experience immersive performances at top venues with guaranteed authentic tickets.</p>
						<ul>
							<li>Browse upcoming <?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> tour dates</li>
							<li>Compare ticket prices from trusted sellers</li>
							<li>Secure seats for popular venues</li>
							<li>Mobile friendly ticket delivery</li>
							<li>Backed by a 100% buyer guarantee</li>
						</ul>
						<p class="one-liner">Buy with confidence and enjoy live music the way it was meant to be experienced.</p> -->
						</div>
					</div>
					<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
						<div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0">
							<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" />
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($faqs)) { ?>
				<div class="tab-section content-section-detail" id="faqs">
					<h2 class="mb-4 fw-bold text-black so-heading fs-4"> FAQs About <?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> Tickets at {Venue}</h2>
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
										<?php echo htmlspecialchars($question); ?>
									</button>
								</h2>
								<div id="<?php echo $collapseId; ?>"
									class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>"
									aria-labelledby="<?php echo $headingId; ?>"
									data-bs-parent="#faqAccordion">
									<div class="accordion-body">
										<?php echo nl2br(htmlspecialchars($answer)); ?>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>
			<div class="tab-section content-section-detail" id="country">
			<div class="row">
				<div class="col-sm-12 col-md-8 col-lg-8 col-xl-8 col-xxl-8">
					<div class="me-0 me-md-3 me-lg-3 me-xl-3 me-xxl-3">
						<h2 class="mb-4 fw-bold text-black so-heading fs-4"> About {Venue} in {City}, {State} </h2>
						
						<p>Aaron Lewis is set to perform live at <strong>White Eagle Hall in Jersey City, New Jersey,</strong> offering fans an unforgettable night of country and acoustic rock music. Known as the lead vocalist of the rock band Staind and for his successful solo country career, Aaron Lewis delivers powerful live performances that connect deeply with audiences.</p><br>

						<p>White Eagle Hall is one of Jersey City’s most popular live music venues, known for its intimate atmosphere and excellent acoustics. This historic venue provides the perfect setting for fans to experience Aaron Lewis performing some of his biggest hits along with new material from his latest releases.</p><br>

						<p>Fans attending the show can expect a memorable concert featuring a mix of classic songs, heartfelt storytelling, and an authentic country sound. Whether you have followed Aaron Lewis for years or are discovering his music for the first time, his performance at White Eagle Hall promises to be an incredible live music experience.</p>
					</div>
				</div>
				<div class="col-sm-12 col-md-4 col-lg-4 col-xl-4 col-xxl-4">
					<div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0 image-area">
						<!-- <img src="<?php //echo $performer_image; ?>" alt="<?php //echo $artistName; ?>" /> -->
						<div class="img-thub ad-image">
							<img src="../assets/images/white-eagle-hall.webp" alt="" class="img-fluid" />
						</div>
					</div>
				</div>
			</div>
		</div>

		</div>
</section>


<section class="tab-section lake-links-section py-5" id="map">
	<div class="container">
		<div class="row align-items-center g-4">


			<div class="col-12 col-lg-6">
				<div class="map-wrapper">
				<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3023.6824359157713!2d-74.05412772316913!3d40.725006836818!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c250b5312d5141%3A0xd3be6df92d9de094!2sWhite%20Eagle%20Hall!5e0!3m2!1sen!2sin!4v1773401117044!5m2!1sen!2sin" width="100%" height="auto" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
				</div>
			</div>


			<div class="col-12 col-lg-6">
				<div class="links-wrapper text-center text-lg-start">

					<h2 class="mb-4 fw-bold text-white so-heading fs-4">
					Complete {Performer} Concert Guide – {Venue}
					</h2>

					<div class="row g-3">


						<div class="col-12">
							<a href="#" class="lake-link-box">About {Venue Name} in {City}</a>
						</div>

						<div class="col-12">
							<a href="#" class="lake-link-box">Capacity in {Venue Name} in {City}</a>
						</div>

						<div class="col-12">
							<a href="#" class="lake-link-box">Public transportation in {Venue Name} in {City}</a>
						</div>


					</div>

				</div>
			</div>

		</div>
	</div>
</section>
<div class="container similar-events">
   <div class="tab-section content-section-detail" id="fans">
      <div class="row g-4">
	  <h2 class="mb-4 fw-bold text-black so-heading fs-4">Top Upcoming Performances at {Venue}</h2>
         <div class="col-lg-3 col-md-4 col-sm-6">
            <a href="#">
		 	<div class="other-event-card">
               <div class="other-event-img">
                  <img src="assets/images/indie-rock-night.webp" class="img-fluid">
               </div>
               <div class="other-event-body">
                  <h3 class="other-event-title">
				  Indie Rock Night
                  </h3>
                  <p class="other-event-date">
                     Apr 12 · White Eagle Hall
                  </p>
                  <div class="other-event-price">
                     From <span>$35</span>
                  </div>
               </div>
            </div>
			</a>
         </div>
         <div class="col-lg-3 col-md-4 col-sm-6">
			<a href="#">
            <div class="other-event-card">
               <div class="other-event-img">
                  <img src="assets/images/pru-hall.webp" class="img-fluid">
               </div>
               <div class="other-event-body">
                  <h3 class="other-event-title">
				  Symphony Orchestra Live
                  </h3>
                  <p class="other-event-date">
                     Apr 18· New Jersey Performing Arts Center – Prudential Hall
                  </p>
                  <div class="other-event-price">
                     From <span>$45</span>
                  </div>
               </div>
            </div>
			</a>
         </div>
         <div class="col-lg-3 col-md-4 col-sm-6">
			<a href="#">
            <div class="other-event-card">
               <div class="other-event-img">
                  <img src="assets/images/loews-theatre.webp" class="img-fluid">
               </div>
               <div class="other-event-body">
                  <h3 class="other-event-title">
				  Jazz Evening at Loew’s
                  </h3>
                  <p class="other-event-date">
                     Apr 25 · Loew's Jersey Theatre
                  </p>
                  <div class="other-event-price">
                     From <span>$28</span>
                  </div>
               </div>
            </div>
			</a>
         </div>
         <div class="col-lg-3 col-md-4 col-sm-6">
			<a href="#">
            <div class="other-event-card">
               <div class="other-event-img">
                  <img src="assets/images/loews-theatre-restoration.webp" class="img-fluid">
               </div>
               <div class="other-event-body">
                  <h3 class="other-event-title">
				  Alternative Rock Concert
                  </h3>
                  <p class="other-event-date">
                     May 3 · White Eagle Hall
                  </p>
                  <div class="other-event-price">
                     From <span>$40</span>
                  </div>
               </div>
            </div>
			</a>
         </div>
      </div>
   </div>
</div>

<section class="location-section tab-section py-5" id="state">
	<div class="container">

		<div class="row g-3">
			<h2 class="mb-4 fw-bold text-white text-center fs-4">
			<?php echo $artistName; ?> Concerts in Other {Country}
			</h2>
			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>
			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>
			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>
			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>

			<div class="col-12 col-md-6 col-lg-4">
				<a href="#" class="location-box">{Artist} Concerts in {country}</a>
			</div>


		</div>
	</div>
</section>


<script>

document.addEventListener("DOMContentLoaded", function () {

let selectedDatesTemp = [];

function getMonthCount() {
	return window.innerWidth <= 689 ? 1 : 2;
}

let fp = flatpickr("#parformerDatePicker", {
	mode: "range",
	dateFormat: "M j, Y",
	showMonths: getMonthCount(),
	disableMobile: true,
	clickOpens: true,

	onChange: function(selectedDates) {
		selectedDatesTemp = selectedDates;
	},

	onReady: function(selectedDates, dateStr, instance) {

		instance.calendarContainer.classList.add("so-date-picker");

		const footer = document.createElement("div");
		footer.className = "fp-footer";

		footer.innerHTML = `
			<div class="fp-footer-left">
				<button class="fp-reset">Reset</button>
			</div>
		`;

		instance.calendarContainer.appendChild(footer);

		footer.querySelector(".fp-reset").addEventListener("click", () => {
			instance.clear();
		});
	}
});

// 🔥 Handle resize dynamically
window.addEventListener("resize", function () {
	const newMonthCount = getMonthCount();

	if (fp.config.showMonths !== newMonthCount) {
		fp.set("showMonths", newMonthCount);
		fp.redraw();
	}
});

});
</script>

<?php include 'footer.php'; ?>