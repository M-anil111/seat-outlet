<?php $GLOBALS['soNoAds'] = true;   // no ad banners on event pages: the seat map and ticket list are the page
include 'header.php'; ?>


<?php
// Sanitize and validate event ID from query string.
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
  $slug  = $_GET['slug'] ?? '';
  [$id]  = soSlugResolve('event', (string) $slug);   // stored slug, or the old name-and-id form
  $id    = (int) $id;
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
$evNmForLead = (string) ($event['text']['name'] ?? '');
?>
<?php
$eventVenueParts = [];
if ($eventVenueName !== '') {
  $eventVenueParts[] = $eventVenueId ? ['/venue/' . soVenueSlug($eventVenueName, $eventVenueId, $eventCityLabel), $eventVenueName] : [null, $eventVenueName];
}
if ($eventCityName !== '') {
  $eventVenueParts[] = $eventCityId ? ['/' . $categoryCityPrefix . '/' . soSlug('city', $eventCityLabel, $eventCityId), $eventCityName] : [null, $eventCityName];
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
  $eventInfoLink = ['/artist-city/' . soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id']) . '/' . soSlug('city', $eventCityLabel, $eventCityId), 'More ' . $primaryPerformer['name'] . ' tickets in ' . $eventCityLabel];
} elseif (!empty($eventCityId)) {
  $eventInfoLink = ['/' . $categoryCityPrefix . '/' . soSlug('city', $eventCityLabel, $eventCityId), 'More events in ' . $eventCityLabel];
}
?>
<?php
$evType = imageEntityTypeForPerformer($event['defaultCategory'] ?? []);
$evWho  = (string) ($primaryPerformer['name'] ?? ($event['text']['name'] ?? ''));
$evImg  = getEntityImage($evType, $evWho, ['category' => $event['defaultCategory'] ?? [], 'resolve' => false]);
$evReal = in_array($evImg['status'] ?? '', ['ok', 'manual'], true) && ($evImg['url'] ?? '') !== '';
$evHue  = hexdec(substr(md5($evWho), 0, 4)) % 360;
$evCatLabel = ucwords(strtolower((string) ($event['defaultCategory']['text']['name'] ?? '')));
?>
<section class="so-evhero">
  <div class="container">
    <?php if (!empty($evTrail)) { /* the same trail as the BreadcrumbList in the head: up to the category and the performer */ ?>
    <nav class="so-crumbs" aria-label="Breadcrumb"><ol>
      <?php foreach ($evTrail as $c) { ?><li><a href="<?php echo $h($c['url']); ?>"><?php echo $h($c['label']); ?></a></li><?php } ?>
      <li aria-current="page"><?php echo $h($evCrumbLabel ?? ($event['text']['name'] ?? '')); ?></li>
    </ol></nav>
    <?php } ?>
    <div class="so-evhero__card">
      <div class="so-evhero__media" style="--so-hue:<?php echo (int) $evHue; ?>">
        <?php if ($evReal) { ?>
          <img src="<?php echo $h($evImg['url']); ?>" alt="<?php echo $h($evWho); ?>" width="480" height="480" fetchpriority="high" decoding="async">
        <?php } else { ?>
          <span class="so-evhero__initials" aria-hidden="true"><?php echo $h(soInitials($evWho)); ?></span>
        <?php } ?>
      </div>
      <div class="so-evhero__main">
        <?php if ($eventTimestamp) { ?>
          <p class="so-evhero__when"><?php echo $h(date('l, F j, Y', $eventTimestamp)); ?><?php echo $eventTimeText !== '' ? ' &middot; ' . $h($eventTimeText) : ''; ?></p>
        <?php } ?>
        <h1 class="ev-title so-evhero__title"><?php
          // The page's one H1 (no keyword strip on event pages). Showings of one event share a name, so venue, day and time follow it: each page gets its own heading.
          echo $h($event['text']['name'] ?? '');
          $evH1Bits = array_filter([(string) ($event['venue']['text']['name'] ?? ''), $eventTimestamp ? date('M j, Y', $eventTimestamp) . ($eventTimeText !== '' ? ', ' . $eventTimeText : '') : '']);
          if ($evH1Bits) { echo ' <span class="so-evhero__sub">' . $h(implode(', ', $evH1Bits)) . '</span>'; }
        ?></h1>
        <?php
          $evChips = [];
          $evTickets = (int) ($event['_metadata']['ticketCount'] ?? 0);
          $evRank = (int) ($event['salesRank'] ?? 0);
          $evDays = $eventTimestamp ? (int) floor(($eventTimestamp - strtotime('today')) / 86400) : null;
          if ($evRank >= 1 && $evRank <= 3) { $evChips[] = ['hot', 'Top seller right now']; }
          if ($evTickets > 0 && $evTickets <= 30) { $evChips[] = ['warn', 'Only ' . $evTickets . ' tickets listed']; }
          if ($evDays !== null && $evDays >= 0 && $evDays <= 14) { $evChips[] = ['soon', $evDays === 0 ? 'Happening today' : ($evDays === 1 ? 'Tomorrow' : 'In ' . $evDays . ' days')]; }
          if ($evChips) { ?>
          <ul class="so-evhero__chips" aria-label="Event status"><?php foreach ($evChips as [$ck, $cl]) { ?><li class="so-chipx so-chipx--<?php echo $ck; ?>"><?php echo $h($cl); ?></li><?php } ?></ul>
        <?php } ?>
        <?php if ($eventVenueParts) { ?>
          <p class="so-evhero__venue"><i class="bi bi-geo-alt" aria-hidden="true"></i><span><?php
            $out = [];
            foreach ($eventVenueParts as [$href, $label]) {
              $out[] = $href ? '<a href="' . $h($href) . '">' . $h($label) . '</a>' : $h($label);
            }
            echo implode("\u{2060}, ", $out);   // word joiner: the comma never starts a new line after a link
          ?></span></p>
        <?php } ?>
        <div class="so-evhero__actions">
          <button type="button" class="so-action so-action--icon so-action--save" data-so-save aria-pressed="false"><i class="bi bi-heart" aria-hidden="true"></i><span>Save</span></button>
          <button type="button" class="so-action so-action--icon" data-so-share><i class="bi bi-share" aria-hidden="true"></i><span>Share</span></button>
          <?php if ($eventTimestamp) { ?>
            <div class="so-cal" data-so-cal>
              <button type="button" class="so-action so-action--icon" data-so-cal-toggle aria-expanded="false">
                <i class="bi bi-calendar-plus" aria-hidden="true"></i><span>Add to calendar</span>
              </button>
              <div class="so-cal__menu" hidden>
                <a data-cal="google" target="_blank" rel="noopener">Google Calendar</a>
                <a data-cal="outlook" target="_blank" rel="noopener">Outlook.com</a>
                <a data-cal="office" target="_blank" rel="noopener">Microsoft 365</a>
                <a data-cal="yahoo" target="_blank" rel="noopener">Yahoo Calendar</a>
                <button type="button" data-cal="ics">Apple Calendar or other (.ics file)</button>
              </div>
            </div>
          <?php } ?>
          <?php if ($eventInfoLink) { ?>
            <a class="so-action so-action--text" href="<?php echo $h($eventInfoLink[0]); ?>"><span>More dates</span></a>
          <?php } ?>
          <button type="button" class="so-action so-action--text so-action--saved" data-so-saved-open aria-haspopup="dialog" hidden><i class="bi bi-heart-fill" aria-hidden="true"></i><span>Saved (<b data-so-saved-count>0</b>)</span></button>
        </div>
        <span class="visually-hidden" id="so-action-status" role="status" aria-live="polite"></span>
      </div>
      <div class="so-evhero__buy">
        <p class="so-evhero__price"><?php if ($eventLowPrice !== '') { ?><span>Price per ticket</span> <strong>From <?php echo $h($eventLowPrice); ?></strong><?php } else { ?><span>Price per ticket</span><?php } ?></p>
        <a class="so-evhero__cta ev-cta-btn" href="#tn-maps" data-so-cta>View tickets</a>
        <ul class="so-evhero__trust" aria-label="Buyer protection">
          <li><a href="/worry-free-guarantee"><i class="bi bi-shield-check" aria-hidden="true"></i>100% Worry-Free Guarantee</a></li>
          <li><a href="/ticket-buyer-protection">Buyer protection</a></li>
          <li><a href="/ticket-customer-service">Questions? Contact us</a></li>
        </ul>
        <p class="so-evhero__note">Resale marketplace. Prices are set by sellers and may be above or below face value. All prices in USD.</p>
      </div>
    </div>
  </div>
</section>
<?php if (!$evReal) { ?>
<script>
// No picture stored yet: ask for it once (the server only looks up names it has queued), keep the initials tile otherwise.
(function () {
  var media = document.querySelector('.so-evhero__media');
  if (!media) return;
  setTimeout(function () {
    fetch('/ajax/resolve-images.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: [{ name: <?php echo json_encode($evWho); ?>, type: <?php echo json_encode($evType); ?> }] }) })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var url = d && d.images && d.images[0];
        if (!url) return;
        var img = new Image(); img.alt = <?php echo json_encode($evWho); ?>; img.width = 480; img.height = 480;
        img.onload = function () { media.innerHTML = ''; media.appendChild(img); };
        img.src = url;
      }).catch(function () {});
  }, 1200);
})();
</script>
<?php } ?>

