<?php include 'header.php';
$perPage = 20;
$params = categoryListingParams('', $perPage);
$year = date('Y');
$results = getAllEvents();
$total_count = (int) ($results['totalCount'] ?? 0);

$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;
$events = $results['results'] ?? [];
$percent = $total_count > 0 ? ($perPage / $total_count) * 100 : 0;
$faqs = getFaqs('events');
?>

<!-- Hero Section -->
<section class="tickets-hero-section section-padding">
	<div class="container">		
		<!-- Hero Content -->
		<div class="row justify-content-center align-items-center">
			<div class="col-lg-7 col-md-8">
				<h1 class="hero-title text-center">
					<span class="hero-title-white">Buy Tickets Online</span>
				</h1>
				<p class="hero-subtitle">
					Discover the best deals on live events with our verified ticket marketplace network. Safe checkout, real tickets, and instant access to unforgettable experiences.
				</p>
			</div>
		</div>
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
						<div class="filter-bar">
							<input type="hidden" id="latEvent" value="">
							<input type="hidden" id="lngEvent" value="">
							<input type="hidden" id="sdateEvent" value="">
							<input type="hidden" id="edateEvent" value="">
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
							<div id="location-no-results" class="text-center no-location mt-5 mb-5"></div>
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
													data-date="<?php echo date('D, M d', $timestamp); ?>"
													data-venue="<?php echo $event['venue']['text']['name']; ?>"
													data-venueSlug="<?php echo $venueSlug; ?>"
													data-location="<?php echo $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']; ?>"
													data-title="<?php echo $event['text']['name']; ?>"
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
												<a href="/event/<?php echo $slug; ?>"><?php echo $event['text']['name']; ?></a>
											</div>
										</div>
										<div class="ms-3">
											<?php renderEventPriceTag($event); ?>
											<a href="/event/<?php echo $slug; ?>" class="btn btn-primary d-flex align-items-center gap-2">
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
								No Events found!
							</h3>
						<?php } ?>
					</div>
					<div class="ad-container-left my-4 mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
						<img src="/images/adsense.webp" alt="Sponsored advertisement" class="ad-image-left" width="804" height="96" />
					</div>	
					<div class="tab-section content-section-detail mb-0" id="promocode">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">Event Promo Codes in the US</h2>
						<p>Apply verified ticket promo codes and save instantly on all events at checkout.</p>
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
								<img src="/images/6233961956292020331.webp" alt="Sponsored advertisement" class="ad-image" width="335" height="279" />
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
						<!-- <div class="category-links guarantee-card d-flex align-items-center justify-content-between">
							<div class="guarantee">
								<strong>Categories</strong><br>
								<a href="/concerts" class="common-btn">View All Concerts Events</a>
								<a href="/sports" class="common-btn">View All Sports Events</a>
								<a href="/theater" class="common-btn">View All Theater Events</a>
								<a href="/festival" class="common-btn">View All Festival Events</a>
							</div>
						</div>							 -->
					</div>
				</div>
			</div>
		</div>
		<div class="tab-section content-section-detail">
			<div class="row">
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="me-0 me-md-3 me-lg-3 me-xl-3 me-xxl-3">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">About Live Events Across the United States</h2>
						Discover upcoming live events across the United States with access to concerts, sports games, theater performances, comedy shows, festivals, and more. Seat Outlet helps fans explore events in major cities and venues nationwide with an easy-to-browse event listing experience.<br><br>
						Browse thousands of upcoming events by date, location, performer, venue, or category. Whether you're searching for last-minute tickets, planning ahead for a major tour, or looking for weekend entertainment near you, our event listings make it simple to find the right experience.<br><br>
						From chart-topping concerts and championship sports matchups to Broadway shows and family-friendly entertainment, Seat Outlet connects fans with a wide selection of live events happening throughout the country.
					</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="so-about mt-3 mt-sm-3 mt-md-0 mt-lg-0 mt-xl-0 mt-xxl-0">
						<img src="/images/event-ticket-buying.webp" alt="About Live Events Across the United States" class="img-about img-fluid rounded" width="567" height="378" />
					</div>
				</div>				
			</div>
		</div>
		<?php if (!empty($faqs)) { ?>
			<div class="tab-section content-section-detail">
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
