<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical. Kept noindex (header.php's site-wide default) since
// the visible content is still illustrative sample reviews - see the
// disclaimer added to this page.
$pageMetaTitle       = 'Customer Reviews | Seat Outlet';
$pageMetaDescription = 'Read customer reviews of Seat Outlet, a ticket marketplace for buying concert, sports, and event tickets online.';
$pageCanonicalUrl    = HOME_URL . '/reviews';
include 'header.php';
?>
<style>
    /* ========== Page Header ========== */

    .reviews-section {
    padding: 100px 0;
}

/* ========== Page Header ========== */
.reviews-title {
    font-size: 32px; /* 2rem */
    font-weight: 700;
    color: #2556e0;
}

.reviews-subtitle {
    font-size: 26px;
    font-weight: 600;
    color: var(--text-dark);
}

/* ========== Review Summary Section ========== */
.review-summary {
    background-color: #ffffff;
    border: 1px solid var(--border-light);
    border-radius: 6px; /* 0.375rem */
    padding: 24px; /* 1.5rem */
}

/* Green rating box with 4.4 score */
.rating-box {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    background-color: #2556e0;
    color: #ffffff;
    border-radius: 6px; /* 0.375rem */
    padding: 0;
}

.rating-value {
    font-size: 45px;
    font-weight: 700;
    line-height: 1.2;
    display: block;
    padding: 30px 0;
}

.rating-label {
    font-size: 17px;
    font-weight: bold;
    width: 100%;
    text-align: center;
    padding: 5px;
    background-color: #6c757d;
}

/* Star breakdown progress bars */
.star-breakdown {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 8px 0; /* 0.5rem */
}

.star-row {
    display: flex;
    align-items: center;
    gap: 8px; /* 0.5rem */
    margin-bottom: 8px; /* 0.5rem */
}

.star-row:last-child {
    margin-bottom: 0;
}

.star-label {
    font-size: 14px; /* 0.875rem */
}

.star-count {
    font-size: 14px; /* 0.875rem */
    min-width: 40px; /* 2.5rem */
    text-align: right;
}

.star-breakdown .progress {
    height: 8px; /* 0.5rem */
    background-color: #e9ecef;
}

.star-breakdown .progress-bar {
    background-color: #6c757d !important;
}

/* Overall rating & percentage section */
.overall-rating {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 8px 0; /* 0.5rem */
}

.percentage-value {
    font-size: 24px; /* 1.5rem */
    font-weight: 700;
    color: var(--text-dark);
}

.percentage-desc {
    color: var(--text-dark);
    line-height: 1.4;
}

/* ========== Individual Customer Reviews ========== */
.customer-reviews {
    border-top: 1px solid #80808040;
}

.review-item {
    padding: 20px 0; /* 1.25rem */
    border-bottom: 1px solid #80808040;
}

.review-item:first-child {
    padding-top: 24px; /* 1.5rem */
}

.review-item:last-child {
    border-bottom: none;
}

.review-stars i {
    font-size: 16px; /* 1rem */
}

.review-meta {
    font-size: 14.4px; /* 0.9rem */
}

.verified-badge {
    font-size: 12.8px; /* 0.8rem */
    color: var(--verified-blue);
    white-space: nowrap;
}

.review-text {
    font-size: 16px; /* 1rem */
    font-style: italic;
    color: var(--text-dark);
    line-height: 1.5;
}

/* Favorite button */
.btn-favorite {
    font-size: 14px; /* 0.875rem */
}

.btn-favorite:hover {
    text-decoration: none;
}

/* ========== Pagination & Footer ========== */
.reviews-footer {
    padding-top: 16px; /* 1rem */
    border-top: 1px solid var(--border-light);
}

.reviews-footer .page-link {
    padding: 6px 12px; /* 0.375rem 0.75rem */
    color: var(--text-muted);
    border: 1px solid var(--border-light);
}

.reviews-footer .page-item.active .page-link {
    background-color: #2556e0;
    border-color: #2556e0;
    color: #ffffff;
}

.reviews-footer .page-item:not(.active) .page-link:hover {
    background-color: #f8f9fa;
}

/* Shopper Approved badge */

.badge-container {
    display: inline-flex;
    align-items: center;
    padding: 8px; /* 0.5rem */
    background: #ffffff;
    border: 1px solid var(--border-light);
    border-radius: 4px; /* 0.25rem */
}

.badge-shopper {
    font-weight: 700;
    color: #0d6efd;
    margin-right: 4px; /* 0.25rem */
}

.badge-approved {
    font-weight: 700;
    color: var(--rating-green);
}

/* ========== Sidebar ========== */
.sidebar-sticky {
    position: sticky;
    top: 16px; /* 1rem */
}

.sidebar-image img {
    width: 100%;
    height: auto;
    object-fit: cover;
}

/* Trust badges panel */
.trust-panel {
    background-color: var(--trust-panel-bg);
    border: 1px solid #f0e6d8;
    border-radius: 6px; /* 0.375rem */
    padding: 20px; /* 1.25rem */
}

.trust-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0; /* 0.75rem */
    font-size: 14.4px; /* 0.9rem */
    color: var(--text-dark);
    flex-direction: column;
}

