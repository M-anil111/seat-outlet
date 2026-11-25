<?php
/**
 * The web server's page cache (nginx fastcgi_cache, since 25 Nov 2026). No CDN sits in front of seatoutlet.com any more, so every page
 * view rebuilt the page and called the ticket API: 72% of visitor pages were served without live data because the call budget ran out.
 * Pages now tell nginx how long it may keep them (X-Accel-Expires): a good page 120 s, a 404 60 s, and 0 for a page built without API
 * data or one that ends with any other status. Runs the real bootstrap through PHP's server (needs the CI database), and checks the Vhost.
 * Run: php tools/test-page-cache.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function pageCacheCheck(string $name, $got, $want): void {
    global $fail;
    $ok = $got === $want;
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . ($ok ? '' : ' (got ' . json_encode($got) . ', want ' . json_encode($want) . ')') . "\n";
    if (!$ok) $fail++;
}

// 1. The Vhost keeps pages only on the page's own say-so, and never for admin, checkout, non-GET or a signed-in admin.
$vh = (string) file_get_contents($root . '/deploy/nginx/seatoutlet.com.vhost');
pageCacheCheck('vhost declares the cache zone', strpos($vh, 'fastcgi_cache_path /var/cache/nginx/seatoutlet ') !== false && strpos($vh, 'keys_zone=seatoutlet_pages:') !== false, true);
pageCacheCheck('the PHP location uses it, keyed by method, host and full address', strpos($vh, 'fastcgi_cache seatoutlet_pages;') !== false && strpos($vh, 'fastcgi_cache_key "$scheme$request_method$host$request_uri";') !== false, true);
pageCacheCheck('admin, checkout, order, sign-up and search pages, non-GET and a signed-in admin are never cached',
    strpos($vh, 'if ($request_method !~ ^(GET|HEAD)$) { set $so_no_cache 1; }') !== false
    && strpos($vh, '^/(admin|checkout|order-confirmation|thank-you|unsubscribe|newsletter|search|cron|tools)') !== false
    && strpos($vh, 'if ($http_cookie ~* "admin_session") { set $so_no_cache 1; }') !== false
    && strpos($vh, 'fastcgi_cache_bypass $so_no_cache;') !== false && strpos($vh, 'fastcgi_no_cache $so_no_cache;') !== false, true);
pageCacheCheck('the no-cache rules run before the clean-address rewrites (a rewrite with "last" ends the server-level rules)',
    strpos($vh, 'set $so_no_cache 0;') < strpos($vh, 'rewrite ^/artist/'), true);
pageCacheCheck('no fixed cache time overrides the page (no fastcgi_cache_valid, no ignored headers)', strpos($vh, 'fastcgi_cache_valid') === false && strpos($vh, 'fastcgi_ignore_headers') === false, true);
pageCacheCheck('an expired page is answered from the old copy while one request rebuilds it', strpos($vh, 'fastcgi_cache_use_stale error timeout updating http_500 http_503;') !== false && strpos($vh, 'fastcgi_cache_background_update on;') !== false && strpos($vh, 'fastcgi_cache_lock on;') !== false, true);

// 2. The real headers, from the real bootstrap and sendPageCacheHeaders().
$router = sys_get_temp_dir() . '/so-pc-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\nchdir(" . var_export($root, true) . ");\nrequire " . var_export($root . '/functions.php', true) . ";\n"
    . "if (isset(\$_GET['status'])) http_response_code((int) \$_GET['status']);\n"
    . "if (isset(\$_GET['degraded_before'])) \$GLOBALS['tn_degraded'] = true;\n"
    . "sendPageCacheHeaders();\n"
    . "if (isset(\$_GET['degraded_after'])) \$GLOBALS['tn_degraded'] = true;\n"
    . "if (isset(\$_GET['late'])) http_response_code((int) \$_GET['late']);\n"
    . "echo 'page';\nreturn true;\n");
$base = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'PATH', 'HOME', 'TMPDIR'] as $k) { if (getenv($k) !== false) $base[$k] = getenv($k); }
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { $base[$k] = 'test-placeholder'; }
$base['HOME_URL'] = 'https://seatoutlet.com';
$base['SITE_INDEXABLE'] = '1';
$env = '';
foreach ($base as $k => $v) { $env .= $k . '=' . escapeshellarg($v) . ' '; }
$sock = stream_socket_server('tcp://127.0.0.1:0');
$port = (int) substr(strrchr(stream_socket_get_name($sock, false), ':'), 1);
fclose($sock);
$proc = proc_open('exec env -i ' . $env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
usleep(900000);
$get = function (string $path) use ($port): array {
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'ignore_errors' => true, 'header' => 'Host: seatoutlet.com']]);
    @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
    $h = ['code' => 0, 'accel' => null, 'cc' => null];
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $line, $m)) $h['code'] = (int) $m[1];
        if (stripos($line, 'X-Accel-Expires:') === 0) $h['accel'] = trim(substr($line, 16));
        if (stripos($line, 'Cache-Control:') === 0) $h['cc'] = trim(substr($line, 14));
    }
    return $h;
};
try {
    $h = $get('/');
    pageCacheCheck('a good page: kept 120 s by the server cache', [$h['code'], $h['accel']], [200, '120']);
    $h = $get('/?status=404');
    pageCacheCheck('a 404: kept 60 s', [$h['code'], $h['accel']], [404, '60']);
    $h = $get('/?degraded_before=1');
    pageCacheCheck('a page known to be without API data at the start: never kept', [$h['accel'], $h['cc']], ['0', 'no-store']);
    $h = $get('/?degraded_after=1');
    pageCacheCheck('a page that lost its API data while it rendered: never kept (corrected at the end)', [$h['accel'], $h['cc']], ['0', 'no-store']);
    $h = $get('/?late=503');
    pageCacheCheck('a page that ends as a 503 after the header was chosen: never kept', [$h['code'], $h['accel'], $h['cc']], [503, '0', 'no-store']);
    $h = $get('/?status=500');
    pageCacheCheck('any other status from the start: never kept', [$h['accel'], $h['cc']], ['0', 'no-store']);
} finally {
    proc_terminate($proc); proc_close($proc);
    @unlink($router);
}

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "page cache: all passed\n";
