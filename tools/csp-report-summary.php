<?php
// Summarises the "csp-report-only:" lines that ajax/csp-report.php writes to the PHP error log, so the policy can be tightened.
// Usage on the server:  php tools/csp-report-summary.php /path/to/php-error.log
// Prints each blocked host per directive with a count, most frequent first. Add the hosts you recognise to the policy in
// soSendSecurityHeaders() (functions.php); when the list is empty or only contains things you accept losing, set CSP_ENFORCE=1.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$f = $argv[1] ?? '';
if ($f === '' || !is_readable($f)) { fwrite(STDERR, "Usage: php tools/csp-report-summary.php /path/to/php-error.log\n"); exit(1); }
$n = []; $total = 0;
foreach (new SplFileObject($f) as $line) {
    if (!preg_match('/csp-report-only: (\S+) blocked=(\S*) page=/', (string) $line, $m)) continue;
    $host = $m[2] === '' ? '(inline or eval)' : (preg_match('#^[a-z]+://([^/]+)#i', $m[2], $h) ? strtolower($h[1]) : $m[2]);
    $k = $m[1] . '  ' . $host; $n[$k] = ($n[$k] ?? 0) + 1; $total++;
}
arsort($n);
foreach ($n as $k => $c) printf("%6d  %s\n", $c, $k);
echo $total === 0 ? "No CSP reports in this log.\n" : "$total report(s) in total.\n";
