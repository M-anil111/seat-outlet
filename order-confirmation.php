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
|     last 24 hours (first-party cookie `so_checkout`, set by js/event-widget.js);
|   - an unsigned return (?oid=&total=) NEVER writes a GA4 `purchase`: it writes
|     `order_return` (no value) and `purchase_pending`, once per order per browser;
|   - the `purchase` event is written only for an order verified outside the
|     browser (inc/order-verify.php: a signed return link, or the order webhook
|     /ajax/tn-order-webhook.php), once per order (verified_orders.ga4_fired_at is
|     claimed atomically), with the VERIFIED total, never the URL's;
|   - the e-mail address is never read from the URL and never echoed or pushed.
| Verification needs TN_RETURN_SECRET or TN_WEBHOOK_SECRET and a sender that signs
| (docs/launch-checklist.md); without them every return stays pending.
|
| Accepted parameters: oid | orderId | order (order number), eid (event id, for the summary and the calendar); total, ts and sig only with a signature.
*/

const SO_CHECKOUT_COOKIE_MAX_AGE = 86400;      // a purchase can only follow a Buy click made within the last 24 hours
require_once __DIR__ . '/inc/order-verify.php';
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');

$orderNumber = soOrderNumber($_GET['oid'] ?? $_GET['orderId'] ?? $_GET['order'] ?? '');   // "no order number" when implausible
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
$confirmed = $orderNumber !== '' && $cookieOk;           // show "Purchase complete" (a display state only: revenue needs soOrderDecide() to verify)
$state = $confirmed ? 'complete' : ($orderNumber !== '' ? 'received' : 'neutral');

$event = $eventId > 0 ? getTnEventById($eventId) : [];
$hasEvent = !empty($event['text']['name']) && !tnEntityMissing($event);
$evTs = $hasEvent && !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
$venueName = $hasEvent ? (string) ($event['venue']['text']['name'] ?? '') : '';
$cityLabel = $hasEvent ? trim((string) ($event['city']['text']['name'] ?? '') . ', ' . (string) ($event['stateProvince']['text']['abbr'] ?? ''), ', ') : '';

// Verified or not? A signed return link or the order webhook makes an order verified; the URL's total is never used as revenue.
$soDb = (defined('MYSQLI') && MYSQLI instanceof mysqli) ? MYSQLI : null;
$soVerdict = ['oid' => $orderNumber, 'verified' => false, 'value' => null, 'eid' => 0, 'reason' => $orderNumber === '' ? 'no_order' : 'unverified'];
if ($orderNumber !== '' && $soDb) {
    try {
        $soVerdict = soOrderDecide($_GET, $cookieOk, (string) getenv('TN_RETURN_SECRET'),
            fn($o, $e, $t) => soOrderRecordVerified($soDb, $o, $e, $t, 'signed_return'),
            fn($o) => soOrderLookup($soDb, $o));
    } catch (Throwable $t) { /* no database: the order stays unverified and no purchase is written */ }
}
// Only the buyer's own browser may fire the purchase event, once per order: the claim is one atomic UPDATE, and the Buy-click marker is used up with it.
$firePurchase = false; $purchaseValue = null;
if ($soVerdict['verified'] && $soDb) {
    try { $firePurchase = soOrderClaimPurchase($soDb, $orderNumber); } catch (Throwable $t) { $firePurchase = false; }
    if ($firePurchase) {
        $purchaseValue = $soVerdict['value'];
        if (!headers_sent()) setcookie('so_checkout', '', ['expires' => time() - 3600, 'path' => '/', 'samesite' => 'Lax']);
    }
}
// Unverified (or already counted) return: tell analytics the buyer came back, without revenue.
$fireReturn = $orderNumber !== '' && !$firePurchase;
$returnEvent = $soVerdict['verified'] ? 'order_return' : 'purchase_pending';

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
        foreach (array_slice(cache_get('home_events_' . $tab, 2 * 86400) ?: [], 0, 2) as $ev) {
            if (!empty($ev['id']) && (int) $ev['id'] !== $eventId && count($moreEvents) < 5) $moreEvents[] = $ev;
        }
    }
}
$moreHeading = $moreFromPerformer > 0 && !empty($event['performers'][0]['name']) ? 'More ' . $event['performers'][0]['name'] . ' dates and shows' : 'Going to more shows?';

$soConfEvent = null;
if ($hasEvent) {
    $slug = soEventSlug($event);
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
                  <a href="/event/<?php echo htmlspecialchars(soEventSlug($ev), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ev['name'], ENT_QUOTES, 'UTF-8'); ?></a>
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

<?php if ($firePurchase || $fireReturn) { ?>
<script>
(function () {
  // GA4: `purchase` only for an order verified on the server and claimed once (value = the verified total). Anything else is an
  // `order_return` / `purchase_pending` event with no value, once per order in this browser. The URL's total is never read.
  try {
    window.dataLayer = window.dataLayer || [];
    <?php if ($firePurchase) { ?>
    window.dataLayer.push({ ecommerce: null });
    var ec = {
      transaction_id: <?php echo json_encode($orderNumber); ?>,
      currency: 'USD',
      affiliation: 'Seat Outlet',
      items: [<?php echo $hasEvent ? json_encode(['item_id' => (string) $eventId, 'item_name' => $event['text']['name'], 'affiliation' => 'Seat Outlet']) : ''; ?>]
    };
    <?php if ($purchaseValue !== null) { ?>ec.value = <?php echo json_encode($purchaseValue); ?>;<?php } ?>

    window.dataLayer.push({ event: 'purchase', ecommerce: ec, value_verified: true });
    <?php } else { ?>
    var key = 'so_return_' + <?php echo json_encode($orderNumber); ?>;
    if (window.localStorage && localStorage.getItem(key)) return;
    window.dataLayer.push({ event: <?php echo json_encode($returnEvent); ?>, transaction_id: <?php echo json_encode($orderNumber); ?>, event_id: <?php echo json_encode((string) $eventId); ?>, value_verified: false });
    if (window.localStorage) localStorage.setItem(key, '1');
    <?php } ?>
  } catch (e) {}
})();
</script>
<?php } ?>

<?php include 'footer.php'; ?>
