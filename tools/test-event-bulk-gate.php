<?php
/**
 * CI (needs the migrated database): an upcoming event that the local catalog copy holds is served from it (200) when the live lookup
 * is refused, instead of the gate in header.php answering 503 before the page can use the copy. SE Ranking (18 Nov 2026) and Search
 * Console list event pages from the sitemap, which is built from the same catalog, as "5XX". With no catalog copy the page still
 * answers 503. An old id the fresh copy does not hold is gone (404, not 503); a recent one may be new (still 503). Runs the real event page through PHP's built-in server with the call budget used up.
 * Run: php tools/test-event-bulk-gate.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
$fail = 0;
function ebCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);
$h = (string) file_get_contents($root . '/header.php');
ebCheck('the event gate may use the catalog copy', strpos($h, 'getTnEventById($soEvId, true)') !== false, true);
ebCheck('the event gate asks the catalog copy whether a missing event is gone', strpos($h, 'tnBulkEventGone(MYSQLI, $soEvId') !== false, true);

// The rule itself (pure): absent from the copy and well below its newest id.
require_once $root . '/inc/tn-bulk-read.php';
ebCheck('absent and far below the newest id: gone', tnBulkAbsentMeansGone(false, 8048654, 8400000), true);
ebCheck('a live row in the copy: not gone', tnBulkAbsentMeansGone(true, 8048654, 8400000), false);
ebCheck('absent but within the margin of the newest id: not gone (may be new)', tnBulkAbsentMeansGone(false, 8390000, 8400000), false);
ebCheck('above the newest id: not gone', tnBulkAbsentMeansGone(false, 8500000, 8400000), false);
ebCheck('an empty copy (no newest id): not gone', tnBulkAbsentMeansGone(false, 100, 0), false);
ebCheck('id 0: not gone', tnBulkAbsentMeansGone(false, 0, 8400000), false);

exec('php ' . escapeshellarg($root . '/tools/seed-critical-fixtures.php') . ' 2>&1', $out, $rc);
ebCheck('fixtures seeded (a ready catalog copy)', $rc, 0);
$cache = $root . '/cache/';
foreach (glob($cache . 'tn_bucket*.json') ?: [] as $b) { @unlink($b); }
file_put_contents($cache . 'tn_bucket.json', json_encode(['tokens' => 0, 't' => microtime(true)]));
$router = sys_get_temp_dir() . '/so-eb-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/event/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/event.php'; require " . var_export($root . '/event.php', true) . "; return true; }\nreturn false;\n");
$serve = function (string $bulk) use ($router) {
    $port = 8600 + random_int(0, 80);
    $env = 'SO_SLUG_MIN_ROWS=-1 TN_BULK_ENABLED=' . $bulk . ' TN_CALLS_PER_SECOND=0.00001 ';
    foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env .= $k . '=test-placeholder '; }
    $proc = proc_open('exec env ' . $env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(900000);
    $get = function (string $path) use ($port) {
        $ctx = stream_context_create(['http' => ['timeout' => 40, 'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0]]);
        $body = (string) @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
        $code = 0; $loc = '';
        foreach ($http_response_header ?? [] as $hd) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $hd, $m)) $code = (int) $m[1];
            if (stripos($hd, 'Location:') === 0) $loc = trim(substr($hd, 9));
        }
        return [$code, $body, $loc];
    };
    return [$proc, $get];
};
[$proc, $get] = $serve('1');
[$c1, $b1, $l1] = $get('/event/zq-band-live-9300002');
if ($c1 === 301 && $l1 !== '') { [$c1, $b1, $l1] = $get(parse_url($l1, PHP_URL_PATH)); }   // the old name-and-id spelling goes to the canonical slug first
// An old id the copy does not hold (well below its newest id): gone, so 404 with the not-found page, not a 503 (Search Console 5xx).
[$c3, $b3] = $get('/event/zq-old-show-9000001');
// An id near the top of the copy's range may be an event created after the last pull: still the retryable 503.
[$c4] = $get('/event/zq-new-show-9300009');
proc_terminate($proc); proc_close($proc);   // exec: the server is the child itself, so this stops it (no orphan left)
ebCheck('catalog copy on, lookup refused: the event page answers 200 (not 503)', $c1, 200);
ebCheck('an old id the copy does not hold answers 404 (not 503)', $c3, 404);
ebCheck('its page says the event was not found and is noindex', strpos($b3, 'Event Not Found') !== false && preg_match('#<meta name="robots" content="noindex#', $b3) === 1, true);
ebCheck('a recent id the copy does not hold yet still answers 503', $c4, 503);
ebCheck('the head names the event (a title and a canonical)', preg_match('#<title>[^<]*Zq Band[^<]*</title>#', $b1) === 1 && preg_match('#<link rel="canonical" href="[^"]*/event/[^"]+"#', $b1) === 1, true);
ebCheck('it shows the event from the copy', strpos($b1, 'Zq Band Live 9300002') !== false, true);
[$proc, $get] = $serve('0');
[$c2] = $get('/event/zq-band-live-9300002');
proc_terminate($proc); proc_close($proc);   // exec: the server is the child itself, so this stops it (no orphan left)
ebCheck('no catalog copy (feed really down): still 503', $c2, 503);

exec('php ' . escapeshellarg($root . '/tools/seed-critical-fixtures.php') . ' --clean 2>&1');
@unlink($router);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: an event the catalog copy holds is served from it when the live lookup is refused\n";
