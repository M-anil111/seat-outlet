<?php
require_once 'functions.php';

/*
|--------------------------------------------------------------------------
| /checkout - order review and hand-off to the secure checkout
|--------------------------------------------------------------------------
| Where the money changes hands today: TicketNetwork's hosted white-label
| checkout (TN_CHECKOUT_URL, the host the Seatics widget deep-links to with
| ?tgid=&qty=&prc=). That checkout locks the ticket group, takes payment,
| runs fraud screening (Riskified) and handles delivery. Building all of
| that here needs the Mercury API, which this API subscription does not
| have (every /mercury/v5 call returns 403 "API Subscription validation
| failed" - verified). Until it is enabled this page is the branded order
| review step: it confirms what the visitor picked, sets expectations on
| fees/delivery, fires begin_checkout, and continues to the hosted
| checkout with the same parameters. When Mercury is enabled, the ticket
| group lookup, hold, payment and order creation slot in here.
|
| Parameters (all from the widget deep link / our own links):
|   eid   catalog event id            tgid  ticket group id
|   qty   quantity                    prc   expected per-ticket price
*/

$eventId = (int) ($_GET['eid'] ?? 0);
$tgid    = preg_replace('/[^0-9]/', '', (string) ($_GET['tgid'] ?? ''));
$qty     = max(0, min(50, (int) ($_GET['qty'] ?? 0)));
$prc     = is_numeric($_GET['prc'] ?? null) ? round((float) $_GET['prc'], 2) : null;

$event = $eventId > 0 ? getTnEventById($eventId) : [];
$hasEvent = !empty($event['text']['name']);
$hasSelection = $tgid !== '' && $qty > 0;

