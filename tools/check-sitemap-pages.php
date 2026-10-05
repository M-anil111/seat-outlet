<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * CI check for the XML sitemap's automatic page list (soSitemapStaticPaths() in inc/sitemap-build.php):
 *   - every page the sitemap has always listed is still found,
 *   - nothing is listed that is a template behind a slug, a redirect, a noindex page or a disallowed path,
 *   - every listed path has a script in the web root (or is the home page).
 * Run: php tools/check-sitemap-pages.php
 */
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/check.php';
require_once __DIR__ . '/../inc/sitemap-build.php';
$root = dirname(__DIR__);
$paths = soSitemapStaticPaths();
$errors = [];

// The pages every earlier release listed by hand.
$mustHave = ['/', '/blog', '/about-seat-outlet', '/ticket-partner-program', '/how-to-buy-tickets-online', '/ticket-buyer-protection', '/worry-free-guarantee',
    '/customer-testimonials', '/seat-outlet-reviews', '/seat-outlet-bbb', '/why-are-concert-tickets-so-expensive', '/ticket-faq', '/ticket-customer-service',
    '/city-events', '/buy-tickets-online', '/all-artists-and-teams', '/concert-tickets-for-sale', '/hip-hop-tickets', '/country-music-tickets', '/pop-rock-concert-tickets',
    '/rnb-soul-concert-tickets', '/latin-music-tickets', '/alternative-concert-tickets', '/metal-concert-tickets', '/jazz-and-blues-tickets', '/electronic-music-tickets',
    '/comedy-show-tickets', '/classical-music-tickets', '/nba-tickets', '/nfl-tickets', '/mlb-tickets', '/nhl-tickets', '/mls-tickets', '/soccer-tickets', '/tennis-tickets',
    '/racing-tickets', '/boxing-tickets', '/las-vegas-shows-tickets', '/christmas-shows-near-me', '/game-day-tickets', '/concert-artists', '/sports-teams', '/nfl-teams',
    '/nba-teams', '/mlb-teams', '/nhl-teams', '/mls-teams', '/broadway-shows', '/comedians-on-tour', '/music-festivals-list', '/buy-broadway-tickets',
    '/upcoming-music-festivals', '/tickets-promo-code', '/ticket-deals', '/hunt-tickets', '/ticket-scanner', '/grab-tickets-now', '/our-network', '/dotbooker', '/wingcms',
    '/salespeep', '/signs-n-more', '/it-sprinkles', '/austin-sign-masters', '/viralpep', '/mindshare-consulting', '/terms-and-conditions', '/privacy-policy', '/cookie-policy'];
foreach ($mustHave as $p) { if (!in_array($p, $paths, true)) $errors[] = "missing from the scan: $p"; }

// Never a page.
$never = ['/robots', '/sitemap', '/sitemap-page', '/search', '/checkout', '/order-confirmation', '/thank-you', '/unsubscribe', '/newsletter-email', '/404', '/header', '/footer', '/functions',
    '/performer', '/venue', '/event', '/city', '/state', '/country', '/category', '/artist-city', '/concerts-city', '/event-city', '/best-events', '/cheap-tickets', '/last-minute-tickets', '/weekend-events'];
foreach ($never as $p) { if (in_array($p, $paths, true)) $errors[] = "listed but not a page: $p"; }

foreach ($paths as $p) {
    if ($p !== '/' && !is_file($root . $p . '.php')) $errors[] = "no script for $p";
    foreach (SO_ROBOTS_DISALLOW as $d) { if ($p !== '/' && strpos($p, rtrim($d, '/')) === 0) $errors[] = "disallowed in robots.txt but listed: $p"; }
}
if ($errors) { fwrite(STDERR, implode("\n", $errors) . "\n"); exit(1); }
echo count($paths) . " pages found by the scan, none missing, none wrong\n";
