<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * The event page's fallback to the bulk copy (tnBulkEventDetail in inc/tn-bulk-read.php): served only for the event page, only while the
 * full refresh is fresh and the switch is on, and never for test, taken-down or incomplete events. Checkout keeps its live prices.
 * Needs the migrated database. Uses event ids 9300001 and up and deletes them at the end.
 * Run: php tools/test-tn-bulk-event.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';

$fail = 0;
function beCheck(string $what, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else echo "ok   $what\n"; }

$now = time();
$fresh = ['last_full_ok' => $now - 7200, 'last_full_count' => 90000];
$stale = ['last_full_ok' => $now - 40 * 3600, 'last_full_count' => 90000];
$seen = $now - 7200;

MYSQLI->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9300001 AND 9300099");
$put = function (int $id, array $o = []) use ($seen) {
    $e = ['id' => $id, 'text' => ['name' => $o['name'] ?? "Be Show $id"],
          'date' => ['datetime' => date('Y-m-d', strtotime('+10 days')) . 'T19:30:00', 'datetimeOffset' => date('Y-m-d', strtotime('+10 days')) . 'T19:30:00-04:00'],
          'venue' => ['id' => 9300001, 'text' => ['name' => $o['venue'] ?? 'Be Hall']], 'city' => ['id' => 9300001, 'text' => ['name' => 'Beville']],
          'stateProvince' => ['id' => 9300001, 'text' => ['abbr' => 'BE']], 'country' => ['alphaCode' => 'US'],
          'defaultCategory' => ['path' => '.1859.1986.'], 'performers' => [['id' => 9300001, 'name' => 'Be Act', 'isPerformance' => true]],
          '_metadata' => ['ticketCount' => 10, 'hasTickets' => true], 'uriComponent' => 'be', 'isTest' => $o['test'] ?? false];
    if (isset($o['takedown'])) $e['takedownAt'] = $o['takedown'];
    $m = tnBulkMapEvent($e);
    $c = array_map(fn($k) => $m[$k], TN_BULK_COLUMNS);
    $c[] = gmdate('Y-m-d H:i:s', $seen);
    $st = MYSQLI->prepare(tnBulkUpsertSql()); $st->bind_param(str_repeat('s', count($c)), ...$c); $st->execute(); $st->close();
};
$put(9300001);
$put(9300002, ['test' => true]);
$put(9300003, ['takedown' => gmdate('Y-m-d\TH:i:s\Z', $now - 3600)]);
$put(9300004, ['venue' => '']);

putenv('TN_BULK_ENABLED=1'); putenv('TN_BULK_READ');
$db = MYSQLI;

// ---- served from the copy when everything is in order
$r = tnBulkEventDetail($db, 9300001, $fresh, $now);
beCheck('a complete, fresh event is served from the copy', $r !== null && $r['event']['id'] === 9300001, true);
beCheck('the event carries its name and venue for the page', $r['event']['text']['name'] === 'Be Show 9300001' && $r['event']['venue']['text']['name'] === 'Be Hall', true);
beCheck('the event carries its performers', ($r['event']['performers'][0]['name'] ?? '') === 'Be Act', true);
beCheck('the answer says when the copy was seen (unix time)', $r['seenAt'] === $seen, true);
beCheck('the shape passes the listing-copy completeness check', tnListingCopyComplete($r['event']), true);

// ---- never served when the copy cannot be trusted
beCheck('a stale full refresh is not served', tnBulkEventDetail($db, 9300001, $stale, $now), null);
beCheck('a copy that saw no events is not served', tnBulkEventDetail($db, 9300001, ['last_full_ok' => $now - 60, 'last_full_count' => 0], $now), null);
putenv('TN_BULK_ENABLED'); beCheck('the switch off means no copy', tnBulkEventDetail($db, 9300001, $fresh, $now), null); putenv('TN_BULK_ENABLED=1');
putenv('TN_BULK_READ=0'); beCheck('TN_BULK_READ=0 means no copy', tnBulkEventDetail($db, 9300001, $fresh, $now), null); putenv('TN_BULK_READ');
beCheck('a test event is not served', tnBulkEventDetail($db, 9300002, $fresh, $now), null);
beCheck('a taken-down event is not served', tnBulkEventDetail($db, 9300003, $fresh, $now), null);
beCheck('an event without a venue name is not served', tnBulkEventDetail($db, 9300004, $fresh, $now), null);
beCheck('an unknown event is not served', tnBulkEventDetail($db, 9399999, $fresh, $now), null);

// ---- wiring: only the event page asks for the copy; checkout and the other callers keep live prices
$fn = (string) file_get_contents(__DIR__ . '/../functions.php');
beCheck('getTnEventById takes an allowBulk flag that defaults to off', strpos($fn, 'function getTnEventById($eventId, bool $allowBulk = false)') !== false, true);
beCheck('the copy is consulted only when allowBulk is set', strpos($fn, 'if ($allowBulk) {') !== false || strpos($fn, 'if ($allowBulk && ') !== false, true);
beCheck('the event page asks for the copy', strpos((string) file_get_contents(__DIR__ . '/../event.php'), 'getTnEventById($id, true)') !== false, true);
foreach (['checkout.php', 'order-confirmation.php', 'inc/seo-event.php', 'cron/send-price-alerts.php', 'cron/warm-event-details.php', 'header.php'] as $f) {
    beCheck("$f does not ask for the copy", preg_match('/getTnEventById\([^)]*,\s*true\s*\)/', (string) file_get_contents(__DIR__ . '/../' . $f)) === 0, true);
}
beCheck('the event page drops live prices when the copy answers', strpos((string) file_get_contents(__DIR__ . '/../event.php'), "unset(\$event['pricingInfo'])") !== false, true);

$db->query("DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9300001 AND 9300099");
echo $fail ? "\ntn bulk event: $fail failed\n" : "\ntn bulk event: all passed\n";
exit($fail ? 1 : 0);
