<?php include 'header.php'; ?>

<style>
  /* Event header card (reference design): date tile | name, venue, links | tickets CTA | guarantee */
  .event-detail {
    background: #f5f7fb;
    padding: 28px 0;
  }
  .event-detail > .container { max-width: 1640px; }
  .ev-card {
    display: grid;
    grid-template-columns: auto minmax(0, 1.5fr) auto minmax(0, 1fr);
    grid-template-areas: "date info cta guarantee";
    align-items: center;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(17, 24, 39, .06);
    padding: 24px 28px;
  }
  .ev-date, .ev-info, .ev-cta, .ev-guarantee { min-width: 0; }
  .ev-date { grid-area: date; }
  .ev-info { grid-area: info; padding: 0 32px; border-left: 1px solid #e8ecf3; align-self: stretch; display: flex; flex-direction: column; justify-content: center; margin-left: 24px; }
  .ev-cta { grid-area: cta; padding: 0 32px; border-left: 1px solid #e8ecf3; align-self: stretch; display: flex; align-items: center; }
  .ev-guarantee { grid-area: guarantee; padding-left: 32px; border-left: 1px solid #e8ecf3; align-self: stretch; display: flex; flex-direction: column; justify-content: center; }

  /* Date tile */
  .ev-date {
    background: #eef3fd;
    border-radius: 12px;
    text-align: center;
    padding: 14px 18px 12px;
    min-width: 132px;
  }
  .ev-date-day { color: #2556e0; font-weight: 700; font-size: 22px; line-height: 1.2; text-transform: uppercase; }
  .ev-date-num { color: #111827; font-weight: 800; font-size: 40px; line-height: 1.1; }
  .ev-date-month { color: #374151; font-size: 15px; font-weight: 500; text-transform: uppercase; }
  .ev-date-time { color: #4b5563; font-size: 15px; border-top: 1px solid #dbe3f3; margin-top: 10px; padding-top: 10px; display: flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap; }
  .ev-date-time svg { width: 16px; height: 16px; flex: 0 0 auto; }

  /* Name, venue, links */
  .event-detail h1.ev-title,
.event-detail h2.h1.ev-title { font-size: 30px; font-weight: 800; color: #111827; line-height: 1.25; margin: 0 0 10px; font-family: inherit; overflow-wrap: anywhere; }
  .ev-venue { display: flex; align-items: flex-start; gap: 8px; color: #4b5563; font-size: 18px; line-height: 1.4; margin: 0 0 14px; }
  .ev-venue .bi { font-size: 18px; margin-top: 2px; }
  .ev-venue a { color: inherit; text-decoration: none; }
  .ev-venue a:hover { color: #2556e0; text-decoration: underline; }
  .ev-link { color: #2556e0; font-size: 18px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: flex-start; gap: 8px; }
  .ev-link:hover { color: #1a3fa8; text-decoration: underline; }
  .ev-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
  .ev-action { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border: 1px solid #c9d3e6; border-radius: 999px; background: #fff; color: #1f3a8a; font-size: 14px; font-weight: 600; cursor: pointer; line-height: 1.2; }
  .ev-action:hover { background: #eef3ff; border-color: #2556e0; }
  .ev-action:focus-visible { outline: 3px solid #2556e0; outline-offset: 2px; }
  .ev-action .bi { font-size: 16px; }
  .ev-link .bi { font-size: 20px; line-height: 1.2; }

  /* Tickets CTA box */
  .ev-cta-box { border: 1.5px solid #dbe3f3; border-radius: 12px; padding: 16px 20px; text-align: center; min-width: 200px; }
  .ev-cta-box .bi { color: #2556e0; font-size: 32px; line-height: 1; display: inline-block; margin-bottom: 6px; }
  .ev-cta-label { color: #374151; font-size: 15px; margin-bottom: 10px; }
  .ev-cta-label strong { color: #111827; font-weight: 700; }
  .ev-cta-btn { display: block; border: 2px solid #2556e0; border-radius: 8px; color: #2556e0; font-weight: 700; font-size: 16px; padding: 9px 18px; text-decoration: none; transition: background .2s, color .2s; }
  .ev-cta-btn:hover, .ev-cta-btn:focus { background: #2556e0; color: #fff; }

  /* Guarantee */
  .ev-guarantee-head { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
  .ev-guarantee-head svg { width: 38px; height: 38px; color: #2556e0; flex: 0 0 auto; }
  .ev-guarantee-head h2 { color: #2556e0; font-size: 22px; font-weight: 700; margin: 0; line-height: 1.25; font-family: inherit; }
  .ev-guarantee p { color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0; }

  /* Laptop: guarantee drops to its own row under the card's top row */
  @media (max-width: 1299.98px) {
    .ev-card {
      grid-template-columns: auto minmax(0, 1fr) auto;
      grid-template-areas: "date info cta" "guarantee guarantee guarantee";
      row-gap: 20px;
    }
    .ev-guarantee { padding: 20px 0 0; border-left: 0; border-top: 1px solid #e8ecf3; }
  }
  /* Tablet: date + info, then CTA, then guarantee */
  @media (max-width: 991.98px) {
    .ev-card {
      grid-template-columns: auto minmax(0, 1fr);
      grid-template-areas: "date info" "cta cta" "guarantee guarantee";
      padding: 20px;
    }
    .ev-info { padding: 0 0 0 20px; margin-left: 20px; }
    .ev-cta { padding: 20px 0 0; border-left: 0; border-top: 1px solid #e8ecf3; }
    .ev-cta-box { width: 100%; display: flex; align-items: center; gap: 14px; text-align: left; padding: 12px 16px; }
    .ev-cta-box .bi { margin: 0; }
    .ev-cta-label { margin: 0; flex: 1 1 auto; }
    .ev-cta-btn { flex: 0 0 auto; }
  }
  /* Mobile: everything stacked, date tile becomes a single row */
  @media (max-width: 575.98px) {
    .event-detail { padding: 16px 0; }
    .ev-card { grid-template-columns: minmax(0, 1fr); grid-template-areas: "date" "info" "cta" "guarantee"; padding: 16px; row-gap: 16px; border-radius: 14px; }
    .ev-date { display: flex; align-items: baseline; justify-content: center; flex-wrap: wrap; gap: 4px 8px; padding: 10px 12px; }
    .ev-date-day, .ev-date-month { font-size: 15px; }
    .ev-date-num { font-size: 22px; }
    .ev-date-time { border-top: 0; margin: 0; padding: 0 0 0 10px; border-left: 1px solid #dbe3f3; }
    .ev-info { padding: 0; margin: 0; border-left: 0; }
    .event-detail h1.ev-title,
.event-detail h2.h1.ev-title { font-size: 22px; }
    .ev-venue { font-size: 16px; }
    .ev-link { font-size: 15px; }
    .ev-cta { padding: 16px 0 0; }
    .ev-cta-box { flex-wrap: wrap; }
    .ev-cta-btn { width: 100%; text-align: center; }
    .ev-guarantee { padding-top: 16px; }
    .ev-guarantee-head h2 { font-size: 19px; }
    .ev-guarantee-head svg { width: 32px; height: 32px; }
  }

  /* This card replaces the widget's own event header (same date, name and
     venue), so it is not shown twice. */
  @media (min-width: 992px) {
    .seatics .event-info-ctn { display: none !important; }
  }
  /* Phones and iPads (< 992px): the widget pins its own header bar to the
     top of the screen (position: fixed, built for pages without a site
     header), which covered this card and its date tile. Keep it in the page
     flow under the card instead, as a slim bar with the widget's date line
     and its "Important Event Information" link. Name and venue are already
     in the card, so only those two are hidden. */
  @media (max-width: 991.98px) {
    .seatics .event-info-ctn {
      position: relative !important;
      top: auto !important;
      z-index: 2 !important;
      padding: 10px 16px !important;
      background: #fff;
      border-bottom: 1px solid #e8ecf3;
      box-shadow: none;
    }
    .seatics #event-info-area::before { display: none !important; }
    .seatics .event-info-ctn .event-info-name,
    .seatics .event-info-ctn .event-info-place,
    .seatics #event-info-right-col { display: none !important; }
    .seatics .event-info-ctn .event-info-left-col:empty,
    .seatics .event-info-ctn .mobile-event-info-right-col:empty { display: none !important; }
    .seatics .event-info-ctn .event-info-details-ctn { float: none; width: 100%; text-align: center; }
    .seatics .event-info-ctn .event-info-date-time-span {
      display: block !important;
      color: #0f1b3d;
      font-size: 14px;
      font-weight: 600;
      white-space: normal;
    }
    .seatics .event-info-ctn .event-info-date-time-span .cm-time { color: #2556e0; margin-right: 4px; }
    .seatics .event-info-ctn .event-info-notes { margin: 4px 0 0; }
    .seatics .event-info-ctn .event-note-popup-trigger { color: #2556e0; font-size: 13px; font-weight: 600; height: auto !important; }
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
  echo tnEntityUnavailable($event) ? unavailableBlockHtml('Event') : notFoundBlockHtml('Event');
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
<?php
$eventVenueParts = [];
if ($eventVenueName !== '') {
  $eventVenueParts[] = $eventVenueId ? ['/venue/' . createSlug($eventVenueName, $eventVenueId), $eventVenueName] : [null, $eventVenueName];
}
if ($eventCityName !== '') {
  $eventVenueParts[] = $eventCityId ? ['/' . $categoryCityPrefix . '/' . createSlug($eventCityLabel, $eventCityId), $eventCityName] : [null, $eventCityName];
}
foreach ([$event['stateProvince']['text']['abbr'] ?? '', ($event['country']['alphaCode'] ?? '') !== 'US' ? ($event['country']['text']['name'] ?? '') : ''] as $part) {
  if ($part !== '') $eventVenueParts[] = [null, $part];
}
$eventTimeText = trim((string) ($event['date']['text']['time'] ?? ''));
if ($eventTimeText === '' && $eventTimestamp) {
  $eventTimeText = date('g:i A', $eventTimestamp);
}
$eventLowPrice = $event['pricingInfo']['lowPrice']['text']['formatted'] ?? '';
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
// One related link under the venue: this performer in this city, else this city.
$eventInfoLink = null;
if (!empty($primaryPerformer['id']) && !empty($primaryPerformer['name']) && !empty($eventCityId)) {
  $eventInfoLink = ['/artist-city/' . createSlug($primaryPerformer['name'], $primaryPerformer['id']) . '/' . createSlug($eventCityLabel, $eventCityId), 'More ' . $primaryPerformer['name'] . ' tickets in ' . $eventCityLabel];
} elseif (!empty($eventCityId)) {
  $eventInfoLink = ['/' . $categoryCityPrefix . '/' . createSlug($eventCityLabel, $eventCityId), 'More events in ' . $eventCityLabel];
}
?>
<section class="event-detail">
  <div class="container">
    <div class="ev-card">
      <?php if ($eventTimestamp) { ?>
        <div class="ev-date">
          <div class="ev-date-day"><?php echo $h(date('D', $eventTimestamp)); ?></div>
          <div class="ev-date-num"><?php echo $h(date('j', $eventTimestamp)); ?></div>
          <div class="ev-date-month"><?php echo $h(date('M Y', $eventTimestamp)); ?></div>
          <?php if ($eventTimeText !== '') { ?>
            <div class="ev-date-time">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/></svg>
              <?php echo $h($eventTimeText); ?>
            </div>
          <?php } ?>
        </div>
      <?php } ?>

      <div class="ev-info"<?php echo $eventTimestamp ? '' : ' style="margin-left:0;padding-left:0;border-left:0"'; ?>>
        <h1 class="ev-title"><?php echo $h($event['text']['name'] ?? ''); ?></h1>
        <?php if ($eventVenueParts) { ?>
          <p class="ev-venue"><i class="bi bi-geo-alt" aria-hidden="true"></i><span><?php
            $out = [];
            foreach ($eventVenueParts as [$href, $label]) {
              $out[] = $href ? '<a href="' . $h($href) . '">' . $h($label) . '</a>' : $h($label);
            }
            echo implode(', ', $out);
          ?></span></p>
        <?php } ?>
        <?php if ($eventInfoLink) { ?>
          <a class="ev-link" href="<?php echo $h($eventInfoLink[0]); ?>"><i class="bi bi-info-circle" aria-hidden="true"></i><span><?php echo $h($eventInfoLink[1]); ?></span></a>
        <?php } ?>
        <div class="ev-actions">
          <?php if ($eventTimestamp) { ?>
            <button type="button" class="ev-action" data-so-ics><i class="bi bi-calendar-plus" aria-hidden="true"></i><span>Add to calendar</span></button>
          <?php } ?>
          <button type="button" class="ev-action" data-so-share><i class="bi bi-share" aria-hidden="true"></i><span>Share</span></button>
        </div>
        <span class="visually-hidden" id="so-action-status" role="status" aria-live="polite"></span>
      </div>

      <div class="ev-cta">
        <div class="ev-cta-box">
          <i class="bi bi-ticket-perforated" aria-hidden="true"></i>
          <div class="ev-cta-label">
            <?php if ($eventLowPrice !== '') { ?>Tickets from <strong><?php echo $h($eventLowPrice); ?></strong><?php } else { ?>Tickets<?php } ?>
          </div>
          <a class="ev-cta-btn" href="#tn-maps">View Tickets</a>
        </div>
      </div>

      <div class="ev-guarantee">
        <div class="ev-guarantee-head">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m2.146 5.146a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793z"/></svg>
          <h2><a href="/worry-free-guarantee" class="text-reset text-decoration-none">100% Worry-Free Guarantee</a></h2>
        </div>
        <p>We are a resale marketplace, not the ticket seller. Prices are set by third-party sellers and may be above or below face value. Your seats are together unless otherwise noted. All prices are in USD.</p>
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
        <a class="so-linkchip" href="/<?php echo htmlspecialchars($categoryCityPrefix, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(createSlug($eventCityLabel, $eventCityId), ENT_QUOTES, 'UTF-8'); ?>">More events in <?php echo htmlspecialchars($eventCityLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
      <?php if (!empty($eventVenueId)) { ?>
        <a class="so-linkchip" href="/venue/<?php echo htmlspecialchars(createSlug($eventVenueName, $eventVenueId), ENT_QUOTES, 'UTF-8'); ?>">More at <?php echo htmlspecialchars($eventVenueName, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
    </div>
  </div>
</div>
<?php
// Facts for js/event-actions.js (calendar file, share, "recently viewed"). Everything here is already on the page.
$soEventSlug = createSlug($event['text']['name'] ?? '', $id);
$soEventData = [
  'id'       => (int) $id,
  'name'     => (string) ($event['text']['name'] ?? ''),
  'slug'     => $soEventSlug,
  'url'      => rtrim(HOME_URL, '/') . '/event/' . $soEventSlug,
  'date'     => (string) ($event['date']['date'] ?? ''),
  'start'    => (string) ($event['date']['datetimeOffset'] ?? ''),
  'allDay'   => (($event['date']['time'] ?? '') === '' || ($event['date']['time'] ?? '') === '00:00:00'),
  'venue'    => (string) $eventVenueName,
  'city'     => (string) $eventCityLabel,
  'tickets'  => (int) ($event['_metadata']['ticketCount'] ?? 0),
];
?>
<script type="application/json" id="so-event-data"><?php echo json_encode($soEventData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?></script>
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
    var cta = document.querySelector('.ev-cta-btn');
    if (cta) cta.setAttribute('href', '#so-no-tickets');
    push('no_inventory', { reason: title });
  }
  Seatics.config.noEventHandler = function () {
    showFallback('This event has already taken place', 'Browse upcoming dates for the same performer, city or venue below.');
  };
  Seatics.config.noTicketsHandler = function () {
    showFallback();
  };
  // TicketNetwork widget features, switched on from the page (verified against the live widget): the "How many tickets?"
  // sheet that opens first, the per-listing value score (stars and a deal label from TicketNetwork's own data), and the
  // widget's own "other customers" urgency messages. Nothing here is written by the site: if the widget has no figure,
  // it shows nothing.
  Seatics.config.enableQuantityModal = true;
  Seatics.config.forceQuantityModalSelection = false;
  Seatics.config.enableValueScore = true;
  Seatics.config.showOtherCustomersUrgencyMessagingMobile = true;
  Seatics.config.showOtherCustomersUrgencyMessagingDesktop = true;
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
color: #2556e0;
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
    color: #2556e0;
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
    /* The quantity sheet ("How many tickets?") is skinned in css/style.css. */

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
        border: 1px solid #2556e0;
    }

    /* Clear Filters */
    #sea-filterCardClearFilter {
        color: #2556e0 !important;
        /*font-family: 'TTNorms-Regular', sans-serif !important;*/
        font-size: 14px !important;
        font-weight: 600 !important;
    }

    /* Filter styling */
    #sea-inventory-filtersBtncnt {
        background-color: #3358e4 !important;
        color: #fff !important;
    }

    .cm-down-arrow.sea-angle {
        color: #fff !important;
    }

    .btn.qty-filter-opt-label-js.active,
    .sea-qty-filter-any.btn.qty-filter-opt-label-js.active {
        background-color: #3358e4 !important;
        border: 1px solid #3358e4 !important;
        color: #fff !important;
    }

        .btn.qty-filter-opt-label-js:hover,
        .sea-qty-filter-any.btn.qty-filter-opt-label-js.active:hover {
            background-color: #3358e4 !important;
            border: 1px solid #3358e4 !important;
            opacity: 0.7 !important;
        }

    .sea-filterCard-parent #sea-filterCard-submit-ctn #sea-filterCard-submit-btn {
        background-color: #2556e0 !important;
        color: white !important;
    }

        .sea-filterCard-parent #sea-filterCard-submit-ctn #sea-filterCard-submit-btn:hover {
            background-color: #2556e0 !important;
            opacity: 0.7 !important;
        }

    .sea-filterCard-parent #sea-filterCard-wrapper .switch.active .slider:before {
        background-color: #3358e4 !important;
    }

    .sea-filterCard-parent #sea-filterCard-wrapper .switch .slider[disabled]:before {
        background-color: #8e8e93 !important;
        transform: translateX(31px);
    }

    .sort-opt-label.btn.sort-option-js.active,
    .sort-cnt .btn.active .sort-opt-check:before {
        color: #3358e4 !important;
    }

    .sort-cnt .btn.active .sort-opt-check {
        border: 1px solid #3358e4 !important;
    }

        .sort-cnt .btn.active .sort-opt-check:before {
            background: #3358e4 !important;
            color: #3358e4 !important;
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
        border: 1px solid #2556e0;
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
        background: #2556e0 !important;
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
            background: #3358e4 !important;
            border: solid 1px #3358e4 !important;
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
        background: #3358e4 !important;
        border: solid 1px #3358e4 !important;
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
        color: #3358e4 !important;
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
        border-color: #3358e4;
        background-color: #ffffff;
        color: #3358e4;
    }

        .btn.btn-primary:not(.disabled):hover,
        .btn.btn-pink:not(.disabled):hover {
            background-color: #3358e4;
            color: #ffffff;
        }

    .btn.btn-secondary,
    .btn.btn-blue {
        border: 2px;
        border-style: solid;
        border-color: #2556e0;
        background-color: #2556e0;
        color: #ffffff;
    }

        .btn.btn-secondary:not(.disabled):hover,
        .btn.btn-blue:not(.disabled):hover {
            background-color: #ffffff;
            color: #2556e0;
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
        border: 1px solid #2556e0;
    }

    #sea-filterCardClearFilter {
        color: #2556e0;
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
        background: #3358e4;
    }

        #sea-filterCard-submit-btn:hover,
        .btn.qty-filter-opt-label-js:hover,
        .sea-qty-filter-any.btn.qty-filter-opt-label-js.active:hover {
            opacity: 0.7;
        }

    .sort-opt-label.btn.sort-option-js.active,
    .sort-cnt > .btn.active > .sort-opt-check:before {
        color: #3358e4;
    }

    .sort-cnt > .btn.active > .sort-opt-check {
        border: 1px solid #3358e4;
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
        border: solid 2px #2556e0;
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
                color: #2556e0;
                font-weight: normal !important;
                /*font-family: 'TTNorms-Bold', sans-serif;*/
                font-size: 14px;
                margin-bottom: 5px;
            }

        .sea-marketing-header .sea-marketing-header-label-show,
        .sea-marketing-html-map .sea-marketing-header-label-show {
            color: #2556e0;
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

    /* ---------- FILTER & SORT DRAWER (Seat Outlet design) ----------
       Restyles the Seatics drawer (#sea-filterCard-parent): white panel,
       #2556E0 accents, navy headings, light dividers, rounded inputs.
       Every selector starts with #tn-maps #sea-filterCard-parent so it
       outranks the older red/green overrides above without editing them.
       Markup and behaviour are the widget's own. */
    #tn-maps #sea-filterCard-parent {
        border: 0 !important;
        border-left: 1px solid #e8ecf3 !important;
        box-shadow: -12px 0 32px rgba(15, 27, 61, .08);
        padding: 20px 24px 0;
        font-family: inherit;
        color: #0f1b3d;
    }
    /* The page-wide .btn rule forces font-weight: normal and a TTNorms font
       the site does not load; drawer buttons use the site font instead. */
    #tn-maps #sea-filterCard-parent .btn,
    #tn-maps #sea-filterCard-parent button,
    #tn-maps #sea-filterCard-parent input { font-family: inherit; }
    #tn-maps #sea-filterCard-parent #sea-filters-back-to-list {
        width: auto;
        line-height: 1;
        margin-bottom: 14px;
    }
    #tn-maps #sea-filterCard-parent #sea-filters-back-to-list .cm-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        padding: 0;
        background: #fff;
        border: 1px solid #e8ecf3;
        box-shadow: none;
        color: #0f1b3d;
        font-size: 15px;
    }
    #tn-maps #sea-filterCard-parent #sea-filters-back-to-list .cm-close:hover { border-color: #2556E0; color: #2556E0; }
    #tn-maps #sea-filterCard-parent .sea-button-padding::after { content: ''; display: table; clear: both; }

    /* Header: title + Clear Filters */
    #tn-maps #sea-filterCard-parent .sea-filterCard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #e8ecf3;
        padding: 0 0 16px;
        margin-bottom: 4px;
    }
    #tn-maps #sea-filterCard-parent .sea-filterCardTitle { font-size: 20px; font-weight: 700; color: #0f1b3d; line-height: 1.3; }
    #tn-maps #sea-filterCard-parent #sea-filterCardClearFilter {
        float: none;
        color: #2556E0 !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        background: none;
        border: 0;
        padding: 0;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCardClearFilter:hover { text-decoration: underline; }

    /* Section labels */
    #tn-maps #sea-filterCard-parent .filters-type-label,
    #tn-maps #sea-filterCard-parent .filters-price-input-text {
        color: #0f1b3d;
        font-size: 15px;
        font-weight: 600 !important;
    }
    #tn-maps #sea-filterCard-parent .filters-type-label { padding: 18px 0 12px !important; }
    #tn-maps #sea-filterCard-parent .sea-filterCard-sortByCnt .filters-type-label { padding-top: 0 !important; }
    #tn-maps #sea-filterCard-parent .filters-price-input-text { padding: 0 0 8px; }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .sea-filterCardSection { margin-bottom: 18px; }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .sea-filterCard-sortByCnt { margin-top: 0 !important; }
    #tn-maps #sea-filterCard-parent .sea-filterCard-separator {
        border: 0;
        border-top: 1px solid #e8ecf3;
        margin: 4px 0 18px;
        opacity: 1;
    }

    /* Quantity: circular options, Any active in blue */
    #tn-maps #sea-filterCard-parent .filters-qty-filter-cnt { display: flex; flex-wrap: wrap; gap: 10px; text-align: left; }
    #tn-maps #sea-filterCard-parent .filters-qty-filter-cnt::after { display: none; }
    #tn-maps #sea-filterCard-parent .filters-qty-filter .sea-btn,
    #tn-maps #sea-filterCard-parent .btn.qty-filter-opt-label-js {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        padding: 0;
        margin: 0;
        border-radius: 50%;
        background: #fff !important;
        border: 1px solid #d6dce8 !important;
        color: #0f1b3d;
        font-size: 15px;
        font-weight: 600 !important;
        opacity: 1 !important;
        transition: border-color .2s, color .2s, background .2s;
    }
    #tn-maps #sea-filterCard-parent .btn.qty-filter-opt-label-js:hover { border-color: #2556E0 !important; color: #2556E0; }
    #tn-maps #sea-filterCard-parent .btn.qty-filter-opt-label-js.active,
    #tn-maps #sea-filterCard-parent .btn.qty-filter-opt-label-js.sea-active {
        background: #2556E0 !important;
        border-color: #2556E0 !important;
        color: #fff;
    }
    #tn-maps #sea-filterCard-parent .filters-qty-filter .sea-btn.disabled { opacity: .4 !important; }

    /* Min / Max price: rounded inputs with a $ prefix */
    #tn-maps #sea-filterCard-parent .filters-price-input-cnt { display: flex; align-items: flex-end; gap: 12px; }
    #tn-maps #sea-filterCard-parent .filters-price-input-min-cnt { position: relative; flex: 1 1 0; width: auto; }
    #tn-maps #sea-filterCard-parent .filters-price-input-min-cnt::after {
        content: '$';
        position: absolute;
        left: 14px;
        bottom: 12px;
        color: #6b7280;
        font-size: 15px;
        line-height: 20px;
        pointer-events: none;
    }
    #tn-maps #sea-filterCard-parent .filters-price-input-min {
        height: 44px;
        border: 1px solid #d6dce8 !important;
        border-radius: 10px;
        color: #0f1b3d !important;
        font-size: 15px;
        text-align: left;
        padding: 0 12px 0 26px;
        transition: border-color .2s, box-shadow .2s;
    }
    #tn-maps #sea-filterCard-parent .filters-price-input-min:focus { border-color: #2556E0 !important; box-shadow: 0 0 0 3px rgba(37, 86, 224, .15); }
    #tn-maps #sea-filterCard-parent .sea-filters-price-divider { flex: 0 0 12px; width: 12px; margin: 0 0 22px; background: #d6dce8; }

    /* Toggles */
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: 28px;
        margin-bottom: 16px !important;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch .filter-tg-type-text {
        padding: 0 !important;
        color: #0f1b3d;
        font-size: 15px !important;
        font-weight: 600 !important;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch .slider {
        position: relative;
        flex: 0 0 48px;
        width: 48px;
        height: 28px !important;
        border: 0;
        border-radius: 999px;
        background: #d6dce8;
        transition: background .2s;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch .slider:before {
        left: 3px !important;
        bottom: 3px !important;
        width: 22px !important;
        height: 22px !important;
        background: #fff !important;
        box-shadow: 0 1px 3px rgba(15, 27, 61, .25);
        transform: none;
        transition: transform .2s;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch.active .slider { background: #2556E0; }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch.active .slider:before { transform: translateX(20px); }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch .slider[disabled] { background: #eef1f6; cursor: not-allowed; }
    #tn-maps #sea-filterCard-parent #sea-filterCard-wrapper .switch .slider[disabled]:before { background: #f8f9fc !important; box-shadow: 0 1px 2px rgba(15, 27, 61, .12); transform: none; }
    #tn-maps #sea-filterCard-parent .sea-ada-accessible-ctn .switch { margin-bottom: 6px !important; }
    #tn-maps #sea-filterCard-parent .switch:has(.slider[disabled]) .filter-tg-type-text { color: #9aa3b5; }
    #tn-maps #sea-filterCard-parent .sea-filter-no-results-text { color: #6b7280; font-size: 13px; line-height: 1.45; }
    #tn-maps #sea-filterCard-parent #sea-filterCard-deliveryTypeCnt > ul > li { margin-bottom: 16px !important; }

    /* Sort by dropdown. Height is left non-!important so the widget can still open it. */
    #tn-maps #sea-filterCard-parent .sort-cnt {
        height: 44px;
        border: 1px solid #d6dce8;
        border-radius: 10px;
        background: #fff;
    }
    #tn-maps #sea-filterCard-parent .sort-cnt-label { display: flex; align-items: center; justify-content: space-between; height: 42px; padding: 0 14px; }
    #tn-maps #sea-filterCard-parent #sort-type-label { color: #0f1b3d; font-size: 15px !important; font-weight: 500 !important; }
    #tn-maps #sea-filterCard-parent .sort-label-arrow { color: #6b7280; }
    #tn-maps #sea-filterCard-parent .sort-cnt .sea-btn { padding: 10px 14px; border-bottom-color: #eef1f6; color: #0f1b3d; font-size: 15px !important; font-weight: 400 !important; line-height: 1.4; }
    #tn-maps #sea-filterCard-parent .sort-cnt .sea-btn:hover { background: #f5f7fb; }
    #tn-maps #sea-filterCard-parent .sort-cnt .sea-btn.active,
    #tn-maps #sea-filterCard-parent .sort-opt-label.btn.sort-option-js.active { color: #2556E0 !important; font-weight: 600 !important; }
    #tn-maps #sea-filterCard-parent .sort-opt-check { border-color: #d6dce8; }
    #tn-maps #sea-filterCard-parent .sort-cnt .btn.active .sort-opt-check { border: 1px solid #2556E0 !important; }
    #tn-maps #sea-filterCard-parent .sort-cnt .btn.active .sort-opt-check:before { background: #2556E0 !important; color: #2556E0 !important; }

    /* Done: full-width blue button pinned to the bottom of the drawer */
    #tn-maps #sea-filterCard-parent #sea-filterCard-submit-ctn {
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 12px 0 20px;
        margin-top: 8px;
        z-index: 2;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCard-submit-ctn #sea-filterCard-submit-btn {
        float: none;
        display: block;
        width: 100%;
        background: #2556E0 !important;
        color: #fff !important;
        border: 0;
        border-radius: 10px;
        padding: 14px;
        font-size: 16px;
        font-weight: 700 !important;
        opacity: 1 !important;
        transition: background .2s;
    }
    #tn-maps #sea-filterCard-parent #sea-filterCard-submit-ctn #sea-filterCard-submit-btn:hover { background: #1a3fa8 !important; }

    /* ---------- SEATICS DEFAULT ACCENTS -> SEAT OUTLET PALETTE ----------
       The widget's own stylesheets (loaded from Seatics) colour these with
       their default greens. Every such rule, as listed
       from light-desktop(-delayed) and light-mobile(-delayed), is mapped to
       the secondary #3358e4 (hover: primary #2556e0), with white text on
       filled states. The older overrides above only matched some of them
       (e.g. the pre-checkout quantity needed a .draggable class Seatics
       does not add on phones), which is why green still showed. */

    /* Filled buttons: Buy, pre-checkout CTA, legend / quantity / warning / feedback buttons */
    .seatics .venue-ticket-list-cta-button,
    .seatics .sea-sold-out-button,
    .seatics .pre-checkout-price-cta,
    .seatics .sea-quantity-modal-get,
    .seatics .legendDriven .mobLegend .legend-submit-btn,
    .seatics .sea-quantity-warning-modal-btn,
    .seatics #sea-feedback-form .sea-feedback-form-wrapper button.sea-feedback-form-submit {
        background-color: #3358e4 !important;
        border-color: #3358e4 !important;
        color: #fff !important;
        font-family: inherit;
    }
    .seatics .venue-ticket-list-cta-button:hover,
    .seatics .pre-checkout-price-cta:hover,
    .seatics .sea-quantity-modal-get:hover,
    .seatics .legendDriven .mobLegend .legend-submit-btn:hover,
    .seatics .sea-quantity-warning-modal-btn:hover,
    .seatics #sea-feedback-form .sea-feedback-form-wrapper button.sea-feedback-form-submit:hover {
        background-color: #2556e0 !important;
        border-color: #2556e0 !important;
        opacity: 1 !important;
    }
    .seatics .pre-checkout-price-cta { border-radius: 10px; font-weight: 700 !important; }

    /* Selected / hovered quantity choices (pre-checkout slider and filter circles) */
    .seatics .sea-quantity-items .sea-selected,
    .seatics .sea-quantity-items .sea-listItem:hover,
    .seatics .filters-qty-filter .sea-btn.active,
    .seatics .filters-qty-filter .sea-btn.sea-active {
        background: #3358e4 !important;
        border-color: #3358e4 !important;
        color: #fff !important;
        opacity: 1 !important;
    }
    .seatics .filters-qty-filter .sea-btn:hover { border-color: #3358e4 !important; color: #3358e4; }
    .seatics .sea-quantity-items .slick-prev:hover,
    .seatics .sea-quantity-items .slick-next:hover { color: #3358e4 !important; }

    /* Sort list: active option text, ring and dot */
    .seatics .sort-cnt .sea-btn.active,
    .seatics .sea-btn.active .sort-opt-label { color: #3358e4 !important; }
    .seatics .sea-btn.active .sort-opt-check { border-color: #3358e4 !important; }
    .seatics .sea-btn.active .sort-opt-check:before { background: #3358e4 !important; }

    /* Odds and ends */
    .seatics .seller-rating .text-success { color: #3358e4 !important; }
    .seatics .sea-feedback-success { background-color: #3358e4 !important; }
    .seatics .sea-feedback-success:after { border-top-color: #3358e4 !important; }

    /* ---------- TICKET-TYPE TABS (Tickets / Packages / ADA / Parking Passes ...) ----------
       Seatics pins the Filters button over the right end of the tab strip
       (position:absolute; right:0; width:105px) and lets the tabs run under
       it, so whichever tab slid to the end (Parking Passes here) could not be
       clicked. End the strip's visible area before the button instead; the
       widget's own next-arrow (right:103px) then sits in the gap. */
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-list { margin-right: 120px; }
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label {
        border-radius: 8px;
        padding: 4px 12px;
        font-family: inherit;
    }
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label span { color: #0f1b3d; font-size: 14px; }
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label.active,
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label:hover {
        background-color: #eef3fd;
        border-color: #2556e0;
    }
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label.active span { color: #2556e0; font-weight: 600; }
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider button.slick-prev,
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider button.slick-next { color: #2556e0; }
    /* The tab's hidden button keeps focus after a click, which drew the
       widget's thick 3px focus box around the active tab. Show a ring only
       for keyboard focus. */
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label { outline: none !important; }
    #tn-maps #sea-inventory-slider-with-filterBtn .sea-inventory-slider .slick-track label:has(> .sea-visually-hidden-input:focus-visible) {
        outline: 2px solid #2556e0 !important;
        outline-offset: 2px;
    }

    /* ---------- EMPTY LIST ("No Matches", e.g. ADA Accessible with no seats) ---------- */
    #tn-maps .sea-ticket-list-empty-results-message { padding: 24px 16px !important; text-align: center; }
    #tn-maps .sea-ticket-list-empty-results-content {
        max-width: 340px;
        margin: 0 auto;
        padding: 28px 24px 24px;
        background: #f7f9fe;
        border: 1px solid #e3e8f0;
        border-radius: 12px;
        font-family: inherit;
    }
    #tn-maps .sea-ticket-list-empty-results-content::before {
        content: '';
        display: block;
        width: 48px;
        height: 48px;
        margin: 0 auto 14px;
        border-radius: 50%;
        background: #eef3fd url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232556e0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E") center / 22px no-repeat;
    }
    #tn-maps .sea-ticket-list-empty-results-header { color: #0f1b3d; font-size: 18px; font-weight: 700; line-height: 1.3; margin-bottom: 6px; }
    #tn-maps .sea-ticket-list-empty-results-text { color: #5b6578; font-size: 14px; line-height: 1.5; margin-bottom: 18px; }
    #tn-maps .sea-ticket-list-empty-results-content .sea-ticket-list-empty-results-action {
        display: inline-block;
        min-width: 0;
        min-height: 0;
        padding: 10px 22px;
        background: #fff !important;
        border: 1.5px solid #2556e0 !important;
        border-radius: 999px;
        color: #2556e0 !important;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600 !important;
        line-height: 1.2;
        transition: background-color .2s, color .2s;
    }
    #tn-maps .sea-ticket-list-empty-results-content .sea-ticket-list-empty-results-action:hover,
    #tn-maps .sea-ticket-list-empty-results-content .sea-ticket-list-empty-results-action:focus-visible {
        background: #2556e0 !important;
        color: #fff !important;
    }

    /* ---------- PARKING PASSES, ACCESS PASSES, HOTELS and PACKAGES LISTS (only while one of those tabs is selected) ----------
       Card rows: checkbox | car icon + title / row / quantity | notes icon |
       price "each" | blue Buy Now. The widget's dark "Parking Passes (Not
       valid for entry to event)" bar becomes an amber notice. Rows are the
       widget's own table rows (.Sea-TicketRow); markup and behaviour unchanged. */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) table:has(> tbody > tr.Sea-TicketRow),
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) table:has(> tbody > tr.Sea-TicketRow) > tbody {
        display: block;
        width: 100%;
        table-layout: auto;
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) table:has(> tbody > tr.Sea-TicketRow) { padding: 4px 12px 12px; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list { background: #fff; }
    /* Each row is a flex card, so Seatics' fixed table column widths cannot squeeze it. */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow {
        display: flex !important;
        align-items: center;
        gap: 4px;
        margin: 10px 0 0;
        padding: 14px;
        background: #fff;
        border: 1px solid #e3e8f0 !important;
        border-radius: 12px;
        height: auto !important;
        max-height: none !important;
        font-family: inherit;
        transition: background .2s, border-color .2s;
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow > td {
        display: block;
        border: 0 !important;
        padding: 0;
        background: transparent !important;
        height: auto !important;
        max-height: none !important;
        width: auto;
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow > td:empty { display: none; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow > td:last-child { flex: 1 1 auto; min-width: 0; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow:hover { background: #f7f9fe; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow.venue-ticket-list-highlighted,
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow:has(.sea-checked) { background: #eef3fd; border-color: #2556e0 !important; }
    /* colour bar column: not part of this design */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .tickets-table-second-col-table-mark { display: none !important; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .venue-ticket-list-section-color-arrow { display: none !important; }
    /* inner details: text | notes icon | price | Buy, as a grid */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .venue-ticket-list-tbl,
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .venue-ticket-list-tbl > tbody { display: block; width: 100%; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .venue-ticket-list-tbl > tbody > tr {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 40px 84px auto;
        align-items: center;
        column-gap: 8px;
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .venue-ticket-list-tbl td { display: block; padding: 0; width: auto !important; min-width: 0; }
    /* segment header ("Not valid for entry") as a block above the cards */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.venue-ticket-list-segment-tr { display: block; height: auto; margin: 10px 0 0; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.venue-ticket-list-segment-tr > td { display: block; }

    /* checkbox (My List) */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .Sea-TicketRow .sea-my-list-toggle { width: 34px; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .Sea-TicketRow .sea-my-list-check {
        width: 22px;
        height: 22px;
        border: 1.5px solid #c5ccd9;
        border-radius: 6px;
        background: #fff;
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .Sea-TicketRow .sea-checked .sea-my-list-check { background: #2556e0; border-color: #2556e0; color: #fff; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .Sea-TicketRow .sea-my-list-check-icon:before { color: #fff; font-size: 16px; margin: 2px 0 0 2px; }

    /* title / row / quantity */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-section-qty-col { padding-left: 6px !important; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-section-row-text { color: #0f1b3d; font-size: 16px; font-weight: 700; line-height: 1.3; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .sea-tg-list-section-tg-type-icon { color: #0f1b3d; font-size: 16px; margin-right: 8px; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-tg-row { display: block; color: #5b6578; font-size: 14px; line-height: 1.4; margin-top: 2px; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-quantity { display: inline; color: #5b6578; font-size: 14px; line-height: 1.4; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .sea-ticket-list-quantity-sep { display: none; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .sea-deliv-type-icon { color: #8a93a6; margin-left: 4px; }

    /* notes icon, price, Buy Now */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-info-col { text-align: center; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-info-child .cm-doc-text { color: #8a93a6; font-size: 22px; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-price-col { text-align: center; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-price-col .venue-ticket-list-cta-amt { color: #0f1b3d; font-size: 17px; font-weight: 800; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-price-col .venue-ticket-list-cta-amt-suffix { color: #5b6578; font-size: 13px; margin-top: 2px; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-cta-col { text-align: right; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-cta-button {
        background-color: #2556e0 !important;
        border: 0 !important;
        border-radius: 8px;
        color: #fff !important;
        font-size: 15px;
        font-weight: 600;
        padding: 10px 20px;
        min-width: 0;
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-cta-button:hover { background-color: #1a3fa8 !important; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .sea-ticket-list-default-cta { color: #2556e0; font-weight: 600; }

    /* "Parking Passes (Not valid for entry to event)" -> amber notice */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-segment-tr { height: auto; text-align: left; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-segment-td {
        background: #fff7e8;
        border: 1px solid #f3d6a4;
        border-radius: 10px;
        color: #9a5b00;
        font-size: 14px;
        font-weight: 600;
        padding: 12px 14px 12px 46px;
        position: relative;
        font-size: 0; /* swap the widget's wording for the design's (below) */
    }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-segment-td::after {
        content: 'Parking passes do not include event admission.';
        font-size: 14px;
    }
    #tn-maps:has(.sea-ticket-type-option.active button[value="AccessPasses"]) .venue-ticket-list-segment-td::after {
        content: 'Access passes do not include event admission.';
    }
    #tn-maps:has(.sea-ticket-type-option.active button[value="Hotels"]) .venue-ticket-list-segment-td::after {
        content: 'Hotel deals do not include event admission.';
    }
    /* Packages include admission: if the widget shows a section header there,
       keep its own wording as a plain label, not the amber notice. */
    #tn-maps:has(.sea-ticket-type-option.active button[value="Packages"]) .venue-ticket-list-segment-td {
        background: #f7f9fe;
        border-color: #e3e8f0;
        color: #0f1b3d;
        font-size: 14px;
        padding: 10px 14px !important;
    }
    #tn-maps:has(.sea-ticket-type-option.active button[value="Packages"]) .venue-ticket-list-segment-td::before,
    #tn-maps:has(.sea-ticket-type-option.active button[value="Packages"]) .venue-ticket-list-segment-td::after { content: none; }
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-segment-td::before {
        content: '!';
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #e07b00;
        color: #fff;
        font-weight: 800;
        font-size: 14px;
        line-height: 22px;
        text-align: center;
    }

    /* Compact cards whenever the list itself is narrow (desktop side panel ~400px,
       phones, foldables): sized by the list's width, not the screen's. */
    #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list { container-type: inline-size; }
    @container (max-width: 520px) {
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) table:has(> tbody > tr.Sea-TicketRow) { padding: 2px 8px 8px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .Sea-TicketRow .sea-my-list-toggle { width: 28px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow { padding: 12px 10px; margin-top: 8px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) tr.Sea-TicketRow .venue-ticket-list-tbl > tbody > tr { grid-template-columns: minmax(0, 1fr) 26px 60px auto; column-gap: 6px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-section-row-text { font-size: 15px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-tg-row,
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-quantity { font-size: 13px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-info-child .cm-doc-text { font-size: 18px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-price-col .venue-ticket-list-cta-amt { font-size: 15px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-cta-button { font-size: 13px; padding: 8px 12px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-segment-td { padding: 10px 12px 10px 42px; }
        #tn-maps:has(.sea-ticket-type-option.active :is(button[value="Parking"], button[value="AccessPasses"], button[value="Hotels"], button[value="Packages"])) .venue-ticket-list-segment-td::after { font-size: 13px; }
    }

    /* ======================================================================
       Phone and foldable fit for the seat map and the ticket details panel.
       Seatics ships fixed pixel layouts (a 3 px side padding, 10 to 21 px insets that do not
       match each other). These rules keep the widget inside the screen and give the panel one
       comfortable inset that scales with the screen width (14 px on a 280 px cover screen,
       up to 24 px on an unfolded one) and respects display cutouts.
       ====================================================================== */
    #tn-maps { max-width: 100%; }
    @supports (overflow: clip) { #tn-maps { overflow-x: clip; } }   /* nothing inside the widget can widen the page */
    .seatics .map-list-ctn,
    .seatics .map-ctn { max-width: 100% !important; box-sizing: border-box; }

    #precheckout-parent {
        --so-pc-inset: clamp(14px, 4.5vw, 24px);
        --so-pc-inset-r: max(var(--so-pc-inset), env(safe-area-inset-right, 0px));
        box-sizing: border-box;
        max-width: 100vw;
        overscroll-behavior: contain;
    }
    #precheckout-parent .sea-quantity-container fieldset { margin: 0; padding: 0 var(--so-pc-inset-r) 0 var(--so-pc-inset); min-width: 0; }
    #precheckout-parent .sea-quantity-container fieldset legend { margin: 18px 0 12px; padding: 0; }
    #precheckout-parent #sea-precheckout-qty-select { padding: 0 !important; margin: 0 !important; }
    #precheckout-parent #sea-precheckout-qty-select .slick-list { margin: 0 !important; }
    #precheckout-parent #sea-precheckout-qty-select .slick-track { margin-left: 0 !important; margin-right: 0 !important; }
    #precheckout-parent .pre-checkout-price-text-ctn,
    #precheckout-parent .sea-precheckout-fees-parent,
    #precheckout-parent #sea-pre-checkout-disclaimer-ctn,
    #precheckout-parent .pre-checkout-delivery-note,
    #precheckout-parent .sea-pre-checkout-delivery-note-hdr { padding-left: var(--so-pc-inset) !important; padding-right: var(--so-pc-inset-r) !important; }
    #precheckout-parent .pre-checkout-delivery-ctn { padding-left: var(--so-pc-inset) !important; padding-right: var(--so-pc-inset-r) !important; box-sizing: border-box; }
    #precheckout-parent .pre-checkout-price-ctn {
        box-sizing: border-box; height: auto !important; margin: 10px 0 18px;
        padding: 0 var(--so-pc-inset-r) 0 var(--so-pc-inset) !important;
    }
    /* No "display" here on purpose: Seatics shows only one of these two buttons (sold out or not) and hides the other with it. */
    #precheckout-parent #pre-checkout-price-cta,
    #precheckout-parent #pre-checkout-sold-cta {
        float: none; width: 100%; box-sizing: border-box;
        height: 52px; padding: 14px 16px; border-radius: 12px;
        font-size: 17px; line-height: 1.2; white-space: normal;
    }
  </style>

<?php include 'footer.php'; ?>