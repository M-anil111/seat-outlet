<?php
/**
 * Keeps maintenance scripts (cron/*, db/migrate.php, tools/*) off the public web.
 *
 * These live inside the web root, and a plain GET used to RUN them for anyone:
 * /cron/warm-listings.php fired ~57 TicketNetwork calls per request and
 * /db/migrate.php touched the database. They are meant for `php script.php`
 * from cron or SSH.
 *
 * CLI always works. Over HTTP the request is refused with 403 unless the
 * environment defines CRON_TOKEN (in the env file inc/env.php loads) and the request carries
 * the same value as ?token=... - only for a host that can schedule URLs and not
 * commands. Prefer the CLI.
 */
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/env.php';
    $so_token = (string) getenv('CRON_TOKEN');
    $so_given = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
    if ($so_token === '' || !hash_equals($so_token, $so_given)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit("Forbidden\n");
    }
}
