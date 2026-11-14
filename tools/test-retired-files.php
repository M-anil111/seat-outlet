<?php
/**
 * CI: deploy/retired-files.txt never deletes a file the repository still ships.
 *
 * The pull deploy copies the tree, then deletes every path in deploy/retired-files.txt. The CSS split was once two files per page type, so
 * css/style.home.3.min.css and css/style.content.3.min.css were listed as retired; later builds made a third part again, and every deploy
 * copied it and then deleted it. Production home and content pages loaded two of their three stylesheets: the compact header rules were
 * missing, so the top bar grew when the full stylesheet arrived (SE Ranking audit, 12 Nov 2026: CLS 0.307 on the home page).
 * Run: php tools/test-retired-files.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';

$fail = 0;
function rfCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

$root = dirname(__DIR__);
$list = (string) file_get_contents($root . '/deploy/retired-files.txt');
$tracked = [];
exec('git -C ' . escapeshellarg($root) . ' ls-files 2>/dev/null', $tracked, $rc);
rfCheck('git ls-files works', $rc === 0 && count($tracked) > 100);
$tracked = array_flip($tracked);
foreach (preg_split('/\R/', $list) as $line) {
    $f = trim($line);
    if ($f === '' || $f[0] === '#') continue;
    rfCheck("retired path is not a file the repository ships: $f", !isset($tracked[$f]));
}
// Every stylesheet a page loads is shipped and not retired (soCssBundleFiles() globs css/style.<group>.N.min.css on the server).
foreach (glob($root . '/css/style.*.min.css') ?: [] as $css) {
    $rel = 'css/' . basename($css);
    rfCheck("stylesheet part is not retired: $rel", strpos($list, $rel) === false);
}
// Defence in depth: the deploy never deletes a retired path that exists in the commit it deploys.
$deploy = (string) file_get_contents($root . '/deploy/pull-deploy.sh');
rfCheck('deploy skips a retired path that the deployed commit contains', strpos($deploy, 'git cat-file -e "$NEW:$f"') !== false);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: retired files never delete shipped files\n";
