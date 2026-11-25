<?php
/**
 * The admin dashboard must open on a database whose images table predates migration 0005 (no created_at column). 25 Nov 2026 on live:
 * imageQueueStats() read MIN(created_at), MariaDB answered "Unknown column 'created_at'", and /admin/dashboard answered 500.
 * Builds that older table in a scratch database (dropped afterwards), with mysqli in strict mode like db/config.php, and checks that
 * the statistics still come back, and that migration 0054 adds the column (and is a no-op the second time).
 * Needs the CI database server (DB_* variables). Run: php tools/test-images-created-at.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function icaCheck(string $name, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fail++; }

if (!defined('MYSQLI')) define('MYSQLI', null);   // inc/images.php names it as a default argument; this test passes its own connection
require_once $root . '/inc/images.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);   // as db/config.php: every failed query throws
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$admin = new mysqli($host, (string) getenv('DB_USER'), (string) getenv('DB_PASS'), '', $port);
$scratch = 'so_images_drift_' . getmypid();
$admin->query("CREATE DATABASE `$scratch`");
try {
    $db = new mysqli($host, (string) getenv('DB_USER'), (string) getenv('DB_PASS'), $scratch, $port);
    // The images table as an older database has it: no created_at (the columns imageQueueStats reads otherwise).
    $db->query("CREATE TABLE images (ID int NOT NULL AUTO_INCREMENT PRIMARY KEY, imgkey varchar(255) NOT NULL, url varchar(1000) NOT NULL,
        entity_type varchar(20) NULL, status varchar(20) NULL, store_key varchar(255) NULL, source varchar(40) NULL)");
    $db->query("INSERT INTO images (imgkey, url, entity_type, status) VALUES ('a', 'u', 'performer', 'ok'), ('b', 'u', 'performer', 'pending')");

    $stats = null; $threw = false;
    try { $stats = imageQueueStats($db); } catch (\Throwable $e) { $threw = true; }
    icaCheck('an images table without created_at: the statistics still come back (no 500)', !$threw && is_array($stats));
    icaCheck('the counts are right and the age is left out', is_array($stats) && $stats['ok'] === 1 && $stats['pending'] === 1 && $stats['oldest_pending_age'] === null);

    // Migration 0054 adds the column; a second run is "duplicate column" (1060), which the runner treats as already done.
    $sql = (string) file_get_contents($root . '/db/migrations/0054_images_created_at.sql');
    $stmt = trim(preg_replace('/^--.*$/m', '', $sql));
    $db->query($stmt);
    $cols = []; $r = $db->query('SHOW COLUMNS FROM images'); while ($row = $r->fetch_assoc()) $cols[] = $row['Field'];
    icaCheck('migration 0054 adds images.created_at', in_array('created_at', $cols, true));
    $errno = 0; try { $db->query($stmt); } catch (\mysqli_sql_exception $e) { $errno = $e->getCode(); }
    require_once $root . '/db/migrate-lib.php';
    icaCheck('a second run is "duplicate column", which the runner treats as already done', $errno === 1060 && in_array(1060, MIGRATE_ALREADY_DONE_ERRNOS, true));

    $stats = imageQueueStats($db);
    icaCheck('with the column, the age of the oldest queued picture is reported again', is_int($stats['oldest_pending_age']) && $stats['oldest_pending_age'] >= 0);
    $db->close();
} finally {
    $admin->query("DROP DATABASE IF EXISTS `$scratch`");
}

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "images created_at: all passed\n";
