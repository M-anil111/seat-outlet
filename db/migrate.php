<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Schema migration runner for the Seat Outlet database.
 *
 * Applies every *.sql file in db/migrations/ that hasn't been applied yet,
 * in filename order, tracked in a `schema_migrations` table so this is safe
 * to run repeatedly - locally, in CI (see .github/workflows/ci.yml, which
 * runs this against a fresh MySQL container on every PR to catch broken
 * SQL before it can reach beta), and against the real beta database.
 *
 * Usage:
 *   php db/migrate.php                 Apply all pending migrations.
 *   php db/migrate.php --status        List migrations and whether applied.
 *   php db/migrate.php --seed=NAME     Also apply db/seeds/NAME.sql
 *                                      (sample/demo data - never applied
 *                                      automatically, and not tracked in
 *                                      schema_migrations since seeds are
 *                                      expected to be safely re-runnable,
 *                                      not "applied once").
 *
 * Uses the same DB_HOST/DB_USER/DB_PASS/DB_NAME environment contract as
 * the rest of the app (see db/config.php / .env.example) - nothing new to
 * configure.
 */

require_once __DIR__ . '/config.php';

/** @var mysqli $mysqli */
$mysqli = MYSQLI;

function migrate_run_sql_file(mysqli $mysqli, string $path): void {
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("Could not read $path");
    }

    if (!$mysqli->multi_query($sql)) {
        throw new RuntimeException("Error in $path: " . $mysqli->error);
    }

    // multi_query() runs statements one at a time - drain all results (and
    // surface the first error, if any) before this connection can be used
    // for anything else.
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        if ($mysqli->errno) {
            throw new RuntimeException("Error in $path: " . $mysqli->error);
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
}

$mysqli->query(
    'CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `migration` varchar(255) NOT NULL,
        `applied_at` datetime NOT NULL,
        PRIMARY KEY (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = [];
$result = $mysqli->query('SELECT migration FROM schema_migrations');
while ($row = $result->fetch_assoc()) {
    $applied[$row['migration']] = true;
}

$migrationFiles = glob(__DIR__ . '/migrations/*.sql');
sort($migrationFiles);

$args = array_slice($argv, 1);
$statusOnly = in_array('--status', $args, true);
$seedName = null;
foreach ($args as $arg) {
    if (str_starts_with($arg, '--seed=')) {
        $seedName = substr($arg, strlen('--seed='));
    }
}

$exitCode = 0;

foreach ($migrationFiles as $file) {
    $name = basename($file);
    $isApplied = isset($applied[$name]);

    if ($statusOnly) {
        echo ($isApplied ? '[applied]  ' : '[pending]  ') . $name . "\n";
        continue;
    }

    if ($isApplied) {
        echo "skip     $name (already applied)\n";
        continue;
    }

    try {
        migrate_run_sql_file($mysqli, $file);
        $stmt = $mysqli->prepare('INSERT INTO schema_migrations (migration, applied_at) VALUES (?, NOW())');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
        echo "applied  $name\n";
    } catch (Throwable $e) {
        echo "FAILED   $name: " . $e->getMessage() . "\n";
        $exitCode = 1;
        break; // stop at the first failure - later migrations may depend on this one
    }
}

if ($seedName !== null && $exitCode === 0) {
    $seedFile = __DIR__ . '/seeds/' . basename($seedName) . '.sql';
    if (!is_file($seedFile)) {
        echo "FAILED   seed '$seedName' not found at $seedFile\n";
        $exitCode = 1;
    } else {
        try {
            migrate_run_sql_file($mysqli, $seedFile);
            echo "seeded   " . basename($seedFile) . "\n";
        } catch (Throwable $e) {
            echo "FAILED   seed " . basename($seedFile) . ': ' . $e->getMessage() . "\n";
            $exitCode = 1;
        }
    }
}

exit($exitCode);
