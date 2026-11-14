<?php
/**
 * CI: two showings of one event on one day get different titles (SE Ranking audit, "URLs with duplicate page titles").
 * The feed's date.date is a plain day; the start time is in date.datetime (local wall clock) and date.datetimeOffset.
 * Run: php tools/test-event-showing-time.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/title.php';
require_once __DIR__ . '/../inc/page-spec.php';
if (!function_exists('seoClampDescription')) { function seoClampDescription($d, $max = 155) { return mb_strlen($d) <= $max ? $d : mb_substr($d, 0, $max); } }   // functions.php needs the database

$fail = 0;
function escCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name $detail\n"; $fail++; } }

// 1. The clock comes from the wall-clock time in the feed, whatever the server's time zone.
escCheck('datetime 19:30 is 7:30 PM', soEventShowingClock(['date' => ['date' => '2026-12-26', 'datetime' => '2026-12-26T19:30:00', 'datetimeOffset' => '2026-12-26T19:30:00-05:00']]) === '7:30 PM');
escCheck('datetime 15:00 is 3:00 PM', soEventShowingClock(['date' => ['datetime' => '2026-12-26T15:00:00']]) === '3:00 PM');
escCheck('offset only is read', soEventShowingClock(['date' => ['datetimeOffset' => '2026-12-26T09:05:00-05:00']]) === '9:05 AM');
escCheck('noon is 12:00 PM', soEventShowingClock(['date' => ['datetime' => '2026-12-26T12:00:00']]) === '12:00 PM');
escCheck('midnight is no time', soEventShowingClock(['date' => ['datetime' => '2026-12-26T00:00:00']]) === '');
escCheck('a day alone is no time', soEventShowingClock(['date' => ['date' => '2026-12-26']]) === '');
escCheck('no date is no time', soEventShowingClock([]) === '');

// 2. The event page reads the clock through that helper, not from a day-only field.
$src = (string) file_get_contents(__DIR__ . '/../inc/seo-event.php');
escCheck('seo-event uses soEventShowingClock', substr_count($src, 'soEventShowingClock($event)') === 2, 'expected the two clock assignments');
escCheck('seo-event no longer reads the clock from the day-only field', strpos($src, "date('H:i', \$evTs)") === false);

// 3. The two showings of the Trans-Siberian Orchestra page (3:00 PM and 7:30 PM, Dec 26) get different titles.
$label = 'Trans-Siberian Orchestra';
$spec  = soSpecEventText('concerts', ['label' => $label, 'perf' => $label, 'venue' => 'Prudential Center', 'city' => 'Newark', 'state' => 'NJ', 'day' => 'Dec 26', 'cat' => 'Rock', 'country' => 'the United States', 'hasTickets' => true]);
$base  = soTitleUpTo(SO_TITLE_EVENT_MAX, ...$spec['titles']);
$first  = ['date' => ['date' => '2026-12-26', 'datetime' => '2026-12-26T15:00:00']];
$second = ['date' => ['date' => '2026-12-26', 'datetime' => '2026-12-26T19:30:00']];
$t1 = soEventUniqueTitle($base, $label, 'Dec 26', soEventShowingClock($first), true);
$t2 = soEventUniqueTitle($base, $label, 'Dec 26', soEventShowingClock($second), true);
escCheck('showings get different titles', $t1 !== $t2, "$t1 / $t2");
escCheck('second showing names 7:30 PM', strpos($t2, '7:30 PM') !== false, $t2);
escCheck('title stays within the event title limit', mb_strlen($t2) <= SO_TITLE_EVENT_MAX + 5, $t2);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: event showing time in titles\n";
