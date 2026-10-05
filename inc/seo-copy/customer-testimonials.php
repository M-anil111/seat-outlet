<?php if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); header('X-Robots-Tag: noindex'); exit; } ?>
<h2>How we plan to handle testimonials</h2>
<p>A testimonial is only worth reading if it comes from a real customer. Our plan is to publish testimonials only when they can be checked against a real order, to include critical comments as well as positive ones, and not to edit out useful complaints. Until that is in place, this page stays free of quotes and statistics.</p>
<p>The <a href="https://en.wikipedia.org/wiki/Testimonial" target="_blank" rel="noopener">testimonial overview on Wikipedia</a> describes the general idea, and the FTC's <a href="https://www.ftc.gov/business-guidance/resources/consumer-reviews-testimonials-rule-questions-answers" target="_blank" rel="noopener">questions and answers on consumer reviews</a> set out how honest endorsements should work.</p>

<h2>Share your own experience</h2>
<p>If you have bought from Seat Outlet, write to us through the <a href="/ticket-customer-service">contact form</a> or at <a href="mailto:support@seatoutlet.com">support@seatoutlet.com</a>. Helpful feedback includes the event, date and venue, how the tickets were delivered, anything that was unclear, and your order ID if you want us to look into a specific purchase.</p>

<h2>What stands behind an order</h2>
<?php require_once __DIR__ . '/../guarantee.php'; echo soGuaranteeBlock(); ?>
<p>More: <a href="/seat-outlet-reviews">how to judge any ticket seller</a>, <a href="/seat-outlet-bbb">our customer commitments</a>, and <a href="/about-seat-outlet">about Seat Outlet</a>.</p>

<h2>Questions</h2>
<details class="so-faq"><summary>Are there testimonials on this page?</summary><p>No. We would rather show none than show testimonials we cannot verify.</p></details>
<details class="so-faq"><summary>Is there a refund if an event is canceled?</summary><p>Yes, under the guarantee: a full refund (delivery fees excluded) if the event is canceled.</p></details>
