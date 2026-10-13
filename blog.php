<?php
// The one blog entry point: /blog is the list, /blog/<slug> is an article. The web server rule sends /blog/<slug> here with
// ?slug=<slug> (see docs/server-rewrites.md); /blog.php/<slug> works as well, and so does the original path in REQUEST_URI.
define('SO_BLOG_ENTRY', true);
require_once __DIR__ . '/functions.php';
$soBlogSlug = (string) ($_GET['slug'] ?? '');
if ($soBlogSlug === '' && !empty($_SERVER['PATH_INFO']) && preg_match('#^/blog\.php/#', (string) ($_SERVER['REQUEST_URI'] ?? ''))) { $soBlogSlug = trim((string) $_SERVER['PATH_INFO'], '/'); }
if ($soBlogSlug === '' && preg_match('#^/blog/([a-z0-9-]+)/?(?:\?|$)#i', (string) ($_SERVER['REQUEST_URI'] ?? ''), $soBlogM)) { $soBlogSlug = $soBlogM[1]; }
if ($soBlogSlug !== '') {
    $_GET['slug'] = $soBlogSlug;
    require __DIR__ . '/inc/blog-post-view.php';
    return;
}
require_once __DIR__ . '/inc/blog-render.php';   // soBlogNewsletterBox: the same alerts box as the articles

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
$baseTitle           = $activeCat ? $activeCat['name'] . (substr($activeCat['name'], -6) === 'Guides' ? '' : ' Guides') : 'Ticket Buying Tips & Event Guides';
$soKeepOwnMeta       = $activeCat || $page > 1;   // the plain /blog page takes its title from the keyword plan
$pageMetaTitle       = soTitle($baseTitle . " Ticket Buying Tips" . ($page > 1 ? ", Page $page" : ''), $baseTitle . ($page > 1 ? ", Page $page" : ''));
$pageMetaDescription = $activeCat
    ? soMetaFit($activeCat['name'] . ' guides from the Seat Outlet blog: practical ticket buying tips, venue advice and event picks written for fans.', 'Learn how to compare seats and prices and buy with confidence.')
    : soMetaFit('Ticket buying tips, event guides and city guides from Seat Outlet. Learn how to find better seats, compare prices and buy tickets with confidence.');
$pageCanonicalUrl    = HOME_URL . $blogUrl($page);
$pageJsonLdNodes = array_values(array_filter([
    buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL]], 'Blog'),
]));

if ($page === 1 && !empty($posts) && !empty($posts[0]['featured_image'])) { $pagePreloadImage = $posts[0]['featured_image']; }   // the largest thing on screen: found early, not lazy
include 'header.php';

