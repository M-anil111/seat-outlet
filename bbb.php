<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical.
$pageMetaTitle       = 'Seat Outlet BBB Profile & Customer Commitment | Seat Outlet';
$pageMetaDescription = 'Learn about Seat Outlet\'s commitment to secure transactions, verified ticket listings, and transparent policies as a trusted ticket marketplace.';
$pageCanonicalUrl    = HOME_URL . '/bbb';
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
    <h1 class="main-title mb-lg-4 mb-3 text-white">Seat Outlet BBB Profile & Customer Commitment</h1>
    <p class="text-white mb-4">At Seat Outlet, we are committed to providing a secure, transparent, and reliable ticket-buying experience. Customer trust is our goal: we build it with every customer by delivering verified tickets, clear policies, and responsive support.</br> </br>
    We understand that purchasing event tickets online requires confidence. That’s why we prioritize customer satisfaction, safe transactions, and honest communication in everything we do.
    </p>
    <!-- <a href="#" class="btn btn-primary h-auto px-3 py-2">Work With Us</a> -->
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
 
<!-- ════════════════════ STATS ════════════════════ -->
<section class="py-5 bg-surface">
  <div class="container experience-section">
    <div class="stats-strip">
      <div class="row g-4 align-items-center justify-content-center">
        <div class="col-6 col-md-3">
          <div class="stat-num">100%</div>
          <div class="stat-label">Verified Tickets</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-num">SSL</div>
          <div class="stat-label">Encrypted Checkout</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-num">24/7</div>
          <div class="stat-label">Support Available</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-num">0</div>
          <div class="stat-label">Hidden Fees Policy</div>
        </div>
      </div>
    </div>
  </div>
</section>
 
<!-- ════════════════════ COMMITMENT ════════════════════ -->
<section id="commitment" class="bg-surface pb-5">
  <div class="container">
    <div class="row align-items-center g-2 g-md-4 g-lg-5">
      <div class="col-lg-5">
        <span class="section-label">Customer First</span>
        <h2 class="section-title">Our Commitment to Customer Trust</h2>
        <div class="section-divider"></div>
        <img src="/images/ticket-trusted.webp" class="img-fluid rounded mb-3" alt="Customer trust in Seat Outlet's ticket marketplace" loading="lazy" width="750" height="875" decoding="async">
        <p class="text-muted mb-4">Seat Outlet operates with a customer-first approach. Every transaction is handled with care to ensure buyers receive valid tickets for their chosen events. Our team works continuously to improve service quality and ensure a smooth experience from browsing to checkout - the same kind of consumer trust the
        <a href="https://www.bbb.org" target="_blank" rel="noopener">Better Business Bureau</a> encourages shoppers to look for online.</p>
        <ul class="check-list">
          <li><i class="bi bi-shield-lock-fill"></i> Secure and encrypted checkout process</li>
          <li><i class="bi bi-patch-check-fill"></i> Verified ticket listings from trusted sources</li>
          <li><i class="bi bi-tag-fill"></i> Transparent pricing with no hidden surprises</li>
          <li><i class="bi bi-clock-fill"></i> Timely delivery of tickets before the event</li>
        </ul>
      </div>
      <div class="col-lg-7">
        <div class="commitment-band mt-3 mt-md-0 mt-lg-0">
          <span class="section-label">Why It Matters</span>
          <h2 class="mb-3" style="font-size:1.6rem;">Building Long-Term Trust with Every Customer</h2>
          <p style="color:rgba(255,255,255,.75); font-size:.95rem;">We understand that purchasing event tickets online requires confidence. That's why we prioritize customer satisfaction, safe transactions, and honest communication in everything we do.</p>
          <div class="row g-3 mt-2">
            <div class="col-sm-6">
              <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:1.1rem;">
                <i class="bi bi-people-fill text-accent fs-4"></i>
                <div style="color:#fff;font-weight:600;margin-top:.5rem;">Customer Satisfaction</div>
                <div style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:.2rem;">Our priority in every interaction</div>
              </div>
            </div>
            <div class="col-sm-6">
              <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:1.1rem;">
                <i class="bi bi-lock-fill text-accent fs-4"></i>
                <div style="color:#fff;font-weight:600;margin-top:.5rem;">Secure Transactions</div>
                <div style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:.2rem;">Safe payments, every time</div>
              </div>
            </div>
            <div class="col-sm-6">
              <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:1.1rem;">
                <i class="bi bi-megaphone-fill text-accent fs-4"></i>
                <div style="color:#fff;font-weight:600;margin-top:.5rem;">Honest Communication</div>
                <div style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:.2rem;">Clear, straightforward policies</div>
              </div>
            </div>
            <div class="col-sm-6">
              <div style="background:rgba(255,255,255,.07);border-radius:10px;padding:1.1rem;">
                <i class="bi bi-arrow-repeat text-accent fs-4"></i>
                <div style="color:#fff;font-weight:600;margin-top:.5rem;">Continuous Improvement</div>
                <div style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:.2rem;">Always refining our service</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
 

