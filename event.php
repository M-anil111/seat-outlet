<?php include 'header.php'; ?>

<style>
  .event-detail {
    background: #f8f9fa;
    border-bottom: 1px solid #e2e2e2;
    padding: 10px 0;
  }

  .event-detail .event-card {
    background: none !important;
    padding: 20px;
    border-radius: 0px !important;
    border: none !important;
    flex-direction: row;
    transition: none !important;
    min-height: auto;
  }

  .event-detail .event-card:hover,
  .event-detail .event-card:focus {
    border-bottom: none !important;
    box-shadow: none !important;
    transition: none !important;
    outline: none !important;
    transform: none !important;
  }

  .event-detail .event-card .common-btn:hover {
    background: #0d6efd !important;
    color: #fff !important;
  }

  /* Date Box */
  .event-detail .date-box {
    background: #e4e9f8;
    border-radius: 6px;
    width: 60px;
    padding: 8px 0;
    line-height: 1.1;
    border: 1px #c3bfbf solid;
    box-shadow: rgba(18, 18, 18, 0.18) 0px 3px 12px 0px;

  }

  .event-detail .date-box .month {
    font-size: 13px;
    font-weight: 700;
    color: #555;
  }

  .event-detail .date-box .date,
  .event-detail .date-box .time {
    font-size: 20px;
    font-weight: 800;
  }
   .event-detail .day-weeks, .event-detail .time-clock {
    font-size: 14px;
    color: #646464;}

    .event-detail .dot {
    font-size: 22px;
    color: #646464;
    font-weight: bold;
    line-height: 0px;
}

  /* Event Info */
  .event-detail .event-title {
    font-size: 18px;
    font-weight: 600;
    color: #000;
    background: rgba(37, 86, 224, 0.1);
    padding: 2px 6px;
    border-radius: 3px;
  }

  .event-detail .event-card .common-btn {
    display: inline-block;
    padding: 8px 14px;
    background: transparent;
    border-radius: 999px;
    border: 1px #0d6efd solid;
    margin-top: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #0d6efd;
    transition: all 0.2s ease;
  }

  .event-detail .event-card .common-btn:hover {
    background: #0d6efd;
    color: #fff;
  }

  .event-detail .event-location {
    font-size: 14px;
    color: #666;
  }

  /* Guarantee */
  .event-detail .guarantee h6 {
    font-weight: 600;
    font-size: 14px;
  }

  .event-detail .guarantee p {
    font-size: 12px;
    color: #666;
    max-width: 420px;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .event-detail .event-card {
      flex-direction: column;
      align-items: flex-start;
    }

    .event-detail .guarantee {
      text-align: left !important;
    }
  }
</style>

<?php
// Sanitize and validate event ID from query string.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
  echo '<div class="container"><p>Invalid event.</p></div>';
  include 'footer.php';
  exit;
}

$event = getTnEventById($id);

if (empty($event) || empty($event['text']['name'])) {
  echo '<div class="container"><p>Event not found.</p></div>';
  include 'footer.php';
  exit;
}

$eventNameSafe = htmlspecialchars($event['text']['name'], ENT_QUOTES, 'UTF-8');
$mapScriptUrl  = 'https://mapwidget3.seatics.com/js?eventId=' . $id . '&websiteConfigId=12498&mobileOptimized=true&includeJQuery=false&containerId=tn-maps&useDarkTheme=false';
$defaultCategory = $event['defaultCategory'];
$subcategory = '';
if (!empty($defaultCategory)) {
  if ($defaultCategory['depth'] == 1) {
    $subcategory = $defaultCategory['text']['name'];
  } else {
    if (!empty($defaultCategory['ancestors'])) {
      foreach ($defaultCategory['ancestors'] as $ancestor) {
        if ($ancestor['depth'] == 1) {
          $subcategory = $ancestor['text']['name'];
          break;
        }
      }
    }
  }
}

$eventDateRaw = $event['date']['date'];
$timestamp    = strtotime($eventDateRaw);
$year = date('Y');
?>


<div class="container-fluid event-detail">
  <div class="event-card d-flex flex-wrap align-items-center justify-content-between">

    <!-- Left Section -->
    <div class="d-flex align-items-baseline">

      <!-- Date Box -->

      <div class="date-box text-center me-3">
        <div class="month"><?php echo strtoupper(date('M', $timestamp)); ?></div>
        <div class="day"><?php echo date('d', $timestamp); ?></div>
        <?php if(date('Y', $timestamp) > $year) { ?>
						<div class="month"><?php echo date('Y', $timestamp); ?></div>
				<?php } ?>
      </div>

      <!-- Event Details -->
      <div class="event-info">
      <div class="d-flex align-items-center gap-2">
												<span class="fw-semibold day-weeks"><?php echo date('D', $timestamp); ?></span>
												<span class="dot">·</span>
												<span class="time-clock"><?php echo $event['date']['text']['time']; ?></span>
											</div>
        <h5 class="event-title mb-1">
          <?php echo $eventNameSafe; ?>
        </h5>
        <p class="event-location mb-2">
          <?php echo $event['venue']['text']['name'] . ', ' . $event['city']['text']['name'] . ', ' . $event['stateProvince']['text']['abbr']; ?> 
        </p>
        <a href="/tickets" class="btn common-btn">Show All Events</a>
      </div>

    </div>

    <!-- Right Section -->
    <div class="guarantee text-md-end mt-3 mt-md-0">
      <h6 class="mb-1">100% Money-Back Guarantee</h6>
      <p class="mb-0 small">
        TicketNetwork is a resale marketplace, not a box office or venue. Prices may be above or below face value. Your seats are together unless otherwise noted. Tickets will be the ones you ordered or better. Refunds for canceled events.
      </p>
    </div>

  </div>
</div>

<div class="hero">
  <div class="hero-content">
    <h1><?php echo $eventNameSafe; ?></h1>
    <h2 id="artist-<?php echo $event['performers'][0]['id']; ?>"><?php echo 'Performer: ' . $event['performers'][0]['name']; ?></h2>
    <h4><?php echo 'Category: ' . $subcategory; ?></h4>
    <div style="display:none;">
      <?php print_r($event); ?>
    </div>
  </div>
</div>

<div id="tn-maps" style="height:500px; margin-top: 50px;"></div>
<script src="<?php echo htmlspecialchars($mapScriptUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
<input type="hidden" id="checkoutUrl" value="checkout.seatoutlet.com">
<script type="text/javascript">
  Seatics.config.checkoutUrl = $("#checkoutUrl").val();
  Seatics.config.enableLegalDisclosureMobile = true;
  Seatics.config.preCheckoutButtonHtml = 'Continue to Payment';
  Seatics.config.buyButtonContentHtml = '<div class="buy-btn">' + 'Buy Now' + '</div>';
  Seatics.config.defaultSort = Seatics.SortOptions.PriceAsc;
  Seatics.config.tgMarkTooltipText = 'We recommend this seller&#039;s tickets.';
  Seatics.config.enableMyList = true;
  Seatics.config.showCents = false;
  Seatics.config.skipPrecheckoutMobile = true;
  Seatics.config.showZoomControls = true;
  Seatics.config.ticketListOnRight = true;
  Seatics.config.legendExpanded = true;
  Seatics.config.skipPrecheckoutDesktop = true;
</script>




<?php include 'footer.php'; ?>