<?php
/**
 * The sitemap crawl must finish on a busy site. 24 Nov 2026 on live: the crawl's calls were refused by our own call budget again and again
 * (over_budget 51,712 against 3,615 live calls in a day), each refusal counted as an API failure, and after 12 in a row the crawl restarted
 * from page 1, so the sitemap (still listing beta addresses) was never rebuilt. Now the crawl waits for a budget token from cron
 * (tnBudgetPrepay) and a budget refusal never counts toward a restart. No network, no database. Run: php tools/test-sitemap-budget.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function sbCheck(string $name, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fail++; }

require_once $root . '/inc/tn-budget.php';

// 1. tnBudgetPrepay against a real bucket file in a scratch budget folder.
$dir = sys_get_temp_dir() . '/so-sb-' . getmypid();
@mkdir($dir, 0700, true);
putenv('TN_BUDGET_DIR=' . $dir);
putenv('TN_CALLS_PER_SECOND=10');   // burst 20; a background caller needs 1 + 20/2 = 11 tokens, so an empty bucket needs 1.1 s to refill
$bucket = $dir . '/tn_bucket.json';
$empty = function () use ($bucket) { file_put_contents($bucket, json_encode(['tokens' => 0.0, 't' => microtime(true)])); };

$empty();
$GLOBALS['tn_budget_prepaid'] = false;
$t0 = microtime(true);
$got = tnBudgetPrepay(0.3);
sbCheck('an empty budget: a short wait gives up (no call is made)', $got === false && empty($GLOBALS['tn_budget_prepaid']));
sbCheck('and it waited, not failed on the spot', microtime(true) - $t0 >= 0.25);

$empty();
$t0 = microtime(true);
$got = tnBudgetPrepay(4.0);
$took = microtime(true) - $t0;
sbCheck('a longer wait gets the token once the bucket refills (about 1.1 s)', $got === true && $took >= 0.9 && $took < 3.5);
sbCheck('the token is marked as already paid for the next call', !empty($GLOBALS['tn_budget_prepaid']));
$state = json_decode((string) file_get_contents($bucket), true);
// Taken as soon as the bucket reached the 11 a background caller needs (polled every 0.25 s at 10 a second): one token, so 10 to 12.5 remain.
sbCheck('exactly one token was taken when the threshold was reached', is_array($state) && (float) $state['tokens'] >= 9.9 && (float) $state['tokens'] <= 12.6);

putenv('TN_CALLS_PER_SECOND=0');
$GLOBALS['tn_budget_prepaid'] = false;
sbCheck('budget off (rate 0): no wait', tnBudgetPrepay(0.0) === true);
putenv('TN_CALLS_PER_SECOND');
putenv('TN_BUDGET_DIR');
array_map('unlink', glob($dir . '/*') ?: []);
@rmdir($dir);

// 2. The wiring: the prepaid token is used once, a budget refusal is recorded, and the crawl treats it as a wait, never a failure.
$fn = (string) file_get_contents($root . '/functions.php');
sbCheck('tnBucketAllow uses a prepaid token once instead of taking another',
    strpos($fn, "if (!empty(\$GLOBALS['tn_budget_prepaid'])) { \$GLOBALS['tn_budget_prepaid'] = false; return true; }") !== false
    && strpos($fn, "if (!empty(\$GLOBALS['tn_budget_prepaid']))") < strpos($fn, 'return tnBudgetWait(tnCallerClass(), tnCallsPerSecond(), tnBudgetDir());'));
sbCheck('a budget refusal is recorded before it is counted', strpos($fn, "\$GLOBALS['tn_last_refusal'] = 'budget';") !== false
    && strpos($fn, "\$GLOBALS['tn_last_refusal'] = 'budget';") < strpos($fn, "tnCount('over_budget');"));

$sm = (string) file_get_contents($root . '/inc/sitemap-build.php');
$prepay = strpos($sm, 'tnBudgetPrepay(min(20.0');
$call = strpos($sm, "tnRequest('/catalog/v2/events/'");
$waitBranch = strpos($sm, "=== 'budget') {");
$failBranch = strpos($sm, "if (++\$s['fails'] >= 12)");
sbCheck('from cron the crawl waits for a token before each page', $prepay !== false && $call !== false && $prepay < $call && strpos($sm, "PHP_SAPI === 'cli' && function_exists('tnBudgetPrepay')") !== false);
sbCheck('a budget refusal is handled before the failure counter, and never touches it', $waitBranch !== false && $failBranch !== false && $waitBranch < $failBranch
    && strpos(substr($sm, $waitBranch, $failBranch - $waitBranch), "fails") === false);
sbCheck('an unused prepaid token is never carried over to another call', strpos($sm, "\$GLOBALS['tn_budget_prepaid'] = false;") > $call);
sbCheck('the refusal marker is cleared before each page call', strpos($sm, "\$GLOBALS['tn_last_refusal'] = '';") !== false && strpos($sm, "\$GLOBALS['tn_last_refusal'] = '';") < $call);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "sitemap budget: all passed\n";
