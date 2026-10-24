<?php
// The CSP report summary (inc/csp.php soCspSummarize, tools/csp-report-summary.php) and the report endpoint's log line. No database.
// Run: php tools/test-csp-summary.php
require_once __DIR__ . '/../inc/csp.php';
$fail = 0;
function csCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$lines = [
    "[24-Oct-2026 01:00:00 UTC] csp-report-only: script-src-elem blocked=https://Evil.Example/a.js page=https://seatoutlet.com/event/x-1?utm=1",
    "[24-Oct-2026 01:00:01 UTC] csp-report-only: script-src-elem blocked=https://evil.example/b.js page=https://seatoutlet.com/event/y-2",
    "[24-Oct-2026 01:00:02 UTC] csp-report-only: script-src-elem blocked=https://evil.example/c.js page=https://seatoutlet.com/event/y-2",
    "[24-Oct-2026 01:00:03 UTC] csp-report-only: script-src blocked= page=https://seatoutlet.com/",
    "[24-Oct-2026 01:00:04 UTC] csp-report-only: img-src blocked=data page=https://seatoutlet.com/blog",
    "[24-Oct-2026 01:00:05 UTC] some other log line",
    "[24-Oct-2026 01:00:06 UTC] csp-report-only: connect-src blocked=wss://live.example/socket page=https://seatoutlet.com/event/z-3",
];
$r = soCspSummarize($lines);
csCheck('six report lines counted, the unrelated line ignored', $r['total'] === 6);
csCheck('hosts are lower-cased and grouped per directive', $r['rows'][0][0] === 3 && $r['rows'][0][1] === 'script-src-elem' && $r['rows'][0][2] === 'evil.example');
csCheck('the most frequent row is first', $r['rows'][0][0] >= $r['rows'][1][0]);
csCheck('an empty blocked uri is "inline or eval"', in_array([1, 'script-src', '(inline or eval)'], array_map(fn($x) => [$x[0], $x[1], $x[2]], $r['rows']), true));
csCheck('a keyword such as "data" is kept as it is', in_array('data', array_column($r['rows'], 2), true));
csCheck('a ws:// host is reduced to its host', in_array('live.example', array_column($r['rows'], 2), true));
csCheck('pages are paths without the query string, at most three, no repeats', $r['rows'][0][3] === ['/event/x-1', '/event/y-2']);
csCheck('an empty log is zero', soCspSummarize([])['total'] === 0 && soCspSummarize([])['rows'] === []);

// the command line tool
$tmp = tempnam(sys_get_temp_dir(), 'csp'); file_put_contents($tmp, implode("\n", $lines) . "\n");
$out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/csp-report-summary.php') . ' ' . escapeshellarg($tmp));
csCheck('the tool prints rows and a total', strpos($out, 'script-src-elem  evil.example') !== false && strpos($out, '6 report(s) in total.') !== false);
$j = json_decode((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/csp-report-summary.php') . ' ' . escapeshellarg($tmp) . ' --json'), true);
csCheck('--json gives the same rows', is_array($j) && $j['total'] === 6 && $j['rows'][0]['host'] === 'evil.example' && $j['rows'][0]['count'] === 3);
unlink($tmp);

// the endpoint writes the line this tool reads
$ep = (string) file_get_contents(__DIR__ . '/../ajax/csp-report.php');
csCheck("the endpoint logs 'csp-report-only: <directive> blocked=<uri> page=<url>'", strpos($ep, "'csp-report-only: ' . \$pick('effective-directive', 'effectiveDirective') . ' blocked=' . \$pick('blocked-uri', 'blockedURL') . ' page=' . \$pick('document-uri', 'documentURL')") !== false);
exit($fail ? 1 : 0);
