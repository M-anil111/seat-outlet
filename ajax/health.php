<?php
/**
 * Read-only status page for the people running the site: which commit is live, whether the database schema is current, and how the
 * picture queue is doing. Counts and yes/no only: no names, no keys, no visitor data.
 *
 *   GET /ajax/health.php
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$out = ['ok' => true, 'time' => gmdate('c')];
$stamp = @file_get_contents(__DIR__ . '/../.deployed-commit');
$out['commit'] = $stamp !== false ? substr(trim($stamp), 0, 8) : null;

try {
    $files = glob(__DIR__ . '/../db/migrations/*.sql') ?: [];
    $names = array_map(function ($f) { return basename($f); }, $files);
    $applied = [];
    $res = @MYSQLI->query('SELECT migration FROM schema_migrations');
    if ($res) { while ($r = $res->fetch_row()) { $applied[$r[0]] = true; } }
    $pending = array_values(array_filter($names, function ($n) use ($applied) { return !isset($applied[$n]); }));
    $out['migrations'] = ['files' => count($names), 'applied' => count($applied), 'pending' => count($pending), 'pendingNames' => array_slice($pending, 0, 10)];
    if ($pending) $out['ok'] = false;
    $ml = json_decode((string) @file_get_contents(__DIR__ . '/../cache/migrate_last.json'), true);
    if (is_array($ml)) $out['migrations']['lastAutoRun'] = ['secondsAgo' => time() - (int) ($ml['at'] ?? 0), 'applied' => $ml['applied'] ?? [], 'error' => $ml['error'] ?? null];
    $out['migrations']['autoMigrate'] = soAutoMigrateEnabled();
} catch (Throwable $e) {
    $out['migrations'] = ['error' => 'could not read'];
    $out['ok'] = false;
}

try {
    $img = ['byStatus' => []];
    $res = @MYSQLI->query('SELECT status, COUNT(*) FROM images GROUP BY status');
    if ($res) { while ($r = $res->fetch_row()) { $img['byStatus'][$r[0]] = (int) $r[1]; } }
    else { $img['error'] = 'images table not readable'; }
    $stampFile = __DIR__ . '/../cache/image_worker.stamp';
    $img['workerLastRunSecondsAgo'] = is_file($stampFile) ? time() - (int) filemtime($stampFile) : null;
    $img['cacheWritable'] = is_writable(__DIR__ . '/../cache');
    $img['fastcgi'] = function_exists('fastcgi_finish_request');
    $img['webWorkerEnabled'] = getenv('IMAGE_WEB_WORKER') !== '0';
    $out['images'] = $img;
} catch (Throwable $e) {
    $out['images'] = ['error' => 'could not read'];
}
echo json_encode($out, JSON_UNESCAPED_SLASHES);
