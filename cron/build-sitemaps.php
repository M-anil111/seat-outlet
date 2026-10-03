<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Build (or refresh) the XML sitemap files from the live catalog. The site also does this by itself in the background after public page
 * requests (inc/sitemap-build.php); this script is for a server cron or for running it by hand.
 *
 *   php cron/build-sitemaps.php            continue or start a crawl and run it to the end (up to 15 minutes)
 *   php cron/build-sitemaps.php --force    start a fresh crawl now, even if the last build is recent
 *   php cron/build-sitemaps.php --status   show where the crawl is
 */
require_once __DIR__ . '/../functions.php';

$opts = getopt('', ['force', 'status', 'help']);
if (isset($opts['help'])) { echo "Usage: php cron/build-sitemaps.php [--force] [--status]\n"; exit(0); }
if (isset($opts['status'])) {
    $s = soSitemapState();
    echo $s ? json_encode($s, JSON_PRETTY_PRINT) . "\n" : "No build yet.\n";
    exit(0);
}
$force = isset($opts['force']);
$end = time() + 900;
while (time() < $end) {
    $status = soSitemapStep(60.0, $force);
    $force = false;
    echo $status . "\n";
    if ($status === 'fresh' || strpos($status, 'built') === 0 || $status === 'busy') break;
    if (strpos($status, 'api paused') !== false) sleep(30);
}
