<?php
/**
 * CI (needs the migrated database): building the menu makes a handful of database queries, not one per link. soSlug() asks the database for
 * every link it has not seen in the request, the menu carries dozens of links (hub categories, cities) and the hub lists dozens of rows, so
 * every page paid dozens of separate round trips before printing anything (20 Nov 2026: menu 0.2 to 5.7 s with no API call and 10 to 40 ms of
 * cache reads). Category ids 9200001 and up; removed at the end. Run: php tools/test-menu-query-count.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/category-tree.php';

$fail = 0;
function mqCheck(string $name, bool $ok, string $detail = ''): void {
    global $fail;
    if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; }
}
$questions = function (): int {
    $r = MYSQLI->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch_row();
    return (int) $r[1];
};
$clean = function () {
    MYSQLI->query("DELETE FROM url_slugs WHERE type = 'category' AND ext_id BETWEEN '9200001' AND '9200099'");
};
$clean();

// Fixture: 40 sub-categories with tickets under each hub, stored in the data cache so no ticket call is made.
foreach (SO_HUB_ROOTS as $hubHref => $def) {
    $rows = [];
    for ($i = 1; $i <= 40; $i++) {
        $id = 9200000 + (int) (substr_count($hubHref, 'g') * 100) + $i;   // distinct ids per hub is not needed, only that they exist
        $rows[] = ['id' => $id, 'path' => $def['path'] . $id . '.', 'text' => ['name' => 'Zq Category ' . $id], '_metadata' => ['ticketCount' => 5, 'eventCount' => 2]];
    }
    [$ep, $params] = soHubCategoryRequest($hubHref, 1);
    cache_set(tnCacheKey($ep, $params), ['results' => $rows, 'totalCount' => 40, 'count' => 40]);
}

// First build stores every slug the menu needs (as production already has); then forget the per request memory and count a steady state build.
$build = function () { return require __DIR__ . '/../inc/menu.php'; };
$build();
$mem = &soSlugMemory(); $mem = [];
$GLOBALS['so_hub_memo_reset'] = true;
$before = $questions();
$menu = $build();
$used = $questions() - $before - 1;   // minus the SHOW STATUS itself
mqCheck('the menu was built', is_array($menu) && count($menu) >= 3);
mqCheck("a steady state menu build makes at most 12 database queries (made $used)", $used <= 12, "made $used");
$src = (string) file_get_contents(__DIR__ . '/../inc/menu.php');
mqCheck('menu.php warms the slugs of its links once', substr_count($src, 'soSlugWarm($warm);') === 1);
$tree = (string) file_get_contents(__DIR__ . '/../inc/category-tree.php');
mqCheck('the hub lists warm the slugs of their rows', strpos($tree, "soSlugWarm(\$warm);") !== false);

$fn = (string) file_get_contents(__DIR__ . '/../functions.php');
mqCheck('Server-Timing reports the request\'s database query count (dbq)', strpos($fn, "', dbq;desc=\"'") !== false && strpos($fn, "SHOW SESSION STATUS LIKE 'Questions'") !== false);
mqCheck('the count is skipped in the command line (tests, cron)', strpos($fn, "PHP_SAPI !== 'cli') {\n        try {\n            \$q = MYSQLI->query(\"SHOW SESSION STATUS") !== false);

$clean();
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: the menu builds with a handful of queries (made $used)\n";
