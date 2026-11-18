<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * The event page's "other events in this city" list (soSpecCityEvents, inc/page-spec.php) is answered by the local catalog copy when the
 * copy is fresh, without a TicketNetwork call: on a cold event page it was the third live round trip in a row (6.5 to 9.1 s, 18 Nov 2026).
 * With the copy off it still asks TicketNetwork, as before. Needs the migrated database; uses event ids 9300011 to 9300013 and deletes them.
 * Run: php tools/test-spec-city-bulk.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
$mode = $argv[1] ?? '';
putenv('TN_BULK_ENABLED=' . ($mode === 'off' ? '0' : '1')); putenv('TN_BULK_READ');
putenv('TN_CALLS_PER_SECOND=0.00001');   // a live call would be refused by the budget: nothing here may reach the network
require_once __DIR__ . '/../functions.php';

$fail = 0;
function scCheck(string $what, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } }

if ($mode === 'off') {
    // Child run, copy switched off: the list comes from the live path (refused by the budget here), never from the copy directly.
    $r = soSpecCityEvents('concert', 9300011);
    $fromCopyFirst = false;
    foreach ($r['events'] ?? [] as $ev) { if (!empty($ev['_bulk']) && empty($GLOBALS['so_bulk_served'])) $fromCopyFirst = true; }
    scCheck('copy off: the copy is not read first', $fromCopyFirst, false);
    exit($fail ? 1 : 0);
}

$now = time();
$seen = $now - 3600;
MYSQLI->query('DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9300011 AND 9300013');
$prevState = [];
$res = MYSQLI->query('SELECT name, value FROM tn_bulk_state');
while ($res && ($row = $res->fetch_row())) $prevState[$row[0]] = $row[1];
$put = function (int $id, string $path, int $days) use ($seen) {
    $d = date('Y-m-d', strtotime("+$days days"));
    $e = ['id' => $id, 'text' => ['name' => "Sc Show $id"], 'date' => ['datetime' => $d . 'T19:30:00', 'datetimeOffset' => $d . 'T19:30:00-05:00'],
          'venue' => ['id' => 9300011, 'text' => ['name' => 'Sc Hall']], 'city' => ['id' => 9300011, 'text' => ['name' => 'Scville']],
          'stateProvince' => ['id' => 9300011, 'text' => ['abbr' => 'SC']], 'country' => ['alphaCode' => 'US'],
          'defaultCategory' => ['path' => $path], 'performers' => [['id' => 9300011, 'name' => 'Sc Act', 'isPerformance' => true]],
          '_metadata' => ['ticketCount' => 4, 'hasTickets' => true], 'uriComponent' => 'sc'];
    $m = tnBulkMapEvent($e);
    $c = array_map(fn($k) => $m[$k], TN_BULK_COLUMNS);
    $c[] = gmdate('Y-m-d H:i:s', $seen);
    $st = MYSQLI->prepare(tnBulkUpsertSql()); $st->bind_param(str_repeat('s', count($c)), ...$c); $st->execute(); $st->close();
};
$put(9300011, '.1859.1986.1987.', 9);   // a concert
$put(9300012, '.1859.1986.1987.', 4);   // a concert, sooner
$put(9300013, '.1859.1988.1879.', 6);   // a game: not in the concert list
MYSQLI->query('DELETE FROM tn_bulk_state');
foreach (['last_full_ok' => (string) ($now - 3600), 'last_full_count' => '90000'] as $k => $v) { $st = MYSQLI->prepare('INSERT INTO tn_bulk_state (name, value, updated_at) VALUES (?, ?, NOW())'); $st->bind_param('ss', $k, $v); $st->execute(); $st->close(); }

$r = soSpecCityEvents('concert', 9300011);
$ids = array_map(fn($e) => (int) ($e['id'] ?? 0), $r['events'] ?? []);
scCheck('the copy answers the city list: the concerts only, soonest first', $ids, [9300012, 9300011]);
scCheck('the total comes from the copy', $r['total'] ?? null, 2);
scCheck('answered from the copy directly, not as the fallback after a failed live call', empty($GLOBALS['so_bulk_served']), true);
scCheck('a city with nothing in the copy gives an empty list', soSpecCityEvents('concert', 9300099)['events'] ?? null, []);

exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' off 2>&1', $out, $rc);
scCheck('copy off: still the live path (' . trim(implode(' ', $out)) . ')', $rc, 0);

MYSQLI->query('DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9300011 AND 9300013');
MYSQLI->query('DELETE FROM tn_bulk_state');
foreach ($prevState as $k => $v) { $st = MYSQLI->prepare('INSERT INTO tn_bulk_state (name, value, updated_at) VALUES (?, ?, NOW())'); $st->bind_param('ss', $k, $v); $st->execute(); $st->close(); }
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: the event page's city list comes from the catalog copy when it is fresh\n";
