<?php
/**
 * Loads the server's secrets file (putenv() calls - see
 * deploy/env.local.php.example) into the environment. Every entry point that
 * needs secrets goes through here: db/config.php, inc/geoip.php, and anything
 * that includes functions.php.
 *
 * The file is looked for in this order; the first one that exists wins:
 *
 *   1. SEATOUTLET_ENV_FILE         explicit path, e.g. set in the PHP-FPM pool
 *                                  (env[SEATOUTLET_ENV_FILE] = ...) or a crontab
 *   2. /home/<site-user>/.seatoutlet/env.local.php
 *                                  RECOMMENDED: outside htdocs, so no deploy
 *                                  (pull, rsync or a fresh directory) can
 *                                  overwrite or delete it, and it is never
 *                                  web-served. <site-user> is taken from this
 *                                  file's own path (CloudPanel puts every site
 *                                  under /home/<site-user>/htdocs/...).
 *   3. <code root>/inc/env.local.php
 *                                  legacy location, written by the SFTP deploy
 *                                  workflow; gitignored.
 *
 * Variables already present in the real environment (PHP-FPM env[], a
 * crontab, the shell) are not overridden by the file.
 */
if (defined('SEATOUTLET_ENV_LOADED')) {
    return;
}
define('SEATOUTLET_ENV_LOADED', true);

function seatoutletEnvCandidates() {
    $candidates = [];
    $explicit = getenv('SEATOUTLET_ENV_FILE');
    if ($explicit !== false && $explicit !== '') {
        $candidates[] = $explicit;
    }
    if (preg_match('#^(/home/[^/]+)/#', __DIR__, $m)) {
        $candidates[] = $m[1] . '/.seatoutlet/env.local.php';
    }
    $candidates[] = __DIR__ . '/env.local.php';
    return $candidates;
}

function seatoutletLoadEnvFile($path) {
    // Snapshot what the real environment already provides so the file only
    // fills gaps (putenv() in the file would otherwise overwrite it).
    $before = getenv();
    require $path;
    foreach ($before as $name => $value) {
        if (getenv($name) !== $value) {
            putenv($name . '=' . $value);
        }
    }
}

define('SEATOUTLET_ENV_FILE_USED', (function () {
    foreach (seatoutletEnvCandidates() as $path) {
        if (is_file($path) && is_readable($path)) {
            seatoutletLoadEnvFile($path);
            return $path;
        }
    }
    return '';
})());
