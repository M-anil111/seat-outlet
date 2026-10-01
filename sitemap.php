<?php
// Dynamic XML sitemap for Seat Outlet's own pages.
//
// Scope decisions (so a future reader doesn't have to reverse-engineer why
// something is/isn't here):
//
// - INCLUDED: Seat Outlet's own static/marketing pages, its category index
//   pages, and every currently-listed, upcoming, US event (live from the
//   TicketNetwork API - no fabricated URLs).
// - INCLUDED: the "network partner" pages (Grab Tickets Now, Ticket Deals,
//   Hunt Tickets, Ticket Scanner, Dotbooker, WingCMS, Salespeep, Signs N
//   More Inc, IT Sprinkles, Austin Sign Masters, Viralpep, Mindshare
//   Consulting) and /our-network, the hub page linking all of them - these
//   are real businesses, deliberately networked together, not orphaned
//   demo clutter.
// - EXCLUDED: admin/, ajax/, cache/, search.php, category.php, performer.php,
//   city.php, venue.php - each of these needs a specific
//   ID/query param and has no safe "list all valid IDs" source in this repo
//   to enumerate from.
// - EXCLUDED: the artist-city/-state/-country/-venue and
//   concerts-city/sports-state/etc. combinator pages (24 files, see
//   functions.php's renderArtistLocationPage()/renderCategoryLocationPage()).
//   At this data scale (performer x location, potentially hundreds of
//   thousands of combinations), enumerating every valid pair in a static
//   sitemap is the wrong tool - Google discovers and indexes this kind of
//   long-tail programmatic page through internal link discovery (from
//   performer pages, event pages, and city/venue hubs), which is exactly
//   what this repo's own SEO documentation calls for ("Internal link
//   density optimized"). Once those internal links are wired up (tracked
//   separately - performer.php/event.php currently don't link out to these
//   pages yet), that's the correct discovery path for them, not this file.
//
// Every URL uses HOME_URL, so this automatically points at production once
// that environment variable is switched over from the beta subdomain.

require_once 'functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
// Built XML is cached for an hour: the page fetches ~10 event pages and 60
// city lookups, which crawlers should not trigger on every request.
$cachedSitemap = cache_get('sitemap_xml', 3600);
if ($cachedSitemap !== false && !empty($cachedSitemap['xml'])) {
    echo $cachedSitemap['xml'];
    exit;
}

$staticPaths = [
    '/',
    '/about-seat-outlet',
    '/ticket-partner-program',
    '/how-to-buy-tickets-online',
    '/ticket-buyer-protection',
    '/worry-free-guarantee',
    '/customer-testimonials',
    '/seat-outlet-reviews',
    '/seat-outlet-bbb',
    '/why-are-concert-tickets-so-expensive',
    '/ticket-faq',
    '/ticket-customer-service',
    '/city-events',
    '/buy-tickets-online',
    '/all-artists-and-teams',
    '/blog',
    '/concert-tickets-for-sale',
    '/game-day-tickets',
    '/buy-broadway-tickets',
    '/upcoming-music-festivals',
    '/tickets-promo-code',
    '/ticket-deals',
    '/hunt-tickets',
    '/ticket-scanner',
    '/grab-tickets-now',
    '/our-network',
    '/dotbooker',
    '/wingcms',
    '/salespeep',
    '/signs-n-more',
    '/it-sprinkles',
    '/austin-sign-masters',
    '/viralpep',
    '/mindshare-consulting',
    '/terms-and-conditions',
    '/privacy-policy',
    '/cookie-policy',
];

$urls = [];
foreach ($staticPaths as $path) {
    $urls[] = [
        'loc' => HOME_URL . $path,
        'changefreq' => $path === '/' ? 'daily' : 'weekly',
        'priority' => $path === '/' ? '1.0' : '0.6',
    ];
}

// Published blog posts - real rows from blog_posts, not a static list.
foreach (listBlogPosts() as $post) {
    if (($post['status'] ?? '') !== 'published' || empty($post['published_at']) || strtotime($post['published_at']) > time()) {
        continue;
    }
    $urls[] = [
        'loc' => HOME_URL . '/blog/' . $post['slug'],
        'changefreq' => 'monthly',
        'priority' => '0.5',
        'lastmod' => date('Y-m-d', strtotime($post['updated_at'] ?? $post['published_at'])),
    ];
}

