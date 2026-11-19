<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * The bulk catalog read side (inc/tn-bulk-read.php): the filter parser, freshness, the row-to-event mapping, listings from tn_bulk_events and
 * the category counts the sitemap uses. Needs the migrated database. Uses event ids 9200001 and up and deletes them at the end.
 * Run: php tools/test-tn-bulk-read.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';

$fail = 0;
function brCheck(string $what, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else echo "ok   $what\n"; }
$today = date('Y-m-d');

// ---- the switch and freshness
putenv('TN_BULK_ENABLED'); brCheck('off by default', tnBulkReadEnabled(), false);
putenv('TN_BULK_ENABLED=1'); brCheck('on with TN_BULK_ENABLED=1', tnBulkReadEnabled(), true);
putenv('TN_BULK_READ=0'); brCheck('TN_BULK_READ=0 turns only the reading off', tnBulkReadEnabled(), false); putenv('TN_BULK_READ');
$now = 1800000000;
brCheck('fresh: a full refresh 2 hours ago', tnBulkFresh(['last_full_ok' => $now - 7200, 'last_full_count' => 90000], $now), true);
brCheck('stale: 40 hours ago', tnBulkFresh(['last_full_ok' => $now - 40 * 3600, 'last_full_count' => 90000], $now), false);
brCheck('never imported', tnBulkFresh([], $now), false);
brCheck('an import that saw no events is not fresh', tnBulkFresh(['last_full_ok' => $now - 60, 'last_full_count' => 0], $now), false);
brCheck('a clock in the future is not fresh', tnBulkFresh(['last_full_ok' => $now + 99999, 'last_full_count' => 5], $now), false);

// ---- the parser understands what the listing pages send, and nothing else
$loc = locationListingParams('city/id eq 677', 20, 2, '', 'soonest');
$q = tnBulkParseListing($loc);
brCheck('a city listing parses', $q !== null && $q['page'] === 2 && $q['perPage'] === 20 && in_array('city_id = ?', $q['where'], true) && in_array('has_tickets = 1', $q['where'], true), true);
brCheck('category listing parses (startswith)', tnBulkParseListing(categoryListingParams('.1859.1986.', 10, 1, '', 'popular')) !== null, true);
brCheck('price cap parses', tnBulkParseListing(locationListingParams('venue/id eq 5', 5, 1, '', 'price', 80)) !== null, true);
brCheck('performer page parses', tnBulkParseListing(performerPageEventsSpec(123, 8)[1]) !== null, true);
brCheck('country filter parses', tnBulkParseListing(locationListingParams("country/alphaCode eq 'US'", 5, 1, '', 'soonest')) !== null, true);
brCheck('an unknown clause means no answer', tnBulkParseListing(['filter' => "city/id eq 5 and contains(text/name, 'x')"]), null);
brCheck('an unknown sort means no answer', tnBulkParseListing(['filter' => 'city/id eq 5', 'sort' => 'rank']), null);
brCheck('no filter means no answer', tnBulkParseListing(['sort' => 'date/date']), null);
brCheck('a malformed performer filter means no answer', tnBulkParseListing(['filter' => 'city/id eq 5', 'performerFilter' => "id eq 5 or id eq 6"]), null);
brCheck('SQL in a value cannot get through', tnBulkParseListing(['filter' => "city/id eq 5; DROP TABLE x"]), null);
brCheck('perPage is capped at 200', tnBulkParseListing(['filter' => 'city/id eq 5', 'perPage' => 9999])['perPage'], 200);
// Category, genre, hub, holiday and home-feed listings filter with contains(defaultCategory/path, '.<id>.') (SE Ranking audit, Nov 12 2026:
// 57 category and genre pages answered 503 because every one of these went to the live API and was refused by the call budget).
$qc = tnBulkParseListing(locationListingParams("contains(defaultCategory/path, '.1906.') and country/alphaCode eq 'US'", 20, 1, '', 'soonest'));
brCheck('category page listing parses (contains)', $qc !== null && in_array('category_path LIKE ?', $qc['where'], true) && in_array('%.1906.%', $qc['values'], true), true);
brCheck('hub listing parses (contains, no space after the comma)', tnBulkParseListing(['filter' => 'date/date ge 2026-11-14 and date/date le 2027-11-14 and _metadata/hasTickets eq true and contains(defaultCategory/path,\'.1859.1986.\')', 'sort' => '-salesRank']) !== null, true);
brCheck('a contains value that is not a dotted id path means no answer', tnBulkParseListing(['filter' => "contains(defaultCategory/path, 'rock')"]), null);
brCheck('a contains value with a LIKE wildcard means no answer', tnBulkParseListing(['filter' => "contains(defaultCategory/path, '.19%.')"]), null);
$qy = tnBulkParseListing(locationListingParams(soCountyFilter([11, 12, 13]), 20, 1, '', 'soonest'));
brCheck('county listing parses (a group of city ids)', $qy !== null && in_array('city_id IN (?,?,?)', $qy['where'], true) && count(array_intersect([11, 12, 13], $qy['values'])) === 3, true);
brCheck('a group that mixes fields means no answer', tnBulkParseListing(['filter' => '(city/id eq 1 or venue/id eq 2)']), null);
brCheck('a group with "and" inside means no answer', tnBulkParseListing(['filter' => '(city/id eq 1 and city/id eq 2)']), null);
// A parameter the copy cannot apply must never be dropped silently: a "near you" listing (geoFilter) answered from the copy would list the
// whole country under a "near you" heading.
$near = locationListingParams("country/alphaCode eq 'US' and contains(defaultCategory/path, '.1906.')", 12, 1, '', 'popular');
$near['geoFilter'] = 'nearby(30.270000,-97.740000,50mi)';
brCheck('a near-you listing (geoFilter) means no answer', tnBulkParseListing($near), null);
$nearPath = categoryListingParams('.1859.1986.', 12, 1, '', 'soonest'); $nearPath['geoFilter'] = 'nearby(30.270000,-97.740000,50mi)';
brCheck('a near-you listing by path means no answer', tnBulkParseListing($nearPath), null);
foreach (['q' => 'taylor', 'categoryFilter' => "path eq '.1.'", 'eventFilter' => 'id eq 5', 'latitude' => '30.2', 'longitude' => '-97.7', 'radius' => '50'] as $k => $v) {
    brCheck("an unknown parameter ($k) means no answer", tnBulkParseListing(['filter' => 'city/id eq 5', $k => $v]), null);
}
brCheck('the parameters that do not change which events match are accepted', tnBulkParseListing(['filter' => 'city/id eq 5', 'sort' => '-salesRank', 'salesRankOptions' => '{"interval":"day","metric":"orderVolume"}', 'includeTotalCount' => 'true', 'perPage' => 12, 'page' => 1]) !== null, true);

