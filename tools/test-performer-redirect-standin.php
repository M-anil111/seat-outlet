<?php
/**
 * CI (needs the migrated database): an artist page for a performer with no upcoming event answers 200 (the "no upcoming events" page,
 * noindex) when the live lookup is refused and the local catalog copy is ready, using the name and category event_redirects remembered
 * from its past events. With no catalog copy (the feed really is down) it still answers 503. Search Console (18 Nov 2026): ~18% of the
 * "Server error (5xx)" pages were artists, e.g. a performer whose only events had ended.
 * Performer ids 9100301 and up; removed at the end. Run: php tools/test-performer-redirect-standin.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/tn-bulk.php';

$fail = 0;
function psCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);
$clean = function () {
    MYSQLI->query("DELETE FROM event_redirects WHERE event_id BETWEEN 9100301 AND 9100399");
    MYSQLI->query("DELETE FROM url_slugs WHERE type = 'performer' AND ext_id BETWEEN 9100301 AND 9100399");
};
$clean();
$ev = fn(int $id, int $perfId, string $perf, string $path) => [
    'id' => $id, 'text' => ['name' => $perf . ' Live'], 'date' => ['date' => date('Y-m-d', time() - 40 * 86400) . 'T19:30:00'],
    'city' => ['text' => ['name' => 'Austin']], 'stateProvince' => ['text' => ['abbr' => 'TX']],
    'performers' => [['id' => $perfId, 'name' => $perf]], 'defaultCategory' => ['path' => $path],
];
soEventRememberMany([$ev(9100301, 9100301, 'Zq Past Act', '.1986.1987.'), $ev(9100302, 9100302, 'Zq No Category', '')], 'visit');

// The stand-in itself.
$p = tnBulkEntity(MYSQLI, 'performer', 9100301);
psCheck('a performer with only past events: name and category from event_redirects', $p, ['id' => 9100301, 'text' => ['name' => 'Zq Past Act'], 'defaultCategory' => ['path' => '.1986.1987.'], '_bulk' => true]);
psCheck('no remembered category: no stand-in (nothing made up)', tnBulkEntity(MYSQLI, 'performer', 9100302), null);
psCheck('a performer nothing is known about: no stand-in', tnBulkEntity(MYSQLI, 'performer', 9100399), null);
psCheck('venues and cities do not use event_redirects', [tnBulkEntity(MYSQLI, 'venue', 9100301), tnBulkEntity(MYSQLI, 'city', 9100301)], [null, null]);

// The page, end to end: bulk copy ready, call budget used up.
exec('php ' . escapeshellarg($root . '/tools/seed-critical-fixtures.php') . ' 2>&1', $out, $rc);
psCheck('fixtures seeded (a ready catalog copy)', $rc, 0);
$cache = $root . '/cache/';
foreach (glob($cache . 'tn_bucket*.json') ?: [] as $b) { @unlink($b); }
file_put_contents($cache . 'tn_bucket.json', json_encode(['tokens' => 0, 't' => microtime(true)]));
$router = sys_get_temp_dir() . '/so-ps-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/artist/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/performer.php'; require " . var_export($root . '/performer.php', true) . "; return true; }\nreturn false;\n");
$serve = function (string $bulk) use ($router) {
    $port = 8500 + random_int(0, 80);
    $env = 'TN_BULK_ENABLED=' . $bulk . ' TN_CALLS_PER_SECOND=0.00001 ';
    foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env .= $k . '=test-placeholder '; }
    $proc = proc_open('exec env ' . $env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(900000);
    $get = function (string $path) use ($port) {
        $ctx = stream_context_create(['http' => ['timeout' => 40, 'ignore_errors' => true]]);
        $body = (string) @file_get_contents('http://127.0.0.1:' . $port . $path, false, $ctx);
        $code = 0; foreach ($http_response_header ?? [] as $h) { if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) $code = (int) $m[1]; }
        return [$code, $body];
    };
    return [$proc, $get];
};
[$proc, $get] = $serve('1');
[$c1, $b1] = $get('/artist/zq-past-act-9100301');
proc_terminate($proc); proc_close($proc);   // exec: the server is the child itself, so this stops it (no orphan left)
psCheck('catalog copy on: the artist page answers 200 (not 503)', $c1, 200);
psCheck('it is the no-upcoming-events page: noindex', preg_match('/<meta name="robots" content="noindex/', $b1) === 1, true);
psCheck('it names the performer', strpos($b1, 'Zq Past Act') !== false, true);
psCheck('it carries no ad slot (a noindex page with nothing to list)', strpos($b1, 'so-ad so-ad--') === false, true);
[$proc, $get] = $serve('0');
[$c2] = $get('/artist/zq-past-act-9100301');
proc_terminate($proc); proc_close($proc);   // exec: the server is the child itself, so this stops it (no orphan left)
psCheck('no catalog copy (feed really down): still 503', $c2, 503);

exec('php ' . escapeshellarg($root . '/tools/seed-critical-fixtures.php') . ' --clean 2>&1');
$clean();
@unlink($router);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: an artist with no upcoming events answers 200 (noindex) from the catalog copy\n";
