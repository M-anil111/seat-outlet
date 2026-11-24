<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: an install with too few stored addresses (new database not filled, or migrations not applied) is detected, so event, artist and venue
 * pages answer a retryable 503 instead of a 404 "Invalid event." that search engines act on. A filled registry is not flagged.
 * Uses a scratch database it creates and drops. Run: php tools/test-empty-registry.php (needs the migrated test database and CREATE rights).
 */
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/entity-pages.php';
require_once __DIR__ . '/../inc/slugs.php';

$fail = 0;
function check(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}

// A scratch database: no table, a table with too few rows of the type, and one with enough. The minimum is lowered to 3 for the test.
putenv('SO_SLUG_MIN_ROWS=3');
$scratch = 'so_empty_registry_' . getmypid();
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, '', (int) DB_PORT);
try {
    $db->query("CREATE DATABASE `$scratch`");
    $db->select_db($scratch);
    check('no table is flagged (migrations not applied)', soSlugRegistryMissing('event', $db), true);
    $db->query('CREATE TABLE url_slugs (type VARCHAR(20), ext_id VARCHAR(40), slug VARCHAR(190))');
    check('an empty table is flagged', soSlugRegistryMissing('event', $db), true);
    $db->query("INSERT INTO url_slugs VALUES ('event','1','a'),('event','2','b'),('performer','1','c'),('performer','2','d'),('performer','3','e')");
    check('too few rows of the type is flagged, even when another type has plenty', soSlugRegistryMissing('event', $db), true);
    check('enough rows of the type is not flagged', soSlugRegistryMissing('performer', $db), false);
    $db->query("INSERT INTO url_slugs VALUES ('event','3','f')");
    check('the minimum reached is not flagged', soSlugRegistryMissing('event', $db), false);
    check('an unknown type is not flagged', soSlugRegistryMissing('nonsense', $db), false);
} finally {
    $db->query("DROP DATABASE IF EXISTS `$scratch`");
}

// Wiring: the three page types and the event header use it.
$src = file_get_contents(__DIR__ . '/../functions.php') . file_get_contents(__DIR__ . '/../header.php') . file_get_contents(__DIR__ . '/../event.php');
check('renderNotFoundPage asks', str_contains($src, "'Event' => 'event'][\$what] ?? ''"), true);
check('header answers 503 for an event', (bool) preg_match("/soSlugRegistryMissing\\('event'\\)\\) \\{\\s*http_response_code\\(503\\)/", $src), true);
check('event.php shows the unavailable block', str_contains($src, "soSlugRegistryMissing('event') ? unavailableBlockHtml('Event')"), true);

echo $fail ? "$fail failure(s)\n" : "empty registry: all passed\n"; exit($fail ? 1 : 0);
