<?php
// Offline source check: an event the ticket feed lists with tickets must never be told "Tickets are not available" because the seat-map
// service does not know it. Run: php tools/test-widget-mismatch.php
$js = (string) file_get_contents(dirname(__DIR__) . '/js/event-widget.js');
$fails = 0;
function wmCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
preg_match('/c\.noEventHandler = function \(\) \{(.*?)\n    \};/s', $js, $m);
$h = $m[1] ?? '';
wmCheck('noEventHandler found', $h !== '');
wmCheck('listed tickets are checked before the "not available" message', preg_match('/ev\.hasTickets\s*&&\s*ev\.tickets\s*>\s*0.*showEmpty\(.Tickets are not available/s', $h) === 1);
wmCheck('the mismatch is tracked', strpos($h, 'widget_no_event_but_listed') !== false);
wmCheck('the automatic retry is guarded so it cannot loop', strpos($h, 'sessionStorage') !== false && strpos($h, 'location.reload') !== false && strpos($h, 'again') !== false);
wmCheck('the mismatch shows the reloadable failed state, not the empty state', strpos($h, "showState('failed'") !== false);
echo $fails ? "$fails failed\n" : "widget mismatch: all passed\n";
exit($fails ? 1 : 0);
