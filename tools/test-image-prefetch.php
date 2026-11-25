<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: a grid of cards reads its pictures with one query. soImageFirst() ranked 20 related artists with one SELECT each, and the 8 shown
 * cards asked again; a card for an entity with no row also repeated the INSERT. imagePrefetch() loads the rows of many keys at once and
 * imageRecordGet() reads them from memory; only keys loaded that way are remembered (workers and tests read the table), and a write drops the key.
 * Entities named Zq Imgp 1..; removed at the end. Run: php tools/test-image-prefetch.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';

$fail = 0;
function ipCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$status = function (string $name): int { return (int) MYSQLI->query("SHOW SESSION STATUS LIKE '$name'")->fetch_row()[1]; };
$clean = function () { MYSQLI->query("DELETE FROM images WHERE entity_name LIKE 'Zq Imgp %'"); };
$clean();

// 12 entities: 1 to 4 have a stored picture, 5 and 6 are known misses, 7 to 12 have no row at all.
$keys = []; $names = [];
for ($i = 1; $i <= 12; $i++) { $names[$i] = 'Zq Imgp ' . $i; $keys[$i] = imageEntityKey('artist', $names[$i]); }
for ($i = 1; $i <= 4; $i++) imageRecordUpsert(['imgkey' => $keys[$i], 'entity_type' => 'artist', 'entity_name' => $names[$i], 'url' => '/images/zq' . $i . '.webp', 'status' => 'ok', 'source' => 'test']);
for ($i = 5; $i <= 6; $i++) imageRecordUpsert(['imgkey' => $keys[$i], 'entity_type' => 'artist', 'entity_name' => $names[$i], 'url' => '', 'status' => 'fallback', 'expires_at' => date('Y-m-d H:i:s', time() + 86400)]);

// Without a prefetch every lookup reads the table (workers and tests rely on that).
$plain = []; foreach ($keys as $i => $k) $plain[$i] = imageRecordGet($k);
ipCheck('without a prefetch the row is read from the table', ($plain[1]['url'] ?? null), '/images/zq1.webp');
$q0 = $status('Questions');
imageRecordGet($keys[1]);
ipCheck('and costs a query each time', $status('Questions') - $q0 - 1, 1);

// With a prefetch: one query for all 12, then none.
$q0 = $status('Questions');
imagePrefetch(array_values($keys));
$prefetchCost = $status('Questions') - $q0 - 1;
ipCheck('prefetching 12 keys costs one query', $prefetchCost, 1);
$q0 = $status('Questions');
$viaMemo = []; foreach ($keys as $i => $k) $viaMemo[$i] = imageRecordGet($k);
ipCheck('then 12 lookups cost no query', $status('Questions') - $q0 - 1, 0);
ipCheck('the rows are the same as the table gave', $viaMemo, $plain);
ipCheck('a key with no row is remembered as no row', $viaMemo[9], null);
$q0 = $status('Questions');
imagePrefetch(array_values($keys));
ipCheck('prefetching the same keys again costs nothing', $status('Questions') - $q0 - 1, 0);

// getEntityImage() gives the same answers either way.
$q0 = $status('Questions');
$a = getEntityImage('artist', $names[1], ['resolve' => false]);
$b = getEntityImage('artist', $names[5], ['resolve' => false]);
ipCheck('a stored picture is served from the prefetched row', $a['url'], '/images/zq1.webp');
ipCheck('a known miss is served as the fallback', $b['status'], 'fallback');

// An entity with no row: queued once per request, however many times its card is built, and the next lookup sees the new row.
$ins0 = $status('Com_insert');
getEntityImage('artist', $names[9], ['resolve' => false]);
getEntityImage('artist', $names[9], ['resolve' => false]);
getEntityImage('artist', $names[9], ['resolve' => false]);
ipCheck('an entity with no row is queued once, not once per card', $status('Com_insert') - $ins0, 1);
ipCheck('and then reads back as pending', (imageRecordGet($keys[9])['status'] ?? null), 'pending');

// A write in this request drops the remembered row.
imageRecordUpsert(['imgkey' => $keys[2], 'entity_type' => 'artist', 'entity_name' => $names[2], 'url' => '/images/zq2-new.webp', 'status' => 'ok', 'source' => 'test']);
ipCheck('a row stored in this request is read back, not the old one', (imageRecordGet($keys[2])['url'] ?? null), '/images/zq2-new.webp');

// soImageFirst: one query for the list, and the same order as without the prefetch.
$items = []; for ($i = 12; $i >= 1; $i--) $items[] = ['n' => $names[$i]];
$describe = function ($it) { return ['artist', $it['n'], []]; };
$memo = &imageRowMemo(); $memo = [];   // a new request
$sel0 = $status('Com_select');
$ranked = soImageFirst($items, $describe);
ipCheck('ranking 12 cards reads their pictures with one SELECT (the inserts queueing new entities are writes, not reads)', $status('Com_select') - $sel0, 1);
ipCheck('cards with a picture come first', array_slice(array_column($ranked, 'n'), 0, 4), [$names[4], $names[3], $names[2], $names[1]]);

$clean();
echo $fail ? "$fail failure(s)\n" : "image prefetch: all passed\n"; exit($fail ? 1 : 0);
