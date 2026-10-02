<?php
require_once 'functions.php';
require_once __DIR__ . '/inc/blog-render.php';

$slug = sanitize_title((string) ($_GET['slug'] ?? ''));

if ($slug === '') {
    include 'header.php';
    echo '<div class="container py-5"><p>Invalid blog post.</p></div>';
    include 'footer.php';
    exit;
}

$post = getBlogPostBySlug($slug);

if (empty($post)) {
    include 'header.php';
    echo '<div class="container py-5"><p>That blog post could not be found.</p></div>';
    include 'footer.php';
    exit;
}

$postUrl = HOME_URL . '/blog/' . $post['slug'];

// --- SEO: computed before including header.php, same convention used
// throughout this app - see functions.php. ---
$pageMetaTitle       = !empty($post['meta_title']) ? $post['meta_title'] : ($post['title'] . ' | Seat Outlet Blog');
$pageMetaDescription = !empty($post['meta_description']) ? $post['meta_description'] : ($post['excerpt'] ?? '');
$pageCanonicalUrl    = $postUrl;
$pageJsonLdNodes = array_values(array_filter([
    buildBreadcrumbListSchema([
        ['label' => 'Home', 'url' => HOME_URL],
        ['label' => 'Blog', 'url' => HOME_URL . '/blog'],
    ], $post['title']),
    buildArticleSchema($post, $postUrl),
]));

include 'header.php';

$wordCount = str_word_count(strip_tags((string) $post['content']));
$readMinutes = max(1, (int) round($wordCount / 220));
$related = [];
foreach (listPublishedBlogPosts(1, 8) as $r) {
    if ($r['slug'] !== $post['slug'] && count($related) < 3) { $related[] = $r; }
}
// Shortcodes first (live events, newsletter, CTA boxes), then the contents list and heading ids from the real headings.
[$articleHtml, $toc] = soBlogProcess(soBlogShortcodes((string) $post['content']));
$author   = trim((string) ($post['author_name'] ?? '')) ?: 'Jay Mehta';
$aParts   = preg_split('/\s+/', $author);
$initials = strtoupper(substr($aParts[0], 0, 1) . (count($aParts) > 1 ? substr(end($aParts), 0, 1) : ''));
$cat      = trim((string) ($post['category'] ?? ''));
$endCta   = soBlogEndCta($cat);
$live     = trim((string) ($post['live_search'] ?? ''));
?>
<article class="so-art">
    <header class="so-art__hero">
        <div class="container so-art__wrap">
            <nav class="so-art__crumb" aria-label="Breadcrumb"><a href="/blog">Blog</a><?php if ($cat !== '') { ?><span aria-hidden="true">/</span><a href="/blog?category=<?php echo soBlogH(sanitize_title($cat)); ?>"><?php echo soBlogH($cat); ?></a><?php } ?></nav>
            <h1><?php echo soBlogH($post['title']); ?></h1>
            <?php if (!empty($post['excerpt'])) { ?><p class="so-art__lede"><?php echo soBlogH($post['excerpt']); ?></p><?php } ?>
            <div class="so-art__by">
                <span class="so-np__av" aria-hidden="true"><?php echo soBlogH($initials); ?></span>
                <span class="so-art__who"><strong><?php echo soBlogH($author); ?></strong><small><?php echo $readMinutes; ?> min read</small></span>
            </div>
            <?php if (!empty($post['featured_image'])) { ?>
            <img class="so-art__img" src="<?php echo soBlogH($post['featured_image']); ?>" alt="" width="1200" height="675" fetchpriority="high" decoding="async">
            <?php } ?>
        </div>
    </header>

    <div class="container so-art__wrap so-art__main">
        <?php echo soBlogTocHtml($toc); ?>
        <div class="blog-post-content">
            <?php echo $articleHtml; ?>
        </div>

        <?php if ($live !== '') { echo soBlogEventsBlock(['performer' => $live, 'limit' => 6]); } ?>

        <section class="so-art__end" aria-label="Next steps">
            <?php echo soBlogCtaBox($endCta); ?>
            <?php echo soBlogNewsletterBox(['id' => 'subscribe']); ?>
        </section>

        <aside class="so-art__author">
            <span class="so-np__av so-art__avbig" aria-hidden="true"><?php echo soBlogH($initials); ?></span>
            <div>
                <p class="so-art__written">Written by</p>
                <p class="so-art__name"><?php echo soBlogH($author); ?></p>
                <?php if ($author === 'Jay Mehta') { ?>
                <p class="so-art__bio">Jay Mehta is the Founder and CEO of Mindshare Consulting Inc and writes the Seat Outlet guides to help fans buy tickets with confidence.</p>
                <?php } ?>
            </div>
        </aside>

        <?php if ($related) { ?>
            <aside class="so-art__more" aria-label="More guides">
                <h2>More guides</h2>
                <div class="so-art__more-grid">
                    <?php foreach ($related as $r) { ?>
                        <a href="/blog/<?php echo soBlogH($r['slug']); ?>">
                            <?php if (!empty($r['featured_image'])) { ?><img src="<?php echo soBlogH($r['featured_image']); ?>" alt="" width="400" height="260" loading="lazy" decoding="async"><?php } ?>
                            <span><?php echo soBlogH($r['title']); ?></span>
                        </a>
                    <?php } ?>
                </div>
            </aside>
        <?php } ?>
        <p class="so-art__top"><a href="#top" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">Back to top <span aria-hidden="true">&uarr;</span></a></p>
    </div>
</article>

<?php include 'footer.php'; ?>
