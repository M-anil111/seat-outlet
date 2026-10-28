<?php
/** CI: event titles stay unique per showing (SE Ranking audit, "URLs with duplicate page titles"). Run: php tools/test-event-titles.php */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/title.php';
require_once __DIR__ . '/../inc/page-spec.php';
if (!function_exists('seoClampDescription')) { function seoClampDescription($d, $max = 155) { return mb_strlen($d) <= $max ? $d : mb_substr($d, 0, $max); } }   // functions.php needs the database

$fail = 0;
function etCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name $detail\n"; $fail++; } }

$long = 'Fuck This Week A Comedic Exploration of Your Week';
$dates = ['Oct 26', 'Nov 2', 'Nov 9', 'Nov 16'];
$titles = [];
foreach ($dates as $d) {
    $t = soEventUniqueTitle('Buy ' . $long . ' Tickets', $long, $d, '', false);   // the spec fell back to an undated title
    etCheck("long name keeps $d", mb_strpos($t, $d) !== false, $t);
    etCheck("long name fits 65 ($d)", mb_strlen($t) <= SO_TITLE_EVENT_MAX, $t);
    $titles[] = $t;
}
etCheck('four dates, four titles', count(array_unique($titles)) === 4, implode(' / ', $titles));

$keep = soEventUniqueTitle('Buy Daley Tickets for Laugh Factory Show in Los Angeles, CA on Oct 27 at Seat Outlet', 'Daley', 'Oct 27', '8:00 PM', false);
etCheck('a title that already names the day is kept', mb_strpos($keep, 'Laugh Factory') !== false, $keep);

$a = soEventUniqueTitle('Buy Daley Tickets on Oct 27 at Seat Outlet', 'Daley', 'Oct 27', '8:00 PM', true);
$b = soEventUniqueTitle('Buy Daley Tickets on Oct 27 at Seat Outlet', 'Daley', 'Oct 27', '10:30 PM', true);
etCheck('same-day showings differ by time', $a !== $b && mb_strpos($a, '8:00 PM') !== false && mb_strpos($b, '10:30 PM') !== false, "$a / $b");

etCheck('no day, nothing changes', soEventUniqueTitle('X Tickets', 'X', '', '', false) === 'X Tickets');
foreach ([$a, $b, $keep] as $t) etCheck('no forbidden separator', !preg_match('/[|\x{2013}\x{2014}]|\s-\s/u', $t), $t);

// Two showings of one event (same name, venue, city) differ in the H1 and in the description, for every kind of event.
foreach (['concert', 'sports', 'theater', 'festival'] as $kind) {
    $mk = fn(string $day) => soSpecEventText($kind, ['label' => 'Town Biz Comedy Night', 'perf' => 'Town Biz Comedy Night', 'venue' => 'The Layover', 'city' => 'Oakland', 'state' => 'CA', 'day' => $day, 'cat' => 'Comedy', 'country' => 'the United States', 'hasTickets' => true]);
    $a = $mk('Nov 1'); $b = $mk('Nov 5');
    etCheck("$kind: H1 differs by day", $a['h1'] !== $b['h1'], $a['h1']);
    $da = soSpecPick(155, ...$a['descs']); $db = soSpecPick(155, ...$b['descs']);
    etCheck("$kind: description differs by day", $da !== $db, $da);
    etCheck("$kind: description fits 155", mb_strlen($da) <= 155, (string) mb_strlen($da));
    etCheck("$kind: no day, same text as before", strpos($mk('')['h1'], ' on ') === false);
}

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: event title uniqueness\n";
