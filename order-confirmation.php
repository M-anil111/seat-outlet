<?php
require_once 'functions.php';

/*
|--------------------------------------------------------------------------
| /order-confirmation - "purchase complete"
|--------------------------------------------------------------------------
| The page the hosted checkout should send buyers back to once an order is
| placed (TicketNetwork configures the post-purchase redirect per website
| config; ask them to point it here with the order number). It never trusts
| the URL for anything money-related: the order number is displayed, the
| total only feeds the GA4 purchase event when present, and nothing here
| grants access to tickets.
|
| Accepted parameters: oid | orderId | order (order number), total (order
| total), email (masked when shown), eid (event id, for the summary).
*/

$orderIn = $_GET['oid'] ?? $_GET['orderId'] ?? $_GET['order'] ?? '';
$orderNumber = is_string($orderIn) ? trim($orderIn) : '';
$orderNumber = preg_replace('/[^A-Za-z0-9\-]/', '', $orderNumber);
$total = is_string($_GET['total'] ?? null) && is_numeric($_GET['total']) ? round((float) $_GET['total'], 2) : null;
// The URL can be typed by anyone, so it must not feed absurd values into revenue reporting: out-of-range totals and
// implausible order numbers are ignored (the real fix is a server-confirmed purchase signal, see docs/launch-checklist.md).
if ($total !== null && ($total <= 0 || $total > 25000)) $total = null;
if (strlen($orderNumber) < 4 || strlen($orderNumber) > 40) { $orderNumber = ''; $total = null; }
$email = is_string($_GET['email'] ?? null) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL) ? $_GET['email'] : '';
$eventId = (int) ($_GET['eid'] ?? 0);
$event = $eventId > 0 ? getTnEventById($eventId) : [];
$hasEvent = !empty($event['text']['name']);

$maskedEmail = '';
if ($email !== '') {
    [$local, $domain] = explode('@', $email, 2);
    $maskedEmail = substr($local, 0, 2) . str_repeat('•', max(1, strlen($local) - 2)) . '@' . $domain;
}

// Cross-sell from the already-cached homepage feeds (no API call).
$moreEvents = [];
foreach (['concerts', 'sports', 'theatre'] as $tab) {
    foreach (array_slice(cache_get('home_events_' . $tab, 30 * 86400) ?: [], 0, 2) as $ev) {
        if (!empty($ev['id']) && $ev['id'] !== $eventId) $moreEvents[] = $ev;
    }
}

$pageMetaTitle       = 'Order Confirmed | Seat Outlet';
$pageMetaDescription = 'Your Seat Outlet order is confirmed.';
$pageCanonicalUrl    = HOME_URL . '/order-confirmation';
$pageRobots          = 'noindex, nofollow';
include 'header.php';
?>

<section class="confirmation-page py-4 py-md-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="checkout-card text-center text-lg-start">
          <div class="confirmation-check mx-auto mx-lg-0" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
          <h1 class="fs-3 fw-bold mt-3 mb-1">Purchase complete</h1>
          <?php if ($orderNumber !== '') { ?>
            <p class="mb-2">Order number <strong><?php echo htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8'); ?></strong></p>
          <?php } ?>
          <p class="text-muted mb-0">
            A confirmation email<?php echo $maskedEmail !== '' ? ' is on its way to <strong>' . htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8') . '</strong>' : ' is on its way'; ?>. Keep it: it has your order number and delivery details.
          </p>
        </div>

        <?php if ($hasEvent) {
          $ts = !empty($event['date']['date']) ? strtotime($event['date']['date']) : false;
        ?>
          <div class="checkout-card mt-3">
            <h2 class="fs-6 fw-bold mb-2">Your event</h2>
            <div class="fw-semibold"><?php echo htmlspecialchars($event['text']['name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="text-muted"><?php echo $ts ? htmlspecialchars(date('l, F j, Y', $ts) . (!empty($event['date']['text']['time']) ? ' · ' . $event['date']['text']['time'] : ''), ENT_QUOTES, 'UTF-8') : ''; ?></div>
            <div class="text-muted"><?php echo htmlspecialchars(trim(($event['venue']['text']['name'] ?? '') . ' · ' . ($event['city']['text']['name'] ?? '') . ', ' . ($event['stateProvince']['text']['abbr'] ?? ''), ' ·,'), ENT_QUOTES, 'UTF-8'); ?></div>
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
            <h2 class="fs-6 fw-bold mb-3">Going to more shows?</h2>
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

<?php if ($orderNumber !== '') { ?>
<script>
(function () {
  // GA4 purchase event, once per order number per browser.
  try {
    var key = 'so_purchase_' + <?php echo json_encode($orderNumber); ?>;
    if (window.localStorage && localStorage.getItem(key)) return;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'purchase',
      ecommerce: {
        transaction_id: <?php echo json_encode($orderNumber); ?>,
        value: <?php echo json_encode($total); ?>,
        currency: 'USD',
        affiliation: 'Seat Outlet',
        items: [<?php echo $hasEvent ? json_encode(['item_id' => (string) $eventId, 'item_name' => $event['text']['name']]) : ''; ?>]
      }
    });
    if (window.localStorage) localStorage.setItem(key, '1');
  } catch (e) {}
})();
</script>
<?php } ?>

<?php include 'footer.php'; ?>
