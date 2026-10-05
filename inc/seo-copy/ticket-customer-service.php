<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<h2>How we can help</h2>
<p>Our support team can help with the questions that come up around a live-event order: where your tickets are, what happens if an event changes, and how the guarantee applies. Reach us by email or with the form on this page. Most questions are faster to resolve when you have your order ID and the event name ready, so keep your confirmation email nearby.</p>

<div class="so-seo-figs">
<figure><img src="/images/event-ticket-buying-800.webp" alt="Mobile ticket on a phone next to a laptop order page" width="800" height="533" loading="lazy"><figcaption>Your confirmation email has your order number and delivery details.</figcaption></figure>
<figure><img src="/images/venue.webp" alt="Empty arena with rows of seats facing a lit stage" width="1536" height="1024" loading="lazy"><figcaption>Tell us your section and row if your question is about seating.</figcaption></figure>
</div>

<h2>What to include in your message</h2>
<ol>
<li>Your order ID, from the confirmation email.</li>
<li>The event name, date and the section you bought.</li>
<li>The topic that fits: order inquiry, refund request, technical support, partnership or other.</li>
<li>What you expected and what happened, in a few sentences.</li>
</ol>

<h3>Orders and delivery</h3>
<p>Check your order confirmation email first. It has the delivery details for your event. If tickets have not arrived when you expect them, contact us and we will look into the order.</p>
<h3>Refunds and event changes</h3>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>
<p>We cannot promise a fixed number of days for a refund, because timing depends on the payment provider and the event. Tell us your order ID and we will check where it stands.</p>
<h3>Questions you can answer yourself</h3>
<p>Many common questions have short answers in the <a href="/ticket-faq">ticket FAQ</a>, such as how to access tickets and whether you need to print them. Our <a href="/why-are-concert-tickets-so-expensive">guide to concert ticket pricing</a> explains fees and resale.</p>

<h2>Staying safe</h2>
<p>Scammers sometimes pose as support teams. Contact us only through the email address or form on this page, and be cautious about any message that pushes you to pay outside the checkout or share a full card number. Our guide to <a href="/blog/how-to-avoid-ticket-scams">avoiding ticket scams</a> has more. If you need to report a suspicious message, the U.S. government lists where to <a href="https://www.usa.gov/consumer-complaints" target="_blank" rel="noopener">file a consumer complaint</a>.</p>
<div class="so-callout"><p>Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.</p></div>

<h2>Business enquiries</h2>
<p>Choose the Partnership topic in the form to reach us about working together, or read the <a href="/ticket-partner-program">partner page</a> first.</p>

<h2>Questions</h2>
<details class="so-faq"><summary>How do I contact Seat Outlet?</summary><p>Use the contact form on this page or email support@seatoutlet.com. A member of our team replies by email.</p></details>
<details class="so-faq"><summary>How do I check my order?</summary><p>Look in your order confirmation email. It has your order number and delivery details.</p></details>
<details class="so-faq"><summary>How long do refunds take?</summary><p>It depends on the payment provider and the event, so we cannot give a fixed number of days. Contact us with your order ID.</p></details>

<h2>Looking for Ticketmaster, SeatGeek or Vivid Seats support?</h2>
<p>Seat Outlet is an independent company and is not affiliated with Ticketmaster, SeatGeek or Vivid Seats. If you bought from one of them, contact that company directly for help with your order. If you bought from Seat Outlet, use the contact form or email on this page.</p>
