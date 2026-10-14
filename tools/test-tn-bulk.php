<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the bulk importer's helpers (inc/tn-bulk.php): request parameters, event mapping, run choice, delete safety, and
 * that the columns, the migration and the field names agree. Run: php tools/test-tn-bulk.php
 */
require_once __DIR__ . '/../inc/tn-bulk.php';

$fails = 0;
function tkCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

// 1. Request parameters
$p = tnBulkParams(3, 5000, null);
tkCheck('sorted by createdAt then id, as TicketNetwork asks', $p['sort'] === 'createdAt,id');
tkCheck('page and perPage are passed', $p['page'] === 3 && $p['perPage'] === 5000);
tkCheck('a full refresh has no filter', !isset($p['filter']));
tkCheck('page is never below 1', tnBulkParams(0, 100)['page'] === 1);
tkCheck('the changes pull filters on metadataInclusiveUpdatedAt', tnBulkParams(1, 5000, '2026-10-14T15:33:00Z')['filter'] === 'metadataInclusiveUpdatedAt ge 2026-10-14T15:33:00Z');
tkCheck('since is UTC with a Z and 10 minutes of overlap', tnBulkSinceIso(1792000000 + 600, 600) === gmdate('Y-m-d\TH:i:s\Z', 1792000000));
tkCheck('fields are a plain comma list without spaces', strpos($p['fields'], ' ') === false && strpos($p['fields'], 'id,text/name') === 0);
putenv('TN_BULK_PER_PAGE=99999'); tkCheck('perPage is capped at 10000', tnBulkPerPage() === 10000);
putenv('TN_BULK_PER_PAGE=5'); tkCheck('perPage has a floor of 100', tnBulkPerPage() === 100);
putenv('TN_BULK_PER_PAGE=abc'); tkCheck('a bad perPage falls back to 5000', tnBulkPerPage() === 5000);
putenv('TN_BULK_PER_PAGE');
putenv('TN_BULK_ENABLED'); tkCheck('the importer is off by default', tnBulkEnabled() === false);
putenv('TN_BULK_ENABLED=1'); tkCheck('TN_BULK_ENABLED=1 turns it on', tnBulkEnabled() === true);
putenv('TN_BULK_ENABLED=0'); tkCheck('TN_BULK_ENABLED=0 keeps it off', tnBulkEnabled() === false);
putenv('TN_BULK_ENABLED');

// 2. Field names are ones the Swagger lists for /events/bulk (copied from its "Fields" section)
$allowed = array_map('trim', explode(',', 'id, date/date, date/time, date/datetime, date/datetimeOffset, date/weekday, date/text/date, date/text/time, endDate/date, endDate/time, endDate/datetime, endDate/datetimeOffset, endDate/weekday, endDate/text/date, endDate/text/time, takedownAt, isMultiDayEvent, isTest, text/name, text/notes, scheduleStatus, geoLocation/latitude, geoLocation/longitude, geoLocation/distance/distance, geoLocation/distance/units, salesRank, _metadata/ticketCount, _metadata/hasTickets, _metadata/hasMyTickets, _metadata/hasTnPrimeTickets, _metadata/eTickets/ticketCount, _metadata/eTickets/hasTickets, _metadata/parkingTickets/ticketCount, _metadata/parkingTickets/hasTickets, _metadata/mercuryEligibleTicketCount, country/alphaCode, country/text/name, stateProvince/id, stateProvince/text/abbr, stateProvince/text/name, city/id, city/text/name, venue/id, venue/text/name, venue/text/formerNames, defaultCategory/path, defaultCategory/depth, defaultCategory/text/name, defaultCategory/ancestors/path, defaultCategory/ancestors/depth, defaultCategory/ancestors/text/name, performers/name, performers/id, performers/role, performers/isPerformance, pricingInfo/lowPrice/value, pricingInfo/lowPrice/currencyCode, pricingInfo/lowPrice/text/formatted, pricingInfo/averagePrice/value, pricingInfo/averagePrice/currencyCode, pricingInfo/averagePrice/text/formatted, pricingInfo/highPrice/value, pricingInfo/highPrice/currencyCode, pricingInfo/highPrice/text/formatted, eventPriority, venueConfig/id, metadataInclusiveUpdatedAt, updatedAt, createdAt, version, uriComponent, uriComponentHistory'));
$bad = array_diff(TN_BULK_FIELDS, $allowed);
tkCheck('every requested field is in the Swagger list' . ($bad ? ' (bad: ' . implode(', ', $bad) . ')' : ''), !$bad);
tkCheck('no field is requested twice', count(TN_BULK_FIELDS) === count(array_unique(TN_BULK_FIELDS)));

