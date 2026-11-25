<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Test: the slow request log line (soSlowRequestLine in inc/server-load.php). A page that takes SO_SLOW_LOG_MS or more leaves one line in the
 * error log with its path, status, time, load and Server-Timing breakdown, so a slow period can be explained afterwards. Nothing about the visitor
 * is kept, and a crafted address cannot add a line to the log. The line is checked as a pure function, then end to end in real web requests (needs the
 * migrated database): one line for a slow page, none for a fast one, and a failed database connection is logged too. Run: php tools/test-slow-request-log.php
 */
require_once __DIR__ . '/../inc/server-load.php';

$fail = 0;
function slowLogCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$timing = 'app;dur=8850, tn;dur=400, dbq;desc="23", dbconn;dur=3200, boot;dur=8850';

slowLogCheck('the limit is 5000 ms when SO_SLOW_LOG_MS is not set', soSlowRequestLimitMs(false), 5000);
slowLogCheck('the limit is 5000 ms when SO_SLOW_LOG_MS is empty', soSlowRequestLimitMs(''), 5000);
slowLogCheck('the limit is 5000 ms when SO_SLOW_LOG_MS is not a number', soSlowRequestLimitMs('soon'), 5000);
slowLogCheck('the limit is the number given', soSlowRequestLimitMs('1500'), 1500);
slowLogCheck('0 turns the log off', soSlowRequestLimitMs('0'), 0);
slowLogCheck('a negative number turns the log off', soSlowRequestLimitMs('-1') <= 0, true);
slowLogCheck('a fast page is not logged', soSlowRequestLine(120.0, '/artist/x', 200, $timing, 0.4, 5000), null);
slowLogCheck('a page just under the limit is not logged', soSlowRequestLine(4999.0, '/artist/x', 200, $timing, 0.4, 5000), null);
slowLogCheck('the log is off at 0', soSlowRequestLine(60000.0, '/artist/x', 200, $timing, 0.4, 0), null);
slowLogCheck('the log is off below 0', soSlowRequestLine(60000.0, '/artist/x', 200, $timing, 0.4, -1), null);
slowLogCheck('a page at the limit is logged with path, status, time, load and breakdown',
    soSlowRequestLine(5000.0, '/artist/menopause-the-musical', 200, $timing, 3.456, 5000),
    'slow request: /artist/menopause-the-musical status 200 took 5000 ms load 3.46 [' . $timing . ']');
slowLogCheck('without a load figure the load is left out', soSlowRequestLine(9000.4, '/', 503, 'app;dur=9000', null, 5000), 'slow request: / status 503 took 9000 ms [app;dur=9000]');
slowLogCheck('the query string is not kept', strpos((string) soSlowRequestLine(9000.0, '/search?q=secret%20name&email=a@b.test', 200, $timing, 1.0, 5000), 'secret') === false, true);
slowLogCheck('the path is kept without its query string', strpos((string) soSlowRequestLine(9000.0, '/search?q=x', 200, $timing, 1.0, 5000), 'slow request: /search status') === 0, true);
$crafted = (string) soSlowRequestLine(9000.0, "/a\r\nFAKE LINE: x\x00b", 200, "t;dur=1\nINJECT", 1.0, 5000);
slowLogCheck('a crafted address cannot add a line', strpbrk($crafted, "\r\n\0") === false, true);
slowLogCheck('the path is cut at 120 characters', soSlowRequestLine(9000.0, '/' . str_repeat('a', 500), 200, $timing, 1.0, 5000), 'slow request: /' . str_repeat('a', 119) . ' status 200 took 9000 ms load 1 [' . $timing . ']');
slowLogCheck('an empty address is logged as the home page', strpos((string) soSlowRequestLine(9000.0, '', 200, $timing, 1.0, 5000), 'slow request: / status') === 0, true);


// The hook itself, in a real web request (PHP's built-in server): one line for a slow page, none for a fast one, and a slow failed database
// connection (the 503 page) is logged too. A temporary router maps the clean path to the page file; the server's error output is read back.
$root = dirname(__DIR__);
$router = sys_get_temp_dir() . '/so-slowlog-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/([a-z0-9-]+)\$#', \$p, \$m) && is_file(" . var_export($root, true) . " . '/' . \$m[1] . '.php')) { \$_SERVER['SCRIPT_NAME'] = '/' . \$m[1] . '.php'; \$_SERVER['SCRIPT_FILENAME'] = " . var_export($root, true) . " . '/' . \$m[1] . '.php'; require \$_SERVER['SCRIPT_FILENAME']; return true; }\nreturn false;\n");
$serve = function (string $extraEnv, string $path) use ($router): array {
    $port = 8700 + random_int(0, 90);
    $err = sys_get_temp_dir() . '/so-slowlog-err-' . getmypid() . '-' . $port . '.log';
    $env = $extraEnv . ' SO_SLUG_MIN_ROWS=-1 SO_AUTO_MIGRATE=0 ';
    foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env .= $k . '=test-placeholder '; }
    $proc = proc_open('exec env ' . $env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', $err, 'w']], $pipes);
    usleep(900000);
    $ctx = stream_context_create(['http' => ['timeout' => 40, 'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0]]);
    @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $hd) { if (preg_match('#^HTTP/\S+ (\d{3})#', $hd, $m)) $code = (int) $m[1]; }
    usleep(300000);
    proc_terminate($proc); proc_close($proc);
    $log = (string) @file_get_contents($err);
    @unlink($err);
    return [$code, array_values(array_filter(explode("\n", $log), fn($l) => strpos($l, 'slow request:') !== false))];
};
[$c1, $lines1] = $serve('SO_SLOW_LOG_MS=1', '/worry-free-guarantee?visitor=secretvalue');
slowLogCheck('a slow page answers 200', $c1, 200);
slowLogCheck('a slow page writes exactly one slow request line', count($lines1), 1);
slowLogCheck('the line names the path and the status', strpos($lines1[0] ?? '', 'slow request: /worry-free-guarantee status 200 took ') !== false, true);
slowLogCheck('the line carries the timing breakdown with the connection time', preg_match('/\[app;dur=\d+.*dbconn;dur=\d+.*\]$/', $lines1[0] ?? '') === 1, true);
slowLogCheck('the line does not cost a database query (no query count in it)', strpos($lines1[0] ?? '', 'dbq;') === false, true);
slowLogCheck('the query string never reaches the log', strpos($lines1[0] ?? '', 'secretvalue') === false, true);
[$c2, $lines2] = $serve('SO_SLOW_LOG_MS=600000', '/worry-free-guarantee');   // far above any real page time, so a slow CI runner cannot make this flaky
slowLogCheck('a page under the limit writes no line', [$c2, count($lines2)], [200, 0]);
[$c3, $lines3] = $serve('SO_SLOW_LOG_MS=1 DB_PASS=wrong-password-for-the-test', '/worry-free-guarantee');
slowLogCheck('a failed database connection answers 503', $c3, 503);
slowLogCheck('a failed database connection is logged as a slow request too', strpos($lines3[0] ?? '', 'slow request: /worry-free-guarantee status 503 took ') !== false, true);
@unlink($router);

echo $fail ? "$fail failure(s)\n" : "slow request log: all passed\n"; exit($fail ? 1 : 0);
