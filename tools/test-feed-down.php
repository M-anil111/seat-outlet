<?php
// Offline test (no network, no database): when the ticket feed fails, listing and entity pages keep rendering (no empty 503 page) and
// say so, instead of claiming "no events". Run: php tools/test-feed-down.php
$root = dirname(__DIR__);
$src = file_get_contents($root . '/functions.php');
preg_match("/function soStateIcon\(.*?\n}\n/s", $src, $m1);
preg_match("/function soFeedDownStateHtml\(.*?\n}\n/s", $src, $m2);
preg_match("/function soFeedDownGate\(.*?\n}\n/s", $src, $m3);
if (!$m1 || !$m2 || !$m3) { fwrite(STDERR, "FAIL: helper functions not found in functions.php\n"); exit(1); }
eval($m1[0] . $m2[0]);

$fails = 0;
function feedDownCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$html = soFeedDownStateHtml();
feedDownCheck('state card says the feed is slow', strpos($html, 'Live listings are loading slowly') !== false);
feedDownCheck('state card does not claim there are no events', stripos($html, 'no events') === false && stripos($html, 'on sale right now') === false);
feedDownCheck('state card offers a reload and browse links', strpos($html, 'Reload this page') !== false && strpos($html, '/buy-tickets-online') !== false);
feedDownCheck('gate marks the feed down and sets noindex', strpos($m3[0], "\$GLOBALS['soFeedDown'] = true") !== false && strpos($m3[0], 'noindex, follow') !== false);
feedDownCheck('gate serves a snapshot first', strpos($m3[0], 'soSnapshotServe()') !== false);
feedDownCheck('gate never sends a 503 itself', strpos($m3[0], '503') === false);

// Every listing and entity page that used to stop on the 503 page now goes through the gate.
foreach (['category.php', 'inc/holidays.php', 'inc/discovery-pages.php', 'inc/entity-listing.php', 'performer.php'] as $f) {
    $s = file_get_contents($root . '/' . $f);
    feedDownCheck("$f uses soFeedDownGate", strpos($s, 'soFeedDownGate(') !== false && strpos($s, 'renderUnavailablePage(') === false);
}
$list = file_get_contents($root . '/inc/listing.php');
feedDownCheck('soRenderListingPage no longer answers 503 after output', strpos($list, "http_response_code(503)") === false);
feedDownCheck('listing empty state honours the flag', strpos($list, "soFeedDown") !== false);
echo $fails ? "$fails failed\n" : "feed down: all passed\n";
exit($fails ? 1 : 0);
