<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical.
$pageMetaTitle       = 'Our Guarantee | Seat Outlet';
$pageMetaDescription = 'Seat Outlet orders are covered by the TicketNetwork 100% guarantee: authentic and valid tickets, delivery before the event, the tickets you ordered or better, and a refund if the event is canceled.';
$pageCanonicalUrl    = HOME_URL . '/worry-free-guarantee';
require_once __DIR__ . '/inc/guarantee.php';
include 'header.php';
?>

<style>

.ticketingtruths-page .section-padding {
    padding: 60px 0;
}

.trusted-question + .trusted-question{
    margin-top:30px;
}

/* Hero Section */
.ticketingtruths-page .hero-section {
    position: relative;
    background-color: #05070b;
    color: #ffffff;
    overflow: hidden;
    padding-bottom:120px;
}

.ticketingtruths-page .hero-section::before {
    content: "";
    position: absolute;
    right: -18%;
    top: -25%;
    width: 55%;
    height: 170%;
    background: linear-gradient(135deg, #0b1120 0%, #0056d6 55%, #0b1120 100%);
    transform: skewX(-18deg);
    opacity: 0.9;
    z-index: 0;
}

.ticketingtruths-page .hero-inner {
    position: relative;
    z-index: 1;
}

.ticketingtruths-page .hero-eyebrow {
    font-size: 12.8px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #9ca3af;
    margin-bottom: 9.6px;
}

.hero-title {
    font-weight: 800;
    line-height: 1.1;
    font-size: 70px;
    position: relative;
    margin-bottom: 20px;
}

.ticketingtruths-page .hero-title .hero-title-blue {
    color: #2556E0;
    letter-spacing: 0.06em;
    display: block;
}

.ticketingtruths-page .hero-title .hero-title-white {
    color: #ffffff;
    letter-spacing: 0.06em;
}

.ticketingtruths-page .hero-subtitle {
    color: #d1d5db;
    font-size: 16px;
    text-align: center;
    z-index: 2;
    position: relative;
}

.ticketingtruths-page .section-light {
    background-color: #f5f5f7;
}

.ticketingtruths-page .section-blue {
    background-color: #0056d6;
    color: #ffffff;
}

.ticketingtruths-page .section-blue p {
    color: #e5e7eb;
}

.ticketingtruths-page .section-heading {
    font-size:38px;
    font-weight: 700;
    margin-bottom: 12.8px;
}

.ticketingtruths-page .section-body {
    font-size: 16px;
    max-width: 520px;
}

.ticketingtruths-page .section-body + .section-body{
    margin-top:20px;
}

.ticketingtruths-page .section-image-wrapper {
    text-align: center;
    position: relative;
}

/* Modern guarantee section */
.guarantee-modern {
    padding: 80px 0;
    background: radial-gradient(circle at top left, #eef2ff 0, #f9fafb 40%, #ffffff 100%);
}

.guarantee-modern-inner {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    align-items: center;
    gap: 60px;
}

.guarantee-modern-text h2 {
    font-size: 34px;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin-bottom: 14px;
    color: #111827;
}

.guarantee-lead {
    font-size: 16px;
    line-height: 1.7;
    color: #4b5563;
    max-width: 520px;
    margin-bottom: 24px;
}

.guarantee-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.07);
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 12px;
}

.guarantee-badge::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.25);
}

.guarantee-bullets {
    list-style: none;
    padding: 0;
    margin: 0 0 26px;
    display: grid;
    gap: 10px;
}

.guarantee-bullets li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
    color: #374151;
}

.guarantee-bullets .icon {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    background: #e0f2fe;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    position: relative;
}

.guarantee-bullets .icon::before {
    content: "✓";
    font-size: 12px;
    color: #0284c7;
}

.guarantee-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    padding-top: 8px;
}

.guarantee-meta div strong {
    display: block;
    font-size: 20px;
    color: #111827;
}

.guarantee-meta div span {
    display: block;
    font-size: 12px;
    color: #6b7280;
}

.guarantee-modern-visual {
    display: flex;
    justify-content: center;
}

