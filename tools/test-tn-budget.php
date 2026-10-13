<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the shared TicketNetwork call budget, Retry-After handling and single-flight on cold keys.
 * Run: php tools/test-tn-budget.php
 */
require_once __DIR__ . '/../inc/tn-budget.php';

$fails = 0;
function tbCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$dir = sys_get_temp_dir() . '/so-tn-budget-test-' . getmypid();
@mkdir($dir, 0777, true);
$bucket = $dir . '/bucket.json';
@unlink($bucket); @unlink($bucket . '.lock');

// 1. Budget: at 2 calls a second the bucket holds 4 tokens (2 x 2 seconds of burst)
$t = 1000.0;
tbCheck('a visitor takes the whole burst', array_sum(array_map(fn() => tnBucketTake(false, 2.0, $bucket, $t) ? 1 : 0, range(1, 5))) === 4);
tbCheck('the fifth visitor call in the same instant is refused', tnBucketTake(false, 2.0, $bucket, $t) === false);
@unlink($bucket); @unlink($bucket . '.lock');

$t = 2000.0;
tbCheck('a cron may take only the upper half (3 of 4 tokens at burst 4)', tnBucketTake(true, 2.0, $bucket, $t) && tnBucketTake(true, 2.0, $bucket, $t) && !tnBucketTake(true, 2.0, $bucket, $t));
tbCheck('the held-back tokens are still there for a visitor', tnBucketTake(false, 2.0, $bucket, $t));
// one token is left at this point; one second at 2 a second adds 2, so three visitor calls fit and the fourth does not
tbCheck('tokens refill with time (one second at 2 a second adds 2)', tnBucketTake(false, 2.0, $bucket, $t + 1.0) && tnBucketTake(false, 2.0, $bucket, $t + 1.0) && tnBucketTake(false, 2.0, $bucket, $t + 1.0) && !tnBucketTake(false, 2.0, $bucket, $t + 1.0));
@unlink($bucket); @unlink($bucket . '.lock');

tbCheck('rate 0 turns the budget off', tnBucketTake(false, 0.0, $bucket, 3000.0) && tnBucketTake(true, 0.0, $bucket, 3000.0));
tbCheck('fails open when the bucket file cannot be locked (a counter fault never blocks a visitor)', tnBucketTake(false, 2.0, '/nonexistent-so-dir/x/bucket.json', 1.0));
@unlink($bucket); @unlink($bucket . '.lock');
tnBucketTake(true, 1.0, $bucket);   // real clock: two tokens at burst 2, the background call takes one and leaves one
$start = microtime(true);
tbCheck('a background call never waits for a token', tnBucketWait(true, 1.0, $bucket) === false && microtime(true) - $start < 0.5);

// 1b. Device scope: web and cron get their own tokens, so one refresh cannot revoke the other's
$role = PHP_SAPI === 'cli' ? 'cli' : 'web';   // this test runs under either SAPI, so the expected role follows the SAPI it runs in
putenv('TN_DEVICE_ID');
tbCheck('a process with no configured id asks for its own role scope', tnDeviceScope() === 'device_' . $role);
putenv('TN_DEVICE_ID=host-2');
tbCheck('TN_DEVICE_ID names the device and the role', tnDeviceScope() === 'device_host-2_' . $role);
putenv('TN_DEVICE_ID=bad id/../x');
tbCheck('the device id is cleaned for use in a scope and a file name', tnDeviceScope() === 'device_badidx_' . $role);
putenv('TN_DEVICE_ID');

// 2. Retry-After: numeric seconds are read; HTTP dates and other headers are not; the breaker length is clamped
tbCheck('Retry-After: 30 is read', tnRetryAfterFromHeader("Retry-After: 30\r\n") === 30);
tbCheck('a Retry-After HTTP date is not read as seconds', tnRetryAfterFromHeader('Retry-After: Wed, 21 Oct 2026 07:28:00 GMT') === null);
tbCheck('other headers are ignored', tnRetryAfterFromHeader("Content-Type: application/json\r\n") === null);
tbCheck('no Retry-After gives the default wait', tnThrottleWait(null, 60) === 60);
tbCheck('a short Retry-After is raised to 5 seconds', tnThrottleWait(1, 60) === 5);
tbCheck('a long Retry-After is capped at 300 seconds', tnThrottleWait(9999, 60) === 300);
tbCheck('a Retry-After inside the range is used as given', tnThrottleWait(45, 60) === 45);