$soReadMins = function (array $p) { return max(1, (int) ceil(str_word_count(strip_tags((string) ($p['content'] ?? ''))) / 220)); };
$soCardImg = function (array $p, int $w, int $h, bool $first = false) {
    return !empty($p['featured_image'])
        ? '<img src="' . htmlspecialchars($p['featured_image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars((string) ($p['featured_image_alt'] ?? ''), ENT_QUOTES, 'UTF-8') . '" width="' . $w . '" height="' . $h . '" ' . ($first ? 'fetchpriority="high" decoding="async"' : 'loading="lazy" decoding="async"') . '>'
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
// Pill icon by category name (inline SVG, 20px, stroke follows the text colour): the first matching pattern wins, so the
// specific ones (city, safety, venues) come before the generic ticket/guide one. Unknown names get the grid icon.
$soCatIcon = function (string $name) {
    $n = strtolower($name);
    $paths = [
        'concert|tour|music|festiv' => '<path d="M9 18V5.5l11-2V16"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="17.5" cy="16" r="2.5"/>',
        'city|cities|travel|near'   => '<path d="M12 21s-6.5-6.2-6.5-11.2a6.5 6.5 0 0 1 13 0C18.5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.8" r="2.3"/>',
        'safe|scam|secur|protect'   => '<path d="M12 3 5 5.8v5.4c0 4.4 3 8.1 7 9.8 4-1.7 7-5.4 7-9.8V5.8L12 3Z"/>',
        'venue|stadium|arena'       => '<ellipse cx="12" cy="8" rx="8.5" ry="3.5"/><path d="M3.5 8v7.5c0 1.9 3.8 3.5 8.5 3.5s8.5-1.6 8.5-3.5V8M8 11.2v7.4M16 11.2v7.4M12 11.5v7.5"/>',
        'sport|game'                => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4ZM8 6H5v1.5A3 3 0 0 0 8 10.5M16 6h3v1.5a3 3 0 0 1-3 3M12 13v4M8.5 20h7"/>',
        'theat|broadway|comedy'     => '<path d="M4 4h10v6a5 5 0 0 1-10 0V4Z"/><path d="M14 8h6v6a5 5 0 0 1-8.6 3.5M7 8h.01M11 8h.01M7.5 12.5a2.5 2.5 0 0 0 3 0"/>',
        'ticket|guide|buy'          => '<path d="M3.5 8.5V6.5a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v2a2.5 2.5 0 0 0 0 5v2a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1v-2a2.5 2.5 0 0 0 0-5Z"/><path d="M14.5 5.5v13" stroke-dasharray="1.6 2.2"/>',
    ];
    $d = '<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/>';
    foreach ($paths as $re => $p) { if ($n !== '' && preg_match('/' . $re . '/', $n)) { $d = $p; break; } }
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
};
$featured = ($page === 1 && !empty($posts)) ? array_shift($posts) : null;
?>
<style>
/* Blog hero: full-width pale blue banner, copy on the left, ticket/guide artwork on the right. Keeps .so-np__head so the
   banner ad still follows the hero; the .so-bh rules override the older centred .so-np__head styles. */
.so-np__head.so-bh { --bh-navy: #0b1437; --bh-blue: #2556e0; position: relative; overflow: hidden; max-width: none; margin: 0; padding: clamp(40px, 6vw, 88px) 0; text-align: left; background: linear-gradient(100deg, #f8fafe 0%, #f1f5fe 48%, #ecf2fd 100%); }
.so-np__head.so-bh::before { content: ""; position: absolute; left: -160px; bottom: -220px; width: 520px; height: 520px; border-radius: 50%; background: radial-gradient(circle, rgba(37,86,224,.07), transparent 70%); pointer-events: none; }
.so-bh .so-bh__in { position: relative; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); align-items: center; column-gap: clamp(16px, 2vw, 32px); }
.so-bh .so-bh__copy { grid-column: 1; grid-row: 1; min-width: 0; position: relative; z-index: 1; }
/* the pill row runs under the artwork, as in the design; the artwork spans both rows behind it */
.so-np__head.so-bh .so-np__pills { grid-column: 1 / -1; grid-row: 2; position: relative; z-index: 1; max-width: 1040px; }
.so-np__head.so-bh h1 { margin: 0; max-width: 12em; font-size: clamp(36px, 4.7vw, 68px); font-weight: 800; letter-spacing: -.035em; line-height: 1.06; color: var(--bh-navy); text-wrap: balance; }
.so-np__head.so-bh .so-np__sub { margin: 20px 0 0; max-width: 560px; font-size: clamp(17px, 1.55vw, 22px); font-weight: 400; line-height: 1.5; color: #687087; }
.so-np__head.so-bh .so-np__pick { position: relative; max-width: 720px; margin: 32px 0 0; }
.so-bh .so-np__pick .so-bh__grid { position: absolute; left: 24px; top: 50%; transform: translateY(-50%); display: flex; color: var(--bh-blue); pointer-events: none; }
.so-np__head.so-bh .so-np__pick select { height: 64px; padding: 0 60px 0 66px; border: 1px solid #e6ecf8; border-radius: 20px; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='11' viewBox='0 0 18 11'%3E%3Cpath d='M2 2l7 7 7-7' fill='none' stroke='%232556e0' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 26px center; box-shadow: 0 10px 30px rgba(37,86,224,.08); color: var(--bh-navy); font-size: 18px; font-weight: 500; cursor: pointer; -webkit-appearance: none; appearance: none; }
.so-np__head.so-bh .so-np__pick select:focus-visible { outline: 3px solid var(--bh-blue); outline-offset: 2px; }
.so-np__head.so-bh .so-np__pillslabel { margin: 30px 0 14px; font-size: 18px; font-weight: 700; color: var(--bh-navy); }
.so-np__head.so-bh .so-np__pills ul { justify-content: flex-start; gap: 12px; }
.so-np__head.so-bh .so-np__pills a { display: inline-flex; align-items: center; gap: 10px; min-height: 48px; padding: 0 22px; border: 1.5px solid #9fb6ee; border-radius: 999px; background: #fff; box-shadow: none; color: var(--bh-navy); font-size: 16px; font-weight: 600; text-decoration: none; transition: border-color .15s, background-color .15s, color .15s, transform .15s; }
.so-np__head.so-bh .so-np__pills a svg { flex: none; color: var(--bh-blue); }
.so-np__head.so-bh .so-np__pills a:hover { transform: translateY(-1px); border-color: var(--bh-blue); color: var(--bh-blue); box-shadow: none; }
.so-np__head.so-bh .so-np__pills a.is-on { border-color: var(--bh-blue); background: var(--bh-blue); color: #fff; }
.so-np__head.so-bh .so-np__pills a.is-on svg { color: #fff; }
.so-np__head.so-bh .so-np__pills a.is-on:hover { background: #1a3fa8; border-color: #1a3fa8; color: #fff; }
.so-np__head.so-bh .so-np__pills a:focus-visible { outline: 3px solid var(--bh-blue); outline-offset: 2px; }
.so-bh .so-bh__art { grid-column: 2; grid-row: 1 / span 2; align-self: center; position: relative; margin: -40px -72px -40px -24px; }
.so-bh .so-bh__art img { display: block; width: 100%; height: auto; -webkit-mask-image: radial-gradient(ellipse 66% 64% at 54% 52%, #000 55%, transparent 97%); mask-image: radial-gradient(ellipse 66% 64% at 54% 52%, #000 55%, transparent 97%); }
.so-np { padding-top: clamp(32px, 5vw, 64px); }
@media (max-width: 991.98px) {
  .so-bh .so-bh__in { grid-template-columns: minmax(0, 1fr); }
  .so-bh .so-bh__art { grid-column: 1; grid-row: 1; width: min(400px, 80%); margin: -12px auto 0; }
  .so-bh .so-bh__copy { grid-row: 2; }
  .so-np__head.so-bh .so-np__pills { grid-row: 3; }
  .so-np__head.so-bh h1 { max-width: none; }
}
@media (max-width: 575.98px) {
  .so-np__head.so-bh { padding: 28px 0 36px; }
  .so-bh .so-bh__art { width: min(270px, 76%); margin: -8px auto -4px; }
  .so-np__head.so-bh .so-np__sub { margin-top: 14px; }
  .so-np__head.so-bh .so-np__pick { margin-top: 24px; }
  .so-np__head.so-bh .so-np__pick select { height: 58px; border-radius: 16px; font-size: 16px; padding-left: 58px; }
  .so-bh .so-np__pick .so-bh__grid { left: 20px; }
  .so-np__head.so-bh .so-np__pillslabel { margin: 24px 0 12px; font-size: 16px; }
  .so-np__head.so-bh .so-np__pills ul { gap: 10px; }
  .so-np__head.so-bh .so-np__pills a { min-height: 44px; padding: 0 16px; gap: 8px; font-size: 15px; }
}
</style>
<header class="so-np__head so-bh">
    <div class="container so-bh__in">
        <div class="so-bh__copy">
            <h1><?php echo htmlspecialchars($activeCat ? $baseTitle : 'Ticket Buying Tips & Event Guides', ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="so-np__sub">Plain-English guides to buying tickets safely, planning your night out and finding the right seats.</p>
            <?php if ($categories) { ?>
            <form class="so-np__pick" method="get" action="/blog">
                <span class="so-bh__grid" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" focusable="false"><rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/></svg></span>
                <label class="visually-hidden" for="soBlogCat">Explore by category</label>
                <select id="soBlogCat" name="category" onchange="this.form.submit()">
                    <option value="">Explore by category</option>
                    <?php foreach ($categories as $c) { ?>
                    <option value="<?php echo htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $activeCat && $activeCat['slug'] === $c['slug'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $c['count']; ?>)</option>
                    <?php } ?>
                </select>
                <noscript><button type="submit">Go</button></noscript>
            </form>
            <?php } ?>
        </div>
        <?php if ($categories) { ?>
        <div class="so-np__pills">
            <span class="so-np__pillslabel">Popular categories:</span>
            <ul>
                <?php foreach ($categories as $i => $c) { $on = $activeCat ? $activeCat['slug'] === $c['slug'] : $i === 0; ?>
                <li><a<?php echo $on ? ' class="is-on"' : ''; ?> href="/blog?category=<?php echo htmlspecialchars($c['slug'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $activeCat && $activeCat['slug'] === $c['slug'] ? ' aria-current="true"' : ''; ?>><?php echo $soCatIcon($c['name']); ?><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                <?php } ?>
                <?php if ($activeCat) { ?><li><a href="/blog"><?php echo $soCatIcon(''); ?>All guides</a></li><?php } ?>
            </ul>
        </div>
        <?php } ?>
        <div class="so-bh__art"><img src="/images/blog-hero.webp" alt="" width="860" height="660" decoding="async"></div>
    </div>
</header>

<section class="so-np">
    <div class="container">

        <?php if ($featured) { ?>
        <a class="so-np__feature" href="/blog/<?php echo htmlspecialchars($featured['slug'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="so-np__media"><?php echo $soCardImg($featured, 1200, 630, true); ?></span>
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

        <?php echo soBlogNewsletterBox(['source' => 'blog-index', 'id' => 'subscribe']); ?>

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
