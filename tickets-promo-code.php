<?php
require_once 'functions.php';
// SEO: this page previously relied on header.php's generic fallback
// title/canonical.
$pageMetaTitle       = 'Deals & Promotions – Ticket Discount Codes | Seat Outlet';
$pageMetaDescription = 'Promo codes listed for Seat Outlet orders, how to try one at checkout, and honest tips for paying less on concert, sports and theater tickets.';
$pageCanonicalUrl    = HOME_URL . '/tickets-promo-code';
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
                    <h1 class="hero-title">Ticket Promo Codes and Deals</h1>
                    <p class="hero-subtitle">Find a tickets promo code for concerts, sports and theater, plus tips on using it at checkout.
                    </p>
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
                    <h2>Promo codes for concerts, sports and events</h2>
                    <p>Below are the promo codes we list for orders placed through Seat Outlet. Whether a code applies is decided at checkout, which is hosted by TicketNetwork, so we cannot promise that a code will work on every event or every order. If a code does not apply, nothing is charged extra: you simply pay the price shown.</p>
                    <p>Seat Outlet is a resale marketplace. Prices are set by sellers and may be above or below face value, so comparing seats usually saves more than any code. See all <a href="/buy-tickets-online">ticket listings</a> to get started.</p>
                </div>
                <div class="tab-section content-section-detail mb-0" id="promocode">
						<h2 class="so-heading fw-bold fs-4 mb-4 text-black">Latest Ticket Promo Codes</h2>
						
						<div class="so-evp-promo so-evp-promo--stack so-evp-promo--page">
							<div class="so-evp-promo__grid">
								<?php foreach (SO_PROMO_CODES as $soPromo) { echo soSpecPromoCard($soPromo, 'event'); } ?>
							</div>
						</div>
                        <p></p>	
                            </br>
                        <p>Enter a promo code in the promo code field at checkout when one is offered. Codes apply only where the checkout accepts them, minimum order amounts apply as shown, and codes can change or stop working without notice.</p>			
					</div>
                <!-- 3 -->
                <div class="policy-section" id="section-3">
                    <h2>How to try a promo code</h2>
                    <ol>
                        <li>Pick your event and your seats on the event page.</li>
                        <li>Continue to checkout.</li>
                        <li>If the checkout shows a promo code field, enter the code and check the new total.</li>
                        <li>Review the full cost before you pay.</li>
                    </ol>
                </div>

                <!-- 4 -->
                <div class="policy-section" id="section-4">
                    <h2>Ways to pay less that do not need a code</h2>
                    <?php ob_start(); ?>
                    <ul>
                        <li>Compare sections and rows for the same event. Prices differ a lot between listings.</li>
                        <li>Check the <a href="/ticket-deals">ticket deals page</a> for lower-priced events.</li>
                        <li>Be flexible on dates when you can.</li>
                        <li>Read why <a href="/why-are-concert-tickets-so-expensive">concert tickets cost what they do</a> so you know what you are paying for.</li>
                        <li>Look at the all-in price before you choose a listing, not only the price per ticket.</li>
                        <li>Set a price alert on an event you want, and we email you if the price drops.</li>
                    </ul>
                    <?php echo soReadMoreBlock((string) ob_get_clean(), 'pay-less-list', 3, 'Show all ways to pay less', 'Show fewer'); ?>
                </div>

                <!-- 5 -->
                <div class="policy-section" id="section-5">
                    <h2>Get ticket alerts by email</h2>
                    <?php echo soLeadForm(['source' => 'promo', 'title' => 'Get ticket alerts and new guides in your inbox', 'text' => 'Tour announcements, on-sale news and plain-English ticket advice. We cannot promise promo codes.', 'class' => 'so-nl--inline']); ?>
                </div>

                <?php soMiniFaq('Promo code questions', [
                    ['Which promo codes can I use?', 'We list TAKE5 (5% off orders of $199 or more) and TAKE10 (10% off orders of $349 or more). Whether a code applies to your order is decided at checkout.'],
                    ['Where do I enter a promo code?', 'At checkout, in the promo code field, when the checkout shows one. Check the new total before you pay.'],
                    ['Why did my promo code not work?', 'Codes apply only where the checkout accepts them, the order must meet the minimum shown next to the code, and codes cannot be combined with other offers. If it still fails, contact customer service with your order details.'],
                    ['Can I use a promo code on any event?', 'Not always. Some events or listings may not accept codes, and codes can change or stop working without notice.'],
                ]); ?>

                <!-- 6 -->
                <div class="policy-section" id="section-6">
                    <h2>Terms</h2>
                    <ul>
                        <li>Codes apply only at checkout and only where the checkout accepts them.</li>
                        <li>Minimum order amounts apply as shown next to each code.</li>
                        <li>Codes may not be combined with other offers.</li>
                        <li>Codes can change or stop working without notice.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

</main>

<?php soSeoCopy('tickets-promo-code'); ?>
<?php include 'footer.php'; ?>