<!-- ════════════════════ SUPPORT ════════════════════ -->
<section id="support" style="background:#fff;">
  <div class="container py-5">
    <div class="text-center mb-5">
      <span class="section-label">Help When You Need It</span>
      <h2 class="section-title">Customer Support You Can Rely On</h2>
      <div class="section-divider mx-auto"></div>
      <p class="text-muted mx-auto" style="max-width:560px;">Whether you have questions before buying or need help after placing an order, our support team is available to resolve concerns quickly and professionally - built on the same customer trust that guides everything we do.</p>
    </div>
    <div class="d-flex justify-content-center flex-wrap gap-3 mb-4">
      <img src="/images/team-event.webp" class="img-fluid rounded" style="max-width:200px;" alt="Support team that builds customer trust at Seat Outlet" loading="lazy" width="250" height="250" decoding="async">
      <img src="/images/secure-payment-p3.png" class="img-fluid rounded" style="max-width:200px;" alt="Secure payment builds customer trust" loading="lazy" width="65" height="68" decoding="async">
      <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded" style="max-width:200px;" alt="Fans who rely on our customer trust commitment" loading="lazy" width="442" height="442" decoding="async">
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="support-item">
          <i class="bi bi-receipt-cutoff"></i>
          <div>
            <strong>Order Status Updates</strong>
            <p>Track your order in real time from confirmation to delivery.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="support-item">
          <i class="bi bi-ticket-perforated"></i>
          <div>
            <strong>Ticket Delivery Assistance</strong>
            <p>Guidance on receiving and accessing your tickets before the event.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="support-item">
          <i class="bi bi-calendar-event"></i>
          <div>
            <strong>Event Information</strong>
            <p>Accurate details about venues, dates, and entry requirements.</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="support-item">
          <i class="bi bi-headset"></i>
          <div>
            <strong>Issue Resolution</strong>
            <p>Prompt follow-ups to ensure every concern is fully resolved.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ════════════════════ POLICIES ════════════════════ -->
<section id="policies" class="bg-surface py-5">
  <div class="container">
    <div class="row g-2 g-md-4 g-lg-5 align-items-start">
      <div class="col-lg-4">
        <span class="section-label">Transparency First</span>
        <h2 class="section-title">Transparent Policies &amp; Practices</h2>
        <div class="section-divider"></div>
        <p class="text-muted">We believe in clear and straightforward policies so customers always know what to expect. We encourage customers to review all details before purchase to ensure a smooth experience.</p>
        <a href="/terms-and-conditions" class="btn common-btn mt-4">Review Full Policies</a>
      </div>
      <div class="col-lg-8">
        <div class="row g-4 pt-4 pt-lg-0 pt-md-0">
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-file-text-fill"></i></div>
              <h4>Clear Terms &amp; Conditions</h4>
              <p>Plain-language terms that are easy to understand before you buy.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
              <h4>Refund &amp; Replacement Policy</h4>
              <p>Defined processes for refunds and replacements, clearly communicated.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
              <h4>Secure Payment Processing</h4>
              <p>Industry-standard encryption protects every transaction you make.</p>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-info-circle-fill"></i></div>
              <h4>Accurate Event Details</h4>
              <p>Up-to-date event and ticket information so there are no surprises.</p>
            </div>
          </div>
        </div>
        <p class="text-muted mt-4">
          Customer trust isn't something a company can claim for itself - it's earned one order at a
          time, through policies that are actually followed and support that actually helps. That's
          the standard we hold ourselves to: if a policy sounds good in writing but falls apart the
          moment a customer needs it, it isn't a real policy. We'd rather have fewer promises that we
          keep than a long list of ones we don't, because customer trust built on real follow-through
          is the only kind worth having.
        </p>
      </div>
    </div>
  </div>
</section>
<section class="faq-section py-3 py-md-2 py-lg-2">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="faq-card mb-4">
                            <!-- 1. Ticket Purchase & Delivery -->
                            <h3 class="mb-0">Frequently Asked Questions</h3>
                            <div class="accordion mb-0" id="purchaseFaq">
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent" data-bs-toggle="collapse" data-bs-target="#purchaseOne">
                                    Is Seat Outlet a trusted ticket platform?
                                    </button>
                                    <div id="purchaseOne" class="accordion-collapse collapse show" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        Yes, Seat Outlet uses secure payment systems and verified ticket sources to provide a safe buying experience.

                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseTwo">
                                    How does Seat Outlet ensure ticket authenticity?
                                    </button>
                                    <div id="purchaseTwo" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        We work with trusted partners and use verification processes to ensure tickets are valid for entry.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseThree">
                                    What should I do if I have an issue with my order?
                                    </button>
                                    <div id="purchaseThree" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        You can contact our support team for assistance with any order-related concerns.

                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item border-0">
                                    <button class="accordion-button bg-transparent collapsed" data-bs-toggle="collapse" data-bs-target="#purchaseFour">
                                    Are ticket prices transparent?
                                    </button>
                                    <div id="purchaseFour" class="accordion-collapse collapse" data-bs-parent="#purchaseFaq">
                                        <div class="accordion-body">
                                        Yes, we aim to provide clear pricing and avoid hidden charges.
                                        </div>
                                    </div>
                                </div>
                              
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<!-- ── CTA ── -->
<section class="hero-so-why-bottom">
<div class="container">
 <div class="text-center">
    <h2 class="text-white"> A Ticket Platform You Can Trust</h2>
    <p style="color:#fff;">Customer trust is at the center of everything we do - we maintain high standards of customer satisfaction, secure transactions, and transparent practices.</p>
  <a href="/" class="btn btn-primary h-auto px-3 py-2 mt-4">Start Your Search Today</a>
</div>
</div>
</section>


</div>

<?php include 'footer.php'; ?>