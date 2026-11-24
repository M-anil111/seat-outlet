<?php
/**
 * Production without the Cloudflare Worker (24 Nov 2026 on): seatoutlet.com is its own CloudPanel site, served directly by nginx and PHP,
 * with its own inc/env.local.php (HOME_URL https://seatoutlet.com). Beta is a separate install. This test runs the real bootstrap through
 * PHP's built-in server and checks that:
 *   - a request for the public host is served (no 503 because the Worker's X-SO-Public-Host header is missing: the guard PR 300 added
 *     would have taken the live site down at its first deploy);
 *   - www goes to the bare domain, and a client-sent X-Forwarded-Host can never choose the redirect target (open redirect);
 *   - the TicketNetwork call budget and token can be shared by two installs (TN_BUDGET_DIR, TN_TOKEN_DIR): group-writable files;
 *   - nothing in the code still reads the Worker's header.
 * Needs the CI database (DB_* variables), like the other bootstrap tests. Run: php tools/test-direct-production.php
 */
$root = dirname(__DIR__);
$fail = 0;
function directProdCheck(string $name, $got, $want): void {
    global $fail;
    $ok = $got === $want;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' (got ' . json_encode($got) . ', want ' . json_encode($want) . ')') . "\n";
    if (!$ok) $fail++;
}

// 1. Source: no Worker dependency left in the request path.
$constants = file_get_contents($root . '/inc/constants.php');
$health = file_get_contents($root . '/ajax/health.php');
directProdCheck('inc/constants.php no longer 503s the public host', strpos($constants, 'soMisroutedPublicHost') === false && strpos($constants, 'X_SO_PUBLIC_HOST') === false, true);
directProdCheck('health no longer reports the public host as a problem', strpos($health, 'soMisroutedPublicHost') === false && strpos($health, 'SO_CONFIG_GUARD_SKIP') === false, true);
$php = '';
foreach (array_merge(glob($root . '/*.php'), glob($root . '/inc/*.php'), glob($root . '/ajax/*.php'), glob($root . '/admin/includes/*.php')) as $f) { $php .= file_get_contents($f); }
directProdCheck('no code reads the Worker header X-SO-Public-Host', strpos($php, 'HTTP_X_SO_PUBLIC_HOST') === false, true);
directProdCheck('no code trusts a client X-Forwarded-Host', strpos($php, 'HTTP_X_FORWARDED_HOST') === false, true);

// 1b. The reference Vhost (deploy/nginx/seatoutlet.com.vhost) keeps the rules the Worker used to supply.
$vh = (string) file_get_contents($root . '/deploy/nginx/seatoutlet.com.vhost');
directProdCheck('vhost: extensionless pages reach <path>.php through a named location (never $uri.php in try_files, which would serve PHP source)',
    strpos($vh, 'try_files $uri $uri/ @ext;') !== false && strpos($vh, 'rewrite ^/(.+?)/?$ /$1.php last;') !== false && preg_match('/try_files[^;]*\$uri\.php/', $vh) === 0, true);
directProdCheck('vhost: artist, event, location, holiday and blog addresses are rewritten', strpos($vh, '/performer.php?slug=$1') !== false && strpos($vh, '/$1.php?slug=$2&loc=$3') !== false
    && strpos($vh, '/holiday-city.php?holiday=$1&slug=$2') !== false && strpos($vh, '/blog.php?slug=$1') !== false && strpos($vh, '(event|venue|city|category|county)') !== false, true);
directProdCheck('vhost: repository, code and secrets folders answer 404', strpos($vh, 'location ~ ^/(docs|deploy|tools|db|cron|cache|vendor|phpmailer|inc|tests?|node_modules)(/|$) { return 404; }') !== false
    && strpos($vh, 'location ~ /\.(?!well-known/) { return 404; }') !== false && strpos($vh, 'location ~ ^/admin/includes(/|$) { return 404; }') !== false, true);
directProdCheck('vhost: www and http go to https://seatoutlet.com, robots.txt is robots.php, branded 404', strpos($vh, 'return 301 https://seatoutlet.com$request_uri;') !== false
    && strpos($vh, 'location = /robots.txt { rewrite ^ /robots.php last; }') !== false && strpos($vh, 'error_page 404 /404.php;') !== false, true);
directProdCheck('vhost: PHP runs in the HTTPS block itself (no Varnish or port 8080 hop, so PHP sees the visitor\'s own address)',
    strpos($vh, 'varnish') === false && strpos($vh, 'listen 8080') === false && strpos($vh, 'fastcgi_pass 127.0.0.1:{{php_fpm_port}};') !== false && strpos($vh, 'fastcgi_intercept_errors off;') !== false, true);
directProdCheck('vhost: archives and backups in the web root answer 404 (a source zip was downloadable on 24 Nov 2026)',
    strpos($vh, '|gz|zip|tar|tgz|7z|rar|bz2|xz)$ { return 404; }') !== false, true);
foreach (['concert-tickets-for-sale.php', 'performer.php', 'event.php', 'holiday-city.php', 'artist-city.php', 'blog.php', 'robots.php', '404.php', 'county.php'] as $page) {
    if (!is_file($root . '/' . $page)) directProdCheck('vhost target exists: ' . $page, false, true);
}

