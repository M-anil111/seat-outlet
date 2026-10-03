<?php
require_once 'functions.php';

/*
|--------------------------------------------------------------------------
| /order-confirmation - where the hosted checkout sends buyers back to
|--------------------------------------------------------------------------
| TicketNetwork configures the post-purchase redirect per website config.
| Anyone can open this URL with any parameters, so nothing here is trusted
| for anything money-related and nothing grants access to tickets:
|   - the page says "Purchase complete" only for a plausible order number
|     AND a visitor who really clicked Buy on one of our event pages in the
|     last 24 hours (first-party cookie `so_checkout`, set by
|     js/event-widget.js; the cookie is used up when the purchase event
|     is written, so a refresh or a shared link cannot repeat it);
|   - the GA4 `purchase` event is written only in that case, once per order
|     number, with the total only if it is a sane amount;
|   - the e-mail address is never read from the URL (it ended up in logs and
|     referrers) and never echoed or pushed.
| This is hardening, not proof: real revenue attribution needs TicketNetwork's
| server-side order webhook (docs/launch-checklist.md).
|
| Accepted parameters: oid | orderId | order (order number), total, eid (event id, for the summary and the calendar).
*/

const SO_CHECKOUT_COOKIE_MAX_AGE = 86400;      // a purchase can only follow a Buy click made within the last 24 hours
const SO_PURCHASE_MAX_TOTAL = 25000;           // dollars; anything above is treated as not credible and sent without a value

$orderNumber = trim((string) ($_GET['oid'] ?? $_GET['orderId'] ?? $_GET['order'] ?? ''));
$orderNumber = preg_replace('/[^A-Za-z0-9\-]/', '', $orderNumber);
// A plausible order number: 4 to 40 characters with at least one digit. Anything else is treated as "no order number".
if (!preg_match('/^(?=.*\d)[A-Za-z0-9\-]{4,40}$/', $orderNumber)) $orderNumber = '';
$totalRaw = $_GET['total'] ?? null;
$total = (is_string($totalRaw) && preg_match('/^\d{1,6}(\.\d{1,2})?$/', $totalRaw) && (float) $totalRaw > 0 && (float) $totalRaw <= SO_PURCHASE_MAX_TOTAL) ? round((float) $totalRaw, 2) : null;
$eventId = (int) ($_GET['eid'] ?? 0);
if ($eventId < 0 || $eventId > 2000000000) $eventId = 0;

// The Buy-click marker: "<unix time>.<event id>".
$cookieOk = false; $cookieEvent = 0;
if (!empty($_COOKIE['so_checkout']) && preg_match('/^(\d{9,11})\.(\d{1,10})$/', (string) $_COOKIE['so_checkout'], $cm)) {
    $age = time() - (int) $cm[1];
    $cookieEvent = (int) $cm[2];
    $cookieOk = $age >= -300 && $age <= SO_CHECKOUT_COOKIE_MAX_AGE && ($eventId === 0 || $eventId === $cookieEvent);
}
if ($eventId === 0 && $cookieOk) $eventId = $cookieEvent;   // the redirect may not carry the event id: the Buy click did
$confirmed = $orderNumber !== '' && $cookieOk;           // show "Purchase complete" and fire the purchase event
$state = $confirmed ? 'complete' : ($orderNumber !== '' ? 'received' : 'neutral');

$event = $eventId > 0 ? getTnEventById($eventId) : [];
$hasEvent = !empty($event['text']['name']) && !tnEntityMissing($event);
$evTs = $hasEvent && !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
$venueName = $hasEvent ? (string) ($event['venue']['text']['name'] ?? '') : '';
$cityLabel = $hasEvent ? trim((string) ($event['city']['text']['name'] ?? '') . ', ' . (string) ($event['stateProvince']['text']['abbr'] ?? ''), ', ') : '';

