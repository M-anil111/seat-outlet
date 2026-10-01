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
  .event-detail h1.event-title { margin: 0; line-height: 1.4; font-family: inherit; }
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
  $slug  = $_GET['slug'] ?? '';
  $parts = explode('-', (string) $slug);
  $id    = (int) end($parts);
  if ($id <= 0) {
    echo notFoundBlockHtml('Event');
    include 'footer.php';
    exit;
  }
}

$event = getTnEventById($id);

if (tnEntityMissing($event) || empty($event['text']['name'])) {
  echo notFoundBlockHtml('Event');
  include 'footer.php';
  exit;
}

// Sandbox catalog IDs only resolve in the widget against the sandbox website
// config, live IDs against the live one: both must come from the same
// environment (WEBSITE_CONFIG_ID follows BASE_URL in inc/constants.php).
$wcid = WEBSITE_CONFIG_ID;
$mapScriptUrl  = 'https://mapwidget3.seatics.com/js?eventId=' . $id . '&websiteConfigId=' . $wcid . '&mobileOptimized=true&includeJQuery=false&containerId=tn-maps&useDarkTheme=false';
?>

<?php
// Real internal links back into the city/category and artist-city location
// pages, using data already fetched above ($event) - not fabricated. This
// page had no editorial content at all before (it's almost entirely the
// third-party Seatics ticketing widget below), and the .event-detail/
// .event-card/.date-box/etc. CSS above was already fully styled but had no
// matching HTML anywhere in this file to use it - dead styling for a
// section that was apparently never finished.
$eventCategoryPath = $event['defaultCategory']['path'] ?? '';
$eventCityName  = $event['city']['text']['name'] ?? '';
$eventCityId    = $event['city']['id'] ?? null;
$eventCityLabel = trim($eventCityName . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
$eventVenueName = $event['venue']['text']['name'] ?? '';
$eventVenueId   = $event['venue']['id'] ?? null;
$primaryPerformer = $event['performers'][0] ?? null;
$categoryCityPrefix = getCategoryCityLinkPrefix($eventCategoryPath);
$eventTimestamp = !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
?>
<section class="event-detail">
  <div class="container">
    <div class="event-card d-flex align-items-center gap-3 flex-wrap">
      <?php if ($eventTimestamp) { ?>
        <div class="date-box text-center">
          <div class="month"><?php echo htmlspecialchars(strtoupper(date('M', $eventTimestamp)), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="date"><?php echo htmlspecialchars(date('d', $eventTimestamp), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php } ?>
      <div class="flex-grow-1">
        <h1 class="event-title"><?php echo htmlspecialchars($event['text']['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="event-location mt-1">
          <?php if (!empty($eventCityId)) { ?>
            <a href="/<?php echo htmlspecialchars($categoryCityPrefix, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($eventCityLabel, $eventCityId), ENT_QUOTES, 'UTF-8'); ?>">
              More events in <?php echo htmlspecialchars($eventCityLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php } ?>
          <?php if (!empty($eventVenueId)) { ?>
            <?php echo !empty($eventCityId) ? ' &middot; ' : ''; ?>
            <a href="/venue/<?php echo htmlspecialchars(createSlug($eventVenueName, $eventVenueId), ENT_QUOTES, 'UTF-8'); ?>">
              More at <?php echo htmlspecialchars($eventVenueName, ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php } ?>
        </div>
        <?php if (!empty($primaryPerformer['id']) && !empty($primaryPerformer['name']) && !empty($eventCityId)) { ?>
          <a class="common-btn mt-2" href="/artist-city/<?php echo htmlspecialchars(createSlug($primaryPerformer['name'], $primaryPerformer['id']), ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($eventCityLabel, $eventCityId), ENT_QUOTES, 'UTF-8'); ?>">
            More <?php echo htmlspecialchars($primaryPerformer['name'], ENT_QUOTES, 'UTF-8'); ?> tickets in <?php echo htmlspecialchars($eventCityLabel, ENT_QUOTES, 'UTF-8'); ?>
          </a>
        <?php } ?>
      </div>
      <div class="guarantee text-end">
        <h6>Shop Tickets Worry Free</h6>
        <p>Every order is backed by our Buyer Protection Guarantee.</p>
      </div>
    </div>
  </div>
</section>

<div id="tn-maps" class="seatics" style="height: calc(100vh - 50px);width: 100%;"></div>
<div id="so-no-tickets" class="so-no-tickets d-none">
  <div class="container py-5 text-center">
    <h2 class="fw-bold fs-4 mb-2" id="so-no-tickets-title">No tickets are listed for this event right now</h2>
    <p class="text-muted mb-4" id="so-no-tickets-text">Inventory changes hourly as sellers list seats. Try another date, or browse related tickets below.</p>
    <div class="d-flex flex-wrap justify-content-center gap-2">
      <?php if (!empty($primaryPerformer['id']) && !empty($primaryPerformer['name'])) { ?>
        <a class="btn btn-primary" href="/artist/<?php echo htmlspecialchars(createSlug($primaryPerformer['name'], $primaryPerformer['id']), ENT_QUOTES, 'UTF-8'); ?>">All <?php echo htmlspecialchars($primaryPerformer['name'], ENT_QUOTES, 'UTF-8'); ?> dates</a>
      <?php } ?>
      <?php if (!empty($eventCityId)) { ?>
        <a class="btn btn-outline-secondary" href="/<?php echo htmlspecialchars($categoryCityPrefix, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($eventCityLabel, $eventCityId), ENT_QUOTES, 'UTF-8'); ?>">More events in <?php echo htmlspecialchars($eventCityLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
      <?php if (!empty($eventVenueId)) { ?>
        <a class="btn btn-outline-secondary" href="/venue/<?php echo htmlspecialchars(createSlug($eventVenueName, $eventVenueId), ENT_QUOTES, 'UTF-8'); ?>">More at <?php echo htmlspecialchars($eventVenueName, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
    </div>
  </div>
</div>
<script src="<?php echo htmlspecialchars($mapScriptUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
(function () {
  // Seatics MapWidget3 hooks (MapWidget3 Integration Guide v1.4, "Special
  // Functions" and "User Event Tracking"). Everything here is additive: the
  // widget's own checkout hand-off is unchanged.
  if (!window.Seatics || !Seatics.config) return;
  var eventPayload = {
    item_id: '<?php echo (int) $id; ?>',
    item_name: <?php echo json_encode($event['text']['name'] ?? ''); ?>,
    item_category: <?php echo json_encode(strtolower($event['defaultCategory']['ancestors'][0]['text']['name'] ?? $event['defaultCategory']['text']['name'] ?? '')); ?>,
    affiliation: 'Seat Outlet',
    location_id: <?php echo json_encode($eventVenueName); ?>
  };
  function push(name, extra) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(Object.assign({ event: name, ecommerce: { items: [eventPayload] } }, extra || {}));
  }
  <?php if (getenv('TN_CHECKOUT_URL')) { ?>
  Seatics.config.checkoutUrl = <?php echo json_encode(TN_CHECKOUT_URL); ?>;
  <?php } ?>
  // Replaces the widget's bare "Sorry, this event has expired" / "no
  // tickets" text with our own block that keeps the visitor on the site.
  function showFallback(title, text) {
    var box = document.getElementById('so-no-tickets');
    var map = document.getElementById('tn-maps');
    if (!box) return;
    if (title) document.getElementById('so-no-tickets-title').textContent = title;
    if (text) document.getElementById('so-no-tickets-text').textContent = text;
    box.classList.remove('d-none');
    if (map) map.style.display = 'none';
    push('no_inventory', { reason: title });
  }
  Seatics.config.noEventHandler = function () {
    showFallback('This event has already taken place', 'Browse upcoming dates for the same performer, city or venue below.');
  };
  Seatics.config.noTicketsHandler = function () {
    showFallback();
  };
  Seatics.config.onBuyButtonClicked = function () { push('begin_checkout'); };
  if (Seatics.TrackingEvents && Seatics.TrackingEvents.registerEventListener) {
    Seatics.TrackingEvents.registerEventListener(function (type, data) {
      if (type === 'FinishedLoading') {
        push('view_item', { ticket_groups: data && data.numTicketGroups, tickets_available: data && data.numTickets });
        if (data && data.numTicketGroups === 0) showFallback();
      } else if (type === 'BuyButtonClicked') {
        push('select_item', { list_placement: data && data.listPlacement });
      }
    });
  }
})();
</script>


<style>
#sea-seatics-link > img {margin-top: 30px;}
#bannersFiltersDiv > p.sea-affirm-low-as{
text-align: center;
    padding: 12px;
    border: solid 1px #E9EBEC;
}
/*#bannersFiltersDiv > p.sea-affirm-low-as > span.affirm-message.affirm-as-low-as{display: none;}*/
#bannersFiltersDiv > p.sea-affirm-low-as{font-weight: bold;}


#sea-quantity-modal-close:hover,
.sea-show-full-notes-packages:hover {
    background-color: rgba(255,255,255,0);
}
div#ticketsContainer.no-inventory {
position:relative;
}


#legal {
display: none !important;
}
.pdp-blurbtext a {
color: #0077ff;
}
.pdp-blurbtext a:hover {
opacity: .8;
}

#sea-seatics-link::after {
    content: '';
    display: block;
    position: absolute;
    z-index: 9999;
    background-color: #fff;
    left: -5px;
    top: -5px;
    right: -5px;
    bottom: -5px;
    pointer-events:none;
}

#category-menu{
display:none;
}

    section#header-container {
        padding-top: 15px;
        padding-bottom: 15px;
    }

    div#ticketsWrap {
        margin-top: 0;
    }

    header#header-wrapper {
        height: initial;
    }

    section#header-container {
        max-width: 100%;
    }

    #search-container {
        max-width: 500px;
        margin: 0 auto;
    }

    @media only screen and (min-width:992px) {
#modal-overlay {
    top: 70px;
    height: calc(100vh - 70px);
}
div#ticketsContainer {
    height: calc(100vh - 150px);
}
div#ticketsContainer.no-inventory {
height: calc(100vh - 160px);
}
}
    @media only screen and (max-width:991px) {
#sea-urgency-messaging-container-mobile {
pointer-events: none;
}
#sea-urgency-messaging-container-mobile * {
pointer-events:initial;
}
div#ticketsContainer {
    height: auto;
}
        .seatics .event-info-ctn {
            padding: 50px 0 10px;
        }
