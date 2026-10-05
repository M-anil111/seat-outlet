<?php
// Offline checks for the county helpers (no network, no database): php tools/test-counties.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('MYSQLI_STUB', true);
require_once dirname(__DIR__) . '/inc/counties.php';
$fail = 0;
function ok($cond, $msg) { global $fail; if (!$cond) { $fail++; echo "FAIL: $msg\n"; } }

$j = '{"result":{"geographies":{"Counties":[{"GEOID":"48453","NAME":"Travis County","STATE":"48","COUNTY":"453"}]}}}';
ok(soCountyParseCensus($j) === ['fips' => 48453, 'name' => 'Travis County'], 'parses Travis County');
ok(soCountyParseCensus('{"result":{"geographies":{"Counties":[{"GEOID":"01001","NAME":"Autauga County"}]}}}')['fips'] === 1001, 'keeps a leading zero as a number');
ok(soCountyParseCensus('{"result":{"geographies":{}}}') === null, 'no county returns null');
ok(soCountyParseCensus('not json') === null, 'bad json returns null');
ok(soCountyParseCensus('{"result":{"geographies":{"Counties":[{"GEOID":"x1","NAME":"Bad"}]}}}') === null, 'non numeric code rejected');
ok(soCountyFilter([5, 6, 6, '7']) === '(city/id eq 5 or city/id eq 6 or city/id eq 7)', 'filter de-duplicates and casts ids');
$many = soCountyFilter(range(1, 60));
ok(substr_count($many, 'city/id eq') === SO_COUNTY_MAX_CITIES, 'filter capped at SO_COUNTY_MAX_CITIES');
ok(in_array('TX', SO_COUNTY_STATES, true) && !in_array('ON', SO_COUNTY_STATES, true), 'US states in, Canadian provinces out');
echo $fail === 0 ? "counties ok\n" : "$fail failure(s)\n";
exit($fail === 0 ? 0 : 1);