// 3. Single-flight: one leader, followers wait for its stored answer, and stop when the leader stores nothing
$flight = $dir . '/key.flight';
$lead = tnFlightOpen($flight);
tbCheck('the first request leads', is_resource($lead));
tbCheck('a second request while the first is fetching is a follower', tnFlightOpen($flight) === false);
// The leader stores its answer and lets go of the lock on the second poll. The follower must then see the lock free and read the answer
// the leader stored, so the "free" branch of tnFlightWait runs with a real hit.
$stored = ['id' => 7];
$polls = 0;
$got = tnFlightWait($flight, function () use (&$polls, &$lead, $stored) {
    $polls++;
    if ($polls === 2) { tnFlightClose($lead); return false; }   // leader done: lock released, answer already stored (here, returned on the next read)
    return $polls >= 3 ? $stored : false;
}, 1.0);
tbCheck('a follower waits, sees the leader release the lock, and returns the stored answer', $got === $stored && $polls >= 3);
tbCheck('after the leader closes, a new request leads again', is_resource($nextLead = tnFlightOpen($flight)));
tnFlightClose($nextLead);

$lead2 = tnFlightOpen($flight);
$t0 = microtime(true);
$none = tnFlightWait($flight, fn() => false, 3.0);   // the leader still holds the lock; the follower gives up at its deadline
$elapsed = microtime(true) - $t0;
tbCheck('a follower gives up after its deadline when nothing is stored', $none === false && $elapsed >= 2.9 && $elapsed < 4.5);
tnFlightClose($lead2);

$lead3 = tnFlightOpen($flight);
tnFlightClose($lead3);
$t1 = microtime(true);
tbCheck('a follower stops early when the leader finished without storing anything', tnFlightWait($flight, fn() => false, 3.0) === false && microtime(true) - $t1 < 1.0);

// 4. Wiring in the live code
$fn = file_get_contents(__DIR__ . '/../functions.php');
tbCheck('the token request asks for the device scope', strpos($fn, "'scope' => 'default ' . tnDeviceScope()") !== false);
tbCheck('the token file and cache key are per device', strpos($fn, "CONSUMER_KEY . '|' . BASE_URL . '|' . tnDeviceScope()") !== false && strpos($fn, "TN_ACCESS_TOKEN_CACHE_KEY . '_' . tnDeviceScope()") !== false);
tbCheck('functions.php loads the budget helpers', strpos($fn, "require_once __DIR__ . '/inc/tn-budget.php';") !== false);
tbCheck('every live call passes the shared budget before it goes out', strpos($fn, 'if (!tnBucketAllow()) {') !== false && strpos($fn, 'tnBucketWait(tnBackgroundCaller()') !== false);
tbCheck('a batch of prefetched calls waits for tokens like one call would, instead of failing the third on the spot',
    strpos($fn, '$deadline = microtime(true) + 1.5;') !== false && strpos($fn, 'if (!tnBucketWait($background, tnCallsPerSecond(), $budgetFile, max(0.0, $deadline - microtime(true)))) {') !== false && strpos($fn, 'tnBucketTake(PHP_SAPI') === false);
unset($_SERVER['HTTP_X_SO_WARM']);
tbCheck('a cron is a background caller', tnBackgroundCaller() === true);   // this test runs on the CLI
tbCheck('the snapshot warmer tells the site its visits are background, and nothing counts them as visitor outcomes',
    strpos(file_get_contents(__DIR__ . '/../cron/warm-snapshots.php'), "CURLOPT_HTTPHEADER => ['X-SO-Warm: 1']") !== false && strpos($fn, "if (tnBackgroundCaller()) return;") !== false
    && strpos(file_get_contents(__DIR__ . '/../inc/tn-budget.php'), "(!empty(\$_SERVER['HTTP_X_SO_WARM']) && (\$_SERVER['HTTP_USER_AGENT'] ?? '') === 'SeatOutletSnapshotWarmer/1.0')") !== false);