#event-info-area::before {
    width: 100%;
    height: 40px;
    background: ;
    background-color: #fff;
    content: '';
    z-index: 8;
    display: block;
    top: 0;
    position: absolute;
border-bottom: 1px solid #EAEAEA;
}
#header-logo .mobile {
position:fixed;
top:12px;
left:0;
right:0;
margin: 0 auto;
z-index: 1001;
}
.event-info-col.event-info-left-col {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
        .showMap #map-shower {
  color:#4a4a4a;
 }
div#siteTopContent {
    display: block;
}
#search-wrap, #mobile-menu-container, #header-logo .desktop, #header-container::after {
    display: none !important;
}

div#modal-overlay {
    top: 0;
    height: 100vh;
}
    }
    @media only screen and (min-width:769px) {
.inventory-modal .more-btn:hover {
    color: white;
}
.modal-back-btn:hover {
    color: #0077ff;
}
}




    /* FROM HYBRIS TN */

    #js-phone-number {
        visibility: hidden !important;
    }

    header > div.header-content > div.content-container > div.header-promo > div.yCmsComponent {
        visibility: hidden !important;
    }

        header > div.header-content > div.content-container > div.header-promo > div.yCmsComponent > div.content {
            visibility: hidden !important;
        }

            header > div.header-content > div.content-container > div.header-promo > div.yCmsComponent > div.content > div {
                visibility: hidden !important;
                display: none !important;
            }

    #event-info-guarantee-close {
        color: #aaaaaa;
        float: right;
        font-size: 18px;
        font-weight: bold;
        line-height: 20px;
        border: none;
        background: transparent;
        background-color: transparent;
    }

    #event-info-guarantee > div:nth-child(4) > ul {
        list-style-type: disc;
    }
    /* ----------- QTY MODAL STYLING OVERRIDES ----------- */

    /* Qty Modal & Content */
    #sea-quantity-modal.sea-quantity-modal {
        width: 350px !important;
    }

    #sea-quantity-modal > .sea-qty-modal-content {
        padding: 13px 0 13px 0 !important;
    }

    /* Qty Modal Header */
    #sea-quantity-modal.sea-qty-modal > .sea-qty-modal-content > span,
    #sea-quantity-modal > div > span {
        color: #4a4a4a !important;
        content: 'How many tickets do you want?' !important;
    }

    /* Qty Modal Close Button */
    #sea-quantity-modal-close:hover,
    #sea-quantity-modal-close:focus,
    #sea-quantity-modal-close > .cm-close:hover,
    #sea-quantity-modal-close > .cm-close:focus {
        opacity: 0.7 !important;
        outline: none !important;
    }

    /* Qty Modal Filter Styling */
    #sea-quantity-modal-options > label.sea-btn.btn-default.sea-quantity-modal-option {
        border: 2px solid #ff5566 !important;
        background-color: #fff !important;
        color: #ff5566 !important;
    }

        #sea-quantity-modal-options > label.sea-btn.btn-default.sea-quantity-modal-option.sea-active {
            background-color: #ff5566 !important;
            color: #fff !important;
        }

        #sea-quantity-modal-options > label.sea-btn.btn-default.sea-quantity-modal-option:hover,
        #sea-quantity-modal-options > label.sea-btn.btn-default.sea-quantity-modal-option:focus {
            background-color: #ff5566 !important;
            color: #fff !important;
            opacity: 0.6 !important;
        }

    /* Qty Modal Find Button */
    #sea-quantity-modal-skip.sea-quantity-modal-skip {
        border: 2px solid #fff !important;
        background-color: #ff5566 !important;
        border-radius: 5px !important;
        color: #fff !important;
        font-weight: 700 !important;
        padding: 5px 10px !important;
        width: 100% !important;
        margin-top: 25px !important;
    }

        #sea-quantity-modal-skip.sea-quantity-modal-skip:hover,
        #sea-quantity-modal-skip.sea-quantity-modal-skip:focus {
            opacity: 0.6 !important;
        }

    /* Qty Modal Media Queries */
    @media screen and (max-aspect-ratio: 13/9) and (max-width: 991px) {
        #sea-quantity-modal.sea-quantity-modal {
            /* width: 300px !important; */
            /*margin: -112.5px 0 0 -150px/* /*uncomment me if we go back to the old code*/
        }
    }

    /* ----------- DISCLAIMER COLUMN OVERRIDES ----------- */

    #event-info-right-col {
        max-width: 405px !important;
    }

    .grey-text {
        font-size: 13px !important;
    }

    #moreDeliverycontent > ul {
        list-style: disc !important;
    }

    /* ---------- SELLER HIGHLIGHTING OVERRIDES ---------- */

    #content-area > .sea-highlighted-row {
        /* animation: none !important; */
        animation: sea-bgFlash 3s 3 !important;
        animation-duration: 3s !important;
        animation-iteration-count: 3 !important;
    }

        #content-area > .sea-highlighted-row .sea-superhighlight-text {
            animation: sea-colorChange 3s 3 !important;
            animation-duration: 3s !important;
            animation-iteration-count: 3 !important;
        }

    /* ---------- MAPS FILTER STYLING OVERRIDES ---------- */

    #sea-list-3d-vfs,
    #sea-3d-interactive-thumbnail {
        display: none !important;
    }

    /* Filter Container */
    #sea-filterCard-parent {
        border: 1px solid #0077ff;
    }

    /* Clear Filters */
    #sea-filterCardClearFilter {
        color: #0077ff !important;
        /*font-family: 'TTNorms-Regular', sans-serif !important;*/
        font-size: 14px !important;
        font-weight: 600 !important;
    }

    /* Filter styling */
    #sea-inventory-filtersBtncnt {
        background-color: #ff5566 !important;
        color: #fff !important;
    }

    .cm-down-arrow.sea-angle {
        color: #fff !important;
    }

    .btn.qty-filter-opt-label-js.active,
    .sea-qty-filter-any.btn.qty-filter-opt-label-js.active {
        background-color: #ff5566 !important;
        border: 1px solid #ff5566 !important;
    }

        .btn.qty-filter-opt-label-js:hover,
        .sea-qty-filter-any.btn.qty-filter-opt-label-js.active:hover {
            background-color: #ff5566 !important;
            border: 1px solid #ff5566 !important;
            opacity: 0.7 !important;
        }

    .sea-filterCard-parent #sea-filterCard-submit-ctn #sea-filterCard-submit-btn {
        background-color: #0077ff !important;
        color: white !important;
    }

        .sea-filterCard-parent #sea-filterCard-submit-ctn #sea-filterCard-submit-btn:hover {
            background-color: #0077ff !important;
            opacity: 0.7 !important;
        }

    .sea-filterCard-parent #sea-filterCard-wrapper .switch.active .slider:before {
        background-color: #ff5566 !important;
    }

    .sea-filterCard-parent #sea-filterCard-wrapper .switch .slider[disabled]:before {
        background-color: #8e8e93 !important;
        transform: translateX(31px);
    }

    .sort-opt-label.btn.sort-option-js.active,
    .sort-cnt .btn.active .sort-opt-check:before {
        color: #ff5566 !important;
    }

    .sort-cnt .btn.active .sort-opt-check {
        border: 1px solid #ff5566 !important;
    }

        .sort-cnt .btn.active .sort-opt-check:before {
            background: #ff5566 !important;
            color: #ff5566 !important;
        }

    #sort-type-label,
    #sea-sort-type-items > .sort-opt-label.btn.sort-option-js {
        /*font-family: 'TTNorms-Regular', sans-serif !important;*/
        font-size: 14px !important;
    }

        .filters-type-label,
        .filters-price-input-text,
        #sort-type-label,
        #sea-sort-type-items > .sort-opt-label.btn.sort-option-js.active {
            font-weight: 600 !important;
        }

    #sea-inventory-slider-with-filterBtn > div.sea-inventory-slider.slick-initialized.slick-slider > div.slick-list.draggable > div.slick-track > label.btn.sea-ticket-type-option.slick-slide.slick-active > span.filter-tg-type-text {
        font-weight: normal !important;
    }

    /* Min-Max input field */
    #price-filter-min,
    #price-filter-max,
    input[type=tel] {
        color: #4a4a4a !important;
    }

    /* Filter menu padding */
    .dropdown-list.dropdown-list-js > li.dropdown-list-option-js {
        padding-bottom: 0px !important;
    }

    /* Spacing Adjustments */
    #sea-filterCard-wrapper > div.sea-filterCard-qtyCnt.sea-filterCardSection > fieldset > legend.filters-type-label.sea-filter-text {
        padding: 10px 0px !important;
    }

    #sea-filterCard-wrapper > div.sea-filterCard-sortByCnt.sea-filterCardSection {
        margin-top: -10px !important;
    }

    #sea-filterCard-deliveryTypeCnt > ul > li {
        margin-bottom: 8px !important;
    }

    /* Filter Toggle Resizing */
    .sea-filterCard-parent #sea-filterCard-wrapper .switch .slider {
        height: 26px !important;
    }

        .sea-filterCard-parent #sea-filterCard-wrapper .switch .slider:before {
            bottom: 2px !important;
            height: 20px !important;
            left: 3px !important;
            width: 20px !important;
        }

    .sea-filterCard-parent #sea-filterCard-wrapper .switch.active .slider:before {
        bottom: 2px !important;
        height: 20px !important;
        left: 12px !important;
        width: 20px !important;
    }

    .sea-filterCard-parent #sea-filterCard-wrapper .switch .filter-tg-type-text {
        padding: 0 0 10px 0 !important;
        font-weight: 700 !important;
    }

    /* Package Filter - when applicable */
    .sea-ticket-row-note {
        /* white-space: normal !important; */
    }

    /* ------------- PRECHECKOUT STYLING OVERRIDES ------------- */

    /* Filter Container */
    #precheckout-parent {
        border: 1px solid #0077ff;
    }

    /* Section and Row */
    #pre-checkout-tg-info-section,
    #pre-checkout-tg-info-row {
        font-size: 14px !important;
        line-height: 16px !important;
        text-align: left !important;
        bottom: 0px !important;
    }

    /* Primary CTA */
    #pre-checkout-price-cta {
        background: #0077ff !important;
        color: white !important;
    }

        #pre-checkout-price-cta:hover {
            opacity: 0.7 !important;
        }

    /* Quantity Filters Header */
    #sea-pre-checkout-wrapper > .pre-checkout-left > .sea-quantity-container > h4 {
        margin-top: 10px !important;
        margin-bottom: 5px !important;
    }

    /* Quantity Filters */
    #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide,
    #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide.slick-active {
        background: #fff !important;
        border: solid 1px #d2d2d2 !important;
        color: #333 !important;
        font-weight: 700 !important;
    }

        #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide:hover,
        #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide.slick-active:hover {
            background: #ff5566 !important;
            border: solid 1px #ff5566 !important;
            color: #fff !important;
            font-weight: 700 !important;
            opacity: 0.5 !important;
        }

    #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .sea-disabled.sea-disabled-js.slick-slide,
    #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .sea-disabled.sea-disabled-js.slick-slide.slick-active {
        opacity: 0.25 !important;
    }

    #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide.sea-selected,
    #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide.slick-active.sea-selected {
        background: #ff5566 !important;
        border: solid 1px #ff5566 !important;
        color: #fff !important;
        font-weight: 700 !important;
    }

        #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide.sea-selected:hover,
        #sea-precheckout-qty-select > .slick-list.draggable > .slick-track > .slick-slide.slick-active.sea-selected:hover {
            opacity: 0.7 !important;
        }

    /* Quantity Slider Next/Previous */
    .sea-quantity-items > button.slick-prev:hover,
    .sea-quantity-items > button.slick-next:hover {
        color: #ff5566 !important;
    }

    .sea-quantity-items > button.slick-prev.slick-disabled,
    .sea-quantity-items > button.slick-next.slick-disabled {
        color: #e4e4e4 !important;
        cursor: default !important;
    }

    /* Price */
    #pre-checkout-price-text-ctn {
        font-size: 13px !important;
    }

    #pre-checkout-price-amt {
        font-size: 16px !important;
    }

    /* Delivery Method */
    #pre-checkout-delivery-help {
        float: none !important;
    }

    #pre-checkout-delivery-description > .pre-checkout-delivery-desc {
        margin: 0px !important;
    }

    #pre-checkout-delivery-description > div.pre-checkout-delivery-desc > span.pre-checkout-delivery-label {
        font-weight: normal !important;
    }

    .sea-deliv-type-icon:before,
    .cm-instant-download:before,
    .cm-e-tickets:before,
    .cm-mobile-delivery:before {
        margin: 0 0 0 2px !important;
    }

    /* Notes */
    #moreDeliverycontent > ul > li {
        list-style: disc !important;
        padding-bottom: 5px !important;
    }

    #moreDeliverycontent {
        padding-bottom: 0px !important;
    }




    /* TN RESPONSIVE FILE */

