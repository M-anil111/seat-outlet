<?php
/**
 * CI: database errors are never silent. db/config.php sets mysqli to throw on every failed query, explicitly (the PHP 8.1 default, stated
 * so no php.ini and no older PHP can turn it back into a quiet false that lets a write look as if it worked).
 * Run: php tools/test-db-error-mode.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function emCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

$src = (string) file_get_contents(__DIR__ . '/../db/config.php');
emCheck('db/config.php sets the mysqli error mode explicitly', strpos($src, 'mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);') !== false);
emCheck('the error mode is set before the connection is opened', strpos($src, 'mysqli_report(') < strpos($src, 'new mysqli('));

$code = 'foreach (["CONSUMER_KEY","CONSUMER_SECRET","GAPI_KEY","AWS_ACCESS_KEY","AWS_SECRET_KEY","RECAPTCHA_SECRET_KEY"] as $k) { if ((string) getenv($k) === "") putenv($k . "=x"); }'
      . ' require ' . var_export(realpath(__DIR__ . '/../db/config.php'), true) . ';'
      . ' try { MYSQLI->query("SELECT * FROM so_table_that_does_not_exist_zq"); echo "silent"; } catch (mysqli_sql_exception $e) { echo "threw"; }';
$out = trim((string) shell_exec('php -d mysqli.reconnect=0 -r ' . escapeshellarg($code) . ' 2>/dev/null'));
emCheck('a failed query throws instead of returning false', $out === 'threw', $out);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: database errors are never silent\n";
