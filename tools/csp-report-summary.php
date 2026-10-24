<?php
// Summarises the "csp-report-only:" lines that ajax/csp-report.php writes to the PHP error log, so the policy can be tightened.
// Usage on the server:  php tools/csp-report-summary.php /path/to/php-error.log [--json]
// Prints one row per directive and blocked host: how many times, and up to three pages it was seen on, most frequent first. Add the hosts
// you recognise to SO_CSP_POLICY (inc/csp.php, and SO_CSP_EXCEPTIONS if it is a wildcard); when the list is empty or only has things you
// accept losing, set CSP_ENFORCE=1 (docs/csp.md has the checklist).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../inc/csp.php';
$args = array_values(array_filter(array_slice($argv, 1), fn($a) => $a !== '--json'));
$json = in_array('--json', $argv, true);
$f = $args[0] ?? '';
if ($f === '' || !is_readable($f)) { fwrite(STDERR, "Usage: php tools/csp-report-summary.php /path/to/php-error.log [--json]\n"); exit(1); }
$r = soCspSummarize(new SplFileObject($f));
if ($json) {
    echo json_encode(['total' => $r['total'], 'rows' => array_map(fn($x) => ['count' => $x[0], 'directive' => $x[1], 'host' => $x[2], 'pages' => $x[3]], $r['rows'])], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}
foreach ($r['rows'] as [$c, $dir, $host, $pages]) printf("%6d  %s  %s%s\n", $c, $dir, $host, $pages ? '   (' . implode(', ', $pages) . ')' : '');
echo $r['total'] === 0 ? "No CSP reports in this log.\n" : $r['total'] . " report(s) in total.\n";
