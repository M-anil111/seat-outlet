<?php
/**
 * CI: deploy/verify-production.sh (the read-only post-deploy check) passes against a site that has the PR #288/#289 fixes and fails, check
 * by check, against one that does not. Runs the script against a small fake site on this machine (no database, no TicketNetwork), in a
 * "good" and a "bad" mode. Also checks that a health token is sent but never printed.
 * Run: php tools/test-verify-production.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function vpCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? "\n$detail" : '') . "\n"; $fail++; } }

$tmp = sys_get_temp_dir() . '/so-vp-' . getmypid();
@mkdir($tmp, 0700, true);
file_put_contents($tmp . '/site.php', <<<'PHP'
<?php
$bad = getenv('VP_MODE') === 'bad';
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$page = function (string $robots, int $ads, string $extra = '') { echo '<!doctype html><html><head><meta name="robots" content="' . $robots . '"></head><body>', str_repeat('<ins class="adsbygoogle so-adunit"></ins>', $ads), $extra, '</body></html>'; };
if ($p === '/ajax/health.php') {
    header('Content-Type: application/json');
    if (($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '') === 'zq-secret-token-123') {
        echo json_encode(['ok' => true, 'commit' => 'abcd1234', 'migrations' => ['pending' => $bad ? 1 : 0, 'autoMigrate' => $bad], 'deployError' => null, 'ticketnetwork' => ['bulk' => ['fresh' => true], 'visitorNoDataRate' => 0.01, 'alert' => false]]);
    } else {
        echo json_encode(['ok' => true, 'time' => gmdate('c'), 'commit' => 'abcd1234']);
    }
    return true;
}
if ($p === '/event/keller-williams-pioneertown-ca-2026-11-05') { if ($bad) { http_response_code(503); } else { header('Location: /artist/keller-williams?so_notice=event-past', true, 302); } return true; }
if ($p === '/event/lenny-pearce-8048654') { http_response_code($bad ? 503 : 404); $page('noindex, follow', 0); return true; }
if ($p === '/category/west-end') { $bad ? $page('index, follow', 4) : $page('noindex, follow', 0); return true; }
if ($p === '/city/boston-ma') { $page('index, follow', 1, str_repeat('<div class="so-dates-wrap" data-so-subs>', $bad ? 2 : 1)); return true; }
if (in_array($p, ['/.git/HEAD', '/cron/backup-db.php', '/cache/tn_bucket.json', '/deploy/pull-deploy.sh'], true)) { if ($bad && $p === '/.git/HEAD') { echo "ref: refs/heads/main\n"; return true; } http_response_code(404); return true; }
if ($p === '/inc/env.local.php') { return true; }   // executed PHP: 200 with an empty body
http_response_code(404); $page('noindex, follow', $bad ? 2 : 0); return true;
PHP);
$freePort = function (): int { $s = stream_socket_server('tcp://127.0.0.1:0'); $n = (int) substr(strrchr((string) stream_socket_get_name($s, false), ':'), 1); fclose($s); return $n; };

$run = function (string $mode, string $args, string $extraEnv = '') use ($tmp, $root, $freePort) {
    $port = $freePort();
    $proc = proc_open('exec env VP_MODE=' . $mode . ' ' . escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $port . ' ' . escapeshellarg($tmp . '/site.php'), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    for ($i = 0; $i < 100; $i++) { $c = @fsockopen('127.0.0.1', $port, $en, $es, 0.2); if ($c) { fclose($c); break; } usleep(100000); }
    exec('env -u HTTPS_PROXY -u HTTP_PROXY -u https_proxy -u http_proxy SITE=http://127.0.0.1:' . $port . ' ' . $extraEnv . ' bash ' . escapeshellarg($root . '/deploy/verify-production.sh') . ' ' . $args . ' 2>&1', $out, $rc);
    proc_terminate($proc); proc_close($proc);
    return [$rc, implode("\n", $out)];
};

[$rc, $out] = $run('good', 'abcd1234', 'HEALTH_TOKEN=zq-secret-token-123');
vpCheck('a fixed site passes every check (exit 0)', $rc === 0, $out);
vpCheck('it checks the expected commit', strpos($out, 'PASS  deployed commit is abcd1234') !== false, $out);
vpCheck('with the token it checks auto-migrate and migrations', strpos($out, 'PASS  auto-migrate is off') !== false && strpos($out, 'PASS  no pending migration') !== false, $out);
vpCheck('the health token is never printed', strpos($out, 'zq-secret-token-123') === false);
vpCheck('an executed PHP file with an empty body is not counted as a leak', strpos($out, 'PASS  /inc/env.local.php returns an empty body') !== false, $out);

[$rc, $out] = $run('good', 'ffff0000');
vpCheck('a different deployed commit fails', $rc === 1 && strpos($out, 'FAIL  deployed commit is abcd1234, expected ffff0000') !== false, $out);
vpCheck('without a token the detailed checks are skipped, not failed', strpos($out, 'INFO  HEALTH_TOKEN not set') !== false, $out);

[$rc, $out] = $run('bad', '', 'HEALTH_TOKEN=zq-secret-token-123');
vpCheck('a site without the fixes fails (exit 1)', $rc === 1, $out);
foreach ([
    'FAIL  past event answered 503',
    'FAIL  old event id answered 503',
    "FAIL  empty category robots: 'index, follow'",
    'FAIL  empty category has 4 ad unit(s)',
    'FAIL  404 page has 2 ad unit(s)',
    'FAIL  city page: HTTP 200, 2 date strip(s)',
    'FAIL  /.git/HEAD is served',
    'FAIL  auto-migrate is off',
    'FAIL  no pending migration',
] as $line) {
    vpCheck('bad site reports: ' . $line, strpos($out, $line) !== false, $out);
}
vpCheck('the health token is never printed (bad site)', strpos($out, 'zq-secret-token-123') === false);

array_map('unlink', glob($tmp . '/*') ?: []); @rmdir($tmp);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: deploy/verify-production.sh passes a fixed site and reports each problem on an unfixed one\n";
