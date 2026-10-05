<?php
// Prints every holiday page URL from the built Holiday Events sitemap (sitemaps/holiday-events-N.xml) and a count per holiday.
// Run on the server after `php cron/build-sitemaps.php --force`:   php tools/list-holiday-urls.php [--urls]
// Without --urls only the counts are printed.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$files = glob(dirname(__DIR__) . '/sitemaps/holiday-events-*.xml') ?: [];
if (!$files) { fwrite(STDERR, "No sitemaps/holiday-events-*.xml found. Run: php cron/build-sitemaps.php --force\n"); exit(1); }
$byHoliday = []; $all = [];
foreach ($files as $f) {
    if (!preg_match_all('#<loc>([^<]+)</loc>#', (string) file_get_contents($f), $m)) continue;
    foreach ($m[1] as $url) {
        $all[] = $url;
        $path = (string) parse_url($url, PHP_URL_PATH);
        $h = preg_match('#^/(.+)-in-[a-z0-9-]+$#', $path, $mm) ? $mm[1] : 'other';
        $byHoliday[$h] = ($byHoliday[$h] ?? 0) + 1;
    }
}
ksort($byHoliday);
foreach ($byHoliday as $h => $n) printf("%6d  %s\n", $n, $h);
printf("%6d  TOTAL in %d file(s)\n", count($all), count($files));
if (in_array('--urls', $argv, true)) { echo "\n" . implode("\n", $all) . "\n"; }
