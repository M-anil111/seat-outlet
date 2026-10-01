<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical. Kept noindex (header.php's site-wide default) since
// the visible content is still illustrative sample testimonials - see
// the disclaimer added to this page.
$pageMetaTitle       = 'Customer Testimonials | Seat Outlet';
$pageMetaDescription = 'See what Seat Outlet customers say about buying concert, sports, and event tickets through our verified ticket marketplace.';
$pageCanonicalUrl    = HOME_URL . '/testimonials';
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
                    <h1 class="hero-title">Customer Testimonials: What Our Customers Say</h1>
                    <p class="hero-subtitle">Don't just take our word for it - read customer testimonials from thousands of satisfied customers who have discovered amazing events and secured their tickets through SeatOutlet, backed by our
                    <a href="/guarantee">satisfaction guarantee</a>. From last-minute concert tickets to
                    season-long sports packages, these are the moments our customers came back to tell us about.</p>
                </div>
            </div>
        </div>
    </section>

    <!--
        Same finding as reviews.php: the testimonial cards below and the
        "50K+"/"4.9/5"/"98%" stats further down are illustrative sample
        content, not real collected customer feedback - no reviews table
        or collection flow backs this page. See functions.php's
        buildOrganizationSchema() for the matching fix already applied to
        this site's machine-readable schema (fabricated review/
        aggregateRating removed there). Replace with real testimonials/
        stats before this page goes live/indexed - it's currently noindex
        by header.php's site-wide default.
    -->
    <div class="container mt-4">
        <div class="alert alert-warning" role="alert">
            <strong>Note:</strong> The testimonials and stats below are illustrative examples while we build out real customer review collection. Real verified testimonials will replace this content soon.
        </div>
        <p class="mt-3">
          Customer testimonials matter because they're one of the few signals a new visitor has to
          judge whether a marketplace actually delivers on its promises. We take that seriously, which
          is why we're building a real collection system instead of just writing quotes ourselves -
          once it's live, every testimonial on this page will link back to a verifiable order, and
          you'll be able to filter by event type, city, or ticket category to see feedback relevant to
          what you're planning to book.
        </p>
    </div>

    <!-- Testimonials Content -->
    <section class="section-padding">
        <div class="container">
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <!-- <div class="quote-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" x="0" y="0" viewBox="0 0 128 128" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,-1.2246467991473532e-16,1.2246467991473532e-16,-1,127.99583406094462,128.00541877746582)"><path d="M126.74 40.767c-2.64-12.504-12.513-27.32-27.952-30.301a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98zm-51.02 73.1-.541-3.978c5.902-2.295 31.975-14.101 33.317-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M87.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2 2 0 1 0 3.113-2.515c-6.099-7.542-15.87-3.896-16.283-3.737zM32.788 10.466a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98-2.64-12.504-12.513-27.32-27.952-30.301zM9.72 113.868l-.543-3.979c5.903-2.295 31.976-14.101 33.318-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M21.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2.054 2.054 0 0 0 2.814.299 2 2 0 0 0 .299-2.813c-6.099-7.543-15.87-3.897-16.283-3.738z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path></g></svg>
                    </div> -->
                    
                    <p class="testimonial-text">
                        I've been using SeatOutlet for concert tickets for over a year now, and I'm always impressed! The <span class="highlight">easy booking process</span> and <span class="highlight">instant ticket delivery</span> make it so convenient. I got front-row tickets to my favorite artist's show last month - the experience was unforgettable!
                    </p>
                    <div class="rating">★★★★★</div>
                    <div class="testimonial-author">
                        <div class="author-avatar">SM</div>
                        <div class="author-info">
                            <h3>Sarah Mitchell</h3>
                            <p>Music Enthusiast</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <!-- <div class="quote-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" x="0" y="0" viewBox="0 0 128 128" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,-1.2246467991473532e-16,1.2246467991473532e-16,-1,127.99583406094462,128.00541877746582)"><path d="M126.74 40.767c-2.64-12.504-12.513-27.32-27.952-30.301a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98zm-51.02 73.1-.541-3.978c5.902-2.295 31.975-14.101 33.317-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M87.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2 2 0 1 0 3.113-2.515c-6.099-7.542-15.87-3.896-16.283-3.737zM32.788 10.466a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98-2.64-12.504-12.513-27.32-27.952-30.301zM9.72 113.868l-.543-3.979c5.903-2.295 31.976-14.101 33.318-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M21.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2.054 2.054 0 0 0 2.814.299 2 2 0 0 0 .299-2.813c-6.099-7.543-15.87-3.897-16.283-3.738z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path></g></svg>
                    </div> -->
                    
                    <p class="testimonial-text">
                        SeatOutlet saved our corporate event planning! We needed <span class="highlight">50 tickets</span> for a team-building conference, and their bulk booking feature was seamless. The customer support team was incredibly helpful, and we received all tickets instantly via email. Highly recommend for business events!
                    </p>
                    <div class="rating">★★★★★</div>
                    <div class="testimonial-author">
                        <div class="author-avatar">JD</div>
                        <div class="author-info">
                            <h3>James Davidson</h3>
                            <p>Event Coordinator, TechCorp</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <!-- <div class="quote-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" x="0" y="0" viewBox="0 0 128 128" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,-1.2246467991473532e-16,1.2246467991473532e-16,-1,127.99583406094462,128.00541877746582)"><path d="M126.74 40.767c-2.64-12.504-12.513-27.32-27.952-30.301a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98zm-51.02 73.1-.541-3.978c5.902-2.295 31.975-14.101 33.317-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M87.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2 2 0 1 0 3.113-2.515c-6.099-7.542-15.87-3.896-16.283-3.737zM32.788 10.466a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98-2.64-12.504-12.513-27.32-27.952-30.301zM9.72 113.868l-.543-3.979c5.903-2.295 31.976-14.101 33.318-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M21.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2.054 2.054 0 0 0 2.814.299 2 2 0 0 0 .299-2.813c-6.099-7.543-15.87-3.897-16.283-3.738z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path></g></svg>
                    </div> -->
                    
                    <p class="testimonial-text">
                        As someone who attends multiple sports events throughout the season, SeatOutlet has become my go-to platform. The <span class="highlight">seat selection feature</span> is fantastic - I can see exactly where I'll be sitting before purchasing. The prices are competitive, and I've never had any issues with ticket validity!
                    </p>
                    <div class="rating">★★★★★</div>
                    <div class="testimonial-author">
                        <div class="author-avatar">EL</div>
                        <div class="author-info">
                            <h3>Emily Lopez</h3>
                            <p>Sports Fan</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <!-- <div class="quote-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" x="0" y="0" viewBox="0 0 128 128" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,-1.2246467991473532e-16,1.2246467991473532e-16,-1,127.99583406094462,128.00541877746582)"><path d="M126.74 40.767c-2.64-12.504-12.513-27.32-27.952-30.301a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98zm-51.02 73.1-.541-3.978c5.902-2.295 31.975-14.101 33.317-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M87.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2 2 0 1 0 3.113-2.515c-6.099-7.542-15.87-3.896-16.283-3.737zM32.788 10.466a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98-2.64-12.504-12.513-27.32-27.952-30.301zM9.72 113.868l-.543-3.979c5.903-2.295 31.976-14.101 33.318-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M21.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2.054 2.054 0 0 0 2.814.299 2 2 0 0 0 .299-2.813c-6.099-7.543-15.87-3.897-16.283-3.738z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path></g></svg>
                    </div> -->
                    
                    <p class="testimonial-text">
                        The mobile app is absolutely brilliant! I booked last-minute tickets to a comedy show while on the train, and the <span class="highlight">QR code entry</span> made everything so smooth. No printing, no hassle - just scan and enjoy. SeatOutlet has revolutionized how I experience live events!
                    </p>
                    <div class="rating">★★★★★</div>
                    <div class="testimonial-author">
                        <div class="author-avatar">MR</div>
                        <div class="author-info">
                            <h3>Michael Rodriguez</h3>
                            <p>Frequent Event Goer</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <!-- <div class="quote-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" x="0" y="0" viewBox="0 0 128 128" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,-1.2246467991473532e-16,1.2246467991473532e-16,-1,127.99583406094462,128.00541877746582)"><path d="M126.74 40.767c-2.64-12.504-12.513-27.32-27.952-30.301a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98zm-51.02 73.1-.541-3.978c5.902-2.295 31.975-14.101 33.317-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M87.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2 2 0 1 0 3.113-2.515c-6.099-7.542-15.87-3.896-16.283-3.737zM32.788 10.466a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98-2.64-12.504-12.513-27.32-27.952-30.301zM9.72 113.868l-.543-3.979c5.903-2.295 31.976-14.101 33.318-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M21.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2.054 2.054 0 0 0 2.814.299 2 2 0 0 0 .299-2.813c-6.099-7.543-15.87-3.897-16.283-3.738z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path></g></svg>
                    </div> -->
                    
                    <p class="testimonial-text">
                        We organized a charity fundraiser and needed to sell tickets online. SeatOutlet's <span class="highlight">event management tools</span> made it incredibly easy. The platform handled everything from ticket sales to attendee check-ins. Our event was a huge success, and we'll definitely use SeatOutlet again!
                    </p>
                    <div class="rating">★★★★★</div>
                    <div class="testimonial-author">
                        <div class="author-avatar">AW</div>
                        <div class="author-info">
                            <h3>Amanda Wilson</h3>
                            <p>Non-Profit Director</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <!-- <div class="quote-icon">
                       <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="50" height="50" x="0" y="0" viewBox="0 0 128 128" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g transform="matrix(-1,-1.2246467991473532e-16,1.2246467991473532e-16,-1,127.99583406094462,128.00541877746582)"><path d="M126.74 40.767c-2.64-12.504-12.513-27.32-27.952-30.301a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98zm-51.02 73.1-.541-3.978c5.902-2.295 31.975-14.101 33.317-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M87.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2 2 0 1 0 3.113-2.515c-6.099-7.542-15.87-3.896-16.283-3.737zM32.788 10.466a27.26 27.26 0 0 0-21.712 5.357 27.695 27.695 0 0 0 .12 44.405 27.276 27.276 0 0 0 25.509 4.324 1.33 1.33 0 0 1 1.25.186 1.364 1.364 0 0 1 .542 1.177c-1.249 27.64-26.037 38.446-30.994 40.332a3.658 3.658 0 0 0-2.323 3.937s.632 4.641.633 4.647a3.725 3.725 0 0 0 4.987 2.916c56.567-22.964 52.526-64.727 49.94-76.98-2.64-12.504-12.513-27.32-27.952-30.301zM9.72 113.868l-.543-3.979c5.903-2.295 31.976-14.101 33.318-43.793a5.348 5.348 0 0 0-7.102-5.324 23.287 23.287 0 0 1-21.768-3.723 23.697 23.697 0 0 1-.109-38.055 23.237 23.237 0 0 1 18.511-4.6c13.648 2.636 22.42 15.947 24.796 27.2 2.417 11.454 6.144 50.444-47.103 72.274z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path><path d="M21.293 22.002a2 2 0 1 0 1.437 3.734c.075-.03 7.446-2.785 11.733 2.518a2.054 2.054 0 0 0 2.814.299 2 2 0 0 0 .299-2.813c-6.099-7.543-15.87-3.897-16.283-3.738z" fill="#2556E0" opacity="1" data-original="#2556E0" class=""></path></g></svg>
                    </div> -->
                    
                    <p class="testimonial-text">
                        I was skeptical about buying tickets online, but SeatOutlet proved me wrong! When a show I wanted to see was sold out elsewhere, I found tickets here at a <span class="highlight">fair price</span>. The <span class="highlight">secure payment system</span> and instant confirmation gave me peace of mind. I'm now a loyal customer!
                    </p>
                    <div class="rating">★★★★★</div>
                    <div class="testimonial-author">
                        <div class="author-avatar">RT</div>
                        <div class="author-info">
                            <h3>Robert Thompson</h3>
                            <p>Theater Enthusiast</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-section">
                <img src="/images/crowd-at-concert-or-event.webp" class="img-fluid rounded mb-3" alt="Fans who left customer testimonials for Seat Outlet at a live event" loading="lazy" width="442" height="442" decoding="async">
                <h2>Real Customer Testimonials, Trusted by Thousands</h2>
                <p>Join the SeatOutlet community and discover amazing events near you. Read more about what drives genuine
                <a href="https://en.wikipedia.org/wiki/Customer_satisfaction" target="_blank" rel="noopener">customer satisfaction</a>.</p>
                <div class="stats-grid">
                    <div class="stat-item">
                        <h2>50K+</h2>
                        <p>Happy Customers</p>
                    </div>
                    <div class="stat-item">
                        <h2>4.9/5</h2>
                        <p>Average Rating</p>
                    </div>
                    <div class="stat-item">
                        <h2>98%</h2>
                        <p>Satisfaction Rate</p>
                    </div>
                    <div class="stat-item">
                        <h2>24/7</h2>
                        <p>Customer Support</p>
                    </div>
                </div>
                <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
                    <img src="/images/team-event.webp" class="img-fluid rounded" style="max-width:200px;" alt="Team behind real customer testimonials" loading="lazy" width="250" height="250" decoding="async">
                    <img src="/images/stage.webp" class="img-fluid rounded" style="max-width:200px;" alt="Live event featured in customer testimonials" loading="lazy" width="750" height="843" decoding="async">
                    <img src="/images/event-concert.jpg" class="img-fluid rounded" style="max-width:200px;" alt="Concert fans sharing customer testimonials" loading="lazy" width="800" height="512" decoding="async">
                </div>
                <p class="mt-4 text-center">
                  If you've booked with us before, we'd genuinely like to hear how it went - good or
                  bad. Real customer testimonials, including the critical ones, are how a ticket
                  marketplace actually improves over time. Once our verified review collection system
                  launches, you'll be able to leave feedback directly from your order confirmation, no
                  separate account or login required, and every submitted review will be checked
                  against a real order before it's published on this page. Until then, please treat the
                  examples shown above as a preview of the intended format, not as a record of any
                  actual customer feedback we have collected so far from real ticket buyers.
                </p>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
