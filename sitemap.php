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

$staticPaths = [
    '/',
    '/about-us',
    '/why-us',
    '/what-we-do',
    '/buyer-protection',
    '/guarantee',
    '/testimonials',
    '/reviews',
    '/bbb',
    '/ticketing-truths',
    '/faq',
    '/contact',
    '/cities',
    '/tickets',
    '/blog',
    '/concerts',
    '/sports',
    '/theater',
    '/festival',
    '/deals-promotions',
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

// Live, upcoming, US events - bounded to a handful of pages so this stays
// fast; increase MAX_EVENT_PAGES if the catalog grows well past this.
const SITEMAP_MAX_EVENT_PAGES = 10;
const SITEMAP_EVENTS_PER_PAGE = 200;

$today = date('Y-m-d');
for ($page = 1; $page <= SITEMAP_MAX_EVENT_PAGES; $page++) {
    $response = tnRequest('/catalog/v2/events/', [
        'filter' => "date/date ge $today and country/alphaCode eq 'US'",
        'perPage' => SITEMAP_EVENTS_PER_PAGE,
        'page' => $page,
        'includeTotalCount' => 'true',
    ]);

    $events = $response['results'] ?? [];
    if (empty($events)) {
        break;
    }

    foreach ($events as $event) {
        $slug = createSlug($event['text']['name'] ?? '', $event['id'] ?? 0);
        $eventDate = $event['date']['date'] ?? null;
        $urls[] = [
            'loc' => HOME_URL . '/event/' . $slug,
            'changefreq' => 'daily',
            'priority' => '0.8',
            'lastmod' => $eventDate,
        ];
    }

    $totalCount = $response['totalCount'] ?? 0;
    if ($page * SITEMAP_EVENTS_PER_PAGE >= $totalCount) {
        break;
    }
}

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