tbCheck('the cold path is single-flight', strpos($fn, '$flight = tnFlightOpen($flightPath);') !== false && strpos($fn, 'tnFlightClose($flight);') !== false);
tbCheck('the breaker after a throttle uses Retry-After', strpos($fn, 'tnThrottleWait($GLOBALS[\'tn_retry_after\'] ?? null') !== false);
tbCheck('the header callback keeps Retry-After', strpos($fn, 'tnRetryAfterFromHeader($line)') !== false);

// 5. Breaker families: event lists, details and search pause together; reference data has its own circuit
tbCheck('an event list is in the events family', tnBreakerFamily('/catalog/v2/events/') === 'events');
tbCheck('an event detail is in the events family', tnBreakerFamily('/catalog/v2/events/8139366') === 'events');
tbCheck('event search is in the events family', tnBreakerFamily('/catalog/v2/events/search') === 'events');
tbCheck('a performer lookup is in the reference family', tnBreakerFamily('/catalog/v2/performers/1451') === 'reference');
tbCheck('the two families are the ones the breaker tracks', TN_BREAKER_FAMILIES === ['events', 'reference']);
$fnBreaker = file_get_contents(__DIR__ . '/../functions.php');
tbCheck('the request path checks the breaker of the call\'s own family in all five places', substr_count($fnBreaker, 'tnBreakerOpen(tnBreakerFamily($endpoint))') === 5);
tbCheck('the request path trips only the call\'s own family', substr_count($fnBreaker, ', tnBreakerFamily($endpoint));') >= 3);
tbCheck('performer lookups check the reference family', strpos($fnBreaker, "tnBreakerOpen('reference')") !== false);

// 6. Health summary: the no-data share and when it is worth an alert
$s = tnStatsSummary(['pages' => 120, 'live_call' => 400, 'no_data' => 30]);
tbCheck('the no-data share is per page, not per call (400 calls on 120 pages do not dilute it)', $s['pages'] === 120 && $s['noData'] === 30 && $s['rate'] === 0.25);
tbCheck('25% over 120 pages is an alert', $s['alert'] === true);
tbCheck('30% over 10 pages is not (too few pages to mean anything)', tnStatsSummary(['pages' => 10, 'no_data' => 3])['alert'] === false);
tbCheck('10% over 1000 pages is not', tnStatsSummary(['pages' => 1000, 'no_data' => 100])['alert'] === false);
$flap = tnStatsSummary(['pages' => 100, 'no_data' => 2, 'pages_suspect' => 30]);
tbCheck('a long empty-feed flap (30% of pages kept a stored list) is an alert even with a low no-data rate', $flap['suspectRate'] === 0.3 && $flap['alert'] === true);
$empty = tnStatsSummary([]);
tbCheck('an empty day is rate 0 and no alert', $empty['pages'] === 0 && $empty['rate'] === 0.0 && $empty['suspectRate'] === 0.0 && $empty['alert'] === false);
$left = tnStatsSummary(['pages' => 60, 'no_data' => 1442, 'pages_suspect' => 70]);   // the live file on the day "pages" was added
tbCheck('a no_data count older than the pages counter is clamped to the pages: the share is never over 100%', $left['noData'] === 60 && $left['rate'] === 1.0 && $left['suspect'] === 60 && $left['alert'] === true);
tbCheck('throttled and over-budget calls are reported separately (per call)', tnStatsSummary(['throttled' => 7, 'over_budget' => 12])['throttled'] === 7 && tnStatsSummary(['over_budget' => 12])['overBudget'] === 12 && $empty['throttled'] === 0 && $empty['overBudget'] === 0);
$fnStats = file_get_contents(__DIR__ . '/../functions.php');
tbCheck('a call our own budget holds back is counted (over_budget), a 429 is counted (throttled), in both request paths',
    substr_count($fnStats, "tnCount('over_budget');") === 2 && substr_count($fnStats, "if (!empty(\$r['throttled'])) tnCount('throttled');") === 2);
