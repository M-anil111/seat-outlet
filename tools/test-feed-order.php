<?php
// Offline test (no network, no database): the best-sellers / nationwide rows are listed by date as one set that "See more" pages through,
// the three best sellers keep the "Popular" flag, and the sort chip says "Best sellers" when there is no location.
// Run: php tools/test-feed-order.php
$root = dirname(__DIR__);
$feed = (string) file_get_contents($root . '/ajax/get-home-feed.php');
$js = (string) file_get_contents($root . '/js/near-you.js');
$fails = 0;
function foCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

// The real function, with the picture ordering replaced by "pictures first for ids divisible by 5" so the test can see it work.
preg_match("/function soHomeFeedByDate\(.*?\n}\n/s", $feed, $m);
foCheck('soHomeFeedByDate found', !empty($m[0]));
eval('function soHomeFeedImageFirst(array $e, $p = null) { usort($e, function ($a, $b) { return [(int) ($a["id"] % 5 !== 0), $a["_i"]] <=> [(int) ($b["id"] % 5 !== 0), $b["_i"]]; }); return $e; }');
eval($m[0]);

$ranked = [];   // the API's sales-rank order: rank 0 is the best seller
$dates = ['2026-12-06', '2026-10-14', '2026-10-11', '', '2026-10-14', '2027-01-02', '2026-11-20', '2026-10-11', '2026-12-30', '2026-10-30', '2026-11-02', '2026-10-12', '2026-10-13', '2026-12-01', '2026-11-11', '2026-10-20'];
foreach ($dates as $i => $d) { $ranked[] = ['id' => 100 + $i, 'iso' => $d, 'name' => "E$i"]; }
$out = call_user_func('soHomeFeedByDate', $ranked);
$isos = array_column($out, 'iso');
$dated = array_values(array_filter($isos, 'strlen'));
$sorted = $dated; sort($sorted);
foCheck('every event is kept', count($out) === count($ranked));
foCheck('dated events are in date order across the whole set', $dated === $sorted);
foCheck('the undated event is last', end($isos) === '');
$same = array_values(array_filter($out, function ($e) { return $e['iso'] === '2026-10-11'; }));
foCheck('events on one date keep their sales-rank order, not their picture order', array_column($same, 'id') === [102, 107]);
$topIds = array_column(array_filter($out, function ($e) { return $e['top']; }), 'id');
sort($topIds);
foCheck('exactly three events carry the Popular flag', count($topIds) === 3);
foCheck('they are the best sellers of the first twelve, picture-bearing ones first (ids 100, 105, 110 have pictures)', $topIds === [100, 105, 110]);
foCheck('no helper field leaks into the answer', !array_key_exists('_i', $out[0]));
$page1 = array_slice($out, 0, 12); $page2 = array_slice($out, 12, 12);
foCheck('page 2 continues after page 1 in date order', max(array_column($page1, 'iso')) <= min(array_filter(array_column($page2, 'iso'), 'strlen')));

$declared = preg_match('/\bconst SO_HOME_FEED_WIDE\b/', $feed, $dm, PREG_OFFSET_CAPTURE) === 1 ? $dm[0][1] + 6 : -1;   // offset of the name inside the declaration
$firstUse = strpos($feed, 'SO_HOME_FEED_WIDE');
foCheck('the constant is declared, and its declaration is the first place the name appears (so it exists before any use)', $declared > 0 && $firstUse !== false && $firstUse === $declared);
foCheck('the best-sellers request is one wide page', preg_match("/\\\$wide\) \{ \\\$params\['perPage'\] = SO_HOME_FEED_WIDE; \\\$params\['page'\] = 1;/", $feed) === 1);
foCheck('the far-location fallback uses the same date-ordered set', preg_match("/buildParams\(false, true\)\);\s*\\\$nwAll = soHomeFeedByDate/", $feed) === 1);
foCheck('hasMore comes from the set when the answer is one', strpos($feed, 'count($wideAll)') !== false);
foCheck('the page honours the server flag for the Popular badge', strpos($js, 'e.top === true') !== false);
foCheck('the sort chip shows and highlights Best sellers with no location', preg_match("/key === 'sort' && cur === 'distance' && state\.nw\) cur = 'popular'/", $js) === 1);
foCheck('the chip is refreshed after the scope is known', preg_match("/state\.nw = state\.scope === 'nationwide';\s*syncUi\(\);/", $js) === 1);
echo $fails ? "$fails failed\n" : "feed order: all passed\n";
exit($fails ? 1 : 0);