$eventName  = $event['text']['name'] ?? '';
$eventDate  = !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
$eventTime  = $event['date']['text']['time'] ?? '';
$venueName  = $event['venue']['text']['name'] ?? '';
$cityLabel  = trim(($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
$eventSlug  = $hasEvent ? soEventSlug($event) : '';

$handoff = TN_CHECKOUT_URL . '/?' . http_build_query(array_filter([
    'tgid' => $tgid !== '' ? $tgid : null,
    'qty'  => $qty > 0 ? $qty : null,
    'prc'  => $prc !== null ? number_format($prc, 2, '.', '') : null,
]));
$subtotal = ($prc !== null && $qty > 0) ? $prc * $qty : null;

$pageMetaTitle       = 'Secure Checkout | Seat Outlet';
$pageMetaDescription = 'Review your ticket selection and continue to secure checkout.';
$pageCanonicalUrl    = HOME_URL . '/checkout';
$pageRobots          = 'noindex, nofollow';
$GLOBALS['soNoAds'] = true;   // no ads on the pages where someone is paying or has paid
include 'header.php';
?>

<link rel="stylesheet" href="<?php echo htmlspecialchars(soAsset('css/checkout.css'), ENT_QUOTES, 'UTF-8'); ?>">
<section class="checkout-page so-co" aria-labelledby="soCoTitle">
  <div class="container so-co__wrap">
    <p class="so-co__secure"><svg width="16" height="18" viewBox="0 0 16 18" fill="currentColor" aria-hidden="true"><path d="M8 0a4.5 4.5 0 0 0-4.5 4.5V7H2.5A1.5 1.5 0 0 0 1 8.5v8A1.5 1.5 0 0 0 2.5 18h11a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 13.5 7h-1V4.5A4.5 4.5 0 0 0 8 0Zm-2.5 4.5a2.5 2.5 0 0 1 5 0V7h-5V4.5Z"/></svg> Secure Checkout</p>

    <div class="so-co__grid">
      <div class="so-co__main">
        <div class="so-co__card so-co__step">
          <div class="so-co__step-head">
            <span class="so-co__num" aria-hidden="true">1</span>
            <div>
              <h1 id="soCoTitle" class="so-co__title">Review your order</h1>
              <p class="so-co__sub">Step 1 of 2 · Next: secure payment</p>
            </div>
          </div>

          <?php if (!$hasSelection) { ?>
            <div class="so-co__empty">
              <h2 class="so-co__h2">No tickets selected yet</h2>
              <p>Pick your seats on the event's seat map first; the price and quantity you choose carry over here.</p>
              <?php if ($hasEvent) { ?>
                <a class="so-co__btn" href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>">Choose seats for <?php echo htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8'); ?></a>
              <?php } else { ?>
                <a class="so-co__btn" href="/buy-tickets-online">Browse events</a>
              <?php } ?>
            </div>
          <?php } else { ?>
            <h2 class="so-co__h2">Your tickets</h2>
            <dl class="so-co__lines">
              <div><dt>Tickets</dt><dd><?php echo (int) $qty; ?> × <?php echo $prc !== null ? '$' . number_format($prc, 2) : 'price shown at checkout'; ?></dd></div>
              <?php if ($subtotal !== null) { ?>
                <div><dt>Ticket subtotal</dt><dd>$<?php echo number_format($subtotal, 2); ?></dd></div>
              <?php } ?>
              <div><dt>Service &amp; delivery fees</dt><dd class="so-co__muted">calculated at checkout</dd></div>
            </dl>
            <p class="so-co__note"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 11v6M12 7.5v.01"/></svg><span>Ticket group <?php echo htmlspecialchars($tgid, ENT_QUOTES, 'UTF-8'); ?>. Prices can change until the order is placed; the final total, including all fees, is shown before you pay.</span></p>

            <div class="so-co__actions">
              <a id="checkout-continue" class="so-co__btn" href="<?php echo htmlspecialchars($handoff, ENT_QUOTES, 'UTF-8'); ?>">Continue to secure payment <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
              <p class="so-co__partner">You'll complete payment on our secure checkout partner page (<?php echo htmlspecialchars(parse_url(TN_CHECKOUT_URL, PHP_URL_HOST), ENT_QUOTES, 'UTF-8'); ?>).</p>
            </div>
          <?php } ?>
        </div>

        <div class="so-co__card so-co__step so-co__step--next">
          <div class="so-co__step-head">
            <span class="so-co__num so-co__num--off" aria-hidden="true">2</span>
            <div>
              <h2 class="so-co__title so-co__title--sm">Payment</h2>
              <p class="so-co__sub">Delivery details and payment on the secure checkout</p>
            </div>
          </div>
        </div>
      </div>

      <aside class="so-co__side" aria-label="Order details">
        <?php if ($hasEvent) { ?>
          <div class="so-co__card so-co__event">
            <div class="so-co__event-head">
              <svg class="so-co__ticket" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5V7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v2.5a2.5 2.5 0 0 0 0 5V17a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-2.5a2.5 2.5 0 0 0 0-5Z" transform="rotate(-35 12 12)"/><path d="M14 7.5v9" stroke-dasharray="1.6 2" transform="rotate(-35 12 12)"/></svg>
              <div>
                <h2 class="so-co__event-name"><?php echo htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8'); ?></h2>
                <?php if ($eventDate) { ?><p class="so-co__event-meta"><?php echo htmlspecialchars(date('l, F j, Y', $eventDate) . ($eventTime ? ' at ' . $eventTime : ''), ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
                <p class="so-co__event-meta"><?php echo htmlspecialchars(trim($venueName . (($venueName !== '' && $cityLabel !== '') ? ' in ' : '') . $cityLabel), ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="so-co__change" href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>">Change seats</a>
              </div>
            </div>
            <?php if ($hasSelection) { ?>
              <div class="so-co__facts">
                <div><span>Quantity</span><strong><?php echo (int) $qty; ?></strong></div>
                <div><span>Price each</span><strong><?php echo $prc !== null ? '$' . number_format($prc, 2) : 'At checkout'; ?></strong></div>
              </div>
              <p class="so-co__fine">All prices are in US Dollars ($).</p>
            <?php } ?>
          </div>
        <?php } ?>

        <?php if ($hasSelection) { ?>
          <div class="so-co__card so-co__summary">
            <h2 class="so-co__h2">Order Summary</h2>
            <div class="so-co__total"><span><?php echo $subtotal !== null ? 'Ticket subtotal:' : 'Order total:'; ?></span><strong><?php echo $subtotal !== null ? '$' . number_format($subtotal, 2) . ' USD' : 'Shown at checkout'; ?></strong></div>
            <p class="so-co__fine">Service and delivery fees are added on the secure checkout, and the final total is shown before you pay.</p>
          </div>
        <?php } ?>

        <div class="so-co__card so-co__info">
          <h2 class="so-co__h3">What happens next</h2>
          <ol class="so-co__steps">
            <li>Enter your details and pay on the secure checkout.</li>
            <li>Your order is confirmed by email within minutes.</li>
            <li>Tickets are delivered by the method shown at checkout (mobile transfer, e-ticket or shipping), usually before the event date.</li>
          </ol>
          <h2 class="so-co__h3 so-co__h3--gap">Buyer Protection Guarantee</h2>
          <p>Every order is backed by our guarantee: valid tickets, delivered in time for the event, or your money back.</p>
          <a href="/ticket-buyer-protection" class="so-co__link">Read the guarantee</a>
        </div>
      </aside>
    </div>
  </div>
</section>

<script>
(function () {
  window.dataLayer = window.dataLayer || [];
  var item = {
    item_id: '<?php echo (int) $eventId; ?>',
    item_name: <?php echo json_encode($eventName); ?>,
    item_variant: <?php echo json_encode($tgid); ?>,
    price: <?php echo json_encode($prc); ?>,
    quantity: <?php echo (int) $qty; ?>
  };
  <?php if ($hasSelection) { ?>
  window.dataLayer.push({ event: 'view_cart', ecommerce: { currency: 'USD', value: <?php echo json_encode($subtotal); ?>, items: [item] } });
  var btn = document.getElementById('checkout-continue');
  if (btn) btn.addEventListener('click', function () {
    window.dataLayer.push({ event: 'begin_checkout', ecommerce: { currency: 'USD', value: <?php echo json_encode($subtotal); ?>, items: [item] } });
  });
  <?php } ?>
})();
</script>

<?php include 'footer.php'; ?>
