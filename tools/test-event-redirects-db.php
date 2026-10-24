<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test for removed-event redirects (inc/event-redirects.php): what is remembered from visits, performer pages and the bulk
 * import, what a refresh keeps, and where a removed event goes. Needs the migrated database (run after db/migrate.php).
 * Uses event ids 9100001 and up and deletes them at the end. Run: php tools/test-event-redirects-db.php
 */
// functions.php refuses to load without the API and mail settings; the test never calls those services, so placeholders do.
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/tn-bulk.php';

$fail = 0;
function erCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else echo "ok   $what\n";
}
$clean = function () {
    MYSQLI->query("DELETE FROM event_redirects WHERE event_id BETWEEN 9100001 AND 9100099");
    MYSQLI->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9100001 AND 9100099");
    MYSQLI->query("DELETE FROM url_slugs WHERE (type = 'event' AND ext_id BETWEEN 9100001 AND 9100099) OR (type = 'performer' AND ext_id BETWEEN 9100001 AND 9100099)");
};
$clean();
$ev = fn(int $id, string $name, int $perfId, string $perf, string $path, string $date = '2027-03-05') => [
    'id' => $id, 'text' => ['name' => $name], 'date' => ['date' => $date . 'T19:30:00'],
    'city' => ['text' => ['name' => 'Austin']], 'stateProvince' => ['text' => ['abbr' => 'TX']],
    'performers' => $perfId ? [['id' => $perfId, 'name' => $perf]] : [], 'defaultCategory' => ['path' => $path],
];
$row = function (int $id) {
    $r = MYSQLI->query("SELECT * FROM event_redirects WHERE event_id = $id")->fetch_assoc();
    return $r ?: null;
};

// 1. a visit stores performer, category, slug, name, date
erCheck('remember reports one event', soEventRememberMany([$ev(9100001, 'Zq Band Live', 9100001, 'Zq Band', '.1986.1987.')], 'visit'), 1);
$r = $row(9100001);
erCheck('performer and category stored', [(int) $r['performer_id'], $r['performer_name'], $r['category_path']], [9100001, 'Zq Band', '.1986.1987.']);
erCheck('canonical slug stored', $r['event_slug'] !== null && $r['event_slug'] !== '' && strpos($r['event_slug'], 'zq-band-live') !== false, true);
erCheck('date and name stored', [substr((string) $r['event_date'], 0, 10), $r['event_name'], $r['source']], ['2027-03-05', 'Zq Band Live', 'visit']);

// 2. a refresh with an empty performer or category never blanks what is known; a changed performer replaces it
soEventRememberMany([$ev(9100001, 'Zq Band Live', 0, '', '')], 'catalog');
$r = $row(9100001);
erCheck('an empty refresh keeps performer and category', [(int) $r['performer_id'], $r['category_path'], $r['source']], [9100001, '.1986.1987.', 'visit']);
soEventRememberMany([$ev(9100001, 'Zq Band Live', 9100002, 'Zq Other', '.1986.1987.')], 'catalog');
erCheck('a new performer replaces the old', [(int) $row(9100001)['performer_id'], $row(9100001)['performer_name']], [9100002, 'Zq Other']);
erCheck('the same batch twice keeps one row', MYSQLI->query("SELECT COUNT(*) FROM event_redirects WHERE event_id = 9100001")->fetch_row()[0], '1');

// 3. redirect targets: performer first, then category, then nothing known
erCheck('removed event goes to the performer page', strpos(soEventRedirectTarget(null, 9100001), '/artist/') === 0, true);
soEventRememberMany([$ev(9100003, 'Zq Game', 0, '', '.1988.1990.')], 'visit');
erCheck('no performer: the sports hub', soEventRedirectTarget(null, 9100003), '/game-day-tickets');
soEventRememberMany([$ev(9100004, 'Zq Show', 0, '', '.1989.2000.')], 'visit');
erCheck('no performer: the Broadway hub', soEventRedirectTarget(null, 9100004), '/buy-broadway-tickets');
soEventRememberMany([$ev(9100005, 'Zq Fest', 0, '', '.1877.5.')], 'visit');
erCheck('no performer: the festivals hub', soEventRedirectTarget(null, 9100005), '/upcoming-music-festivals');
erCheck('an event never seen stays "nothing known" (the page answers 404)', soEventRedirectTarget(null, 9100099), '/buy-tickets-online');
erCheck('an invalid id stays "nothing known"', soEventRedirectTarget(null, 0), '/buy-tickets-online');
foreach ([9100001, 9100003, 9100004, 9100005, 9100099] as $id) erCheck("target for $id is never an event page (no loop)", strpos(soEventRedirectTarget(null, $id), '/event/'), false);

// 4. bulk import: an event nobody opened is covered through tn_bulk_events, and through the importer's own write
$bulk = $ev(9100010, 'Zq Bulk Show', 9100010, 'Zq Bulk Act', '.1986.1987.');
$bulk['uriComponent'] = 'x'; $bulk['_metadata'] = ['hasTickets' => true];
$mapped = tnBulkMapEvent($bulk);
$cols = array_map(fn($c) => $mapped[$c], TN_BULK_COLUMNS); $cols[] = gmdate('Y-m-d H:i:s');
$st = MYSQLI->prepare(tnBulkUpsertSql()); $st->bind_param(str_repeat('s', count($cols)), ...$cols); $st->execute(); $st->close();
erCheck('bulk copy only: goes to the bulk performer page', strpos(soEventRedirectTarget(null, 9100010), '/artist/') === 0, true);
erCheck('bulk row is not in event_redirects yet', $row(9100010), null);
soEventRememberMany([$bulk], 'bulk');
$r = $row(9100010);
erCheck('the importer write stores performer, category and date', [(int) $r['performer_id'], $r['category_path'], substr((string) $r['event_date'], 0, 10), $r['source']], [9100010, '.1986.1987.', '2027-03-05', 'bulk']);
erCheck('bulk rows carry no slug (none registered per catalog event)', in_array($r['event_slug'], [null, ''], true), true);
soEventRememberMany([$bulk], 'visit');
erCheck('a later visit fills the slug', !in_array($row(9100010)['event_slug'], [null, ''], true), true);

// 5. a remembered row without a performer still lets the bulk copy supply one
soEventRememberMany([$ev(9100011, 'Zq Half', 0, '', '')], 'visit');
$b2 = $ev(9100011, 'Zq Half', 9100011, 'Zq Half Act', '.1986.1987.'); $b2['uriComponent'] = 'y'; $b2['_metadata'] = [];
$m2 = tnBulkMapEvent($b2); $c2 = array_map(fn($c) => $m2[$c], TN_BULK_COLUMNS); $c2[] = gmdate('Y-m-d H:i:s');
$st = MYSQLI->prepare(tnBulkUpsertSql()); $st->bind_param(str_repeat('s', count($c2)), ...$c2); $st->execute(); $st->close();
erCheck('row without performer falls back to the bulk copy', strpos(soEventRedirectTarget(null, 9100011), '/artist/') === 0, true);

// 6. bad input never throws
erCheck('events without an id are skipped', soEventRememberMany([['text' => ['name' => 'x']], 'junk', []], 'visit'), 0);
erCheck('an unknown source is stored as visit', (function () use ($ev, $row) { soEventRememberMany([$ev(9100020, 'Zq Src', 0, '', '.1986.')], 'evil'); return $row(9100020)['source']; })(), 'visit');

$clean();
exit($fail ? 1 : 0);
