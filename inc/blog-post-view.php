<?php
// Include-only: one blog entry point, blog.php. It shows the list at /blog and an article at /blog/<slug> (this file).
if (!defined('SO_BLOG_ENTRY')) { http_response_code(404); exit; }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/blog-render.php';

$slug = sanitize_title((string) ($_GET['slug'] ?? ''));
$post = $slug !== '' ? getBlogPostBySlug($slug) : null;

// Unknown or unpublished post: a real 404 with noindex and the branded not-found page. The title is fixed text, never
// built from the URL (a made-up address must not be able to put its own words in a search result).
if (empty($post)) {
    http_response_code(404);
    $pageRobots          = 'noindex, follow';
    $pageMetaTitle       = 'Page Not Found | Seat Outlet';
    $pageMetaDescription = '';
    include __DIR__ . '/../404.php';
    exit;
}

$postUrl = HOME_URL . '/blog/' . $post['slug'];

// --- SEO: computed before including header.php, same convention used
// throughout this app - see functions.php. ---
$pageMetaTitle       = soTitle(!empty($post['meta_title']) ? $post['meta_title'] : $post['title'], $post['title']);
$pageMetaDescription = !empty($post['meta_description']) ? $post['meta_description'] : ($post['excerpt'] ?? '');
$pageCanonicalUrl    = $postUrl;
$GLOBALS['soBlogSlug'] = $post['slug'];
// Link previews: tell the header this is an article and use the post's own picture, not the site logo.
$pageOgType  = 'article';
$pageOgImage = soBlogAbsUrl($post['featured_image'] ?? '') ?: null;
$featAlt = trim((string) ($post['featured_image_alt'] ?? ''));
$pageJsonLdNodes = array_values(array_filter([
    buildBreadcrumbListSchema([
        ['label' => 'Home', 'url' => HOME_URL],
        ['label' => 'Blog', 'url' => HOME_URL . '/blog'],
    ], $post['title']),
    buildArticleSchema($post, $postUrl),
    soAuthorPerson($post['author_name'] ?? ''),
]));
$pageMainEntity = $postUrl . '#article';
$soAuthorNode = soAuthorPerson($post['author_name'] ?? '');
$pageAuthorId = $soAuthorNode ? $soAuthorNode['@id'] : null;

include __DIR__ . '/../header.php';

$wordCount = str_word_count(strip_tags((string) $post['content']));
$readMinutes = max(1, (int) round($wordCount / 220));
// More guides: the same topic cluster (category) first, then the newest others, up to six.
$related = []; $relatedOthers = [];
foreach (listPublishedBlogPosts(1, 40) as $r) {
    if ($r['slug'] === $post['slug']) continue;
    if (($r['category'] ?? '') !== '' && ($r['category'] ?? '') === ($post['category'] ?? '')) { $related[] = $r; } else { $relatedOthers[] = $r; }
}
$related = array_slice(array_merge($related, $relatedOthers), 0, 6);
$soInGuideCluster = ($post['category'] ?? '') === 'Ticket Guides' && $post['slug'] !== 'ticket-buying-guide';
// Shortcodes first (live events, newsletter, CTA boxes), then the contents list and heading ids from the real headings.
$live     = trim((string) ($post['live_search'] ?? ''));
$cat      = trim((string) ($post['category'] ?? ''));
// What the article is about travels with every sign-up, so alerts can be targeted later.
$nlArgs   = $live !== '' ? ['interest_type' => 'performer', 'interest_name' => $live] : ($cat !== '' ? ['interest_type' => 'category', 'interest_name' => $cat] : []);
[$articleHtml, $toc] = soBlogProcess(soBlogShortcodes((string) $post['content']), true, $nlArgs);
$articleHtml = soNoDashes($articleHtml);
$author   = trim((string) ($post['author_name'] ?? '')) ?: 'Jay Mehta';
$aParts   = preg_split('/\s+/', $author);
$initials = strtoupper(substr($aParts[0], 0, 1) . (count($aParts) > 1 ? substr(end($aParts), 0, 1) : ''));
$endCta   = soBlogEndCta($cat);
$updated  = !empty($post['updated_at']) ? strtotime($post['updated_at']) : 0;
$published = !empty($post['published_at']) ? strtotime($post['published_at']) : 0;
?>
<article class="so-art">
    <header class="so-art__hero">
        <div class="container so-art__wrap">
            <nav class="so-art__crumb" aria-label="Breadcrumb"><a href="/blog">Blog</a><?php if ($cat !== '') { ?><span aria-hidden="true">/</span><a href="/blog?category=<?php echo soBlogH(sanitize_title($cat)); ?>"><?php echo soBlogH($cat); ?></a><?php } ?></nav>
            <h1><?php echo soBlogH($post['title']); ?></h1>
            <?php if (!empty($post['excerpt'])) { ?><p class="so-art__lede"><?php echo soBlogH($post['excerpt']); ?></p><?php } ?>
            <div class="so-art__by">
                <span class="so-np__av" aria-hidden="true"><?php echo soBlogH($initials); ?></span>
                <span class="so-art__who"><strong><?php echo soBlogH($author); ?></strong><small><?php echo $readMinutes; ?> min read<?php if ($updated) { ?> &middot; Updated <time datetime="<?php echo soBlogH(date('Y-m-d', $updated)); ?>"><?php echo soBlogH(date('M j, Y', $updated)); ?></time><?php } ?></small></span>
            </div>
            <?php if (!empty($post['featured_image'])) { ?>
            <img class="so-art__img" src="<?php echo soBlogH($post['featured_image']); ?>" alt="<?php echo soBlogH($featAlt); ?>" width="1200" height="675" fetchpriority="high" decoding="async">
            <?php } ?>
        </div>
    </header>

    <div class="container so-art__wrap so-art__main">
        <?php echo soBlogTocHtml($toc); ?>
        <div class="blog-post-content">
            <?php $soTblN = 0; echo preg_replace_callback('#<div class="so-table-wrap">#', function () use (&$soTblN) { return '<div class="so-table-wrap" tabindex="0" role="region" aria-label="Table ' . (++$soTblN) . ', scrollable">'; }, $articleHtml); ?>
        </div>

        <?php if ($live !== '') { echo soBlogEventsBlock(['performer' => $live, 'limit' => 6]); } ?>

        <section class="so-art__end" aria-label="Next steps">
            <?php echo soBlogCtaBox($endCta); ?>
            <?php echo soBlogNewsletterBox($nlArgs + ['id' => 'subscribe', 'source' => 'blog-end']); ?>
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
                <?php if (!empty($soInGuideCluster)) { ?><p class="so-art__cluster">This guide is part of the <a href="/blog/ticket-buying-guide">Ticket Buying Guide</a>, which links every guide in the series.</p><?php } ?>
                <div class="so-art__more-grid">
                    <?php foreach ($related as $r) { ?>
                        <a href="/blog/<?php echo soBlogH($r['slug']); ?>">
                            <?php if (!empty($r['featured_image'])) { ?><img src="<?php echo soBlogH($r['featured_image']); ?>" alt="<?php echo soBlogH($r['featured_image_alt'] ?? ''); ?>" width="400" height="260" loading="lazy" decoding="async"><?php } ?>
                            <span><?php echo soBlogH($r['title']); ?></span>
                        </a>
                    <?php } ?>
                </div>
            </aside>
        <?php } ?>
        <p class="so-art__top"><a href="#top" onclick="window.scrollTo({top:0,behavior:'smooth'});return false;">Back to top <span aria-hidden="true">&uarr;</span></a></p>
    </div>
</article>

<?php include __DIR__ . '/../footer.php'; ?>
