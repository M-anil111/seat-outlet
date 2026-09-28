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

<section>
    <div class="container py-5" style="max-width: 800px;">
        <nav class="mb-3">
            <a href="/blog">&larr; Back to Blog</a>
        </nav>
        <h1><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="text-muted mb-4">
            <?php if (!empty($post['author_name'])) { ?>
                By <?php echo htmlspecialchars($post['author_name'], ENT_QUOTES, 'UTF-8'); ?>
                <?php echo !empty($post['published_at']) ? ' &middot; ' : ''; ?>
            <?php } ?>
            <?php if (!empty($post['published_at'])) { ?>
                <?php echo htmlspecialchars(date('F j, Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
        </div>
        <?php if (!empty($post['featured_image'])) { ?>
            <img src="<?php echo htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded mb-4">
        <?php } ?>
        <div class="blog-post-content">
            <?php echo $post['content']; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
