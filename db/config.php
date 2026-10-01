<?php
// Secrets (DB_PASS, API keys) come from the server's own env file, loaded by
// inc/env.php from outside the deploy directory - never from git. Loaded here
// (rather than in functions.php) so every entry point that needs the DB or
// constants.php secrets gets them, whether it goes through functions.php or
// includes this file directly (ajax/check-email.php, newsletter-email.php).
require_once __DIR__ . '/../inc/env.php';

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'seatoutlet-beta');
define('DB_USER', getenv('DB_USER') ?: 'beta-seatoutlet');
$dbPass = getenv('DB_PASS');
if ($dbPass === false || $dbPass === '') {
    error_log('DB_PASS is not set. Env file used: ' . (SEATOUTLET_ENV_FILE_USED ?: 'none found')
        . '; looked in: ' . implode(', ', seatoutletEnvCandidates()));
    die('Database connection failed: DB_PASS environment variable is not set.');
}
define('DB_PASS', $dbPass);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');


define('MYSQLI', getenv('MYSQLI') ?: $mysqli);