.icon {
    display: inline-block;
    vertical-align: middle;
    background-image: url('//ticketnetwork.s3.amazonaws.com/images/icons-sprite.png');
    background-repeat: no-repeat;
}

    .icon.arrow-right {
        width: 12px;
        height: 22px;
        background-position: -10px 0px;
    }

    .icon.arrow-left {
        width: 12px;
        height: 22px;
        background-position: -45px 0px;
    }

    .icon.arrow-down {
        width: 22px;
        height: 12px;
        background-position: -75px -5px;
    }

    .icon.arrow-calendar {
        width: 20px;
        height: 20px;
        background-position: -114px -1px;
    }

        .icon.arrow-calendar:hover,
        a:hover .icon.arrow-calendar,
        .btn:hover .icon.arrow-calendar {
            background-position: -150px -1px;
        }

    .icon.zoom {
        width: 22px;
        height: 22px;
        background-position: -5px -35px;
    }

        .icon.zoom:hover,
        a:hover .icon.zoom,
        .btn:hover .icon.zoom {
            background-position: -42px -35px;
        }

    .icon.zoom-white {
        width: 22px;
        height: 22px;
        background-position: -115px -35px;
    }

        .icon.zoom-white:hover,
        a:hover .icon.zoom-white,
        .btn:hover .icon.zoom-white {
            background-position: -77px -35px;
        }

    .icon.guarantee {
        width: 32px;
        height: 33px;
        background-position: -146px -29px;
    }

    .icon.star-full {
        width: 19px;
        height: 20px;
        background-position: -7px -69px;
    }

    .icon.star-half {
        width: 19px;
        height: 20px;
        background-position: -44px -69px;
    }

    .icon.star-empty {
        width: 19px;
        height: 20px;
        background-position: -80px -69px;
    }

    .icon.hamburger {
        width: 24px;
        height: 18px;
        background-position: -150px -70px;
    }

        .icon.hamburger:hover,
        a:hover .icon.hamburger,
        .btn:hover .icon.hamburger {
            background-position: -114px -70px;
        }

    .icon.twitter {
        width: 34px;
        height: 34px;
        background-position: -38px -98px;
    }

        .icon.twitter:hover,
        a:hover .icon.twitter,
        .btn:hover .icon.twitter {
            background-position: 1px -98px;
        }

    .icon.google {
        width: 34px;
        height: 34px;
        background-position: -39px -136px;
    }

        .icon.google:hover,
        a:hover .icon.google,
        .btn:hover .icon.google {
            background-position: 0px -136px;
        }

    .icon.facebook {
        width: 34px;
        height: 34px;
        background-position: -39px -174px;
    }

        .icon.facebook:hover,
        a:hover .icon.facebook,
        .btn:hover .icon.facebook {
            background-position: 0px -174px;
        }

    .icon.phone {
        width: 17px;
        height: 20px;
        background-position: -83px -106px;
    }

        .icon.phone:hover,
        a:hover .icon.phone,
        .btn:hover .icon.phone,
        a:focus .icon.phone,
        a:active .icon.phone {
            background-position: -119px -106px;
        }

    .icon.close {
        width: 22px;
        height: 22px;
        background-position: -80px -142px;
    }

        .icon.close:hover,
        a:hover .icon.close,
        .btn:hover .icon.close {
            background-position: -117px -142px;
        }

    .icon.close-white {
        width: 22px;
        height: 22px;
        background-position: -151px -142px;
    }

        .icon.close-white:hover,
        a:hover .icon.close-white,
        .btn:hover .icon.close-white {
            background-position: -117px -142px;
        }

    .icon.location {
        min-width: 18px;
        width: 18px;
        height: 22px;
        background-position: -83px -180px;
    }

        .icon.location:hover,
        a:hover .icon.location,
        .btn:hover .icon.location {
            background-position: -120px -180px;
        }

    .icon.arrow-small {
        min-width: 13px;
        width: 13px;
        height: 10px;
        background-position: -156px -107px;
    }

    .icon.arrow-right-small {
        width: 10px;
        height: 14px;
        background-position: -156px -184px;
    }
