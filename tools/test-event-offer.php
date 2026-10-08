<?php
// Offline test (no network, no database): the Event offer is an AggregateOffer with highPrice when the feed has one, and a plain
// Offer with a price (never a made-up high price) when it does not. Run: php tools/test-event-offer.php
$src = file_get_contents(__DIR__ . '/../functions.php');
preg_match("/function soEventNode\(.*?\n}\n/s", $src, $m);
if (!$m) { fwrite(STDERR, "FAIL: could not find soEventNode in functions.php\n"); exit(1); }
define('HOME_URL', 'https://seatoutlet.com');
if (!defined('SO_EVENT_DEFAULT_HOURS')) define('SO_EVENT_DEFAULT_HOURS', 3);
function soEventSlug($e) { return 'test-show'; }
function soVenueSlug($n, $i, $p) { return 'arena'; }
function soPlaceLabel($e) { return 'Dallas, TX'; }
function soEventStatusUrl($e) { return 'https://schema.org/EventScheduled'; }
function buildEventPerformerSchema($e) { return ['@type' => 'PerformingGroup', 'name' => 'Test']; }
function soEventSchemaImage($e) { return 'https://seatoutlet.com/i.jpg'; }
function soEventSchemaDescription($e) { return 'Test show tickets.'; }
function soEventListedFrom($e) { return ''; }
eval($m[0]);
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

$sa = soSocialSameAs();
offerCheck(in_array('https://x.com/SeatOutlet', $sa, true) && count($sa) >= 11, 'sameAs lists the social profiles');
$bad = array_filter($sa, function ($u) { return strpos($u, 'https://') !== 0 || preg_match('/password|passwd|pwd/i', $u); });
offerCheck(!$bad, 'sameAs holds only public https addresses');
echo $fail ? "$fail failed\n" : "event offer: all passed\n";
exit($fail ? 1 : 0);
