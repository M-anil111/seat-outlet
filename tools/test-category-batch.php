<?php
/**
 * CI (needs the migrated database): a cold category page sends its three TicketNetwork lookups (the category's name, its popular events,
 * its listed events) in one parallel batch, not one after another, and sends no more calls than before. Runs the real category page
 * against a small mock of the catalog API (Node) that answers after 400 ms and logs when every request arrives. The page used to wait for three answers
 * in a row (docs/adsense-readiness.md, slow pages: /category/west-end took about 18 s cold on the live site).
 * Also checks an old name-and-id address, which only redirects, sends nothing.
 * Run: php tools/test-category-batch.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function cbCheck(string $name, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } }

$src = (string) file_get_contents($root . '/category.php');
cbCheck('the batch is sent before the first lookup', ($b = strpos($src, 'tnRequestMulti([tnCatByIdSpec($id), soCategoryDataSpec($id), soCategoryListSpec(')) !== false && $b < strpos($src, '$cat = getTnCatById($id);'), true);
cbCheck('the list uses the same request as the batch', strpos($src, '[$catEndpoint, $params] = soCategoryListSpec($id, $perPage, $when, $sort, $maxPrice);') !== false, true);
cbCheck('soCategoryData uses the same request as the batch', strpos((string) file_get_contents($root . '/inc/seo-category.php'), '= soCategoryDataSpec($id);') !== false, true);
cbCheck('getTnCatById uses the same request as the batch', strpos((string) file_get_contents($root . '/functions.php'), '= tnCatByIdSpec($catId);') !== false, true);

$tmp = sys_get_temp_dir() . '/so-cb-' . getmypid();
@mkdir($tmp, 0700, true);
$log = $tmp . '/calls.log';
$catId = 9300098;
// The mock API, in Node: it handles every request at once (PHP's built-in server hands requests to its workers without balancing, so two
// calls could queue on one worker and look sequential). It logs the time each request ARRIVES, then answers after 400 ms. Calls sent
// together arrive within milliseconds; calls sent one after another arrive at least 400 ms apart (each waits for the previous answer).
// The category is "Zq Batch" (slug zq-batch), with one US event.
file_put_contents($tmp . '/mock.js', <<<'JS'
const http = require('http'), fs = require('fs');
const [port, log, cat] = process.argv.slice(2);
const d = new Date(Date.now() + 12 * 86400000).toISOString().slice(0, 10);
http.createServer((req, res) => {
  const arrived = (Date.now() / 1000).toFixed(4);
  setTimeout(() => {
    fs.appendFileSync(log, arrived + ' ' + req.url + '\n');
    res.setHeader('Content-Type', 'application/json');
    const p = req.url.split('?')[0];
    if (p.startsWith('/catalog/v2/categories')) return res.end(JSON.stringify({ results: [{ id: +cat, path: '.1859.1989.' + cat + '.', depth: 2, text: { name: 'Zq Batch' } }], totalCount: 1 }));
    if (p.startsWith('/catalog/v2/events')) return res.end(JSON.stringify({ results: [{ id: 9300097, text: { name: 'Zq Batch Show' },
      date: { date: d + 'T19:30:00', datetime: d + 'T19:30:00', datetimeOffset: d + 'T19:30:00-05:00' },
      venue: { id: 9300097, text: { name: 'Zq Batch Hall' } }, city: { id: 9300097, text: { name: 'Zqtown' } },
      stateProvince: { id: 9300097, text: { abbr: 'TX' } }, country: { alphaCode: 'US' }, defaultCategory: { path: '.1859.1989.' + cat + '.' },
      performers: [{ id: 9300097, name: 'Zq Batch Act' }], _metadata: { hasTickets: true, ticketCount: 4 } }], totalCount: 1 }));
    res.statusCode = 404;
    res.end(JSON.stringify({ Message: 'The requested resource was not found.' }));
  }, 400);
}).listen(+port, '127.0.0.1');
JS);
exec('command -v node', $nodeOut, $nodeRc);
cbCheck('node is available for the mock API', $nodeRc, 0);
// Free ports from the system (a fixed or random port can already be taken by another test's server).
$freePort = function (): int { $s = stream_socket_server('tcp://127.0.0.1:0'); $n = (int) substr(strrchr((string) stream_socket_get_name($s, false), ':'), 1); fclose($s); return $n; };
$mockPort = $freePort();
$sitePort = $freePort();
$base = 'http://127.0.0.1:' . $mockPort;
$env = ['BASE_URL' => $base, 'TN_TOKEN_DIR' => $tmp, 'TN_CALLS_PER_SECOND' => '100', 'TN_BULK_ENABLED' => '0', 'HOME_URL' => 'http://127.0.0.1:' . $sitePort];
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env[$k] = 'test-placeholder'; }
$envStr = '';
foreach ($env as $k => $v) { $envStr .= $k . '=' . escapeshellarg($v) . ' '; }

// Setup in a child with the same settings: a valid token for the mock (no call to the real key manager), the category's slug, and no
// cached answers for the requests this page makes, so the page is cold.
$setup = $tmp . '/setup.php';
file_put_contents($setup, '<?php
require ' . var_export($root . '/functions.php', true) . ';
require_once ' . var_export($root . '/inc/seo-category.php', true) . ';
file_put_contents(tnTokenCacheFile(), json_encode(["token" => "mock-token", "expires_at" => time() + 3600]));
echo soSlug("category", "Zq Batch", ' . $catId . '), "\n";
[$w, $s] = listingRequestState("soonest");
foreach ([tnCatByIdSpec(' . $catId . '), soCategoryDataSpec(' . $catId . '), soCategoryListSpec(' . $catId . ', 20, $w, $s, 0)] as $r) { @unlink(cache_file_path(tnCacheKey($r[0], $r[1]))); @unlink(cache_file_path(tnCacheKey($r[0], $r[1]) . "_nf")); }
foreach (glob(cache_dir() . "tn_bucket*.json") ?: [] as $f) @unlink($f);
foreach (glob(cache_dir() . "tn_breaker*") ?: [] as $f) @unlink($f);
');
exec($envStr . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($setup) . ' 2>&1', $setupOut, $rc);
cbCheck('setup ran', $rc, 0);
$slug = trim((string) end($setupOut));
cbCheck('the category has the slug the page expects', $slug, 'zq-batch');

$router = $tmp . '/router.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/category/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/category.php'; require " . var_export($root . '/category.php', true) . "; return true; }\nreturn false;\n");
$mock = proc_open('exec node ' . escapeshellarg($tmp . '/mock.js') . ' ' . $mockPort . ' ' . escapeshellarg($log) . ' ' . $catId, [1 => ['file', '/dev/null', 'w'], 2 => ['file', $tmp . '/mock.err', 'w']], $p1);
$site = proc_open('exec env ' . $envStr . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $sitePort . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', $tmp . '/site.err', 'w']], $p2);
foreach ([$mockPort, $sitePort] as $port) {   // wait until both servers accept connections (at most 10 s)
    for ($i = 0; $i < 100; $i++) { $c = @fsockopen('127.0.0.1', $port, $en, $es, 0.2); if ($c) { fclose($c); break; } usleep(100000); }
}
$get = function (string $path) use ($sitePort) {
    $ctx = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true, 'follow_location' => 0]]);
    $t = microtime(true);
    $body = (string) @file_get_contents('http://127.0.0.1:' . $sitePort . $path, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) { if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) $code = (int) $m[1]; }
    return [$code, $body, microtime(true) - $t];
};
@unlink($log);
[$code, $body] = $get('/category/zq-batch');
// Only this category's own lookups (its id is in each): on a cold cache the page also builds site-wide parts (the menu's category tree,
// the site's lowest price), which are cached for hours on a running site and are not what this test is about.
$calls = array_values(array_filter(explode("\n", (string) @file_get_contents($log)), fn($l) => strpos(urldecode($l), "." . $catId . ".") !== false));
$times = array_map(fn($l) => (float) strtok($l, ' '), $calls);
cbCheck('the cold category page answers 200', $code, 200);
if ($code !== 200) echo substr(strip_tags($body), 0, 300), "\n", preg_replace('/^.*(Accepted|Closing|Development Server|Listening).*$\n?/m', '', (string) @file_get_contents($tmp . '/site.err')), "\n";
cbCheck('it shows the event', strpos($body, 'Zq Batch Show') !== false, true);
cbCheck('three lookups, no more than before', count($calls), 3);
if (count($calls) !== 3) echo implode("\n", array_map(fn($l) => substr(urldecode($l), 0, 160), $calls)), "\n";
// Arrival times: together within a few ms; one after another, each at least 400 ms after the previous.
$spread = $times ? max($times) - min($times) : 99.0;
cbCheck('the three lookups were sent together, not one after another (arrival spread ' . round($spread, 3) . ' s)', $spread < 0.2, true);

// An old name-and-id address redirects before using any list: nothing is sent for it.
@unlink($log);
[$code2] = $get('/category/zq-batch-' . $catId);
cbCheck('the old address redirects', $code2, 301);
cbCheck('the old address sends no list lookup', count(array_filter(explode("\n", (string) @file_get_contents($log)), fn($l) => strpos($l, '/catalog/v2/events') !== false && strpos(urldecode($l), '.' . $catId . '.') !== false)), 0);

// "exec" made each server the process itself (not a shell around it); its workers are its children and are stopped first.
foreach ([$site, $mock] as $proc) {
    $pid = (int) (proc_get_status($proc)['pid'] ?? 0);
    if ($pid > 0) exec('pkill -TERM -P ' . $pid . ' 2>/dev/null');
    proc_terminate($proc);
    proc_close($proc);
}
exec($envStr . escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('require ' . var_export($root . '/functions.php', true) . '; @unlink(tnTokenCacheFile()); MYSQLI->query("DELETE FROM url_slugs WHERE type = \'category\' AND ext_id = \'' . $catId . '\'");'));
array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: a cold category page sends its three lookups in one batch\n";
