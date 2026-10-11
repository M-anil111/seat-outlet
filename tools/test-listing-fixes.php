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
lfCheck('soListingStripPage keeps the visitor query from REQUEST_URI, not the rewrite-added $_GET keys', preg_match('/function soListingStripPage\(\).*?REQUEST_URI.*?parse_str.*?unset\(\$q\[\'page\'\]\).*?exit;/s', $fn) === 1 && strpos($fn, 'function soListingStripPage') !== false);
lfCheck('the all-artists letter bar is removed from the page', strpos($r('all-artists-and-teams.php'), 'performerFilterRow') === false);
lfCheck('letter addresses still resolve (old links do not 404)', strpos($r('all-artists-and-teams.php'), "\$_GET['letter']") !== false);
$copy = '';
foreach (glob($root . '/inc/seo-copy/*.php') as $cf) { $copy .= file_get_contents($cf); }
lfCheck('no directory copy tells visitors to use the removed letter bar or letter buttons', preg_match('/letter bar|letter button|pick a letter|tap a letter|choose a letter|a letter at a time|by letter|the letter T/i', $copy) === 0);
lfCheck('the directory lead text no longer asks for a letter', strpos($r('inc/directories.php'), 'Pick a letter') === false);
$w = $r('js/event-widget.js');
lfCheck('seat map is reserved on the server, before the synchronous Seatics script runs', strpos($r('event.php'), 'id="tn-maps" class="seatics so-seatmap so-seatmap--pending"') !== false);
lfCheck('the widget watchdog also covers an already-loaded Seatics (no script load)', preg_match('/function load\(\) \{.*?watchdog = setTimeout.*?if \(window\.Seatics\)/s', $w) === 1);
lfCheck('seat map space is released when it loads, empties or fails', substr_count($w, "map.classList.remove('so-seatmap--pending')") >= 2);
lfCheck('CSS reserves the seat map height only while pending', strpos($r('css/event.css'), '#tn-maps.so-seatmap--pending { min-height: 520px; }') !== false);
echo $fails ? "\n$fails failed\n" : "\nlisting fixes: all passed\n";
exit($fails ? 1 : 0);
