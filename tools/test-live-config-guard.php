<?php
/**
 * CI (needs the migrated database): the public host must only reach this code through the Cloudflare Worker.
 * The origin runs the real application under its own (beta) hostname with beta-style settings; the live Worker fetches it under that name, adds
 * X-SO-Public-Host and rewrites the pages. 24 Nov 2026: the DNS records of seatoutlet.com and www pointed straight at the origin (proxy off), so
 * visitors got raw pages (noindex,nofollow, beta addresses) from a server setup that was not the live application under that name, and nothing
 * noticed. A request for the public host that did not come through the Worker now answers 503. The case that must NEVER break: a Worker request
 * on the origin's normal beta-style settings. Runs the real bootstrap (inc/constants.php) through PHP's built-in server.
 * Run: php tools/test-live-config-guard.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function lgCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);

// 1. The rule itself (the function from inc/constants.php, loaded without running the bootstrap).
$src = (string) file_get_contents($root . '/inc/constants.php');
preg_match('/if \(!function_exists\(\'soMisroutedPublicHost\'\)\) \{\n(?:\/\*\*[^\n]*\*\/\n)?(function soMisroutedPublicHost.*?\n\})\n\}/s', $src, $m);
lgCheck('the rule was found in inc/constants.php', isset($m[1]), true);
eval($m[1]);
$pub = 'https://seatoutlet.com';
lgCheck('public host, no Worker header: misrouted', soMisroutedPublicHost(['HTTP_HOST' => 'seatoutlet.com'], $pub), true);
lgCheck('www host, no Worker header: misrouted', soMisroutedPublicHost(['HTTP_HOST' => 'www.seatoutlet.com'], $pub), true);
lgCheck('with a port: misrouted', soMisroutedPublicHost(['HTTP_HOST' => 'seatoutlet.com:443'], $pub), true);
lgCheck('public host through the Worker: fine', soMisroutedPublicHost(['HTTP_HOST' => 'seatoutlet.com', 'HTTP_X_SO_PUBLIC_HOST' => 'seatoutlet.com'], $pub), false);
lgCheck('the Worker\'s normal request (the beta host plus its header): fine', soMisroutedPublicHost(['HTTP_HOST' => 'beta.seatoutlet.com', 'HTTP_X_SO_PUBLIC_HOST' => 'seatoutlet.com'], $pub), false);
lgCheck('a direct request on the beta host: fine', soMisroutedPublicHost(['HTTP_HOST' => 'beta.seatoutlet.com'], $pub), false);
lgCheck('localhost: fine', soMisroutedPublicHost(['HTTP_HOST' => '127.0.0.1:8000'], $pub), false);
lgCheck('no host at all: fine', soMisroutedPublicHost([], $pub), false);

// 2. The real bootstrap through PHP's server.
$router = sys_get_temp_dir() . '/so-lg-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (\$p === '/ajax/health.php') { require " . var_export($root . '/ajax/health.php', true) . "; return true; }\nrequire " . var_export($root . '/functions.php', true) . ";\necho 'page ok';\nreturn true;\n");
$base = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'PATH', 'HOME', 'TMPDIR'] as $k) { if (getenv($k) !== false) $base[$k] = getenv($k); }
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { $base[$k] = 'test-placeholder'; }
$run = function (array $extraEnv, string $path, array $headers) use ($router, $base): array {
    $env = '';
    foreach (array_merge($base, $extraEnv) as $k => $v) { $env .= $k . '=' . escapeshellarg($v) . ' '; }
    $port = 8700 + random_int(0, 90);
    $proc = proc_open('exec env -i ' . $env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(900000);
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true, 'header' => implode("\r\n", $headers)]]);
    $body = (string) @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
    $code = 0; $retry = '';
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $mm)) $code = (int) $mm[1];
        if (stripos($h, 'Retry-After:') === 0) $retry = trim(substr($h, 12));
    }
    proc_terminate($proc); proc_close($proc);
    return [$code, $body, $retry];
};
$BETA_STYLE = [];   // what the origin really runs on: every setting at its beta default
$LIVE_HOST = ['Host: seatoutlet.com'];
$WORKER = ['Host: beta.seatoutlet.com', 'X-SO-Public-Host: seatoutlet.com', 'X-Forwarded-Host: seatoutlet.com'];

[$c, $b, $r] = $run($BETA_STYLE, '/', $LIVE_HOST);
lgCheck('the public host straight at the origin: 503 with Retry-After', [$c, $r], [503, '60']);
lgCheck('and the page names no setting, host or address', strpos($b, 'beta.') === false && strpos($b, 'HOME_URL') === false && strpos($b, 'Cloudflare') === false, true);
[$c, $b] = $run($BETA_STYLE, '/', ['Host: www.seatoutlet.com']);
lgCheck('www straight at the origin: 503', $c, 503);
[$c, $b] = $run($BETA_STYLE, '/', $WORKER);
lgCheck('THE NORMAL PRODUCTION PATH: a Worker request on beta-style settings works', [$c, $b], [200, 'page ok']);
[$c] = $run($BETA_STYLE, '/', ['Host: beta.seatoutlet.com']);
lgCheck('a direct request on the beta host works', $c, 200);
[$c] = $run($BETA_STYLE, '/', ['Host: 127.0.0.1']);
lgCheck('a request with no public host works', $c, 200);
[$c] = $run(['SO_ALLOW_DIRECT_PUBLIC_HOST' => '1'], '/', $LIVE_HOST);
lgCheck('the switch turns the guard off on purpose', $c, 200);
[$c, $b] = $run($BETA_STYLE, '/ajax/health.php', $LIVE_HOST);
$j = json_decode($b, true);
lgCheck('health still answers for the public host straight at the origin, and says not ok', [$c, $j['ok'] ?? null], [200, false]);
lgCheck('the public health answer keeps its four fields', array_keys($j ?? []), ['ok', 'time', 'commit', 'commitAgeSeconds']);
[$c, $b] = $run($BETA_STYLE, '/ajax/health.php', $WORKER);
lgCheck('health through the Worker answers', $c, 200);

@unlink($router);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: the public host never reaches the origin without the Worker, and the Worker path is untouched\n";
