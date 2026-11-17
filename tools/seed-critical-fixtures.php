<?php
/**
 * Local fixtures for tools/critical-css.cjs: a venue, a city, an artist and a category with eight upcoming events each, read from the
 * bulk catalog copy, so those pages render as real listings (hero, date strip, event list) with no ticket API and no call budget.
 * Without them the generator sees the "no data" fallback and the critical CSS lacks the rules a real page needs at first paint
 * (audit of 17 Nov 2026: category pages shifted 0.11 to 0.12, venue pages on desktop 0.14, until the entity and browse files were
 * regenerated from these pages).
 *
 *   php tools/seed-critical-fixtures.php           create the rows, print the URLs
 *   php tools/seed-critical-fixtures.php --clean   remove them (and restore the bulk copy state the seeder replaced)
 *
 * Then run the site with the bulk copy on and the live API unreachable, so the pages answer from the fixtures:
 *   TN_BULK_ENABLED=1 TN_CALLS_PER_SECOND=0.00001 php -S 127.0.0.1:8161 ...
 *   node tools/critical-css.cjs --base=http://127.0.0.1:8161 --groups=entity,browse --urls=<json with the URLs printed here>
 * LOCAL AND BETA DATABASES ONLY: it refuses to run on an indexable (live) host. Rows use ids 9300001 to 9300009 and the slugs
 * zq-arena, zqtown-tx, zq-band and zq-rock; the category slug points at category id 1986.
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=fixture-placeholder'); }
require_once __DIR__ . '/../functions.php';

if (SITE_INDEXABLE) { fwrite(STDERR, "Refusing to seed fixtures on an indexable (live) host.\n"); exit(1); }
$stateFile = __DIR__ . '/../cache/.critical-fixtures-state.json';
$clean = in_array('--clean', $argv ?? [], true);

MYSQLI->query('DELETE FROM tn_bulk_events WHERE event_id BETWEEN 9300001 AND 9300009');
MYSQLI->query("DELETE FROM url_slugs WHERE (type = 'venue' AND ext_id = '9300001') OR (type = 'city' AND ext_id = '9300001') OR (type = 'performer' AND ext_id = '9300001') OR (type = 'category' AND ext_id = '1986' AND slug = 'zq-rock')");
if ($clean) {
    $prev = is_file($stateFile) ? json_decode((string) file_get_contents($stateFile), true) : null;
    MYSQLI->query('DELETE FROM tn_bulk_state');
    foreach (is_array($prev) ? $prev : [] as $k => $v) { $st = MYSQLI->prepare('INSERT INTO tn_bulk_state (name, value, updated_at) VALUES (?, ?, NOW())'); $st->bind_param('ss', $k, $v); $st->execute(); $st->close(); }
    @unlink($stateFile);
    echo "fixtures removed\n";
    exit(0);
}

foreach ([9300001 => 3, 9300002 => 12, 9300003 => 9, 9300004 => 21, 9300005 => 30, 9300006 => 45, 9300007 => 60, 9300008 => 75] as $id => $days) {
    $day = date('Y-m-d', strtotime("+$days days"));
    $e = ['id' => $id, 'text' => ['name' => "Zq Band Live $id"], 'date' => ['datetime' => "{$day}T19:30:00", 'datetimeOffset' => "{$day}T19:30:00-05:00"],
        'venue' => ['id' => 9300001, 'text' => ['name' => 'Zq Arena']], 'city' => ['id' => 9300001, 'text' => ['name' => 'Zqtown']], 'stateProvince' => ['id' => 9300001, 'text' => ['abbr' => 'TX']],
        'country' => ['alphaCode' => 'US'], 'defaultCategory' => ['path' => '.1859.1986.1987.'], 'performers' => [['id' => 9300001, 'name' => 'Zq Band', 'isPerformance' => true]],
        'pricingInfo' => ['lowPrice' => ['value' => 45, 'currencyCode' => 'USD']], '_metadata' => ['ticketCount' => 20, 'hasTickets' => true], 'uriComponent' => 'zq', 'isTest' => false];
    $m = tnBulkMapEvent($e); $c = array_map(fn($k) => $m[$k], TN_BULK_COLUMNS); $c[] = gmdate('Y-m-d H:i:s');
    $st = MYSQLI->prepare(tnBulkUpsertSql()); $st->bind_param(str_repeat('s', count($c)), ...$c); $st->execute(); $st->close();
}
if (!is_file($stateFile)) file_put_contents($stateFile, json_encode(tnBulkState()));
MYSQLI->query('DELETE FROM tn_bulk_state');
foreach (['last_full_ok' => (string) (time() - 3600), 'last_full_count' => '90000'] as $k => $v) { $st = MYSQLI->prepare('INSERT INTO tn_bulk_state (name, value, updated_at) VALUES (?, ?, NOW())'); $st->bind_param('ss', $k, $v); $st->execute(); $st->close(); }
$slugs = [soSlug('category', 'Zq Rock', 1986), soSlug('venue', 'Zq Arena', 9300001, ['place' => 'Zqtown, TX']), soSlug('city', 'Zqtown, TX', 9300001), soSlug('performer', 'Zq Band', 9300001)];
echo "fixtures ready: /category/{$slugs[0]}  /venue/{$slugs[1]}  /city/{$slugs[2]}  /artist/{$slugs[3]}\n";
