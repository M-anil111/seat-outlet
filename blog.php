<?php
require_once 'functions.php';

$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;

$posts       = listPublishedBlogPosts($page, $perPage);
$total_count = countPublishedBlogPosts();
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;

// --- SEO: computed before including header.php, same convention used
// throughout this app - see functions.php. ---
$pageMetaTitle       = $page > 1 ? "Blog - Page $page | Seat Outlet" : 'Blog | Seat Outlet';
$pageMetaDescription = 'News, guides, and updates from Seat Outlet - buying tips, event spotlights, and ticket marketplace insights.';
$pageCanonicalUrl    = HOME_URL . '/blog' . ($page > 1 ? '?page=' . $page : '');
$pageJsonLdNodes = array_values(array_filter([
    buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL]], 'Blog'),
]));

include 'header.php';
?>

<section class="section-featured-header text-sm-center text-md-start">
    <div class="container-fluid min-vh-50 d-flex align-items-center justify-content-center text-white all-sports-events"
        style="background-image: url('<?php echo HOME_URL; ?>/assets/event-so.webp'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <div class="container mx-xl-5 mx-lg-5 mx-md-3">
            <div class="row">
                <div class="col-12">
                    <h1 class="artist-title">Seat Outlet Blog</h1>
                </div>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="container py-4">
        <?php if (!empty($posts)) { ?>
            <div class="row g-4">
                <?php foreach ($posts as $post) { ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-reset">
                            <div class="card h-100">
                                <?php if (!empty($post['featured_image'])) { ?>
                                    <img src="<?php echo htmlspecialchars($post['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php } ?>
                                <div class="card-body">
                                    <h2 class="h5 card-title"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <?php if (!empty($post['excerpt'])) { ?>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php } ?>
                                    <?php if (!empty($post['published_at'])) { ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars(date('F j, Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php } ?>
                                </div>
                            </div>
                        </a>
                    </div>
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
            <h4 class="text-center py-5">No blog posts yet - check back soon.</h4>
        <?php } ?>
    </div>
</section>

<?php include 'footer.php'; ?>
