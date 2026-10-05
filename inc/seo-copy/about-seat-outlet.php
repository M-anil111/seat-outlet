<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<h2>About Seat Outlet: what we do</h2>
<p>Seat Outlet is an online ticket marketplace where fans can find, compare and buy tickets to concerts, sports, theater, festivals and other live events. We are a resale marketplace, which means tickets are offered by sellers and prices are set by them, not by us.</p>
<p>Because sellers set the price, a ticket may cost more or less than its face value. Our job is to make the search easier and show you the options side by side. Listings, seats and prices come from the TicketNetwork marketplace, which also fulfills the order.</p>

<div class="so-seo-figs">
  <figure><img src="/images/crowd-at-concert-or-event.webp" alt="Fans cheering at a live event" width="442" height="442" loading="lazy"><figcaption>Live music is one of the biggest categories fans browse.</figcaption></figure>
  <figure><img src="/images/venue.webp" alt="Empty arena with rows of seats facing a lit stage" width="1536" height="1024" loading="lazy"><figcaption>Seat and section details are shown before you decide.</figcaption></figure>
</div>

<h2>How the marketplace is organized</h2>
<p>The easiest way to see how the site works is to follow the path a fan takes: pick a category, narrow down by city or performer, compare seats and prices, and check out.</p>
<ul>
  <li><a href="/concert-tickets-for-sale">Concert tickets</a> for tours, headliners and local shows.</li>
  <li><a href="/game-day-tickets">Game day tickets</a> for professional and college sports.</li>
  <li><a href="/buy-broadway-tickets">Broadway and theater tickets</a> for touring and long-running productions.</li>
  <li><a href="/upcoming-music-festivals">Music festivals</a> across the calendar.</li>
  <li><a href="/city-events">City listings</a> if you want to see what is on near you.</li>
</ul>
<p>You can also search by performer or team on the <a href="/all-artists-and-teams">artists and teams page</a>. Our step-by-step guide to <a href="/how-to-buy-tickets-online">buying tickets online</a> shows each stage of an order.</p>

<h2>The guarantee</h2>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>

<h2>Why tickets are resold</h2>
<p>Fans resell tickets for ordinary reasons: plans change, a friend cannot make it, or someone bought a pair and only needs one. Marketplaces give those tickets a place to be listed and buyers a place to find them. For a short, neutral explanation, see the overview of <a href="https://en.wikipedia.org/wiki/Ticket_resale" target="_blank" rel="noopener">ticket resale on Wikipedia</a>. Our guide to <a href="/why-are-concert-tickets-so-expensive">why concert tickets cost what they do</a> explains how prices and fees add up.</p>
<div class="so-callout"><p><strong>Good to know:</strong> we do not promise the lowest price on every event. We promise clear information about what you are buying and a way to reach us if something goes wrong. Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.</p></div>

<h2>Reaching us</h2>
<p>Use the form on the <a href="/ticket-customer-service">contact page</a> or email <a href="mailto:support@seatoutlet.com">support@seatoutlet.com</a>. Include your order ID if your question is about a purchase. Common questions are answered in the <a href="/ticket-faq">ticket FAQ</a>, and our <a href="/privacy-policy">privacy policy</a> explains how personal information is handled. If you are comparing sellers, see our <a href="/seat-outlet-reviews">reviews page</a> for how we handle feedback.</p>

<h2>Questions</h2>
<details class="so-faq"><summary>What is Seat Outlet?</summary><p>Seat Outlet is an online resale marketplace for tickets to concerts, sports, theater, festivals and other live events.</p></details>
<details class="so-faq"><summary>Who sets the prices?</summary><p>Sellers do. Prices may be above or below face value. Review the full cost at checkout before you pay.</p></details>
<details class="so-faq"><summary>Is Seat Outlet the event organizer?</summary><p>No. Seat Outlet is a marketplace and is not the event organizer or venue. Organizers and venues control the event itself.</p></details>
<details class="so-faq"><summary>How do I contact Seat Outlet?</summary><p>Use the contact form or email support@seatoutlet.com.</p></details>
