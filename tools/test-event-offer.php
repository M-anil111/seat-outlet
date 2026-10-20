<?php
// Offline test (no network, no database): the Event offer is an AggregateOffer with highPrice when the feed has one, and a plain
// Offer with a price (never a made-up high price) when it does not. Run: php tools/test-event-offer.php
$src = file_get_contents(__DIR__ . '/../functions.php');
preg_match("/function soEventNode\(.*?\n}\n/s", $src, $m);
if (!$m) { fwrite(STDERR, "FAIL: could not find soEventNode in functions.php\n"); exit(1); }
define('HOME_URL', 'https://seatoutlet.com');
if (!defined('SO_EVENT_DEFAULT_HOURS')) define('SO_EVENT_DEFAULT_HOURS', 3);
// Stubs are defined through eval (one string per function) so the duplicate-function check, which greps source lines, does not mistake them for real definitions.
foreach ([
    'soEventSlug($e) { return "test-show"; }',
    'soVenueSlug($n, $i, $p) { return "arena"; }',
    'soPlaceLabel($e) { return "Dallas, TX"; }',
    'soEventStatusUrl($e) { return "https://schema.org/EventScheduled"; }',
    'buildEventPerformerSchema($e) { return ["@type" => "PerformingGroup", "name" => "Test"]; }',
    'soEventSchemaImage($e) { return "https://seatoutlet.com/i.jpg"; }',
    'soEventSchemaDescription($e) { return "Test show tickets."; }',
    'soEventListedFrom($e) { return ""; }',
] as $stub) { eval('function ' . $stub); }
eval($m[0]);
foreach (["soEventHasOffer", "soEventItemList"] as $fn) { preg_match("/(?:\\/\\*\\*(?:(?!\\*\\/).)*\\*\\/\\n)?function $fn\\(.*?\\n}\\n/s", $src, $mm); if (!$mm) { fwrite(STDERR, "FAIL: could not find $fn\n"); exit(1); } eval(preg_replace("/^\\/\\*\\*.*?\\*\\/\\n/s", "", $mm[0])); }
require_once __DIR__ . '/../inc/social.php';

$base = ['id' => 1, 'text' => ['name' => 'Test Show'], 'date' => ['date' => '2027-01-01'], '_metadata' => ['hasTickets' => true, 'ticketCount' => 5],
         'venue' => ['id' => 9, 'text' => ['name' => 'Arena']], 'city' => ['text' => ['name' => 'Dallas']], 'stateProvince' => ['text' => ['abbr' => 'TX']]];
$fail = 0;
function offerCheck($ok, $label) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }

$withHigh = call_user_func('soEventNode', $base + ['pricingInfo' => ['lowPrice' => ['value' => 40], 'highPrice' => ['value' => 300]]]);
offerCheck(($withHigh['offers']['@type'] ?? '') === 'AggregateOffer' && ($withHigh['offers']['highPrice'] ?? '') === '300.00', 'high price present: AggregateOffer with highPrice');

$noHigh = call_user_func('soEventNode', $base + ['pricingInfo' => ['lowPrice' => ['value' => 40]]]);
$o = $noHigh['offers'] ?? [];
offerCheck(($o['@type'] ?? '') === 'Offer' && ($o['price'] ?? '') === '40.00' && !isset($o['highPrice'], $o['lowPrice'], $o['offerCount']), 'no high price: plain Offer with price only');
foreach (['eventStatus', 'performer', 'startDate', 'location'] as $k) offerCheck(!empty($noHigh[$k]), "node has $k");

// An event with no real price (tickets listed but no lowest price, or no tickets at all) gets no offers key, and every list and the
// event page leave it out: Search Console flags an Event without an offer ("Missing field offers") and an offer without a price.
$noPrice = call_user_func('soEventNode', $base);
offerCheck(!isset($noPrice['offers']) && !soEventHasOffer($noPrice), 'tickets listed but no price: no offers key');
$noTickets = call_user_func('soEventNode', ['_metadata' => ['hasTickets' => false]] + $base);
offerCheck(!isset($noTickets['offers']) && !soEventHasOffer($noTickets), 'no tickets: no offers key');
offerCheck(soEventHasOffer($withHigh) && soEventHasOffer($noHigh), 'a priced event passes soEventHasOffer');
$priced = $base + ['pricingInfo' => ['lowPrice' => ['value' => 40]]];
$list = soEventItemList([$priced, ['id' => 2, '_metadata' => ['hasTickets' => false]] + $base, $base], 'https://seatoutlet.com/x');
offerCheck(is_array($list) && ($list['numberOfItems'] ?? 0) === 1 && count($list['itemListElement']) === 1, 'a list keeps only the priced event');
offerCheck(soEventItemList([$base, ['_metadata' => ['hasTickets' => false]] + $base], 'https://seatoutlet.com/x') === null, 'a list of unpriced events is no list at all');
foreach (['inc/seo-event.php' => '!soEventHasOffer($eventSchema)', 'inc/seo-tickets.php' => '!soEventHasOffer($node)', 'inc/entity-pages.php' => '!soEventHasOffer($evNode)', 'inc/seo.php' => 'if (!$offer) continue;'] as $file => $needle) {
    offerCheck(strpos((string) file_get_contents(__DIR__ . '/../' . $file), $needle) !== false, "$file leaves an unpriced event out");
}
offerCheck(preg_match('/foreach \(array_slice\(\$events, 0, 20\) as \$event\) \{.*?soEventHasOffer\(\$evNode\)\) \$nodes\[\] = \$evNode;/s', $src) === 1, 'the artist page leaves an unpriced event out');

$sa = soSocialSameAs();
offerCheck(in_array('https://x.com/SeatOutlet', $sa, true) && count($sa) >= 11, 'sameAs lists the social profiles');
$bad = array_filter($sa, function ($u) { return strpos($u, 'https://') !== 0 || preg_match('/password|passwd|pwd/i', $u); });
offerCheck(!$bad, 'sameAs holds only public https addresses');
echo $fail ? "$fail failed\n" : "event offer: all passed\n";
exit($fail ? 1 : 0);
