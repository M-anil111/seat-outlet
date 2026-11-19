<?php
/**
 * CI (needs the migrated database): an empty listing answers 503 only when ITS OWN list could not be had, not when some other lookup on the
 * page failed. Live, 19 Nov 2026: /category/west-end (0 US events) answered 503 "Listings loading", then 200 with 0 results a moment later:
 * the request-wide "degraded" flag was set by another lookup while the event list itself had answered "no events".
 * Runs the real category page against a mock catalog API (Node) in four modes, each from a cold cache with no page snapshot:
 *   ok        every call answers; the category has no event          -> 200, noindex, no ad unit
 *   ref-fail  the category lookups (reference family) fail; the event list answers "no events" -> 200, noindex, no ad unit (was 503)
 *   list-fail the event list fails; the category lookup answers       -> 503 (a real failure stays retryable)
 *   outage    every call fails                                        -> 503
 * Also counts the calls for this category in each mode: the fix is a check on an answer the page already has, so it adds none.
 * Run: php tools/test-empty-listing-503.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function elCheck(string $name, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } }

// ---- The rule itself
require_once $root . '/inc/entity-pages.php';
$GLOBALS['tn_degraded'] = false;
elCheck('a healthy request never counts a list as unanswered', soListingUnanswered([]), false);
$GLOBALS['tn_degraded'] = true;
elCheck('degraded, the list answered "no events": answered', soListingUnanswered(['results' => [], 'totalCount' => 0]), false);
elCheck('degraded, the list failed ([]): unanswered', soListingUnanswered([]), true);
elCheck('degraded, an error body (Message, no results): unanswered', soListingUnanswered(['Message' => 'An error has occurred.']), true);
elCheck('degraded, results that are not a list: unanswered', soListingUnanswered(['results' => null]), true);
elCheck('degraded, not an array: unanswered', soListingUnanswered(null), true);
elCheck('degraded, the catalog copy answered: answered', soListingUnanswered(['_bulk' => true, 'results' => [], 'totalCount' => 0]), false);
elCheck('degraded, a list with events: answered', soListingUnanswered(['results' => [['id' => 1]], 'totalCount' => 1]), false);
$GLOBALS['tn_degraded'] = false;

// ---- Wiring: every listing gate asks about its own list
$gates = [
    'category.php' => 'soListingUnanswered($eventsResponse)', 'performer.php' => 'soListingUnanswered($results)', 'search.php' => 'soListingUnanswered($results)',
    'inc/holidays.php' => 'soListingUnanswered($resp)', 'inc/discovery-pages.php' => 'soListingUnanswered($resp)',
    'inc/entity-listing.php' => "soListingUnanswered(\$c['listingResponse'])",
];
foreach ($gates as $f => $needle) elCheck("$f gates on its own list", strpos((string) file_get_contents("$root/$f"), $needle) !== false, true);
elCheck('functions.php: both location listings gate on their own list', substr_count((string) file_get_contents("$root/functions.php"), '!$events && soListingUnanswered($eventsResponse)) { soFeedDownGate('), 2);
foreach (['venue.php', 'city.php', 'state.php', 'county.php', 'country.php'] as $f) {
    elCheck("$f passes its list answer to the entity listing", strpos((string) file_get_contents("$root/$f"), "'listingResponse' => \$eventsResponse,") !== false, true);
}
$gateLines = [];
foreach (array_merge(glob("$root/*.php") ?: [], glob("$root/inc/*.php") ?: []) as $f) {   // functions.php is in the root
    foreach (preg_split('/\R/', (string) file_get_contents($f)) as $line) {
        if (strpos($line, 'soFeedDownGate(') !== false && strpos($line, 'soApiDegraded()') !== false && strpos($line, 'listingResponse') === false) $gateLines[] = basename($f) . ': ' . trim($line);
    }
}
elCheck('no listing gate decides on the request-wide flag alone', $gateLines, []);

// ---- The real page against a mock API
exec('command -v node', $nodeOut, $nodeRc);
elCheck('node is available for the mock API', $nodeRc, 0);
$tmp = sys_get_temp_dir() . '/so-el-' . getmypid();
@mkdir($tmp, 0700, true);
$log = $tmp . '/calls.log';
$catId = 9300096;
file_put_contents($tmp . '/mock.js', <<<'JS'
const http = require('http'), fs = require('fs');
const [port, log, cat, mode] = process.argv.slice(2);
http.createServer((req, res) => {
  fs.appendFileSync(log, req.url + '\n');
  res.setHeader('Content-Type', 'application/json');
  const p = req.url.split('?')[0];
  const isEvents = p.startsWith('/catalog/v2/events');
  const failRef = mode === 'ref-fail' || mode === 'outage', failList = mode === 'list-fail' || mode === 'outage';
  if ((isEvents && failList) || (!isEvents && failRef)) { res.statusCode = 500; return res.end(JSON.stringify({ Message: 'An error has occurred.' })); }
  if (p.startsWith('/catalog/v2/categories')) return res.end(JSON.stringify({ results: [{ id: +cat, path: '.1859.1989.' + cat + '.', depth: 2, text: { name: 'Zq Empty Cat' } }], totalCount: 1 }));
  if (isEvents) return res.end(JSON.stringify({ count: 0, page: 1, results: [], totalCount: 0 }));
  res.statusCode = 404; res.end(JSON.stringify({ Message: 'The requested resource was not found.' }));
}).listen(+port, '127.0.0.1');
JS);
$freePort = function (): int { $s = stream_socket_server('tcp://127.0.0.1:0'); $n = (int) substr(strrchr((string) stream_socket_get_name($s, false), ':'), 1); fclose($s); return $n; };
$sitePort = $freePort();
$mockPort = $freePort();
$env = ['BASE_URL' => 'http://127.0.0.1:' . $mockPort, 'TN_TOKEN_DIR' => $tmp, 'TN_CALLS_PER_SECOND' => '100', 'TN_BULK_ENABLED' => '0', 'HOME_URL' => 'http://127.0.0.1:' . $sitePort];
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env[$k] = 'test-placeholder'; }
$envStr = '';
foreach ($env as $k => $v) { $envStr .= $k . '=' . escapeshellarg($v) . ' '; }

// Cold start for this category: a token for the mock, the slug (with its stored name), no cached answers, no open breaker, no snapshot.
$setup = $tmp . '/setup.php';
file_put_contents($setup, '<?php
$_SERVER["REQUEST_METHOD"] = "GET";
require ' . var_export($root . '/functions.php', true) . ';
require_once ' . var_export($root . '/inc/seo-category.php', true) . ';
file_put_contents(tnTokenCacheFile(), json_encode(["token" => "mock-token", "expires_at" => time() + 3600]));
echo soSlug("category", "Zq Empty Cat", ' . $catId . '), "\n";
[$w, $s] = listingRequestState("soonest");
foreach ([tnCatByIdSpec(' . $catId . '), soCategoryDataSpec(' . $catId . '), soCategoryListSpec(' . $catId . ', 20, $w, $s, 0), ["/catalog/v2/categories/", ["filter" => "contains(path, \'.' . $catId . '.\')", "perPage" => 10]]] as $r) {
    @unlink(cache_file_path(tnCacheKey($r[0], $r[1]))); @unlink(cache_file_path(tnCacheKey($r[0], $r[1]) . "_nf"));
}
foreach (glob(cache_dir() . "tn_bucket*") ?: [] as $f) @unlink($f);
foreach (["events", "reference"] as $fam) @unlink(tnBreakerFile($fam));   // breakers live in the temp dir, one per family (tnBreakerFile)
$snap = soSnapshotFile("/category/zq-empty-cat"); if ($snap) @unlink($snap);
');
$router = $tmp . '/router.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/category/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/category.php'; require " . var_export($root . '/category.php', true) . "; return true; }\nreturn false;\n");
$site = proc_open('exec env ' . $envStr . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $sitePort . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $p2);

$run = function (string $mode) use ($tmp, $log, $catId, $mockPort, $sitePort, $envStr, $setup) {
    $out = [];
    exec($envStr . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($setup) . ' 2>&1', $out, $rc);
    $mock = proc_open('exec node ' . escapeshellarg($tmp . '/mock.js') . ' ' . $mockPort . ' ' . escapeshellarg($log) . ' ' . $catId . ' ' . $mode, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $p1);
    foreach ([$mockPort, $sitePort] as $port) { for ($i = 0; $i < 100; $i++) { $c = @fsockopen('127.0.0.1', $port, $en, $es, 0.2); if ($c) { fclose($c); break; } usleep(100000); } }
    @unlink($log);
    $ctx = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true, 'follow_location' => 0]]);
    $body = (string) @file_get_contents('http://127.0.0.1:' . $sitePort . '/category/zq-empty-cat', false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) { if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) $code = (int) $m[1]; }
    proc_terminate($mock); proc_close($mock);
    preg_match('#<meta name="robots" content="([^"]*)"#', $body, $rb);
    $calls = array_filter(explode("\n", (string) @file_get_contents($log)), fn($l) => strpos(urldecode($l), '.' . $catId . '.') !== false);
    return ['setup' => trim((string) end($out)), 'code' => $code, 'robots' => $rb[1] ?? '', 'ads' => substr_count($body, '<ins class="adsbygoogle'), 'calls' => count($calls)];
};

$want = ['ok' => [200, 'noindex'], 'ref-fail' => [200, 'noindex'], 'list-fail' => [503, ''], 'outage' => [503, '']];
foreach ($want as $mode => [$code, $robots]) {
    $r = $run($mode);
    elCheck("$mode: setup (slug)", $r['setup'], 'zq-empty-cat');
    elCheck("$mode: status", $r['code'], $code);
    if ($robots !== '') elCheck("$mode: robots is noindex", strpos($r['robots'], 'noindex') === 0, true);
    elCheck("$mode: no ad unit", $r['ads'], 0);
    // The batch sends the category's name, its popular events and its list once each; a failed name lookup may also try the
    // any-depth lookup. Nothing more for this category in any mode.
    elCheck("$mode: at most 4 calls for this category (got {$r['calls']})", $r['calls'] >= 1 && $r['calls'] <= 4, true);
    echo "     $mode: HTTP {$r['code']}, robots '{$r['robots']}', {$r['ads']} ad unit(s), {$r['calls']} call(s) for the category\n";
}

proc_terminate($site); proc_close($site);
exec($envStr . escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('require ' . var_export($root . '/functions.php', true) . '; @unlink(tnTokenCacheFile()); MYSQLI->query("DELETE FROM url_slugs WHERE type = \'category\' AND ext_id = \'' . $catId . '\'");'));
array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: an empty listing is a 503 only when its own list failed\n";