.guarantee-image-frame {
    position: relative;
    border-radius: 24px;
    overflow: hidden;
    box-shadow:
        0 18px 45px rgba(15, 23, 42, 0.15),
        0 0 0 1px rgba(148, 163, 184, 0.25);
    background: linear-gradient(135deg, #0f172a, #1f2937);
}

.guarantee-image-frame img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: cover;
}

.guarantee-sticker {
    position: absolute;
    bottom: 18px;
    left: 18px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.92);
    color: #f9fafb;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.6);
}

.guarantee-sticker span {
    display: block;
    font-size: 11px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.guarantee-sticker small {
    display: block;
    font-size: 13px;
    font-weight: 600;
}

/* CTA section */
.ticketingtruths-page .cta-section-last {
    background-image:url(../images/cta-banner.webp) ;
    color: #ffffff;
    padding: 100px 0;
    text-align: center;
    position: relative;
}
.ticketingtruths-page .cta-section-last::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    left: 0;
    top: 0;
    background-color: #00000087;
    z-index: 0;
}
.ticketingtruths-page .cta-title {
    font-size: 38px;
    font-weight: 700;
    margin-bottom: 18px;
}

.ticketingtruths-page .cta-text {
    max-width: 620px;
    margin: 0 auto 24px;
    font-size: 16px;
    color: #e5e7eb;
}

.ticketingtruths-page .btn-cta {
    border-radius: 999px;
    padding: 8.8px 30.4px;
    font-weight: 600;
    font-size: 14.4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    background-color: #ffffff;
    color: #0056d6;
    border: none;
}

.ticketingtruths-page .btn-cta:hover {
    background-color: #e5e7eb;
    color: #0041a3;
}
.cta-content{
    position: relative;
}


/* ================= MEDIA QUERIES ================= */

@media (min-width: 768px) {
    .ticketingtruths-page .section-image-wrapper {
        text-align: right;
    }

    .ticketingtruths-page .section-image-wrapper.img-left {
        text-align: left;
    }
}

@media (max-width: 1365px){
    .hero-title{
        font-size: 60px;
    }
    
}
@media (max-width: 1199px){
    .ticketingtruths-page .cta-section-last{
        padding: 80px 0;
    }
    .ticketingtruths-page .section-heading{
        font-size: 24px;
    }
}
@media (max-width: 991px){
    .hero-title {
        font-size: 50px;
    }
    .ticketingtruths-page .cta-title{
        font-size: 32px;
    }
}
@media (max-width: 767.98px) {
    .ticketingtruths-page .cta-title{
        font-size: 28px;
    }
    .ticketingtruths-page .section-padding {
        padding: 50px 0;
    }

    .ticketingtruths-page .hero-section::before {
        right: -40%;
        width: 80%;
        height: 150%;
    }

    .ticketingtruths-page .hero-subtitle {
        font-size: 14.4px;
    }

    .ticketingtruths-page .hero-image-wrapper {
        position: static;
        width: 70%;
        max-width: 320px;
        margin: 30px auto 0;
    }
    .ticketingtruths-page .section-image-wrapper{
        margin-top: 30px;
    }
    .hero-title {
        font-size: 32px;
    }
    .ticketingtruths-page .cta-section-last {
        padding: 50px 0;
    }
    .guarantee-modern-inner {
        grid-template-columns: minmax(0, 1fr);
        gap: 40px;
    }

    .guarantee-modern-text {
        text-align: left;
    }

    .guarantee-modern-visual {
        margin-bottom: 10px;
    }
}
    </style>

    <!-- Main Content -->
    <main class="ticketingtruths-page">
