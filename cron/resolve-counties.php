<?php
// Places queued US cities in their county (inc/counties.php). Run from cron, for example every 30 minutes:
//   php cron/resolve-counties.php [limit]        (default 150 cities per run, one Census lookup per second)
// The queue is filled by the sitemap build (php cron/build-sitemaps.php), so run that first on a new database.
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../functions.php';
$limit = isset($argv[1]) && ctype_digit($argv[1]) ? (int) $argv[1] : 150;
[$ok, $miss] = soCountyResolvePending($limit);
$left = MYSQLI->query("SELECT COUNT(*) FROM city_counties WHERE status = 'pending'")->fetch_row()[0] ?? 0;
echo "counties: resolved $ok, missed $miss, still pending $left\n";
