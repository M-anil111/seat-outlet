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
/* ===== Seat Outlet BBB page (content between header and footer) ===== */

.so-bbb { background: #fff; }
.so-bbb { --bbb-blue: #2556e0; --bbb-navy: #0f1b3d; --bbb-text: #3f4a5e; --bbb-line: #e3e8f2; --bbb-pale: #f1f5ff; color: var(--bbb-text); }
.so-bbb .container { max-width: 1140px; }
.so-bbb a:not(.btn) { color: var(--bbb-blue); text-underline-offset: 2px; }
.so-bbb-label { display: block; margin-bottom: 8px; font-size: 12px; font-weight: 700; letter-spacing: .18em; text-transform: uppercase; color: var(--bbb-blue); }
.so-bbb-h2 { margin: 0 0 12px; font-size: clamp(24px, 2.6vw, 32px); font-weight: 800; line-height: 1.2; letter-spacing: -.01em; color: var(--bbb-navy); }
.so-bbb-lead { max-width: 640px; margin: 0; font-size: 16px; line-height: 1.65; color: var(--bbb-text); }
.so-bbb-sec { padding: 72px 0; background: #fff; }
.so-bbb-sec--tint { background: #f7f9fd; border-top: 1px solid #edf1f8; border-bottom: 1px solid #edf1f8; }
.so-bbb-ic { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 48px; height: 48px; border-radius: 50%; background: #e7eeff; color: var(--bbb-blue); }

/* Hero (keeps .hero-so-why so the page's ad banner still follows it; the shared .hero-so-why rules are !important) */
.so-bbb .hero-so-why.so-bbb-hero { position: relative; overflow: hidden; min-height: 0 !important; padding: 72px 0 !important; text-align: left !important; background: linear-gradient(135deg, #f7f9ff 0%, #ecf2ff 100%) !important; }
.so-bbb-hero::before { content: ""; position: absolute; right: -12%; top: -40%; width: 55%; aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, rgba(214, 226, 255, .85) 0, rgba(214, 226, 255, 0) 70%); pointer-events: none; }
.so-bbb-hero > .container { position: relative; z-index: 1; }
.so-bbb-hero__grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr); gap: 40px; align-items: center; }
.so-bbb .so-bbb-hero h1 { margin: 0 0 16px; font-size: clamp(30px, 3.6vw, 46px); font-weight: 800; line-height: 1.12; letter-spacing: -.02em; color: var(--bbb-navy); }
.so-bbb .so-bbb-hero p { margin: 0 0 22px; font-size: 17px !important; line-height: 1.65; color: var(--bbb-text) !important; }
.so-bbb .so-bbb-hero a:not(.btn) { color: var(--bbb-blue) !important; }
.so-bbb-note { display: flex; gap: 16px; align-items: flex-start; max-width: 620px; padding: 18px 22px; border: 1px solid #d7e2fb; border-radius: 14px; background: rgba(233, 240, 255, .8); }
.so-bbb .so-bbb-note p { margin: 0; font-size: 15px !important; line-height: 1.6; }
.so-bbb-note strong { display: block; margin-bottom: 2px; color: var(--bbb-navy); }
.so-bbb-note .so-bbb-ic { width: 36px; height: 36px; background: var(--bbb-blue); color: #fff; }
.so-bbb-hero__art { position: relative; display: flex; align-items: center; justify-content: center; aspect-ratio: 1; max-width: 360px; width: 100%; margin-left: auto; border-radius: 50%; background: radial-gradient(circle, #e3ebff 0, #eef3ff 60%, rgba(238, 243, 255, 0) 72%); color: var(--bbb-blue); }
.so-bbb-hero__art svg { width: 62%; height: auto; }
.so-bbb-hero__art::before, .so-bbb-hero__art::after { content: ""; position: absolute; border-radius: 50%; background: #d6e2ff; }
.so-bbb-hero__art::before { width: 14px; height: 14px; left: 8%; top: 18%; }
.so-bbb-hero__art::after { width: 10px; height: 10px; right: 10%; bottom: 20%; }

/* Commitments */
.so-bbb-cards { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; margin: 32px 0 22px; padding: 0; list-style: none; }
.so-bbb-card { height: 100%; padding: 24px 22px; border: 1px solid var(--bbb-line); border-radius: 14px; background: #fff; box-shadow: 0 6px 18px rgba(15, 27, 61, .04); }
.so-bbb-card .so-bbb-ic { margin-bottom: 16px; }
.so-bbb-card h3 { margin: 0 0 8px; font-size: 17px; font-weight: 700; line-height: 1.35; color: var(--bbb-navy); }
.so-bbb-card p { margin: 0; font-size: 15px; line-height: 1.6; }
.so-bbb-guar { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 24px; padding: 28px 30px; border: 1px solid #d7e2fb; border-radius: 16px; background: var(--bbb-pale); }
.so-bbb-guar__ic { width: 76px; height: 76px; background: #fff; box-shadow: 0 6px 16px rgba(37, 86, 224, .12); }
.so-bbb-guar h3 { margin: 0 0 14px; font-size: 20px; font-weight: 800; color: var(--bbb-navy); }
.so-bbb-guar ul { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 28px; margin: 0 0 18px; padding: 0; list-style: none; }
.so-bbb-guar li { position: relative; padding-left: 32px; font-size: 15px; line-height: 1.55; }
.so-bbb-guar li::before { content: ""; position: absolute; left: 0; top: 1px; width: 22px; height: 22px; border-radius: 50%; background: var(--bbb-blue) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12.5l4 4 8-9'/%3E%3C/svg%3E") center / 14px no-repeat; }
.so-bbb-guar__limits { margin: 0; font-size: 14px; line-height: 1.65; color: #4b5565; }

/* Checklist */
.so-bbb-steps { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 30px 0 22px; padding: 0; list-style: none; counter-reset: bbbstep; }
.so-bbb-steps li { counter-increment: bbbstep; display: flex; align-items: center; gap: 16px; padding: 20px 22px; border: 1px solid var(--bbb-line); border-radius: 14px; background: #fff; font-size: 16px; font-weight: 600; line-height: 1.5; color: var(--bbb-navy); }
.so-bbb-steps li::before { content: counter(bbbstep); flex: none; display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; background: var(--bbb-blue); color: #fff; font-size: 16px; font-weight: 700; }
.so-bbb-after { max-width: 760px; margin: 0; font-size: 15px; line-height: 1.7; }

/* FAQ: the shared .so-qa accordion design (style.css); only the card around it is page-specific */
.so-bbb-faq { padding: 8px 0 72px; }
.so-bbb-faq .so-bbb-h2 { margin-bottom: 20px; }
.so-bbb-faq__card { padding: 10px; border: 1px solid var(--bbb-line); border-radius: 16px; background: #fff; box-shadow: 0 10px 30px rgba(15, 27, 61, .06); }
.so-bbb-faq__card .so-qa.so-qa .accordion-item:first-of-type { border-top: 0; }
.so-bbb-faq__card .so-qa.so-qa .accordion-item:last-of-type { border-bottom: 0; }

/* CTA */
.so-bbb-cta { padding: 64px 0; background: linear-gradient(120deg, #1e47c9 0%, #2556e0 55%, #3d6bff 100%); color: #fff; text-align: center; }
.so-bbb-cta h2 { margin: 0 0 10px; font-size: clamp(24px, 2.8vw, 34px); font-weight: 800; color: #fff; }
.so-bbb-cta p { margin: 0 auto 26px; max-width: 560px; font-size: 16px; line-height: 1.6; color: rgba(255, 255, 255, .9); }
.so-bbb .so-bbb-cta .btn { display: inline-flex; align-items: center; min-height: 48px; padding: 0 30px; border: 0; border-radius: 10px; background: #fff; color: var(--bbb-blue); font-weight: 700; box-shadow: 0 10px 24px rgba(10, 30, 90, .25); }
.so-bbb .so-bbb-cta .btn:hover { background: #eef3ff; color: #1e47c9; }

@media (max-width: 1199.98px) {
  .so-bbb-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
  .so-bbb .hero-so-why.so-bbb-hero { padding: 56px 0 !important; }
  .so-bbb-hero__grid { grid-template-columns: 1fr; gap: 28px; }
  .so-bbb-hero__art { display: none; }
  .so-bbb-sec { padding: 56px 0; }
  .so-bbb-guar ul { grid-template-columns: 1fr; }
  .so-bbb-steps { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
  .so-bbb .hero-so-why.so-bbb-hero { padding: 40px 0 !important; }
  .so-bbb .so-bbb-hero p { font-size: 16px !important; }
  .so-bbb-note { padding: 16px; gap: 12px; }
  .so-bbb-sec { padding: 44px 0; }
  .so-bbb-cards { grid-template-columns: 1fr; gap: 14px; }
  .so-bbb-guar { grid-template-columns: 1fr; gap: 14px; padding: 22px 18px; }
  .so-bbb-guar__ic { width: 60px; height: 60px; }
  .so-bbb-steps li { padding: 16px; font-size: 15px; }
  .so-bbb-faq { padding-bottom: 48px; }
  .so-bbb-cta { padding: 48px 0; }
}
</style>

<div class="so-bbb">
<section class="hero-so-why so-bbb-hero">
  <div class="container">
    <div class="so-bbb-hero__grid">
      <div>
        <h1>Seat Outlet BBB: Our Customer Commitments</h1>
        <p>People who search for Seat Outlet and the Better Business Bureau usually want to check that a ticket marketplace is legitimate before they pay. That is a smart habit.</p>
        <div class="so-bbb-note">
          <span class="so-bbb-ic" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg></span>
          <p><strong>This page does not show a BBB rating, grade or accreditation.</strong> To check any business, search for it on <a href="https://www.bbb.org" target="_blank" rel="noopener">bbb.org</a> and read what is listed there. Below are the commitments we make, so you can judge them for yourself.</p>
        </div>
      </div>
      <div class="so-bbb-hero__art" aria-hidden="true"><svg width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.5l8 3v6c0 5-3.4 9.1-8 10.5-4.6-1.4-8-5.5-8-10.5v-6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg></div>
    </div>
  </div>
</section>

<!-- COMMITMENTS -->
<section id="commitment" class="so-bbb-sec">
  <div class="container">
    <span class="so-bbb-label">What we commit to</span>
    <h2 class="so-bbb-h2">Our customer commitments</h2>
    <p class="so-bbb-lead">Seat Outlet is a resale marketplace. These are the things you can check for yourself before and after you order.</p>
    <ul class="so-bbb-cards">
      <li class="so-bbb-card"><span class="so-bbb-ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.5l8 3v6c0 5-3.4 9.1-8 10.5-4.6-1.4-8-5.5-8-10.5v-6z"/></svg></span><h3>Orders are protected by the TicketNetwork guarantee</h3><p>Orders are covered by the TicketNetwork 100% guarantee, explained on our <a href="/worry-free-guarantee">guarantee page</a></p></li>
      <li class="so-bbb-card"><span class="so-bbb-ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span><h3>Prices are set by sellers</h3><p>Prices are set by sellers and may be above or below face value, and the full cost is shown at checkout before you pay</p></li>
      <li class="so-bbb-card"><span class="so-bbb-ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg></span><h3>Our policies are public</h3><p>Our <a href="/terms-and-conditions">terms</a>, <a href="/privacy-policy">privacy policy</a> and <a href="/cookie-policy">cookie policy</a> are public and dated</p></li>
      <li class="so-bbb-card"><span class="so-bbb-ic"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span><h3>You can reach a person</h3><p>You can reach a person by <a href="/ticket-customer-service">email or the contact form</a></p></li>
    </ul>
    <div class="so-bbb-guar">
      <span class="so-bbb-ic so-bbb-guar__ic" aria-hidden="true"><svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.5l8 3v6c0 5-3.4 9.1-8 10.5-4.6-1.4-8-5.5-8-10.5v-6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/></svg></span>
      <div>
        <h3>What the guarantee covers</h3>
        <?php echo soGuaranteeList(); ?>
        <p class="so-bbb-guar__limits"><?php echo htmlspecialchars(soGuaranteeLimits(), ENT_QUOTES, 'UTF-8'); ?> <?php echo soGuaranteeLinks(); ?></p>
      </div>
    </div>
  </div>
</section>

<!-- CHECKLIST -->
<section id="policies" class="so-bbb-sec so-bbb-sec--tint">
  <div class="container">
    <span class="so-bbb-label">Before you pay anyone</span>
    <h2 class="so-bbb-h2">How to check any ticket seller</h2>
    <ol class="so-bbb-steps">
      <li>Read the refund, delivery and cancellation policies before you enter payment details</li>
      <li>Confirm the full cost, including fees, is shown before you pay</li>
      <li>Look for a real way to contact the seller, and for independent feedback, including complaints</li>
      <li>Keep your confirmation email and order ID</li>
    </ol>
    <p class="so-bbb-after">What we can and cannot show about feedback on our own site is explained on the <a href="/seat-outlet-reviews">reviews page</a> and the <a href="/customer-testimonials">testimonials page</a>. Our guide to <a href="/blog/how-to-avoid-ticket-scams">avoiding ticket scams</a> goes into more detail.</p>
  </div>
</section>

<!-- FAQ -->
<section class="so-bbb-sec so-bbb-faq">
  <div class="container">
    <h2 class="so-bbb-h2">Frequently Asked Questions</h2>
    <div class="so-bbb-faq__card">
                            <div class="accordion so-qa mb-0" id="purchaseFaq">
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
</section>

<!-- CTA -->
<section class="so-bbb-cta">
  <div class="container">
    <h2>Ready to look at tickets?</h2>
    <p>Compare seats and prices, and review the full cost at checkout before you pay.</p>
    <a href="/buy-tickets-online" class="btn btn-primary">Browse events</a>
  </div>
</section>
</div>

<?php soSeoCopy('seat-outlet-bbb'); ?>
<?php include 'footer.php'; ?>
