<?php include 'header-new.php'; 
$page = $_GET['page'] ?? 1;
$perPage  = 20;
$slug = $_GET['slug'];
$parts = explode('-', $slug);
$id = end($parts);
$performer = getTnPerformerById($id);
$eventsResponse = getTnPerformerEvents($id, [
    'page'    => (int)$page,
    'perPage' => $perPage
]);
$total_count = getTnPerformerEventsCount($id);
$total_pages = ceil($total_count / $perPage);
$count = $eventsResponse['count'] ?? count($eventsResponse['results']);
$events = $eventsResponse['results'] ?? [];
$sep = '<span class="separator"><strong> / </strong></span>';
$breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);
$relatedPerformersResponse = getRelatedPerformers($performer['defaultCategory']['path'], $id);
$relatedPerformers = $relatedPerformersResponse;
$percent = ($perPage / $total_count) * 100;
?>

<section class="section-featured-header text-sm-center text-md-start">
	<div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
		style="background-image: url('../artists/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
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
							<span class="current"><?php echo $performer['text']['name']; ?></span>
						</nav>
					</div>
				</div>
				<div class="col-12">
					<div class="row align-items-center text-center text-md-start">
						<div class="col-md-4">
							<img src="../artists/17.jpg" alt="RAYE" class="img-fluid rounded artist-img">
						</div>
						<div class="col-md-8 text-white">
							<div class="artist-heading text-center text-md-start text-lg-start text-xl-start text-xxl-start">
								<div class="artist-category"><a href="<?php echo sanitize_title(end($breadcrumbs)['label']); ?>"><?php echo end($breadcrumbs)['label']; ?></a></div>
								<h1 class="artist-title"><?php echo $performer['text']['name']; ?> Tickets</h1>
								<div class="d-flex gap-3 mt-3 justify-content-center justify-content-md-start">
									<div class="icon-circle"><i class="bi bi-heart fs-6 pt-1"></i></div>
									<div class="rating">
										⭐ <strong>5.0</strong>
									</div>
								</div>
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
				<button class="nav-link" type="button"  data-target="about" onclick="scrollToElement('about')">About</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button"  data-target="faqs" onclick="scrollToElement('faqs')">FAQs</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button"  data-target="fans" onclick="scrollToElement('fans')">
					Fans Also Viewed
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
									<?php echo strtoupper($breadcrumbs[1]['label']); ?> <span class="dot">·</span>
									<span class="count" id="results_count"> <?php echo $count; ?> <?php echo $count > 1 ? 'RESULTS' : 'RESULT'; ?></span>
								</h2>
							</div>
							<div class="view-toggle btn-group d-none">
								<button class="btn btn-toggle active" aria-label="List view">
									<i class="bi bi-list"></i>
								</button>
								<button class="btn btn-toggle" aria-label="Calendar view">
									<i class="bi bi-calendar3"></i>
								</button>
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
										<input type="text" class="form-control" placeholder="City" id="locationInput" autocomplete="off" data-cpid="<?php echo $id; ?>" data-dcat="<?php echo strtolower($breadcrumbs[1]['label']); ?>">
										<div id="locationResults" class="tn-dropdown-menu dropdown"></div>
									</div>
								</div>
								<div class="col-md-6">
									<label class="filter-label">Dates</label>
									<div class="filter-input">
										<i class="bi bi-calendar3"></i>
										<input type="text" id="dateRange" class="form-control" placeholder="All Dates" readonly>
									</div>
								</div>
							</div>
							<h3 id="locationHeading" class="mt-2 fs-5"></h3>
							<div id="location-no-results" class="text-center no-location"></div>
						</div>
						<?php if (!empty($events)) { ?>
							<div id="eventsSection" class="section-artist-content event-row-all">
								<?php foreach ($events as $event) { 
									$evtPerformers = $event['performers'];
									$names = array_map(function ($performer) {
										return $performer['name'] ?? null;
									}, $evtPerformers);
									$dataPerformers = implode('|', array_filter($names));	
								?>
									<div class="d-flex align-items-center justify-content-between performer-event-item">
										<div class="date-box text-center me-3">
											<div class="month"><?php echo strtoupper(date('M', strtotime($event['date']['date']))); ?></div>
											<div class="day"><?php echo date('d', strtotime($event['date']['date'])); ?></div>
										</div>
										<div class="flex-grow-1 w-50">
											<div class="d-flex align-items-center gap-2">
												<span class="fw-semibold day-weeks"><?php echo date('D', strtotime($event['date']['date'])); ?></span>
												<span class="dot">·</span>
												<span class="time-clock"><?php echo $event['date']['text']['time']; ?></span>
												<i class="bi bi-info-circle text-muted icon-i" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" data-id="<?php echo $event['id']; ?>" data-date="<?php echo date('D, M d', strtotime($event['date']['date'])); ?>" data-venue="<?php echo $event['venue']['text']['name']; ?>" data-location="<?php echo $event['city']['text']['name']; ?>, <?php echo $event['stateProvince']['text']['abbr']; ?>" data-title="<?php echo $event['text']['name']; ?>" data-performers="<?php echo $dataPerformers; ?>"></i>
											</div>
											<div class="fw-semibold">
												<?php echo $event['city']['text']['name']; ?>, <?php echo $event['stateProvince']['text']['abbr']; ?> · <?php echo $event['venue']['text']['name']; ?>
											</div>
											<div class="text-muted small">
												<?php echo $event['text']['name']; ?>
											</div>
										</div>
										<div class="ms-3">
											<a href="/event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary d-flex align-items-center gap-2">
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
									<button class="btn more-events-btn d-inline-flex align-items-center gap-2" id="loadMoreBtn" data-total="<?php echo $total_count; ?>" data-page="2" data-performer="<?php echo $id; ?>" data-perpage="<?php echo $perPage; ?>">
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
							<h4 style="padding: 20px;">No <?php echo strtoupper($breadcrumbs[1]['label']); ?> found!</h4>
						<?php } ?>
					</div>					
				</div>
				<div id="secondary" class="sidebar col-sm-12 col-md-4">
					<div class="sticky-top sidebar-inner">
						<div class="ad-container mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
							<div class="ad-card mt-3 mt-md-3 mt-lg-0">
								<img
									src="../artists/6233961956292020331.jpg"
									alt="Sponsored advertisement"
									class="ad-image" />
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
						<div class="sidebar-card">
							<h4><?php echo $performer['text']['name']; ?> Tickets Promo Codes</h4>
							<p>Use verified promo codes to save instantly on event tickets at checkout.</p>
							<div class="promo-list mt-3">
								<a href="javascript:void(0)" onclick="copyPromoCode('TAKE5', this)">
									<div class="promo">
										<span>5% OFF</span>
										TAKE5
									</div>
								</a>
								<a href="javascript:void(0)" onclick="copyPromoCode('TAKE10', this)">
									<div class="promo">
										<span>10% OFF</span>
										TAKE10
									</div>
								</a>
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
					<h2 class="so-heading mb-3"><?php echo $performer['text']['name']; ?> Tickets and Tour Information</h2>
					<p><?php echo $performer['text']['name']; ?> brings a unique blend of trip hop, hip hop, and cinematic soundscapes to live audiences across the country. Fans can experience immersive performances at top venues with guaranteed authentic tickets.</p>
					<ul>
						<li>Browse upcoming <?php echo $performer['text']['name']; ?> tour dates</li>
						<li>Compare ticket prices from trusted sellers</li>
						<li>Secure seats for popular venues</li>
						<li>Mobile friendly ticket delivery</li>
						<li>Backed by a 100% buyer guarantee</li>
					</ul>
					<p class="one-liner">Buy with confidence and enjoy live music the way it was meant to be experienced.</p>
				</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
					<div class="so-about">
						<img src="../artists/closeup.jpg" alt="" class="img-fluid" />
					</div>
				</div>
				
			</div>
		</div>
		<div class="tab-section content-section-detail" id="faqs">
			<h2 class="so-heading mb-3">Frequently Asked Questions</h2>
			<div class="accordion" id="faqAccordion">
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading1">
						<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
							Are <?php echo $performer['text']['name']; ?> tickets guaranteed?
						</button>
					</h2>
					<div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="heading1" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Yes, all tickets are backed by a 100% guarantee to ensure authenticity and timely delivery.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading2">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
							Can I use promo codes on tickets?
						</button>
					</h2>
					<div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Promo codes like SAVE5 and SAVE10 can be applied during checkout.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading3">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
							When will I receive my tickets?
						</button>
					</h2>
					<div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Delivery timing depends on the venue and ticket type.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading4">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
							Are tickets mobile friendly?
						</button>
					</h2>
					<div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Most tickets are available for mobile entry.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading5">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
							Can I get a refund if the event is canceled?
						</button>
					</h2>
					<div id="collapse5" class="accordion-collapse collapse" aria-labelledby="heading5" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Yes, canceled events are eligible for refunds.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading6">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
							Do ticket prices change?
						</button>
					</h2>
					<div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Prices may fluctuate based on demand and availability.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-section content-section-detail" id="fans">
			<h2 class="so-heading mb-4">Fans Also Viewed</h2>
			<?php if (!empty($relatedPerformers)) { ?>
				<div class="area-container">
					<?php foreach ($relatedPerformers as $performer) { ?>
						<a href="/artist/<?php echo strtolower($performer['uriComponent']); ?>" target="_blank" class="area-box"><?php echo $performer['text']['name']; ?></a>
					<?php } ?>
				</div>
			<?php } else { ?>
				<p>No related performers found.</p>
			<?php } ?>
		</div>
	</div>
</section>

	
<?php include 'footer.php'; ?>
