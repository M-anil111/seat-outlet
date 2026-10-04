<?php
// Human-readable sitemap: the main sections of the site in one place. (sitemap.xml is the XML file for search engines.)
require_once 'functions.php';
$pageMetaTitle       = 'Sitemap: Every Section of Seat Outlet';
$pageMetaDescription = 'Find your way around Seat Outlet: concerts, sports, theater and festivals, city listings, buying guides, help, guarantee and policy pages.';
$pageCanonicalUrl    = HOME_URL . '/sitemap-page';
$soMenu = require __DIR__ . '/inc/menu.php';
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$posts = [];
try { $posts = listPublishedBlogPosts(1, 50); } catch (Throwable $e) { $posts = []; }
include 'header.php';
?>
<section class="so-sitemap py-5">
    <div class="container">
        <h2 class="so-sitemap__title">Sitemap</h2>
        <p class="so-sitemap__lead">Every main section of Seat Outlet in one place. Search engines read our <a href="/sitemap.xml">XML sitemap</a> instead.</p>

        <div class="so-sitemap__grid">
            <?php foreach ($soMenu as $sec) { ?>
            <nav class="so-sitemap__col" aria-labelledby="sm-<?php echo $h($sec['key']); ?>">
                <h3 id="sm-<?php echo $h($sec['key']); ?>"><a href="<?php echo $h($sec['href']); ?>"><?php echo $h($sec['label']); ?></a></h3>
                <?php foreach ($sec['groups'] as $g) { ?>
                    <p class="so-sitemap__group"><?php echo $h($g['title']); ?></p>
                    <ul>
                        <?php foreach ($g['links'] as $l) { ?><li><a href="<?php echo $h($l[1]); ?>"><?php echo $h($l[0]); ?></a></li><?php } ?>
                    </ul>
                <?php } ?>
            </nav>
            <?php } ?>

            <nav class="so-sitemap__col" aria-labelledby="sm-guides">
                <h3 id="sm-guides"><a href="/blog">Guides and blog</a></h3>
                <ul>
                    <?php foreach ($posts as $p) { ?><li><a href="/blog/<?php echo $h($p['slug']); ?>"><?php echo $h($p['title']); ?></a></li><?php } ?>
                    <li><a href="/how-to-buy-tickets-online">How to buy tickets online</a></li>
                    <li><a href="/why-are-concert-tickets-so-expensive">Why concert tickets cost what they do</a></li>
                    <li><a href="/ticket-deals">Ticket deals</a></li>
                    <li><a href="/tickets-promo-code">Promo codes</a></li>
                </ul>
            </nav>

            <nav class="so-sitemap__col" aria-labelledby="sm-help">
                <h3 id="sm-help">Help and trust</h3>
                <ul>
                    <li><a href="/worry-free-guarantee">Our guarantee</a></li>
                    <li><a href="/ticket-buyer-protection">Ticket buyer protection</a></li>
                    <li><a href="/ticket-faq">Ticket FAQ</a></li>
                    <li><a href="/ticket-customer-service">Contact us</a></li>
                    <li><a href="/seat-outlet-reviews">Reviews and feedback</a></li>
                    <li><a href="/customer-testimonials">Testimonials</a></li>
                    <li><a href="/seat-outlet-bbb">Our customer commitments</a></li>
                </ul>
            </nav>

            <nav class="so-sitemap__col" aria-labelledby="sm-about">
                <h3 id="sm-about">About and legal</h3>
                <ul>
                    <li><a href="/about-seat-outlet">About Seat Outlet</a></li>
                    <li><a href="/ticket-partner-program">Partner with us</a></li>
                    <li><a href="/our-network">Our network</a></li>
                    <li><a href="/terms-and-conditions">Terms and conditions</a></li>
                    <li><a href="/privacy-policy">Privacy policy</a></li>
                    <li><a href="/cookie-policy">Cookie policy</a></li>
                </ul>
            </nav>
        </div>
    </div>
</section>
<?php include 'footer.php'; ?>
