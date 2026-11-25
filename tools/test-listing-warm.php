<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: a listing page asks the database for the links and pictures of its whole list with a few queries, not one of each per row. The
 * structured data of an event list (soEventItemList) and the category page's text (soCategorySeo) looked up each event, performer, venue and city
 * slug and each artist picture one at a time: 109 queries on a category page, all before the header. Output is the same either way.
 * Ids 9800001 and up; removed at the end. Run: php tools/test-listing-warm.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/seo-category.php';

$fail = 0;
function lwCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$selects = function (): int { return (int) MYSQLI->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch_row()[1]; };
$clean = function () {
    foreach (['event' => [9800001, 9800099], 'performer' => [9810001, 9810099], 'venue' => [9820001, 9820099], 'city' => [9830001, 9830099]] as $type => [$lo, $hi]) {
        MYSQLI->query("DELETE FROM url_slugs WHERE type = '$type' AND CAST(ext_id AS UNSIGNED) BETWEEN $lo AND $hi");
    }
    MYSQLI->query("DELETE FROM images WHERE entity_type = 'artist' AND entity_name LIKE 'Zq Warm %'");
};
$forget = function () { $m = &soSlugMemory(); $m = []; $x = &soSlugMissMemory(); $x = []; $i = &imageRowMemo(); $i = []; };
$clean();

$events = []; $artists = []; $venues = []; $cities = [];
for ($i = 1; $i <= 20; $i++) {
    $day = date('Y-m-d', strtotime('+' . (3 + $i) . ' days'));
    $events[] = ['id' => 9800000 + $i, 'text' => ['name' => 'Zq Warm Act ' . $i], 'date' => ['date' => $day, 'datetime' => $day . 'T19:30:00', 'datetimeOffset' => $day . 'T19:30:00-05:00'],
        'venue' => ['id' => 9820000 + ($i % 8) + 1, 'text' => ['name' => 'Zq Warm Hall ' . ($i % 8)]], 'city' => ['id' => 9830000 + ($i % 10) + 1, 'text' => ['name' => 'Zqwarmtown ' . ($i % 10)]],
        'stateProvince' => ['abbr' => 'TX'], 'country' => ['alphaCode' => 'US'], 'defaultCategory' => ['path' => '.1859.1988.9700001.'],
        'performers' => [['id' => 9810000 + $i, 'name' => 'Zq Warm Act ' . $i]], 'pricingInfo' => ['lowPrice' => ['value' => 40 + $i, 'currencyCode' => 'USD']],
        '_metadata' => ['ticketCount' => 12, 'hasTickets' => true]];
}
for ($i = 1; $i <= 12; $i++) $artists[] = ['id' => 9810000 + $i, 'name' => 'Zq Warm Act ' . $i, 'n' => 13 - $i, 'first' => $i, 'cat' => ['path' => '.1859.1988.9700001.']];
for ($i = 1; $i <= 8; $i++) $venues[] = ['id' => 9820000 + $i, 'name' => 'Zq Warm Hall ' . ($i - 1), 'city' => 'Zqwarmtown ' . ($i - 1) . ', TX', 'n' => 9 - $i];
for ($i = 1; $i <= 10; $i++) $cities[] = ['id' => 9830000 + $i, 'label' => 'Zqwarmtown ' . ($i - 1) . ', TX', 'n' => 11 - $i];
$cfg = ['id' => 9700001, 'label' => 'Zq Warm', 'long' => 'zq warm', 'kind' => 'sports', 'profile' => null, 'slug' => 'zq-warm'];
$data = ['total' => 100, 'artists' => $artists, 'venues' => $venues, 'cities' => $cities, 'min' => 31.0, 'next' => null, 'path' => '.1859.1988.9700001.'];

// Make the slugs and pictures exist, as on a live site (each entity got its slug the first time it was listed), then start a new request.
foreach ($events as $e) { soEventSlug($e); soSlug('performer', $e['performers'][0]['name'], $e['performers'][0]['id']); }
foreach ($venues as $v) soVenueSlug($v['name'], $v['id'], $v['city']);
foreach ($cities as $c) soSlug('city', $c['label'], $c['id']);
$a0 = soEventItemList($events, 'https://example.test/category/zq-warm');
$s0 = soCategorySeo($cfg, $data);
$forget();

// The structured data of the event list: a few queries for the whole list, and the same result as the first (unwarmed) build.
$q0 = $selects();
$a1 = soEventItemList($events, 'https://example.test/category/zq-warm');
$cost = $selects() - $q0;
lwCheck('the event list structured data is the same', $a1, $a0);
lwCheck('the event list structured data reads the list with a handful of SELECTs, not 60', $cost <= 8, true);

// The category text: the same HTML, a handful of queries.
$forget();
$q0 = $selects();
$s1 = soCategorySeo($cfg, $data);
$cost = $selects() - $q0;
lwCheck('the category text is the same', $s1['html'] ?? null, $s0['html'] ?? null);
lwCheck('the category text reads its artists, venues and cities with a handful of SELECTs, not 42', $cost <= 8, true);

$clean();
echo $fail ? "$fail failure(s)\n" : "listing warm: all passed\n"; exit($fail ? 1 : 0);
