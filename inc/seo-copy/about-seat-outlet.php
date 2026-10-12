<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<h2>About Seat Outlet: what we do</h2>
<p>Seat Outlet is an online ticket marketplace where fans can find, compare and buy tickets to concerts, sports, theater, festivals and other live events. We are a resale marketplace, which means tickets are offered by sellers and prices are set by them, not by us.</p>
<p>Because sellers set the price, a ticket may cost more or less than its face value. Our job is to make the search easier and show you the options side by side. Listings, seats and prices come from the TicketNetwork marketplace, which also fulfills the order.</p>

<div class="so-seo-figs">
  <figure><img src="/images/crowd-at-concert-or-event.webp" alt="Fans cheering at a live event" width="442" height="442" loading="lazy"><figcaption>Live music is one of the biggest categories fans browse.</figcaption></figure>
  <figure><img src="/images/venue.webp" alt="Empty arena with rows of seats facing a lit stage" width="1536" height="1024" loading="lazy"><figcaption>Seat and section details are shown before you decide.</figcaption></figure>
</div>

<div class="so-mk">
<h2>How the marketplace is organized</h2>
<p>The easiest way to see how the site works is to follow the path a fan takes: pick a category, narrow down by city or performer, compare seats and prices, and check out.</p>
<ul class="so-mk__cats">
  <li><a class="so-mk__cat" href="/concert-tickets-for-sale"><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></span><strong>Concert tickets</strong><span>for tours, headliners and local shows.</span></a></li>
  <li><a class="so-mk__cat" href="/game-day-tickets"><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6c3.5 1.2 6.3 4 7.6 7.6M18.4 18.4c-3.5-1.2-6.3-4-7.6-7.6M8 16l8-8"/></svg></span><strong>Game day tickets</strong><span>for professional and college sports.</span></a></li>
  <li><a class="so-mk__cat" href="/buy-broadway-tickets"><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h10v5a5 5 0 0 1-10 0z"/><path d="M6 7h.01M10 7h.01M6 11c1 .8 3 .8 4 0"/><path d="M13 9h8v5a5 5 0 0 1-8 2.5"/><path d="M16 12h.01M19 12h.01M16 16c.8-.6 2.2-.6 3 0"/></svg></span><strong>Broadway and theater tickets</strong><span>for touring and long-running productions.</span></a></li>
  <li><a class="so-mk__cat" href="/upcoming-music-festivals"><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1M12 18v4M8 22h8"/></svg></span><strong>Music festivals</strong><span>across the calendar.</span></a></li>
  <li><a class="so-mk__cat" href="/city-events"><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span><strong>City listings</strong><span>if you want to see what is on near you.</span></a></li>
</ul>
<p>You can also search by performer or team on the <a href="/all-artists-and-teams">artists and teams page</a>. Our step-by-step guide to <a href="/how-to-buy-tickets-online">buying tickets online</a> shows each stage of an order.</p>

<div class="so-mk__grid">
<div class="so-mk__card so-mk__card--guar">
<h2><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 3v6c0 5.2-3.4 9.4-8 11-4.6-1.6-8-5.8-8-11V5z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg></span>The guarantee</h2>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>
</div>
<div class="so-mk__side">
<div class="so-mk__card">
<h2><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-3a2 2 0 0 0 0-4z"/><path d="M14 6v14" stroke-dasharray="2 2"/></svg></span>Why tickets are resold</h2>
<p>Fans resell tickets for ordinary reasons: plans change, a friend cannot make it, or someone bought a pair and only needs one. Marketplaces give those tickets a place to be listed and buyers a place to find them. For a short, neutral explanation, see the overview of <a href="https://en.wikipedia.org/wiki/Ticket_resale" target="_blank" rel="noopener">ticket resale on Wikipedia</a>. Our guide to <a href="/why-are-concert-tickets-so-expensive">why concert tickets cost what they do</a> explains how prices and fees add up.</p>
</div>
<div class="so-callout so-mk__note"><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg></span><p><strong>Good to know:</strong> we do not promise the lowest price on every event. We promise clear information about what you are buying and a way to reach us if something goes wrong. Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.</p></div>
<div class="so-mk__card">
<h2><span class="so-mk__ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span>Reaching us</h2>
<p>Use the form on the <a href="/ticket-customer-service">contact page</a> or email <a href="mailto:support@seatoutlet.com">support@seatoutlet.com</a>. Include your order ID if your question is about a purchase. Common questions are answered in the <a href="/ticket-faq">ticket FAQ</a>, and our <a href="/privacy-policy">privacy policy</a> explains how personal information is handled. If you are comparing sellers, see our <a href="/seat-outlet-reviews">reviews page</a> for how we handle feedback.</p>
</div>
</div>
</div>
</div>

<h2>Questions</h2>
<details class="so-faq"><summary>What is Seat Outlet?</summary><p>Seat Outlet is an online resale marketplace for tickets to concerts, sports, theater, festivals and other live events.</p></details>
<details class="so-faq"><summary>Who sets the prices?</summary><p>Sellers do. Prices may be above or below face value. Review the full cost at checkout before you pay.</p></details>
<details class="so-faq"><summary>Is Seat Outlet the event organizer?</summary><p>No. Seat Outlet is a marketplace and is not the event organizer or venue. Organizers and venues control the event itself.</p></details>
<details class="so-faq"><summary>How do I contact Seat Outlet?</summary><p>Use the contact form or email support@seatoutlet.com.</p></details>
