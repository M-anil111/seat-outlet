<?php
/**
 * Sends one test message to Sentry so you can confirm error reporting works on this server.
 *
 *   php tools/sentry-test.php
 *
 * Then look for "Sentry test" in the project's Issues page within a minute. Run it from the
 * command line (a web request is refused, see inc/cli-guard.php).
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../functions.php';

if (SENTRY_DSN === '') {
    fwrite(STDERR, "SENTRY_DSN is empty: add it to inc/env.local.php (see deploy/env.local.php.example).\n");
    exit(1);
}
$id = \Sentry\captureMessage('Sentry test from tools/sentry-test.php (' . SENTRY_ENVIRONMENT . ' on ' . gethostname() . ')');
$client = \Sentry\SentrySdk::getCurrentHub()->getClient();
if ($client) {
    $client->flush(3);
}
echo $id
    ? "Sent test event $id to Sentry (environment: " . SENTRY_ENVIRONMENT . ").\n"
    : "Nothing was delivered. Check that SENTRY_DSN is correct and that this server can reach *.ingest.us.sentry.io over HTTPS (firewall / outbound rules).\n";
exit($id ? 0 : 1);
