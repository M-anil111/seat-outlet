<?php
/**
 * CI: a cron script that dies with an uncaught exception or a fatal error says so (error log + stderr, exit code that is not 0), and a script
 * that finishes cleanly prints nothing extra. Every cron script is wrapped. Audit 16 Nov 2026: cron failures were invisible.
 * Run: php tools/test-cron-report.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function crCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }
$root = dirname(__DIR__);
$lib = var_export($root . '/inc/cron-report.php', true);

$run = function (string $body) use ($lib) {
    $tmp = tempnam(sys_get_temp_dir(), 'so-cron-'); file_put_contents($tmp, "<?php\nrequire_once $lib; soCronWatch('zq');\n$body\n");
    $proc = proc_open(['php', '-d', 'log_errors=1', '-d', 'error_log=' . $tmp . '.log', '-d', 'display_errors=0', $tmp], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $rc = proc_close($proc);
    $log = (string) @file_get_contents($tmp . '.log');
    @unlink($tmp); @unlink($tmp . '.log');
    return [$rc, $out, $err, $log];
};

[$rc, $out, $err, $log] = $run("throw new RuntimeException('boom');");
crCheck('an uncaught exception exits 1', $rc === 1, (string) $rc);
crCheck('...names the script and the error on stderr', strpos($err, 'cron zq FAILED: RuntimeException: boom') !== false, $err);
crCheck('...and in the error log', strpos($log, 'cron zq FAILED: RuntimeException: boom') !== false, $log);

[$rc, $out, $err, $log] = $run("zq_undefined_function_call();");
crCheck('a fatal error is reported (exit code is not 0)', $rc !== 0, (string) $rc);
crCheck('...on stderr and in the error log', strpos($err, 'cron zq FAILED') !== false && strpos($log, 'cron zq FAILED') !== false, $err . $log);

[$rc, $out, $err, $log] = $run("echo 'done';");
crCheck('a clean run exits 0, prints only its own output, logs nothing', $rc === 0 && $out === 'done' && $err === '' && trim($log) === '', "$rc|$out|$err|$log");

// Every cron script is wrapped, except the four that already report their own failures.
$own = ['backup-db.php', 'check-php-version.php', 'check-tn-health.php', 'tn-bulk-import.php'];
foreach (glob($root . '/cron/*.php') as $f) {
    if (in_array(basename($f), $own, true)) continue;
    crCheck('cron/' . basename($f) . ' calls soCronWatch', strpos((string) file_get_contents($f), "soCronWatch('" . basename($f, '.php') . "')") !== false);
}
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: cron failures are reported\n";
