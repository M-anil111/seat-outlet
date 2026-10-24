<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical.
$pageMetaTitle       = 'Deals & Promotions – Ticket Discount Codes | Seat Outlet';
$pageMetaDescription = 'Promo codes TAKE5 and TAKE10 for Seat Outlet orders, how to use one at checkout, and honest tips for paying less on concert, sports and theater tickets.';
$pageCanonicalUrl    = HOME_URL . '/tickets-promo-code';
include 'header.php';
$e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
// Line icons (24px grid, stroke follows the text colour).
$soPcIcons = [
    'ticket'  => '<path d="M3.5 8.5V6.5a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v2a2.5 2.5 0 0 0 0 5v2a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1v-2a2.5 2.5 0 0 0 0-5Z"/><path d="M14.5 5.5v13" stroke-dasharray="1.6 2.2"/>',
    'cart'    => '<path d="M3 4h2.2l2.1 10.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.1L20.5 8H6.1"/><circle cx="9.5" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>',
    'tag'     => '<path d="M12.6 3.5H20.5v7.9l-9 9a1.6 1.6 0 0 1-2.3 0l-5.6-5.6a1.6 1.6 0 0 1 0-2.3l9-9Z"/><circle cx="16.4" cy="7.6" r="1.4"/>',
    'doc'     => '<path d="M6.5 3.5h7.5l4.5 4.5v12a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1v-15.5a1 1 0 0 1 1-1Z"/><path d="M13.5 3.5V8.5h5M9 12.5h6M9 16h6"/>',
    'seats'   => '<rect x="4" y="5" width="16" height="11" rx="2"/><path d="M4 9.5h16M8 5v11M12 5v11M16 5v11M6 16v3M18 16v3"/>',
    'cal'     => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4M16 3v4M3.5 10h17M8 14h.01M12 14h.01M16 14h.01M8 17.5h.01M12 17.5h.01"/>',
    'bars'    => '<path d="M4 20.5h16"/><rect x="5" y="11" width="3.2" height="7" rx="1"/><rect x="10.4" y="6.5" width="3.2" height="11.5" rx="1"/><rect x="15.8" y="3.5" width="3.2" height="14.5" rx="1"/>',
    'dollar'  => '<circle cx="12" cy="12" r="8.5"/><path d="M14.8 9.2c-.4-1-1.5-1.7-2.8-1.7-1.6 0-2.8.9-2.8 2.1 0 2.9 5.7 1.5 5.7 4.4 0 1.2-1.3 2.2-2.9 2.2-1.4 0-2.6-.7-3-1.8M12 6v1.5M12 16.5V18"/>',
    'bell'    => '<path d="M18 9.5a6 6 0 0 0-12 0c0 5.5-2.5 7.5-2.5 7.5h17S18 15 18 9.5"/><path d="M10.3 20a1.9 1.9 0 0 0 3.4 0"/>',
    'warn'    => '<path d="M10.3 4.3 2.6 17.6a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9.5v4.5M12 17.2h.01"/>',
];
$soPcIcon = function (string $k, int $s = 24) use ($soPcIcons) {
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $soPcIcons[$k] . '</svg>';
};
$soPcSteps = [
    ['ticket', 'Choose event and seats', 'Pick your event and your seats on the event page.'],
    ['cart', 'Continue to checkout', 'Continue to checkout when you are ready.'],
    ['tag', 'Enter code if a field is available', 'If the checkout shows a promo code field, enter the code and check the new total.'],
    ['doc', 'Review the new total before paying', 'Review the full cost before you pay.'],
];
$soPcWays = [
    ['seats', 'Compare sections and rows', 'Compare sections and rows for the same event. Prices differ a lot between listings.'],
    ['ticket', 'Explore ticket deals', 'Check the <a href="/ticket-deals">ticket deals page</a> for lower-priced events.'],
    ['cal', 'Be flexible on dates', 'Be flexible on dates when you can.'],
    ['bars', 'Understand ticket pricing', 'Read why <a href="/why-are-concert-tickets-so-expensive">concert tickets cost what they do</a> so you know what you are paying for.'],
    ['dollar', 'Compare the full cost', 'Look at the all-in price before you choose a listing, not only the price per ticket.'],
    ['bell', 'Set a price alert', 'Set a price alert on an event you want, and we email you if the price drops.'],
];
$soPcTerms = [
    'Codes apply only at checkout and only where the checkout accepts them.',
    soPromoCodes() ? 'Minimum order amounts apply as shown next to each code.' : 'Minimum order amounts and other conditions apply as shown at checkout.',
    'Codes may not be combined with other offers.',
    'Codes can change or stop working without notice.',
];
?>

