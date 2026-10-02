<?php
// Deploy-generated file holding real secret values as putenv() calls, sourced from
// GitHub repository secrets at deploy time. Never committed to git - only exists
// on a server after the "Deploy To Demo" pipeline step has run. Loaded here (rather
// than in functions.php) so every entry point that needs the DB or constants.php
// secrets gets them, whether it goes through functions.php or includes this file
// directly (ajax/check-email.php, newsletter-email.php).
// Looked up inside the site folder first (where inc/geoip.php, inc/cli-guard.php and
// deploy/pull-deploy.sh expect it), then one level up (the older SFTP layout). The
// second is still read when the first exists but does not set DB_PASS (an incomplete
// copy in the site folder must not hide the complete file one level up).
foreach ([__DIR__ . '/../inc/env.local.php', __DIR__ . '/../../inc/env.local.php'] as $envLocalFile) {
    if (file_exists($envLocalFile)) {
        require_once $envLocalFile;
        $loadedPass = getenv('DB_PASS');
        if ($loadedPass !== false && $loadedPass !== '') {
            break;
        }
    }
}

/**
 * The database is unreachable or not configured. Visitors get a short "try again" page with a
 * 503 (so search engines retry later instead of indexing an error as a normal page), never the
 * raw error: it can name the database host and user. The detail goes to the server error log.
 */
function soDbUnavailable($detail) {
    error_log('Database unavailable: ' . $detail);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Database unavailable: ' . $detail . "\n");
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 30');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex"><title>Temporarily unavailable | Seat Outlet</title></head>'
       . '<body style="font-family:system-ui,sans-serif;text-align:center;padding:12vh 16px;color:#1f2937">'
       . '<h1 style="font-size:24px;margin:0 0 8px">We will be right back</h1>'
       . '<p style="margin:0;color:#5b6573">Seat Outlet is temporarily unavailable. Please try again in a minute.</p></body></html>';
    exit;
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'seatoutlet-beta');
define('DB_USER', getenv('DB_USER') ?: 'beta-seatoutlet');
$dbPass = getenv('DB_PASS');
if ($dbPass === false || $dbPass === '') {
    soDbUnavailable('DB_PASS environment variable is not set (is inc/env.local.php missing?)');
}
define('DB_PASS', $dbPass);

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
} catch (Throwable $e) {   // PHP 8.1+ throws on a failed connection instead of setting connect_error
    soDbUnavailable($e->getMessage());
}

if ($mysqli->connect_error) {
    soDbUnavailable($mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');


define('MYSQLI', getenv('MYSQLI') ?: $mysqli);
