<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: the events on a performer page are remembered (event_redirects) once an hour per performer, not on every view. The guard stored
 * the number 1, and cache_get() only returns arrays, so it never held: every artist page view wrote up to 20 rows (row locks on a shared database)
 * and looked up 20 slugs one by one. Also: the slugs of the events are loaded in one query before the rows are built.
 * Ids 9500001 and up; removed at the end. Run: php tools/test-event-remember-once.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/event-redirects.php';

$fail = 0;
function orCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$rows = function (): int { return (int) MYSQLI->query('SELECT COUNT(*) FROM event_redirects WHERE event_id BETWEEN 9500001 AND 9500099')->fetch_row()[0]; };
$status = function (string $name): int { return (int) MYSQLI->query("SHOW SESSION STATUS LIKE '$name'")->fetch_row()[1]; };
$clean = function () {
    MYSQLI->query("DELETE FROM event_redirects WHERE event_id BETWEEN 9500001 AND 9500099");
    MYSQLI->query("DELETE FROM url_slugs WHERE type = 'event' AND CAST(ext_id AS UNSIGNED) BETWEEN 9500001 AND 9500099");   // ext_id is a string: a text range would also match 95000010
    @unlink(cache_file_path('evredir_p9500001_n20')); @unlink(cache_file_path('evredir_p9500001_n8'));
};
$clean();

$events = [];
for ($i = 1; $i <= 12; $i++) {
    $day = date('Y-m-d', strtotime('+' . (5 + $i) . ' days'));
    $events[] = ['id' => 9500000 + $i, 'text' => ['name' => 'Zq Remember Act'], 'date' => ['date' => $day, 'datetime' => $day . 'T19:30:00'],
        'venue' => ['id' => 9500001, 'text' => ['name' => 'Zq Remember Hall']], 'city' => ['id' => 9500001, 'text' => ['name' => 'Zqrememberville']],
        'stateProvince' => ['abbr' => 'TX'], 'performers' => [['id' => 9500001, 'name' => 'Zq Remember Act']], 'defaultCategory' => ['path' => '.1859.1986.1987.']];
}
[$ep, $params] = performerPageEventsSpec(9500001, 20);
cache_set(tnCacheKey($ep, $params, 'GET'), ['results' => $events, 'totalCount' => 12, 'count' => 12]);

// The events already have slugs (as on a live site, where each event got its slug the first time it was listed): creating a new slug costs one insert per
// event and happens once. The guard and the rows are reset, so the next view is the first one that remembers these events.
foreach ($events as $e) soEventSlug($e);
MYSQLI->query("DELETE FROM event_redirects WHERE event_id BETWEEN 9500001 AND 9500099");
@unlink(cache_file_path('evredir_p9500001_n20'));
$mem = &soSlugMemory(); $mem = []; $miss = &soSlugMissMemory(); $miss = [];   // a new request: nothing remembered

// First view: the rows are written (one statement) and the slugs are loaded in a handful of queries, not 12 single lookups.
$q0 = $status('Questions'); $i0 = $status('Com_insert');
getPerformerPageEvents(9500001, 20);
$firstQuestions = $status('Questions') - $q0 - 1;
orCheck('first view writes the event rows', MYSQLI->query('SELECT COUNT(*) FROM event_redirects WHERE event_id BETWEEN 9500001 AND 9500099')->fetch_row()[0] >= 12, true);
orCheck('first view does not look the 12 slugs up one by one', $firstQuestions < 12, true);
orCheck('the hourly guard is stored as something cache_get() can read, marked done', (cache_get('evredir_p9500001_n20', 3600)['ok'] ?? null), true);

// Second view: nothing is remembered again (no event_redirects write, no insert at all, no query of its own).
$i1 = $status('Com_insert'); $u1 = $status('Com_update'); $q1 = $status('Questions');
getPerformerPageEvents(9500001, 20);
$q2 = $status('Questions');   // read first: the SHOW statements below would count too
orCheck('second view inserts nothing', $status('Com_insert') - $i1, 0);
orCheck('second view updates nothing', $status('Com_update') - $u1, 0);
orCheck('second view asks the database nothing', $q2 - $q1 - 1, 0);

// Event pages ask for 8 rows and the artist page for 20: the 8-row call must not mark the 20-row one done.
$clean();
[$ep8, $params8] = performerPageEventsSpec(9500001, 8);
cache_set(tnCacheKey($ep8, $params8, 'GET'), ['results' => array_slice($events, 0, 8), 'totalCount' => 12, 'count' => 8]);
cache_set(tnCacheKey($ep, $params, 'GET'), ['results' => $events, 'totalCount' => 12, 'count' => 12]);
getPerformerPageEvents(9500001, 8);
orCheck('the 8-row call remembers its 8 events', $rows() >= 8 && $rows() < 12, true);
getPerformerPageEvents(9500001, 20);
orCheck('and does not stop the 20-row call from remembering all 12', $rows(), 12);

// Marker states: "in progress or failed" holds for 5 minutes, then the write is tried again; "done" holds for an hour.
$key = 'evredir_p9500001_n20';
MYSQLI->query("DELETE FROM event_redirects WHERE event_id BETWEEN 9500001 AND 9500099");
cache_set($key, ['at' => time(), 'ok' => false]);
getPerformerPageEvents(9500001, 20);
orCheck('a write in progress or failed less than 5 minutes ago is not repeated', $rows(), 0);
cache_set($key, ['at' => time() - 600, 'ok' => false]);
getPerformerPageEvents(9500001, 20);
orCheck('a failed write is tried again after 5 minutes', $rows() >= 12, true);
orCheck('and the marker then says done', cache_get($key, 3600)['ok'] ?? null, true);

$clean();
echo $fail ? "$fail failure(s)\n" : "event remember once: all passed\n"; exit($fail ? 1 : 0);
