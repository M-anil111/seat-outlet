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
$performer_bio = getArtistBio($artistName, $id);
$faqs = getFaqs($mysqli, 'performer');
if(strtolower($breadcrumbs[1]['label']) == 'sports') {
	$topcats = [
		'.1859.1988.1879.1959.' => 'nfl',
		'.1859.1988.1865.1971.' => 'nba',
		'.1859.1988.1864.1969.' => 'mlb',
		'.1859.1988.1883.1972.' => 'nhl',
		'.1859.1988.1913.1970.' => 'mls'
	];
	$imageType = 'team';
	$performer_image = getTeamImage($artistName, $topcats[$performer['defaultCategory']['path']], strtolower($performer['defaultCategory']['ancestors'][0]['text']['name']));
}else{
	$imageType = 'artist';
	$performer_image = getArtistImage($artistName, $performer['defaultCategory']);
}
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
								<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" class="img-fluid rounded artist-img" />
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
											data-dcat="<?php echo strtolower($breadcrumbs[1]['label']); ?>">
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
									$dataPerformers = implode('|', array_filter($names));	
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
													data-location="<?php echo $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']; ?>"
													data-title="<?php echo $event['text']['name']; ?>"
													data-performers="<?php echo $dataPerformers; ?>"
												></i>
											</div>
											<div class="fw-semibold location-venue-name">
												<a href="#">
													<?php echo $event['city']['text']['name']; ?>,
													<?php echo $event['stateProvince']['text']['abbr']; ?>
												</a>
												·
												<a href="#"><?php echo $event['venue']['text']['name']; ?></a>
											</div>
											<div class="text-muted small">
												<a href="/event.php?id=<?php echo (int) ($event['id'] ?? 0); ?>"><?php echo $event['text']['name']; ?></a>
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
								No <?php echo strtoupper($breadcrumbs[1]['label']); ?> found!
							</h4>
						<?php } ?>
					</div>	
					<div class="ad-container-left my-4 mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
						<img src="<?php echo HOME_URL; ?>/assets/adsense.webp" alt="Sponsored advertisement"	class="ad-image-left" />
					</div>
					<div class="tab-section content-section-detail mb-0" id="promocode">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black"><?php echo $artistName; ?> Concert Promo Codes in US</h2>
						<p>Apply verified <?php echo $artistName; ?> ticket promo codes and save instantly on your concert tickets at checkout.</p>
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
						<div class="ad-container mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
							<div class="mt-3 mt-md-3 mt-lg-0">
								<img src="<?php echo HOME_URL; ?>/assets/6233961956292020331.jpg" alt="Sponsored advertisement" class="ad-image img-fluid" />
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
						<div class="ad-container-fixe mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
							<div class="mt-3 mt-md-3 mt-lg-0">
								<img src="<?php echo HOME_URL; ?>/assets/6233961956292020331.jpg" alt="Sponsored advertisement" class="ad-image img-fluid" />	
							</div>
						</div>
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
					</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0">
						<img src="<?php echo $performer_image; ?>" alt="<?php echo $artistName; ?>" class="img-about img-fluid rounded" />
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
		<?php if (!empty($relatedPerformers)) { $i = 0; ?>
			<div class="tab-section content-section-detail" id="fans">
				<div class="row g-4">
					<h2 class="so-heading fw-bold fs-4 mb-4 text-black"><?php echo $artistName; ?> Fans Also Love</h2>
					<?php foreach ($relatedPerformers as $performer) { 
						$artistName = $performer['text']['name'];
						if($imageType == 'team') {
							$performer_image = getTeamImage($artistName, $topcats[$performer['defaultCategory']['path']], strtolower($performer['defaultCategory']['ancestors'][0]['text']['name']));
						}else{
							$performer_image = getArtistImage($artistName, $performer['defaultCategory']);
						}						
						$i++;
						if($i > 8) continue;
					?>
						<div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
							<a href="/artist/<?php echo strtolower($performer['uriComponent']); ?>" class="band-card-bootstrap text-decoration-none">
								<div class="position-relative overflow-hidden rounded">
									<img src="<?php echo $performer_image; ?>" class="img-fluid w-100 h-100 band-img" alt="<?php echo $artistName; ?>">
									<div class="band-content d-flex justify-content-between align-items-center">
										<span class="band-name"></span>								
									</div>
								</div>
								<div class="band-name-title text-black mt-2"><?php echo $artistName; ?></div>
							</a>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	</div>
</section>

	
<?php include 'footer.php'; ?>