.btn {
    border: 0;
    min-width: 130px;
    min-height: 40px;
    line-height: 1em;
    padding: 10px;
    display: inline-block;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    font-weight: normal !important;
    font-family: 'TTNorms-Bold', sans-serif;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
    font-size: 16px;
}

    .btn:hover {
        text-decoration: none;
    }

    .btn.btn-primary,
    .btn.btn-pink {
        border: 2px;
        border-style: solid;
        border-color: #ff5566;
        background-color: #ffffff;
        color: #ff5566;
    }

        .btn.btn-primary:not(.disabled):hover,
        .btn.btn-pink:not(.disabled):hover {
            background-color: #ff5566;
            color: #ffffff;
        }

    .btn.btn-secondary,
    .btn.btn-blue {
        border: 2px;
        border-style: solid;
        border-color: #0077ff;
        background-color: #0077ff;
        color: #ffffff;
    }

        .btn.btn-secondary:not(.disabled):hover,
        .btn.btn-blue:not(.disabled):hover {
            background-color: #ffffff;
            color: #0077ff;
        }

    .btn.btn-white {
        border: 2px;
        border-style: solid;
        border-color: #ffffff;
        color: #ffffff;
    }

        .btn.btn-white.disabled,
        .btn.btn-white:hover {
            background-color: #ffffff;
            color: #4a4a4a;
        }

    .btn.btn-block {
        display: block;
    }

    .btn.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn.btn-small {
        min-height: auto;
        min-width: auto;
        padding: 4px 10px;
        font-size: 14px;
    }
    .seatics {
        /*font-family: 'TTNorms-Regular', sans-serif;*/
    }

       /* .seatics .map-list-ctn {
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }*/
    .map-list-ctn{
        padding: 20px 3px 0px 3px;
    }
        .seatics .event-info-ctn {
            background-color: #f9f9f9;
            border-bottom: 1px solid #e2e2e2;
        }

    .seatics .event-info-ctn .event-info-inner-ctn {
        /*max-width: 1200px;
        margin-left: auto;
        margin-right: auto;*/
        display: flex;
        display: -ms-flexbox;
    }

        .seatics .event-info-ctn .event-info-inner-ctn > div {
            float: none;
            flex: 1 1 auto;
            -ms-flex-positive: 1;
            -ms-flex-negative: 1;
            -ms-flex-preferred-size: auto;
        }

            .seatics .event-info-ctn .event-info-inner-ctn > div.event-info-date-ctn {
                flex: 0 0 auto;
                -ms-flex-positive: 0;
                -ms-flex-negative: 0;
                -ms-flex-preferred-size: auto;
            }

    .seatics .event-info-ctn .event-info-date {
        border: 0;
        text-align: center;
        text-transform: uppercase;
        padding: 15px 15px 15px 25px;
        line-height: 1.2em;
        width: auto;
        border-right: 1px solid #e2e2e2;
    }

        .seatics .event-info-ctn .event-info-date .event-info-date-day {
            background: transparent;
            /*font-weight: normal !important;
            /*font-family: 'TTNorms-Bold', sans-serif;*/
            font-size: 16px;
        }

        .seatics .event-info-ctn .event-info-date .event-info-date-time,
        .seatics .event-info-ctn .event-info-date .event-info-date-date {
            font-weight: normal;
            padding: 3px 0 0;
            color: #808080;
            font-size: 12px;
        }

    .seatics .event-info-place span.cm-location {
        display: none;
    }

    .seatics .event-info-name,
    .seatics .event-info-place {
        /*font-family: 'TTNorms-Bold', sans-serif;*/
        color: #4a4a4a;
        font-weight: normal;
    }
 .seatics .event-info-ctn .event-info-date .event-info-date-day,
