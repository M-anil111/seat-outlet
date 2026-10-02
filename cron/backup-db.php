<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Nightly database backup: a compressed mysqldump, kept for a number of days, optionally copied to a private S3 bucket.
 *
 *   php cron/backup-db.php                 dump into the backup folder, delete dumps older than the retention
 *   php cron/backup-db.php --dry-run       print what would happen, write nothing
 *   php cron/backup-db.php --verify=FILE   check a dump file (gzip test + the expected tables), restore nothing
 *
 * Settings (server-only, inc/env.local.php or the environment; nothing is stored in the repository):
 *   SO_BACKUP_DIR        where dumps go. Must be OUTSIDE the web root. Default: ../seatoutlet-backups next to the site folder.
 *   SO_BACKUP_KEEP_DAYS  how many days to keep (default 14)
 *   SO_BACKUP_S3         optional "s3://bucket/prefix" to copy each dump to (needs the aws command line tool and the AWS keys)
 *
 * The database password is passed to mysqldump through the MYSQL_PWD environment variable, never on the command line, so it
 * does not show up in the process list. Dumps hold customer email addresses: keep the folder and the bucket private.
 */
require_once __DIR__ . '/../db/config.php';

$opts = getopt('', ['dry-run', 'verify::', 'help']);
if (isset($opts['help'])) { echo "Usage: php cron/backup-db.php [--dry-run] [--verify=FILE]\n"; exit(0); }

function backup_verify(string $file): int {
    if (!is_file($file)) { fwrite(STDERR, "No such file: $file\n"); return 1; }
    $gz = gzopen($file, 'rb');
    if (!$gz) { fwrite(STDERR, "Not a readable gzip file\n"); return 1; }
    $head = ''; $tables = [];
    while (!gzeof($gz)) {
        $chunk = gzread($gz, 1 << 20);
        if ($chunk === false) { fwrite(STDERR, "gzip read error: the dump is damaged\n"); return 1; }
        if (strlen($head) < 200) $head .= $chunk;
        if (preg_match_all('/^CREATE TABLE `([^`]+)`/m', $chunk, $m)) foreach ($m[1] as $t) $tables[$t] = true;
    }
    gzclose($gz);
    $need = ['leads', 'lead_interests', 'images', 'blog_posts', 'schema_migrations'];
    $missing = array_values(array_diff($need, array_keys($tables)));
    echo 'tables in dump: ' . count($tables) . "\n";
    if ($missing) { fwrite(STDERR, 'Missing expected tables: ' . implode(', ', $missing) . "\n"); return 1; }
    echo "OK: gzip is intact and the expected tables are present.\n";
    return 0;
}

if (isset($opts['verify'])) exit(backup_verify((string) $opts['verify']));

$dbName = getenv('DB_NAME') ?: 'seatoutlet-beta';       // same defaults as db/config.php
$dbUser = getenv('DB_USER') ?: 'beta-seatoutlet';
$dbPass = (string) getenv('DB_PASS');
$dry = isset($opts['dry-run']);
$dir = getenv('SO_BACKUP_DIR') ?: dirname(__DIR__, 2) . '/seatoutlet-backups';
$keep = max(1, (int) (getenv('SO_BACKUP_KEEP_DAYS') ?: 14));
$s3 = trim((string) getenv('SO_BACKUP_S3'));
$real = realpath(dirname($dir)) ?: dirname($dir);
if (strpos(rtrim($real, '/') . '/', rtrim(realpath(__DIR__ . '/..') ?: '', '/') . '/') === 0) {
    fwrite(STDERR, "Refusing to write backups inside the web root ($dir). Set SO_BACKUP_DIR to a folder outside it.\n");
    exit(1);
}
$name = 'seatoutlet-' . preg_replace('/[^A-Za-z0-9_-]/', '', $dbName) . '-' . date('Ymd-His') . '.sql.gz';
$file = rtrim($dir, '/') . '/' . $name;
echo ($dry ? 'DRY RUN: ' : '') . "dump $file (keep $keep days" . ($s3 !== '' ? ", copy to $s3" : '') . ")\n";
if ($dry) exit(0);

if (!is_dir($dir) && !@mkdir($dir, 0700, true)) { fwrite(STDERR, "Cannot create $dir\n"); exit(1); }
@chmod($dir, 0700);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$cmd = ['mysqldump', '--host=' . $host, '--port=' . $port, '--user=' . $dbUser, '--single-transaction', '--quick', '--routines', '--default-character-set=utf8mb4', $dbName];
$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['MYSQL_PWD' => $dbPass, 'PATH' => getenv('PATH') ?: '/usr/bin:/bin']);
if (!is_resource($proc)) { fwrite(STDERR, "Could not start mysqldump (is it installed?)\n"); exit(1); }
$out = gzopen($file, 'wb6');
while (!feof($pipes[1])) { $buf = fread($pipes[1], 1 << 20); if ($buf !== false && $buf !== '') gzwrite($out, $buf); }
gzclose($out);
$err = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
$code = proc_close($proc);
if ($code !== 0) { @unlink($file); fwrite(STDERR, "mysqldump failed ($code): " . trim($err) . "\n"); exit(1); }
@chmod($file, 0600);
if (backup_verify($file) !== 0) { fwrite(STDERR, "The new dump failed verification and was kept as $file for inspection.\n"); exit(1); }
echo 'wrote ' . $name . ' (' . number_format(filesize($file) / 1048576, 1) . " MB)\n";

if ($s3 !== '') {
    $env = ['PATH' => getenv('PATH') ?: '/usr/bin:/bin', 'AWS_ACCESS_KEY_ID' => (string) getenv('AWS_ACCESS_KEY'), 'AWS_SECRET_ACCESS_KEY' => (string) getenv('AWS_SECRET_KEY')];
    $p = proc_open(['aws', 's3', 'cp', $file, rtrim($s3, '/') . '/' . $name, '--only-show-errors', '--sse', 'AES256'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, null, $env);
    $e = is_resource($p) ? stream_get_contents($pp[2]) : 'could not start the aws tool';
    $c = is_resource($p) ? proc_close($p) : 1;
    echo $c === 0 ? "copied to $s3\n" : "S3 copy FAILED (the local dump is safe): " . trim($e) . "\n";
}

$cut = time() - $keep * 86400; $removed = 0;
foreach (glob(rtrim($dir, '/') . '/seatoutlet-*.sql.gz') ?: [] as $f) { if (filemtime($f) < $cut && @unlink($f)) $removed++; }
echo "removed $removed dump(s) older than $keep days\n";
