<?php
// Offline source checks for the QA round 3 fixes (no database). Run: php tools/test-listing-fixes.php
$root = dirname(__DIR__);
$r = function (string $f) use ($root): string { return (string) file_get_contents($root . '/' . $f); };
$fails = 0;
function lfCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$listing = ['buy-broadway-tickets', 'buy-tickets-online', 'category', 'city', 'concert-tickets-for-sale', 'country', 'county', 'game-day-tickets', 'state', 'upcoming-music-festivals', 'venue'];
foreach ($listing as $f) {
    lfCheck("$f.php strips ?page= with a 301 (Load more handles later events)", strpos($r("$f.php"), 'soListingStripPage();') !== false);
}
$fn = $r('functions.php');
lfCheck('soListingStripPage removes only the page parameter and never returns after redirecting', preg_match('/function soListingStripPage\(\).*?unset\(\$q\[\'page\'\]\).*?exit;/s', $fn) === 1);
lfCheck('the all-artists letter bar is removed from the page', strpos($r('all-artists-and-teams.php'), 'performerFilterRow') === false);
lfCheck('letter addresses still resolve (old links do not 404)', strpos($r('all-artists-and-teams.php'), "\$_GET['letter']") !== false);
lfCheck('no copy tells visitors to tap the removed A to Z bar', strpos($r('inc/seo-copy/all-artists-and-teams.php') . $r('inc/seo-copy/concert-artists.php'), 'A to Z bar') === false);
$w = $r('js/event-widget.js');
lfCheck('seat map holds its space while loading (class added on load start)', strpos($w, "map.classList.add('so-seatmap--pending')") !== false);
lfCheck('seat map space is released when it loads, empties or fails', substr_count($w, "map.classList.remove('so-seatmap--pending')") >= 2);
lfCheck('CSS reserves the seat map height only while pending', strpos($r('css/event.css'), '#tn-maps.so-seatmap--pending { min-height: 520px; }') !== false);
echo $fails ? "\n$fails failed\n" : "\nlisting fixes: all passed\n";
exit($fails ? 1 : 0);
