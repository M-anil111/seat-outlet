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
$eventSlug  = $hasEvent ? createSlug($eventName, $eventId) : '';

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
include 'header.php';
?>

<section class="checkout-page py-4 py-md-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <h1 class="fs-3 fw-bold mb-1">Review your order</h1>
        <p class="text-muted mb-4">Step 1 of 2 · Next: secure payment</p>

        <?php if (!$hasSelection) { ?>
          <div class="checkout-card">
            <h2 class="fs-5 fw-bold">No tickets selected yet</h2>
            <p class="mb-3">Pick your seats on the event's seat map first; the price and quantity you choose carry over here.</p>
            <?php if ($hasEvent) { ?>
              <a class="btn btn-primary" href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>">Choose seats for <?php echo htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php } else { ?>
              <a class="btn btn-primary" href="/buy-tickets-online">Browse events</a>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="checkout-card">
            <?php if ($hasEvent) { ?>
              <div class="d-flex gap-3 align-items-start">
                <?php if ($eventDate) { ?>
                  <div class="date-box text-center">
                    <div class="month"><?php echo strtoupper(date('M', $eventDate)); ?></div>
                    <div class="day"><?php echo date('d', $eventDate); ?></div>
                  </div>
                <?php } ?>
                <div>
                  <h2 class="fs-5 fw-bold mb-1"><?php echo htmlspecialchars($eventName, ENT_QUOTES, 'UTF-8'); ?></h2>
                  <div class="text-muted"><?php echo $eventDate ? htmlspecialchars(date('l, F j, Y', $eventDate) . ($eventTime ? ' · ' . $eventTime : ''), ENT_QUOTES, 'UTF-8') : ''; ?></div>
                  <div class="text-muted"><?php echo htmlspecialchars(trim($venueName . ' · ' . $cityLabel, ' ·'), ENT_QUOTES, 'UTF-8'); ?></div>
                  <a class="small" href="/event/<?php echo htmlspecialchars($eventSlug, ENT_QUOTES, 'UTF-8'); ?>">Change seats</a>
                </div>
              </div>
              <hr>
            <?php } ?>
            <dl class="row mb-0 checkout-lines">
              <dt class="col-7">Tickets</dt><dd class="col-5 text-end"><?php echo (int) $qty; ?> × <?php echo $prc !== null ? '$' . number_format($prc, 2) : 'price shown at checkout'; ?></dd>
              <?php if ($subtotal !== null) { ?>
                <dt class="col-7">Ticket subtotal</dt><dd class="col-5 text-end">$<?php echo number_format($subtotal, 2); ?></dd>
              <?php } ?>
              <dt class="col-7">Service &amp; delivery fees</dt><dd class="col-5 text-end text-muted">calculated at checkout</dd>
            </dl>
            <p class="small text-muted mt-3 mb-0">Ticket group <?php echo htmlspecialchars($tgid, ENT_QUOTES, 'UTF-8'); ?>. Prices can change until the order is placed; the final total, including all fees, is shown before you pay.</p>
          </div>

          <a id="checkout-continue" class="btn btn-primary btn-lg w-100 mt-3" href="<?php echo htmlspecialchars($handoff, ENT_QUOTES, 'UTF-8'); ?>">Continue to secure payment</a>
          <p class="small text-muted text-center mt-2 mb-0">You'll complete payment on our secure checkout partner page (<?php echo htmlspecialchars(parse_url(TN_CHECKOUT_URL, PHP_URL_HOST), ENT_QUOTES, 'UTF-8'); ?>).</p>
        <?php } ?>
      </div>

      <div class="col-lg-5">
        <div class="checkout-card">
          <h2 class="fs-6 fw-bold mb-3">What happens next</h2>
          <ol class="checkout-steps ps-3 mb-0">
            <li>Enter your details and pay on the secure checkout.</li>
            <li>Your order is confirmed by email within minutes.</li>
            <li>Tickets are delivered by the method shown at checkout (mobile transfer, e-ticket or shipping), usually before the event date.</li>
          </ol>
        </div>
        <div class="checkout-card mt-3">
          <h2 class="fs-6 fw-bold mb-2">Buyer Protection Guarantee</h2>
          <p class="mb-2">Every order is backed by our guarantee: valid tickets, delivered in time for the event, or your money back.</p>
          <a href="/ticket-buyer-protection" class="small">Read the guarantee</a>
        </div>
      </div>
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