tbCheck('a throttle names the gateway code and message, so Sentry says which limit was hit', strpos($fnStats, "'throttled (HTTP ' . \$code . (\$why !== '' ? ', ' . substr(\$why, 0, 120) : '') . ')'") !== false);
tbCheck('a failed performer lookup is never kept in APCu (one throttled call used to 503 the artist page for an hour)',
    preg_match("/if \(function_exists\('apcu_store'\) && isset\(\\\$result\['id'\]\)\) \{\s*apcu_store\(\\\$cacheKey, \\\$result, 3600\);/", $fnStats) === 1);
tbCheck('an APCu entry without a record (kept before the guard, outliving the deploy) is dropped, not returned',
    strpos($fnStats, "if (\$found && is_array(\$cached) && isset(\$cached['id'])) {") !== false && strpos($fnStats, "if (\$found && function_exists('apcu_delete')) apcu_delete(\$cacheKey);") !== false);
$fnStats = file_get_contents(__DIR__ . '/../functions.php');
tbCheck('tnCount counts the page once (pages) and a kept stored list once per page (pages_suspect)', strpos($fnStats, "\$GLOBALS['tn_page_counted'] = true;\n        \$firstOfPage = true;") !== false && strpos($fnStats, "\$GLOBALS['tn_page_suspect'] = true;\n        \$firstSuspect = true;") !== false && tnCountKeys('empty_suspect', true, true, false, false) === ['empty_suspect', 'pages', 'pages_suspect']);
tbCheck('the health page and the cron read the counts under a shared lock', strpos($fnStats, 'function tnStatsToday(): array') !== false && strpos($fnStats, 'flock($fh, LOCK_SH)') !== false && strpos(file_get_contents(__DIR__ . '/../ajax/health.php'), 'tnStatsToday()') !== false && strpos(file_get_contents(__DIR__ . '/../cron/check-tn-health.php'), 'tnStatsToday()') !== false);

