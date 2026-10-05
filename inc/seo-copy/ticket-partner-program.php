<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<h2>Working with Seat Outlet</h2>
<p>Seat Outlet is a resale marketplace, not a primary ticket seller. Event listings, seats and prices come from the TicketNetwork marketplace, and orders are fulfilled through TicketNetwork. That shapes what a conversation with us can and cannot cover, so it helps to know before you write.</p>
<p>We do not publish fixed terms, fees or commission rates, and nothing on this page is an offer. If you run a venue, festival, team or business, use the form above and tell us what you have in mind.</p>

<h2>Questions worth asking any marketplace</h2>
<p>Whoever you talk to, get answers in writing to these before you agree to anything.</p>
<ol>
  <li>How are prices set, and who controls them?</li>
  <li>What fees apply, and who pays them?</li>
  <li>How and when are you paid?</li>
  <li>What happens if an event is canceled, postponed or rescheduled?</li>
  <li>How are buyer problems handled, and what is promised to fans?</li>
  <li>Who is your day-to-day contact?</li>
</ol>

<h2>What fans are told</h2>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>
<p>Venues and organizers set their own entry and safety rules, and our <a href="/terms-and-conditions">terms and conditions</a> say those usually govern attendance at the event.</p>

<h2>Questions</h2>
<details class="so-faq"><summary>Does Seat Outlet publish partner fees?</summary><p>No. We do not publish fixed fees or rates. Ask for any terms in writing during your conversation with our team.</p></details>
<details class="so-faq"><summary>How do I get in touch?</summary><p>Use the enquiry form on this page, or choose the Partnership topic on the <a href="/ticket-customer-service">contact page</a>. You can also email <a href="mailto:support@seatoutlet.com">support@seatoutlet.com</a>.</p></details>
<details class="so-faq"><summary>Is Seat Outlet affiliated with venues, teams or artists?</summary><p>No. Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist. Names are used only to identify events.</p></details>
