<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical.
$pageMetaTitle       = 'Seat Outlet BBB: Our Customer Commitments | Seat Outlet';
$pageMetaDescription = 'Looking for Seat Outlet on the Better Business Bureau? This page does not show a BBB rating. It explains the commitments we make to customers and how to reach us.';
$pageCanonicalUrl    = HOME_URL . '/seat-outlet-bbb';
require_once __DIR__ . '/inc/guarantee.php';
include 'header.php';
?>
<style>
.why-us-page .faq-card {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 18px 55px rgba(15, 23, 42, 0.12);
    border: 1px solid rgba(209, 213, 219, 0.8);
    padding: 0;
}
.why-us-page .faq-card .accordion {
    padding: 25px 30px;
}
.why-us-page .accordion-item {
    border-radius: 0.85rem !important;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    margin-bottom: 0.75rem;
    background-color: #ffffff;
}
.why-us-page .accordion-button:not(.collapsed) {
    color: #2556E0;
    background-color: rgba(37, 86, 224, 0.03);
    box-shadow: inset 0 -1px 0 rgba(229, 231, 235, 0.7);
}
.why-us-page .accordion-button::after {
    filter: hue-rotate(200deg);
}
.why-us-page .accordion-body {
    font-size: 0.95rem;
    color: #4b5563;
    line-height: 1.7;
    padding-top: 15px;
}
.why-us-page .accordion-button:focus {
    box-shadow: 0 0 0 0.15rem rgba(37, 86, 224, 0.28);
    border-color: #2556E0;
}
.why-us-page .accordion-button {
    padding-top: 0.9rem;
    padding-bottom: 0.9rem;
    font-weight: 500;
    font-size: 18px;
}
.why-us-page .faq-card h3 {
    font-size: 21px;
    font-weight: 600;
    padding: 25px 30px;
    background-color: #2556E0;
    border-radius: 10px 10px 0 0;
    color: #fff;
    margin: 0;
}
.why-us-page .section-title{font-size: 24px;
    font-weight: 700;
    margin-bottom: .6rem;}

@media (max-width: 991px) {
  .why-us-page .main-title {
    font-size: 36px !important;
    line-height: 1.2 !important;
}
    }
    @media (max-width: 576px) {
  .why-us-page .main-title {
    font-size: 24px !important;
    line-height: 1.2 !important;
}
    }
</style>


<div class="why-us-page">
<section class="hero-so-why">
    <div class="container">
        <div class="row align-items-center">
  <div class="hero-left col-lg-6 col-xl-6 col-xxl-6">
    <h1 class="main-title mb-lg-4 mb-3 text-white">Seat Outlet BBB: Our Customer Commitments</h1>
    <p class="text-white mb-4">People who search for Seat Outlet and the Better Business Bureau usually want to check that a ticket marketplace is legitimate before they pay. That is a smart habit.</p>
    <p class="text-white mb-4"><strong>This page does not show a BBB rating, grade or accreditation.</strong> To check any business, search for it on <a href="https://www.bbb.org" target="_blank" rel="noopener">bbb.org</a> and read what is listed there. Below are the commitments we make, so you can judge them for yourself.</p>
  </div>

  <div class="hero-right col-lg-6 col-xl-6 col-xxl-6">
    <div class="hero-visual position-relative mx-auto">
              <div class="hero-circle hero-circle-lg"></div>
              <div class="hero-circle hero-circle-sm"></div>
            </div>
  </div>
  </div>
    </div>
</section>

