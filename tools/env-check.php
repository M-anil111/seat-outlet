<?php
/**
 * Server environment check. Run on the server as the site user:
 *
 *   php tools/env-check.php
 *
 * Reports which env file was loaded, which variables are set or missing
 * (names only - values are never printed), whether the database connects,
 * and whether the runtime directories are writable. Exit code 0 = ready.
 * CLI only: refuses to run from the web.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../inc/env.php';

$required = [
    'DB_PASS'              => 'database password (db/config.php)',
    'CONSUMER_KEY'         => 'TicketNetwork API',
    'CONSUMER_SECRET'      => 'TicketNetwork API',
    'GAPI_KEY'             => 'Google APIs',
    'AWS_ACCESS_KEY'       => 'Cloudflare R2 image storage',
    'AWS_SECRET_KEY'       => 'Cloudflare R2 image storage',
    'RECAPTCHA_SECRET_KEY' => 'reCAPTCHA on forms',
];
$optional = [
    'DB_HOST' => 'default 127.0.0.1', 'DB_PORT' => 'default 3306',
    'DB_NAME' => 'default seatoutlet-beta', 'DB_USER' => 'default beta-seatoutlet',
    'SMTP_USER' => 'emails skipped without it', 'SMTP_PASS' => 'emails skipped without it',
    'SENTRY_DSN' => 'no error monitoring without it', 'GKGSAPI_KEY' => 'unused for images',
    'THESPORTSDB_KEY' => 'free public key used', 'PEXELS_API_KEY' => 'no city skylines',
    'MAXMIND_ACCOUNT_ID' => 'geoip cron only', 'MAXMIND_LICENSE_KEY' => 'geoip cron only',
    'GEOIP_DB_PATH' => 'default beside the code root', 'TN_CHECKOUT_URL' => 'default https://checkout.seatoutlet.com',
    'GTM_ID' => 'no analytics tag', 'TN_TOKEN_DIR' => 'default system temp dir',
    'BASE_URL' => 'default sandbox', 'HOME_URL' => 'default https://beta.seatoutlet.com',
    'CRON_TOKEN' => 'cron/tools refused over HTTP (CLI still works)',
];

$ok = true;
echo "Code root:     " . dirname(__DIR__) . "\n";
echo "Env file used: " . (SEATOUTLET_ENV_FILE_USED ?: 'NONE FOUND') . "\n";
echo "Looked in:\n";
foreach (seatoutletEnvCandidates() as $c) {
    echo "  " . (is_file($c) ? '[exists]  ' : '[missing] ') . $c . "\n";
}
if (SEATOUTLET_ENV_FILE_USED) {
    $perms = substr(sprintf('%o', fileperms(SEATOUTLET_ENV_FILE_USED)), -3);
    echo "  permissions: $perms" . ($perms[2] !== '0' ? '  <- readable by other users, run: chmod 600 ' . SEATOUTLET_ENV_FILE_USED : '') . "\n";
    if (strpos(realpath(SEATOUTLET_ENV_FILE_USED), realpath(dirname(__DIR__))) === 0) {
        echo "  NOTE: this file is inside the code directory; a deploy into a fresh directory will not have it. Move it to ~/.seatoutlet/env.local.php\n";
    }
}

echo "\nRequired:\n";
foreach ($required as $name => $what) {
    $set = getenv($name) !== false && getenv($name) !== '';
    $ok = $ok && $set;
    printf("  %-22s %s  (%s)\n", $name, $set ? 'set' : 'MISSING', $what);
}
echo "\nOptional:\n";
foreach ($optional as $name => $what) {
    $set = getenv($name) !== false && getenv($name) !== '';
    printf("  %-22s %s\n", $name, $set ? 'set' : "not set ($what)");
}

echo "\nDatabase: ";
if (getenv('DB_PASS') === false || getenv('DB_PASS') === '') {
    echo "skipped (DB_PASS missing)\n";
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'beta-seatoutlet',
        getenv('DB_PASS'), getenv('DB_NAME') ?: 'seatoutlet-beta', (int) (getenv('DB_PORT') ?: 3306));
    if ($db->connect_error) {
        $ok = false;
        echo "FAILED - " . $db->connect_error . "\n";
    } else {
        echo "connected\n";
        $res = $db->query("SHOW TABLES LIKE 'schema_migrations'");
        if ($res && $res->num_rows) {
            $n = $db->query('SELECT COUNT(*) c FROM schema_migrations')->fetch_assoc()['c'];
            $total = count(glob(__DIR__ . '/../db/migrations/*.sql'));
            echo "  migrations applied: $n of $total" . ($n < $total ? '  <- run: php db/migrate.php' : '') . "\n";
        } else {
            echo "  migrations: none recorded  <- run: php db/migrate.php\n";
        }
    }
}

echo "\nWritable directories:\n";
foreach ([dirname(__DIR__) . '/cache', getenv('TN_TOKEN_DIR') ?: sys_get_temp_dir()] as $dir) {
    $w = is_dir($dir) ? is_writable($dir) : is_writable(dirname($dir));
    $ok = $ok && $w;
    printf("  %-60s %s\n", $dir, $w ? 'writable' : 'NOT WRITABLE');
}

echo "\n" . ($ok ? "READY\n" : "NOT READY - fix the items marked above\n");
exit($ok ? 0 : 1);