<style>
/* ===== Ticket promo codes: content between header and footer (scoped .so-pc; the SEO copy below uses .so-pc-copy) ===== */
.so-pc, .so-pc-copy { --pc-blue: #2556e0; --pc-navy: #0f1b3d; --pc-text: #4a5468; --pc-line: #e1e7f2; --pc-pale: #eef3ff; --pc-tint: #f3f7ff; }
.so-pc { background: #fff; color: var(--pc-text); }
.so-pc .container, .so-pc-copy .container { max-width: 1180px; }
.so-pc a:not([class]) { color: var(--pc-blue); text-underline-offset: 2px; }
.so-pc-sec { padding: 40px 0; }
.so-pc-sec--tint { background: linear-gradient(180deg, #f3f7ff 0%, #eef4ff 100%); }
.so-pc .so-pc-h2, .so-pc-copy .so-pc-h2 { margin: 0 0 22px; font-size: clamp(22px, 2.4vw, 28px); font-weight: 800; line-height: 1.2; letter-spacing: -.01em; color: var(--pc-navy); }
.so-pc-card { border: 1px solid var(--pc-line); border-radius: 14px; background: #fff; box-shadow: 0 6px 18px rgba(15, 27, 61, .04); }
.so-pc-ic { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 52px; height: 52px; border-radius: 50%; background: var(--pc-pale); color: var(--pc-blue); }

/* Hero (keeps .hero-section so the banner ad still follows it; the !important rules beat the shared .hero-section ones) */
.so-pc .hero-section.so-pc-hero { position: relative; overflow: hidden; min-height: 0 !important; margin: 0 !important; padding: 44px 0 !important; text-align: left !important; background: linear-gradient(120deg, #f1f6ff 0%, #e6efff 55%, #dce8ff 100%) !important; }
.so-pc-hero::before { display: none !important; }
.so-pc-hero__grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 32px; align-items: center; }
.so-pc .so-pc-hero h1 { margin: 0 0 18px !important; font-size: clamp(34px, 4.4vw, 54px) !important; font-weight: 800 !important; line-height: 1.08 !important; letter-spacing: -.02em !important; color: var(--pc-navy) !important; }
.so-pc .so-pc-hero p { max-width: 520px; margin: 0 !important; font-size: clamp(16px, 1.4vw, 18px) !important; line-height: 1.6 !important; color: var(--pc-text) !important; }
.so-pc-hero__art { justify-self: end; width: min(100%, 360px); }
.so-pc-hero__art svg { display: block; width: 100%; height: auto; }

/* Latest codes: ticket-shaped coupons */
.so-pc-codes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 22px; }
.so-pc-coupon { position: relative; display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(0, 1fr); align-items: center; min-height: 128px; border: 1px solid var(--pc-line); border-radius: 14px; background: #fff; box-shadow: 0 10px 26px rgba(15, 27, 61, .07); }
.so-pc-coupon::before, .so-pc-coupon::after { content: ""; position: absolute; top: 50%; width: 22px; height: 22px; margin-top: -11px; border: 1px solid var(--pc-line); border-radius: 50%; background: #fff; }
.so-pc-coupon::before { left: -11px; clip-path: inset(0 0 0 50%); }
.so-pc-coupon::after { right: -11px; clip-path: inset(0 50% 0 0); }
.so-pc-coupon__deal { padding: 22px 18px 22px 26px; text-align: center; }
.so-pc-coupon__deal strong { display: block; font-size: clamp(30px, 3.4vw, 42px); font-weight: 800; line-height: 1.05; letter-spacing: -.01em; color: var(--pc-blue); }
.so-pc-coupon__deal span { display: block; margin-top: 8px; font-size: 15px; color: var(--pc-text); }
.so-pc-coupon__use { display: flex; flex-direction: column; gap: 12px; padding: 22px 26px 22px 22px; border-left: 2px dashed #d5def0; }
.so-pc-coupon__code { display: block; padding: 8px 10px; border: 1.5px dashed #6f93ec; border-radius: 6px; background: var(--pc-tint); font-size: 18px; font-weight: 800; letter-spacing: .04em; text-align: center; color: var(--pc-navy); }
.so-pc .so-pc-coupon .offer-copy-btn { min-height: 38px; padding: 6px 12px; border: 0; border-radius: 6px; background: var(--pc-blue) !important; color: #fff !important; font-size: 14px; font-weight: 700; box-shadow: none; }
.so-pc .so-pc-coupon .offer-copy-btn:hover { background: #1c46c4 !important; }
.so-pc-note { max-width: 880px; margin: 18px auto 0; font-size: 13.5px; line-height: 1.6; text-align: center; color: #5d6679; }
.so-pc-note p { margin: 0 0 4px; }

/* How to try: four steps */
.so-pc-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin: 0; padding: 0; list-style: none; counter-reset: pc-step; }
.so-pc-steps > li { position: relative; display: flex; flex-direction: column; align-items: center; padding: 24px 18px 22px; text-align: center; counter-increment: pc-step; }
.so-pc-steps > li::before { content: counter(pc-step); position: absolute; top: 16px; left: 16px; display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--pc-pale); font-size: 14px; font-weight: 800; color: var(--pc-blue); }
.so-pc-steps .so-pc-step__ic { display: inline-flex; margin: 6px 0 14px; color: var(--pc-blue); }
.so-pc-steps h3 { margin: 0 0 8px; font-size: 15.5px; font-weight: 700; line-height: 1.35; color: var(--pc-navy); }
.so-pc-steps p { margin: 0; font-size: 14.5px; line-height: 1.55; }

/* Ways to pay less: six cards */
.so-pc-ways { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin: 0; padding: 0; list-style: none; }
.so-pc-ways > li { display: flex; align-items: center; gap: 16px; padding: 18px 18px; }
.so-pc-ways h3 { margin: 0 0 4px; font-size: 15.5px; font-weight: 700; line-height: 1.35; color: var(--pc-navy); }
.so-pc-ways p { margin: 0; font-size: 14.5px; line-height: 1.5; }
.so-pc-more { display: none; margin: 22px auto 0; min-height: 46px; padding: 0 30px; border: 0; border-radius: 8px; background: var(--pc-blue); color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; }
.so-pc-more:hover { background: #1c46c4; }
.so-pc-more:focus-visible, .so-pc .so-pc-coupon .offer-copy-btn:focus-visible { outline: 3px solid #9db6f4; outline-offset: 2px; }

/* Alerts: the shared split sign-up form on a card */
.so-pc-alerts .so-nl.so-nl--split { margin: 0; padding: 30px 32px; border: 1px solid var(--pc-line); border-radius: 16px; background: #fff; box-shadow: 0 10px 26px rgba(15, 27, 61, .06); grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); }
.so-pc-alerts .so-nl--split .so-nl__intro { display: grid; grid-template-columns: auto minmax(0, 1fr); column-gap: 22px; align-items: center; }
.so-pc-alerts .so-nl--split .so-nl__icon { grid-row: span 2; width: 84px; height: 84px; border-radius: 50%; background: var(--pc-pale); }
.so-pc-alerts .so-nl--split .so-nl__title { margin: 0 0 8px; font-size: clamp(22px, 2.2vw, 26px); font-weight: 800; color: var(--pc-navy); }
.so-pc-alerts .so-nl--split .so-nl__sub { font-size: 15px; }
.so-pc-alerts .so-nl--split .so-nl__fields { gap: 12px 16px; }
.so-pc-alerts .so-nl--split .so-nl__label { font-size: 14px; }
.so-pc-alerts .so-nl--split .so-nl__fields input { height: 46px; border-radius: 8px; font-size: 15px; }
.so-pc-alerts .so-nl--split .so-nl__fields button { height: 48px; min-height: 48px; border-radius: 8px; font-size: 16px; }
.so-pc-alerts .so-nl--split .so-nl__legal { margin-top: 12px; font-size: 13px; text-align: center; }

/* Questions: two columns, the first answer open in the left column */
.so-pc-faq .so-minifaq { margin: 0; padding: 0; border: 0; background: none; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px 20px; align-items: start; }
.so-pc-faq .so-minifaq > h2 { grid-column: 1 / -1; margin: 0 0 10px !important; font-size: clamp(22px, 2.4vw, 28px) !important; font-weight: 800 !important; color: var(--pc-navy) !important; }
.so-pc-faq .so-minifaq > details:first-of-type { grid-column: 1; grid-row: 2 / span 4; }
.so-pc-faq .so-minifaq > details:not(:first-of-type) { grid-column: 2; }
.so-pc-faq .so-minifaq.so-qa > details.so-faq { margin: 0; border: 1px solid var(--pc-line); border-radius: 10px; background: #fff; }
.so-pc-faq .so-minifaq.so-qa > details.so-faq[open] { border-color: #c9d7f6; background: #f4f8ff; }
.so-pc-faq .so-minifaq.so-qa > details.so-faq > summary { padding: 14px 48px 14px 18px; font-size: 15.5px; font-weight: 700; color: var(--pc-navy); }
.so-pc-faq .so-minifaq.so-qa > details.so-faq > p { padding: 0 18px 16px; font-size: 15px; line-height: 1.6; color: var(--pc-text); }

/* Terms: four numbered points in one card */
.so-pc-terms { padding: 22px 24px; }
.so-pc-terms h2 { margin: 0 0 14px; font-size: 20px; font-weight: 800; color: var(--pc-navy); }
.so-pc-terms ol { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 0; padding: 0; list-style: none; counter-reset: pc-term; }
.so-pc-terms li { display: flex; align-items: flex-start; gap: 12px; padding: 4px 18px; font-size: 14.5px; line-height: 1.5; counter-increment: pc-term; }
.so-pc-terms li:first-child { padding-left: 0; }
.so-pc-terms li + li { border-left: 1px solid var(--pc-line); }
.so-pc-terms li::before { content: counter(pc-term); flex: none; display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--pc-pale); font-size: 14px; font-weight: 800; color: var(--pc-blue); }

/* SEO copy below (inc/seo-copy/tickets-promo-code.php), laid out as cards */
.so-seo-copy.so-qa:has(.so-pc-copy) { padding-top: 56px; background: #fff; }
.so-seo-copy:has(.so-pc-copy) > .container { max-width: 1180px !important; }
.so-seo-copy .so-pc-copy h2.so-pc-h2 { margin: 0 0 14px; font-size: clamp(22px, 2.4vw, 28px); font-weight: 800; line-height: 1.2; color: var(--pc-navy); }
.so-seo-copy .so-pc-copy .so-pc-duo h2 { margin: 0 0 8px; font-size: 18px; }
.so-seo-copy .so-pc-copy ul.so-pc-cats > li { margin: 0; padding: 0; list-style: none; }
.so-seo-copy .so-pc-copy ul.so-pc-cats > li::before, .so-seo-copy .so-pc-copy ul.so-pc-cats > li::marker { content: none; }
.so-seo-copy .so-pc-copy .so-pc-cats a { text-decoration: none; }
.so-pc-copy { color: var(--pc-text); }
.so-pc-copy .so-pc-duo { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-bottom: 36px; }
.so-pc-copy .so-pc-duo > .so-pc-card { display: flex; gap: 20px; padding: 22px 24px; }
.so-pc-copy .so-pc-duo h2 { margin: 0 0 8px; font-size: 18px; font-weight: 800; line-height: 1.3; color: var(--pc-navy); }
.so-pc-copy .so-pc-duo p, .so-pc-copy .so-pc-duo li { font-size: 14.5px; line-height: 1.6; }
.so-pc-copy .so-pc-duo p { margin: 0 0 8px; }
.so-pc-copy .so-pc-duo ul { margin: 0 0 8px; padding-left: 18px; }
.so-pc-copy .so-pc-duo .so-pc-ic { width: 60px; height: 60px; }
/* Three image cards: equal columns, the same 16:9 image frame (cropped, never stretched) and equal card heights */
.so-seo-copy .so-pc-copy ul.so-pc-cats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: stretch; gap: 20px; margin: 18px 0 36px; padding: 0; list-style: none; }
.so-seo-copy .so-pc-copy ul.so-pc-cats > li { display: flex; min-width: 0; }
.so-seo-copy .so-pc-copy .so-pc-cats a { display: flex; flex-direction: column; flex: 1 1 auto; width: 100%; overflow: hidden; padding: 0; border: 1px solid var(--pc-line); border-radius: 12px; background: #fff; color: var(--pc-navy); text-decoration: none; box-shadow: 0 6px 18px rgba(15, 27, 61, .05); transition: transform .15s ease, box-shadow .15s ease; }
.so-seo-copy .so-pc-copy .so-pc-cats a::after { display: none; }
.so-seo-copy .so-pc-copy .so-pc-cats a:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(15, 27, 61, .1); }
.so-seo-copy .so-pc-copy .so-pc-cats img { display: block; flex: none; width: 100%; max-width: none; height: auto; aspect-ratio: 16 / 9; margin: 0; border-radius: 0; object-fit: cover; object-position: center; }
.so-seo-copy .so-pc-copy .so-pc-cats span { position: relative; display: flex; flex-direction: column; justify-content: center; flex: 1 1 auto; padding: 14px 44px 16px 16px; }
.so-pc-copy .so-pc-cats strong { display: block; font-size: 15px; font-weight: 700; }
.so-pc-copy .so-pc-cats small { display: block; margin-top: 2px; font-size: 13px; color: var(--pc-blue); text-decoration: underline; }
.so-pc-copy .so-pc-cats span::after { content: ""; position: absolute; right: 18px; top: 50%; width: 9px; height: 9px; margin-top: -5px; border-top: 2px solid var(--pc-navy); border-right: 2px solid var(--pc-navy); transform: rotate(45deg); }

@media (max-width: 991.98px) {
  .so-pc-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .so-pc-ways { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .so-pc-codes { grid-template-columns: minmax(0, 1fr); max-width: 560px; margin: 0 auto; }
  .so-pc-alerts .so-nl.so-nl--split { grid-template-columns: minmax(0, 1fr); }
  .so-pc-terms ol { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 14px; }
  .so-pc-terms li:nth-child(odd) { padding-left: 0; border-left: 0; }
  .so-pc-copy .so-pc-duo { grid-template-columns: minmax(0, 1fr); }
  .so-seo-copy.so-qa:has(.so-pc-copy) { padding-top: 48px; }
  .so-seo-copy .so-pc-copy ul.so-pc-cats { gap: 16px; }
}
@media (max-width: 767.98px) {
  .so-pc-sec { padding: 32px 0; }
  .so-pc .hero-section.so-pc-hero { padding: 32px 0 !important; }
  .so-pc-hero__grid { grid-template-columns: minmax(0, 1fr) 150px; gap: 16px; }
  .so-pc-ways { grid-template-columns: minmax(0, 1fr); }
  .so-pc-ways.is-clamped > li:nth-child(n+4) { display: none; }
  .so-pc-more { display: block; }
  .so-pc-faq .so-minifaq { grid-template-columns: minmax(0, 1fr); }
  .so-pc-faq .so-minifaq > details:first-of-type, .so-pc-faq .so-minifaq > details:not(:first-of-type) { grid-column: 1; grid-row: auto; }
  .so-seo-copy.so-qa:has(.so-pc-copy) { padding-top: 40px; }
  /* phones: the three image cards swipe sideways instead of stacking */
  .so-seo-copy .so-pc-copy ul.so-pc-cats { grid-template-columns: none; grid-auto-flow: column; grid-auto-columns: 78%; gap: 14px; margin-left: -12px; margin-right: -12px; padding: 2px 12px 12px; overflow-x: auto; overscroll-behavior-x: contain; scroll-snap-type: x mandatory; scroll-padding-inline: 12px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
  .so-seo-copy .so-pc-copy ul.so-pc-cats::-webkit-scrollbar { display: none; }
  .so-seo-copy .so-pc-copy ul.so-pc-cats > li { scroll-snap-align: start; }
  .so-seo-copy .so-pc-copy .so-pc-cats a:hover { transform: none; }
}
@media (max-width: 575.98px) {
  .so-pc-hero__grid { grid-template-columns: minmax(0, 1fr); }
  .so-pc-hero__art { display: none; }
  .so-pc-coupon { grid-template-columns: minmax(0, 1fr); }
  .so-pc-coupon__use { border-left: 0; border-top: 2px dashed #d5def0; padding: 16px 22px 20px; }
  .so-pc-coupon__deal { padding: 20px 22px 16px; }
  .so-pc-coupon::before, .so-pc-coupon::after { display: none; }
  .so-pc-note { text-align: left; }
  .so-pc-steps { grid-template-columns: minmax(0, 1fr); }
  .so-pc-terms ol { grid-template-columns: minmax(0, 1fr); }
  .so-pc-terms li, .so-pc-terms li + li { padding: 0; border-left: 0; }
  .so-pc-terms li + li { margin-top: 12px; }
  .so-pc-alerts .so-nl.so-nl--split { padding: 24px 18px; }
  .so-pc-alerts .so-nl--split .so-nl__icon { width: 60px; height: 60px; }
  .so-pc-copy .so-pc-duo > .so-pc-card { flex-direction: column; gap: 12px; padding: 20px; }
}
</style>

<main class="so-pc">

    <section class="hero-section so-pc-hero">
        <div class="container">
            <div class="so-pc-hero__grid">
                <div>
                    <h1>Ticket Promo Codes and Deals</h1>
                    <p>Find a tickets promo code for concerts, sports and theater, plus tips on using it at checkout.</p>
                </div>
                <div class="so-pc-hero__art" aria-hidden="true">
                    <svg viewBox="0 0 360 240" focusable="false">
                        <defs>
                            <linearGradient id="soPcT1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4f86ff"/><stop offset="1" stop-color="#1f56e8"/></linearGradient>
                            <linearGradient id="soPcT2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a9c4ff"/><stop offset="1" stop-color="#6f97f5"/></linearGradient>
                        </defs>
                        <path d="M70 52c38-40 128-50 196-28 52 17 86 58 74 104-13 50-70 92-150 96C110 228 34 196 22 146 14 110 40 82 70 52Z" fill="#d3e2ff" opacity=".7"/>
                        <g transform="rotate(-14 230 130)"><path d="M150 78h150a10 10 0 0 1 10 10v24a18 18 0 0 0 0 36v24a10 10 0 0 1-10 10H150a10 10 0 0 1-10-10v-24a18 18 0 0 0 0-36V88a10 10 0 0 1 10-10Z" fill="url(#soPcT2)"/></g>
                        <g transform="rotate(-10 180 130)"><path d="M84 70h176a12 12 0 0 1 12 12v28a20 20 0 0 0 0 40v28a12 12 0 0 1-12 12H84a12 12 0 0 1-12-12v-28a20 20 0 0 0 0-40V82a12 12 0 0 1 12-12Z" fill="url(#soPcT1)"/><path d="M226 74v112" stroke="#fff" stroke-width="3" stroke-dasharray="7 7" opacity=".55"/><text x="150" y="152" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="84" font-weight="800" fill="#fff">%</text></g>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <section class="so-pc-sec" id="promocode" aria-labelledby="soPcCodes">
        <div class="container">
            <h2 class="so-pc-h2" id="soPcCodes">Latest Ticket Promo Codes</h2>
            <div class="so-pc-codes">
                <?php foreach (soPromoCodes() as $p) { ?>
                <div class="so-pc-coupon">
                    <div class="so-pc-coupon__deal"><strong><?php echo (int) $p['pct']; ?>% OFF</strong><span>Orders of $<?php echo (int) $p['min']; ?> or more</span></div>
                    <div class="so-pc-coupon__use"><span class="so-pc-coupon__code"><?php echo $e($p['code']); ?></span><button type="button" class="btn offer-copy-btn" data-code="<?php echo $e($p['code']); ?>" aria-label="Copy code <?php echo $e($p['code']); ?>">Copy code</button></div>
                </div>
                <?php } ?>
                <?php if (!soPromoCodes()) { ?>
                <div class="so-pc-coupon"><div class="so-pc-coupon__deal"><strong>Offers at checkout</strong><span>Any available promotion appears at checkout</span></div></div>
                <?php } ?>
            </div>
            <div class="so-pc-note">
                <p><?php echo soPromoCodes() ? 'Below are the promo codes we list for orders placed through Seat Outlet.' : 'We are not listing a promo code at the moment.'; ?> Whether a code applies is decided at checkout, which is hosted by TicketNetwork, so we cannot promise that a code will work on every event or every order. If a code does not apply, nothing is charged extra: you simply pay the price shown.</p>
                <p>Seat Outlet is a resale marketplace. Prices are set by sellers and may be above or below face value, so comparing seats usually saves more than any code. See all <a href="/buy-tickets-online">ticket listings</a> to get started.</p>
            </div>
        </div>
    </section>

    <section class="so-pc-sec so-pc-sec--tint" aria-labelledby="soPcHow">
        <div class="container">
            <h2 class="so-pc-h2" id="soPcHow">How to try a promo code</h2>
            <ol class="so-pc-steps">
                <?php foreach ($soPcSteps as [$ic, $t, $d]) { ?>
                <li class="so-pc-card"><span class="so-pc-step__ic"><?php echo $soPcIcon($ic, 34); ?></span><h3><?php echo $e($t); ?></h3><p><?php echo $e($d); ?></p></li>
                <?php } ?>
            </ol>
        </div>
    </section>

    <section class="so-pc-sec so-pc-sec--tint" aria-labelledby="soPcWays" style="padding-top:8px">
        <div class="container text-center">
            <h2 class="so-pc-h2 text-start" id="soPcWays">Ways to pay less without a code</h2>
            <ul class="so-pc-ways is-clamped text-start" id="soPcWaysList">
                <?php foreach ($soPcWays as [$ic, $t, $d]) { ?>
                <li class="so-pc-card"><span class="so-pc-ic"><?php echo $soPcIcon($ic, 26); ?></span><div><h3><?php echo $e($t); ?></h3><p><?php echo $d; /* trusted HTML: fixed text with internal links */ ?></p></div></li>
                <?php } ?>
            </ul>
            <button type="button" class="so-pc-more" aria-controls="soPcWaysList" aria-expanded="false" data-less="Show fewer">Show all ways to pay less</button>
        </div>
    </section>
    <script>(function(){var b=document.querySelector('.so-pc-more'),l=document.getElementById('soPcWaysList');if(!b||!l)return;var more=b.textContent;b.addEventListener('click',function(){var open=l.classList.toggle('is-clamped')===false;b.setAttribute('aria-expanded',open?'true':'false');b.textContent=open?b.dataset.less:more;});})();</script>

    <section class="so-pc-sec so-pc-alerts" aria-label="Ticket alerts">
        <div class="container">
            <?php echo soLeadForm(['source' => 'promo', 'layout' => 'split', 'button' => 'Sign me up', 'title' => 'Get ticket alerts by email', 'text' => 'Tour announcements, on-sale news and plain-English ticket advice. We cannot promise promo codes.']); ?>
        </div>
    </section>

    <section class="so-pc-sec so-pc-faq" style="padding-top:8px">
        <div class="container">
            <?php ob_start(); soMiniFaq('Promo code questions', [
                ['Which promo codes can I use?', soPromoCodes() ? 'TAKE5 takes 5% off orders of $199 or more, and TAKE10 takes 10% off orders of $349 or more. Enter one code in the promo code field at checkout and check the new total before you pay.' : 'We do not list a promo code right now. If the checkout, which is hosted by TicketNetwork, offers a promotion for your order, it appears there and you can check the new total before you pay.'],
                ['Where do I enter a promo code?', 'At checkout, in the promo code field, when the checkout shows one. Check the new total before you pay.'],
                ['Why did my promo code not work?', 'Codes apply only where the checkout accepts them, the order must meet the minimum shown next to the code, and codes cannot be combined with other offers. If it still fails, contact customer service with your order details.'],
                ['Can I use a promo code on any event?', 'Not always. Some events or listings may not accept codes, and codes can change or stop working without notice.'],
            ]);
            // the first answer starts open, as in the design
            echo preg_replace('/<details class="so-faq"/', '<details class="so-faq" open', (string) ob_get_clean(), 1); ?>
        </div>
    </section>

    <section class="so-pc-sec" style="padding-top:8px">
        <div class="container">
            <div class="so-pc-card so-pc-terms">
                <h2>Terms</h2>
                <ol>
                    <?php foreach ($soPcTerms as $t) { ?><li><?php echo $e($t); ?></li><?php } ?>
                </ol>
            </div>
        </div>
    </section>

</main>

<?php soSeoCopy('tickets-promo-code'); ?>
<?php include 'footer.php'; ?>