// ---- the table
MYSQLI->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9200001 AND 9200099");
$put = function (int $id, array $o = []) {
    $e = ['id' => $id, 'text' => ['name' => $o['name'] ?? "Zq Show $id"], 'date' => ['datetime' => ($o['date'] ?? date('Y-m-d', strtotime('+10 days'))) . 'T19:30:00', 'datetimeOffset' => ($o['date'] ?? date('Y-m-d', strtotime('+10 days'))) . 'T19:30:00-05:00'],
          'venue' => ['id' => $o['venue'] ?? 9200001, 'text' => ['name' => 'Zq Hall']], 'city' => ['id' => $o['city'] ?? 9200001, 'text' => ['name' => 'Zqville']],
          'stateProvince' => ['id' => 9200001, 'text' => ['abbr' => 'ZQ']], 'country' => ['alphaCode' => $o['country'] ?? 'US'], 'defaultCategory' => ['path' => $o['path'] ?? '.1859.1986.1987.'],
          'performers' => [['id' => $o['perf'] ?? 9200001, 'name' => 'Zq Act', 'isPerformance' => true]], 'pricingInfo' => ['lowPrice' => ['value' => $o['low'] ?? 50, 'currencyCode' => 'USD']],
          '_metadata' => ['ticketCount' => $o['tickets'] ?? 10, 'hasTickets' => $o['has'] ?? true], 'uriComponent' => 'zq', 'isTest' => $o['test'] ?? false];
    if (isset($o['takedown'])) $e['takedownAt'] = $o['takedown'];
    $m = tnBulkMapEvent($e); $c = array_map(fn($k) => $m[$k], TN_BULK_COLUMNS); $c[] = gmdate('Y-m-d H:i:s');
    $st = MYSQLI->prepare(tnBulkUpsertSql()); $st->bind_param(str_repeat('s', count($c)), ...$c); $st->execute(); $st->close();
};
$put(9200001, ['low' => 90, 'date' => date('Y-m-d', strtotime('+3 days')), 'tickets' => 5]);
$put(9200002, ['low' => 30, 'date' => date('Y-m-d', strtotime('+20 days')), 'tickets' => 50]);
$put(9200003, ['low' => 60, 'date' => date('Y-m-d', strtotime('+7 days')), 'path' => '.1859.1988.1989.']);
$put(9200004, ['has' => false]);
$put(9200005, ['test' => true]);
$put(9200006, ['date' => date('Y-m-d', strtotime('-3 days'))]);
$put(9200007, ['takedown' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600)]);
$put(9200008, ['city' => 9200002, 'perf' => 9200002]);

