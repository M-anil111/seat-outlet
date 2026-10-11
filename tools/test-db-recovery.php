<?php
// Run only against a disposable development/CI database: this deliberately kills its own connections.
require_once dirname(__DIR__) . '/db/config.php';
require_once dirname(__DIR__) . '/db/migrate-lib.php';
$fail = 0;
function dbRecoveryCheck(bool $ok, string $label): void {
    global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++;
}
$killer = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
// A temporary table shadows the real table; repair tests never alter site data.
$create = $killer->query('SHOW CREATE TABLE images')->fetch_row()[1];
$killer->query(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $create));
$killer->query('ALTER TABLE images DROP INDEX images_miss_hits, DROP COLUMN miss_hits');
migrate_run_sql_file($killer, dirname(__DIR__) . '/db/migrations/0048_repair_image_columns.sql');
$r = $killer->query("SHOW COLUMNS FROM images LIKE 'miss_hits'");
dbRecoveryCheck($r->num_rows === 1, 'repair adds miss_hits when store_key already exists');
migrate_run_sql_file($killer, dirname(__DIR__) . '/db/migrations/0048_repair_image_columns.sql');
dbRecoveryCheck($killer->query("SHOW INDEX FROM images WHERE Key_name = 'images_miss_hits'")->num_rows === 2, 'repair is repeatable and restores the compound index');
$source = file_get_contents(dirname(__DIR__) . '/functions.php');
preg_match('/function getFaqs\(.*?\n}\n/s', $source, $m); eval($m[0]);
$expected = MYSQLI->query("SELECT * FROM faq WHERE type LIKE '%performer%'")->fetch_all(MYSQLI_ASSOC);
$killer->query('KILL ' . (int) MYSQLI->thread_id);
dbRecoveryCheck(getFaqs('performer') === $expected, 'prepared FAQ read recovers after its connection is killed');
dbRecoveryCheck(soDbRead('SELECT 42 AS n')->fetch_assoc()['n'] == 42, 'plain county-style SELECT recovers after disconnect');
foreach (['get_bio' => 0, 'get_image' => '__recovery_fixture_missing__', 'get_keyword' => '__recovery_fixture_missing__'] as $name => $param) {
    preg_match('/function ' . $name . '\(.*?\n}\n/s', $source, $m); eval($m[0]);
    dbRecoveryCheck($name($param) === null, $name . ' recovers on the disconnected connection');
}

try { soDbRead('SELECT definitely_missing_column FROM faq'); dbRecoveryCheck(false, 'schema errors remain visible'); }
catch (mysqli_sql_exception $e) { dbRecoveryCheck((int) $e->getCode() === 1054, 'schema errors remain visible'); }
try { soDbRead('DELETE FROM faq'); dbRecoveryCheck(false, 'writes cannot be replayed'); }
catch (InvalidArgumentException $e) { dbRecoveryCheck(true, 'writes cannot be replayed'); }
require_once dirname(__DIR__) . '/inc/counties.php';
if (!defined('SO_SITEMAP_CITYPAGE_MIN')) define('SO_SITEMAP_CITYPAGE_MIN', 1);
dbRecoveryCheck(soCountyOfCity(-1) === null && soCountyInfo(-1) === null, 'county queries recover on the disconnected connection');
dbRecoveryCheck(is_array(soCitiesInState('TX')) && is_array(soCountiesInState('TX')), 'prepared state and county reads recover');
$killer->close();
exit($fail ? 1 : 0);