// 7. Who asked: the live Worker versus the beta address, crawlers versus visitors, and the counters that follow
$fwd = fn($h) => ['HTTP_X_FORWARDED_HOST' => $h];
tbCheck('a request through the live Worker (X-Forwarded-Host seatoutlet.com) is live', soViaLiveProxy($fwd('seatoutlet.com'), 'seatoutlet.com') === true);
tbCheck('the Worker\'s own header counts even when X-Forwarded-Host was rewritten to beta', soViaLiveProxy(['HTTP_X_SO_PUBLIC_HOST' => 'seatoutlet.com', 'HTTP_X_FORWARDED_HOST' => 'beta.seatoutlet.com'], 'seatoutlet.com') === true);
tbCheck('www, a port and a list of hosts still count (the first host decides)', soViaLiveProxy($fwd('www.seatoutlet.com'), 'seatoutlet.com') && soViaLiveProxy($fwd('seatoutlet.com:443'), 'seatoutlet.com') && soViaLiveProxy($fwd('SeatOutlet.com, other.test'), 'seatoutlet.com'));
tbCheck('a direct hit on the beta address (no header, or the beta host) is not live', !soViaLiveProxy([], 'seatoutlet.com') && !soViaLiveProxy($fwd(''), 'seatoutlet.com') && !soViaLiveProxy($fwd('beta.seatoutlet.com'), 'seatoutlet.com'));
tbCheck('look-alike hosts are not live', !soViaLiveProxy($fwd('seatoutlet.com.evil.test'), 'seatoutlet.com') && !soViaLiveProxy($fwd('evil.test'), 'seatoutlet.com') && !soViaLiveProxy($fwd('xseatoutlet.com'), 'seatoutlet.com'));
tbCheck('crawlers and scripts are told apart from visitors', soClientIsCrawler('Mozilla/5.0 (compatible; SemrushBot/7~bl)') && soClientIsCrawler('Mozilla/5.0 (compatible; Googlebot/2.1)') && soClientIsCrawler('ShapBot 0.1.0') && soClientIsCrawler('curl/8.5.0') && !soClientIsCrawler('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1') && !soClientIsCrawler('') && !soClientIsCrawler('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120 Safari/537.36'));
tbCheck('a visitor on live: the outcome, and the page once', tnCountKeys('live_call', true, false, false, false) === ['live_call', 'pages'] && tnCountKeys('live_call', false, false, false, false) === ['live_call']);
tbCheck('a crawler: the same outcome and page again under _bot', tnCountKeys('live_call', true, false, true, false) === ['live_call', 'pages', 'live_call_bot', 'pages_bot'] && tnCountKeys('no_data', false, false, true, false) === ['no_data', 'no_data_bot']);
tbCheck('a direct hit on beta: under _direct; both at once when a crawler hits beta directly', tnCountKeys('no_data', true, false, false, true) === ['no_data', 'pages', 'no_data_direct', 'pages_direct'] && tnCountKeys('live_call', true, false, true, true) === ['live_call', 'pages', 'live_call_bot', 'pages_bot', 'live_call_direct', 'pages_direct']);
tbCheck('throttles and budget holds are split too, without counting a page', tnCountKeys('throttled', false, false, true, false) === ['throttled', 'throttled_bot'] && tnCountKeys('over_budget', false, false, false, true) === ['over_budget', 'over_budget_direct']);
tbCheck('an outcome that is not a demand figure is not split, only the page is', tnCountKeys('event_copy', true, false, true, false) === ['event_copy', 'pages', 'pages_bot']);
$dm = tnStatsSummary(['pages' => 100, 'pages_bot' => 60, 'live_call' => 200, 'live_call_bot' => 150, 'no_data' => 70, 'no_data_bot' => 63, 'pages_direct' => 10, 'live_call_direct' => 20, 'no_data_direct' => 7])['demand'];
tbCheck('the demand shares: crawlers 60% of pages, 75% of live calls, 90% of the no-data pages', $dm['botPageShare'] === 0.6 && $dm['botCallShare'] === 0.75 && $dm['botNoDataShare'] === 0.9);
tbCheck('the beta-address shares', $dm['directPageShare'] === 0.1 && $dm['directCallShare'] === 0.1 && $dm['directNoDataShare'] === 0.1);
tbCheck('shares are 0 on an empty day and never over 100%', tnStatsSummary([])['demand']['botPageShare'] === 0.0 && tnStatsSummary(['pages' => 10, 'pages_bot' => 99])['demand']['botPageShare'] === 1.0);
$fnW = file_get_contents(__DIR__ . '/../functions.php');
tbCheck('tnCount takes its keys from tnCountKeys, with the client class worked out once per request', strpos($fnW, '$bump = tnCountKeys($kind, $firstOfPage, $firstSuspect, $GLOBALS[\'tn_client_class\'][0], $GLOBALS[\'tn_client_class\'][1]);') !== false && strpos($fnW, "if (!isset(\$GLOBALS['tn_client_class'])) \$GLOBALS['tn_client_class'] = [soClientIsCrawler(), !soViaLiveProxy()];") !== false);
$hl = file_get_contents(__DIR__ . '/../ajax/health.php');
tbCheck('the health page reports the demand split and whether this request came through the Worker', strpos($hl, "['demand'] = \$tnSummary['demand']") !== false && strpos($hl, "['viaProxy'] = soViaLiveProxy()") !== false);

// tidy up
@unlink($bucket); @unlink($bucket . '.lock'); @unlink($flight); @rmdir($dir);

echo $fails ? "\n$fails failed\n" : "\ntn budget: all passed\n";
exit($fails ? 1 : 0);
