<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<?php /* Laid out as cards on the promo code page (styles in tickets-promo-code.php, scoped .so-pc-copy); the text is unchanged. */ ?>
<div class="so-pc-copy">
<div class="so-pc-duo">
  <div class="so-pc-card"><span class="so-pc-ic" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M3.5 8.5V6.5a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v2a2.5 2.5 0 0 0 0 5v2a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1v-2a2.5 2.5 0 0 0 0-5Z"/><path d="M14.5 5.5v13" stroke-dasharray="1.6 2.2"/></svg></span><div>
    <h2>How a tickets promo code works</h2>
    <p class="so-cseo__lead"><strong>A ticket promo code, also called a ticket coupon or discount code, is entered at checkout when an offer is available.</strong> Not every event has one, so this page also shows ways to pay less that do not need a code.</p>
    <p>A promo code is a short code you type in at checkout to lower the total of an order. The discount might be a percentage off or a fixed amount, and most codes come with conditions such as a minimum order size. The codes above list their own terms, so read them before you order. Codes are tied to the site that issues them, and a code can stop working when it expires or when its limit is reached.</p>
  </div></div>
  <div class="so-pc-card"><span class="so-pc-ic" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M10.3 4.3 2.6 17.6a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9.5v4.5M12 17.2h.01"/></svg></span><div>
    <h2>If a code is rejected</h2>
    <ul>
      <li><strong>Typing errors.</strong> Use the Copy button and paste the code. Codes may be case sensitive.</li>
      <li><strong>The order is too small.</strong> Some codes only apply once the order reaches a minimum amount.</li>
      <li><strong>The code has expired or changed.</strong> We list codes as they are provided to us and cannot promise they are still active.</li>
      <li><strong>The event is not included.</strong> A code may not apply to every event or listing.</li>
    </ul>
    <p>Still stuck? <a href="/ticket-customer-service">Contact support</a> with your order details.</p>
  </div></div>
</div>

<h2 class="so-pc-h2">Concert, sports and Broadway promo codes</h2>
<p>Offers can differ by event type. A concert ticket promo code, a sports ticket promo code and a Broadway promo code are each tied to the offer shown at checkout, and some do not apply to every event or seat. Read the terms next to the code. For cheap concert tickets and cheap tickets near me, compare sections and dates, since a different seat can save more than a code. See <a href="/ticket-deals">ticket deals</a> and our guide to <a href="/how-to-buy-tickets-online">how to buy tickets online</a>.</p>
<ul class="so-pc-cats">
  <li><a href="/concert-tickets-for-sale"><img src="/images/home-cat-concerts.webp" alt="" width="480" height="270" loading="lazy" decoding="async"><span><strong>Concert Tickets</strong><small>See concert events</small></span></a></li>
  <li><a href="/game-day-tickets"><img src="/images/home-cat-sports.webp" alt="" width="480" height="270" loading="lazy" decoding="async"><span><strong>Sports Tickets</strong><small>See sports events</small></span></a></li>
  <li><a href="/buy-broadway-tickets"><img src="/images/home-cat-theater.webp" alt="" width="480" height="270" loading="lazy" decoding="async"><span><strong>Broadway and Theater Tickets</strong><small>See theater events</small></span></a></li>
</ul>

<h2 class="so-pc-h2">Other ways to save</h2>
<p>Because Seat Outlet is a resale marketplace, sellers set their own prices, and the same event can have listings at many price points. Open the event page and look at several sections: a seat a few rows back can cost less than one near the front and still give a good view. A weeknight show or a less popular game often has lower prices than a weekend headliner. Compare the full amount at checkout, not only the price on the listing. We do not promise that any ticket is the lowest price available, since prices change with demand.</p>

<h2 class="so-pc-h2">Your protection does not depend on a code</h2>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>
<p>Be careful with codes from unknown sources. The FTC explains how to spot problems in its <a href="https://consumer.ftc.gov/articles/online-shopping" target="_blank" rel="noopener">online shopping advice</a>. New to ordering online? Our guide to <a href="/how-to-buy-tickets-online">buying tickets online</a> covers the whole process.</p>

<h2 class="so-pc-h2">Questions</h2>
<details class="so-faq"><summary>Where do I enter a promo code?</summary><p>In the promo code field at checkout, when one is offered. Codes apply only where the checkout accepts them.</p></details>
<details class="so-faq"><summary>Can I combine codes?</summary><p>Codes generally cannot be combined with other offers. The terms shown with each code say what applies.</p></details>
<details class="so-faq"><summary>Will a code lower the price of every event?</summary><p>No. Savings vary by event, and codes may not apply to every listing. Check your checkout total.</p></details>
</div>
