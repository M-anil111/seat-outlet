<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Test: the slow request log line (soSlowRequestLine in inc/server-load.php). A page that takes SO_SLOW_LOG_MS or more leaves one line in the
 * error log with its path, status, time, load and Server-Timing breakdown, so a slow period can be explained afterwards. Nothing about the visitor
 * is kept, and a crafted address cannot add a line to the log. No database. Run: php tools/test-slow-request-log.php
 */
require_once __DIR__ . '/../inc/server-load.php';

$fail = 0;
function slowLogCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$timing = 'app;dur=8850, tn;dur=400, dbq;desc="23", dbconn;dur=3200, boot;dur=8850';

slowLogCheck('a fast page is not logged', soSlowRequestLine(120.0, '/artist/x', 200, $timing, 0.4, 5000), null);
slowLogCheck('a page just under the limit is not logged', soSlowRequestLine(4999.0, '/artist/x', 200, $timing, 0.4, 5000), null);
slowLogCheck('the log is off at 0', soSlowRequestLine(60000.0, '/artist/x', 200, $timing, 0.4, 0), null);
slowLogCheck('the log is off below 0', soSlowRequestLine(60000.0, '/artist/x', 200, $timing, 0.4, -1), null);
slowLogCheck('a page at the limit is logged with path, status, time, load and breakdown',
    soSlowRequestLine(5000.0, '/artist/menopause-the-musical', 200, $timing, 3.456, 5000),
    'slow request: /artist/menopause-the-musical status 200 took 5000 ms load 3.46 [' . $timing . ']');
slowLogCheck('without a load figure the load is left out', soSlowRequestLine(9000.4, '/', 503, 'app;dur=9000', null, 5000), 'slow request: / status 503 took 9000 ms [app;dur=9000]');
slowLogCheck('the query string is not kept', strpos((string) soSlowRequestLine(9000.0, '/search?q=secret%20name&email=a@b.test', 200, $timing, 1.0, 5000), 'secret') === false, true);
slowLogCheck('the path is kept without its query string', strpos((string) soSlowRequestLine(9000.0, '/search?q=x', 200, $timing, 1.0, 5000), 'slow request: /search status') === 0, true);
$crafted = (string) soSlowRequestLine(9000.0, "/a\r\nFAKE LINE: x\x00b", 200, "t;dur=1\nINJECT", 1.0, 5000);
slowLogCheck('a crafted address cannot add a line', strpbrk($crafted, "\r\n\0") === false, true);
slowLogCheck('the path is cut at 120 characters', strlen((string) soSlowRequestLine(9000.0, '/' . str_repeat('a', 500), 200, $timing, 1.0, 5000)) < 120 + 140 + strlen($timing), true);
slowLogCheck('an empty address is logged as the home page', strpos((string) soSlowRequestLine(9000.0, '', 200, $timing, 1.0, 5000), 'slow request: / status') === 0, true);

echo $fail ? "$fail failure(s)\n" : "slow request log: all passed\n"; exit($fail ? 1 : 0);
