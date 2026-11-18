<?php
/**
 * CI (needs the migrated database): an /event/ page for an event we already know is over redirects to the performer page instead of
 * answering 503 when the ticket call budget refuses the lookup. Search Console (18 Nov 2026) counted 11,582 "Server error (5xx)" pages;
 * a sample of the listed URLs showed past-dated and old-form event pages answering 503 after a 5 second wait.
 * Runs the real event gate (header.php) through PHP's built-in server with the call budget used up.
 * Event ids 9100201 and up; removed at the end. Run: php tools/test-event-over-redirect.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';

$fail = 0;
function eoCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);
$clean = function () {
    MYSQLI->query("DELETE FROM event_redirects WHERE event_id BETWEEN 9100201 AND 9100299");
};
$clean();
$ev = fn(int $id, string $name, string $date, int $perfId = 9100201, string $perf = 'Zq Over Band') => [
    'id' => $id, 'text' => ['name' => $name], 'date' => ['date' => $date . 'T19:30:00'],
    'city' => ['text' => ['name' => 'Austin']], 'stateProvince' => ['text' => ['abbr' => 'TX']],
    'performers' => [['id' => $perfId, 'name' => $perf]], 'defaultCategory' => ['path' => '.1986.1987.'],
];
$day = fn(int $n) => date('Y-m-d', time() + $n * 86400);

// The helper, with a fixed clock.
soEventRememberMany([$ev(9100201, 'Zq Over Old', $day(-30)), $ev(9100202, 'Zq Over Recent', $day(-2)), $ev(9100203, 'Zq Upcoming', $day(10)), $ev(9100204, 'Zq Today', $day(0))], 'visit');
$old = soEventKnownOver(9100201);
eoCheck('a past event is known over', $old !== null, true);
eoCheck('it carries the performer', [$old['performer_id'], $old['performer_name']], [9100201, 'Zq Over Band']);
eoCheck('and an age past the skip threshold', $old['age'] > SO_EVENT_OVER_SKIP_DAYS * 86400, true);
$recent = soEventKnownOver(9100202);
eoCheck('an event over two days ago is known over, but younger than the skip threshold', $recent !== null && $recent['age'] < SO_EVENT_OVER_SKIP_DAYS * 86400, true);
eoCheck('an upcoming event is not known over', soEventKnownOver(9100203), null);
eoCheck('an event dated today is not known over (30 hour margin: no time of day is stored)', soEventKnownOver(9100204), null);
eoCheck('an event never seen is not known over', soEventKnownOver(9100299), null);
eoCheck('an invalid id is not known over', soEventKnownOver(0), null);
$fixedNow = strtotime($day(0) . ' 00:00:00') + 29 * 3600;
eoCheck('29 hours after the date: not over yet', soEventKnownOver(9100204, $fixedNow), null);
eoCheck('31 hours after the date: over', soEventKnownOver(9100204, $fixedNow + 2 * 3600) !== null, true);
soEventRememberMany([$ev(9100205, 'Zq No Date', '')], 'bulk');
MYSQLI->query("UPDATE event_redirects SET event_date = NULL WHERE event_id = 9100205");
eoCheck('a row without a date is not known over', soEventKnownOver(9100205), null);

// The gate in header.php uses it, before and after the lookup.
$h = (string) file_get_contents($root . '/header.php');
eoCheck('header.php checks the remembered date before the lookup', strpos($h, 'soEventKnownOver($soEvId)') !== false && strpos($h, 'soEventKnownOver($soEvId)') < strpos($h, 'getTnEventById($soEvId)'), true);

// End to end: the call budget is used up, so every live lookup is refused.
$cache = $root . '/cache/';
foreach (glob($cache . 'tn_bucket*.json') ?: [] as $b) { @unlink($b); }
file_put_contents($cache . 'tn_bucket.json', json_encode(['tokens' => 0, 't' => microtime(true)]));
$router = sys_get_temp_dir() . '/so-eo-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/event/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/event.php'; require " . var_export($root . '/event.php', true) . "; return true; }\nreturn false;\n");
$env = 'TN_CALLS_PER_SECOND=0.00001 ';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env .= $k . '=test-placeholder '; }
$port = 8400 + random_int(0, 80);
$proc = proc_open($env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
usleep(900000);
$get = function (string $path) use ($port) {
    $ctx = stream_context_create(['http' => ['timeout' => 40, 'ignore_errors' => true, 'follow_location' => 0, 'max_redirects' => 0]]);
    @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
    $code = 0; $loc = '';
    foreach ($http_response_header ?? [] as $hd) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $hd, $m)) $code = (int) $m[1];
        if (stripos($hd, 'Location:') === 0) $loc = trim(substr($hd, 9));
    }
    return [$code, $loc];
};
[$c1, $l1] = $get('/event/zq-over-old-9100201');
eoCheck('over for weeks, budget used up: 302, not 503', $c1, 302);
eoCheck('it goes to the performer page, marked as a past event', strpos($l1, '/artist/') === 0 && strpos($l1, 'so_notice=event-past') !== false, true);
[$c2, $l2] = $get('/event/zq-over-recent-9100202');
eoCheck('over two days ago, lookup refused: 302 to the performer page, not 503', [$c2, strpos($l2, '/artist/') === 0], [302, true]);
[$c3] = $get('/event/zq-upcoming-9100203');
eoCheck('an upcoming event whose lookup is refused still answers 503 (retry later)', $c3, 503);
[$c4] = $get('/event/zq-never-seen-9100298');
eoCheck('an event nothing is known about, lookup refused: 503', $c4, 503);
proc_terminate($proc);
@unlink($router);
$clean();
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: a known-over event redirects instead of answering 503\n";
