<?php 


$params = [];
$filterParts = [];

/*
|--------------------------------------------------------------------------
| GEO FILTER
|--------------------------------------------------------------------------
*/
if (
    isset($_POST['latHeader'], $_POST['lngHeader']) &&
    $_POST['latHeader'] !== '' &&
    $_POST['lngHeader'] !== ''
) {
	$title = 'Current location';
    $lat = floatval($_POST['latHeader']);
    $lng = floatval($_POST['lngHeader']);

    $params['geoFilter'] = sprintf('nearby(%F, %F, 50mi)', $lat, $lng);
}else{
	if(
		isset($_POST['locationInputHeader']) && 
		$_POST['locationInputHeader'] !== ''
	) {
		$title = $_POST['locationInputHeader'];
		$explode = explode(',', $title); 
		if (count($explode) == 2) { 
			$city = trim($explode[0]); 
			$state = trim($explode[1]); 
			$filterParts[] = "city/text/name eq '$city' and stateProvince/text/abbr eq '$state'"; 
		}elseif(count($explode) == 3) {
			$city = trim($explode[1]); 
			$state = trim($explode[2]); 
			$filterParts[] = "city/text/name eq '$city' and stateProvince/text/abbr eq '$state'"; 
		}
	}	
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
	$dateTitle = $_POST['startInputHeader'] . ' - ' . $_POST['endInputHeader'];
    $startTimestamp = strtotime($_POST['startInputHeader']);
    $endTimestamp   = strtotime($_POST['endInputHeader']);

    if ($startTimestamp && $endTimestamp) {

        $startDate = date('Y-m-d', $startTimestamp);
        $endDate   = date('Y-m-d', $endTimestamp);

        $filterParts[] = "date/date ge $startDate and date/date le $endDate";
    }
}

/*
|--------------------------------------------------------------------------
| KEYWORD FILTER
|--------------------------------------------------------------------------
*/
if (
	isset($_POST['keywordType'], $_POST['keywordId']) &&
	$_POST['keywordType'] !== '' &&
    $_POST['keywordId'] !== ''
) {
	$keywordID = (int) $_POST['keywordId'];

    if ($_POST['keywordType'] === 'artist') {

        $params['performerFilter'] = "id eq " . $keywordID;

    } elseif ($_POST['keywordType'] === 'event') {

        $filterParts[] = "id eq " . $keywordID;

    } elseif ($_POST['keywordType'] === 'venue') {

        $filterParts[] = "venue/id eq " . $keywordID;
    }
}else{

	if (
		isset($_POST['keywordHeader']) &&
		$_POST['keywordHeader'] !== ''
	) {
		$keywordTitle = $_POST['keywordHeader'];
		$keywordTitle = ucfirst(strtolower($keywordTitle));
		$params['performerFilter'] = "contains(text/name,'$keywordTitle')";
	}
}

/*
|--------------------------------------------------------------------------
| COMBINE FILTERS
|--------------------------------------------------------------------------
*/
if (!empty($filterParts)) {
    $params['filter'] = implode(' and ', $filterParts);
}

$params['page'] = 1;
$params['perPage'] = 20;
$params['q'] = "*";

include 'header.php'; 

if (empty($params)) {
	echo '<div class="container"><p>Invalid search.</p></div>';
	include 'footer.php';
	exit;
}

$results = getHeaderSearchEvents($params);
$count = $results['totalCount'];
$events = $results['results'];
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
								<h2>
									EVENTS <span class="dot">·</span>
									<span class="count" id="results_count">
										<?php echo (int) $count; ?>
										<?php echo $count > 1 ? 'RESULTS' : 'RESULT'; ?>
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
								No Events found!
							</h4>
						<?php } ?>
					</div>	
					<div class="ad-container-left my-4 mx-auto mx-lg-0 mx-xl-0 mx-xxl-0">
						<img src="<?php echo HOME_URL; ?>/artists/adsens.webp" alt="Sponsored advertisement" class="ad-image-left" />
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
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

	
<?php include 'footer.php'; ?>
