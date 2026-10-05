<?php
require_once 'functions.php';

// Ticket policies for orders placed on Seat Outlet. The policy text itself is served by TicketNetwork (our fulfillment partner)
// through the script below, so it always matches what applies at checkout. This URL is the "policy page" link TicketNetwork
// asked for; checkout links to it.
$pageMetaTitle = 'Ticket Policies for Orders on Seat Outlet';
$pageMetaDescription = 'Read the ticket purchase, delivery and refund policies that apply to orders placed on Seat Outlet before you check out and pay.';
$pageCanonicalUrl = HOME_URL . '/policies';
include 'header.php';
?>
<section>
	<div class="container py-4" style="max-width: 920px;">
		<h1 class="fs-2 fw-bold mb-2">Ticket Policies</h1>
		<p class="text-muted mb-4">These are the policies that apply to tickets ordered on Seat Outlet, including purchase, delivery and refund terms. Please read them before you check out.</p>
		<div id="so-ticket-policies">
			<script type="text/javascript" src="https://tickettransaction.com/?https=true&amp;bid=9250&amp;sitenumber=30&amp;tid=600"></script>
		</div>
		<noscript>
			<p>This page needs JavaScript to show the ticket policies. You can also read our <a href="/terms-and-conditions">Terms of Use</a>, <a href="/privacy-policy">Privacy Policy</a> and <a href="/worry-free-guarantee">guarantee</a>, or email <a href="mailto:info@seatoutlet.com">info@seatoutlet.com</a>.</p>
		</noscript>
		<p class="small mt-4 mb-0">Questions about an order? Visit <a href="/ticket-customer-service">Customer Service</a> or email <a href="mailto:info@seatoutlet.com">info@seatoutlet.com</a>. See also our <a href="/terms-and-conditions">Terms of Use</a>, <a href="/privacy-policy">Privacy Policy</a> and <a href="/worry-free-guarantee">guarantee</a>.</p>
	</div>
</section>
<?php include 'footer.php'; ?>
