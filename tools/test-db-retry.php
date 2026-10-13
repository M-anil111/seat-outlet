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

$other = null;
try { soDbRetry(fn($db) => $db->query('SELECT * FROM no_such_table_zq')); } catch (mysqli_sql_exception $e) { $other = $e->getCode(); }
drCheck('any other database error is passed on, not retried', $other, 1146);

$calls = 0; $threw = false;
try { soDbRetry(function ($db) use (&$calls) { $calls++; throw new mysqli_sql_exception('MySQL server has gone away', 2006); }, MYSQLI); } catch (mysqli_sql_exception $e) { $threw = $e->getCode() === 2006; }
drCheck('work that keeps failing is tried twice, then the error is thrown', [$calls, $threw], [2, true]);

drCheck('getFaqs goes through it', preg_match('/function getFaqs\(.*?soDbRetry\(function/s', $src) === 1, true);
echo $fail ? "$fail failed\n" : "db retry: all passed\n";
exit($fail ? 1 : 0);
