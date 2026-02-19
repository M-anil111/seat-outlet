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
$performer_bio = getArtistBioFromWikipedia($artistName);
$performer_image = getArtistImageFromWikimedia($artistName);

$faqs = getFaqs($mysqli, 'performer');

?>

<section class="section-featured-header text-sm-center text-md-start">
	<div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
		style="background-image: url('<?php echo HOME_URL; ?>/artists/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
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
						<div class="col-md-4">
							<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" class="img-fluid rounded artist-img" />							
						</div>
						<div class="col-md-8 text-white">
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
									<?php //echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>Aaron Lewis in {country}
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
				<button class="nav-link" type="button"  data-target="about" onclick="scrollToElement('about')">About</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button"  data-target="faqs" onclick="scrollToElement('faqs')">FAQs</button>
			</li>
			<!-- <li class="nav-item">
				<button class="nav-link" type="button"  data-target="promo" onclick="scrollToElement('promo')">Promo Codes</button>
			</li> -->
			<!-- <li class="nav-item">
				<button class="nav-link" type="button"  data-target="fans" onclick="scrollToElement('fans')">
					Fans Also Viewed
				</button>
			</li> -->
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
									<?php echo strtoupper(htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8')); ?> <?php echo htmlspecialchars(strtoupper($breadcrumbs[1]['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> IN {country} <span class="dot">·</span>
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
											<button type="button" id="locationInputReset" class="d-none">
												<svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false"><path d="M23 3.88H5.66L1 12l4.66 8.12H23zM2.74 12l3.8-6.62h14.95v13.24H6.54zm13.98 4.4-3.38-3.34-3.37 3.34-1.07-1.06L12.27 12 8.9 8.65l1.07-1.06 3.37 3.34 3.38-3.34 1.07 1.06L14.4 12l3.38 3.34z"></path></svg>
											</button>
											<div id="locationResults" class="tn-dropdown-menu dropdown"></div>
									</div>
								</div>
								<div class="col-md-6">
									<label class="filter-label">Dates</label>
									<div class="filter-input">
										<i class="bi bi-calendar3"></i>
										<input type="text" id="dateRange" class="form-control" placeholder="All Dates" readonly>
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
											<?php if(date('Y', $timestamp) > $year) { ?>
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
													data-performers="<?php echo htmlspecialchars($dataPerformers, ENT_QUOTES, 'UTF-8'); ?>"
												></i>
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
						<img src="<?php echo HOME_URL; ?>/artists/adsens.webp" alt="Sponsored advertisement"	class="ad-image-left" />
					</div>				
				</div>
				<div id="secondary" class="sidebar col-sm-12 col-md-4">
					<div class="sticky-top sidebar-inner">
						<div class="ad-container mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
							<div class="mt-3 mt-md-3 mt-lg-0">
								<img src="<?php echo HOME_URL; ?>/artists/6233961956292020331.jpg" alt="Sponsored advertisement" class="ad-image" />
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
						<div class="sidebar-card" id="promo">
							<h4><?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> concert Promo Codes in {country}</h4>
							<p>Apply verified {Artist Name} ticket promo codes and save instantly on your concert tickets at checkout.</p>
							<div class="offer-pill d-flex align-items-center justify-content-between mt-3">
								<div class="d-flex align-items-center">
									<div class="offer-icon me-3 d-flex align-items-center justify-content-center">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
											<path d="M3 12.5V5.8A1.8 1.8 0 0 1 4.8 4h6.7L21 13.5l-6.4 6.4L3 12.5Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"></path>
											<circle cx="8.2" cy="8.2" r="1.1" fill="white"></circle>
										</svg>
									</div>
									<div class="offer-text">
										<div class="offer-title">5% OFF</div>
										<div class="offer-subtitle">TAKE5</div>
									</div>
								</div>
								<div class="offer-copy text-end">
									<button type="button" class="btn btn-primary text-white offer-copy-btn btn-sm px-4 rounded-pill" data-code="TAKE5">
									Copy
									</button>
								</div>
							</div>
							<div class="offer-pill d-flex align-items-center justify-content-between mt-3">
								<div class="d-flex align-items-center">
									<div class="offer-icon me-3 d-flex align-items-center justify-content-center">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
											<path d="M3 12.5V5.8A1.8 1.8 0 0 1 4.8 4h6.7L21 13.5l-6.4 6.4L3 12.5Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"></path>
											<circle cx="8.2" cy="8.2" r="1.1" fill="white"></circle>
										</svg>
									</div>
									<div class="offer-text">
										<div class="offer-title">10% OFF</div>
										<div class="offer-subtitle">TAKE10</div>
									</div>
								</div>
								<div class="offer-copy text-end">
									<button type="button" class="btn btn-primary text-white offer-copy-btn btn-sm px-4 rounded-pill" data-code="TAKE10">
									Copy
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-section content-section-detail" id="about">
			<div class="row">
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="so-about me-3">
						<h2 class="so-heading mb-3">About <?php echo htmlspecialchars($performer['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
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
						<!-- <img src="../artists/closeup.jpg" alt="" class="img-fluid" /> -->
						<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" />
					</div>
				</div>				
			</div>
		</div>
		<?php if (!empty($faqs)) { ?>
			<div class="tab-section content-section-detail" id="faqs">
				<h2 class="so-heading mb-3">FAQs About Aaron Lewis Tickets in {Country}</h2>
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
		
	</div>
</section>

<section class="location-section py-5">
    <div class="container">
	<h2 class="mb-4 fw-bold text-white text-center">
               Upcoming {Venue Name} Concert in Other Countries

            </h2>
      <div class="row g-3">
  
       
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Aaron Lewis in Canada</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Aaron Lewis in UK</a>
        </div>
  
        <div class="col-12 col-md-6 col-lg-4">
          <a href="#" class="location-box">Aaron Lewis in Australia</a>
        </div>
  
        
  
      </div>
    </div>
  </section>


	
<?php include 'footer.php'; ?>
