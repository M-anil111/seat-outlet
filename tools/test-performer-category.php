<?php
/**
 * CI: a performer page with events is never a 404 for a missing category, and an error page for a performer has a title of its own
 * (SE Ranking audit: 4XX on performer pages with events, duplicate titles on the 503 pages). Run: php tools/test-performer-category.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/dead-performers.php';
require_once __DIR__ . '/../inc/title.php';

$fail = 0;
function pcCheck2(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name $detail\n"; $fail++; } }

$cat = ['text' => ['name' => 'Sports'], 'path' => '.1859.1988.'];
$evCat = ['text' => ['name' => 'NFL'], 'path' => '.1859.1988.1879.'];

pcCheck2('the performer record\'s own category is kept', soPerformerCategoryFromEvents(['defaultCategory' => $cat], [['defaultCategory' => $evCat]]) === $cat);
pcCheck2('no category: the first event with one is used', soPerformerCategoryFromEvents([], [['name' => 'x'], ['defaultCategory' => $evCat]]) === $evCat);
pcCheck2('no category and no events: empty, so the page stays a 404', soPerformerCategoryFromEvents([], []) === []);
pcCheck2('an event with an empty category path is skipped', soPerformerCategoryFromEvents([], [['defaultCategory' => ['path' => '']]]) === []);
pcCheck2('a non-array event is skipped', soPerformerCategoryFromEvents([], ['junk', null]) === []);

// The error-page title: one per performer name, the brand kept, within 70 characters.
$a = soTitleUpTo(70, 'Persian Halloween Tickets', 'Performer Temporarily Unavailable');
$b = soTitleUpTo(70, 'Slap Dragon Tickets', 'Performer Temporarily Unavailable');
pcCheck2('two performers get two titles', $a !== $b, "$a / $b");
pcCheck2('the title keeps the brand', substr($a, -strlen(' at Seat Outlet')) === ' at Seat Outlet', $a);
pcCheck2('the title fits 70 characters', mb_strlen($a) <= 70, $a);
$long = soTitleUpTo(70, ucwords(str_replace('-', ' ', 'the-very-long-named-touring-production-of-a-stage-show-with-many-words-in-it')) . ' Tickets', 'Performer Temporarily Unavailable');
pcCheck2('a long name still fits 70', mb_strlen($long) <= 70, $long);

// The label a slug gives, and the 503 title built from it (one per entity, never "Tickets Tickets").
pcCheck2('a clean performer slug becomes its name', soLabelFromSlug('rnb-soul-concert-tickets') === 'Rnb Soul Concert Tickets', soLabelFromSlug('rnb-soul-concert-tickets'));
pcCheck2('an event slug loses its date and collision number', soLabelFromSlug('comedy-cellar-las-vegas-nv-2026-10-27-2') === 'Comedy Cellar Las Vegas Nv', soLabelFromSlug('comedy-cellar-las-vegas-nv-2026-10-27-2'));
pcCheck2('an empty slug gives an empty label', soLabelFromSlug('') === '');
pcCheck2('no label: the generic title', soUnavailableTitle('Performer') === 'Performer Temporarily Unavailable at Seat Outlet', soUnavailableTitle('Performer'));
pcCheck2('a label: its own title', soUnavailableTitle('Performer', 'Persian Halloween') === 'Persian Halloween Tickets at Seat Outlet', soUnavailableTitle('Performer', 'Persian Halloween'));
pcCheck2('a label that already ends in Tickets is not doubled', soUnavailableTitle('Location', 'NBA Tickets') === 'NBA Tickets at Seat Outlet', soUnavailableTitle('Location', 'NBA Tickets'));
pcCheck2('a long label still fits 65 characters', mb_strlen(soUnavailableTitle('Event', str_repeat('Very Long Name ', 6))) <= SO_TITLE_EVENT_MAX, soUnavailableTitle('Event', str_repeat('Very Long Name ', 6)));

// Wiring: the artist-city, -venue and -state pages (renderArtistLocationPage) apply the same fallback before their 404.
$fn = (string) file_get_contents(dirname(__DIR__) . '/functions.php');
$at = strpos($fn, 'function renderArtistLocationPage');
$body = $at !== false ? substr($fn, $at, 6000) : '';
pcCheck2('renderArtistLocationPage takes the category from the events before deciding on a 404', strpos($body, 'soPerformerCategoryFromEvents($performer, $allPerformerEvents)') !== false && strpos($body, 'soPerformerCategoryFromEvents') < strpos($body, "renderNotFoundPage('Performer'"));
pcCheck2('its 404 page names the performer from the URL', strpos($body, "renderNotFoundPage('Performer', \$performer, soLabelFromSlug(\$requestedSlug))") !== false);

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: performer category fallback and error titles\n";
