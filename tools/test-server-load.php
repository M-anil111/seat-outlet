<?php
/**
 * CI: background work yields to visitors (inc/server-load.php). The page warmers, the image queue, the sitemap builder and the top performers
 * refresh share the PHP workers and the CPU with the public pages; on a busy server they skip their turn instead of making it worse. The web
 * triggered image and sitemap workers also stand down while their cron is running (it leaves a heartbeat). Nothing changes when the server is
 * quiet or hides its load. Run: php tools/test-server-load.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
putenv('SO_BG_MAX_LOAD');
require_once __DIR__ . '/../functions.php';

$fail = 0;
function slCheck(string $name, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $name: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; }
}
$root = dirname(__DIR__);

// The rule: busy above one load per core, or above SO_BG_MAX_LOAD.
slCheck('quiet: 0.5 on 4 cores', soServerBusy(0.5, 4, 0.0), false);
slCheck('at the limit: 4.0 on 4 cores', soServerBusy(4.0, 4, 0.0), false);
slCheck('busy: 4.1 on 4 cores', soServerBusy(4.1, 4, 0.0), true);
slCheck('one core, load 1.2: busy', soServerBusy(1.2, 1, 0.0), true);
slCheck('an absolute limit wins over the core count', soServerBusy(3.0, 16, 2.0), true);
slCheck('an absolute limit above the load: quiet', soServerBusy(3.0, 1, 5.0), false);
putenv('SO_BG_MAX_LOAD=0.0001');
slCheck('the environment limit is read (always busy at 0.0001)', soServerBusy(0.5, 64), true);
putenv('SO_BG_MAX_LOAD=100000');
slCheck('the environment limit is read (never busy at 100000)', soServerBusy(50.0, 1), false);
putenv('SO_BG_MAX_LOAD=not-a-number');
slCheck('a bad environment value is ignored (falls back to cores)', soServerBusy(0.1, 4), false);
putenv('SO_BG_MAX_LOAD');
slCheck('a negative limit always means busy, whatever the load', soServerBusy(0.0, 64, -1.0), true);
putenv('SO_BG_MAX_LOAD=-1');
slCheck('SO_BG_MAX_LOAD=-1 pauses background work', soServerBusy(), true);
putenv('SO_BG_MAX_LOAD');
slCheck('there is at least one core', soCpuCores() >= 1, true);
$l = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
slCheck('with no argument it reads the real load without failing', is_bool(soServerBusy()), true);

// Heartbeat.
@unlink($root . '/cache/zq_test.cron');
slCheck('no heartbeat: not recently ran', soCronRecentlyRan('zq_test', 60), false);
soCronHeartbeat('zq_test');
slCheck('after a heartbeat: recently ran', soCronRecentlyRan('zq_test', 60), true);
touch($root . '/cache/zq_test.cron', time() - 120);
slCheck('an old heartbeat does not count', soCronRecentlyRan('zq_test', 60), false);
@unlink($root . '/cache/zq_test.cron');

// The wiring: each cron yields when busy (run for real with a limit nothing can stay under: they exit at once, with no network), the
// web-triggered workers check both, and the heartbeats are written by the crons whose workers they silence.
foreach (['warm-listings', 'warm-event-details', 'resolve-images', 'warm-snapshots'] as $job) {
    $out = []; $rc = 0;
    exec('SO_BG_MAX_LOAD=-1 php ' . escapeshellarg($root . '/cron/' . $job . '.php') . ' 2>&1', $out, $rc);
    $text = implode("\n", $out);
    slCheck("$job: exits 0 and says it skipped because the server is busy", [$rc, strpos($text, 'the server is busy') !== false], [0, true]);
}
$ws = (string) file_get_contents($root . '/cron/warm-snapshots.php');
slCheck('warm-snapshots reads --status before it yields', strpos($ws, "isset(\$opts['status'])") < strpos($ws, "soCronYieldIfBusy('warm-snapshots')"), true);
slCheck('warm-snapshots also stops in the middle of a run when busy', strpos($ws, '% 25 === 0 && soServerBusy()') !== false, true);
slCheck('resolve-images leaves a heartbeat', strpos((string) file_get_contents($root . '/cron/resolve-images.php'), "soCronHeartbeat('image_worker')") !== false, true);
slCheck('build-sitemaps leaves a heartbeat', strpos((string) file_get_contents($root . '/cron/build-sitemaps.php'), "soCronHeartbeat('sitemap_worker')") !== false, true);
slCheck('the image worker stands down for the cron or a busy server', strpos((string) file_get_contents($root . '/inc/images.php'), "soCronRecentlyRan('image_worker', 1800) || soServerBusy()") !== false, true);
slCheck('the sitemap worker stands down for the cron or a busy server', strpos((string) file_get_contents($root . '/inc/sitemap-build.php'), "soCronRecentlyRan('sitemap_worker', 7 * 3600) || soServerBusy()") !== false, true);
slCheck('the top performers refresh yields', strpos((string) file_get_contents($root . '/inc/top-performers.php'), 'if (soServerBusy()) return;') !== false, true);
slCheck('the ticket-catalog import is not touched (it has its own guards and the owner decides)', strpos((string) file_get_contents($root . '/cron/tn-bulk-import.php'), 'soServerBusy') === false, true);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: background work yields to visitors and its web duplicates stand down\n";
