<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * A database connection that has sat idle (for example through the bulk importer's API waits) is closed by the server, and the next
 * query fails with "MySQL server has gone away" (Sentry SEAT-OUTLET-1S, SEAT-OUTLET-R). The helper soDbEnsure() checks the connection and
 * reconnects it before the next use, and the importer and the retry wrapper both use it. Needs the migrated database.
 * Run: php tools/test-db-keepalive.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';

$fail = 0;
function dkCheck(string $what, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $what . "\n"; if (!$ok) $fail++; }

// A private connection, killed from the shared one, stands in for the server closing an idle connection.
$victim = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
$victim->set_charset('utf8mb4');
$victimId = $victim->thread_id;
MYSQLI->query('KILL CONNECTION ' . (int) $victimId);
usleep(200000);

$lost = false;
try { $victim->query('SELECT 1'); } catch (mysqli_sql_exception $e) { $lost = true; }
dkCheck('the connection is gone after the server closes it (test setup)', $lost);

dkCheck('soDbEnsure reconnects a lost connection and reports it is usable', soDbEnsure($victim) === true);
$ok = false;
try { $ok = (int) $victim->query('SELECT 1')->fetch_row()[0] === 1; } catch (Throwable $e) { $ok = false; }
dkCheck('the reconnected handle runs a query', $ok);
dkCheck('soDbEnsure on a healthy connection is a no-op that returns true', soDbEnsure(MYSQLI) === true);

// The retry wrapper recovers on its own, with no error reaching the page.
$victim2 = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
$victim2->set_charset('utf8mb4');
MYSQLI->query('KILL CONNECTION ' . (int) $victim2->thread_id);
usleep(200000);
$value = null;
try { $value = soDbRetry(function ($db) { return (int) $db->query('SELECT 2')->fetch_row()[0]; }, $victim2); } catch (Throwable $e) { $value = 'threw: ' . $e->getMessage(); }
dkCheck('soDbRetry answers after the connection was closed by the server', $value === 2);

// The importer writes through the same guard, so a page written after a long wait does not fail.
$cron = (string) file_get_contents(__DIR__ . '/../cron/tn-bulk-import.php');
dkCheck('the bulk importer checks the connection before each page write', strpos($cron, 'soDbEnsure(MYSQLI)') !== false);

echo $fail ? "\ndb keepalive: $fail failed\n" : "\ndb keepalive: all passed\n";
exit($fail ? 1 : 0);