<?php $evLowValue = (float) ($event['pricingInfo']['lowPrice']['value'] ?? 0); ?>
<?php if ($evLowValue > 0) { ?>
<section class="so-pricealert" aria-label="Price alert">
  <div class="container">
    <details class="so-pricealert__box">
      <summary><i class="bi bi-bell" aria-hidden="true"></i><span>Alert me if the price drops</span></summary>
      <div class="so-pricealert__body">
        <?php echo soLeadForm([
          'source' => 'event-price',
          'title' => 'We will email you if the lowest price drops',
          'text' => 'Lowest listed price right now: ' . $h($eventLowPrice !== '' ? $eventLowPrice : '$' . number_format($evLowValue, 0)) . '. We only email when it falls at least 10% below that. Prices are set by sellers and can also go up.',
          'button' => 'Alert me',
          'interest_type' => 'event',
          'interest_id' => (int) $id,
          'interest_name' => $evNmForLead,
          'names' => false,
          'class' => 'so-nl--compact',
          'alert_kind' => 'price',
          'baseline_price' => $evLowValue,
        ]); ?>
      </div>
    </details>
  </div>
</section>
<?php } ?>
<div id="tn-maps" class="seatics so-seatmap" role="region" aria-label="<?php echo $h('Interactive seating chart and rows map for ' . ($eventVenueName !== '' ? $eventVenueName : 'the venue') . ' during ' . ($event['text']['name'] ?? 'this event')); ?>" aria-live="polite"></div>
<noscript><p class="so-seatmap__nojs container py-4">The seat map needs JavaScript. Please turn it on, or <a href="/ticket-customer-service">contact us</a> and we will help you find tickets.</p></noscript>
<div id="so-no-tickets" class="so-no-tickets d-none" role="region" aria-labelledby="so-no-tickets-title" tabindex="-1">
  <div class="container py-5 text-center">
    <h2 class="fw-bold fs-4 mb-2" id="so-no-tickets-title">No tickets are listed for this event right now</h2>
    <p class="text-muted mb-4" id="so-no-tickets-text">Inventory changes hourly as sellers list seats. Try another date, or browse related tickets below.</p>
    <div class="so-nt__reload" id="so-no-tickets-reload" hidden>
      <button type="button" class="so-nt__btn" data-so-reload>Reload the seat map</button>
    </div>
    <div class="so-nt__lead">
      <?php echo soLeadForm([
        'source' => 'event-empty',
        'title' => 'Notify me when tickets are listed',
        'text' => 'Leave your email and we will let you know when tickets for ' . $evNmForLead . ' show up.',
        'button' => 'Notify me',
        'interest_type' => 'event',
        'interest_id' => (int) $id,
        'interest_name' => $evNmForLead,
        'names' => false,
        'class' => 'so-nl--compact',
      ]); ?>
    </div>
    <div class="so-nt__actions">
      <?php if (!empty($primaryPerformer['id']) && !empty($primaryPerformer['name'])) { ?>
        <a class="so-nt__btn" href="/artist/<?php echo htmlspecialchars(soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id']), ENT_QUOTES, 'UTF-8'); ?>">All <?php echo htmlspecialchars($primaryPerformer['name'], ENT_QUOTES, 'UTF-8'); ?> dates</a>
      <?php } ?>
      <?php if (!empty($eventCityId)) { ?>
        <a class="so-linkchip" href="/<?php echo htmlspecialchars($categoryCityPrefix, ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars(soSlug('city', $eventCityLabel, $eventCityId), ENT_QUOTES, 'UTF-8'); ?>">More events in <?php echo htmlspecialchars($eventCityLabel, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
      <?php if (!empty($eventVenueId)) { ?>
        <a class="so-linkchip" href="/venue/<?php echo htmlspecialchars(soVenueSlug($eventVenueName, $eventVenueId, $eventCityLabel), ENT_QUOTES, 'UTF-8'); ?>">More at <?php echo htmlspecialchars($eventVenueName, ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
    </div>
  </div>
</div>
<?php
// Facts for js/event-actions.js (calendar file, share, "recently viewed"). Everything here is already on the page.
$soEventSlug = soEventSlug($event);
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
  'performer' => (string) ($event['performers'][0]['name'] ?? ''),
  'cat'      => (string) ($event['defaultCategory']['path'] ?? ''),
  'tickets'  => (int) ($event['_metadata']['ticketCount'] ?? 0),
  'rank'     => (int) ($event['salesRank'] ?? 0),
  'price'    => (string) ($event['pricingInfo']['lowPrice']['text']['formatted'] ?? ''),
  'lowPrice' => isset($event['pricingInfo']['lowPrice']['value']) && is_numeric($event['pricingInfo']['lowPrice']['value']) ? round((float) $event['pricingInfo']['lowPrice']['value'], 2) : null,
  'currency' => 'USD',
  'catName'  => strtolower((string) ($event['defaultCategory']['ancestors'][0]['text']['name'] ?? $event['defaultCategory']['text']['name'] ?? '')),
  'hasTickets' => !empty($event['_metadata']['hasTickets']),
  'widgetUrl'   => $mapScriptUrl,
  'checkoutUrl' => TN_CHECKOUT_URL,
  'venueId'  => (int) ($eventVenueId ?? 0),
];
?>
<?php
$evNm   = (string) ($event['text']['name'] ?? '');
$evPlaceFull = $eventCityLabel;
$evWhenLong = $eventTimestamp ? date('l, F j, Y', $eventTimestamp) : '';
$evCatName = ucwords(strtolower((string) ($event['defaultCategory']['text']['name'] ?? '')));
$evOther = [];
if (!empty($primaryPerformer['id'])) {
  [, $evOtherResp] = getPerformerPageEvents((int) $primaryPerformer['id'], 8);
  foreach (($evOtherResp['results'] ?? []) as $oe) { if ((int) ($oe['id'] ?? 0) !== (int) $id && count($evOther) < 6) { $evOther[] = $oe; } }
}
$evFaqs = [
  ['q' => 'How much are ' . $evNm . ' tickets?', 'a' => $eventLowPrice !== '' ? $evNm . ' tickets are listed from ' . $eventLowPrice . ' per ticket today. Prices are set by sellers and change with demand, so compare sections and seats before you buy. They can be above or below face value.' : 'Prices for ' . $evNm . ' tickets are set by sellers and change with demand. Open the seat map above to compare sections and prices.'],
  ['q' => 'When and where is ' . $evNm . '?', 'a' => $evNm . ($evWhenLong !== '' ? ' is on ' . $evWhenLong . ($eventTimeText !== '' ? ' at ' . $eventTimeText : '') : '') . ($eventVenueName !== '' ? ' at ' . $eventVenueName : '') . ($evPlaceFull !== '' ? ' in ' . $evPlaceFull : '') . '. Check the event page again before you travel in case details change.'],
  ['q' => 'Will our seats be together?', 'a' => 'Many listings are for seats next to each other, but not all of them. Choose how many tickets you need and the seat map shows listings that fit your group. Check the section, row and seat details on a listing before you buy, and contact us if you need help.'],
  ['q' => 'Are ' . $evNm . ' tickets on Seat Outlet legit?', 'a' => 'Yes. Every order is covered by our 100% guarantee: valid tickets, delivery before the event, and a refund if the event is canceled and not rescheduled. Seat Outlet is a resale marketplace, not the venue box office.'],
  ['q' => 'What if ' . $evNm . ' is canceled or postponed?', 'a' => 'If the event is canceled and not rescheduled you get a refund (delivery fees excluded). If it is rescheduled your tickets are normally valid for the new date. Read the full terms on our guarantee page.'],
];
$evJsonLd = buildFaqPageSchema(array_map(function ($f) { return ['question' => $f['q'], 'answer' => $f['a']]; }, $evFaqs));
?>
<section class="so-evinfo">
  <div class="container">
    <div class="so-evinfo__grid">
      <div class="so-evinfo__main">
        <h2><?php echo $h($evNm); ?> tickets<?php echo $eventVenueName !== '' ? ' at ' . $h($eventVenueName) : ''; ?></h2>
        <p>Looking for <?php echo $h($evNm); ?> tickets<?php echo $evPlaceFull !== '' ? ' in ' . $h($evPlaceFull) : ''; ?>? Seat Outlet lets you compare seats and prices for this event in one place<?php echo $eventLowPrice !== '' ? ', with tickets listed from <strong>' . $h($eventLowPrice) . '</strong> per ticket' : ''; ?>. Pick your quantity, choose a section on the map and check out securely, backed by our <a href="/worry-free-guarantee">100% guarantee</a>.</p>
        <p>This is a resale marketplace, so prices are set by sellers and may be above or below face value. Read how <a href="/ticket-buyer-protection">ticket buyer protection</a> works, or see <a href="/how-to-buy-tickets-online">how to buy tickets online</a>.</p>

        <h2>How to buy <?php echo $h($evNm); ?> tickets</h2>
        <ol class="so-steps">
          <li><span><strong>Choose how many.</strong> Tell us how many tickets you need and we only show listings that fit.</span></li>
          <li><span><strong>Pick your seats.</strong> Use the map and the filters to compare sections, rows and prices.</span></li>
          <li><span><strong>Check out and go.</strong> Pay securely and get your tickets before the event.</span></li>
        </ol>

        <?php soBuyerGuaranteeSection(['events' => [$event]]); ?>

        <h2>Questions about <?php echo $h($evNm); ?> tickets</h2>
        <div class="so-evfaq">
          <?php foreach ($evFaqs as $fq) { ?>
          <details class="so-faq"><summary><?php echo $h($fq['q']); ?></summary><p><?php echo $h($fq['a']); ?></p></details>
          <?php } ?>
        </div>
      </div>
      <aside class="so-evinfo__side">
        <div class="so-evcard">
          <h3>Event details</h3>
          <dl>
            <?php if ($evWhenLong !== '') { ?><dt>Date</dt><dd><?php echo $h($evWhenLong); ?></dd><?php } ?>
            <?php if ($eventTimeText !== '') { ?><dt>Time</dt><dd><?php echo $h($eventTimeText); ?></dd><?php } ?>
            <?php if ($eventVenueName !== '') { ?><dt>Venue</dt><dd><?php echo $eventVenueId ? '<a href="/venue/' . $h(soVenueSlug($eventVenueName, $eventVenueId, $eventCityLabel)) . '">' . $h($eventVenueName) . ' tickets</a>' : $h($eventVenueName); ?></dd><?php } ?>
            <?php if ($evPlaceFull !== '') { ?><dt>City</dt><dd><?php echo $eventCityId ? '<a href="/' . $h($categoryCityPrefix) . '/' . $h(soSlug('city', $eventCityLabel, $eventCityId)) . '">Events in ' . $h($evPlaceFull) . '</a>' : $h($evPlaceFull); ?></dd><?php } ?>
            <?php if (!empty($primaryPerformer['id'])) { ?><dt>Performer</dt><dd><a href="/artist/<?php echo $h(soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id'])); ?>"><?php echo $h($primaryPerformer['name']); ?> tickets</a></dd><?php } ?>
            <?php if ($evCatName !== '') { ?><dt>Category</dt><dd><?php echo $h($evCatName); ?></dd><?php } ?>
          </dl>
        </div>
        <?php if ($evOther) { ?>
        <div class="so-evcard">
          <h3>More <?php echo $h($primaryPerformer['name'] ?? 'dates'); ?> dates</h3>
          <ul class="so-evother">
            <?php foreach ($evOther as $oe) { $ots = strtotime($oe['date']['date'] ?? 'now'); ?>
            <li><a href="/event/<?php echo $h(soEventSlug($oe)); ?>"><span class="so-evother__d"><?php echo $h(date('M j', $ots)); ?></span><span class="so-evother__t"><?php echo $h(($oe['city']['text']['name'] ?? '') . ', ' . ($oe['stateProvince']['text']['abbr'] ?? '')); ?><small><?php echo $h($oe['venue']['text']['name'] ?? ''); ?></small></span><span class="so-evother__p"><?php echo $h($oe['pricingInfo']['lowPrice']['text']['formatted'] ?? ''); ?></span></a></li>
            <?php } ?>
          </ul>
          <?php if (!empty($primaryPerformer['id'])) { ?><a class="so-evcard__more" href="/artist/<?php echo $h(soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id'])); ?>">See all <?php echo $h($primaryPerformer['name']); ?> tickets &rsaquo;</a><?php } ?>
        </div>
        <?php } ?>
      </aside>
    </div>
  </div>
</section>
<?php if ($evJsonLd) { ?><script type="application/ld+json"><?php echo json_encode(['@context' => 'https://schema.org'] + $evJsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script><?php } ?>

<script type="application/json" id="so-event-data"><?php echo json_encode($soEventData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?></script>
<?php /* The seat-map widget script is loaded synchronously right here, exactly as before, and js/event-widget.js (a plain script in the footer, no defer) applies the settings immediately after it, before the page finishes loading, so the settings are in place when the widget starts. If the script is blocked (ad blocker, network), event-widget.js tries once more asynchronously and shows a visible message when that fails too. */ ?>
<script src="<?php echo htmlspecialchars($mapScriptUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>

<?php include 'footer.php'; ?>