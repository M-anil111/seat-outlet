<?php
require_once 'functions.php';
[$when, $sort, $isFiltered] = listingRequestState();
$maxPrice = soListingMaxPrice();
if ($maxPrice > 0) { $isFiltered = true; }
if ($isFiltered) { $pageRobots = 'noindex, follow'; }
$perPage = 20;
$params = categoryListingParams('', $perPage, 1, $when, $sort, $maxPrice);
$results = tnRequest('/catalog/v2/events/', $params);
$total_count = (int) ($results['totalCount'] ?? 0);
$events = $results['results'] ?? [];
// Upcoming events as an ItemList of Event nodes (the same rows the page lists below).
$pageJsonLdNodes = array_merge($pageJsonLdNodes ?? [], [soEventItemList($events, $pageCanonicalUrl ?? '')]);
include 'header.php';
$faqs = getFaqs('events');
$artistName = 'Live';   // the FAQ copy is shared with performer pages: "FAQs about Live Events"
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
					Buy tickets online for concerts, games, shows and festivals. Compare seats and prices from many sellers, check out securely and get a 100% guarantee on every order.
				</p>
			</div>
		</div>
	</div>
</section>

<?php
soRenderListingPage([
	'tag'      => 'h2',   // the page's h1 is the hero above
	'h1'       => 'EVENTS',
	'total'    => $total_count,
	'basePath' => '/buy-tickets-online',
	'when'     => $when,
	'sort'     => $sort,
	'max'      => $maxPrice,
	'body'     => [
		'events'  => $events,
		'perPage' => $perPage,
		'params'  => $params,
		'empty'   => ['basePath' => '/buy-tickets-online', 'noun' => 'events', 'when' => $when, 'max' => $maxPrice, 'fragment' => soCategoryFragment(''), 'kind' => 'category', 'id' => 0, 'name' => 'Events', 'alts' => soListingHubAlts('/buy-tickets-online')],
	],
	'lead'     => ['source' => 'listing', 'title' => 'Get alerts when new events are added', 'text' => 'One email when new dates go on sale. Unsubscribe any time.', 'button' => 'Notify me', 'interest_type' => 'category', 'interest_id' => 0, 'interest_name' => 'Events', 'names' => true],
	'afterSection' => function () use ($faqs, $artistName) {
		?>
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
						<img src="/images/event-ticket-buying-800.webp" srcset="/images/event-ticket-buying-800.webp 800w, /images/event-ticket-buying.webp 1536w" sizes="(min-width: 992px) 567px, 100vw" alt="About Live Events Across the United States" class="img-about img-fluid rounded" width="567" height="378" loading="lazy" decoding="async" />
					</div>
				</div>				
			</div>
		</div>
		<?php if (!empty($faqs)) { ?>
			<div class="tab-section content-section-detail">
				<h2 class="so-heading fw-bold fs-4 mb-4 text-black">FAQs about <?php echo htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8'); ?> Events</h2>
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
		<?php
	},
]);
?>

<?php soSeoCopy('buy-tickets-online'); ?>
<?php include 'footer.php'; ?>
