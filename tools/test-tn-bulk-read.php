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

// ---- the table
MYSQLI->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9200001 AND 9200099");
$put = function (int $id, array $o = []) {
    $e = ['id' => $id, 'text' => ['name' => $o['name'] ?? "Zq Show $id"], 'date' => ['datetime' => ($o['date'] ?? date('Y-m-d', strtotime('+10 days'))) . 'T19:30:00', 'datetimeOffset' => ($o['date'] ?? date('Y-m-d', strtotime('+10 days'))) . 'T19:30:00-05:00'],
          'venue' => ['id' => $o['venue'] ?? 9200001, 'text' => ['name' => 'Zq Hall']], 'city' => ['id' => $o['city'] ?? 9200001, 'text' => ['name' => 'Zqville']],
          'stateProvince' => ['id' => 9200001, 'text' => ['abbr' => 'ZQ']], 'country' => ['alphaCode' => 'US'], 'defaultCategory' => ['path' => $o['path'] ?? '.1859.1986.1987.'],
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
$ev = $run(locationListingParams('city/id eq 9200001', 1, 1, '', 'soonest'))['results'][0];
brCheck('the event is API-shaped', [$ev['id'], $ev['text']['name'], $ev['venue']['text']['name'], $ev['city']['text']['name'], $ev['stateProvince']['text']['abbr'], $ev['defaultCategory']['path'], $ev['performers'][0]['id'], $ev['pricingInfo']['lowPrice']['value'], $ev['pricingInfo']['lowPrice']['text']['formatted'], $ev['_bulk']],
    [9200001, 'Zq Show 9200001', 'Zq Hall', 'Zqville', 'ZQ', '.1859.1986.1987.', 9200001, 90.0, '$90', true]);
brCheck('date fields the pages read', [substr($ev['date']['date'], 10), $ev['date']['datetimeOffset'] !== ''], ['T19:30:00', true]);

// ---- the read-through (switch + freshness)
putenv('TN_BULK_ENABLED');
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
brCheck('off: not answered', $child('TN_BULK_ENABLED=0', $probe), 'null');
$setState(['last_full_ok' => (string) (time() - 50 * 3600), 'last_full_count' => '90000']);
brCheck('on but the last full refresh is 50 hours old: not answered', $child('TN_BULK_ENABLED=1', $probe), 'null');
$setState([]);
brCheck('on but never imported: not answered', $child('TN_BULK_ENABLED=1', $probe), 'null');
$setState(['last_full_ok' => (string) (time() - 3600), 'last_full_count' => '90000']);
$unparsed = '$r = tnBulkFallback("/catalog/v2/events/", ["filter" => "city/id eq 9200001 and contains(text/name, \\"x\\")", "sort" => "date/date"]); echo $r === null ? "null" : "answered";';
brCheck('a request the parser does not understand is not answered even when ready', $child('TN_BULK_ENABLED=1', $unparsed), 'null');
$setState($saved);

// ---- category counts for the sitemap
$counts = tnBulkCategoryCounts(MYSQLI);
brCheck('counts: an event counts toward every category in its path', [$counts[1859] >= 3, $counts[1986] >= 2, $counts[1988] >= 1], [true, true, true]);
brCheck('counts: a category nothing is listed in is absent', isset($counts[9999999]), false);
MYSQLI->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9200001 AND 9200099");
exit($fail ? 1 : 0);
