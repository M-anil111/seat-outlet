<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Keep a good snapshot of every page that matters, so a TicketNetwork outage or throttling window never shows an error page.
 *
 * Reads the sitemap files (sitemaps/*.xml), and for each URL that has no snapshot or an old one it requests the page from this site,
 * slowly and politely: the request builds the page (which stores the snapshot, see soSnapshotStore() in functions.php) and fills the
 * data cache. It stops early when the API starts failing (the circuit opens, pages answer 503) and tries again on the next run.
 *
 *   php cron/warm-snapshots.php                 one run: up to 400 pages, one per second, oldest or missing first
 *   php cron/warm-snapshots.php --limit=1000    more pages per run
 *   php cron/warm-snapshots.php --delay=2       seconds between requests (default 1)
 *   php cron/warm-snapshots.php --max-age=12    a snapshot younger than this many hours is left alone (default 12)
 *   php cron/warm-snapshots.php --types=pages,cities,venues,performers,events   which sitemap groups (default: pages, cities, holiday-events, venues, performers)
 *   php cron/warm-snapshots.php --status        counts only
 *
 * Schedule: every 30 minutes (see docs/reliability.md). The load is the delay setting, not the number of pages: at the default one
 * request per second it never competes with visitors for TicketNetwork's quota in any meaningful way.
 */
require_once __DIR__ . '/../functions.php';

$opts = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) $opts[$m[1]] = $m[2] ?? '1';
}
$limit  = max(1, (int) ($opts['limit'] ?? 400));
$delay  = max(0.2, (float) ($opts['delay'] ?? 1));
$maxAge = max(1, (float) ($opts['max-age'] ?? 12)) * 3600;
$types  = array_filter(explode(',', (string) ($opts['types'] ?? 'pages,cities,holiday-events,venues,performers')));

$urls = [];
foreach ($types as $t) {
    foreach (glob(__DIR__ . '/../sitemaps/' . preg_replace('/[^a-z-]/', '', $t) . '-*.xml') ?: [] as $file) {
        if (preg_match_all('#<loc>([^<]+)</loc>#', (string) file_get_contents($file), $m)) {
            foreach ($m[1] as $u) $urls[] = html_entity_decode($u, ENT_QUOTES | ENT_XML1);
        }
    }
}
$urls = array_values(array_unique($urls));

$due = [];   // [age or PHP_INT_MAX when missing, url]
$fresh = 0;
foreach ($urls as $u) {
    $path = (string) parse_url($u, PHP_URL_PATH) . (($q = parse_url($u, PHP_URL_QUERY)) ? '?' . $q : '');
    $f = soSnapshotFile($path);
    if ($f === null) continue;
    $age = is_file($f) ? time() - (int) filemtime($f) : PHP_INT_MAX;
    if ($age < $maxAge) { $fresh++; continue; }
    $due[] = [$age, $path];
}
usort($due, fn($a, $b) => $b[0] <=> $a[0]);   // never-built first, then the oldest

echo 'sitemap URLs: ' . count($urls) . ', fresh snapshots: ' . $fresh . ', due: ' . count($due) . "\n";
if (isset($opts['status'])) exit(0);

$lock = fopen(sys_get_temp_dir() . '/seatoutlet_warm_snapshots.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { echo "another run is in progress\n"; exit(0); }

$base = rtrim(HOME_URL, '/');
$done = 0; $fail = 0; $streak = 0; $t0 = microtime(true);
foreach (array_slice($due, 0, $limit) as [$age, $path]) {
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'SeatOutletSnapshotWarmer/1.0', CURLOPT_ENCODING => '',
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200) { $done++; $streak = 0; }
    else {
        $fail++; $streak++;
        if ($code === 503 || $code === 429 || $code === 0) {
            if ($streak >= 3) { echo "stopping: the API looks throttled or down (HTTP $code three times in a row)\n"; break; }
            sleep(20);
        }
    }
    usleep((int) ($delay * 1000000));
    if (microtime(true) - $t0 > 1500) { echo "time box reached\n"; break; }   // leave room for the next run
}
echo "warmed: $done, failed: $fail, seconds: " . round(microtime(true) - $t0) . "\n";
soSnapshotSweep();
