<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical. Kept noindex (header.php's site-wide default) since
// the visible content is still illustrative sample testimonials - see
// the disclaimer added to this page.
$pageMetaTitle       = 'Customer Testimonials | Seat Outlet';
$pageMetaDescription = 'See what Seat Outlet customers say about buying concert, sports, and event tickets through our resale ticket marketplace.';
$pageCanonicalUrl    = HOME_URL . '/customer-testimonials';
include 'header.php';
?>

<style>
    .testimonial-page {
        background-color: #ffffff;
    }

    .testimonial-page .section-padding {
        padding: 50px 0;
    }

    /* Hero / Inner Banner */
    .testimonial-page .hero-section {
        position: relative;
        background-color: #05070b;
        color: #ffffff;
        overflow: hidden;
        padding: 80px 0 90px;
    }

    .testimonial-page .hero-section::before {
        content: "";
        position: absolute;
        right: -20%;
        top: -30%;
        width: 55%;
        height: 170%;
        background: linear-gradient(135deg, #0b1120 0%, #2556E0 55%, #0b1120 100%);
        transform: skewX(-18deg);
        opacity: 0.9;
        z-index: 0;
    }

    .testimonial-page .hero-inner {
        position: relative;
        z-index: 1;
    }

    .testimonial-page .hero-title {
        font-weight: 800;
        line-height: 1.1;
        font-size: 52px;
        margin-bottom: 8px;
    }

    .testimonial-page .hero-subtitle {
        color: #d1d5db;
        font-size: 16px;
        max-width: 740px;
        margin: 0 auto;
    }

    /* Testimonials Section */
    .testimonial-page .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }

    .testimonial-page .testimonial-card {
        background: white;
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    .testimonial-page .testimonial-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #2556E0, #1a42b3);
    }

    .testimonial-page .testimonial-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
    }

    .testimonial-page .quote-icon {
        font-size: 3rem;
        color: #2556E0;
        opacity: 0.6;
        margin-bottom: 15px;
        line-height: 1;
    }

    .testimonial-page .testimonial-text {
        font-size: 16px;
        line-height: 1.8;
        color: #333;
        margin-bottom: 25px;
        font-style: italic;
    }

    .testimonial-page .testimonial-author {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .testimonial-page .author-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2556E0, #1a42b3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
        flex-shrink: 0;
    }

    .testimonial-page .author-info h3 {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .testimonial-page .author-info p {
        font-size: 0.95rem;
        color: #666;
        margin: 0;
    }

    .testimonial-page .rating {
        color: #ffc107;
        font-size: 1.2rem;
        margin-bottom: 15px;
        border: none;
        padding: 0;
        border-radius: 0;
    }

    /* Share card: photo left, invitation right */
    .testimonial-page .so-share {
        display: grid;
        grid-template-columns: minmax(0, 1.12fr) minmax(0, 1fr);
        gap: 28px;
        align-items: stretch;
        max-width: 960px;
        margin: 0 auto;
        padding: 16px;
        background: #fff;
        border: 1px solid #e8ecf3;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 27, 61, .06);
    }
    .testimonial-page .so-share__media { margin: 0; min-height: 100%; }
    .testimonial-page .so-share__media img {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 300px;
        object-fit: cover;
        border-radius: 12px;
    }
    .testimonial-page .so-share__body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 12px 12px 12px 0;
    }
    .testimonial-page .so-share__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin-bottom: 16px;
        border-radius: 50%;
        background: #eaf0ff;
        color: #2556e0;
    }
    .testimonial-page .so-share h2 {
        margin: 0 0 10px;
        font-size: 24px;
        font-weight: 800;
        line-height: 1.25;
        letter-spacing: -.01em;
        color: #0f1b3d;
    }
    .testimonial-page .so-share__text {
        margin: 0 0 20px;
        font-size: 15px;
        line-height: 1.6;
        color: #4b5565;
    }
    .testimonial-page .so-share__text a { color: #2556e0; }
    .testimonial-page .so-share__actions { display: grid; gap: 12px; }
    .testimonial-page .so-share__actions .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 700;
    }
    .testimonial-page .so-share__note {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-top: 22px;
        padding-top: 20px;
        border-top: 1px solid #e8ecf3;
    }
    .testimonial-page .so-share__note-icon {
        flex: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eaf0ff;
        color: #2556e0;
    }
    .testimonial-page .so-share__note strong { display: block; margin-bottom: 2px; font-size: 15px; color: #0f1b3d; }
    .testimonial-page .so-share__note p { margin: 0; font-size: 14px; line-height: 1.5; color: #4b5565; }

    .testimonial-page .highlight {
        background: linear-gradient(120deg, #5d7ff0 0%, #2556E0 100%);
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
        color:#fff;
    }

    /* Responsive */
    @media (max-width: 991px) {
        .testimonial-page .hero-title {
            font-size: 40px;
        }
        .testimonial-page .so-share { gap: 22px; }
    }

    @media (max-width: 767.98px) {
        .testimonial-page .section-padding {
            padding: 40px 0;
        }

        .testimonial-page .hero-title {
            font-size: 30px;
        }

        .testimonial-page .testimonials-grid {
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .testimonial-page .testimonial-card {
            padding: 25px;
        }

        .testimonial-page .so-share { grid-template-columns: 1fr; gap: 18px; padding: 12px; }
        .testimonial-page .so-share__media img { min-height: 0; height: auto; aspect-ratio: 4 / 3; }
        .testimonial-page .so-share__body { padding: 4px 8px 12px; }
        .testimonial-page .so-share h2 { font-size: 22px; }
    }
</style>

<main class="testimonial-page">
    <!-- Hero / Inner Banner -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-9 hero-inner">
                    <h1 class="hero-title">Customer Testimonials</h1>
                    <p class="hero-subtitle">There are no customer testimonials here yet, and we will not write any ourselves. Meanwhile, read how our <a href="/worry-free-guarantee">worry-free guarantee</a> and <a href="/ticket-buyer-protection">buyer protection</a> work, and tell us about your own order.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="container mt-4">
        <p class="mt-3">
          We would rather show nothing than show quotes we wrote ourselves, so this page stays free of sample reviews.
          For how feedback is handled and how to judge any ticket seller, see our <a href="/seat-outlet-reviews">reviews page</a>.
        </p>
    </div>

    <!-- Testimonials Content -->
    <section class="section-padding">
        <div class="container">
            <div class="so-share">
                <figure class="so-share__media">
                    <img src="/images/crowd-at-concert-or-event.webp" alt="Fans cheering at a live event" loading="lazy" width="442" height="442" decoding="async">
                </figure>
                <div class="so-share__body">
                    <span class="so-share__icon" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5c-4.97 0-9 3.36-9 7.5 0 2.1 1.04 4 2.72 5.36L5 20.5l4.3-2.05c.85.2 1.75.3 2.7.3 4.97 0 9-3.36 9-7.5s-4.03-7.25-9-7.25z"/></svg></span>
                    <h2>Share your customer testimonial</h2>
                    <p class="so-share__text">If you have booked with us, we would like to hear how it went, good or bad. Write to
                    <a href="/ticket-customer-service">our customer service team</a> and tell us about your order. Read more about what drives
                    <a href="https://en.wikipedia.org/wiki/Customer_satisfaction" target="_blank" rel="noopener">customer satisfaction</a>.</p>
                    <div class="so-share__actions">
                        <a class="btn btn-primary" href="/ticket-customer-service">Send feedback</a>
                        <a class="btn btn-outline-primary" href="/seat-outlet-reviews">Seat Outlet reviews</a>
                    </div>
                    <div class="so-share__note">
                        <span class="so-share__note-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg></span>
                        <div>
                            <strong>No testimonials published yet.</strong>
                            <p>Be the first to share your experience and help other fans learn about Seat Outlet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php soSeoCopy('customer-testimonials'); ?>
<?php include 'footer.php'; ?>
