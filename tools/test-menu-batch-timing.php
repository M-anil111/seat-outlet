<?php
/**
 * CI: (1) the menu asks the ticket API for its three hubs' categories in one parallel batch (soHubPrefetch), not three calls in a row, and the
 * batch and the reader build the same request so they share a cache key (a cold page used to wait 2 to 4 s for three round trips in a row);
 * (2) the Server-Timing header reports the page's time and its API calls. Run: php tools/test-menu-batch-timing.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/category-tree.php';

$fail = 0;
function mbCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);

// One request per hub, built in one place; the batch and the reader use it.
$hubs = array_keys(SO_HUB_ROOTS);
mbCheck('three hubs', count($hubs), 3);
$keys = [];
foreach ($hubs as $h) {
    [$ep, $params] = soHubCategoryRequest($h, 1);
    mbCheck("$h: endpoint", $ep, '/catalog/v2/categories');
    mbCheck("$h: page 1, 200 a page", [$params['page'], $params['perPage']], [1, 200]);
    $keys[tnCacheKey($ep, $params)] = true;
}
mbCheck('each hub has its own cache key', count($keys), 3);
$tree = (string) file_get_contents($root . '/inc/category-tree.php');
mbCheck('the reader builds its request with the same helper', strpos($tree, '[$endpoint, $params] = soHubCategoryRequest($hubHref, $page);') !== false, true);
mbCheck('the batch is one tnRequestMulti call', substr_count($tree, 'tnRequestMulti($batch)'), 1);
$menu = (string) file_get_contents($root . '/inc/menu.php');
$pre = strpos($menu, 'soHubPrefetch();'); $loop = strpos($menu, 'soHubSubcategories($m[\'href\'])');
mbCheck('the menu prefetches before it reads each hub', $pre !== false && $loop !== false && $pre < $loop, true);

// Server-Timing: rounded to 10 ms, no names or ids.
$GLOBALS['so_tn_stat'] = ['live' => 3, 'cached' => 7, 'ms' => 2403.0];
$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true) - 1.234;
$h = soServerTimingHeader();
mbCheck('Server-Timing shape', preg_match('/^app;dur=\d+, tn;dur=2400, tnlive;desc="3", tncached;desc="7"$/', $h) === 1, true);
preg_match('/app;dur=(\d+)/', $h, $m);
mbCheck('app time is about 1.23 s and a multiple of 10', [(int) $m[1] >= 1220 && (int) $m[1] <= 1290, (int) $m[1] % 10], [true, 0]);
// tnProfile counts without TN_PROFILE set.
putenv('TN_PROFILE'); unset($GLOBALS['so_tn_stat']);
tnProfile('/x', true, 0); tnProfile('/x', false, 400); tnProfile('/y', false, 600);
mbCheck('tnProfile counts cached and live calls', [$GLOBALS['so_tn_stat']['cached'], $GLOBALS['so_tn_stat']['live'], (int) $GLOBALS['so_tn_stat']['ms']], [1, 2, 1000]);
$fn = (string) file_get_contents($root . '/functions.php');
mbCheck('the output guard sends the header', strpos($fn, "header('Server-Timing: ' . soServerTimingHeader());") !== false, true);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: the menu batches its category calls and pages report Server-Timing\n";
