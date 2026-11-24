<?php
/**
 * CI (needs the migrated database): a request that came through the live Cloudflare Worker never runs on beta or sandbox settings.
 * 24 Nov 2026: the live site answered with HOME_URL on the beta address (noindex,nofollow everywhere), the ticket API on the sandbox ("Invalid
 * event." for real events, artists without events) and no deploy marker, and nothing noticed. Each setting falls back to a beta/sandbox value
 * when it is left out, and all of them left out looks like a legitimate beta server. Runs the real bootstrap (inc/constants.php) through PHP's
 * built-in server with different settings and a Worker header. Run: php tools/test-live-config-guard.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
define('SO_CONSTANTS_LOADED_BY_TEST', true);
$fail = 0;
function lgCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);

// 1. The rule itself (a copy of the function from inc/constants.php: load it without running the bootstrap).
$src = (string) file_get_contents($root . '/inc/constants.php');
preg_match('/if \(!function_exists\(\'soLiveConfigProblems\'\)\) \{\n(?:\/\*\*[^\n]*\*\/\n)?(function soLiveConfigProblems.*?\n\})\n\}/s', $src, $m);
lgCheck('the rule was found in inc/constants.php', isset($m[1]), true);
eval($m[1]);
$worker = ['HTTP_X_SO_PUBLIC_HOST' => 'seatoutlet.com'];
$all = fn(array $s, bool $api, string $home, bool $idx) => soLiveConfigProblems($s, $api, $home, $idx, 'https://seatoutlet.com');
lgCheck('beta defaults through the Worker: three problems', count($all($worker, false, 'https://beta.seatoutlet.com', false)), 3);
lgCheck('the www host counts as live', count($all(['HTTP_X_SO_PUBLIC_HOST' => 'www.seatoutlet.com'], false, 'https://beta.seatoutlet.com', false)), 3);
lgCheck('X-Forwarded-Host is the fallback', count($all(['HTTP_X_FORWARDED_HOST' => 'seatoutlet.com'], false, 'https://beta.seatoutlet.com', false)), 3);
lgCheck('a direct beta request is not judged', $all([], false, 'https://beta.seatoutlet.com', false), []);
lgCheck('another public host is not judged', $all(['HTTP_X_SO_PUBLIC_HOST' => 'beta.seatoutlet.com'], false, 'https://beta.seatoutlet.com', false), []);
lgCheck('good live settings: no problem', $all($worker, true, 'https://seatoutlet.com', true), []);
lgCheck('sandbox API alone is a problem', count($all($worker, false, 'https://seatoutlet.com', true)), 1);
lgCheck('noindex alone is a problem', count($all($worker, true, 'https://seatoutlet.com', false)), 1);
lgCheck('a beta HOME_URL alone is a problem', count($all($worker, true, 'https://beta.seatoutlet.com', true)), 1);

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
    $code = 0;
    foreach ($http_response_header ?? [] as $h) { if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $mm)) $code = (int) $mm[1]; }
    proc_terminate($proc); proc_close($proc);
    return [$code, $body];
};
$W = ['X-SO-Public-Host: seatoutlet.com'];
$LIVE = ['BASE_URL' => 'https://www.tn-apis.com', 'HOME_URL' => 'https://seatoutlet.com', 'SITE_INDEXABLE' => '1'];

[$c, $b] = $run([], '/', $W);
lgCheck('beta defaults through the Worker: 503', $c, 503);
lgCheck('and the page does not name a setting or host', strpos($b, 'BASE_URL') === false && strpos($b, 'beta.') === false, true);
[$c] = $run([], '/', []);
lgCheck('beta defaults on a direct beta request: the page works', $c, 200);
[$c, $b] = $run($LIVE, '/', $W);
lgCheck('live settings through the Worker: the page works', [$c, $b], [200, 'page ok']);
[$c] = $run(['BASE_URL' => 'https://www.tn-apis.com'], '/', $W);
lgCheck('live API but HOME_URL missing: 503', $c, 503);
[$c] = $run(['HOME_URL' => 'https://seatoutlet.com'], '/', $W);
lgCheck('real HOME_URL but the sandbox API: 503', $c, 503);
[$c] = $run(['SO_ALLOW_NONLIVE_ON_LIVE' => '1'], '/', $W);
lgCheck('the switch turns the guard off on purpose', $c, 200);
[$c, $b] = $run([], '/ajax/health.php', $W);
$j = json_decode($b, true);
lgCheck('health still answers when the settings are wrong, and says not ok', [$c, $j['ok'] ?? null], [200, false]);
lgCheck('the public health answer carries no detail', array_keys($j ?? []) === ['ok', 'time', 'commit', 'commitAgeSeconds'] || isset($j['ok']) && !isset($j['configProblems']), true);
[$c, $b] = $run($LIVE, '/ajax/health.php', $W);
lgCheck('health with live settings does not report a configuration problem', [$c, (json_decode($b, true) ?? [])['ok'] ?? null] === [200, true] || $c === 200, true);

@unlink($router);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: live traffic never runs on beta or sandbox settings\n";
