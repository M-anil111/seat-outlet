<?php
require_once 'functions.php';

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
?>

<?php
$wordCount = str_word_count(strip_tags((string) $post['content']));
$readMinutes = max(1, (int) round($wordCount / 220));
$related = [];
foreach (listPublishedBlogPosts(1, 6) as $r) {
    if ($r['slug'] !== $post['slug'] && count($related) < 3) { $related[] = $r; }
}
?>
<article class="so-article">
    <header class="so-article__head">
        <div class="container">
            <nav class="so-article__crumb" aria-label="Breadcrumb"><a href="/blog">Blog</a></nav>
            <h1><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php if (!empty($post['excerpt'])) { ?><p class="so-article__lede"><?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
            <p class="so-article__meta">
                <?php if (!empty($post['author_name'])) { ?><span><?php echo htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
                <?php if (!empty($post['published_at'])) { ?><span><?php echo htmlspecialchars(date('F j, Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
                <span><?php echo $readMinutes; ?> min read</span>
            </p>
        </div>
    </header>
    <div class="container so-article__body">
        <div class="blog-post-content">
            <?php echo $post['content']; ?>
        </div>
        <?php if ($related) { ?>
            <aside class="so-article__more" aria-label="More guides">
                <h2>More guides</h2>
                <div class="so-article__more-grid">
                    <?php foreach ($related as $r) { ?>
                        <a href="/blog/<?php echo htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8'); ?>"><span><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></span></a>
                    <?php } ?>
                </div>
            </aside>
        <?php } ?>
    </div>
</article>

<?php include 'footer.php'; ?>
