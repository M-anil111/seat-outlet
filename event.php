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
<?php /* Slim title strip: the one H1, where and when, and the resale disclosure. The seat map and the tickets follow straight away, above the fold. */ ?>
<section class="so-evbar">
  <div class="container">
    <?php if (!empty($evTrail)) { /* the same trail as the BreadcrumbList in the head: up to the category and the performer */ ?>
    <nav class="so-crumbs" aria-label="Breadcrumb"><ol>
      <?php foreach ($evTrail as $c) { ?><li><a href="<?php echo $h($c['url']); ?>"><?php echo $h($c['label']); ?></a></li><?php } ?>
      <li aria-current="page"><?php echo $h($evCrumbLabel ?? ($event['text']['name'] ?? '')); ?></li>
    </ol></nav>
    <?php } ?>
    <h1 class="ev-title so-evbar__title"><?php
      // The page's one H1 (no keyword strip on event pages): the spec's "Buy <Performer> Tickets for <Venue> Show in <City>, <ST>". The date
      // is in the line below and in the title tag, so the heading stays one readable line.
      echo $h($evSpec['h1']);
    ?></h1>
    <?php if ($eventTimestamp || $eventVenueParts || $eventInfoLink) {
      // Date, time, venue and place, each with its icon; the venue and the city keep their links.
      $evIco = [
        'date' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
        'time' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        'pin'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>',
      ];
      $evLink = fn($part) => $part[0] ? '<a href="' . $h($part[0]) . '">' . $h($part[1]) . '</a>' : $h($part[1]);
      $evPlaceParts = $eventVenueName !== '' ? array_slice($eventVenueParts, 1) : $eventVenueParts;
    ?>
    <ul class="so-evbar__facts">
      <?php if ($eventTimestamp) { ?><li><?php echo $evIco['date']; ?><span><?php echo $h(date('l, F j, Y', $eventTimestamp)); ?></span></li><?php } ?>
      <?php if ($eventTimestamp && $eventTimeText !== '') { ?><li><?php echo $evIco['time']; ?><span><?php echo $h($eventTimeText); ?></span></li><?php } ?>
      <?php if ($eventVenueName !== '' && $eventVenueParts) { ?><li><?php echo $evIco['pin']; ?><span><?php echo $evLink($eventVenueParts[0]); ?></span></li><?php } ?>
      <?php if ($evPlaceParts) { ?><li><?php echo $evIco['pin']; ?><span><?php echo implode("\u{2060}, ", array_map($evLink, $evPlaceParts)); /* word joiner: the comma never starts a new line after a link */ ?></span></li><?php } ?>
      <?php if ($eventInfoLink) { ?><li class="so-evbar__more-li"><a class="so-evbar__more" href="<?php echo $h($eventInfoLink[0]); ?>">More dates</a></li><?php } ?>
    </ul>
    <?php } ?>
    <p class="so-evbar__note">Resale marketplace. Prices are set by sellers and may be above or below face value. All prices in USD. <a href="/worry-free-guarantee">100% Worry-Free Guarantee</a></p>
  </div>
