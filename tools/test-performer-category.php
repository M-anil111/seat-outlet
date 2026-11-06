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

// Bulk-copy event rows carry a category with only its path (inc/tn-bulk-read.php). Taken as a performer's category it crashed the
// breadcrumb ("Trying to access array offset on null", Sentry, /artist-city?slug=michigan-wolverines), so it is completed from the catalog.
$bare = ['path' => '.1859.1988.2016.2112.'];
$full = ['id' => 2112, 'path' => '.1859.1988.2016.2112.', 'depth' => 3, 'text' => ['name' => 'NCAA FOOTBALL']];
pcCheck2('a path-only category is bare', soCategoryIsBare($bare));
pcCheck2('a named category is not bare', !soCategoryIsBare($full));
pcCheck2('a category without a path is not bare (nothing to look up)', !soCategoryIsBare([]));
pcCheck2('the leaf id is the last id in the path', soCategoryLeafId('.1859.1988.2016.2112.') === 2112);
pcCheck2('a path with no id gives 0', soCategoryLeafId('') === 0 && soCategoryLeafId('...') === 0);
pcCheck2('the lookup entry with the same path completes the category', soCategoryFromLookup($bare, [['path' => '.1859.1988.2016.', 'text' => ['name' => 'FOOTBALL']], $full]) === $full);
pcCheck2('a failed lookup leaves the category as it was', soCategoryFromLookup($bare, []) === $bare);
pcCheck2('an entry without a name does not replace it', soCategoryFromLookup($bare, [['path' => $bare['path']]]) === $bare);
pcCheck2('renderArtistLocationPage completes the category it takes from events', strpos($body, 'soCompleteCategory(soPerformerCategoryFromEvents($performer, $allPerformerEvents))') !== false);
$perfPage = (string) file_get_contents(dirname(__DIR__) . '/performer.php');
pcCheck2('the performer page completes the category it takes from events', strpos($perfPage, 'soCompleteCategory(soPerformerCategoryFromEvents($performer,') !== false);

// buildCategoryBreadcrumb() itself, run from functions.php's source (that file needs the database, so only this function is loaded)
// with warnings turned into exceptions, as Sentry reports them.
if (!function_exists('sanitize_title')) { function sanitize_title($t) { return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) $t), '-')); } }
if (!function_exists('soCategoryHref')) { function soCategoryHref($id, $name) { return '/category/' . sanitize_title($name) . '-' . (int) $id; } }
if (!function_exists('soGenreById')) { function soGenreById($id) { return null; } }
if (!function_exists('stripCategoryRootPath')) { function stripCategoryRootPath($p) { return str_replace('.', '', str_replace(['.1859.1986.', '.1859.1988.', '.1859.1989.'], '', (string) $p)); } }
if (!defined('HOME_URL')) define('HOME_URL', 'https://seatoutlet.test');
$tokens = token_get_all($fn);
$src = '';
for ($i = 0, $n = count($tokens); $i < $n && $src === ''; $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
    $j = $i + 1;
    while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
    if (!is_array($tokens[$j]) || $tokens[$j][1] !== 'buildCategoryBreadcrumb') continue;
    for ($k = $i, $depth = 0, $open = false; $k < $n; $k++) {
        $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
        $src .= $text;
        if ($text === '{' || (is_array($tokens[$k]) && in_array($tokens[$k][0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) { $depth++; $open = true; }
        if ($text === '}' && --$depth === 0 && $open) break;
    }
}
pcCheck2('buildCategoryBreadcrumb found in functions.php', $src !== '');
if ($src !== '' && !function_exists('buildCategoryBreadcrumb')) eval($src);
set_error_handler(function ($no, $msg, $file, $line) { throw new ErrorException($msg, 0, $no, $file, $line); });
$crumbs = function ($cat) { try { return buildCategoryBreadcrumb($cat); } catch (Throwable $e) { return 'warning: ' . $e->getMessage(); } };
$labels = function ($b) { return is_array($b) ? implode(' > ', array_column($b, 'label')) : (string) $b; };
$fullWithAncestors = $full + ['ancestors' => [['id' => 1988, 'depth' => 1, 'path' => '.1859.1988.', 'text' => ['name' => 'SPORTS']],
                                               ['id' => 2016, 'depth' => 2, 'path' => '.1859.1988.2016.', 'text' => ['name' => 'FOOTBALL']]]];
pcCheck2('a bare category gives a breadcrumb, not a warning', $labels($crumbs($bare)) === 'Home', $labels($crumbs($bare)));
pcCheck2('a missing category gives a breadcrumb, not a warning', $labels($crumbs(null)) === 'Home', $labels($crumbs(null)));
pcCheck2('a full category keeps its steps', $labels($crumbs($fullWithAncestors)) === 'Home > Sports > Football', $labels($crumbs($fullWithAncestors)));
pcCheck2('a named category without ancestors ends on its own name', $labels($crumbs($full)) === 'Home > Ncaa Football', $labels($crumbs($full)));
pcCheck2('an ancestor without a name is skipped', $labels($crumbs($full + ['ancestors' => [['depth' => 1, 'path' => '.1859.1988.']]])) === 'Home > Ncaa Football');
restore_error_handler();

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: performer category fallback, bare-category completion, breadcrumb and error titles\n";
