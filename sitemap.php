<?php
// The human-readable sitemap at /sitemap. Everything on it is read from the site, so a new page appears without editing this file:
// the main menu sections, the blog and its categories (never each post: the XML sitemap lists those), and every other page the
// XML sitemap finds in the web root (soSitemapStaticPaths()). The XML sitemap for search engines is /sitemaps/sitemap.xml.
require_once 'functions.php';
if (strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), '.php') !== false) { soRedirect301('/sitemap'); }   // there is no .php address for the sitemap
$pageMetaTitle       = 'Sitemap: Every Section of Seat Outlet';
$pageMetaDescription = 'Find your way around Seat Outlet: concerts, sports, theater and festivals, city listings, the blog and its categories, help, guarantee and policy pages.';
$pageCanonicalUrl    = HOME_URL . '/sitemap';
$soMenu = require __DIR__ . '/inc/menu.php';
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };

// Blog categories, each a filtered blog index (/blog?category=<slug>).
$blogCats = [];
try { $blogCats = listBlogCategories(); } catch (Throwable $e) { $blogCats = []; }

// Pages found in the web root that the menu does not already link.
$inMenu = [];
foreach ($soMenu as $sec) {
    $inMenu[rtrim((string) $sec['href'], '/') ?: '/'] = true;
    foreach ($sec['groups'] as $g) { foreach ($g['links'] as $l) { $inMenu[rtrim(strtok((string) $l[1], '?'), '/') ?: '/'] = true; } }
}
$other = [];
foreach (soSitemapStaticPaths() as $path) {
    if ($path === '/' || $path === '/blog' || isset($inMenu[$path])) continue;
    $plan = soSeoPlan($path);
    $label = $plan && !empty($plan['keyword']) ? soKeywordLabel($plan['keyword']) : ucwords(str_replace('-', ' ', ltrim($path, '/')));
    $other[$path] = $label;
}
asort($other, SORT_NATURAL | SORT_FLAG_CASE);
include 'header.php';
?>
<section class="so-sitemap py-5">
    <div class="container">
        <h2 class="so-sitemap__title">Sitemap</h2>
        <p class="so-sitemap__lead">Every main section of Seat Outlet in one place. Search engines read our <a href="/sitemaps/sitemap.xml">XML sitemap</a> instead.</p>

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

            <nav class="so-sitemap__col" aria-labelledby="sm-blog">
                <h3 id="sm-blog"><a href="/blog">Blog</a></h3>
                <?php if ($blogCats) { ?>
                <p class="so-sitemap__group">Blog categories</p>
                <ul>
                    <?php foreach ($blogCats as $c) { ?><li><a href="/blog?category=<?php echo $h($c['slug']); ?>"><?php echo $h($c['name']); ?></a></li><?php } ?>
                </ul>
                <?php } ?>
            </nav>

            <?php foreach (array_chunk($other, max(1, (int) ceil(count($other) / 2)), true) as $i => $chunk) { ?>
            <nav class="so-sitemap__col" aria-labelledby="sm-more-<?php echo (int) $i; ?>">
                <h3 id="sm-more-<?php echo (int) $i; ?>"><?php echo $i === 0 ? 'More pages' : 'More pages, continued'; ?></h3>
                <ul>
                    <?php foreach ($chunk as $path => $label) { ?><li><a href="<?php echo $h($path); ?>"><?php echo $h($label); ?></a></li><?php } ?>
                </ul>
            </nav>
            <?php } ?>
        </div>
    </div>
</section>
<?php include 'footer.php'; ?>
