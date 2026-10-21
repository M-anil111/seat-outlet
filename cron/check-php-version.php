<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Raises a Sentry error while the PHP this server runs is older than the fixed release of its branch (inc/php-security.php), so a missed
 * PHP security update does not go unnoticed. Run daily. Prints the verdict; exits 1 when not patched, so a cron mailer notices as well.
 *
 *   php cron/check-php-version.php
 *
 * Run it with the same PHP the web server uses (the FPM version can differ from the command line: check both with `php -v` and
 * `php-fpm<version> -v`).
 */
require_once __DIR__ . '/../inc/php-security.php';

$s = soPhpPatchStatus();
echo $s['note'] . "\n";
if ($s['patched']) { echo "ok\n"; exit(0); }

$message = 'PHP security update needed: ' . $s['note'];
if (!class_exists('\Sentry\SentrySdk') && is_file(__DIR__ . '/../vendor/autoload.php')) require_once __DIR__ . '/../vendor/autoload.php';
if (function_exists('\Sentry\captureMessage')) {
    \Sentry\withScope(function ($scope) use ($message) {
        $scope->setLevel(\Sentry\Severity::error());
        $scope->setFingerprint(['php-security-update']);
        \Sentry\captureMessage($message);
    });
}
echo "PROBLEM: $message\n";
exit(1);