<!-- COMMITMENTS -->
<section id="commitment" class="bg-surface py-5">
  <div class="container">
    <div class="row align-items-start g-2 g-md-4 g-lg-5">
      <div class="col-lg-5">
        <span class="section-label">What we commit to</span>
        <h2 class="section-title">Our customer commitments</h2>
        <div class="section-divider"></div>
        <p class="text-muted mb-4">Seat Outlet is a resale marketplace. These are the things you can check for yourself before and after you order.</p>
      </div>
      <div class="col-lg-7">
        <ul class="check-list">
          <li><i class="bi bi-patch-check-fill"></i> Orders are covered by the TicketNetwork 100% guarantee, explained on our <a href="/worry-free-guarantee">guarantee page</a></li>
          <li><i class="bi bi-tag-fill"></i> Prices are set by sellers and may be above or below face value, and the full cost is shown at checkout before you pay</li>
          <li><i class="bi bi-file-text-fill"></i> Our <a href="/terms-and-conditions">terms</a>, <a href="/privacy-policy">privacy policy</a> and <a href="/cookie-policy">cookie policy</a> are public and dated</li>
          <li><i class="bi bi-envelope-fill"></i> You can reach a person by <a href="/ticket-customer-service">email or the contact form</a></li>
        </ul>
        <div class="feature-card mt-4">
          <h3 class="h5">What the guarantee covers</h3>
          <?php echo soGuaranteeList(); ?>
          <p class="text-muted small mb-0"><?php echo htmlspecialchars(soGuaranteeLimits(), ENT_QUOTES, 'UTF-8'); ?> <?php echo soGuaranteeLinks(); ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CHECKLIST -->
<section id="policies" class="py-5" style="background:#fff;">
  <div class="container">
    <div class="row g-2 g-md-4 g-lg-5 align-items-start">
      <div class="col-lg-5">
        <span class="section-label">Before you pay anyone</span>
        <h2 class="section-title">How to check any ticket seller</h2>
        <div class="section-divider"></div>
      </div>
      <div class="col-lg-7">
        <ul class="check-list">
          <li><i class="bi bi-check-circle-fill"></i> Read the refund, delivery and cancellation policies before you enter payment details</li>
          <li><i class="bi bi-check-circle-fill"></i> Confirm the full cost, including fees, is shown before you pay</li>
          <li><i class="bi bi-check-circle-fill"></i> Look for a real way to contact the seller, and for independent feedback, including complaints</li>
          <li><i class="bi bi-check-circle-fill"></i> Keep your confirmation email and order ID</li>
        </ul>
        <p class="text-muted mt-3">What we can and cannot show about feedback on our own site is explained on the <a href="/seat-outlet-reviews">reviews page</a> and the <a href="/customer-testimonials">testimonials page</a>. Our guide to <a href="/blog/how-to-avoid-ticket-scams">avoiding ticket scams</a> goes into more detail.</p>
      </div>
    </div>
  </div>
</section>

<section class="faq-section py-3 py-md-2 py-lg-2">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="faq-card mb-4">
                            <h2 class="h3 mb-0">Frequently Asked Questions</h2>
                            <div class="accordion mb-0" id="purchaseFaq">
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#purchaseOne">
                                    Does Seat Outlet have a BBB rating?
                                    </button>
                                    <div id="purchaseOne" class="accordion-collapse collapse show" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        This page does not show a BBB rating or accreditation. Please check the Better Business Bureau website directly for anything listed there.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseTwo">
                                    Who stands behind my order?
                                    </button>
                                    <div id="purchaseTwo" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        <?php echo htmlspecialchars(soGuaranteeSentence(), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseThree">
                                    What should I do if I have an issue with my order?
                                    </button>
                                    <div id="purchaseThree" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        Use the <a href="/ticket-customer-service">contact form</a> or email support@seatoutlet.com and include your order ID.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseFour">
                                    Are tickets cheaper than face value?
                                    </button>
                                    <div id="purchaseFour" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        Not necessarily. Prices are set by sellers and may be above or below face value. Review the full cost at checkout before you pay.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<!-- CTA -->
<section class="hero-so-why-bottom">
<div class="container">
 <div class="text-center">
    <h2 class="text-white">Ready to look at tickets?</h2>
    <p style="color:#fff;">Compare seats and prices, and review the full cost at checkout before you pay.</p>
  <a href="/buy-tickets-online" class="btn btn-primary h-auto px-3 py-2 mt-4">Browse events</a>
</div>
</div>
</section>


</div>

<?php soSeoCopy('seat-outlet-bbb'); ?>
<?php include 'footer.php'; ?>
