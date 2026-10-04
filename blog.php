<?php
require_once 'functions.php';

$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;

// Category filter: /blog?category=city-guides. Unknown values are ignored so the page never 404s or shows an empty list.
$categories  = listBlogCategories();
$activeCat   = null;
$catIn       = isset($_GET['category']) ? sanitize_title((string) $_GET['category']) : '';
foreach ($categories as $c) {
    if ($c['slug'] === $catIn) { $activeCat = $c; break; }
}
$catQuery = $activeCat ? 'category=' . $activeCat['slug'] : '';

$posts       = listPublishedBlogPosts($page, $perPage, $activeCat['name'] ?? null);
$total_count = countPublishedBlogPosts($activeCat['name'] ?? null);
$total_pages = $total_count > 0 ? (int) ceil($total_count / $perPage) : 0;

$blogUrl = function (int $p = 1) use ($catQuery) {
    $q = array_filter([$catQuery, $p > 1 ? 'page=' . $p : '']);
    return '/blog' . ($q ? '?' . implode('&', $q) : '');
};

// A page past the last one is not a page: send the visitor (and search engines) to the last real page, or to /blog when
// there is nothing to show. A made-up ?page=99 must not become an indexable thin page.
if ($page > 1 && $page > max(1, $total_pages)) {
    header('Location: ' . $blogUrl(max(1, $total_pages)), true, 301);
    exit;
}

// --- SEO: computed before including header.php, same convention used
// throughout this app - see functions.php. ---
$baseTitle           = $activeCat ? $activeCat['name'] . ' Guides' : 'Ticket Buying Tips & Event Guides';
$soKeepOwnMeta       = $activeCat || $page > 1;   // the plain /blog page takes its title from the keyword plan
$pageMetaTitle       = soTitle($baseTitle . " Ticket Buying Tips" . ($page > 1 ? ", Page $page" : ''), $baseTitle . ($page > 1 ? ", Page $page" : ''));
$pageMetaDescription = $activeCat
    ? soMetaFit($activeCat['name'] . ' guides from the Seat Outlet blog: practical ticket buying tips, venue advice and event picks written for fans.', 'Learn how to compare seats and prices and buy with confidence.')
    : soMetaFit('Ticket buying tips, event guides and city guides from Seat Outlet. Learn how to find better seats, compare prices and buy tickets with confidence.');
$pageCanonicalUrl    = HOME_URL . $blogUrl($page);
$pageJsonLdNodes = array_values(array_filter([
    buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL]], 'Blog'),
]));

include 'header.php';

$soReadMins = function (array $p) { return max(1, (int) ceil(str_word_count(strip_tags((string) ($p['content'] ?? ''))) / 220)); };
$soCardImg = function (array $p, int $w, int $h) {
    return !empty($p['featured_image'])
        ? '<img src="' . htmlspecialchars($p['featured_image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars((string) ($p['featured_image_alt'] ?? ''), ENT_QUOTES, 'UTF-8') . '" width="' . $w . '" height="' . $h . '" loading="lazy" decoding="async">'
        : '<span class="so-np__ph" aria-hidden="true"></span>';
};
// Category label, headline, summary and author line. No posting dates are shown.
$soCardText = function (array $p) use ($soReadMins) {
    $author = trim((string) ($p['author_name'] ?? '')) ?: 'Jay Mehta';
    $parts = preg_split('/\s+/', $author);
    $initials = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ''));
    $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    $html  = !empty($p['category']) ? '<span class="so-np__cat">' . $h($p['category']) . '</span>' : '';
    $html .= '<h2>' . $h($p['title']) . '</h2>';
    if (!empty($p['excerpt'])) $html .= '<span class="so-np__ex">' . $h($p['excerpt']) . '</span>';
    $html .= '<span class="so-np__by"><span class="so-np__av" aria-hidden="true">' . $h($initials) . '</span><span class="so-np__who"><strong>' . $h($author) . '</strong><small>' . $soReadMins($p) . ' min read</small></span></span>';
    return $html;
};
$featured = ($page === 1 && !empty($posts)) ? array_shift($posts) : null;
?>
<section class="so-np">
    <div class="container">
        <header class="so-np__head">
            <h1>Ticket Buying Tips &amp; Event Guides</h1>
            <p class="so-np__sub">Plain-English guides to buying tickets safely, planning your night out and finding the right seats.</p>
            <?php if ($categories) { ?>
            <form class="so-np__pick" method="get" action="/blog">
                <label class="visually-hidden" for="soBlogCat">Explore by category</label>
                <select id="soBlogCat" name="category" onchange="this.form.submit()">
                    <option value="">Explore by category</option>
                    <?php foreach ($categories as $c) { ?>
                    <option value="<?php echo htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $activeCat && $activeCat['slug'] === $c['slug'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $c['count']; ?>)</option>
                    <?php } ?>
                </select>
                <noscript><button type="submit">Go</button></noscript>
            </form>
            <div class="so-np__pills">
                <span class="so-np__pillslabel">Popular categories:</span>
                <ul>
                    <?php foreach ($categories as $c) { ?>
                    <li><a href="/blog?category=<?php echo htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $activeCat && $activeCat['slug'] === $c['slug'] ? ' aria-current="true"' : ''; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <?php } ?>
                    <?php if ($activeCat) { ?><li><a class="so-np__clear" href="/blog">All guides</a></li><?php } ?>
                </ul>
            </div>
            <?php } ?>
        </header>

        <?php if ($featured) { ?>
        <a class="so-np__feature" href="/blog/<?php echo htmlspecialchars($featured['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="so-np__media"><?php echo $soCardImg($featured, 1200, 630); ?></span>
            <span class="so-np__fbody"><?php echo $soCardText($featured); ?></span>
        </a>
        <?php } ?>

        <?php if (!empty($posts)) { ?>
            <div class="so-np__grid">
                <?php foreach ($posts as $post) { ?>
                <a class="so-np__card" href="/blog/<?php echo htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="so-np__media"><?php echo $soCardImg($post, 600, 400); ?></span>
                    <?php echo $soCardText($post); ?>
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

        <?php echo soLeadForm(['source' => 'blog-index', 'class' => 'so-nl--blog', 'id' => 'subscribe']); ?>

        <?php if ($total_pages > 1) { ?>
            <nav class="so-np__pager" aria-label="Blog pagination">
                <?php for ($p = 1; $p <= $total_pages; $p++) { ?>
                    <a href="<?php echo htmlspecialchars($blogUrl($p), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $p === $page ? ' aria-current="page"' : ''; ?>><?php echo $p; ?></a>
                <?php } ?>
            </nav>
        <?php } ?>
    </div>
</section>

<?php include 'footer.php'; ?>