$run = fn(array $params) => tnBulkRunListing(MYSQLI, tnBulkParseListing($params));
$r = $run(locationListingParams('city/id eq 9200001', 20, 1, '', 'soonest'));
brCheck('city listing: only live events with tickets, soonest first', array_column($r['results'], 'id'), [9200001, 9200003, 9200002]);
brCheck('total count matches', $r['totalCount'], 3);
brCheck('an unsold, test, past or taken-down event is left out', count(array_intersect(array_column($r['results'], 'id'), [9200004, 9200005, 9200006, 9200007])), 0);
$r = $run(locationListingParams('city/id eq 9200001', 20, 1, '', 'price'));
brCheck('cheapest first', array_column($r['results'], 'id'), [9200002, 9200003, 9200001]);
$r = $run(locationListingParams('city/id eq 9200001', 20, 1, '', 'popular'));
brCheck('popular: most tickets first', $r['results'][0]['id'], 9200002);
$r = $run(locationListingParams('city/id eq 9200001', 2, 2, '', 'soonest'));
brCheck('page 2 of 2 per page', [array_column($r['results'], 'id'), $r['totalCount']], [[9200002], 3]);
$r = $run(locationListingParams('city/id eq 9200001', 20, 1, '', 'soonest', 55));
brCheck('price cap', array_column($r['results'], 'id'), [9200002]);
$r = $run(categoryListingParams('.1859.1988.', 20, 1, '', 'soonest') );
brCheck('a category page only has its own category', array_column(array_filter($r['results'], fn($e) => in_array($e['id'], [9200001, 9200002, 9200003, 9200008], true)), 'id'), [9200003]);
$r = $run(performerPageEventsSpec(9200002, 20)[1]);
brCheck('performer page: that performer only', array_column($r['results'], 'id'), [9200008]);
$fixtures = [9200001, 9200002, 9200003, 9200008];
$r = $run(locationListingParams("contains(defaultCategory/path, '.1986.') and country/alphaCode eq 'US'", 20, 1, '', 'soonest'));
brCheck('contains: a category in the middle of the path matches its events only', array_values(array_intersect(array_column($r['results'], 'id'), $fixtures)), [9200001, 9200008, 9200002]);
$r = $run(locationListingParams("contains(defaultCategory/path, '.1988.') and country/alphaCode eq 'US'", 20, 1, '', 'soonest'));
brCheck('contains: another category, its own event only', array_values(array_intersect(array_column($r['results'], 'id'), $fixtures)), [9200003]);
$r = $run(locationListingParams(soCountyFilter([9200001, 9200002]), 20, 1, '', 'soonest'));
brCheck('county: events in any of its cities, soonest first', array_values(array_intersect(array_column($r['results'], 'id'), $fixtures)), [9200001, 9200003, 9200008, 9200002]);
$ev = $run(locationListingParams('city/id eq 9200001', 1, 1, '', 'soonest'))['results'][0];
brCheck('the event is API-shaped', [$ev['id'], $ev['text']['name'], $ev['venue']['text']['name'], $ev['city']['text']['name'], $ev['stateProvince']['text']['abbr'], $ev['defaultCategory']['path'], $ev['performers'][0]['id'], $ev['pricingInfo']['lowPrice']['value'], $ev['pricingInfo']['lowPrice']['text']['formatted'], $ev['_bulk']],
    [9200001, 'Zq Show 9200001', 'Zq Hall', 'Zqville', 'ZQ', '.1859.1986.1987.', 9200001, 90.0, '$90', true]);
brCheck('date fields the pages read', [substr($ev['date']['date'], 10), $ev['date']['datetimeOffset'] !== ''], ['T19:30:00', true]);

