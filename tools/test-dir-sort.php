<?php
// A to Z directory order (inc/directories.php): a leading "The " is ignored for sorting and for the letter a name sits under, display and slug
// keep the full name, every name is on exactly one page, and a failed call never leaves a part of a letter. No database. Run: php tools/test-dir-sort.php
if (!function_exists('tnEscapeFilterValue')) { function tnEscapeFilterValue($v = '') { return str_replace("'", "''", $v); } }
require_once __DIR__ . '/../inc/entity-pages.php';   // soAsciiFold()
require_once __DIR__ . '/../inc/directories.php';
$fail = 0;
function dsCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }

dsCheck('"The 1975" sorts as 1975 and sits under 0-9', soDirSortKey('The 1975') === '1975' && soDirLetterOf('The 1975') === '0-9');
dsCheck('"The Beatles" sits under B', soDirLetterOf('The Beatles') === 'B' && soDirSortKey('The Beatles') === 'beatles');
dsCheck('"Taylor Swift" stays under T', soDirLetterOf('Taylor Swift') === 'T');
dsCheck('"The Temptations" is under T, after "Taylor Swift"', soDirLetterOf('The Temptations') === 'T' && strcmp(soDirSortKey('Taylor Swift'), soDirSortKey('The Temptations')) < 0);
dsCheck('"Theory of a Deadman" is not stripped (no space after The)', soDirLetterOf('Theory of a Deadman') === 'T' && soDirSortKey('Theory of a Deadman') === 'theory of a deadman');
dsCheck('"THE WEEKND" in capitals is under W', soDirLetterOf('THE WEEKND') === 'W');
dsCheck('"The The" keeps the second word', soDirSortKey('The The') === 'the');
dsCheck('a name that is only "The" is left as it is', soDirSortKey('The') === 'the' && soDirLetterOf('The') === 'T');
dsCheck('"A Day to Remember" is only "The"-stripped, so it stays under A', soDirLetterOf('A Day to Remember') === 'A');
dsCheck('accents do not move a name out of its place (key starts with the plain letters)', strpos(soDirSortKey('Beyoncé'), 'beyonc') === 0 && soDirLetterOf('Édith Piaf') !== 'T');
dsCheck('a name starting with a symbol belongs to no letter', soDirLetterOf('!!!') === '' && soDirLetterOf("'N Sync") === '');
dsCheck('the filter for B fetches "B..." and "The B..."', soDirLetterFilter('B') === "(startswith(text/name,'B') or startswith(text/name,'The B'))");
dsCheck('the filter for 0-9 covers every digit, with and without "The "', substr_count(soDirLetterFilter('0-9'), 'startswith') === 20 && strpos(soDirLetterFilter('0-9'), "'The 1'") !== false);

// a whole letter from a fake API, paged 200 at a time
$names = ['Taylor Swift', 'The Temptations', 'Tame Impala', 'Tool', 'The Beatles', 'The 1975', 'The Tragically Hip', 'Theory of a Deadman', 'Thomas Rhett', 'Third Eye Blind', 'T_junk', '000000_TL'];
for ($i = 1; $i <= 260; $i++) $names[] = sprintf('Tz Act %03d', $i);
$all = []; foreach ($names as $i => $n) $all[] = ['id' => $i + 1, 'text' => ['name' => $n], 'uriComponent' => 'u' . $i, 'defaultCategory' => ['path' => '.1859.']];
$calls = 0;
$fake = function (array $p) use ($all, &$calls) {
    $calls++;
    $pp = $p['perPage']; $pg = $p['page'];
    usort($all, fn($a, $b) => strcmp($a['text']['name'], $b['text']['name']));   // the API sorts on the raw name
    return ['results' => array_slice($all, ($pg - 1) * $pp, $pp), 'totalCount' => count($all)];
};
$rows = soDirLetterRows(['path' => '/x', 'category' => null], 'T', $fake);
$got = array_column($rows, 'name');
dsCheck('every page of the fake API was read (' . $calls . ' calls for ' . count($all) . ' rows)', $calls === (int) ceil(count($all) / SO_DIR_FETCH_PER_PAGE));
dsCheck('only names that belong under T are kept (no The Beatles, no The 1975, no junk)', !in_array('The Beatles', $got, true) && !in_array('The 1975', $got, true) && !in_array('T_junk', $got, true) && !in_array('000000_TL', $got, true));
$pos = array_flip($got);
dsCheck('The Temptations sorts as "Temptations": after Tame Impala and Taylor Swift, before The Tragically Hip', $pos['Tame Impala'] < $pos['Taylor Swift'] && $pos['Taylor Swift'] < $pos['The Temptations'] && $pos['The Temptations'] < $pos['The Tragically Hip']);
dsCheck('"The Tragically Hip" sorts as "Tragically Hip": after Thomas Rhett and Tool, before Tz Act 001', $pos['The Tragically Hip'] > $pos['Thomas Rhett'] && $pos['The Tragically Hip'] > $pos['Tool'] && $pos['The Tragically Hip'] < $pos['Tz Act 001']);
$n0 = soDirLetterRows(['path' => '/x', 'category' => null], '0-9', $fake);
dsCheck('0-9 holds "The 1975"', array_column($n0, 'name') === ['The 1975']);
$b = soDirLetterRows(['path' => '/x', 'category' => null], 'B', $fake);
dsCheck('B holds "The Beatles"', array_column($b, 'name') === ['The Beatles']);
dsCheck('display names are untouched', in_array('The Temptations', $got, true));

// pagination: consistent, no name twice, none skipped
$seen = []; $pages = (int) ceil(count($rows) / 48);
for ($p = 1; $p <= $pages; $p++) { [$slice, $total] = soDirPageOf($rows, $p, 48); foreach ($slice as $r) $seen[] = $r['name']; if ($p < $pages) dsCheck("page $p has 48", count($slice) === 48); dsCheck("page $p total is the whole letter", $total === count($rows)); }
dsCheck('the pages together are the letter, in order, each name once', $seen === $got && count($seen) === count(array_unique($seen)));
dsCheck('a page past the end is empty', soDirPageOf($rows, $pages + 1, 48)[0] === []);
dsCheck('ties break the same way every time (key, then full name, then id)', array_column(soDirSortRows([['name' => 'B', 'id' => 2], ['name' => 'The B', 'id' => 1], ['name' => 'B', 'id' => 1]]), 'id') === [1, 2, 1]);

// a failed call: nothing partial
$boom = function (array $p) { if ($p['page'] === 2) throw new SoTnUnavailable('x'); return ['results' => [['id' => 1, 'text' => ['name' => 'Tool'], 'uriComponent' => 'u']], 'totalCount' => 999]; };
if (!class_exists('SoTnUnavailable')) { class SoTnUnavailable extends RuntimeException {} }
$threw = false; try { soDirLetterRows(['path' => '/x', 'category' => null], 'T', $boom); } catch (Throwable $e) { $threw = true; }
dsCheck('a failed call throws (no part of a letter is returned or stored)', $threw);
$src = (string) file_get_contents(__DIR__ . '/../all-artists-and-teams.php');
dsCheck('the page cuts letter pages from soDirLetterRows and keeps the unfiltered view as before', strpos($src, 'soDirPageOf(soDirLetterRows(') !== false && strpos($src, 'getTnPerformers($params)') !== false);
exit($fail ? 1 : 0);
