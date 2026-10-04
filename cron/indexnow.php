<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Tell search engines about newly listed events the minute we see them (IndexNow: Bing, Yandex, Seznam, Naver and others).
 *
 *   php cron/indexnow.php --dry-run     list what would be sent
 *   php cron/indexnow.php               send (at most 500 URLs per run)
 *
 * Needs SO_INDEXNOW_KEY (8 to 128 letters, digits or dashes; make one with `php -r "echo bin2hex(random_bytes(16));"`).
 * The key file <docroot>/<key>.txt is written on the first run, as the protocol requires. Only runs on the indexable (production)
 * host. Google does not take part in IndexNow and has no event submission API: it finds new events through sitemap.xml (rebuilt
 * hourly) and the internal links on artist, venue and city pages. Schedule: every 30 minutes.
 */
require_once __DIR__ . '/../functions.php';

$opts = getopt('', ['dry-run', 'help']);
if (isset($opts['help'])) { echo "Usage: php cron/indexnow.php [--dry-run]\n"; exit(0); }
$dry = isset($opts['dry-run']);
$key = (string) getenv('SO_INDEXNOW_KEY');
if (!preg_match('/^[A-Za-z0-9-]{8,128}$/', $key)) { fwrite(STDERR, "SO_INDEXNOW_KEY is not set (or not 8 to 128 letters, digits or dashes). Nothing to do.\n"); exit(0); }
if (!SITE_INDEXABLE && !$dry) { echo "Host is not indexable (beta or staging): not submitting.\n"; exit(0); }

$root = dirname(__DIR__);
$keyFile = $root . '/' . $key . '.txt';
if (!is_file($keyFile) && !$dry) { file_put_contents($keyFile, $key); }
$stateFile = $root . '/cache/indexnow-seen.json';
$seen = is_file($stateFile) ? (json_decode((string) file_get_contents($stateFile), true) ?: []) : [];

// The soonest 600 upcoming US events that have tickets. A new listing further out than that is picked up by the sitemap instead.
$today = date('Y-m-d');
$urls = [];
$ids = [];
$responses = tnRequestMulti(array_map(function ($page) use ($today) {
    return ['/catalog/v2/events/', ['filter' => "date/date ge $today and _metadata/hasTickets eq true and country/alphaCode eq 'US'", 'sort' => 'date/date', 'perPage' => 200, 'page' => $page]];
}, [1, 2, 3]));
foreach ($responses as $r) {
    foreach ($r['results'] ?? [] as $ev) {
        $id = (int) ($ev['id'] ?? 0);
        $name = (string) ($ev['text']['name'] ?? '');
        if ($id <= 0 || $name === '') continue;
        $ids[$id] = true;
        if (!isset($seen[$id])) $urls[] = rtrim(HOME_URL, '/') . '/event/' . soEventSlug($ev);
    }
}
$first = !$seen;   // first run: record what exists without announcing the whole catalog at once
$urls = $first ? [] : array_slice($urls, 0, 500);
echo count($urls) . " new event URL(s)" . ($first ? " (first run: baseline recorded, nothing submitted)" : '') . "\n";
if ($dry) { foreach ($urls as $u) echo "  $u\n"; exit(0); }

if ($urls) {
    $ch = curl_init('https://api.indexnow.org/IndexNow');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_POSTFIELDS => json_encode(['host' => parse_url(HOME_URL, PHP_URL_HOST), 'key' => $key, 'keyLocation' => rtrim(HOME_URL, '/') . '/' . $key . '.txt', 'urlList' => $urls])]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "IndexNow answered HTTP $code\n";
    if ($code < 200 || $code >= 300) exit(1);   // 200/202 = accepted; leave state alone so the next run retries
}
foreach ($ids as $id => $_) $seen[$id] = time();
$seen = array_slice($seen, -5000, null, true);   // keep the file small; ids are never reused
file_put_contents($stateFile, json_encode($seen));