.trust-item:not(:last-child) {
    border-bottom: 1px solid #80808040;
}

.trust-icon {
    font-size: 24px; /* 1.5rem */
    color: var(--text-muted);
    flex-shrink: 0;
}

.trust-panel .trust-item img{max-width: 70px;}

/* Mascot panel */
.mascot-panel {
    background-color: var(--trust-panel-bg);
    border: 1px solid #80808040;
    border-radius: 6px; /* 0.375rem */
    padding: 24px; /* 1.5rem */
    text-align: center;
}

.mascot-panel .social-icons {
    margin: 12px 0 0 0;
    justify-content: center;
}

.mascot-panel .social-icons a i {    
    color: #000;
}

.mascot-placeholder {
    width: 100px;
    height: 100px;
    margin: 0 auto 16px; /* 1rem */
    background-color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #f0e6d8;
}

.mascot-svg {
    width: 70px;
    height: 70px;
}

.mascot-text {
    font-size: 14.4px; /* 0.9rem */
    color: var(--text-dark);
}

.reviews-footer .dropdown-item.display-option.active {
    background-color: #6c757d;
    color: #fff;
}

.overall-rating-title {
    font-size: 26px;
    text-align: center;
    font-weight: bold;
}

.mascot-panel a {
    font-size: 30px;
}

/* ========== Responsive Adjustments ========== */
@media (max-width: 1199px){
    .reviews-section{
        padding: 80px 0;
    }
}

