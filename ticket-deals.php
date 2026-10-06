<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical. Copy per the site's own content documentation.
$pageMetaTitle       = 'Ticket Deals: Find Value on Concert and Sports Tickets | Seat Outlet';
$pageMetaDescription = 'How to find better value on concert, sports and theater tickets: compare sections, watch dates and review the full cost before you pay.';
$pageCanonicalUrl    = HOME_URL . '/ticket-deals';
include 'header.php';
?>

<style>
    /* Content layout: simple, step-by-step */

    .privacy-page .content-wrapper {
        max-width: none;   /* the page container sets the width, like the home page copy */
        margin: 0;
    }

    .privacy-page .policy-section {
        border-top: 1px solid #e5e7eb;
        padding-top: 30px;
        margin-top: 30px;
    }

    .privacy-page .policy-section:first-of-type {
        border-top: none;
        padding-top: 0;
        margin-top: 0;
    }

    .privacy-page h2 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    .privacy-page .policy-num {
        font-size: 22px;
        font-weight: 600;
        color: #0056d6;
        margin-bottom: 8px;
    }

    .privacy-page h3 {
        font-size: 17px;
        font-weight: 600;
        margin-top: 16px;
        margin-bottom: 8px;
    }

    .privacy-page p {
        font-size: 15px;
        color: #374151;
        margin-bottom: 10px;
    }

    .privacy-page ul {
        padding-left: 20px;
        margin-bottom: 10px;
    }

    .privacy-page ul li {
        font-size: 14.5px;
        color: #374151;
        margin-bottom: 4px;
        list-style: disc;
    }

    /* Responsive */

    @media (max-width: 991px) {
        .privacy-page .hero-title {
            font-size: 40px;
        }
    }

    @media (max-width: 767.98px) {
        .privacy-page .section-padding {
            padding: 40px 0;
        }

        .privacy-page .hero-title {
            font-size: 30px;
        }
    }
</style>

<main class="privacy-page">

    <!-- Hero / Inner Banner -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-9 hero-inner">
                    <h1 class="hero-title">Ticket Deals</h1>
                    <p class="hero-subtitle">Compare sections and dates to find better value on live events</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content (exact text, sections 1–14) -->
    <section class="section-padding">
        <div class="container">
            <div class="content-wrapper">

                <!-- 1 -->
                <div class="policy-section" id="section-1">
                    <h2>Finding value on live events</h2>
                    <p>Seat Outlet is a resale marketplace, so independent sellers set their own prices. The same event can have listings at very different price points, and prices may be above or below face value. We do not promise the lowest price on any event, but we show you the options side by side so you can choose.</p>
                    <p>Start with <a href="/concert-tickets-for-sale">concerts</a>, <a href="/game-day-tickets">sports</a>, <a href="/buy-broadway-tickets">theater</a> or <a href="/upcoming-music-festivals">festivals</a>, or look at <a href="/city-events">events in your city</a>.</p>
                </div>

                <!-- 2 -->
                <div class="policy-section" id="section-2">
                    <h2>How to compare</h2>
                    <ul>
                    <li>Open more than one section for the same date, not only the first listing.</li>
                    <li>A seat a few rows back can cost less than one near the front and still give a good view.</li>
                    <li>Weeknight events can be priced differently from weekend ones, so check more than one date.</li>
                    <li>Listings can rise or fall as the event date approaches, so a price you see today may change.</li>
                    <li>Review the full cost at checkout before you pay, because fees and taxes can be added there.</li>
                    </ul>
                </div>

                <!-- 3 -->
                <div class="policy-section" id="section-3">
                    <h2>Promo codes</h2>
                    <p>We list the promo codes we have on the <a href="/tickets-promo-code">promo codes page</a>. Whether a code applies is decided at checkout.</p>
                </div>

                <!-- 4 -->
                <div class="policy-section" id="section-4">
                    <h2>Get ticket alerts by email</h2>
                    <?php echo soLeadForm(['source' => 'deals', 'title' => 'Get ticket alerts and new guides in your inbox', 'text' => 'Tour announcements, on-sale news and plain-English ticket advice.']); ?>
                </div>
            </div>
        </div>
    </section>

</main>

<?php soSeoCopy('ticket-deals'); ?>
<?php include 'footer.php'; ?>