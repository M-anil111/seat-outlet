<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Page types and the stylesheet each one loads.
 *
 * css/style.min.css is the whole site stylesheet (about 220 KB). A page only uses a fraction of it, so the build
 * (tools/build-assets.sh, tools/css-split.php) makes one smaller file per page type, css/style.<group>.min.css, from the
 * templates, the functions they call and the scripts they load. Every file is the full stylesheet minus the rules that
 * page type cannot use, in the same order, so the cascade is unchanged. The first group whose pattern matches wins, so list narrow groups first. A page whose script is not listed here keeps the
 * whole stylesheet.
 *
 *   'templates' root files (fnmatch patterns) that belong to the page type
 *   'js'        scripts the footer loads on those pages (js/<name>.js)
 *   'ajax'      endpoints whose markup is inserted into those pages
 *   'seo_copy'  true: the hand written copy files in inc/seo-copy and the seeded page blocks are part of the page type
 *   'sql'       true: HTML stored in db/migrations and db/seeds (blog posts, page blocks) is part of the page type
 */
const SO_CSS_SHARED_JS = ['main', 'nav-feedback', 'install-prompt', 'menu-near', 'analytics-events', 'consent', 'lead-capture'];
const SO_CSS_SHARED_AJAX = ['get-suggestions', 'keyword-search', 'get-nearby-venues', 'check-email', 'subscribe'];

const SO_CSS_GROUPS = [
    'home' => [
        'templates' => ['index.php', 'home-*.php'],
        'js' => ['home', 'near-you', 'events-listing'],
        'ajax' => ['get-home-feed', 'get-top-performers', 'get-performers', 'get-location-category-events', 'load-events', 'load-more-events'],
    ],
    'event' => [
        'templates' => ['event.php'],
        'js' => ['event-actions', 'saved-events', 'event-widget', 'event-qty', 'idle-nudge', 'events-listing', 'near-you'],
        'ajax' => ['load-events', 'load-more-events'],
    ],
    'entity' => [
        'templates' => ['performer.php', 'venue.php', 'city.php', 'state.php', 'country.php', 'artist-*.php'],
        'js' => ['events-listing', 'near-you', 'performer', 'idle-nudge'],
        'ajax' => ['load-events', 'load-more-events', 'get-location-category-events'],
    ],
    'directory' => [
        'templates' => ['all-artists-and-teams.php', 'concert-artists.php', '*-teams.php', 'broadway-shows.php', 'comedians-on-tour.php', 'music-festivals-list.php', 'artists.php'],
        'js' => [],
        'ajax' => [],
    ],
    'browse' => [
        'templates' => ['category.php', 'concert*.php', 'buy-*.php', 'game-day-tickets.php', '*-tickets.php', '*-city.php', '*-state.php', '*-country.php', '*-venue.php',
            'search.php', 'city-events.php', 'cities.php', 'venues.php', 'performers.php', 'upcoming-music-festivals.php', 'festival*.php', 'christmas-shows-near-me.php',
            'last-minute-tickets.php', 'weekend-events.php', 'cheap-tickets.php', 'best-events.php'],
        'js' => ['events-listing', 'near-you', 'search'],
        'ajax' => ['load-events', 'load-more-events', 'get-location-category-events'],
    ],
    'content' => [
        'templates' => ['about*.php', 'terms*.php', 'privacy*.php', 'cookie-policy.php', 'faq.php', 'ticket-*.php', 'contact*.php', '*reviews.php', 'testimonials.php',
            'customer-testimonials.php', 'guarantee.php', 'worry-free-guarantee.php', 'buyer-protection.php', 'why-*.php', 'what-we-do.php', 'trust.php', 'our-network.php',
            'bbb.php', 'seat-outlet-bbb.php', 'how-to-buy-tickets-online.php', 'tickets-promo-code.php', 'deals-promotions.php', 'image-credits.php', '404.php', 'sitemap.php', 'sitemap-page.php'],
        'js' => [],
        'ajax' => [],
        'seo_copy' => true,
        'sql' => true,
    ],
    'blog' => [
        'templates' => ['blog.php', 'blog-post.php'],
        'js' => [],
        'ajax' => [],
        'sql' => true,
    ],
    'checkout' => [
        'templates' => ['checkout.php', 'order-confirmation.php', 'thank-you.php', 'unsubscribe.php'],
        'js' => [],
        'ajax' => [],
    ],
];

/** The page type of a script (basename such as "category.php"), or null when it keeps the whole stylesheet. */
function soCssGroupFor(string $script): ?string {
    $script = basename($script);
    foreach (SO_CSS_GROUPS as $group => $cfg) {
        foreach ($cfg['templates'] as $pattern) {
            if (fnmatch($pattern, $script)) return $group;
        }
    }
    return null;
}

/** The page type name of a script, "full" when it has none (the name of its critical CSS and stylesheet files). */
function soCssGroupName(?string $script = null): string {
    return soCssGroupFor($script ?? (string) ($_SERVER['SCRIPT_NAME'] ?? '')) ?? 'full';
}

/**
 * The critical CSS of this page type (css/critical/<page type>.css, made by tools/critical-css.cjs): the rules for what is on screen
 * at first paint. header.php prints it in the head and loads the full stylesheets without blocking. '' when there is none.
 */
function soCriticalCss(?string $script = null): string {
    static $memo = [];
    $g = soCssGroupName($script);
    if (getenv('SO_CSS_SPLIT') === '0' || getenv('SO_CRITICAL') === '0') return '';
    if (!isset($memo[$g])) {
        $f = dirname(__DIR__) . '/css/critical/' . $g . '.css';
        $memo[$g] = is_file($f) ? (string) file_get_contents($f) : '';
    }
    return $memo[$g];
}

/**
 * The stylesheet files this page loads, as site relative paths in load order: css/style.<page type>.min.css (or .1, .2 when
 * the build had to split it), "full" for a script that belongs to no page type, and the readable source when the build has not run.
 */
function soCssBundleFiles(?string $script = null): array {
    static $memo = [];
    $script = basename($script ?? (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (isset($memo[$script])) return $memo[$script];
    if (getenv('SO_CSS_SPLIT') === '0') return $memo[$script] = [];   // testing switch: the whole stylesheet, as before the split
    $group = soCssGroupFor($script) ?? 'full';
    $dir = dirname(__DIR__) . '/css/';
    $files = glob($dir . 'style.' . $group . '.[0-9]*.min.css') ?: [];
    if (!$files && is_file($dir . 'style.' . $group . '.min.css')) $files = [$dir . 'style.' . $group . '.min.css'];
    natsort($files);
    $rel = [];
    foreach ($files as $f) $rel[] = 'css/' . basename($f);
    return $memo[$script] = $rel;
}
