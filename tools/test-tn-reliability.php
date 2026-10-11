<?php
// Local HTTP fixtures only: no TicketNetwork credentials, network service or database required.
$root = dirname(__DIR__);
$dir = sys_get_temp_dir() . '/so-tn-test-' . bin2hex(random_bytes(6));
mkdir($dir, 0700);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$socket) throw new RuntimeException($error);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$router = <<<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/auth-html') { http_response_code(401); echo 'expired'; }
elseif ($path === '/auth-json') { http_response_code(401); echo '{"Message":"expired"}'; }
elseif ($path === '/throttle') { http_response_code(429); header('Retry-After: 300'); echo 'rate limited'; }
elseif ($path === '/outage') { http_response_code(503); echo '{"error":"unavailable"}'; }
elseif ($path === '/missing') { http_response_code(404); echo 'not found'; }
elseif ($path === '/truncated' && !is_file(__DIR__ . '/seen')) { touch(__DIR__ . '/seen'); header('Content-Length: 100'); echo '{'; }
else { echo '{"results":[{"id":42}],"totalCount":1}'; }
PHP;
file_put_contents($dir . '/router.php', $router);
$command = [PHP_BINARY, '-c', php_ini_loaded_file() ?: '', '-S', $address, $dir . '/router.php'];
$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $dir . '/server.log', 'a'], 2 => ['file', $dir . '/server.log', 'a']], $pipes);
if (!is_resource($process)) throw new RuntimeException('Could not start fixture server');
register_shutdown_function(function () use ($process, $dir) {
    proc_terminate($process); proc_close($process);
    foreach (glob($dir . '/*') ?: [] as $f) unlink($f);
    rmdir($dir);
});
for ($i = 0; $i < 100; $i++) {
    $ready = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
    if ($ready) { fclose($ready); break; }
    usleep(20000);
}
define('BASE_URL', 'http://' . $address);
define('CONSUMER_KEY', 'local-fixture-key');
define('CONSUMER_SECRET', ''); // Prevent any real OAuth request.
define('WEBSITE_CONFIG_ID', 0);
define('TN_ACCESS_TOKEN_EXPIRY_BUFFER', 60);
define('TN_BREAKER_SECONDS', 20);
define('TN_BREAKER_THROTTLE_SECONDS', 60);
putenv('TN_TOKEN_DIR=' . $dir);
$source = file_get_contents($root . '/functions.php');
foreach (['tnTokenCacheFile','tnTokenFromFile','getTnAccessToken','tnBuildHandle','tnParseResult','tnFetchLive'] as $name) {
    if (!preg_match('/function ' . $name . '\(.*?\n}\n/s', $source, $m)) throw new RuntimeException('Missing ' . $name);
    eval($m[0]);
}
// Profiling/monitoring are not under test.
eval('function tnProfile(...$args) {} function soTnAlert(...$args) {}');
$fail = 0;
function tnReliabilityCheck(bool $ok, string $label): void {
    global $fail;
    echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$ok) $fail++;
}
foreach (['/auth-html','/auth-json'] as $path) {
    $ch = tnBuildHandle($path, [], 'GET', 'fixture');
    $r = tnParseResult($ch, curl_exec($ch));
    tnReliabilityCheck(!$r['ok'] && !empty($r['auth']) && !$r['cacheable'], $path . ' triggers refresh rather than being treated as data');
}
$ch = tnBuildHandle('/throttle', [], 'GET', 'fixture');
$r = tnParseResult($ch, curl_exec($ch));
tnReliabilityCheck(!$r['ok'] && !empty($r['throttled']) && ($r['retry_after'] ?? 0) === 300, 'plain-text 429 honours Retry-After');
$ch = tnBuildHandle('/outage', [], 'GET', 'fixture');
$r = tnParseResult($ch, curl_exec($ch));
tnReliabilityCheck(!$r['ok'] && !$r['cacheable'], '503 remains a failed feed');
$ch = tnBuildHandle('/missing', [], 'GET', 'fixture');
$r = tnParseResult($ch, curl_exec($ch));
tnReliabilityCheck($r['ok'] && ($r['http'] ?? 0) === 404 && !$r['cacheable'], 'real 404 does not open a global circuit');
file_put_contents(tnTokenCacheFile(), json_encode(['token' => 'fixture-token', 'expires_at' => time() + 60]));
$r = tnFetchLive('/truncated', [], 'GET');
tnReliabilityCheck($r['ok'] && ($r['data']['results'][0]['id'] ?? 0) === 42, 'prematurely closed GET succeeds on one bounded retry');
$r = tnFetchLive('/auth-html', [], 'GET');
tnReliabilityCheck(!$r['ok'] && !$r['cacheable'], 'unrecoverable authentication failure is not a valid empty response');
putenv('TN_TOKEN_DIR=' . $dir . '/missing-directory');
tnReliabilityCheck(getTnAccessToken() === '', 'missing shared token lock fails closed');
putenv('TN_TOKEN_DIR=' . $dir);
file_put_contents(tnTokenCacheFile(), json_encode(['token' => 'expired-fixture', 'expires_at' => time() - 1]));
tnReliabilityCheck(getTnAccessToken() === '', 'expired file cannot reuse a process-local stale token');
exit($fail ? 1 : 0);
