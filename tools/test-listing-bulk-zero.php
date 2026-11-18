<?php
/**
 * CI (needs the migrated database): a category with no upcoming events answers 200 when the local catalog copy says so, even though the
 * call budget refused other lookups on the page; with no catalog copy (the feed really is down) the same page still answers 503.
 * SE Ranking audit 17 Nov 2026: /category/west-end and /category/cricket answered 5XX in the sitemap.
 * Runs the real pages through PHP's built-in server with the ticket call budget used up. Run: php tools/test-listing-bulk-zero.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
$fail = 0;
function lzCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }
$root = dirname(__DIR__);

// The rule itself, and where it is applied.
require_once $root . '/db/config.php';
require_once $root . '/inc/entity-pages.php';
$GLOBALS['tn_degraded'] = false;
lzCheck('not degraded: the listing did not fail', soListingUnanswered(['results' => []]) === false);
$GLOBALS['tn_degraded'] = true;
lzCheck('degraded, listing answered by the catalog copy: not a failure', soListingUnanswered(['results' => [], 'totalCount' => 0, '_bulk' => true]) === false);
lzCheck('degraded, listing from the live feed: a failure', soListingUnanswered(['results' => []]) === true);
lzCheck('degraded, no listing at all: a failure', soListingUnanswered([]) === true && soListingUnanswered(null) === true);
$GLOBALS['tn_degraded'] = false;
foreach (['category.php' => "soListingUnanswered(\$eventsResponse)", 'performer.php' => "soListingUnanswered(\$results)", 'inc/listing.php' => "soListingUnanswered(\$o['listingResponse'])", 'header.php' => 'soListingUnanswered($soListingResp)'] as $f => $needle) {
    lzCheck("$f uses the rule", strpos((string) file_get_contents("$root/$f"), $needle) !== false);
}
lzCheck('the category page passes its response to the shared renderer', strpos((string) file_get_contents($root . '/category.php'), "'listingResponse' => \$eventsResponse") !== false);

// The pages, end to end. Needs the fixtures (an empty category and one with events) and a fresh bulk copy state.
exec('php ' . escapeshellarg($root . '/tools/seed-critical-fixtures.php') . ' 2>&1', $out, $rc);
lzCheck('fixtures seeded', $rc === 0, implode(' ', $out));
$cache = $root . '/cache/';
foreach (glob($cache . 'tn_bucket*.json') ?: [] as $b) { @unlink($b); }
file_put_contents($cache . 'tn_bucket.json', json_encode(['tokens' => 0, 't' => microtime(true)]));   // the call budget is used up: every live lookup is refused
$router = sys_get_temp_dir() . '/so-lz-router-' . getmypid() . '.php';
file_put_contents($router, "<?php\n\$p = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\nchdir(" . var_export($root, true) . ");\nif (preg_match('#^/category/([a-z0-9-]+)\$#', \$p, \$m)) { \$_GET['slug'] = \$m[1]; \$_SERVER['SCRIPT_NAME'] = '/category.php'; require " . var_export($root . '/category.php', true) . "; return true; }\nreturn false;\n");
$serve = function (string $bulk) use ($router, $root) {
    $port = 8300 + random_int(0, 80);
    $env = 'TN_BULK_ENABLED=' . $bulk . ' TN_CALLS_PER_SECOND=0.00001 ';
    foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') $env .= $k . '=test-placeholder '; }
    $proc = proc_open($env . 'php -S 127.0.0.1:' . $port . ' ' . escapeshellarg($router), [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
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
[$cEmpty] = $get('/category/zq-empty'); [$cFull, $bFull] = $get('/category/zq-rock');
proc_terminate($proc);
lzCheck('catalog copy on: the empty category answers 200 (not 503)', $cEmpty === 200, (string) $cEmpty);
lzCheck('catalog copy on: a category with events answers 200 and lists them', $cFull === 200 && strpos($bFull, 'Zq Band Live') !== false, (string) $cFull);
[$proc, $get] = $serve('0');
[$dEmpty] = $get('/category/zq-empty'); [$dFull] = $get('/category/zq-rock');
proc_terminate($proc);
lzCheck('no catalog copy (feed really down): the empty category still answers 503', $dEmpty === 503, (string) $dEmpty);
lzCheck('no catalog copy (feed really down): a category with events answers 503 too', $dFull === 503, (string) $dFull);

exec('php ' . escapeshellarg($root . '/tools/seed-critical-fixtures.php') . ' --clean 2>&1');
@unlink($router);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: a catalog-copy answer of no events is not a feed failure\n";
