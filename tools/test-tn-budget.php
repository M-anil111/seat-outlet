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
$stored = ['id' => 7];
tbCheck('a follower returns the answer once the leader stores it', tnFlightWait($flight, fn() => $stored, 1.0) === $stored);
tnFlightClose($lead);
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
tbCheck('functions.php loads the budget helpers', strpos($fn, "require_once __DIR__ . '/inc/tn-budget.php';") !== false);
tbCheck('every live call passes the shared budget before it goes out', strpos($fn, 'if (!tnBucketAllow()) {') !== false && strpos($fn, 'tnBucketWait(PHP_SAPI === \'cli\'') !== false);
tbCheck('the cold path is single-flight', strpos($fn, '$flight = tnFlightOpen($flightPath);') !== false && strpos($fn, 'tnFlightClose($flight);') !== false);
tbCheck('the breaker after a throttle uses Retry-After', strpos($fn, 'tnThrottleWait($GLOBALS[\'tn_retry_after\'] ?? null') !== false);
tbCheck('the header callback keeps Retry-After', strpos($fn, 'tnRetryAfterFromHeader($line)') !== false);

// tidy up
@unlink($bucket); @unlink($bucket . '.lock'); @unlink($flight); @rmdir($dir);

echo $fails ? "\n$fails failed\n" : "\ntn budget: all passed\n";
exit($fails ? 1 : 0);
