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
$count = $eventsResponse['count'] ?? count($eventsResponse['results']);
$hasMore = ($count === $perPage);
$events = $eventsResponse['results'] ?? [];
$daynames = DAY_NAMES;
$sep = '<span class="separator"><strong> / </strong></span>';
$breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);
$relatedPerformersResponse = getRelatedPerformers($performer['defaultCategory']['path'], $id);
$relatedPerformers = $relatedPerformersResponse;
?>

<section class="section-featured-header text-sm-center text-md-start">
	<div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
		style="background-image: url('../artists/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
		<div class="container">
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
					<div class="row align-items-center">
						<div class="col-md-4">
							<img src="../artists/17.jpg" alt="RAYE" class="img-fluid rounded artist-img">
						</div>
						<div class="col-md-8 text-white">
							<div class="artist-heading">
								<div class="artist-category"><a href="<?php echo sanitize_title(end($breadcrumbs)['label']); ?>"><?php echo end($breadcrumbs)['label']; ?></a></div>
								<h1 class="artist-title"><?php echo $performer['text']['name']; ?> Tickets</h1>
								<div class="d-flex align-items-center gap-3 mt-3">
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
				<button class="nav-link active" type="button" onclick="scrollToElement('default')">
					<?php echo $breadcrumbs[1]['label']; ?>
				</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" onclick="scrollToElement('about')">About</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" onclick="scrollToElement('faqs')">FAQs</button>
			</li>
			<li class="nav-item">
				<button class="nav-link" type="button" onclick="scrollToElement('fans')">
					Fans Also Viewed
				</button>
			</li>
		</ul>
		<span class="active-underline"></span>
	</div>
</section>

<section>
	<div class="container">
		<div class="tab-pane-fade-show section-performer-content" id="default">
			<div class="row mt-3 gap-5">
				<div class="col-sm-12 col-md-8">
					<div class="mb-3 mb-md-4 mb-lg-4">
						<div class="d-flex justify-content-between align-items-center results-header">

							<!-- Left -->
							<div class="results-title">
								<span class="active-indicator"></span>
								<h2>
									<?php echo strtoupper($breadcrumbs[1]['label']); ?> <span class="dot">·</span>
									<span class="count"> <?php echo $count; ?> RESULTS</span>
								</h2>
							</div>

							<!-- Right -->
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
						<div id="eventsSection" class="section-artist-content">
							<?php foreach ($events as $event) {
								$class = 'so-cta-tickets'; ?>
								<div class="performer-event-item">
									<div class="row">
										<div class="performer-event-item-info col-sm-9">
											<h3><?php echo $event['text']['name']; ?></h3>
											<ul class="row ps-0">
												<li class="col-sm-5">
													<span>Venue</span>
													<?php echo $event['venue']['text']['name']; ?>
													<span class="location"><?php echo $event['city']['text']['name']; ?>, <?php echo $event['stateProvince']['text']['abbr']; ?></span>
												</li>
												<li class="col-sm-4">
													<span><?php echo $daynames[$event['date']['weekday'] - 1]; ?></span>
													<?php echo date('F. jS, Y', strtotime($event['date']['date'])); ?>
												</li>
												<li class="col-sm-3">
													<span>Time</span>
													<?php echo $event['date']['text']['time']; ?>
												</li>
											</ul>
										</div>
										<div class="performer-event-item-price col-sm-3">
											<?php if (!empty($event['pricingInfo'])) {
												$class = ''; ?>
												<span>Price From</span>
												<strong><?php echo $event['pricingInfo']['lowPrice']['text']['formatted']; ?></strong>
											<?php } ?>
											<a href="/event.php?id=<?php echo $event['id']; ?>" class="<?php echo $class; ?>">Find Tickets</a>
										</div>
									</div>
								</div>
							<?php } ?>
						</div>
						<?php if ($hasMore): ?>
							<div class="load-more-wrapper text-center mt-5">
								<button id="loadMoreBtn" data-page="<?php echo $page + 1; ?>" data-performer="<?php echo $id; ?>" data-perpage="<?php echo $perPage; ?>" class="btn btn-loadmore w-100">
									Load More <i class="bi bi-box-arrow-in-down fs-4"></i>
								</button>
							</div>
						<?php endif; ?>
					<?php } else { ?>
						<h4 style="padding: 20px;">No <?php echo strtoupper($breadcrumbs[1]['label']); ?> found!</h4>
					<?php } ?>
				</div>
				<div id="secondary" class="sidebar col-sm-12 col-md-4">
					<div class="ad-container mx-auto mx-md-0">
						<div class="ad-card">
							<img
								src="../artists/6233961956292020331.jpg"
								alt="Sponsored advertisement"
								class="ad-image" />
						</div>
					</div>
					<div class="guarantee-card d-flex align-items-center justify-content-between">
						<div class="guarantee" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
							<strong>Shop Tickets Worry Free</strong><br>
							<span>With Our 100% Guarantee</span>
						</div>
						<div class="guarantee-icon">
							<i class="bi bi-shield-check"></i>
						</div>
					</div>
					<div class="sidebar-card">
						<h4><?php echo $performer['text']['name']; ?> Tickets Promo Codes</h4>
						<div class="promo-list">
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
		<div class="tab-pane-fade-show content-section-detail" id="about">
			<div class="row">
				<div class="so-about">
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
		</div>
		<div class="tab-pane-fade-show content-section-detail" id="faqs">
			<h2 class="so-heading mb-3">Frequently Asked Questions</h2>
			<div class="accordion" id="faqAccordion">
				<div class="accordion-item">
					<h2 class="accordion-header" id="headingOne">
						<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
							Are <?php echo $performer['text']['name']; ?> tickets guaranteed?
						</button>
					</h2>
					<div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Yes, all tickets are backed by a 100% guarantee to ensure authenticity and timely delivery.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="headingTwo">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
							Can I use promo codes on tickets?
						</button>
					</h2>
					<div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Promo codes like SAVE5 and SAVE10 can be applied during checkout.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="headingThree">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
							When will I receive my tickets?
						</button>
					</h2>
					<div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Delivery timing depends on the venue and ticket type.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="headingFour">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
							Are tickets mobile friendly?
						</button>
					</h2>
					<div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Most tickets are available for mobile entry.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="headingFive">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
							Can I get a refund if the event is canceled?
						</button>
					</h2>
					<div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Yes, canceled events are eligible for refunds.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
				<div class="accordion-item">
					<h2 class="accordion-header" id="headingSix">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
							Do ticket prices change?
						</button>
					</h2>
					<div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
						<div class="accordion-body">
							Prices may fluctuate based on demand and availability.
							<a href="#" class="read-more-btn">Read more</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane-fade-show content-section-detail" id="fans">
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