@media (max-width: 991.98px) {
    .sidebar-sticky {
        position: static;
    }
    
    .sidebar-image {
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    .trust-item span{
        font-size:14px;
    }
}

@media (max-width: 767.98px) {
    .reviews-section{
        padding: 30px 0;
    }
    .reviews-title {
        font-size: 24px; /* 1.5rem */
    }
    
    .rating-value {
        font-size: 32px; /* 2rem */
    }
    
    .reviews-subtitle{
        font-size:22px;
    }
}

@media (max-width: 575.98px) {
    .review-summary .row > div {
        margin-bottom: 8px; /* 0.5rem */
    }
    
    .verified-badge {
        width: 100%;
    }
    .reviews-subtitle {
        font-size: 18px;
    }
}

</style>

<div class="reviews-section">
    <div class="container">
        <div class="row g-4">
            <!-- Main Content - Left Column -->
            <main class="col-md-8">
                <!-- Page Header -->
                <div class="mb-4">
                    <p class="text-uppercase text-muted small">DISCOVER LIVE EVENTS</p>
                    <h1 class="reviews-title mt-2 mb-lg-4 mb-3">SeatOutlet Customer Reviews</h1>
                    <h2 class="reviews-subtitle">Customer Reviews &amp; Feedback – SeatOutlet</h2>
                </div>

                <!--
                    The rating summary and the 12 reviews below are
                    illustrative sample content, not real collected customer
                    feedback (no reviews table or collection flow backs
                    this page - flagged the same way the fabricated
                    Organization schema reviews were removed earlier).
                    Publishing these as genuine risks a false-advertising/
                    deceptive-reviews problem (FTC Endorsement Guides in the
                    US, the Competition Act in Canada) and Google manual
                    action if this page is ever indexed. Replace with a
                    real reviews data source (Trustpilot/Google Reviews
                    embed, or a real reviews table) before this page goes
                    live/indexed - it's currently noindex by header.php's
                    site-wide default.
                -->
                <div class="alert alert-warning mb-4" role="alert">
                    <strong>Note:</strong> The ratings and reviews below are illustrative examples while we build out real customer review collection. Real verified reviews will replace this content soon.
                </div>

                <!-- Review Summary Box -->
                <section class="review-summary mb-4">
                    <div class="row g-3 align-items-stretch">
                        <!-- Overall Rating Display -->
                        <div class="col-12 col-lg-3">
                            <div class="rating-box h-100">
                                <span class="rating-value">4.4</span>
                                <span class="rating-label">Out of 5.0</span>
                            </div>
                        </div>
                        <!-- Star Breakdown Chart -->
                        <div class="col-12 col-lg-3 col-md-4">
                            <div class="star-breakdown h-100">
                                <div class="star-row">
                                    <span class="star-label"><i class="bi bi-star-fill text-warning me-1"></i>5 Star</span>
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%"></div>
                                    </div>
                                    <span class="star-count">1070</span>
                                </div>
                                <div class="star-row">
                                    <span class="star-label"><i class="bi bi-star-fill text-warning me-1"></i>4 Star</span>
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 45%"></div>
                                    </div>
                                    <span class="star-count">484</span>
                                </div>
                                <div class="star-row">
                                    <span class="star-label"><i class="bi bi-star-fill text-warning me-1"></i>3 Star</span>
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 14%"></div>
                                    </div>
                                    <span class="star-count">150</span>
                                </div>
                                <div class="star-row">
                                    <span class="star-label"><i class="bi bi-star-fill text-warning me-1"></i>2 Star</span>
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 5%"></div>
                                    </div>
                                    <span class="star-count">50</span>
                                </div>
                                <div class="star-row">
                                    <span class="star-label"><i class="bi bi-star-fill text-warning me-1"></i>1 Star</span>
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 5%"></div>
                                    </div>
                                    <span class="star-count">50</span>
                                </div>
                            </div>
                        </div>
                        <!-- Overall Rating-->
                        <div class="col-12 col-lg-3 col-md-4">
                            <div class="overall-rating h-100 align-items-center">
                                <h4 class="mb-2 overall-rating-title">Overall Rating</h4>
                                <div class="mb-2">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <i class="bi bi-star-fill text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <!--Percentage -->
                        <div class="col-12 col-lg-3 col-md-4">
                            <div class="overall-rating h-100 align-items-center">                                
                                <p class="percentage-value mb-1">89%</p>
                                <p class="percentage-desc small text-center">of customers who left reviews say they would purchase tickets again through SeatOutlet - read more customer reviews below, or see our
                                <a href="/guarantee">satisfaction guarantee</a>.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Individual Customer Reviews -->
                <section class="customer-reviews">
                    <!-- Review 1 -->
                    <article class="review-item" data-rating="5" data-review-id="1">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">January 28, 2026</span> by <strong>Michael R.</strong> (New York, NY, USA)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Checkout was fast and tickets arrived instantly."</blockquote>
                    </article>

                    <!-- Review 2 -->
                    <article class="review-item" data-rating="5" data-review-id="2">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">January 18, 2026</span> by <strong>Melissa R.</strong> (Vancouver, BC, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Very easy platform to find great seats."</blockquote>
                    </article>

                    <!-- Review 3 -->
                    <article class="review-item" data-rating="5" data-review-id="3">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">January 5, 2026</span> by <strong>Daniel T.</strong> (Calgary, AB, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Seat comparison feature helped me choose perfect seats."</blockquote>
                    </article>

                    <!-- Review 4 -->
                    <article class="review-item" data-rating="4" data-review-id="4">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">December 30, 2025</span> by <strong>Jessica W.</strong> (Los Angeles, CA, USA)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Smooth checkout and secure payment."</blockquote>
                    </article>

                    <!-- Review 5 -->
                    <article class="review-item" data-rating="5" data-review-id="5">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">December 22, 2025</span> by <strong>Amanda K.</strong> (Ottawa, ON, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Customer support was quick and helpful."</blockquote>
                    </article>

                    <!-- Review 6 -->
                    <article class="review-item" data-rating="5" data-review-id="6">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">December 10, 2025</span> by <strong>Ryan L.</strong> (Montreal, QC, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Seat selection UI is clean and simple."</blockquote>
                    </article>

                    <!-- Review 7 -->
                    <article class="review-item" data-rating="5" data-review-id="7">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">November 28, 2025</span> by <strong>Chris P.</strong> (Chicago, IL, USA)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Pricing was transparent with no hidden fees."</blockquote>
                    </article>

                    <!-- Review 8 -->
                    <article class="review-item" data-rating="5" data-review-id="8">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">November 18, 2025</span> by <strong>Michael P.</strong> (Edmonton, AB, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Tickets were delivered exactly as promised."</blockquote>
                    </article>

                    <!-- Review 9 -->
                    <article class="review-item" data-rating="5" data-review-id="9">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">October 30, 2025</span> by <strong>Sarah B.</strong> (Houston, TX, USA)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Navigation is very user friendly."</blockquote>
                    </article>

                    <!-- Review 10 -->
                    <article class="review-item" data-rating="4" data-review-id="10">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">October 15, 2025</span> by <strong>Andrew C.</strong> (Victoria, BC, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Great selection of events."</blockquote>
                    </article>

                    <!-- Review 11 -->
                    <article class="review-item" data-rating="5" data-review-id="11">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">September 28, 2025</span> by <strong>Olivia W.</strong> (Seattle, WA, USA)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Tickets arrived on time and worked perfectly."</blockquote>
                    </article>

                    <!-- Review 12 -->
                    <article class="review-item" data-rating="5" data-review-id="12">
                        <div class="review-stars mb-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <p class="review-meta mb-0">
                                <span class="text-muted">September 12, 2025</span> by <strong>Brian S.</strong> (Toronto, ON, Canada)
                            </p>
                            <span class="verified-badge"><i class="bi bi-person-check me-1"></i>Verified Buyer</span>
                        </div>
                        <blockquote class="review-text mb-0">"Reliable ticket service. Highly recommended."</blockquote>
                    </article>

                </section>

                <!-- Pagination & Display Options -->
                <div class="reviews-footer mt-4">
                    <div class="d-flex flex-wrap align-items-center gap-3 gap-md-4 mb-3 justify-content-md-start justify-content-center">
                        <nav aria-label="Reviews pagination">
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                <li class="page-item active" data-page="1"><a class="page-link" href="#">1</a></li>
                                <li class="page-item" data-page="2"><a class="page-link" href="#">2</a></li>
                                <li class="page-item" data-page="3"><a class="page-link" href="#">3</a></li>
                            </ul>
                        </nav>
                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-md-start justify-content-center">
                            <span class="fw-bold">Display Options</span>
                            <div class="dropdown">
                                <button class="btn btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" id="displayOptionsBtn" aria-expanded="false">
                                    <span id="displayOptionsSelected">Newest to Oldest</span> 
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item display-option" href="#" data-option="highest-lowest">Highest to Lowest</a></li>
                                    <li><a class="dropdown-item display-option" href="#" data-option="newest-oldest">Newest to Oldest</a></li>
                                    <li><a class="dropdown-item display-option" href="#" data-option="oldest-newest">Oldest to Newest</a></li>
                                    <li><a class="dropdown-item display-option" href="#" data-option="lowest-highest">Lowest to Highest</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
            </div>
            </main>

            <!-- Sidebar - Right Column -->
            <aside class="col-md-4">
                <div class="sidebar-sticky">
                    <!-- Crowd/Event Image -->
                    <div class="sidebar-image mb-4">
                        <img src="/images/crowd-at-concert-or-event.webp" alt="Fans who left customer reviews at a live event" class="img-fluid rounded" width="442" height="442" decoding="async">
                    </div>
                    <!-- Trust Badges Panel -->
                    <div class="trust-panel mb-4 bg-white border-0">
                        <div class="trust-item">
                            <img src="/images/moneyback-p3.png" alt="Money Back Guarantee" class="img-fluid" width="67" height="70" decoding="async">
                            <span class="fw-bold text-uppercase">Money Back Guarantee</span>
                        </div>
                        <div class="trust-item">
                            <img src="/images/secure-payment-p3.png" alt="Secure Payment Gateway" class="img-fluid" width="65" height="68" decoding="async">
                            <span class="fw-bold text-uppercase">Secure Payment Gateway</span>
                        </div>
                        <div class="trust-item">
                           <img src="/images/bbb-p3.png" alt="BBB Accredited Business" class="img-fluid" width="68" height="67" decoding="async">
                            <span class="fw-bold text-uppercase">BBB Accredited Business</span>
                        </div>
                    </div>
                    <!-- Mascot & Social Share -->
                    <div class="mascot-panel">
                        <p class="mascot-text mb-2 fw-semibold">Ask your friend to go with you!</p>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/profile.php?id=61588886945534" target="_blank"><i class="bi bi-facebook fs-4"></i></a>
                            <a href="#"><i class="bi bi-twitter-x fs-4"></i></a>
                            <a href="https://www.youtube.com/@SeatOutlet" target="_blank"><i class="bi bi-youtube fs-4"></i></a>
                            <a href="https://www.instagram.com/seatoutlet/" target="_blank"><i class="bi bi-instagram fs-4"></i></a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>


<?php include 'footer.php'; ?>
<script>
// jQuery is a deferred script: wait for it before running this block.
document.addEventListener('DOMContentLoaded', function () {
(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        reviewsPerPage: 8
    };

    // State
    let currentPage = 1;
    let currentDisplayOption = 'newest-oldest'; // highest-lowest, newest-oldest, oldest-newest, lowest-highest

    /**
     * Get reviews based on display option
     */
    function getVisibleReviews() {
        return $('.review-item');
    }

    /**
     * Sort review elements based on current display option
     */
    function sortReviews(elements) {
        const arr = Array.from(elements);
        arr.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);
            const ratingA = parseInt($a.data('rating'), 10);
            const ratingB = parseInt($b.data('rating'), 10);
            const dateA = $a.data('date') || '';
            const dateB = $b.data('date') || '';

            switch (currentDisplayOption) {
                case 'highest-lowest':
                    return ratingB - ratingA;
                case 'lowest-highest':
                    return ratingA - ratingB;
                case 'newest-oldest':
                    return dateB.localeCompare(dateA);
                case 'oldest-newest':
                    return dateA.localeCompare(dateB);
                default:
                    return 0;
            }
        });
        return arr;
    }

    /**
     * Update dropdown button
     */
    function updateDisplayOptionsButton() {
        const labels = {
            'highest-lowest': 'Highest to Lowest',
            'newest-oldest': 'Newest to Oldest',
            'oldest-newest': 'Oldest to Newest',
            'lowest-highest': 'Lowest to Highest'
        };

        $('#displayOptionsSelected').text(labels[currentDisplayOption] || 'Display Options');

        $('.display-option').removeClass('active');
        $('.display-option[data-option="' + currentDisplayOption + '"]').addClass('active');
    }

    /**
     * Show the correct page of reviews
     */
    function showPage(page) {
        currentPage = page;

        const $visible = getVisibleReviews();
        const sorted = sortReviews($visible.get());
        const totalItems = sorted.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / CONFIG.reviewsPerPage));

        const start = (currentPage - 1) * CONFIG.reviewsPerPage;
        const end = start + CONFIG.reviewsPerPage;

        $('.review-item').addClass('d-none');

        for (let i = start; i < end && i < sorted.length; i++) {
            $(sorted[i]).removeClass('d-none');
        }

        updatePagination(totalPages, totalItems);

        $('#pagination .page-item').removeClass('active');
        $('#pagination .page-item[data-page="' + currentPage + '"]').addClass('active');
    }

    /**
     * Update pagination controls
     */
    function updatePagination(totalPages, totalItems) {
        const $pagination = $('#pagination');
        $pagination.find('.page-item').addClass('d-none');

        for (let i = 1; i <= Math.min(3, totalPages); i++) {
            $pagination.find('.page-item[data-page="' + i + '"]').removeClass('d-none');
        }

        if (totalPages < 3) {
            $pagination.find('.page-item[data-page="3"]').addClass('d-none');
        }
        if (totalPages < 2) {
            $pagination.find('.page-item[data-page="2"]').addClass('d-none');
        }
    }

    /**
     * Apply display option
     */
    function applyDisplayOption() {
        currentPage = 1;
        updateDisplayOptionsButton();
        showPage(1);
    }

    // Initialize
    $(document).ready(function() {

        // Pagination click handler
        $(document).on('click', '#pagination .page-link', function(e) {
            e.preventDefault();
            const page = parseInt($(this).closest('.page-item').data('page'), 10);

            if (page && !$(this).closest('.page-item').hasClass('d-none')) {
                showPage(page);
            }
        });

        // Display Options dropdown
        $(document).on('click', '.display-option', function(e) {
            e.preventDefault();
            currentDisplayOption = $(this).data('option');
            applyDisplayOption();
        });

        // Initialize state
        updateDisplayOptionsButton();
        showPage(1);
    });

})(jQuery);
});



</script>