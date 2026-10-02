<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<h2>Before you buy: the short version</h2>
<p>Seat Outlet is a resale marketplace, so listings come from sellers and prices can be above or below face value. Read the event page for seat details, delivery method and venue rules before you check out, and review the full cost at checkout before you pay.</p>
<ol>
  <li>Find your event in <a href="/concert-tickets-for-sale">concerts</a>, <a href="/game-day-tickets">sports</a>, <a href="/buy-broadway-tickets">Broadway and theater</a> or <a href="/upcoming-music-festivals">festivals</a>.</li>
  <li>Compare sections, rows and prices on the event page.</li>
  <li>Check out and review the full cost before you pay.</li>
  <li>Watch for your confirmation email, then follow the delivery instructions for your event.</li>
</ol>
<p>New to buying online? Our guide to <a href="/how-to-buy-tickets-online">buying tickets online</a> walks through each step.</p>

<h2>Delivery and entry</h2>
<h3>Delivery timing</h3>
<p>Some tickets are delivered right away, while others arrive closer to the event date because organizers control when tickets are released. Delivery can be electronic, a mobile transfer or physical shipping, depending on the event and the seller.</p>
<h3>On the day</h3>
<p>Bring your ticket and, if the venue asks for it, a government-issued ID. Rules on bags, re-entry and late arrival vary by venue, so check the venue's own guidance. Tip: save your confirmation email and open your tickets a day early. If something looks wrong, <a href="/ticket-customer-service">contact us</a> well before doors open.</p>

<h2>Refunds, cancellations and the guarantee</h2>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>
<p>Exchanges depend on the event and organizer rules. The <a href="/terms-and-conditions">terms and conditions</a> have the legal detail, and the FTC publishes a helpful guide to <a href="https://consumer.ftc.gov/articles/online-shopping" target="_blank" rel="noopener">shopping safely online</a>.</p>

<h2>What differs by event type</h2>
<ul>
  <li><strong>Concerts:</strong> floor, lower bowl and upper sections price differently, and some shows use general admission.</li>
  <li><strong>Sports:</strong> most events have assigned seating, though a few offer general admission.</li>
  <li><strong>Theater:</strong> early arrival is common and late entry may be restricted.</li>
  <li><strong>Festivals:</strong> passes may cover several days and use special entry steps.</li>
</ul>
<p>Venues and organizers have the final say, so confirm the details on your event page.</p>

<h2>Payment and orders</h2>
<p>Checkout is hosted by TicketNetwork, and the payment options shown there depend on your order. If a payment fails, the cause is often a bank restriction, insufficient funds or a technical error, so try again or use a different method. Your order confirmation arrives by email after a successful payment. Seat Outlet has no customer accounts, so that email is your record of the order.</p>

<h2>Still need help?</h2>
<p>Reach us through the <a href="/ticket-customer-service">contact page</a> and include your order number if you have one. You can also compare options on our <a href="/ticket-deals">ticket deals page</a> or read <a href="/why-are-concert-tickets-so-expensive">why concert tickets cost what they do</a>.</p>

<h2>More questions</h2>
<details class="so-faq"><summary>Where do I find my tickets after I buy?</summary><p>Use your order confirmation email. Delivery timing depends on the event and the seller.</p></details>
<details class="so-faq"><summary>Do I have to print my ticket?</summary><p>Usually not. Most events accept mobile tickets, and printing is only needed if the event page says so.</p></details>
<details class="so-faq"><summary>Are Seat Outlet prices the same as face value?</summary><p>Not always. Sellers set resale prices, so they can be higher or lower than face value. Review the full cost at checkout before you pay.</p></details>
<details class="so-faq"><summary>What if my event is canceled?</summary><p>The guarantee provides a full refund, delivery fees excluded. If an event is rescheduled, tickets typically stay valid for the new date.</p></details>
<details class="so-faq"><summary>Who do I contact if this page does not answer my question?</summary><p>Email support@seatoutlet.com or use the form on the contact page.</p></details>
