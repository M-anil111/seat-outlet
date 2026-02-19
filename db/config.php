<?php
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'seatoutlet-beta');
define('DB_USER', getenv('DB_USER') ?: 'beta-seatoutlet');
define('DB_PASS', getenv('DB_PASS') ?: 'qpBQ1iaddyDA5Li4Jucy');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($mysqli->connect_error) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');


define('MYSQLI', getenv('MYSQLI') ?: $mysqli);