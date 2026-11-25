<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test: a slug the table does not hold is looked up once per request, not once per link. soSlugWarm() remembers the ids it did not get back,
 * and soSlug() remembers a miss of its own, so a page that builds the same unstored link several times (card, schema, breadcrumb) does not repeat
 * the SELECT. Artist page with 20 events and no stored event slugs: 21 single lookups before. A miss must still be creatable (the first soSlug with a
 * real name stores it) and a stored slug must still resolve. Ids 9400001 and up; removed at the end. Run: php tools/test-slug-miss-memory.php
 */
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/entity-pages.php';
require_once __DIR__ . '/../inc/slugs.php';

$fail = 0;
function smCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$questions = function (): int { return (int) MYSQLI->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch_row()[1]; };
MYSQLI->query("DELETE FROM url_slugs WHERE ext_id BETWEEN '9400001' AND '9400099' OR slug LIKE 'zq-miss%'");

// 1. Ten ids that are not stored, asked for five times each with no name (so no new slug can be made: the old-form link is used).
$pairs = []; for ($i = 1; $i <= 10; $i++) $pairs[] = ['performer', 9400000 + $i];
$before = $questions();
soSlugWarm($pairs);
for ($rep = 0; $rep < 5; $rep++) {
    for ($i = 1; $i <= 10; $i++) {
        $s = soSlug('performer', '', 9400000 + $i);
        if ($rep === 0 && $i === 1) smCheck('an unstored entity with no name keeps the old-form link', $s, '-9400001');
    }
}
$used = $questions() - $before - 1;   // minus the SHOW itself
smCheck('50 links for 10 unstored ids cost one warm query, not 50', $used <= 2, true);

// 2. Without a warm, the same id asked twice costs one SELECT.
$before = $questions();
soSlug('performer', '', 9400021); soSlug('performer', '', 9400021); soSlug('performer', '', 9400021);
smCheck('an unwarmed unstored id is asked about once', $questions() - $before - 1, 1);

// 3. A miss can still be created: the first call with a real name stores the slug, later calls and resolve find it.
soSlugWarm([['performer', 9400030]]);
smCheck('a warmed miss with a real name gets a new slug', soSlug('performer', 'Zq Miss Act', 9400030), 'zq-miss-act');
smCheck('the new slug is remembered', soSlug('performer', 'Zq Miss Act (renamed)', 9400030), 'zq-miss-act');
smCheck('and resolves back to the id', soSlugResolve('performer', 'zq-miss-act')[0] ?? null, 9400030);

// 4. A stored slug is returned by warm, with no extra lookups afterwards.
MYSQLI->query("INSERT INTO url_slugs (type, ext_id, slug, name, created_at) VALUES ('performer', '9400040', 'zq-miss-stored', 'Zq Miss Stored', NOW())");
soSlugWarm([['performer', 9400040]]);
$before = $questions();
smCheck('a stored slug is returned', soSlug('performer', 'whatever', 9400040), 'zq-miss-stored');
smCheck('a warmed stored slug costs no query', $questions() - $before - 1, 0);

MYSQLI->query("DELETE FROM url_slugs WHERE ext_id BETWEEN '9400001' AND '9400099' OR slug LIKE 'zq-miss%'");
echo $fail ? "$fail failure(s)\n" : "slug miss memory: all passed\n"; exit($fail ? 1 : 0);
