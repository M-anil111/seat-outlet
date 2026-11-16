<?php
/**
 * CI: on a live (indexable) host the request-time migration runner will not change the schema without a database dump from the last
 * 36 hours (audit 16 Nov 2026: production reported autoMigrate on, with no backup step behind it). Run: php tools/test-auto-migrate-guard.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/auto-migrate.php';
$fail = 0;
function amCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

$tmp = sys_get_temp_dir() . '/so-am-' . getmypid(); @mkdir($tmp, 0700, true);
$now = time();
amCheck('no backup folder: not ok', soAutoMigrateRecentBackup($tmp . '/nope', $now) === false);
amCheck('empty folder: not ok', soAutoMigrateRecentBackup($tmp, $now) === false);
touch($tmp . '/seatoutlet-db-old.sql.gz', $now - 40 * 3600);
amCheck('only a 40 hour old dump: not ok', soAutoMigrateRecentBackup($tmp, $now) === false);
touch($tmp . '/notes.txt', $now - 60);
amCheck('a recent file that is not a dump does not count', soAutoMigrateRecentBackup($tmp, $now) === false);
touch($tmp . '/seatoutlet-db-new.sql.gz', $now - 2 * 3600);
amCheck('a 2 hour old dump: ok', soAutoMigrateRecentBackup($tmp, $now) === true);
touch($tmp . '/seatoutlet-db-future.sql.gz', $now + 7200);
amCheck('a dump dated in the future does not count by itself', soAutoMigrateRecentBackup($tmp, $now, 129600) === true);   // the 2h one still does
array_map('unlink', glob($tmp . '/*')); rmdir($tmp);

$src = (string) file_get_contents(__DIR__ . '/../inc/auto-migrate.php');
amCheck('the runner checks the guard only on an indexable host', strpos($src, 'if (SITE_INDEXABLE && !soAutoMigrateRecentBackup())') !== false);
amCheck('the guard runs before any migration file is applied', strpos($src, 'soAutoMigrateRecentBackup()') < strpos($src, 'migrate_run_sql_file($db, $file)'));
amCheck('a refusal is recorded for the health page', strpos($src, "'error' => \$msg") !== false);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: auto-migrate backup guard\n";
