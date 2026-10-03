<?php
require_once 'functions.php';
$pageMetaTitle       = 'How to Buy Tickets Online | Seat Outlet';
$pageMetaDescription = 'How buying tickets works at Seat Outlet: browse live events, compare seats and prices, and continue to secure checkout in a few clicks.';
$pageCanonicalUrl    = HOME_URL . '/how-to-buy-tickets-online';
include 'header.php';

$soSteps = [
    ['bi-search', 'Browse live events', 'Concerts, sports, theater and festivals from the TicketNetwork marketplace, in one place.'],
    ['bi-geo-alt', 'Search by city or performer', 'Filters take you straight to the event you want, near you or anywhere in the country.'],
    ['bi-columns-gap', 'Compare seats and prices', 'See sections and listed prices side by side and pick the seats that fit your budget.'],
    ['bi-ticket-perforated', 'Check out in a few clicks', 'Choose your seats and continue to the checkout hosted by TicketNetwork.'],
];
$soCats = [
    ['bi-music-note-beamed', 'Concerts and tours', 'Every genre, from small rooms to stadium shows.', '/concert-tickets-for-sale'],
    ['bi-trophy', 'Sports', 'NFL, NBA, MLB, NHL and more.', '/game-day-tickets'],
    ['bi-camera-reels', 'Theater', 'Broadway, touring productions and local stages.', '/buy-broadway-tickets'],
    ['bi-emoji-laughing', 'Comedy and festivals', 'Stand-up, festivals and one-off live events.', '/upcoming-music-festivals'],
];
$soTrust = [
    ['bi-patch-check-fill', 'TicketNetwork marketplace', 'Listings come from sellers on the TicketNetwork marketplace, and orders are covered by its guarantee.', ['/worry-free-guarantee', 'See what is covered']],
    ['bi-shield-lock-fill', 'Secure checkout', 'Checkout is hosted by TicketNetwork. You enter payment details on its checkout page.', ['/privacy-policy', 'How we handle data']],
    ['bi-eye-fill', 'Clear pricing', 'Prices are set by sellers and may be above or below face value. Review the full cost before you pay.', null],
    ['bi-headset', 'Email support', 'Write to us before or after you buy. A member of our team replies by email.', ['/ticket-customer-service', 'Contact support']],
    ['bi-lightning-charge-fill', 'Delivery details', 'Delivery options are shown at checkout, and your confirmation email has the details.', null],
    ['bi-hand-thumbs-up-fill', 'Works on any device', 'A mobile-friendly search and checkout flow, from first search to confirmation.', null],
];
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>

<div class="so-guide">

<section class="so-g-hero">
  <div class="container">
    <p class="so-g-eyebrow">Resale ticket marketplace</p>
    <h2 class="so-g-title">How to buy tickets online</h2>
    <p class="so-g-lead"><?php echo getContentBlock('/what-we-do', 'hero-sub', 'We make ticket buying simple: Seat Outlet connects fans with live events across concerts, sports, theater, and more. Find, compare, and book tickets securely in just a few clicks.'); ?></p>
    <div class="so-g-actions">
      <a href="/buy-tickets-online" class="so-g-btn so-g-btn--primary">Browse events</a>
      <a href="/" class="so-g-btn">Find tickets near you</a>
    </div>
  </div>
</section>

<section class="so-g-sec" id="how">
  <div class="container">
    <h2 class="so-g-h2">Four steps from search to seats</h2>
    <p class="so-g-sub">You can browse without signing up for anything, and there is no newsletter sign-up before checkout.</p>
    <ol class="so-g-rail so-g-rail--steps">
      <?php foreach ($soSteps as $i => [$ic, $t, $d]) { ?>
      <li class="so-g-card">
        <div class="so-g-card__head"><span class="so-g-ic"><i class="bi <?php echo $e($ic); ?>" aria-hidden="true"></i></span><h3><?php echo $e($t); ?></h3></div>
        <p><?php echo $e($d); ?></p>
        <span class="so-g-step" aria-hidden="true"><?php echo $i + 1; ?> of 4</span>
      </li>
      <?php } ?>
    </ol>
  </div>
</section>

<section class="so-g-sec so-g-sec--photo" aria-label="Fans at live events">
  <div class="container">
    <div class="so-g-photos">
      <img src="/images/event-ticket-buying.webp" alt="Making ticket buying simple on Seat Outlet" width="1536" height="1024" loading="lazy" decoding="async">
      <img src="/images/crowd-at-concert-or-event.webp" alt="Fans at a live concert" width="442" height="442" loading="lazy" decoding="async">
      <img src="/images/event-concert.jpg" alt="A concert crowd" width="800" height="512" loading="lazy" decoding="async">
    </div>
  </div>
</section>

<section class="so-g-sec" id="events">
  <div class="container">
    <h2 class="so-g-h2">Live events across the country</h2>
    <p class="so-g-sub">Planning ahead or looking for last-minute tickets, you can find events near you and nationwide.</p>
    <ul class="so-g-rail so-g-rail--cats">
      <?php foreach ($soCats as [$ic, $t, $d, $href]) { ?>
      <li><a class="so-g-card so-g-card--link" href="<?php echo $e($href); ?>">
        <div class="so-g-card__head"><span class="so-g-ic"><i class="bi <?php echo $e($ic); ?>" aria-hidden="true"></i></span><h3><?php echo $e($t); ?></h3></div>
        <p><?php echo $e($d); ?></p>
      </a></li>
      <?php } ?>
    </ul>
  </div>
</section>

<section class="so-g-sec so-g-sec--tint" id="verified">
  <div class="container">
    <h2 class="so-g-h2">Every step, explained</h2>
    <p class="so-g-sub">What to expect before you buy, while you pay and after your order.</p>
    <ul class="so-g-rail so-g-rail--trust" id="security">
      <?php foreach ($soTrust as [$ic, $t, $d, $link]) { ?>
      <li class="so-g-card">
        <div class="so-g-card__head"><span class="so-g-ic"><i class="bi <?php echo $e($ic); ?>" aria-hidden="true"></i></span><h3><?php echo $e($t); ?></h3></div>
        <p><?php echo $e($d); ?></p>
        <?php if ($link) { ?><a class="so-g-more" href="<?php echo $e($link[0]); ?>"><?php echo $e($link[1]); ?> <i class="bi bi-chevron-right" aria-hidden="true"></i></a><?php } ?>
      </li>
      <?php } ?>
    </ul>
  </div>
</section>

<section class="so-g-sec" id="support">
  <div class="container">
    <div class="so-g-cta">
      <div>
        <h2>Book your next event with confidence</h2>
        <p>Browse events, compare sections and prices, and review the full cost at checkout before you pay.</p>
      </div>
      <a href="/" class="so-g-btn so-g-btn--light">Find tickets near you</a>
    </div>
  </div>
</section>

</div>
<?php soSeoCopy('how-to-buy-tickets-online'); ?>
<?php include 'footer.php'; ?>
