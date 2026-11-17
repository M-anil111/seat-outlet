<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Makes a cron script's failure visible. Before, an uncaught exception or a fatal error in cron/*.php ended the script with a message only
 * the cron mailer (if any) saw, and nothing reached the server log or Sentry. soCronWatch('name') at the top of a script:
 *   - logs "cron <name> FAILED: ..." to the PHP error log for an uncaught exception or a fatal error,
 *   - writes the same line to stderr (cron mails stderr to the account owner),
 *   - sends it to Sentry when the SDK is loaded (functions.php loads it; a script that does not include functions.php still logs),
 *   - exits 1 so a wrapper or monitor sees the failure.
 * It changes nothing for a script that finishes cleanly.
 */
function soCronReportFailure(string $name, string $detail): void {
    $line = 'cron ' . $name . ' FAILED: ' . $detail;
    error_log($line);
    if (PHP_SAPI === 'cli') fwrite(STDERR, $line . "\n");
    if (function_exists('\Sentry\captureMessage')) {
        try { \Sentry\captureMessage($line); } catch (\Throwable $e) { /* reporting must never raise a second failure */ }
    }
}

function soCronWatch(string $name): void {
    if (PHP_SAPI !== 'cli') return;
    set_exception_handler(function (\Throwable $e) use ($name) {
        soCronReportFailure($name, get_class($e) . ': ' . $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine());
        exit(1);
    });
    register_shutdown_function(function () use ($name) {
        $err = error_get_last();
        if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            soCronReportFailure($name, $err['message'] . ' at ' . basename($err['file']) . ':' . $err['line']);
        }
    });
}