// ---- the read-through (switch + freshness)
putenv('TN_BULK_ENABLED');
// ---- entity stand-ins (SE Ranking audit, 14 Nov 2026: venue, city and artist pages answered 503 when their first lookup was refused)
$put(9200010, ['venue' => 9200050, 'city' => 9200050, 'perf' => 9200050, 'date' => date('Y-m-d', strtotime('-3 days'))]);   // only a past event
brCheck('entity request: a venue', tnBulkEntityRequest('/catalog/v2/venues/9200001'), ['venue', 9200001]);
brCheck('entity request: a city, trailing slash', tnBulkEntityRequest('/catalog/v2/cities/9200001/'), ['city', 9200001]);
brCheck('entity request: a performer', tnBulkEntityRequest('/catalog/v2/performers/9200002'), ['performer', 9200002]);
brCheck('entity request: an event list is not an entity', tnBulkEntityRequest('/catalog/v2/events/'), null);
brCheck('entity request: a sub-resource is not an entity', tnBulkEntityRequest('/catalog/v2/venues/9200001/configurations'), null);
$v = tnBulkEntity(MYSQLI, 'venue', 9200001);
brCheck('venue stand-in: the fields the venue page reads', [$v['id'], $v['text']['name'], $v['city']['id'], $v['city']['text']['name'], $v['stateProvince']['id'], $v['stateProvince']['text']['abbr'], $v['_bulk']],
    [9200001, 'Zq Hall', 9200001, 'Zqville', 9200001, 'ZQ', true]);
brCheck('venue stand-in is not "missing"', tnEntityMissing($v), false);
$c = tnBulkEntity(MYSQLI, 'city', 9200001);
brCheck('city stand-in: the fields the city page reads', [$c['id'], $c['text']['name'], $c['stateProvince']['id'], $c['stateProvince']['text']['abbr'], $c['country']['alphaCode'], $c['_bulk']], [9200001, 'Zqville', 9200001, 'ZQ', 'US', true]);
$p = tnBulkEntity(MYSQLI, 'performer', 9200002);
brCheck('performer stand-in: id and name only, no made-up category', $p, ['id' => 9200002, 'text' => ['name' => 'Zq Act'], '_bulk' => true]);
brCheck('performer id is matched whole (9200002 is not 92000021)', tnBulkEntity(MYSQLI, 'performer', 920000), null);
brCheck('no stand-in for an entity with only past events', [tnBulkEntity(MYSQLI, 'venue', 9200050), tnBulkEntity(MYSQLI, 'city', 9200050), tnBulkEntity(MYSQLI, 'performer', 9200050)], [null, null, null]);
brCheck('no stand-in for an unknown id or kind', [tnBulkEntity(MYSQLI, 'venue', 9200099), tnBulkEntity(MYSQLI, 'state', 9200001)], [null, null]);
$put(9200011, ['city' => 9200060, 'venue' => 9200060, 'perf' => 9200060]);
MYSQLI->query("UPDATE tn_bulk_events SET state_abbr = 'TX', venue_name = 'Zq Texas Hall' WHERE event_id = 9200011");
brCheck('a known state code gets its full name', tnBulkEntity(MYSQLI, 'venue', 9200060)['stateProvince']['text']['name'] ?? null, 'Texas');
$src = (string) file_get_contents(__DIR__ . '/../functions.php');
brCheck('the bulk-first read (before any live call) never answers an entity: the real record wins', (bool) preg_match('/\$bulk = tnBulkFallback\(\$endpoint, \$params\);\s*if \(\$bulk !== null\) return \$bulk;/', $src), true);
brCheck('every failure path may answer an entity from the copy', substr_count($src, 'tnBulkFallback($endpoint, $params, true)'), 4);
brCheck('a stand-in performer is never kept as the record', strpos($src, "isset(\$result['id']) && empty(\$result['_bulk'])") !== false, true);
brCheck('a stand-in performer is never remembered as dead', strpos((string) file_get_contents(__DIR__ . '/../performer.php'), "empty(\$performer['_bulk'])") !== false, true);

brCheck('fallback is silent while the feature is off', tnBulkFallback('/catalog/v2/events/', locationListingParams('city/id eq 9200001', 20, 1, '', 'soonest')), null);
brCheck('fallback ignores a single-event request', tnBulkFallback('/catalog/v2/events/9200001', []), null);

