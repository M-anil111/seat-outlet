<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the listing filter allowlist: every filter shape the site builds is accepted, and anything else is refused.
 * Run: php tools/test-listing-filter.php
 */
require_once __DIR__ . '/../inc/listing-filter.php';

$fails = 0;
function lfilterCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$accept = [
    'listing date window' => "date/date ge 2026-10-13 and date/date le 2029-10-13",
    'listing default (US, dated)' => "country/alphaCode eq 'US' and date/date ge 2026-10-13 and date/date le 2029-10-13",
    'listing sorted by price, tickets only' => "date/date ge 2026-10-13 and _metadata/hasTickets eq true and pricingInfo/lowPrice/value le 100",
    'sitemap: US or CA, tickets' => "(country/alphaCode eq 'US' or country/alphaCode eq 'CA') and _metadata/hasTickets eq true",
    'category root (contains, no space)' => "contains(defaultCategory/path,'.1859.1986.') and date/date ge 2026-10-13",
    'category root (contains, with space)' => "contains(defaultCategory/path, '.1859.1986.')",
    'category root (startswith)' => "startswith(defaultCategory/path, '.1859.1988.')",
    'category exact path' => "defaultCategory/path eq '.1859.1986.1877.'",
    'city by id' => "city/id eq 1234 and date/date ge 2026-10-13",
    'venue by id' => "venue/id eq 99",
    'state by id' => "stateProvince/id eq 4",
    'several ids' => "id in (1,2,3)",
    'single id' => "id eq 5",
    'depth of categories' => "contains(path, '.1986.') and depth eq 2",
    'price cap' => "pricingInfo/lowPrice/value le 250",
    'country by code' => "country/alphaCode eq 'CA'",
    'repeated clause' => "country/alphaCode eq 'US' and country/alphaCode eq 'US'",
];
foreach ($accept as $label => $f) {
    lfilterCheck("accepts: $label", soLoadMoreFilterOk($f));
}

$reject = [
    'empty' => '',
    'OR outside parentheses (a widened result)' => "date/date ge 2026-10-13 or 1 eq 1",
    'always-true clause' => "_metadata/hasTickets eq true and 1 eq 1",
    'free text in a filter' => "name eq 'Staind'",
    'malformed date' => "date/date ge 2026-13-45",
    'statement separator' => "id eq 1; drop table x",
    'text search clause on the events list' => "startswith(text/name,'Staind')",
    'unknown field' => "secret/field eq 1",
    'overlong filter' => str_repeat("id eq 1 and ", 80) . "id eq 1",
    'nested or in a clause' => "(id eq 1 or id eq 2) and id eq 3 or 1 eq 1",
];
foreach ($reject as $label => $f) {
    lfilterCheck("refuses: $label", !soLoadMoreFilterOk($f));
}

// Geo and performer parameters have their own fixed shapes.
lfilterCheck('geo: nearby with coordinates and a radius', preg_match(LOAD_MORE_GEO_FILTER, 'nearby(40.7128,-74.0060,3000mi)') === 1);
lfilterCheck('geo: refuses an OR appended to the radius', preg_match(LOAD_MORE_GEO_FILTER, 'nearby(40.7,-74.0,3000mi) or 1 eq 1') !== 1);
lfilterCheck('performer: id eq N accepted', preg_match(LOAD_MORE_PERFORMER_FILTER, 'id eq 12') === 1);
lfilterCheck('performer: refuses an OR appended', preg_match(LOAD_MORE_PERFORMER_FILTER, 'id eq 1 or 1 eq 1') !== 1);

echo $fails ? "\n$fails failed\n" : "\nlisting filter: all passed\n";
exit($fails ? 1 : 0);