// Only the buyer's own browser may fire the purchase event, once: use the marker up now (the page is rendered with it present, so the script below runs once).
$firePurchase = $confirmed;
if ($firePurchase && !headers_sent()) {
    setcookie('so_checkout', '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
}

// More to see: other dates of the same performer first (cached API call), then the already-cached homepage feeds.
$moreEvents = [];
$moreFromPerformer = 0;
$perfId = $hasEvent ? (int) ($event['performers'][0]['id'] ?? 0) : 0;
if ($perfId > 0) {
    [, $perfResp] = getPerformerPageEvents($perfId, 8);
    foreach (($perfResp['results'] ?? []) as $oe) {
        if ((int) ($oe['id'] ?? 0) === $eventId || count($moreEvents) >= 4) continue;
        $ots = !empty($oe['date']['date']) ? strtotime($oe['date']['date']) : false;
        $moreEvents[] = ['id' => (int) $oe['id'], 'name' => (string) ($oe['text']['name'] ?? ''), 'date' => $ots ? date('M j, Y', $ots) : '',
            'loc' => trim((string) ($oe['city']['text']['name'] ?? '') . ', ' . (string) ($oe['stateProvince']['text']['abbr'] ?? ''), ', '),
            'price' => (string) ($oe['pricingInfo']['lowPrice']['text']['formatted'] ?? '')];
    }
    $moreFromPerformer = count($moreEvents);
}
if (count($moreEvents) < 4) {
    foreach (['concerts', 'sports', 'theatre'] as $tab) {
        foreach (array_slice(cache_get('home_events_' . $tab, 30 * 86400) ?: [], 0, 2) as $ev) {
            if (!empty($ev['id']) && (int) $ev['id'] !== $eventId && count($moreEvents) < 5) $moreEvents[] = $ev;
        }
    }
}
$moreHeading = $moreFromPerformer > 0 && !empty($event['performers'][0]['name']) ? 'More ' . $event['performers'][0]['name'] . ' dates and shows' : 'Going to more shows?';

$soConfEvent = null;
if ($hasEvent) {
    $slug = createSlug($event['text']['name'], $eventId);
    $soConfEvent = [
        'id' => $eventId, 'name' => (string) $event['text']['name'], 'slug' => $slug, 'url' => rtrim(HOME_URL, '/') . '/event/' . $slug,
        'date' => (string) ($event['date']['date'] ?? ''), 'start' => (string) ($event['date']['datetimeOffset'] ?? ''),
        'allDay' => (($event['date']['time'] ?? '') === '' || ($event['date']['time'] ?? '') === '00:00:00'),
        'venue' => $venueName, 'city' => $cityLabel, 'noRemember' => true,
    ];
}

$pageMetaTitle       = $state === 'complete' ? 'Order Confirmed | Seat Outlet' : 'Thank You | Seat Outlet';
$pageMetaDescription = 'Thank you for your order with Seat Outlet.';
$pageCanonicalUrl    = HOME_URL . '/order-confirmation';
$pageRobots          = 'noindex, nofollow';
$pageFocusKeyword    = $state === 'complete' ? 'Order confirmation' : 'Thank you';   // the strip above the header is the page's H1
$GLOBALS['soNoAds'] = true;   // no ads on the pages where someone is paying or has paid
include 'header.php';
?>

<section class="confirmation-page py-4 py-md-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="checkout-card text-center text-lg-start">
          <div class="confirmation-check mx-auto mx-lg-0" aria-hidden="true"><i class="bi bi-<?php echo $state === 'neutral' ? 'envelope' : 'check-lg'; ?>"></i></div>
          <?php if ($state === 'complete') { ?>
            <h1 class="fs-3 fw-bold mt-3 mb-1">Purchase complete</h1>
          <?php } elseif ($state === 'received') { ?>
            <h1 class="fs-3 fw-bold mt-3 mb-1">Thank you</h1>
          <?php } else { ?>
            <h1 class="fs-3 fw-bold mt-3 mb-1">Thank you</h1>
            <p class="mb-2">Looking for your order? Your confirmation email has the order number and delivery details.</p>
          <?php } ?>
          <?php if ($orderNumber !== '') { ?>
            <p class="mb-2">Order number <strong><?php echo htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8'); ?></strong></p>
          <?php } ?>
          <p class="text-muted mb-0">
            <?php echo $state === 'neutral' ? 'If you have just placed an order, a confirmation email is on its way to the address you used at checkout. Check your spam folder if you do not see it within a few minutes.' : 'A confirmation email is on its way to the address you used at checkout. Keep it: it has your order number and delivery details.'; ?>
          </p>
        </div>

        <?php if ($hasEvent) { ?>
          <div class="checkout-card mt-3">
            <h2 class="fs-6 fw-bold mb-2">Your event</h2>
            <div class="fw-semibold"><?php echo htmlspecialchars($event['text']['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="text-muted"><?php echo $evTs ? htmlspecialchars(date('l, F j, Y', $evTs) . (!empty($event['date']['text']['time']) ? ' · ' . $event['date']['text']['time'] : ''), ENT_QUOTES, 'UTF-8') : ''; ?></div>
            <div class="text-muted"><?php echo htmlspecialchars(trim($venueName . ' · ' . $cityLabel, ' ·,'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="so-evhero__actions mt-3">
              <?php if ($evTs) { ?>
                <div class="so-cal" data-so-cal>
                  <button type="button" class="so-action" data-so-cal-toggle aria-expanded="false"><i class="bi bi-calendar-plus" aria-hidden="true"></i><span>Add to calendar</span></button>
                  <div class="so-cal__menu" hidden>
                    <a data-cal="google" target="_blank" rel="noopener">Google Calendar</a>
                    <a data-cal="outlook" target="_blank" rel="noopener">Outlook.com</a>
                    <a data-cal="office" target="_blank" rel="noopener">Microsoft 365</a>
                    <a data-cal="yahoo" target="_blank" rel="noopener">Yahoo Calendar</a>
                    <button type="button" data-cal="ics">Apple Calendar or other (.ics file)</button>
                  </div>
                </div>
              <?php } ?>
              <button type="button" class="so-action" data-so-share><i class="bi bi-share" aria-hidden="true"></i><span>Share with friends</span></button>
              <?php if ($venueName !== '') { ?>
                <a class="so-action" href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo htmlspecialchars(rawurlencode(trim($venueName . ' ' . $cityLabel)), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Directions and parking</span></a>
              <?php } ?>
            </div>
            <span class="visually-hidden" id="so-action-status" role="status" aria-live="polite"></span>
            <p class="small text-muted mt-2 mb-0">Going with someone? Share sends them the event link.</p>
          </div>
        <?php } ?>

        <div class="checkout-card mt-3">
          <h2 class="fs-6 fw-bold mb-3">What happens next</h2>
          <ol class="checkout-steps ps-3 mb-0">
            <li><strong>Confirmation email.</strong> Arrives within a few minutes. Check spam if you don't see it.</li>
            <li><strong>Delivery.</strong> Tickets arrive by the method chosen at checkout: mobile transfer or e-ticket by email, or shipping to the address you gave. Some sellers release tickets closer to the event date; the email states the expected delivery window.</li>
            <li><strong>On the day.</strong> Have the tickets ready on your phone or printed, and arrive early for entry.</li>
          </ol>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="checkout-card">
          <h2 class="fs-6 fw-bold mb-2">Need help with this order?</h2>
          <p class="mb-2">Quote your order number so we can find it quickly.</p>
          <a href="/ticket-customer-service" class="btn btn-outline-secondary w-100">Contact support</a>
          <a href="/ticket-buyer-protection" class="d-block small mt-3">Buyer Protection Guarantee</a>
          <a href="/ticket-faq" class="d-block small mt-1">Delivery and refund FAQs</a>
        </div>

        <?php if (!empty($moreEvents)) { ?>
          <div class="checkout-card mt-3">
            <h2 class="fs-6 fw-bold mb-3"><?php echo htmlspecialchars($moreHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
            <ul class="list-unstyled mb-0 confirmation-more">
              <?php foreach (array_slice($moreEvents, 0, 5) as $ev) { ?>
                <li class="mb-2">
                  <a href="/event/<?php echo htmlspecialchars(createSlug($ev['name'], $ev['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ev['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                  <div class="small text-muted"><?php echo htmlspecialchars(trim(($ev['date'] ?? '') . ' · ' . ($ev['loc'] ?? ''), ' ·'), ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($ev['price']) ? ' · from ' . htmlspecialchars($ev['price'], ENT_QUOTES, 'UTF-8') : ''; ?></div>
                </li>
              <?php } ?>
            </ul>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</section>

<?php if ($soConfEvent) { ?>
<script type="application/json" id="so-event-data"><?php echo json_encode($soConfEvent, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?></script>
<script src="<?php echo htmlspecialchars(soAsset('js/event-actions.js'), ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php } ?>

<?php if ($firePurchase) { ?>
<script>
(function () {
  // GA4 purchase event: only for a visitor who clicked Buy here in the last 24 hours (checked on the server), once per order number in this browser.
  // The value is the total from the return link when it is a sane amount; it is NOT verified (see docs/launch-checklist.md, TicketNetwork order webhook).
  try {
    var key = 'so_purchase_' + <?php echo json_encode($orderNumber); ?>;
    if (window.localStorage && localStorage.getItem(key)) return;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    var ec = {
      transaction_id: <?php echo json_encode($orderNumber); ?>,
      currency: 'USD',
      affiliation: 'Seat Outlet',
      items: [<?php echo $hasEvent ? json_encode(['item_id' => (string) $eventId, 'item_name' => $event['text']['name'], 'affiliation' => 'Seat Outlet']) : ''; ?>]
    };
    <?php if ($total !== null) { ?>ec.value = <?php echo json_encode($total); ?>;<?php } ?>

    window.dataLayer.push({ event: 'purchase', ecommerce: ec, value_verified: false });
    if (window.localStorage) localStorage.setItem(key, '1');
  } catch (e) {}
})();
</script>
<?php } ?>

<?php include 'footer.php'; ?>
