<?php
require_once 'functions.php';

$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;

$posts       = listPublishedBlogPosts($page, $perPage);
$total_count = countPublishedBlogPosts();
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;

// --- SEO: computed before including header.php, same convention used
// throughout this app - see functions.php. ---
$pageMetaTitle       = $page > 1 ? "Blog - Page $page | Seat Outlet" : 'Blog: Ticket Buying Tips & Event Guides | Seat Outlet';
$pageMetaDescription = 'News, guides, and updates from Seat Outlet - buying tips, event spotlights, and ticket marketplace insights.';
$pageCanonicalUrl    = HOME_URL . '/blog' . ($page > 1 ? '?page=' . $page : '');
$pageJsonLdNodes = array_values(array_filter([
    buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL]], 'Blog'),
]));

include 'header.php';
?>

<section class="so-blog-hero">
    <div class="container">
        <h1>Ticket Buying Tips &amp; Event Guides</h1>
        <p>Plain-English guides to buying tickets safely, planning your night out and finding the right seats.</p>
    </div>
</section>

<section class="so-blog-list">
    <div class="container">
        <?php if (!empty($posts)) { ?>
            <div class="so-blog-grid">
                <?php foreach ($posts as $post) { ?>
                    <a class="so-blog-card" href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (!empty($post['featured_image'])) { ?>
                            <img src="<?php echo htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="" width="600" height="315" loading="lazy" decoding="async">
                        <?php } ?>
                        <div class="so-blog-card__body">
                            <?php if (!empty($post['published_at'])) { ?><span class="so-blog-card__date"><?php echo htmlspecialchars(date('F j, Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
                            <h2><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <?php if (!empty($post['excerpt'])) { ?><p><?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p><?php } ?>
                            <span class="so-blog-card__more">Read the guide</span>
                        </div>
                    </a>
                <?php } ?>
            </div>

            <?php if ($total_pages > 1) { ?>
                <nav class="mt-5" aria-label="Blog pagination">
                    <ul class="pagination justify-content-center">
                        <?php for ($p = 1; $p <= $total_pages; $p++) { ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="/blog<?php echo $p > 1 ? '?page=' . $p : ''; ?>"><?php echo $p; ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php } ?>
        <?php } else { ?>
            <h2 class="text-center py-5">No blog posts yet. Check back soon.</h2>
        <?php } ?>
    </div>
</section>

<?php include 'footer.php'; ?>
