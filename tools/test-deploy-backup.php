<?php
/**
 * CI: the pull deploy dumps the database before it applies pending migrations, only then, and a failed dump either warns (default) or
 * stops the migration (DEPLOY_REQUIRE_BACKUP=1). Audit 16 Nov 2026: a migration ran with no backup behind it.
 * Runs the real deploy/pull-deploy.sh against a throwaway git repo and web root with a stand-in "php" that records what it is asked to run.
 * Run: php tools/test-deploy-backup.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function dbCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

$root = dirname(__DIR__);
$sh = (string) file_get_contents($root . '/deploy/pull-deploy.sh');
dbCheck('the dump runs before the migrations', strpos($sh, 'cron/backup-db.php') < strpos($sh, 'echo "applying database migrations"') || strpos($sh, 'cron/backup-db.php') < strpos($sh, 'log "applying database migrations"'));
dbCheck('repo-only documents are not copied to the web root', strpos($sh, '--exclude=./README.md --exclude=./CLAUDE.md') !== false && strpos($sh, '--exclude=./CONTRIBUTING.md') !== false && strpos($sh, '--exclude=./docs') !== false);
dbCheck('the deploy logs the one-line rollback command', strpos($sh, 'DEPLOY_REF=$OLD $0') !== false);

$tmp = sys_get_temp_dir() . '/so-deploy-bk-' . getmypid();
$sh_ = fn(string $c) => shell_exec($c . ' 2>&1');
@mkdir($tmp, 0700, true);
// A tiny repository with the files the script looks for.
$repo = "$tmp/repo";
mkdir("$repo/db", 0700, true); mkdir("$repo/cron", 0700, true); mkdir("$repo/inc", 0700, true);
file_put_contents("$repo/db/migrate.php", "<?php\n"); file_put_contents("$repo/cron/backup-db.php", "<?php\n"); file_put_contents("$repo/index.php", "<?php\n");
$sh_("cd $repo && git init -q -b main && git -c user.email=t@example.test -c user.name=t add -A && git -c user.email=t@example.test -c user.name=t commit -q -m one");
$sh_("git init -q --bare -b main $tmp/remote.git && cd $repo && git push -q $tmp/remote.git main");
// The stand-in php: logs its arguments; answers --status from FAKE_STATUS; the dump exits FAKE_BACKUP_RC.
file_put_contents("$tmp/fakephp", "#!/usr/bin/env bash\necho \"\$*\" >> \"\$FAKE_LOG\"\ncase \"\$*\" in\n *--status*) echo \"[\$FAKE_STATUS] 0001_x.sql\" ;;\n *backup-db*) exit \"\${FAKE_BACKUP_RC:-0}\" ;;\nesac\nexit 0\n");
chmod("$tmp/fakephp", 0755);

$run = function (string $label, array $env) use ($root, $tmp, $sh_) {
    $web = "$tmp/web-$label"; $work = "$tmp/work-$label"; $log = "$tmp/log-$label.txt";
    @mkdir("$web/inc", 0700, true); file_put_contents("$web/inc/env.local.php", "<?php\n"); @file_put_contents($log, '');
    $e = '';
    foreach (array_merge(['DEPLOY_REPO' => "$tmp/remote.git", 'DEPLOY_BRANCH' => 'main', 'DEPLOY_WEBROOT' => $web, 'DEPLOY_WORKDIR' => $work, 'DEPLOY_PHP' => "$tmp/fakephp", 'FAKE_LOG' => $log], $env) as $k => $v) $e .= $k . '=' . escapeshellarg($v) . ' ';
    $out = (string) shell_exec("cd $tmp && $e bash " . escapeshellarg($root . '/deploy/pull-deploy.sh') . ' 2>&1; echo "rc=$?"');
    $calls = array_filter(explode("\n", (string) @file_get_contents($log)));
    $rc = preg_match('/rc=(\d+)\s*$/', $out, $m) ? (int) $m[1] : -1;
    return ['rc' => $rc, 'calls' => array_values($calls), 'web' => $web, 'out' => $out];
};
$idx = fn(array $calls, string $needle) => (function () use ($calls, $needle) { foreach ($calls as $i => $c) if (strpos($c, $needle) !== false && strpos($c, '--status') === false) return $i; return -1; })();

$a = $run('pending', ['FAKE_STATUS' => 'pending']);
dbCheck('pending migration: the deploy succeeds', $a['rc'] === 0, $a['out']);
dbCheck('pending migration: the dump ran, before the migrations', $idx($a['calls'], 'backup-db') >= 0 && $idx($a['calls'], 'backup-db') < $idx($a['calls'], 'db/migrate.php'), json_encode($a['calls']));
dbCheck('pending migration: the commit marker is written', is_file($a['web'] . '/.deployed-commit'));

$b = $run('applied', ['FAKE_STATUS' => 'applied']);
dbCheck('nothing pending: no dump is made', $idx($b['calls'], 'backup-db') === -1 && $idx($b['calls'], 'db/migrate.php') >= 0, json_encode($b['calls']));

$c = $run('off', ['FAKE_STATUS' => 'pending', 'DEPLOY_BACKUP' => '0']);
dbCheck('DEPLOY_BACKUP=0: no dump, migrations still run', $idx($c['calls'], 'backup-db') === -1 && $idx($c['calls'], 'db/migrate.php') >= 0, json_encode($c['calls']));

$d = $run('warn', ['FAKE_STATUS' => 'pending', 'FAKE_BACKUP_RC' => '1']);
dbCheck('a failed dump only warns by default: migrations still run, deploy succeeds', $d['rc'] === 0 && $idx($d['calls'], 'db/migrate.php') >= 0 && strpos($d['out'], 'WARNING: the pre-migration database dump FAILED') !== false, $d['out']);

$e = $run('strict', ['FAKE_STATUS' => 'pending', 'FAKE_BACKUP_RC' => '1', 'DEPLOY_REQUIRE_BACKUP' => '1']);
dbCheck('DEPLOY_REQUIRE_BACKUP=1 and a failed dump: migrations are NOT run', $idx($e['calls'], 'db/migrate.php') === -1 && $e['rc'] === 1, json_encode($e['calls']) . ' rc=' . $e['rc']);
dbCheck('...the marker is not written, so the next run retries', !is_file($e['web'] . '/.deployed-commit'));
dbCheck('...and .deploy-error says why (health page shows it to admins)', strpos((string) @file_get_contents($e['web'] . '/.deploy-error'), 'database backup failed') !== false);

$sh_("rm -rf " . escapeshellarg($tmp));
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: pre-migration backup\n";
