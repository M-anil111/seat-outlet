<?php
/**
 * CI (needs the migrated database): a cold category page sends its three TicketNetwork lookups (the category's name, its popular events,
 * its listed events) in one parallel batch, not one after another, and sends no more calls than before. Runs the real category page
 * against a small mock of the catalog API that waits 400 ms per answer and logs every request. The page used to wait for three answers
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
// The mock API: every answer after 400 ms; the category is "Zq Batch" (slug zq-batch), with one US event.
file_put_contents($tmp . '/mock.php', '<?php
usleep(400000);
file_put_contents(' . var_export($log, true) . ', microtime(true) . " " . $_SERVER["REQUEST_URI"] . "\n", FILE_APPEND | LOCK_EX);
header("Content-Type: application/json");
$p = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$d = date("Y-m-d", strtotime("+12 days"));
if (strpos($p, "/catalog/v2/categories") === 0) { echo json_encode(["results" => [["id" => ' . $catId . ', "path" => ".1859.1989.' . $catId . '.", "depth" => 2, "text" => ["name" => "Zq Batch"]]], "totalCount" => 1]); return true; }
if (strpos($p, "/catalog/v2/events") === 0) { echo json_encode(["results" => [["id" => 9300097, "text" => ["name" => "Zq Batch Show"], "date" => ["date" => $d . "T19:30:00", "datetime" => $d . "T19:30:00", "datetimeOffset" => $d . "T19:30:00-05:00"], "venue" => ["id" => 9300097, "text" => ["name" => "Zq Batch Hall"]], "city" => ["id" => 9300097, "text" => ["name" => "Zqtown"]], "stateProvince" => ["id" => 9300097, "text" => ["abbr" => "TX"]], "country" => ["alphaCode" => "US"], "defaultCategory" => ["path" => ".1859.1989.' . $catId . '."], "performers" => [["id" => 9300097, "name" => "Zq Batch Act"]], "_metadata" => ["hasTickets" => true, "ticketCount" => 4]]], "totalCount" => 1]); return true; }
http_response_code(404); echo json_encode(["Message" => "The requested resource was not found."]); return true;
');
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
$mock = proc_open('PHP_CLI_SERVER_WORKERS=8 ' . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $mockPort . ' ' . escapeshellarg($tmp . '/mock.php'), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $p1);
$site = proc_open($envStr . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $sitePort . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', $tmp . '/site.err', 'w']], $p2);
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
$calls = array_values(array_filter(explode("\n", (string) @file_get_contents($log))));
$times = array_map(fn($l) => (float) strtok($l, ' '), $calls);
cbCheck('the cold category page answers 200', $code, 200);
if ($code !== 200) echo substr(strip_tags($body), 0, 300), "\n", preg_replace('/^.*(Accepted|Closing|Development Server|Listening).*$\n?/m', '', (string) @file_get_contents($tmp . '/site.err')), "\n";
cbCheck('it shows the event', strpos($body, 'Zq Batch Show') !== false, true);
cbCheck('three lookups, no more than before', count($calls), 3);
if (count($calls) !== 3) echo implode("\n", array_map(fn($l) => substr(urldecode($l), 0, 160), $calls)), "\n";
// Each answer takes 400 ms. In a row the last is logged 1.1 s or more after the first (measured); together, within 0.4 s (the mock's
// workers are not load balanced, so two calls can still share one worker and wait for each other).
$spread = $times ? max($times) - min($times) : 99.0; if (getenv('CB_DEBUG')) foreach ($calls as $c) echo substr(urldecode($c), 0, 60), "\n";
cbCheck('the three lookups were answered together, not one after another (spread ' . round($spread, 3) . ' s)', $spread < 0.75, true);

// An old name-and-id address redirects before using any list: nothing is sent for it.
@unlink($log);
[$code2] = $get('/category/zq-batch-' . $catId);
cbCheck('the old address redirects', $code2, 301);
cbCheck('the old address sends no list lookup', count(array_filter(explode("\n", (string) @file_get_contents($log)), fn($l) => strpos($l, '/catalog/v2/events') !== false)), 0);

proc_terminate($site); proc_terminate($mock);
exec($envStr . escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('require ' . var_export($root . '/functions.php', true) . '; @unlink(tnTokenCacheFile()); MYSQLI->query("DELETE FROM url_slugs WHERE type = \'category\' AND ext_id = \'' . $catId . '\'");'));
array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: a cold category page sends its three lookups in one batch\n";
