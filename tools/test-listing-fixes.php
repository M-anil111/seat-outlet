<?php
// Offline source checks for the QA round 3 fixes (no database). Run: php tools/test-listing-fixes.php
$root = dirname(__DIR__);
$r = function (string $f) use ($root): string { return (string) file_get_contents($root . '/' . $f); };
$fails = 0;
function lfCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$fn = $r('functions.php');
lfCheck('the all-artists letter bar is removed from the page', is_file($root . '/all-artists-and-teams.php') && strpos($r('all-artists-and-teams.php'), 'performerFilterRow') === false);
lfCheck('letter addresses still resolve (old links do not 404)', strpos($r('all-artists-and-teams.php'), "\$_GET['letter']") !== false);
$copy = '';
foreach (glob($root . '/inc/seo-copy/*.php') as $cf) { $copy .= file_get_contents($cf); }
lfCheck('no directory copy tells visitors to use the removed letter bar or letter buttons', preg_match('/letter bar|letter button|pick a letter|tap a letter|choose a letter|a letter at a time|by letter|the letter T/i', $copy) === 0);
lfCheck('the directory lead text no longer asks for a letter', strpos($r('inc/directories.php'), 'Pick a letter') === false);
$w = $r('js/event-widget.js');
lfCheck('seat map is reserved on the server, before the synchronous Seatics script runs', strpos($r('event.php'), 'id="tn-maps" class="seatics so-seatmap so-seatmap--pending"') !== false);
lfCheck('the widget watchdog also covers an already-loaded Seatics (no script load)', preg_match('/function load\(\) \{.*?watchdog = setTimeout.*?if \(window\.Seatics\)/s', $w) === 1);
lfCheck('seat map space is released when it loads, empties or fails', substr_count($w, "map.classList.remove('so-seatmap--pending')") >= 2);
lfCheck('CSS reserves the seat map height on desktop only (phones use the viewport height)', strpos($r('css/event.css'), '@media (min-width: 992px) { #tn-maps.so-seatmap--pending { min-height: 520px; } }') !== false && strpos($r('css/event.css'), 'max-width: 575.98px) { #tn-maps.so-seatmap--pending') === false);
lfCheck('the redirect that drops ?page= on listing pages is still in place (soRedirectCanonicalForm)', preg_match('/unset\(\$qa\[\'page\'\]\); \$qs = http_build_query\(\$qa\); \$dropPage = true;/', $fn) === 1);
echo $fails ? "\n$fails failed\n" : "\nlisting fixes: all passed\n";
exit($fails ? 1 : 0);
