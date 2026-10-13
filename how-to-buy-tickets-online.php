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
    ['bi-music-note-beamed', 'Concerts and tours', 'Every genre, from small rooms to stadium shows.', '/concert-tickets-for-sale', ['/images/event-concert.jpg', 'A concert crowd', 800, 512]],
    ['bi-trophy', 'Sports', 'NFL, NBA, MLB, NHL and more.', '/game-day-tickets', ['/images/home-cat-sports.webp', 'A packed stadium under the lights', 480, 270]],
    ['bi-camera-reels', 'Theater', 'Broadway, touring productions and local stages.', '/buy-broadway-tickets', ['/images/home-cat-theater.webp', 'A theater stage with a red curtain', 480, 270]],
    ['bi-emoji-laughing', 'Comedy and festivals', 'Stand-up, festivals and one-off live events.', '/upcoming-music-festivals', ['/images/home-cat-festivals.webp', 'A festival crowd under stage lights', 480, 270]],
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

<style>
/* ===== How to buy tickets online: content between header and footer (scoped .so-htb) ===== */
.so-htb { --htb-blue: #2556e0; --htb-navy: #0f1b3d; --htb-text: #3f4a5e; --htb-line: #e3e8f2; --htb-pale: #f1f5ff; --htb-tint: #f6f8fc; background: #fff; color: var(--htb-text); }
.so-htb .container { max-width: 1140px; }
.so-htb a:not([class]) { color: var(--htb-blue); text-underline-offset: 2px; }
.so-htb-sec { padding: 72px 0; }
.so-htb-sec--tint { background: var(--htb-tint); border-top: 1px solid #edf1f8; border-bottom: 1px solid #edf1f8; }
.so-htb-eyebrow { display: flex; align-items: center; gap: 10px; margin: 0 0 10px; font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--htb-blue); }
.so-htb-eyebrow::before { content: ""; width: 24px; height: 2px; background: var(--htb-blue); }
.so-htb-h2 { margin: 0 0 10px; font-size: clamp(24px, 2.6vw, 32px); font-weight: 800; line-height: 1.2; letter-spacing: -.01em; color: var(--htb-navy); }
.so-htb-sub { max-width: 680px; margin: 0 0 30px; font-size: 16px; line-height: 1.6; }
.so-htb-ic { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 46px; height: 46px; border-radius: 50%; background: #e7eeff; color: var(--htb-blue); font-size: 20px; }
.so-htb-card { height: 100%; padding: 24px 22px; border: 1px solid var(--htb-line); border-radius: 14px; background: #fff; box-shadow: 0 6px 18px rgba(15, 27, 61, .04); }
.so-htb-card h3 { margin: 0 0 8px; font-size: 17px; font-weight: 700; line-height: 1.35; color: var(--htb-navy); }
.so-htb-card p { margin: 0; font-size: 15px; line-height: 1.6; }
.so-htb a.so-htb-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 0 26px; border: 1.5px solid var(--htb-blue); border-radius: 10px; background: #fff; color: var(--htb-blue); font-size: 15px; font-weight: 700; text-decoration: none; transition: background-color .2s ease, color .2s ease, box-shadow .2s ease; }
.so-htb a.so-htb-btn:hover { background: #eef3ff; color: var(--htb-blue); }
.so-htb a.so-htb-btn--primary { background: var(--htb-blue); color: #fff; box-shadow: 0 8px 20px rgba(37, 86, 224, .25); }
.so-htb a.so-htb-btn--primary:hover { background: #1e47c9; color: #fff; }

/* Hero (keeps .so-g-hero so the ad banner still follows it; overrides the old guide hero rules) */
.so-htb .so-g-hero.so-htb-hero { position: relative; overflow: hidden; padding: 64px 0 !important; text-align: left !important; background: linear-gradient(135deg, #f7f9ff 0%, #ecf2ff 100%); }
.so-htb-hero__grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 40px; align-items: center; }
.so-htb .so-g-hero .so-htb-title { margin: 0 0 16px; max-width: none; font-size: clamp(32px, 4vw, 50px) !important; font-weight: 800 !important; line-height: 1.1 !important; letter-spacing: -.02em !important; color: var(--htb-navy); }
.so-htb-hero__lead { margin: 0 0 28px !important; font-size: 17px; line-height: 1.65; }
.so-htb-hero__actions { display: flex; flex-wrap: wrap; gap: 12px; }
.so-htb-hero__img { display: block; width: 100%; height: auto; border-radius: 18px; }

/* Four steps */
.so-htb-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin: 0; padding: 0; list-style: none; }
.so-htb-steps .so-htb-card__top { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.so-htb-num { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 28px; padding: 0 8px; border-radius: 999px; background: #eef3ff; color: var(--htb-blue); font-size: 13px; font-weight: 800; letter-spacing: .04em; }

/* Event categories */
.so-htb-cats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin: 0; padding: 0; list-style: none; }
.so-htb-cat { display: flex; flex-direction: column; height: 100%; overflow: hidden; border: 1px solid var(--htb-line); border-radius: 14px; background: #fff; color: inherit; text-decoration: none; box-shadow: 0 6px 18px rgba(15, 27, 61, .04); transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
.so-htb-cat:hover { border-color: var(--htb-blue); box-shadow: 0 12px 26px rgba(37, 86, 224, .14); transform: translateY(-2px); color: inherit; }
.so-htb-cat img { display: block; width: 100%; aspect-ratio: 16 / 10; object-fit: cover; }
.so-htb-cat__body { display: flex; flex-direction: column; gap: 6px; padding: 16px 18px 18px; }
.so-htb-cat__title { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0; font-size: 16px; font-weight: 700; color: var(--htb-navy); }
.so-htb-cat__title .bi { color: var(--htb-blue); transition: transform .2s ease; }
.so-htb-cat:hover .so-htb-cat__title .bi { transform: translateX(3px); }
.so-htb-cat p { margin: 0; font-size: 14.5px; line-height: 1.55; }

/* Every step, explained */
.so-htb-trust { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin: 0; padding: 0; list-style: none; }
.so-htb-trust .so-htb-card { display: flex; gap: 16px; align-items: flex-start; }
.so-htb-more { display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; font-size: 14px; font-weight: 700; color: var(--htb-blue); text-decoration: none; }
.so-htb-more:hover { text-decoration: underline; }

/* CTA band */
.so-htb-cta { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 40px 44px; border-radius: 18px; background: #0b1736; color: #fff; }
.so-htb-cta::before { content: ""; position: absolute; inset: 0; background: url("/images/crowd-at-concert-or-event.webp") right center / cover no-repeat; opacity: .35; }
.so-htb-cta::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, #0b1736 30%, rgba(11, 23, 54, .7) 65%, rgba(30, 71, 201, .35) 100%); }
.so-htb-cta > * { position: relative; z-index: 1; }
.so-htb-cta h2 { margin: 0 0 8px; font-size: clamp(22px, 2.4vw, 30px); font-weight: 800; color: #fff; }
.so-htb-cta p { max-width: 560px; margin: 0; font-size: 16px; line-height: 1.6; color: rgba(255, 255, 255, .85); }
.so-htb-cta .so-htb-btn { flex: none; border-color: var(--htb-blue); }

@media (max-width: 1199.98px) {
  .so-htb-steps, .so-htb-cats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .so-htb-trust { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
  .so-htb .so-g-hero.so-htb-hero { padding: 48px 0 !important; }
  .so-htb-hero__grid { grid-template-columns: 1fr; gap: 28px; }
  .so-htb-hero__img { max-width: 560px; }
  .so-htb-sec { padding: 56px 0; }
  .so-htb-cta { flex-direction: column; align-items: flex-start; padding: 32px 28px; }
}
@media (max-width: 575.98px) {
  .so-htb .so-g-hero.so-htb-hero { padding: 36px 0 !important; }
  .so-htb-hero__actions .so-htb-btn { flex: 1 1 100%; }
  .so-htb-sec { padding: 44px 0; }
  .so-htb-steps, .so-htb-cats, .so-htb-trust { grid-template-columns: 1fr; gap: 14px; }
  .so-htb-cta { padding: 28px 20px; }
  .so-htb-cta .so-htb-btn { width: 100%; }
}

/* Guide below (inc/seo-copy/how-to-buy-tickets-online.php inside the shared .so-seo-copy wrapper) */
.so-htb .so-seo-copy { padding: 72px 0; border-top: 0; background: #fff; }
.so-htb .so-seo-copy .container { max-width: 1140px; }
.so-htb .so-htb-g h2 { margin: 0 0 12px; font-size: clamp(22px, 2.4vw, 28px); font-weight: 800; line-height: 1.25; color: var(--htb-navy); }
.so-htb .so-htb-g h3 { margin: 0; font-size: 19px; font-weight: 800; line-height: 1.3; color: var(--htb-navy); }
.so-htb .so-htb-g p, .so-htb .so-htb-g li { font-size: 16px; line-height: 1.7; color: var(--htb-text); }
.so-htb .so-htb-g .so-cseo__lead { max-width: 860px; margin: 0 0 32px; padding: 20px 24px; border-left: 4px solid var(--htb-blue); border-radius: 0 12px 12px 0; background: var(--htb-pale); font-size: 17px; line-height: 1.65; color: #1d2638; }
.so-htb .so-htb-g__intro { max-width: 860px; }
.so-htb .so-htb-g__card { min-width: 0; padding: 26px 28px; border: 1px solid var(--htb-line); border-radius: 16px; background: #fff; box-shadow: 0 6px 18px rgba(15, 27, 61, .04); }
.so-htb .so-htb-g__card--tint { background: var(--htb-pale); border-color: #d7e2fb; box-shadow: none; }
.so-htb .so-htb-g__card > :last-child, .so-htb .so-htb-g__pad > :last-child { margin-bottom: 0; }
.so-htb .so-htb-g__head { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
.so-htb .so-htb-g__head h2, .so-htb .so-htb-g__head h3 { margin: 0; }
.so-htb .so-htb-g__split { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 22px; align-items: start; margin: 8px 0 22px; }
.so-htb .so-htb-g__split--even { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
/* seven steps: numbered blue circles */
.so-htb .so-htb-g__steps ol { margin: 6px 0 0; padding: 0; list-style: none; counter-reset: htbstep; }
.so-htb .so-htb-g__steps li { position: relative; margin: 0; padding: 12px 0 12px 52px; border-top: 1px solid #edf1f8; counter-increment: htbstep; list-style: none; font-size: 15.5px; line-height: 1.6; }
.so-htb .so-htb-g__steps li:first-child { border-top: 0; }
.so-htb .so-htb-g__steps li::before { content: counter(htbstep, decimal-leading-zero); position: absolute; left: 0; top: 12px; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: var(--htb-blue); color: #fff; font-size: 13px; font-weight: 800; }
.so-htb .so-htb-g__steps li strong { display: block; color: var(--htb-navy); }
/* three topic cards with photos */
.so-htb .so-htb-g__three { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-bottom: 22px; }
.so-htb .so-htb-g__card--img { padding: 0; overflow: hidden; }
.so-htb .so-htb-g__card--img img { display: block; width: 100%; aspect-ratio: 16 / 8; object-fit: cover; }
.so-htb .so-htb-g__pad { padding: 20px 22px 22px; }
.so-htb .so-htb-g__card--img h2 { font-size: 20px; }
.so-htb .so-htb-g__card--img p { font-size: 15px; }
/* safe habits list */
.so-htb .so-htb-g__safe ul { margin: 0 0 14px; padding: 0; list-style: none; }
.so-htb .so-htb-g__safe li { position: relative; margin: 0 0 10px; padding-left: 32px; list-style: none; font-size: 15.5px; line-height: 1.55; }
.so-htb .so-htb-g__safe li::before, .so-htb .so-htb-g__guar li::before { content: ""; position: absolute; left: 0; top: 1px; width: 22px; height: 22px; border-radius: 50%; background: var(--htb-blue) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12.5l4 4 8-9'/%3E%3C/svg%3E") center / 14px no-repeat; }
/* guarantee */
.so-htb .so-htb-g__guar { margin-bottom: 22px; }
.so-htb .so-htb-g__guar ul { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px 28px; margin: 4px 0 16px; padding: 0; list-style: none; }
.so-htb .so-htb-g__guar li { position: relative; margin: 0; padding-left: 32px; list-style: none; font-size: 15.5px; line-height: 1.55; }
.so-htb .so-htb-g__guar p { font-size: 15px; }
/* help bar */
.so-htb .so-htb-g__help { display: flex; align-items: center; gap: 16px; margin-bottom: 48px; padding: 18px 22px; border: 1px solid var(--htb-line); border-radius: 14px; background: #fff; }
.so-htb .so-htb-g__help .so-htb-g__callout { margin: 0; padding: 0; border: 0; background: none; }
.so-htb .so-htb-g__help p { margin: 0; font-size: 15.5px; }
/* FAQ: shared .so-qa design, two columns on wide screens */
.so-htb .so-htb-g__faq { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); column-gap: 28px; align-items: start; margin-bottom: 48px; }
.so-htb .so-htb-g__faq h2 { grid-column: 1 / -1; margin-bottom: 18px; }
.so-htb .so-htb-g__faq details:nth-of-type(2) { border-top: 1px solid #e6e9ef; }
/* closing card */
.so-htb .so-htb-g__final { display: flex; align-items: center; justify-content: space-between; gap: 28px; background: var(--htb-pale); border-color: #d7e2fb; box-shadow: none; }
.so-htb .so-htb-g__final p { margin: 0; }
.so-htb .so-htb-g__final .so-htb-btn { flex: none; }
@media (max-width: 991.98px) {
  .so-htb .so-seo-copy { padding: 56px 0; }
  .so-htb .so-htb-g__split, .so-htb .so-htb-g__split--even { grid-template-columns: 1fr; }
  .so-htb .so-htb-g__three { grid-template-columns: 1fr 1fr; }
  .so-htb .so-htb-g__three > :last-child { grid-column: 1 / -1; }
  .so-htb .so-htb-g__faq { grid-template-columns: 1fr; }
  .so-htb .so-htb-g__faq details:nth-of-type(2) { border-top: 0; }
  .so-htb .so-htb-g__final { flex-direction: column; align-items: flex-start; }
}
@media (max-width: 575.98px) {
  .so-htb .so-seo-copy { padding: 44px 0; }
  .so-htb .so-htb-g__card { padding: 20px 18px; }
  .so-htb .so-htb-g__card--img { padding: 0; }
  .so-htb .so-htb-g__pad { padding: 18px; }
  .so-htb .so-htb-g__three { grid-template-columns: 1fr; }
  .so-htb .so-htb-g__guar ul { grid-template-columns: 1fr; }
  .so-htb .so-htb-g__help { align-items: flex-start; padding: 16px; }
  .so-htb .so-htb-g__final .so-htb-btn { width: 100%; }
}
</style>

<div class="so-htb">

<section class="so-g-hero so-htb-hero">
  <div class="container">
    <div class="so-htb-hero__grid">
      <div>
        <p class="so-htb-eyebrow">Resale ticket marketplace</p>
        <h2 class="so-htb-title">How to buy tickets online</h2>
        <p class="so-htb-hero__lead"><?php echo getContentBlock('/what-we-do', 'hero-sub', 'We make ticket buying simple: Seat Outlet connects fans with live events across concerts, sports, theater, and more. Find, compare, and book tickets securely in just a few clicks.'); ?></p>
        <div class="so-htb-hero__actions">
          <a href="/buy-tickets-online" class="so-htb-btn so-htb-btn--primary">Browse events</a>
          <a href="/" class="so-htb-btn">Find tickets near you</a>
        </div>
      </div>
      <img class="so-htb-hero__img" src="/images/event-ticket-buying.webp" alt="Making ticket buying simple on Seat Outlet" width="1536" height="1024" fetchpriority="high" decoding="async">
    </div>
  </div>
</section>

<section class="so-htb-sec" id="how">
  <div class="container">
    <h2 class="so-htb-h2">Four steps from search to seats</h2>
    <p class="so-htb-sub">You can browse without signing up for anything, and there is no newsletter sign-up before checkout.</p>
    <ol class="so-htb-steps">
      <?php foreach ($soSteps as $i => [$ic, $t, $d]) { ?>
      <li class="so-htb-card">
        <div class="so-htb-card__top"><span class="so-htb-num"><?php echo sprintf('%02d', $i + 1); ?></span><span class="so-htb-ic"><i class="bi <?php echo $e($ic); ?>" aria-hidden="true"></i></span></div>
        <h3><?php echo $e($t); ?></h3>
        <p><?php echo $e($d); ?></p>
      </li>
      <?php } ?>
    </ol>
  </div>
</section>

<section class="so-htb-sec so-htb-sec--tint" id="events">
  <div class="container">
    <h2 class="so-htb-h2">Live events across the country</h2>
    <p class="so-htb-sub">Planning ahead or looking for last-minute tickets, you can find events near you and nationwide.</p>
    <ul class="so-htb-cats">
      <?php foreach ($soCats as [$ic, $t, $d, $href, $img]) { ?>
      <li><a class="so-htb-cat" href="<?php echo $e($href); ?>">
        <img src="<?php echo $e($img[0]); ?>" alt="<?php echo $e($img[1]); ?>" width="<?php echo (int) $img[2]; ?>" height="<?php echo (int) $img[3]; ?>" loading="lazy" decoding="async">
        <span class="so-htb-cat__body">
          <span class="so-htb-cat__title"><span><i class="bi <?php echo $e($ic); ?>" aria-hidden="true"></i> <?php echo $e($t); ?></span><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
          <p><?php echo $e($d); ?></p>
        </span>
      </a></li>
      <?php } ?>
    </ul>
  </div>
</section>

<section class="so-htb-sec" id="verified">
  <div class="container">
    <h2 class="so-htb-h2">Every step, explained</h2>
    <p class="so-htb-sub">What to expect before you buy, while you pay and after your order.</p>
    <ul class="so-htb-trust" id="security">
      <?php foreach ($soTrust as [$ic, $t, $d, $link]) { ?>
      <li class="so-htb-card">
        <span class="so-htb-ic"><i class="bi <?php echo $e($ic); ?>" aria-hidden="true"></i></span>
        <div>
          <h3><?php echo $e($t); ?></h3>
          <p><?php echo $e($d); ?></p>
          <?php if ($link) { ?><a class="so-htb-more" href="<?php echo $e($link[0]); ?>"><?php echo $e($link[1]); ?> <i class="bi bi-chevron-right" aria-hidden="true"></i></a><?php } ?>
        </div>
      </li>
      <?php } ?>
    </ul>
  </div>
</section>

<section class="so-htb-sec so-htb-sec--cta" id="support">
  <div class="container">
    <div class="so-htb-cta">
      <div>
        <h2>Book your next event with confidence</h2>
        <p>Browse events, compare sections and prices, and review the full cost at checkout before you pay.</p>
      </div>
      <a href="/" class="so-htb-btn so-htb-btn--primary">Find tickets near you</a>
    </div>
  </div>
</section>

<?php soSeoCopy('how-to-buy-tickets-online'); ?>
</div>
<?php include 'footer.php'; ?>
