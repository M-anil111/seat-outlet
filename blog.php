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

<?php
$soReadMins = function (array $p) { return max(1, (int) ceil(str_word_count(strip_tags((string) ($p['content'] ?? ''))) / 220)); };
$soCardImg = function (array $p, int $w, int $h) {
    return !empty($p['featured_image'])
        ? '<img src="' . htmlspecialchars($p['featured_image'], ENT_QUOTES, 'UTF-8') . '" alt="" width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async">'
        : '<span class="so-np__ph" aria-hidden="true"></span>';
};
$soMeta = function (array $p) use ($soReadMins) {
    $d = !empty($p['published_at']) ? date('F j, Y', strtotime($p['published_at'])) : '';
    return htmlspecialchars(trim($d . ($d !== '' ? ' · ' : '') . $soReadMins($p) . ' min read'), ENT_QUOTES, 'UTF-8');
};
$featured = ($page === 1 && !empty($posts)) ? array_shift($posts) : null;
?>
<section class="so-np">
    <div class="container">
        <header class="so-np__head">
            <p class="so-np__eyebrow">Seat Outlet Guides</p>
            <h1>Ticket Buying Tips &amp; Event Guides</h1>
            <p class="so-np__sub">Plain-English guides to buying tickets safely, planning your night out and finding the right seats.</p>
        </header>

        <?php if ($featured) { ?>
        <a class="so-np__feature" href="/blog/<?php echo htmlspecialchars($featured['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="so-np__media"><?php echo $soCardImg($featured, 1200, 630); ?></span>
            <span class="so-np__fbody">
                <span class="so-np__meta"><?php echo $soMeta($featured); ?></span>
                <h2><?php echo htmlspecialchars($featured['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <?php if (!empty($featured['excerpt'])) { ?><span class="so-np__ex"><?php echo htmlspecialchars($featured['excerpt'], ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
                <span class="so-np__more">Read the guide</span>
            </span>
        </a>
        <?php } ?>

        <?php if (!empty($posts)) { ?>
            <div class="so-np__grid">
                <?php foreach ($posts as $post) { ?>
                <a class="so-np__card" href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="so-np__media"><?php echo $soCardImg($post, 600, 400); ?></span>
                    <span class="so-np__meta"><?php echo $soMeta($post); ?></span>
                    <h2><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if (!empty($post['excerpt'])) { ?><span class="so-np__ex"><?php echo htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8'); ?></span><?php } ?>
                </a>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!$featured && empty($posts)) { ?>
            <div class="so-np__empty">
                <h2>New guides are on the way</h2>
                <p>In the meantime, <a href="/buy-tickets-online">browse upcoming events</a> or read how our <a href="/worry-free-guarantee">100% guarantee</a> works.</p>
            </div>
        <?php } ?>

        <?php if ($total_pages > 1) { ?>
            <nav class="so-np__pager" aria-label="Blog pagination">
                <?php for ($p = 1; $p <= $total_pages; $p++) { ?>
                    <a href="/blog<?php echo $p > 1 ? '?page=' . $p : ''; ?>"<?php echo $p === $page ? ' aria-current="page"' : ''; ?>><?php echo $p; ?></a>
                <?php } ?>
            </nav>
        <?php } ?>
    </div>
</section>

<?php include 'footer.php'; ?>
