<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Keeps the BETA database in step with the code, with nobody on the server: after a public page has been sent, if db/migrations has files
 * that schema_migrations does not list, they are applied (same runner and same rules as `php db/migrate.php`: in filename order, stop at
 * the first failure, "already applied" errors tolerated). A deploy that adds a migration therefore needs no manual step.
 *
 *   On by default only on hosts that are not indexable (beta, staging, local). On the live site it is OFF unless SO_AUTO_MIGRATE=1,
 *   because schema changes on production should be a deliberate step. SO_AUTO_MIGRATE=0 turns it off anywhere.
 *   Checked at most every 2 minutes (a stamp file); a lock stops two requests running it at once; after a failure it waits 15 minutes
 *   before trying again, and the failure (file and message) is in cache/migrate_last.json, shown by /ajax/health.php and sent to Sentry.
 */
function soAutoMigrateEnabled(): bool {
    $v = getenv('SO_AUTO_MIGRATE');
    if ($v === '0') return false;
    if ($v === '1') return true;
    return !SITE_INDEXABLE;
}

function soAutoMigrateMaybeRun(): void {
    if (PHP_SAPI === 'cli' || !soAutoMigrateEnabled() || !function_exists('fastcgi_finish_request')) return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $dir = dirname(__DIR__) . '/cache';
    if (!is_dir($dir) || !is_writable($dir)) return;
    $stamp = $dir . '/migrate.stamp';
    if (is_file($stamp) && time() - (int) @filemtime($stamp) < 120) return;
    @touch($stamp);
    $lock = @fopen($dir . '/migrate.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return;
    try {
        $last = json_decode((string) @file_get_contents($dir . '/migrate_last.json'), true);
        if (is_array($last) && !empty($last['error']) && time() - (int) ($last['at'] ?? 0) < 900) return;   // failed recently: do not hammer

        $db = MYSQLI;
        $db->query('CREATE TABLE IF NOT EXISTS `schema_migrations` (`migration` varchar(255) NOT NULL, `applied_at` datetime NOT NULL, PRIMARY KEY (`migration`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $applied = [];
        $res = $db->query('SELECT migration FROM schema_migrations');
        while ($res && ($row = $res->fetch_row())) { $applied[$row[0]] = true; }
        $files = glob(dirname(__DIR__) . '/db/migrations/*.sql') ?: [];
        sort($files);
        $pending = array_values(array_filter($files, function ($f) use ($applied) { return !isset($applied[basename($f)]); }));
        if (!$pending) return;

        ignore_user_abort(true);
        @set_time_limit(120);
        fastcgi_finish_request();   // the visitor already has the page
        require_once dirname(__DIR__) . '/db/migrate-lib.php';
        $done = [];
        $result = ['at' => time(), 'applied' => [], 'error' => null];
        foreach ($pending as $file) {
            $name = basename($file);
            try {
                migrate_run_sql_file($db, $file);
                $stmt = $db->prepare('INSERT IGNORE INTO schema_migrations (migration, applied_at) VALUES (?, NOW())');
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $stmt->close();
                $result['applied'][] = $name;
            } catch (Throwable $e) {
                $result['error'] = $name . ': ' . $e->getMessage();
                if (class_exists('\Sentry\SentrySdk')) \Sentry\captureMessage('Auto-migration failed: ' . $result['error']);
                break;   // later migrations may depend on this one
            }
        }
        file_put_contents($dir . '/migrate_last.json', json_encode($result));
    } catch (Throwable $e) {
        error_log('auto-migrate: ' . $e->getMessage());
    }
    flock($lock, LOCK_UN);
}
