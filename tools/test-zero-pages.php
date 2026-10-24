<?php
// Zero-event pages and the sitemap (inc/entity-pages.php, inc/sitemap-build.php): an answer we could not get is never recorded as "zero",
// a real zero is, decisions expire, and the sitemap checks category pages against the bulk copy without a call per URL.
// Run: php tools/test-zero-pages.php
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../functions.php';
$fail = 0;
function zpCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$p = '/sports-city/zq-test-city-9300001';
$wipe = function () use ($p) { $m = cache_get(SO_ZERO_PAGES_KEY, 365 * 86400); if (is_array($m)) { unset($m[$p]); cache_set(SO_ZERO_PAGES_KEY, $m); } };
$wipe();

$GLOBALS['tn_degraded'] = false; unset($GLOBALS['so_bulk_served']);
soZeroPageNote($p, true);
zpCheck('a real zero is recorded', in_array($p, soZeroPages(), true));
soZeroPageNote($p, false);
zpCheck('a page that has events again is forgotten', !in_array($p, soZeroPages(), true));

$GLOBALS['tn_degraded'] = true;
soZeroPageNote($p, true);
zpCheck('an outage is not recorded as a zero', !in_array($p, soZeroPages(), true));
$GLOBALS['tn_degraded'] = false; $GLOBALS['so_bulk_served'] = true;
soZeroPageNote($p, true);
zpCheck('a list answered from the fallback copy is not recorded as a zero either', !in_array($p, soZeroPages(), true));
unset($GLOBALS['so_bulk_served']);

// a zero confirmed earlier is not wiped by a failure
soZeroPageNote($p, true);
$GLOBALS['tn_degraded'] = true;
soZeroPageNote($p, true);
zpCheck('a failure does not disturb a recorded zero', in_array($p, soZeroPages(), true));
$GLOBALS['tn_degraded'] = false;

// decisions expire
$m = cache_get(SO_ZERO_PAGES_KEY, 365 * 86400); $m[$p] = time() - (SO_ZERO_PAGES_TTL_DAYS + 1) * 86400; cache_set(SO_ZERO_PAGES_KEY, $m);
zpCheck('a zero older than ' . SO_ZERO_PAGES_TTL_DAYS . ' days is no longer used', !in_array($p, soZeroPages(), true));
$wipe();

// the sitemap build
$src = (string) file_get_contents(__DIR__ . '/../inc/sitemap-build.php');
zpCheck('category pages are checked against the bulk counts', strpos($src, 'tnBulkCategoryCounts()') !== false && substr_count($src, '$catHasEvents(') >= 2);
zpCheck('bulk off or stale leaves every category listed', strpos($src, '$bulkCats === null ||') !== false);
zpCheck('the sitemap makes no per-URL call (the only API read is the crawl page)', substr_count($src, 'tnRequest(') === 1);
exit($fail ? 1 : 0);