// Top US cities by sales (same source as cities.php) and their category
// pages. The per-city category pages are the only combinator pages listed:
// a top city has concerts, sports and theater; deeper combinations
// (performer x location, venue x category) stay link-only.
foreach (getTopCities(60) as $index => $city) {
    $citySlug = createSlug($city['label'], $city['id']);
    $urls[] = ['loc' => HOME_URL . '/city/' . $citySlug, 'changefreq' => 'daily', 'priority' => '0.7'];
    $urls[] = ['loc' => HOME_URL . '/event-city/' . $citySlug, 'changefreq' => 'daily', 'priority' => '0.6'];
    if ($index < 30) {
        foreach (['concerts-city', 'sports-city', 'theater-city'] as $prefix) {
            $urls[] = ['loc' => HOME_URL . '/' . $prefix . '/' . $citySlug, 'changefreq' => 'daily', 'priority' => '0.6'];
        }
    }
}

// Live, upcoming, US events with inventory, best sellers first, so the
// capped list (10 pages x 200) is the events most worth crawling. Page 1
// reports the total; the remaining pages are fetched in parallel.
const SITEMAP_MAX_EVENT_PAGES = 10;
const SITEMAP_EVENTS_PER_PAGE = 200;

$today = date('Y-m-d');
$eventPageSpec = function ($page) use ($today) {
    return ['/catalog/v2/events/', [
        'filter' => "date/date ge $today and _metadata/hasTickets eq true and country/alphaCode eq 'US'",
        'sort' => '-salesRank',
        'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}',
        'perPage' => SITEMAP_EVENTS_PER_PAGE,
        'page' => $page,
        'includeTotalCount' => 'true',
    ]];
};
$first = tnRequest(...$eventPageSpec(1));
$totalPages = min(SITEMAP_MAX_EVENT_PAGES, (int) ceil(($first['totalCount'] ?? 0) / SITEMAP_EVENTS_PER_PAGE));
$eventPages = [$first];
if ($totalPages > 1) {
    $rest = [];
    for ($page = 2; $page <= $totalPages; $page++) { $rest[] = $eventPageSpec($page); }
    $eventPages = array_merge($eventPages, tnRequestMulti($rest));
}
foreach ($eventPages as $response) {
    foreach ($response['results'] ?? [] as $event) {
        $slug = createSlug($event['text']['name'] ?? '', $event['id'] ?? 0);
        $urls[] = [
            'loc' => HOME_URL . '/event/' . $slug,
            'changefreq' => 'daily',
            'priority' => '0.8',
            // no <lastmod>: the event date is not a modification date, and a wrong lastmod teaches crawlers to ignore the field
        ];
    }
}

// Evergreen pages with the most search value: performers and venues that currently have tickets on sale
// (the names cron/build-search-vocab.php already ranks by sales, so this costs no API call) and the
// category pages linked from the homepage. Capped to keep the file small; events above are the long tail.
require_once __DIR__ . '/inc/smart.php';
$seenLoc = [];
$perfCount = 0;
$venueCount = 0;
foreach (smartVocab() as $item) {
    if ($item['t'] === 'performer' && $perfCount < 1000) {
        $perfCount++;
        $urls[] = ['loc' => HOME_URL . $item['u'], 'changefreq' => 'daily', 'priority' => '0.7'];
    } elseif ($item['t'] === 'venue' && $venueCount < 300) {
        $venueCount++;
        $urls[] = ['loc' => HOME_URL . $item['u'], 'changefreq' => 'daily', 'priority' => '0.6'];
    }
}
foreach ((cache_get('top_categories', 30 * 86400) ?: []) as $bucket) {
    foreach ((array) $bucket as $cat) {
        if (!empty($cat['slug'])) {
            $urls[] = ['loc' => HOME_URL . '/category/' . $cat['slug'], 'changefreq' => 'daily', 'priority' => '0.7'];
        }
    }
}

ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($url['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    if (!empty($url['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</lastmod>\n";
    }
    echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $url['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
$xml = ob_get_clean();
if (!empty($urls)) { cache_set('sitemap_xml', ['xml' => $xml, 'built' => time()]); }
echo $xml;
