<?php 


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
    isset($_POST['latHeader'], $_POST['lngHeader'], $_POST['locationInputHeader']) &&
    $_POST['latHeader'] !== '' && $_POST['lngHeader'] !== '' &&
	$_POST['locationInputHeader'] !== ''
) {
	$lat = floatval($_POST['latHeader']);
    $lng = floatval($_POST['lngHeader']);
    $params['geoFilter'] = sprintf('nearby(%F, %F, 50mi)', $lat, $lng);
}

/*
|--------------------------------------------------------------------------
| KEYWORD FILTER
|--------------------------------------------------------------------------
*/

if (
	isset($_POST['keywordHeader']) &&
	$_POST['keywordHeader'] !== ''
) {
	$keywordHeader = $_POST['keywordHeader'];
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
	isset($_POST['startInputHeader'], $_POST['endInputHeader']) &&
	$_POST['startInputHeader'] !== '' &&
    $_POST['endInputHeader'] !== ''
) {
	$startTimestamp = strtotime($_POST['startInputHeader']);
    $endTimestamp   = strtotime($_POST['endInputHeader']);

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

include 'header.php'; 

$year = date('Y');
$results = getHeaderSearchEvents($params);
$total_count = $results['totalCount'];
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
$events = $results['results'];
$count = count($events);
$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
// if(!empty($keywordHeader)) {
// 	$artistVenueData = getKeywordSearchResults($keywordHeader);
// 	$artistData = $artistVenueData['performers'] ?? [];
// 	$venueData = $artistVenueData['venues'] ?? [];
// }
$faqs = getFaqs($mysqli, 'search');
?>

<section>
	<div class="container">
		
		<?php if(!empty($keywordHeader)) { ?>
			<div class="section-suggestions new-slider py-md-5 py-4">
				<h2 class="fw-bold fs-4 mb-4">Top Suggestions</h2>			
				<div class="suggestion-slider"></div>
			</div>
		<?php } ?>

		<?php /*if($artistData['totalResultCount'] > 0 || $venueData['totalResultCount'] > 0) { ?>
			<div class="section-suggestions new-slider py-md-5 py-4">
				<h2 class="fw-bold fs-4 mb-4">Top Suggestions</h2>			
				<div class="suggestion-slider ">
					<?php if($artistData['totalResultCount'] > 0) { ?>
						<?php foreach($artistData['results'] as $artistItem) { 
							$artistDetails = getTnPerformerById($artistItem['id']);
							$defaultCategory = $artistDetails['defaultCategory'];
							$artistImage = getArtistImage($artistItem['name'], $defaultCategory);	
							$subcategory = '';
							if (!empty($defaultCategory)) {
								if ($defaultCategory['depth'] == 2) {
									$subcategory = $defaultCategory['text']['name'];
								} else {
									if (!empty($defaultCategory['ancestors'])) {
										foreach ($defaultCategory['ancestors'] as $ancestor) {
											if ($ancestor['depth'] == 2) {
												$subcategory = $ancestor['text']['name'];
												break;
											}
										}										
									}
								}	
								if(empty($subcategory) || $subcategory == 'OTHER') {
									foreach ($defaultCategory['ancestors'] as $ancestor) {
										if ($ancestor['depth'] == 1) {
											$subcategory = $ancestor['text']['name'];
											break;
										}
									}
								}							
							}
						?>
							<a href="/artist/<?php echo createSlug($artistItem['name'], $artistItem['id']); ?>" class="team-link">
								<div class="card venue-card">
									<div class="venue-img">
										<img src="<?php echo $artistImage; ?>" alt="<?php echo $artistItem['name']; ?>" class="img-fluid">
									</div>
									<div class="venue-content text-center">
										<h5 class="venue-title"><?php echo $artistItem['name']; ?></h5>
										<p class="venue-location mb-0"><?php echo ucfirst(strtolower($subcategory)); ?></p>
									</div>
								</div>
							</a>
						<?php } ?>
					<?php } ?>
					<?php if($venueData['totalResultCount'] > 0) { ?>
						<?php foreach($venueData['results'] as $venueItem) {
							$venueImage = getVenueImage($venueItem['name']);
							$imgUrl = !empty($venueImage) ? $venueImage : '/assets/venue.webp'; 				
						?>
							<a href="/venue/<?php echo createSlug($venueItem['name'], $venueItem['id']); ?>" class="team-link">
								<div class="card venue-card">
									<div class="venue-img">
										<img src="<?php echo $imgUrl; ?>" alt="<?php echo $venueItem['name']; ?>" class="img-fluid">
									</div>
									<div class="venue-content text-center">
										<h5 class="venue-title"><?php echo $venueItem['name']; ?></h5>
										<p class="venue-location mb-0"><?php echo $venueItem['city'] . ', ' . $venueItem['state']; ?></p>
									</div>
								</div>
							</a>
						<?php } ?>
					<?php } ?>							
				</div>
			</div>
		<?php }*/ ?>


        <div class="tab-section section-performer-content" id="default">
			<div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
				<div class="col-sm-12 col-md-8 left-bar">
					<div class="mb-3 mb-md-4 mb-lg-4">
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
													data-date="<?php echo date('D, M d', $timestamp); ?>"
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
										data-type="search"
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
							<div class="text-center no-events-found">
								<h4>No Upcoming Events</h4>
								<p>We're sorry, but we couldn't find any upcoming events for "<?php echo $keywordHeader; ?>". Please try updating your location, date range or searching for something else.</p>
							</div>
						<?php } ?>
					</div>	
					<div class="ad-container-left my-4 mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
						<img src="<?php echo HOME_URL; ?>/assets/adsense.webp" alt="Sponsored advertisement" class="ad-image-left" />
					</div>	
					<div class="tab-section content-section-detail mb-0" id="promocode">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">Exclusive Discounts on Event Tickets</h2>
						<p>Save on your tickets with these SeatOutlet promo codes:</p>
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
