<?php
// Offline source check: the nationwide / best-sellers list is listed by date (the three "Popular" badges stay on the best sellers), and the
// sort chip says "Best sellers" when there is no location. Run: php tools/test-feed-order.php
$root = dirname(__DIR__);
$feed = (string) file_get_contents($root . '/ajax/get-home-feed.php');
$js = (string) file_get_contents($root . '/js/near-you.js');
$fails = 0;
function foCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
foCheck('popular and nationwide rows are sorted by date', preg_match("/nearSort === 'popular' \|\| \\\$nationwide\) \{.*?usort\(.*?strcmp\(\\\$x, \\\$y\)/s", $feed) === 1);
foCheck('the three best sellers are flagged before the date sort', preg_match("/\\\$events\[\\\$i\]\['top'\] = \\\$i < 3;.*?usort\(/s", $feed) === 1);
foCheck('the page honours the server flag for the Popular badge', strpos($js, 'e.top === true') !== false);
foCheck('the sort chip is relabelled for the nationwide list', preg_match("/key === 'sort' && cur === 'distance' && state\.nw/", $js) === 1);
foCheck('the chip is refreshed after the scope is known', preg_match("/state\.nw = state\.scope === 'nationwide';\s*syncUi\(\);/", $js) === 1);
echo $fails ? "$fails failed\n" : "feed order: all passed\n";
exit($fails ? 1 : 0);
