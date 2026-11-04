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
dmCheck('the cron copy of the script refreshes itself from the checkout', strpos($sh, 'cmp -s "$WORKDIR/deploy/pull-deploy.sh" "$SELF"') !== false && strpos($sh, 'mv -f "$SELF.new" "$SELF"') !== false);
dmCheck('the self-update happens after the checkout moved and before the code copy', strpos($sh, 'git reset --quiet --hard') < strpos($sh, 'cmp -s "$WORKDIR/deploy/pull-deploy.sh"') && strpos($sh, 'cmp -s "$WORKDIR/deploy/pull-deploy.sh"') < strpos($sh, 'tar --exclude=./.git'));
dmCheck('the self-update never replaces the checkout\'s own file', strpos($sh, '"$SELF" != "$(readlink -f "$WORKDIR/deploy/pull-deploy.sh")"') !== false);
// Hardening for the github.sh -> pull-deploy.sh migration (4 Nov 2026).
dmCheck('deploy refuses a checkout that is the web root or inside it', strpos($sh, 'REFUSED: DEPLOY_WORKDIR') !== false && strpos($sh, 'REFUSED: DEPLOY_WEBROOT') !== false);
dmCheck('the path guard runs before anything is touched', strpos($sh, 'REFUSED: DEPLOY_WORKDIR') < strpos($sh, 'mkdir -p "$STATE_DIR" "$WEBROOT"'));
dmCheck('git fetch is retried before giving up', strpos($sh, 'for wait in 0 5 15; do') !== false && strpos($sh, 'git fetch failed three times') !== false);
dmCheck('a failed fetch ends the run before the web root is touched', strpos($sh, 'git fetch failed three times') < strpos($sh, 'git reset --quiet --hard'));
dmCheck('cron uses a fixed key without an agent or a prompt when DEPLOY_SSH_KEY is set', strpos($sh, 'GIT_SSH_COMMAND="ssh -i $DEPLOY_SSH_KEY -o IdentitiesOnly=yes -o BatchMode=yes') !== false);
dmCheck('DEPLOY_REF deploys a chosen commit for an emergency rollback', strpos($sh, 'DEPLOY_REF set: deploying') !== false && strpos($sh, 'git reset --quiet --hard "$NEW"') !== false);
dmCheck('deletions are computed against the last copied commit as well as the marker', strpos($sh, 'PREV="$(git rev-parse HEAD') !== false && strpos($sh, 'for base in "$OLD" "$PREV"; do') !== false);
dmCheck('the server config file never overrides the environment', strpos($sh, '[ -z "${!k:-}" ] && export "$k=$v"') !== false);
dmCheck('the log names the web root it deploys into', strpos($sh, 'into $WEBROOT"') !== false);
dmCheck('a config example ships with the script', is_file($root . '/deploy/pull-deploy.conf.example') && strpos((string) file_get_contents($root . '/deploy/pull-deploy.conf.example'), 'DEPLOY_WEBROOT=') !== false);

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
