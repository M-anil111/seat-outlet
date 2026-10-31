<?php
/**
 * CI: the health page can tell why the deployed-commit marker is missing, and the deploy script leaves readable evidence
 * (a 0644 marker; a .deploy-error file when migrations fail). Offline, source checks. Run: php tools/test-deploy-marker.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';

$root = dirname(__DIR__);
$fail = 0;
function dmCheck(string $name, bool $ok): void { global $fail; if (!$ok) { echo "FAIL $name\n"; $fail++; } }

$health = (string) file_get_contents($root . '/ajax/health.php');
dmCheck('health reports commitMarker ok / unreadable / missing', strpos($health, "\$out['commitMarker'] = \$stamp !== false ? 'ok' : (is_file(\$stampFile) ? 'unreadable' : 'missing');") !== false);
dmCheck('health reports deployError from .deploy-error', strpos($health, "\$out['deployError']") !== false && strpos($health, "/../.deploy-error'") !== false);
dmCheck('health never exposes the whole error file (capped)', strpos($health, 'substr(trim($deployErr), 0, 200)') !== false);

$sh = (string) file_get_contents($root . '/deploy/pull-deploy.sh');
dmCheck('deploy makes the marker readable by the web user', strpos($sh, 'chmod 0644 "$STAMP"') !== false);
dmCheck('deploy writes .deploy-error when migrations fail', strpos($sh, '> "$WEBROOT/.deploy-error"') !== false && strpos($sh, 'MIGRATION FAILED') !== false);
dmCheck('deploy clears .deploy-error on a good deploy', strpos($sh, 'rm -f "$WEBROOT/.deploy-error"') !== false);
dmCheck('the marker is still written only after migrations succeed', strpos($sh, 'db/migrate.php') < strpos($sh, 'echo "$NEW" > "$STAMP"'));

// The marker logic itself, run against real files.
$dir = sys_get_temp_dir() . '/so-marker-' . getmypid(); @mkdir($dir);
$f = $dir . '/.deployed-commit';
$read = function () use ($f) { $s = @file_get_contents($f); return $s !== false ? 'ok' : (is_file($f) ? 'unreadable' : 'missing'); };
dmCheck('missing marker reads as missing', $read() === 'missing');
file_put_contents($f, "abcdef1234567890\n");
dmCheck('a readable marker reads as ok', $read() === 'ok');
if (function_exists('posix_geteuid') && posix_geteuid() !== 0) { chmod($f, 0000); dmCheck('an unreadable marker reads as unreadable', $read() === 'unreadable'); chmod($f, 0644); }
@unlink($f); @rmdir($dir);

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: deploy marker diagnostics\n";
