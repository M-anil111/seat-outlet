<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical. Kept noindex (header.php's site-wide default) since
// the visible content is still illustrative sample reviews - see the
// disclaimer added to this page.
$pageMetaTitle       = 'Customer Reviews | Seat Outlet';
$pageMetaDescription = 'Read customer reviews of Seat Outlet, a ticket marketplace for buying concert, sports, and event tickets online.';
$pageCanonicalUrl    = HOME_URL . '/seat-outlet-reviews';
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
                    
                    <h1 class="reviews-title mt-2 mb-lg-4 mb-3">Seat Outlet Reviews: Customer Feedback</h1>
                    <h2 class="reviews-subtitle">What we can show you today</h2>
                </div>

                <section class="mb-4">
                    <p>We have not published customer reviews on this page yet, and we will not publish sample ones. Reviews will appear here only after they are checked against a real order.</p>
                    <p>Bought tickets from us? <a href="/ticket-customer-service">Tell our customer service team how it went</a>, good or bad. For independent opinions, you can also look for Seat Outlet on the <a href="https://www.bbb.org/" target="_blank" rel="noopener">Better Business Bureau</a> website and read what the <a href="/worry-free-guarantee">worry-free guarantee</a> and <a href="/ticket-buyer-protection">ticket buyer protection</a> cover before you buy.</p>
                </section>
            </main>

            <!-- Sidebar - Right Column -->
            <aside class="col-md-4">
                <div class="sidebar-sticky">
                    <!-- Crowd/Event Image -->
                    <div class="sidebar-image mb-4">
                        <img src="/images/crowd-at-concert-or-event.webp" alt="Fans cheering at a live event" class="img-fluid rounded" width="442" height="442" decoding="async">
                    </div>
                    <!-- Mascot & Social Share -->
                    <div class="mascot-panel">
                        <p class="mascot-text mb-2 fw-semibold">Follow Seat Outlet</p>
                        <div class="social-icons">
                            <a href="https://www.facebook.com/profile.php?id=61588886945534" target="_blank" rel="noopener" aria-label="Seat Outlet on Facebook"><i class="bi bi-facebook fs-4"></i></a>
                                                        <a href="https://www.youtube.com/@SeatOutlet" target="_blank" rel="noopener" aria-label="Seat Outlet on YouTube"><i class="bi bi-youtube fs-4"></i></a>
                            <a href="https://www.instagram.com/seatoutlet/" target="_blank" rel="noopener" aria-label="Seat Outlet on Instagram"><i class="bi bi-instagram fs-4"></i></a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>


<?php soSeoCopy('seat-outlet-reviews'); ?>
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