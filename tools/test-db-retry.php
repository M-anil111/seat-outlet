<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test for soDbRetry() (functions.php): a connection the server dropped is reconnected once and the work is repeated, any
 * other error is passed on untouched, and the charset survives. Needs the migrated database, like test-account.php.
 * Run: php tools/test-db-retry.php
 */
require_once __DIR__ . '/../db/config.php';
$src = file_get_contents(__DIR__ . '/../functions.php');
if (!preg_match('/^function soDbRetry\(.*?\n}\n/ms', $src, $m)) { fwrite(STDERR, "FAIL: soDbRetry not found in functions.php\n"); exit(1); }
eval($m[0]);

$fail = 0;
function drCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else { echo "ok   $what\n"; }
}
$killer = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$kill = function () use ($killer) { $killer->query('KILL CONNECTION ' . (int) MYSQLI->thread_id); };

drCheck('a healthy connection runs the work once', soDbRetry(fn($db) => $db->query('SELECT 7')->fetch_row()[0]), '7');

$kill();
$calls = 0;
$val = soDbRetry(function ($db) use (&$calls) { $calls++; return $db->query('SELECT 8')->fetch_row()[0]; });
drCheck('a dropped connection is reconnected and the work repeated', [$val, $calls], ['8', 2]);
drCheck('the charset is utf8mb4 again after the reconnect', MYSQLI->character_set_name(), 'utf8mb4');
drCheck('the handle keeps working for later queries', MYSQLI->query('SELECT 9')->fetch_row()[0], '9');

$kill();
$stmtRows = soDbRetry(function ($db) { $st = $db->prepare('SELECT ?'); $x = 'ok'; $st->bind_param('s', $x); $st->execute(); return $st->get_result()->fetch_row()[0]; });
drCheck('a prepared statement on a dropped connection is retried too', $stmtRows, 'ok');

$other = null; $otherCalls = 0;
try { soDbRetry(function ($db) use (&$otherCalls) { $otherCalls++; return $db->query('SELECT * FROM no_such_table_zq'); }); } catch (mysqli_sql_exception $e) { $other = $e->getCode(); }
drCheck('any other database error is passed on, not retried', [$other, $otherCalls], [1146, 1]);

$calls = 0; $threw = false;
try { soDbRetry(function ($db) use (&$calls) { $calls++; throw new mysqli_sql_exception('MySQL server has gone away', 2006); }, MYSQLI); } catch (mysqli_sql_exception $e) { $threw = $e->getCode() === 2006; }
drCheck('work that keeps failing is tried twice, then the error is thrown', [$calls, $threw], [2, true]);

// The server refuses the first two reconnect attempts (greeting packet error, Sentry SEAT-OUTLET-13): the third gets through.
class DrFlakyMysqli extends mysqli {
    public int $refuse = 2;
    public int $attempts = 0;
    #[\ReturnTypeWillChange]
    public function real_connect(...$a) {
        $this->attempts++;
        if ($this->refuse-- > 0) throw new mysqli_sql_exception('Error while reading greeting packet', 2013);
        return parent::real_connect(...$a);
    }
}
$flaky = new DrFlakyMysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
$killer->query('KILL CONNECTION ' . (int) $flaky->thread_id);
usleep(200000);
$flakyVal = soDbRetry(fn($db) => $db->query('SELECT 9')->fetch_row()[0], $flaky);
drCheck('a reconnect refused twice succeeds on the third try', [$flakyVal, $flaky->attempts], ['9', 3]);
$down = new DrFlakyMysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
$down->refuse = 99;
$killer->query('KILL CONNECTION ' . (int) $down->thread_id);
usleep(200000);
$downCode = null;
try { soDbRetry(fn($db) => $db->query('SELECT 1'), $down); } catch (mysqli_sql_exception $e) { $downCode = $e->getCode(); }
drCheck('a server that stays down: three tries, then the original error', [$downCode, $down->attempts], [2006, 3]);
drCheck('getFaqs goes through it', preg_match('/function getFaqs\(.*?soDbRetry\(function/s', $src) === 1, true);
// Every public-page read that can run after a (slow) TicketNetwork call goes through it too (Sentry SEAT-OUTLET-R was get_bio on an artist page).
foreach (['get_bio', 'get_image', 'get_keyword', 'getPageRuleByPath', 'getContentBlock', 'tnSnapshotGet'] as $reader) {
    // Inside this function's own body only (stop at the next top-level function), so a later function's call cannot satisfy it.
    drCheck("$reader goes through soDbRetry", preg_match('/\nfunction ' . $reader . '\((?:(?!\nfunction ).)*?soDbRetry\(function/s', $src) === 1, true);
}
// The removed-event redirect reads through soEventRedirectLookup() (inc/event-redirects.php), which wraps both of its queries.
$redir = (string) file_get_contents(__DIR__ . '/../inc/event-redirects.php');
drCheck('soEventRedirectLookup goes through soDbRetry (both queries)', preg_match('/function soEventRedirectLookup\(.*?soDbRetry\(function.*?soDbRetry\(function/s', $redir) === 1 && strpos((string) $src, 'soEventRedirectLookup($eventId)') !== false, true);
echo $fail ? "$fail failed\n" : "db retry: all passed\n";
exit($fail ? 1 : 0);