// 3. Mapping
$event = [
    'id' => 203518, 'text' => ['name' => 'Wicked'], 'date' => ['datetime' => '2026-10-20T19:30:00', 'datetimeOffset' => '2026-10-20T19:30:00-04:00'],
    'takedownAt' => '2026-10-21T01:30:00Z', 'isTest' => false, 'scheduleStatus' => 'On Schedule',
    'geoLocation' => ['latitude' => 40.7589, 'longitude' => -73.9851], '_metadata' => ['ticketCount' => 412, 'hasTickets' => true],
    'country' => ['alphaCode' => 'US'], 'stateProvince' => ['id' => 33, 'text' => ['abbr' => 'NY']], 'city' => ['id' => 1, 'text' => ['name' => 'New York']],
    'venue' => ['id' => 98, 'text' => ['name' => 'Gershwin Theatre']], 'defaultCategory' => ['path' => '.1859.1988.'],
    'performers' => [['id' => 1145, 'name' => 'Wicked', 'isPerformance' => true], ['id' => 'x', 'name' => 'dropped']],
    'pricingInfo' => ['lowPrice' => ['value' => 89.5, 'currencyCode' => 'USD'], 'averagePrice' => ['value' => 210], 'highPrice' => ['value' => 1450.256]],
    'uriComponent' => 'wicked', 'createdAt' => '2026-09-01T12:00:00Z', 'updatedAt' => '2026-10-14T09:00:00Z',
    'metadataInclusiveUpdatedAt' => '2026-10-14T10:15:00+00:00', 'version' => 7788,
];
$row = tnBulkMapEvent($event);
tkCheck('the row has exactly the importer columns, in order', is_array($row) && array_keys($row) === TN_BULK_COLUMNS);
tkCheck('id and name map', $row['event_id'] === 203518 && $row['name'] === 'Wicked');
tkCheck('the local start is kept as written, not converted', $row['starts_local'] === '2026-10-20 19:30:00' && $row['starts_offset'] === '2026-10-20T19:30:00-04:00');
tkCheck('timestamps become UTC', $row['takedown_at'] === '2026-10-21 01:30:00' && $row['tn_created_at'] === '2026-09-01 12:00:00' && $row['tn_meta_updated_at'] === '2026-10-14 10:15:00');
tkCheck('an offset timestamp is converted to UTC', tnBulkUtc('2026-10-20T19:30:00-04:00') === '2026-10-20 23:30:00');
tkCheck('flags map to 0 and 1', $row['is_test'] === 0 && $row['has_tickets'] === 1);
tkCheck('place and category map', $row['venue_id'] === 98 && $row['city_name'] === 'New York' && $row['state_abbr'] === 'NY' && $row['country_code'] === 'US' && $row['category_path'] === '.1859.1988.');
tkCheck('prices become two-decimal strings', $row['low_price'] === '89.50' && $row['avg_price'] === '210.00' && $row['high_price'] === '1450.26' && $row['currency'] === 'USD');
tkCheck('coordinates keep six decimals', $row['latitude'] === '40.758900' && $row['longitude'] === '-73.985100');
tkCheck('performers without a numeric id are dropped, the rest stored as JSON', $row['performers'] === '[{"id":1145,"name":"Wicked","isPerformance":true}]');
tkCheck('version and ticket count are integers', $row['tn_version'] === 7788 && $row['ticket_count'] === 412);
tkCheck('an event without an id is skipped', tnBulkMapEvent(['text' => ['name' => 'x']]) === null && tnBulkMapEvent('x') === null && tnBulkMapEvent(['id' => 0]) === null);
$sparse = tnBulkMapEvent(['id' => 5]);
tkCheck('a sparse event maps with nulls, not errors', $sparse['name'] === '' && $sparse['low_price'] === null && $sparse['performers'] === null && $sparse['starts_local'] === null && $sparse['has_tickets'] === 0);
tkCheck('a junk date is null', tnBulkUtc('not a date') === null && tnBulkLocal('soon') === null);
tkCheck('an out-of-range coordinate is null', tnBulkMapEvent(['id' => 6, 'geoLocation' => ['latitude' => 120, 'longitude' => 10]])['latitude'] === null);
tkCheck('a long name is cut to the column size', mb_strlen(tnBulkMapEvent(['id' => 7, 'text' => ['name' => str_repeat('é', 400)]])['name']) === 255);

// 4. SQL and migration agree
$sql = tnBulkUpsertSql();
tkCheck('one placeholder per column plus seen_at', substr_count($sql, '?') === count(TN_BULK_COLUMNS) + 1);
$mig = (string) file_get_contents(__DIR__ . '/../db/migrations/0050_tn_bulk_events.sql');
$missing = array_filter(TN_BULK_COLUMNS, fn($c) => strpos($mig, '`' . $c . '`') === false);
tkCheck('every importer column exists in migration 0050' . ($missing ? ' (missing: ' . implode(', ', $missing) . ')' : ''), !$missing && strpos($mig, '`seen_at`') !== false);
tkCheck('the upsert does not overwrite the key', strpos($sql, '`event_id` = VALUES') === false);

// 5. Run choice and delete safety
$noon = gmmktime(12, 0, 0, 10, 15, 2026); $eight = gmmktime(8, 0, 0, 10, 15, 2026);
tkCheck('never a full run before: full', tnBulkChooseMode(null, $noon) === 'full' && tnBulkChooseMode(0, $noon) === 'full');
tkCheck('a recent full run at noon: changes only', tnBulkChooseMode($noon - 3600, $noon) === 'incremental');
tkCheck('a full run over a day old but outside the quiet window: changes only', tnBulkChooseMode($noon - 90000, $noon) === 'incremental');
tkCheck('a full run over a day old inside the quiet window: full', tnBulkChooseMode($eight - 90000, $eight) === 'full');
tkCheck('a full run under a day old inside the window: changes only', tnBulkChooseMode($eight - 3600, $eight) === 'incremental');
tkCheck('nothing is deleted when the run did not reach the end', tnBulkMayDelete(false, 90000, 100000) === false);
tkCheck('nothing is deleted after an empty run', tnBulkMayDelete(true, 0, 100000) === false);
tkCheck('nothing is deleted when under half of the previous count came back', tnBulkMayDelete(true, 49999, 100000) === false);
tkCheck('a believable full run may delete', tnBulkMayDelete(true, 90000, 100000) === true && tnBulkMayDelete(true, 50000, 100000) === true);
tkCheck('the first full run may delete (nothing to compare with)', tnBulkMayDelete(true, 120, null) === true);

echo $fails ? "\ntn bulk: $fails failed\n" : "\ntn bulk: all passed\n";
exit($fails ? 1 : 0);
