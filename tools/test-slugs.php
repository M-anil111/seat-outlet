<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test for the URL slug registry (inc/slugs.php). Needs the migrated database (run after db/migrate.php).
 * Rolls back nothing: every row it makes uses ids in the 9,000,000 range and is deleted at the end.
 * Run: php tools/test-slugs.php
 */
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/entity-pages.php';   // soAsciiFold(), which the slug words use
require_once __DIR__ . '/../inc/slugs.php';

$fail = 0;
function check(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}

MYSQLI->query("DELETE FROM url_slugs WHERE ext_id LIKE '9000%' OR slug LIKE 'zq-%' OR slug LIKE 'zqville%'");

// A new slug is plain words, stable on the second call, and resolves back to the id.
check('performer slug', soSlug('performer', 'Zq Test Act', 9000001), 'zq-test-act');
check('performer slug again', soSlug('performer', 'Zq Test Act (renamed)', 9000001), 'zq-test-act');
check('resolve', soSlugResolve('performer', 'zq-test-act')[0] ?? null, 9000001);

// Another entity with the same words gets a suffix, never the first one's slug.
check('collision', soSlug('performer', 'Zq Test Act', 9000002), 'zq-test-act-2');

// A venue name shared by two places is told apart by the place.
check('venue one', soSlug('venue', 'Zq Civic Center', 9000010, ['place' => 'Zqville, TX']), 'zq-civic-center');
check('venue two', soSlug('venue', 'Zq Civic Center', 9000011, ['place' => 'Zqboise, ID']), 'zq-civic-center-zqboise-id');

// City, state, category and country.
check('city', soSlug('city', 'Zqville, TX', 9000020), 'zqville-tx');
check('country', soSlug('country', 'Zq Land', 'U9'), 'zq-land');

// An event: name, place and date; without a date there is no new slug (the old form is used for that one link).
$ev = soSlug('event', 'Zq Test Act', 9000030, ['place' => 'Zqville, TX', 'date' => '2026-10-12T19:30:00']);
check('event slug', $ev, 'zq-test-act-zqville-tx-2026-10-12');
check('event resolve', soSlugResolve('event', $ev)[0] ?? null, 9000030);
check('event without a date', soSlug('event', 'Zq Show', 9000031, ['place' => 'Zqville, TX']), 'zq-show-9000031');
check('event date tbd', soSlug('event', 'Zq Show', 9000032, ['place' => 'Zqville, TX', 'date' => 'tbd']), 'zq-show-zqville-tx-date-tbd');

// The old name-and-id form still resolves (and is flagged as old, so the page answers 301).
check('old form', soSlugResolve('performer', 'blink-9000044'), [9000044, true]);
check('unknown', soSlugResolve('performer', 'nobody-here'), [null, false]);

// No stored slug may end in an id-like number unless it is a collision counter or part of a date.
$bad = MYSQLI->query("SELECT slug FROM url_slugs WHERE ext_id LIKE '9000%' AND type <> 'event' AND slug REGEXP '-[0-9]{4,}$'")->fetch_all();
check('no id in slugs', count($bad), 0);

MYSQLI->query("DELETE FROM url_slugs WHERE ext_id LIKE '9000%' OR ext_id = 'U9'");
echo $fail ? "$fail failure(s)\n" : "slug registry ok\n";
exit($fail ? 1 : 0);
