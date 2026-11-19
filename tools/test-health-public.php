<?php
/**
 * CI: the public health page shows only what an uptime monitor needs (ok, time, commit, commitAgeSeconds). The detail (migrations, the
 * TicketNetwork budget and traffic mix, image queue, deploy error) needs the HEALTH_TOKEN header. Audit 16 Nov 2026: it was public.
 * Run: php tools/test-health-public.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function hpCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

$src = (string) file_get_contents(__DIR__ . '/../ajax/health.php');
hpCheck('the token is compared in constant time', strpos($src, 'hash_equals($token, $sent)') !== false);
hpCheck('an empty token never matches', strpos($src, "\$token !== '' && \$sent !== ''") !== false);
hpCheck('the public answer is cut down to four fields', strpos($src, "array_flip(['ok', 'time', 'commit', 'commitAgeSeconds'])") !== false);

// Run the real page as a web request (not CLI) with and without the token, through the PHP built-in server.
$root = dirname(__DIR__);
$port = 8190 + random_int(0, 50);
$env = 'HEALTH_TOKEN=zq-test-token ';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) {   // the CI job sets only the database
    if ((string) getenv($k) === '') $env .= $k . '=test-placeholder ';
}
$proc = proc_open('exec env ' . $env . 'php -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg($root), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
usleep(800000);
$get = function (array $headers) use ($port) {
    $ctx = stream_context_create(['http' => ['header' => implode("\r\n", $headers), 'timeout' => 20, 'ignore_errors' => true]]);
    return json_decode((string) @file_get_contents('http://127.0.0.1:' . $port . '/ajax/health.php', false, $ctx), true);
};
$anon = $get([]);
$bad = $get(['X-Health-Token: wrong']);
$good = $get(['X-Health-Token: zq-test-token']);
proc_terminate($proc); proc_close($proc);   // exec: the server is the child itself, so this stops it (no orphan left)
hpCheck('anonymous: only the four public fields', is_array($anon) && array_keys($anon) === array_values(array_intersect(array_keys($anon), ['ok', 'time', 'commit', 'commitAgeSeconds'])) && isset($anon['ok']), json_encode($anon));
hpCheck('anonymous: no migration, ticket or image detail', is_array($anon) && !isset($anon['migrations']) && !isset($anon['ticketnetwork']) && !isset($anon['images']) && !isset($anon['deployError']));
hpCheck('a wrong token gets the public answer', is_array($bad) && !isset($bad['ticketnetwork']) && !isset($bad['migrations']));
hpCheck('the right token gets the detail', is_array($good) && isset($good['migrations']) && isset($good['ticketnetwork']));

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: public health page shows the minimum\n";