</section>
<section class="so-evtix" aria-label="Seat map and available tickets"><div class="container so-evtix__wrap">
<div id="tn-maps" class="seatics so-seatmap" role="region" aria-label="<?php echo $h('Interactive seating chart and rows map for ' . ($eventVenueName !== '' ? $eventVenueName : 'the venue') . ' during ' . ($event['text']['name'] ?? 'this event')); ?>" aria-live="polite"></div>
<noscript><p class="so-seatmap__nojs container py-4">The seat map needs JavaScript. Please turn it on, or <a href="/ticket-customer-service">contact us</a> and we will help you find tickets.</p></noscript>
</div></section>
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
  'checkoutDomain' => (string) parse_url(TN_CHECKOUT_URL, PHP_URL_HOST),   // Seatics.config.c3CheckoutDomain: the host only (checkout.seatoutlet.com)
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
<?php
// Spec sections (inc/page-spec.php): every one is built from data this page already holds and is left out when there is none.
$evStateAbbr = (string) ($event['stateProvince']['text']['abbr'] ?? '');
$evPerfName = (string) ($primaryPerformer['name'] ?? $evLabel);
$evH2 = $evSpec['h2'];
$evVenueEvents = $evVenueData['events'] ?? [];
$evShownIds = [(int) $id];
$evVenueHref = $eventVenueId ? '/venue/' . soVenueSlug($eventVenueName, $eventVenueId, $eventCityLabel) : '';
// About: a game always has its own facts; the rest need a stored biography or Wikidata description.
if ($evKind === 'sports') {
  $evAboutText = $evLabel . ($evWhenLong !== '' ? ' is on ' . $evWhenLong . ($eventTimeText !== '' ? ' at ' . $eventTimeText : '') : ' is scheduled') . ($eventVenueName !== '' ? ' at ' . $eventVenueName : '') . ($evPlaceFull !== '' ? ' in ' . $evPlaceFull : '') . '. Compare seats and prices from many sellers on Seat Outlet.';
} else {
  $evAboutText = soSpecAboutText((int) ($primaryPerformer['id'] ?? 0), $evPerfName);
}
// The section that differs by kind: tour dates, the venue's events of the same kind, or the festival lineup.
$evKindSection = '';
$evSubPrefix = '.' . implode('.', array_slice(array_filter(explode('.', (string) $eventCategoryPath), 'strlen'), 0, 3)) . '.';
if ($evKind === 'concert' && $evOther) {
  $evKindSection = '<h2>' . $h($evH2['kind']) . '</h2>' . soSpecEventList($evOther, [(int) $id], 6)
    . (!empty($primaryPerformer['id']) ? '<p><a href="/artist/' . $h(soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id'])) . '">See all ' . $h($evPerfName) . ' tour dates</a></p>' : '');
  foreach ($evOther as $oe) { $evShownIds[] = (int) ($oe['id'] ?? 0); }
} elseif (in_array($evKind, ['sports', 'theater'], true)) {
  $evSameSub = array_values(array_filter($evVenueEvents, fn($oe) => strpos((string) ($oe['defaultCategory']['path'] ?? ''), $evSubPrefix) === 0));
  $evList = soSpecEventList($evSameSub, $evShownIds, 6);
  if ($evList !== '' && $eventVenueName !== '') {
    $evKindSection = '<h2>' . $h($evH2['kind']) . '</h2>' . $evList;
    foreach ($evSameSub as $oe) { $evShownIds[] = (int) ($oe['id'] ?? 0); }
  }
} elseif ($evKind === 'festival' && count($event['performers'] ?? []) > 1) {
  $evLineup = '';
  foreach ($event['performers'] as $lp) {
    if (empty($lp['name'])) continue;
    $evLineup .= '<li>' . (!empty($lp['id']) ? '<a href="/artist/' . $h(soSlug('performer', (string) $lp['name'], (int) $lp['id'])) . '">' . $h($lp['name']) . '</a>' : $h($lp['name'])) . '</li>';
  }
  if ($evLineup !== '') $evKindSection = '<h2>' . $h($evH2['kind']) . '</h2><ul class="so-speclist">' . $evLineup . '</ul>';
}
// Venue info: address, directions, transit, the official site and the weather, from the venue record and Wikidata (no policies are stated).
$evVenueFacts = $eventVenueName !== '' ? soEntityFacts($eventVenueName, 'venue', (string) $eventCityName) : [];
$evVenueInfoHtml = soSpecVenueInfoHtml($evH2['venue'], (string) $eventVenueName, (string) $eventCityName, $evStateAbbr, $evVenueHref, is_array($evVenueRec ?? null) ? $evVenueRec : [], $evVenueFacts, (int) ($evVenueData['total'] ?? 0), true);
$evGuideHtml = soSpecGuideHtml($evH2['guide'], $evLabel, [
  'when' => $evWhenLong !== '' ? $evWhenLong . ($eventTimeText !== '' ? ' at ' . $eventTimeText : '') : '',
  'venue' => $eventVenueName, 'venueHref' => $evVenueHref,
  'address' => (string) (($evVenueRec['address']['text']['address1'] ?? '') ?: ''),
  'tickets' => (int) ($event['_metadata']['ticketCount'] ?? 0), 'low' => $eventLowPrice,
], false, true);
$evOtherList = soSpecEventRows($evVenueEvents ?: ($evCityData['events'] ?? []), $evShownIds, 6);
$evSimilarHtml = ($evOtherList !== '' && $eventVenueName !== '') ? '<div class="so-evv-card so-evv-up"><h2>' . $h($evH2['other']) . '</h2>' . $evOtherList . '</div>' : '';
$evOtherCard = array_slice($evOther, 0, 4);
$evAtVenue = [];
$evSkipVenue = array_merge([(int) $id], array_map(fn($oe) => (int) ($oe['id'] ?? 0), $evOtherCard));
foreach ($evVenueEvents as $ve) {
  if (count($evAtVenue) >= 4) break;
  if (empty($ve['text']['name']) || in_array((int) ($ve['id'] ?? 0), $evSkipVenue, true)) continue;
  $evAtVenue[] = $ve;
}
$evKindWord = ['concert' => 'concert', 'sports' => 'sports', 'theater' => 'theater', 'festival' => 'festival'][$evKind] ?? '';
?>
<?php
// The venue card beside "About this event": name, street and place, a map-style panel, Directions and Venue details.
$evStreet = trim((string) ($evVenueRec['address']['text']['address1'] ?? ''));
$evDirHref = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(trim($eventVenueName . ' ' . $evStreet . ' ' . $eventCityLabel));
?>
<section class="so-evp so-evp--white">
  <div class="container so-evp__wrap">
    <div class="so-evp-about<?php echo $eventVenueName === '' ? ' so-evp-about--solo' : ''; ?>">
      <div class="so-evp-about__text so-evinfo__main">
        <h2 class="so-evp__h"><?php echo $h($evH2['tickets']); ?></h2>
        <p>Looking for <?php echo $h($evNm); ?> tickets<?php echo $evPlaceFull !== '' ? ' in ' . $h($evPlaceFull) : ''; ?>? Seat Outlet lets you compare seats and prices for this event in one place<?php echo $eventLowPrice !== '' ? ', with tickets listed from <strong>' . $h($eventLowPrice) . '</strong> per ticket' : ''; ?>. Pick your quantity, choose a section on the map and check out securely, backed by our <a href="/worry-free-guarantee">100% guarantee</a>.</p>
        <p>This is a resale marketplace, so prices are set by sellers and may be above or below face value. Read how <a href="/ticket-buyer-protection">ticket buyer protection</a> works, or see <a href="/how-to-buy-tickets-online">how to buy tickets online</a>.</p>
      </div>
      <?php if ($eventVenueName !== '') { ?>
      <aside class="so-evp-venue" aria-label="Venue">
        <h3><?php echo $h($eventVenueName); ?></h3>
        <p class="so-evp-venue__addr"><svg width="18" height="18" viewBox="0 0 24 24" fill="#2556e0" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg><span><?php echo $evStreet !== '' ? $h($evStreet) . '<br>' : ''; ?><?php echo $h($eventCityLabel); ?></span></p>
        <a class="so-evp-venue__map" href="<?php echo $h($evDirHref); ?>" target="_blank" rel="noopener" aria-label="<?php echo $h('Map: directions to ' . $eventVenueName); ?>"><svg width="30" height="38" viewBox="0 0 24 30" aria-hidden="true"><path fill="#2556e0" d="M12 0C5.4 0 0 5.2 0 11.6 0 20.3 12 30 12 30s12-9.7 12-18.4C24 5.2 18.6 0 12 0Z"/><circle cx="12" cy="11.5" r="4.5" fill="#fff"/></svg></a>
        <div class="so-evp-venue__btns">
          <a class="so-evp-btn" href="<?php echo $h($evDirHref); ?>" target="_blank" rel="noopener">Directions<span class="visually-hidden"> to <?php echo $h($eventVenueName); ?> (opens in a new tab)</span></a>
          <?php if ($evVenueHref !== '') { ?><a class="so-evp-btn so-evp-btn--ghost" href="<?php echo $h($evVenueHref); ?>">Venue details</a><?php } ?>
        </div>
      </aside>
      <?php } ?>
    </div>

    <h2 class="so-evp__h">How to buy tickets</h2>
    <ol class="so-evx__steps">
      <li><span class="so-evx__n" aria-hidden="true">1</span><div><strong>Choose how many</strong><p>Tell us how many tickets you need and we&rsquo;ll show you available listings.</p></div></li>
      <li><span class="so-evx__n" aria-hidden="true">2</span><div><strong>Pick your seats</strong><p>Use the map and filters to compare sections, rows and prices.</p></div></li>
      <li><span class="so-evx__n" aria-hidden="true">3</span><div><strong>Check out and go</strong><p>Pay securely and get your tickets before the event.</p></div></li>
    </ol>
  </div>
</section>

<section class="so-evp so-evp--band">
  <div class="container so-evp__wrap">
    <?php echo soSpecPromoCards($evH2['promo'], $evKind === 'concert' ? $evPerfName : $evLabel); ?>
    <?php soBuyerGuaranteeSection(['events' => [$event]]); ?>
  </div>
</section>

<section class="so-evp so-evp--white">
  <div class="container so-evp__wrap">
    <h2 class="so-evp__h"><?php echo $h($evH2['faqs']); ?></h2>
    <div class="so-evfaq so-qa">
      <?php foreach ($evFaqs as $fq) { ?>
      <details class="so-faq" name="so-qa-ev"><summary><h3 style="display:inline;font:inherit;margin:0"><?php echo $h($fq['q']); ?></h3></summary><p><?php echo $h($fq['a']); ?></p></details>
      <?php } ?>
    </div>

    <div class="so-evx__grid so-evp__cards">
        <div class="so-evx__card">
          <h3>What to expect at <?php echo $h($evLabel); ?></h3>
          <p>Choose how many tickets you need, pick seats on the map, then check out. Prices are set by sellers and can be above or below face value. Read how <a href="/ticket-buyer-protection">ticket buyer protection</a> works before you order <?php echo $h($evLabel); ?> tickets.</p>
        </div>
        <div class="so-evx__card">
          <h3>Event details</h3>
          <dl class="so-evx__dl">
            <?php if ($evWhenLong !== '') { ?><dt>Date</dt><dd><?php echo $h($evWhenLong); ?></dd><?php } ?>
            <?php if ($eventTimeText !== '') { ?><dt>Time</dt><dd><?php echo $h($eventTimeText); ?></dd><?php } ?>
            <?php if ($eventVenueName !== '') { ?><dt>Venue</dt><dd><?php echo $evVenueHref !== '' ? '<a href="' . $h($evVenueHref) . '">' . $h($eventVenueName) . '</a>' : $h($eventVenueName); ?></dd><?php } ?>
            <?php if ($evPlaceFull !== '') { ?><dt>City</dt><dd><?php echo $eventCityId ? '<a href="/' . $h($categoryCityPrefix) . '/' . $h(soSlug('city', $eventCityLabel, $eventCityId)) . '">' . $h($evPlaceFull) . '</a>' : $h($evPlaceFull); ?></dd><?php } ?>
            <?php if (!empty($primaryPerformer['id'])) { ?><dt>Performer</dt><dd><a href="/artist/<?php echo $h(soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id'])); ?>"><?php echo $h($primaryPerformer['name']); ?></a></dd><?php } ?>
            <?php if ($evCatName !== '') { ?><dt>Category</dt><dd><?php echo $h($evCatName); ?></dd><?php } ?>
          </dl>
        </div>
        <?php if ($evOtherCard) { ?>
        <div class="so-evx__card">
          <h3>More <?php echo $h($primaryPerformer['name'] ?? $evLabel); ?> dates</h3>
          <ul class="so-evx__rows">
            <?php foreach ($evOtherCard as $oe) { $ots = strtotime($oe['date']['date'] ?? 'now'); $op = (string) ($oe['pricingInfo']['lowPrice']['text']['formatted'] ?? ''); ?>
            <li><a href="/event/<?php echo $h(soEventSlug($oe)); ?>"><span class="so-evx__date"><?php echo $h(strtoupper(date('M j', $ots))); ?></span><span class="so-evx__t"><strong><?php echo $h(trim(($oe['city']['text']['name'] ?? '') . ', ' . ($oe['stateProvince']['text']['abbr'] ?? ''), ', ')); ?></strong><small><?php echo $h($oe['venue']['text']['name'] ?? ''); ?></small></span><?php if ($op !== '') { ?><span class="so-evx__p">From <u><?php echo $h($op); ?></u></span><?php } ?><span class="so-evx__chev" aria-hidden="true">&rsaquo;</span></a></li>
            <?php } ?>
          </ul>
          <?php if (!empty($primaryPerformer['id'])) { ?><a class="so-evx__more" href="/artist/<?php echo $h(soSlug('performer', $primaryPerformer['name'], $primaryPerformer['id'])); ?>">See all <?php echo $h($primaryPerformer['name']); ?> tickets &rsaquo;</a><?php } ?>
        </div>
        <?php } ?>
        <?php if ($evAtVenue) { ?>
        <div class="so-evx__card">
          <h3>Upcoming <?php echo $evKindWord !== '' ? $h($evKindWord) . ' ' : ''; ?>events at this venue</h3>
          <ul class="so-evx__rows so-evx__rows--venue">
            <?php foreach ($evAtVenue as $ve) { $vts = !empty($ve['date']['date']) ? strtotime($ve['date']['date']) : false; ?>
            <li><a href="/event/<?php echo $h(soEventSlug($ve)); ?>"><span class="so-evx__t"><strong><?php echo $h($ve['text']['name']); ?></strong></span><?php if ($vts) { ?><span class="so-evx__when"><?php echo $h(date('D, M j, Y', $vts)) . ' &bull; ' . $h(date('g:i A', $vts)); ?></span><?php } ?><span class="so-evx__chev" aria-hidden="true">&rsaquo;</span></a></li>
            <?php } ?>
          </ul>
          <?php if ($evVenueHref !== '') { ?><a class="so-evx__more" href="<?php echo $h($evVenueHref); ?>">See more events at <?php echo $h($eventVenueName); ?> &rsaquo;</a><?php } ?>
        </div>
        <?php } ?>
    </div>

    <div class="so-evinfo__main so-evp__more so-evv">
      <?php if ($evAboutText !== '') { ?>
      <h2><?php echo $h($evH2['about']); ?></h2>
      <p><?php echo $h($evAboutText); ?></p>
      <?php } ?>
      <?php echo $evVenueInfoHtml; ?>
      <?php if ($evGuideHtml !== '' || $evSimilarHtml !== '') { ?>
      <div class="so-evv-pair<?php echo ($evGuideHtml === '' || $evSimilarHtml === '') ? ' so-evv-pair--one' : ''; ?>"><?php echo $evGuideHtml . $evSimilarHtml; ?></div>
      <?php } ?>
      <?php echo $evKindSection; ?>
    </div>
  </div>
</section>
<?php if ($evJsonLd) { ?><script type="application/ld+json"><?php echo json_encode(['@context' => 'https://schema.org'] + $evJsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script><?php } ?>

<script type="application/json" id="so-event-data"><?php echo json_encode($soEventData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?></script>
<?php /* The seat-map widget script is loaded synchronously right here, exactly as before, and js/event-widget.js (a plain script in the footer, no defer) applies the settings immediately after it, before the page finishes loading, so the settings are in place when the widget starts. If the script is blocked (ad blocker, network), event-widget.js tries once more asynchronously and shows a visible message when that fails too. */ ?>
<?php /* The widget is asked not to bring its own jQuery (includeJQuery=false), so the page must provide it, and before the widget
   script runs. footer.php only loads jQuery on the home, search and about pages, so event pages load it here (once, not deferred). */ ?>
<script src="/lib/jquery/3.7.1/jquery.min.js"></script>
<script src="<?php echo htmlspecialchars($mapScriptUrl, ENT_QUOTES, 'UTF-8'); ?>"></script>

<?php include 'footer.php'; ?>