// 2. Shared budget and token files (two installs, two users, one group).
require_once $root . '/inc/tn-budget.php';
$tmp = sys_get_temp_dir() . '/so-dp-' . getmypid();
@mkdir($tmp, 0770, true);
putenv('TN_BUDGET_DIR=' . $tmp);
directProdCheck('TN_BUDGET_DIR moves the budget files to the shared folder', tnBudgetDir(), $tmp . '/');
$old = umask(022);
tnBucketTake(false, 1.0, tnBudgetDir() . 'tn_bucket.json', 100.0);
umask($old);
clearstatcache();
directProdCheck('a new budget file is group read/write (the other install must take tokens from it)', fileperms($tmp . '/tn_bucket.json') & 0777, 0660);
directProdCheck('and its lock file too', fileperms($tmp . '/tn_bucket.json.lock') & 0777, 0660);
putenv('TN_BUDGET_DIR');
if (!function_exists('cache_dir')) { function cache_dir() { return '/cache-dir/'; } }
directProdCheck('without TN_BUDGET_DIR the budget stays in this install\'s cache folder', tnBudgetDir(), cache_dir());
$fn = file_get_contents($root . '/functions.php');
directProdCheck('the token file is 0660 in a shared TN_TOKEN_DIR and 0600 in the temp folder', strpos($fn, '@chmod($tmp, tnTokenDirShared() ? 0660 : 0600);') !== false, true);
directProdCheck('every budget wait uses the shared folder', substr_count($fn, 'tnBudgetWait(') - 1 === substr_count($fn, 'tnBudgetDir()') && strpos($fn, 'tnCallsPerSecond(), cache_dir()') === false, true);
directProdCheck('the bulk import takes its tokens from the same budget', strpos(file_get_contents($root . '/cron/tn-bulk-import.php'), "tnBudgetDir() . 'tn_bucket.json'") !== false, true);
array_map('unlink', glob($tmp . '/*'));
@rmdir($tmp);

// 3. The real bootstrap through PHP's server, on live settings.
$router = sys_get_temp_dir() . '/so-dp-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\n"
    . "if (\$p === '/ajax/health.php') { require " . var_export($root . '/ajax/health.php', true) . "; return true; }\n"
    . "require " . var_export($root . '/functions.php', true) . ";\nsoRedirectWwwHost();\necho 'page ok';\nreturn true;\n");
$base = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'PATH', 'HOME', 'TMPDIR'] as $k) { if (getenv($k) !== false) $base[$k] = getenv($k); }
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { $base[$k] = 'test-placeholder'; }
$run = function (array $extraEnv, string $path, array $headers) use ($router, $base): array {
    $env = '';
    foreach (array_merge($base, $extraEnv) as $k => $v) { $env .= $k . '=' . escapeshellarg($v) . ' '; }
    $sock = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr(stream_socket_get_name($sock, false), ':'), 1);
    fclose($sock);
    $proc = proc_open('exec env -i ' . $env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(900000);
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true, 'follow_location' => 0, 'header' => implode("\r\n", $headers)]]);
    $body = (string) @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
    $code = 0; $location = '';
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $mm)) $code = (int) $mm[1];
        if (stripos($h, 'Location:') === 0) $location = trim(substr($h, 9));
    }
    proc_terminate($proc); proc_close($proc);
    return [$code, $body, $location];
};
$LIVE = ['HOME_URL' => 'https://seatoutlet.com', 'SITE_INDEXABLE' => '1'];

[$c, $b] = $run($LIVE, '/', ['Host: seatoutlet.com']);
directProdCheck('THE PRODUCTION PATH: the public host, live settings, no proxy header: served', [$c, $b], [200, 'page ok']);
[$c, $b] = $run([], '/', ['Host: seatoutlet.com']);
directProdCheck('the public host on an install still on beta defaults: served too (no 503 guard)', [$c, $b], [200, 'page ok']);
[$c, , $loc] = $run($LIVE, '/concerts?x=1', ['Host: www.seatoutlet.com']);
directProdCheck('www goes to the bare domain, path and query kept', [$c, $loc], [301, 'https://seatoutlet.com/concerts?x=1']);
[$c, $b, $loc] = $run($LIVE, '/', ['Host: seatoutlet.com', 'X-Forwarded-Host: www.evil.example']);
directProdCheck('a client X-Forwarded-Host cannot redirect visitors to another site', [$c, $b, $loc], [200, 'page ok', '']);
[$c, , $loc] = $run($LIVE, '/', ['Host: www.evil.example']);
directProdCheck('an unknown www host is not redirected anywhere', $loc, '');
[$c] = $run([], '/', ['Host: beta.seatoutlet.com']);
directProdCheck('beta on its own address works', $c, 200);
[$c, $b] = $run($LIVE, '/ajax/health.php', ['Host: seatoutlet.com']);
$j = json_decode($b, true);
directProdCheck('health on the public host: ok', [$c, $j['ok'] ?? null], [200, true]);
directProdCheck('the public health answer keeps its four fields', array_keys($j ?? []), ['ok', 'time', 'commit', 'commitAgeSeconds']);

@unlink($router);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: production works served directly, with no Worker and no trusted proxy headers\n";