// ---- the read-through when the switch is on and the last full refresh is recent (a child process: the state is read once per request)
$child = function (string $env, string $code) {
    $php = escapeshellarg(PHP_BINARY);
    $script = "foreach (['CONSUMER_KEY','CONSUMER_SECRET','GAPI_KEY','AWS_ACCESS_KEY','AWS_SECRET_KEY','RECAPTCHA_SECRET_KEY'] as \$k) { if ((string) getenv(\$k) === '') putenv(\$k . '=x'); } require " . var_export(realpath(__DIR__ . '/../functions.php'), true) . '; ' . $code;
    return trim((string) shell_exec($env . ' ' . $php . ' -r ' . escapeshellarg($script) . ' 2>/dev/null'));
};
$saved = [];
$res = MYSQLI->query('SELECT name, value FROM tn_bulk_state'); while ($res && ($r = $res->fetch_row())) $saved[$r[0]] = $r[1];
$setState = function (array $rows) { MYSQLI->query('DELETE FROM tn_bulk_state'); foreach ($rows as $k => $v) { $st = MYSQLI->prepare('INSERT INTO tn_bulk_state (name, value, updated_at) VALUES (?, ?, NOW())'); $st->bind_param('ss', $k, $v); $st->execute(); $st->close(); } };
$probe = '$r = tnBulkFallback("/catalog/v2/events/", locationListingParams("city/id eq 9200001", 20, 1, "", "soonest")); echo $r === null ? "null" : implode(",", array_column($r["results"], "id")) . "|" . $r["totalCount"] . "|" . (int) !empty($GLOBALS["so_bulk_served"]);';
$setState(['last_full_ok' => (string) (time() - 3600), 'last_full_count' => '90000']);
brCheck('on and fresh: a listing is answered from the copy', $child('TN_BULK_ENABLED=1', $probe), '9200001,9200003,9200002|3|1');
brCheck('on but TN_BULK_READ=0: not answered', $child('TN_BULK_ENABLED=1 TN_BULK_READ=0', $probe), 'null');
// Bulk first (tnRequestRaw): with the read switch on and fresh, a listing request is answered from the copy before any ticket call.
$bulkFirst = '$r = tnRequestRaw("/catalog/v2/events/", locationListingParams("city/id eq 9200001", 20, 1, "", "soonest"), "GET", 0); echo is_array($r) && !empty($r["_bulk"]) ? implode(",", array_column($r["results"], "id")) . "|" . $r["totalCount"] : "other";';
brCheck('bulk first: on, fresh and switched to read: the listing comes from the copy', $child('TN_BULK_ENABLED=1 TN_BULK_READ=1', $bulkFirst), '9200001,9200003,9200002|3');

brCheck('off: not answered', $child('TN_BULK_ENABLED=0', $probe), 'null');
$setState(['last_full_ok' => (string) (time() - 50 * 3600), 'last_full_count' => '90000']);
brCheck('on but the last full refresh is 50 hours old: not answered', $child('TN_BULK_ENABLED=1', $probe), 'null');
$setState([]);
brCheck('on but never imported: not answered', $child('TN_BULK_ENABLED=1', $probe), 'null');
$setState(['last_full_ok' => (string) (time() - 3600), 'last_full_count' => '90000']);
$unparsed = '$r = tnBulkFallback("/catalog/v2/events/", ["filter" => "city/id eq 9200001 and contains(text/name, \\"x\\")", "sort" => "date/date"]); echo $r === null ? "null" : "answered";';
$ent = '$a = tnBulkFallback("/catalog/v2/venues/9200001", [], true); $b = tnBulkFallback("/catalog/v2/venues/9200001", []); echo ($a["text"]["name"] ?? "null") . "|" . ($b === null ? "null" : "answered");';
brCheck('ready: a failed venue lookup is answered from the copy, the bulk-first read is not', $child('TN_BULK_ENABLED=1', $ent), 'Zq Hall|null');
brCheck('a request the parser does not understand is not answered even when ready', $child('TN_BULK_ENABLED=1', $unparsed), 'null');
$setState($saved);

// ---- category counts for the sitemap
// A category whose events are all outside the US (West End: London) lists nothing on its page, which asks for country/alphaCode eq 'US':
// it must not count, or the sitemap lists an empty, noindex page (18 Nov 2026).
$put(9200020, ['path' => '.1859.1989.9200077.', 'country' => 'GB']);
$counts = tnBulkCategoryCounts(MYSQLI);
brCheck('counts: a category with only non-US events is absent (its page lists US events only)', isset($counts[9200077]), false);
brCheck('counts: an event counts toward every category in its path', [$counts[1859] >= 3, $counts[1986] >= 2, $counts[1988] >= 1], [true, true, true]);
brCheck('counts: a category nothing is listed in is absent', isset($counts[9999999]), false);
MYSQLI->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9200001 AND 9200099");
exit($fail ? 1 : 0);