<!-- Hero Section -->
      <section class="hero-section section-padding">
        <div class="container">
            
            <!-- Hero Content -->
            <div class="row justify-content-center align-items-center">
                <div class="col-lg-7 col-md-8">
                    <h1 class="hero-title text-center">
                        <span class="hero-title-white">Our</span> 
                        <span class="hero-title-blue">Worry Free Guarantee</span>
                    </h1>
                    <p class="hero-subtitle">
                    <?php echo getContentBlock('/guarantee', 'hero-subtitle', 'Every Seat Outlet order is backed by a 100% guarantee: valid tickets, delivery before the event, the tickets you ordered or better, and a refund if the event is canceled.'); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>
        <!-- Section 1: 100% Guarantee (same terms as our ticket network partner, TicketNetwork) -->
        <section class="section-padding guarantee-modern">
            <div class="container guarantee-modern-inner">
                <div class="guarantee-modern-text">
                    <span class="guarantee-badge">100% Guarantee</span>
                    <h2 class="section-heading">Every Order Is Backed by a 100% Guarantee</h2>
                    <p class="guarantee-lead">
                        <?php echo htmlspecialchars(soGuaranteeLead(), ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <ul class="guarantee-bullets">
                        <?php foreach (soGuaranteePoints() as $point) { ?>
                        <li>
                            <span class="icon"></span>
                            <?php echo htmlspecialchars($point, ENT_QUOTES, 'UTF-8'); ?>
                        </li>
                        <?php } ?>
                    </ul>
                    <p class="small text-muted mt-3 mb-0">
                        <?php echo htmlspecialchars(soGuaranteeLimits(), ENT_QUOTES, 'UTF-8'); ?>
                        <?php echo soGuaranteeLinks(); ?>
                    </p>
                </div>

                <div class="guarantee-modern-visual">
                    <div class="guarantee-image-frame">
                        <img
                            src="/images/ticket-trusted.webp"
                            alt="Singer performing outdoors in front of a crowd" width="750" height="875" decoding="async"/>
                        <div class="guarantee-sticker">
                            <span>Backed by</span>
                            <small>100% Guarantee</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Blue - Image Left / Text Right -->
        <section class="section-blue section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5 flex-md-row flex-column-reverse">
                    <!-- Image -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <div class="section-image-wrapper img-left">
                            <img
                                src="/images/stage.webp"
                                alt="Stage performance" width="750" height="843" decoding="async"/>
                        </div>
                    </div>

                    <!-- Text -->
                    <div class="col-md-6">
                        <div class="trusted-question">
                            <h2 class="section-heading">How we help if something goes wrong</h2>
                            <p class="section-body">
                                If tickets do not arrive, do not match your order, or an event is canceled, contact our
                                support team. We review every case against the event details and our policies, and work
                                with the seller to make it right.
                            </p>
                            <p class="section-body">
                                Read the full <a href="/ticket-buyer-protection">ticket buyer protection</a> terms and our
                                <a href="/ticket-faq">ticket FAQ</a> so you know what is covered before you order.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Light - Text Left / Image Right -->
        <section class="section-light section-padding">
            <div class="container">
                <div class="row align-items-center gx-lg-5">
                    <!-- Text -->
                    <div class="col-md-6 mb-4 mb-lg-0">
                        <div class="trusted-question">
                            <h2 class="section-heading">Who sets the ticket prices?</h2>
                            <p class="section-body">
                            Ticket pricing is determined by event organizers, performers, or promoters. Prices may vary based on demand, seat location, event popularity, and availability.
                            </p>
                            <p class="section-body">
                            Event schedules and ticket release timelines are decided by event organizers, performers, and promoters. They determine when tickets become available and how they are distributed to the public.
                            Our <a href="/ticket-buyer-protection">buyer protection</a> policy covers every order regardless of price, and follows the same consumer-safety principles outlined by the
                            <a href="https://consumer.ftc.gov/" target="_blank" rel="noopener">FTC's consumer advice</a> on online purchases.
                            </p>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6">
                        <div class="section-image-wrapper">
                            <img
                                src="/images/ticket-trusted.webp"
                                alt="Singer performing outdoors in front of a crowd" width="750" height="875" decoding="async"/>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="cta-section-last">
            <div class="container">
               <div class="cta-content">
               <img src="/images/moneyback-p3.png" class="img-fluid mb-3" alt="Satisfaction guarantee money-back badge" loading="lazy" style="max-width:120px;" width="67" height="70" decoding="async">
               <h2 class="cta-title">Experience Our Satisfaction Guarantee Today</h2>
                <p class="cta-text">
                Shop with confidence knowing that your purchase is protected. If you
        have any questions about our satisfaction guarantee, our support team is here to help.
                </p>
                <a class="btn btn-cta" href="/ticket-customer-service">Contact Support</a>
               </div>
            </div>
        </section>
    </main>



<?php soSeoCopy('worry-free-guarantee'); ?>
<?php include 'footer.php'; ?>