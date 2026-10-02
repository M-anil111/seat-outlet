<?php
/**
 * Dynamic robots.txt: the Sitemap line follows the host the request arrived on
 * (robots.txt itself hard-coded the beta domain), and non-production hosts
 * (beta./staging./dev./localhost, see SITE_INDEXABLE) are blocked outright.
 *
 * Needs one server rule to be served at /robots.txt (see docs/server-rewrites.md);
 * robots.txt stays in place as the fallback.
 */
// inc/constants.php insists on the server's secrets, which db/config.php normally loads for every
// page; this file never touches the database, so load them here (same two places db/config.php looks).
foreach ([__DIR__ . '/inc/env.local.php', __DIR__ . '/../inc/env.local.php'] as $soEnvFile) {
    if (is_file($soEnvFile)) { require_once $soEnvFile; break; }
}
require_once __DIR__ . '/inc/constants.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

echo "# Seat Outlet\n#\n# Per-page <meta name=\"robots\"> in header.php decides indexing; admin page_rules can override any URL.\n\n";
echo "User-agent: *\n";
if (!SITE_INDEXABLE) {
    echo "Disallow: /\n";
    exit;
}
foreach (['/admin/', '/ajax/', '/cache/', '/vendor/', '/db/', '/tools/', '/cron/', '/deploy/', '/docs/', '/inc/', '/search', '/checkout', '/newsletter', '/unsubscribe', '/thank-you', '/order-confirmation'] as $path) {
    echo "Disallow: $path\n";
}
// Always the host this install is configured for (HOME_URL), never a hard-coded domain.
echo "\nSitemap: " . rtrim(HOME_URL, '/') . "/sitemap.php\n";
