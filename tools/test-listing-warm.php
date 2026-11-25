<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: a listing page asks the database for the links and pictures of its whole list with a few queries, not one of each per row. The
 * structured data of an event list (soEventItemList) and the category page's text (soCategorySeo) looked up each event, performer, venue and city
 * slug and each artist picture one at a time: 109 queries on a category page, all before the header. Output is the same either way.
 * Each measured build runs in its own PHP process (empty function-static memos), against a database the parent seeded with slugs and  one stored picture.
 * The output is compared with the output of the parent, which looked each slug and picture up one at a time. Ids 9800001 and up; removed at the end (also on an error).
 * Run: php tools/test-listing-warm.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/seo-category.php';

$mode = $argv[1] ?? 'parent';
$tag = $argv[2] ?? bin2hex(random_bytes(4));   // names are unique per run, so a run never touches (or removes) a picture row that was there before

$selects = function (): int { return (int) MYSQLI->query("SHOW SESSION STATUS LIKE 'Com_select'")->fetch_row()[1]; };

$events = []; $artists = []; $venues = []; $cities = [];
for ($i = 1; $i <= 20; $i++) {
    $day = date('Y-m-d', strtotime('+' . (3 + $i) . ' days'));
    $events[] = ['id' => 9800000 + $i, 'text' => ['name' => 'Zq Warm ' . $tag . ' Act ' . $i], 'date' => ['date' => $day, 'datetime' => $day . 'T19:30:00', 'datetimeOffset' => $day . 'T19:30:00-05:00'],
        'venue' => ['id' => 9820000 + ($i % 8) + 1, 'text' => ['name' => 'Zq Warm Hall ' . ($i % 8)]], 'city' => ['id' => 9830000 + ($i % 10) + 1, 'text' => ['name' => 'Zqwarmtown ' . ($i % 10)]],
        'stateProvince' => ['abbr' => 'TX'], 'country' => ['alphaCode' => 'US'], 'defaultCategory' => ['path' => '.1859.1988.9700001.'],
        'performers' => [['id' => 9810000 + $i, 'name' => 'Zq Warm ' . $tag . ' Act ' . $i]], 'pricingInfo' => ['lowPrice' => ['value' => 40 + $i, 'currencyCode' => 'USD']],
        '_metadata' => ['ticketCount' => 12, 'hasTickets' => true]];
}
for ($i = 1; $i <= 12; $i++) $artists[] = ['id' => 9810000 + $i, 'name' => 'Zq Warm ' . $tag . ' Act ' . $i, 'n' => 13 - $i, 'first' => $i, 'cat' => ['path' => '.1859.1988.9700001.']];
for ($i = 1; $i <= 8; $i++) $venues[] = ['id' => 9820000 + $i, 'name' => 'Zq Warm Hall ' . ($i - 1), 'city' => 'Zqwarmtown ' . ($i - 1) . ', TX', 'n' => 9 - $i];
for ($i = 1; $i <= 10; $i++) $cities[] = ['id' => 9830000 + $i, 'label' => 'Zqwarmtown ' . ($i - 1) . ', TX', 'n' => 11 - $i];
$cfg = ['id' => 9700001, 'label' => 'Zq Warm', 'long' => 'zq warm', 'kind' => 'sports', 'profile' => null, 'slug' => 'zq-warm'];
$data = ['total' => 100, 'artists' => $artists, 'venues' => $venues, 'cities' => $cities, 'min' => 31.0, 'next' => null, 'path' => '.1859.1988.9700001.'];


if ($mode === 'item' || $mode === 'cat') {
    // A fresh process: nothing is remembered, so every lookup the build needs is counted.
    $q0 = $selects();
    $out = $mode === 'item' ? soEventItemList($events, 'https://example.test/category/zq-warm') : soCategorySeo($cfg, $data)['html'];
    echo json_encode(['out' => $out, 'selects' => $selects() - $q0]);
    exit(0);
}

$fail = 0;
function lwCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$clean = function () use ($tag) {
    foreach (['event' => [9800001, 9800099], 'performer' => [9810001, 9810099], 'venue' => [9820001, 9820099], 'city' => [9830001, 9830099]] as $type => [$lo, $hi]) {
        MYSQLI->query("DELETE FROM url_slugs WHERE type = '$type' AND CAST(ext_id AS UNSIGNED) BETWEEN $lo AND $hi");
    }
    $like = 'Zq Warm ' . $tag . ' %';
    $st = MYSQLI->prepare("DELETE FROM images WHERE entity_type IN ('artist', 'team') AND entity_name LIKE ?");
    $st->bind_param('s', $like);
    $st->execute();
    $st->close();
};
$run = function (string $m) use ($argv, $tag): array {
    $r = json_decode((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($argv[0]) . ' ' . escapeshellarg($m) . ' ' . escapeshellarg($tag) . ' 2>/dev/null'), true);
    return is_array($r) ? $r : ['out' => null, 'selects' => 999];
};
$clean();
try {
    // Note: getEntityImage() does not return a 'real' key today, so these three pages always use the category photo; the stored row is still read (and prefetched).
    // Make the slugs and one stored picture exist, as on a live site (each entity got its slug the first time it was listed).
    foreach ($events as $e) { soEventSlug($e); soSlug('performer', $e['performers'][0]['name'], $e['performers'][0]['id']); }
    foreach ($venues as $v) soVenueSlug($v['name'], $v['id'], $v['city']);
    foreach ($cities as $c) soSlug('city', $c['label'], $c['id']);
    foreach (['artist', 'team'] as $type) {
        imageRecordUpsert(['imgkey' => imageEntityKey($type, 'Zq Warm ' . $tag . ' Act 1'), 'entity_type' => $type, 'entity_name' => 'Zq Warm ' . $tag . ' Act 1', 'url' => '/images/zq-warm-1.webp', 'status' => 'ok', 'source' => 'test']);
    }

    // This process looks every slug up one at a time (the way each page worked before). Its output is the reference.
    $ref0 = soEventItemList($events, 'https://example.test/category/zq-warm');
    $refCat = soCategorySeo($cfg, $data)['html'];

    $item = $run('item');
    lwCheck('the event list structured data matches the reference', $item['out'], $ref0);
    lwCheck('the event list structured data reads the list with a handful of SELECTs, not 60', $item['selects'] <= 8, true);

    $cat = $run('cat');
    lwCheck('the category text matches the reference', $cat['out'], $refCat);
    lwCheck('the category text is not empty', is_string($cat['out']) && $cat['out'] !== '', true);
    lwCheck('the category text reads its artists, venues and cities with a handful of SELECTs, not 42', $cat['selects'] <= 8, true);
} finally {
    $clean();
}
echo $fail ? "$fail failure(s)\n" : "listing warm: all passed\n"; exit($fail ? 1 : 0);
