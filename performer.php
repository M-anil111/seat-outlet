<?php include 'header.php'; 
$slug = $_GET['slug'];
$parts = explode('-', $slug);
$id = end($parts);
$performer = getTnPerformerById($id);
$eventsResponse = getTnPerformerEvents($id);
$events = $eventsResponse['results'] ?? [];
$daynames = DAY_NAMES;
$sep = '<span class="separator"><strong> &gt; </strong></span>';
$breadcrumbs = buildCategoryBreadcrumb($performer['defaultCategory']);

?>

<!-- Header -->
<div class="hero">
    <div class="hero-content">
        <h1><?php echo $performer['text']['name']; ?> Tickets</h1>
    </div>
</div>

<!-- Main Layout -->
<div class="container main-layout">
	<nav class="breadcrumb">
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
		<?php echo $sep; ?><span class="current"><?php echo $performer['text']['name']; ?></span>
	</nav>
</div>

<section class="section-performer-content">
	<div class="container">
		<div class="row">
			<div id="primary" class="col-sm-12 col-md-8">
				<?php if(!empty($events)) { ?>		
					<?php foreach ($events as $event) { $class = 'so-cta-tickets'; ?>
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
											<span><?php echo $daynames[$event['date']['weekday']-1]; ?></span>
											<?php echo date('F. jS, Y', strtotime($event['date']['date'])); ?>
										</li>
										<li class="col-sm-3">
											<span>Time</span>
											<?php echo $event['date']['text']['time']; ?>
										</li>
									</ul>
								</div>
								<div class="performer-event-item-price col-sm-3">
									<?php if(!empty($event['pricingInfo'])) { $class = ''; ?>
										<span>Price From</span>
										<strong><?php echo $event['pricingInfo']['lowPrice']['text']['formatted']; ?></strong>
									<?php } ?>
									<a href="/event.php?id=<?php echo $event['id']; ?>" class="<?php echo $class; ?>">Get Tickets</a>
								</div>
							</div>
						</div>
					<?php } ?>			
				<?php }else{ ?>
					<h3 style="padding: 20px;">No events found!</h3>
				<?php } ?>
			</div>

			<div id="secondary" class="sidebar col-sm-12 col-md-4">

				<div class="sidebar-card buy-later">
					Adsence Ad by Google
				</div>

				<div class="sidebar-card guarantee">
					<h4>Shop Tickets Worry Free</h4>
					<p>With Our 100% Guarantee</p>
				</div>
		
				<div class="sidebar-card">
					<h4>Promo Codes</h4>
					
					<div class="promo-list">
						<div class="promo">
							<span>5% OFF</span>
							<a href="javascript:void(0)" onclick="copyPromoCode('TAKE5', this)">TAKE5</a>
						</div>
						<div class="promo">
							<span>10% OFF</span>
							<a href="javascript:void(0)" onclick="copyPromoCode('TAKE10', this)">TAKE10</a>
						</div>
					</div>
				</div>
			</div>
		</div>	
	</div>
</section>

<section class="content-section">

	<h2><?php echo $performer['text']['name']; ?> Tickets and Tour Information</h2>

	<p>
		<?php echo $performer['text']['name']; ?> brings a unique blend of trip hop, hip hop, and cinematic soundscapes to live audiences across the country.
		Fans can experience immersive performances at top venues with guaranteed authentic tickets.
	</p>

	<ul>
		<li>Browse upcoming <?php echo $performer['text']['name']; ?> tour dates</li>
		<li>Compare ticket prices from trusted sellers</li>
		<li>Secure seats for popular venues</li>
		<li>Mobile friendly ticket delivery</li>
		<li>Backed by a 100% buyer guarantee</li>
	</ul>

	<p class="one-liner">
		Buy with confidence and enjoy live music the way it was meant to be experienced.
	</p>

		<main id="faq" class="container my-5 py-3">
		<h1 class="mb-5">Frequently Asked Questions</h1>

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
	</main>
	

</section>

	
<?php include 'footer.php'; ?>
