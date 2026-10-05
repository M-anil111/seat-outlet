<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Checks the holiday dates (inc/holidays.php) against the calendar for 2026 to 2028, and that every page type has a rule.
 * Run: php tools/test-holidays.php
 */
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/test.php';
require_once __DIR__ . '/../inc/holidays.php';

$expected = [   // holiday => [2026, 2027, 2028] day of the holiday itself
    'easter-weekend-events'                => ['2026-04-05', '2027-03-28', '2028-04-16'],
    'thanksgiving-weekend-events'          => ['2026-11-26', '2027-11-25', '2028-11-23'],
    'canadian-thanksgiving-weekend-events' => ['2026-10-12', '2027-10-11', '2028-10-09'],
    'memorial-day-weekend-events'          => ['2026-05-25', '2027-05-31', '2028-05-29'],
    'victoria-day-weekend-events'          => ['2026-05-18', '2027-05-24', '2028-05-22'],
    'labor-day-weekend-events'             => ['2026-09-07', '2027-09-06', '2028-09-04'],
    'labour-day-weekend-events'            => ['2026-09-07', '2027-09-06', '2028-09-04'],
    'mothers-day-weekend-events'           => ['2026-05-10', '2027-05-09', '2028-05-14'],
    'fathers-day-weekend-events'           => ['2026-06-21', '2027-06-20', '2028-06-18'],
    'july-4th-events'                      => ['2026-07-04', '2027-07-04', '2028-07-04'],
    'canada-day-events'                    => ['2026-07-01', '2027-07-01', '2028-07-01'],
    'new-years-eve-events'                 => ['2026-12-31', '2027-12-31', '2028-12-31'],
    'valentines-day-events'                => ['2026-02-14', '2027-02-14', '2028-02-14'],
    'st-patricks-day-events'               => ['2026-03-17', '2027-03-17', '2028-03-17'],
    'halloween-events'                     => ['2026-10-31', '2027-10-31', '2028-10-31'],
    'boxing-day-events'                    => ['2026-12-26', '2027-12-26', '2028-12-26'],
];
$errors = [];
foreach ($expected as $key => $days) {
    foreach ([2026, 2027, 2028] as $i => $year) {
        $d = soHolidayDay($key, $year);
        $got = $d ? $d[0]->format('Y-m-d') : 'none';
        if ($got !== $days[$i]) $errors[] = "$key $year: expected {$days[$i]}, got $got";
    }
}
foreach (SO_HOLIDAYS as $key => $h) {
    if (!isset($expected[$key]) && $h['rule'][0] !== 'category') $errors[] = "$key has no expected dates in this test";
    if (!$h['countries']) $errors[] = "$key covers no country";
}
// A window never starts before today and never ends before it starts; the in-progress and wrap-around cases.
$w = soHolidayWindow('new-years-eve-events', new DateTimeImmutable('2027-01-01'));
if (!$w || $w['to'] !== '2027-01-01' || $w['from'] !== '2027-01-01') $errors[] = 'New Year\'s Eve on January 1 should still be on: ' . json_encode($w);
$w = soHolidayWindow('july-4th-events', new DateTimeImmutable('2026-07-04'));
if (!$w || $w['from'] !== '2026-07-04' || $w['to'] !== '2026-07-05') $errors[] = 'July 4th while on: ' . json_encode($w);
$w = soHolidayWindow('july-4th-events', new DateTimeImmutable('2026-07-06'));
if (!$w || $w['from'] !== '2027-07-03') $errors[] = 'July 4th after it has passed should move to next year: ' . json_encode($w);
if (soHolidayWindow('christmas-shows-near-me') !== null) $errors[] = 'Christmas is read by category, not by dates';
if ($errors) { fwrite(STDERR, implode("\n", $errors) . "\n"); exit(1); }
echo count($expected) . " holidays checked for 2026 to 2028, window rules ok\n";
