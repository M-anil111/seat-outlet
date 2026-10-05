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

    .testimonial-page .stats-section {
        background: white;
        border-radius: 20px;
        padding: 50px 40px;
        margin-top: 50px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        text-align: center;
        border: 1px solid #e5e7eb;
    }

    .testimonial-page .stats-section h2 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .testimonial-page .stats-section > p {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 30px;
    }

    .testimonial-page .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }

    .testimonial-page .stat-item h2 {
        font-size: 3rem;
        color: #2556E0;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .testimonial-page .stat-item p {
        font-size: 1.1rem;
        color: #666;
        margin: 0;
    }

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
        .testimonial-page .stats-section h2{
            font-size: 32px;
        }
        .testimonial-page .stats-section > p{
            font-size: 16px;
        }
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

        .testimonial-page .stats-section {
            padding: 40px 25px;
        }
        .testimonial-page .stats-section h2{
            font-size: 25px;
        }
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
            <div class="stats-section">
                <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded mb-3" alt="Fans cheering at a live event" loading="lazy" width="442" height="442" decoding="async">
                <h2>Share your customer testimonial</h2>
                <p>If you have booked with us, we would like to hear how it went, good or bad. Write to
                <a href="/ticket-customer-service">our customer service team</a> and tell us about your order. Read more about what drives
                <a href="https://en.wikipedia.org/wiki/Customer_satisfaction" target="_blank" rel="noopener">customer satisfaction</a>.</p>
                <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
                    <a class="btn btn-primary" href="/ticket-customer-service">Send feedback</a>
                    <a class="btn btn-outline-primary" href="/seat-outlet-reviews">Seat Outlet reviews</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php soSeoCopy('customer-testimonials'); ?>
<?php include 'footer.php'; ?>