.seatics .event-info-name {
font-weight:600;
}

    @media only screen and (min-width:1025px) {
        .seatics .event-info-name,
        .seatics .event-info-place {
            font-size: 18px;
        }
    }

    .seatics .event-info-place {
        /*font-family: 'TTNorms-Regular', sans-serif;*/
    }

    .seatics .event-info-right-col {
        padding: 10px 0 10px 10px;
        max-width: 250px;
        width: auto;
        min-width: auto;
    }

    .seatics .event-info-left-col {
        min-width: 65px;
    }

    .seatics .desktop-back-btn {
        margin-top: 5px;
    }

    @media only screen and (max-width:991px) {
        .seatics .desktop-back-btn {
            display: none;
        }
    }

    .seatics .mobile-back-btn {
width: 100%;
height: 100%;
flex-direction: row;
justify-content: center;
align-items: center;
display:flex;
        padding: 8px 10px;
        white-space: nowrap;
        font-weight: normal !important;
        /*font-family: 'TTNorms-Bold', sans-serif;*/
        color: #4a4a4a;
    }

        .seatics .mobile-back-btn .arrow-right-small {
margin-right: 6px;
            margin-top: -2px;
            -webkit-transform: rotate(180deg);
            -moz-transform: rotate(180deg);
            -ms-transform: rotate(180deg);
            -o-transform: rotate(180deg);
            transform: rotate(180deg);
        }

    .seatics .btn {
        min-width: 0;
        min-height: 0;
    }

        .seatics .btn.btn-blue {
            padding: 5px;
            font-size: 14px;
        }

    .seatics .hideMapFull .map-Fully-Hidden label {
        color: #ffffff;
    }

    .pdp-blurbtext {
        padding: 0 20px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        font-weight: normal !important;
font-size: 18px;
        /*font-family: 'TTNorms-Bold', sans-serif;*/
    }

    @media only screen and (max-width:991px) {
        .pdp-blurbtext {
            display: none;
        }
    }

    #event-info-guarantee-close {
        color: #aaaaaa;
        float: right;
        font-size: 18px;
        font-weight: bold;
        line-height: 20px;
    }

    #event-info-guarantee > div:nth-child(4) > ul {
        list-style-type: disc;
    }

    #moreDeliverycontent > ul {
        list-style: disc;
    }

    #event-info-right-col overrides additions #event-info-right-col {
        min-width: 412px;
    }

    .grey-text {
        font-size: 13px;
    }

    #sea-filterCard-parent {
        border: 1px solid #0077ff;
    }

    #sea-filterCardClearFilter {
        color: #0077ff;
        /*font-family: 'TTNorms-Regular', sans-serif;*/
        font-size: 14px;
        font-weight: 700;
    }

    .sea-inventory-child,
    .cm-down-arrow.sea-angle {
        color: #fff;
        /*font-weight: 700;*/
    }

    #sort-type-label,
    #sea-sort-type-items > .sort-opt-label.btn.sort-option-js,
    .sea-delivery-type-option.switch > .filter-tg-type-text {
        /*font-family: 'TTNorms-Regular', sans-serif;*/
        font-size: 14px;
    }

        .filters-type-label,
        .filters-price-input-text,
        #sort-type-label,
        #sea-sort-type-items > .sort-opt-label.btn.sort-option-js.active,
        .sea-delivery-type-option.switch > .filter-tg-type-text {
            font-weight: 700;
        }

    #sea-inventory-filtersBtncnt,
    #sea-filterCard-submit-btn,
    .btn.qty-filter-opt-label-js.active,
    .sea-qty-filter-any.btn.qty-filter-opt-label-js.active,
    .sort-cnt > .btn.active > .sort-opt-check:before {
        background: #29c142;
    }

        #sea-filterCard-submit-btn:hover,
        .btn.qty-filter-opt-label-js:hover,
        .sea-qty-filter-any.btn.qty-filter-opt-label-js.active:hover {
            opacity: 0.7;
        }

    .sort-opt-label.btn.sort-option-js.active,
    .sort-cnt > .btn.active > .sort-opt-check:before {
        color: #29c142;
    }

    .sort-cnt > .btn.active > .sort-opt-check {
        border: 1px solid #29c142;
    }

    #price-filter-min,
    #price-filter-max {
        color: #4a4a4a;
    }

    .dropdown-list.dropdown-list-js > li.dropdown-list-option-js {
        padding-bottom: 0px;
    }

    .sea-marketing-html-map {
        cursor: pointer;
        display: none;
        left: 3px;
        position: absolute;
        top: 30px;
        z-index: 3;
    }

    .sea-marketing-header {
        right: 453px;
        z-index: 1;
    }

    .sea-marketing-header,
    .sea-marketing-html-map {
        color: #808080;
        background-color: #ffffff;
        border: solid 2px #0077ff;
        border-radius: 4px;
        cursor: pointer;
        display: none;
        font-size: 10px;
        position: static;
        width: 180px;
        float: right;
        position: relative;
        right: 0;
        opacity: 1;
        padding: 10px;
        top: 10px;
    }

        .sea-marketing-header .sea-marketing-header-label,
        .sea-marketing-html-map .sea-marketing-header-label {
            display: block;
            font-size: 10px;
            text-align: center;
            margin-bottom: 5px;
        }

            .sea-marketing-header .sea-marketing-header-label strong,
            .sea-marketing-html-map .sea-marketing-header-label strong {
                display: block;
                color: #0077ff;
                font-weight: normal !important;
                /*font-family: 'TTNorms-Bold', sans-serif;*/
                font-size: 14px;
                margin-bottom: 5px;
            }

        .sea-marketing-header .sea-marketing-header-label-show,
        .sea-marketing-html-map .sea-marketing-header-label-show {
            color: #0077ff;
            display: block;
            text-align: center;
        }

        .sea-marketing-header .sea-marketing-header-close,
        .sea-marketing-html-map .sea-marketing-header-close {
            cursor: pointer;
            position: absolute;
            right: 0;
            top: 0;
        }

    .price-discounted-strike {
        text-decoration: line-through;
        -webkit-text-decoration-line: line-throug;
        color: #f57777;
    }
</style>

<?php include 'footer.php'; ?>