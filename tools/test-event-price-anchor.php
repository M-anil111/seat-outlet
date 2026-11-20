<?php
/**
 * CI (needs the migrated database and node): the event page's title strip shows "From $X per ticket" when the live record carries a lowest
 * price, and shows nothing for a record without one. Runs the real event page against a mock catalog API (Node).
 *   priced   the event has pricingInfo.lowPrice ($50)      -> the strip says "From $50 per ticket"
 *   unpriced the event has no pricingInfo                  -> no price fact in the strip
 * A saved copy never shows a price (event.php drops pricingInfo for it; checked in tools/test-tn-bulk-event.php). Run: php tools/test-event-price-anchor.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function paCheck(string $name, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } }
$src = (string) file_get_contents($root . '/event.php');
paCheck('event.php prints the price fact only for a live record', strpos($src, "if (\$eventLowPrice !== '' && \$evAsOf === null)") !== false, true);

exec('command -v node', $nodeOut, $nodeRc);
paCheck('node is available for the mock API', $nodeRc, 0);
$id = 6100001;
$tmp = sys_get_temp_dir() . '/so-pa-' . getmypid();
@mkdir($tmp, 0700, true);
$log = $tmp . '/calls.log';
file_put_contents($tmp . '/mock.js', <<<'JS'
const http = require('http'), fs = require('fs');
const [port, log, id, mode] = process.argv.slice(2);
http.createServer((req, res) => {
  fs.appendFileSync(log, req.url + '\n');
  res.setHeader('Content-Type', 'application/json');
  const p = req.url.split('?')[0];
  if (p === '/catalog/v2/events/' + id) {
    const day = new Date(Date.now() + 25 * 86400000).toISOString().slice(0, 10);
    const e = { id: +id, text: { name: 'Zq Price Band' }, date: { date: day + 'T19:30:00', datetime: day + 'T19:30:00', datetimeOffset: day + 'T19:30:00-05:00', text: { time: '7:30 PM' } },
      venue: { id: 9100777, text: { name: 'Zq Price Arena' } }, city: { id: 9100888, text: { name: 'Zqtown' } }, stateProvince: { id: 9, text: { abbr: 'TX', name: 'Texas' } }, country: { alphaCode: 'US', text: { name: 'United States' } },
      defaultCategory: { id: 1987, path: '.1859.1986.1987.', depth: 3, text: { name: 'Rock' } }, performers: [{ id: 9100999, name: 'Zq Price Band', isPerformance: true }],
      _metadata: { hasTickets: true, ticketCount: 20 } };
    if (mode === 'priced') e.pricingInfo = { lowPrice: { value: 50, currencyCode: 'USD', text: { formatted: '$50' } } };
    return res.end(JSON.stringify(e));
  }
  if (p.startsWith('/catalog/v2/events')) return res.end(JSON.stringify({ count: 0, page: 1, results: [], totalCount: 0 }));
  if (p.startsWith('/catalog/v2/categories')) return res.end(JSON.stringify({ results: [], totalCount: 0 }));
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

// Cold start: a token for the mock, no cached answer, no saved copy, no open breaker.
$setup = $tmp . '/setup.php';
file_put_contents($setup, '<?php
$_SERVER["REQUEST_METHOD"] = "GET";
require ' . var_export($root . '/functions.php', true) . ';
file_put_contents(tnTokenCacheFile(), json_encode(["token" => "mock-token", "expires_at" => time() + 3600]));
@unlink(cache_file_path(tnCacheKey("/catalog/v2/events/' . $id . '", []))); @unlink(cache_file_path(tnCacheKey("/catalog/v2/events/' . $id . '", []) . "_nf"));
tnForgetEventCopies(' . $id . ');
foreach (glob(cache_dir() . "tn_bucket*") ?: [] as $f) @unlink($f);
foreach (["events", "reference"] as $fam) @unlink(tnBreakerFile($fam));
echo "ready\n";
');
$router = $tmp . '/router.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/event/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/event.php'; require " . var_export($root . '/event.php', true) . "; return true; }\nreturn false;\n");
$site = proc_open('exec env ' . $envStr . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $sitePort . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $p2);

$run = function (string $mode) use ($tmp, $log, $id, $mockPort, $sitePort, $envStr, $setup) {
    exec($envStr . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($setup) . ' 2>&1', $out, $rc);
    $mock = proc_open('exec node ' . escapeshellarg($tmp . '/mock.js') . ' ' . $mockPort . ' ' . escapeshellarg($log) . ' ' . $id . ' ' . $mode, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $p1);
    foreach ([$mockPort, $sitePort] as $port) { for ($i = 0; $i < 100; $i++) { $c = @fsockopen('127.0.0.1', $port, $en, $es, 0.2); if ($c) { fclose($c); break; } usleep(100000); } }
    $get = function (string $path) use ($sitePort) {
        $ctx = stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0]]);
        $body = (string) @file_get_contents('http://127.0.0.1:' . $sitePort . $path, false, $ctx);
        $code = 0; $loc = '';
        foreach ($http_response_header ?? [] as $h) { if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) $code = (int) $m[1]; if (stripos($h, 'Location:') === 0) $loc = trim(substr($h, 9)); }
        return [$code, $body, $loc];
    };
    [$code, $body, $loc] = $get('/event/zq-price-band-' . $id);
    if ($code === 301 && $loc !== '') [$code, $body] = $get(parse_url($loc, PHP_URL_PATH));   // the old name-and-id spelling goes to the canonical slug first
    proc_terminate($mock); proc_close($mock);
    $strip = preg_match('#<ul class="so-evbar__facts">(.*?)</ul>#s', $body, $m) ? $m[1] : '';
    return ['code' => $code, 'strip' => $strip, 'price' => preg_match('#<li class="so-evbar__price">.*?<span>([^<]*)</span>#s', $strip, $pm) ? $pm[1] : ''];
};
$r = $run('priced');
paCheck('priced: the page answers 200', $r['code'], 200);
paCheck('priced: the title strip has the facts list', $r['strip'] !== '', true);
paCheck('priced: the strip says "From $50 per ticket"', $r['price'], 'From $50 per ticket');
$r = $run('unpriced');
paCheck('unpriced: the page answers 200', $r['code'], 200);
paCheck('unpriced: no price fact in the strip', $r['price'], '');
paCheck('unpriced: the strip still lists the date', strpos($r['strip'], 'November') !== false || preg_match('#\b(January|February|March|April|May|June|July|August|September|October|November|December)\b#', $r['strip']) === 1, true);

proc_terminate($site); proc_close($site);
exec($envStr . escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('require ' . var_export($root . '/functions.php', true) . '; @unlink(tnTokenCacheFile()); tnForgetEventCopies(' . $id . '); MYSQLI->query("DELETE FROM event_redirects WHERE event_id = ' . $id . '"); MYSQLI->query("DELETE FROM url_slugs WHERE type = \'event\' AND ext_id = \'' . $id . '\'");') . ' 2>&1');
array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: the title strip shows the lowest listed price from a live record\n";
