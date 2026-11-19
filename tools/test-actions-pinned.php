<?php
/**
 * CI: every GitHub Action a workflow uses is pinned to a full 40-character commit SHA, not a tag such as "@v4" that its owner can move
 * (a moved tag runs new code in CI with this repository's token and secrets). Local actions ("./...") and Docker images are exempt.
 * Also checks the retired SFTP deploy workflow stays gone: the server pulls main itself (deploy/pull-deploy.sh).
 * Run: php tools/test-actions-pinned.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';

$fail = 0;
$dir = dirname(__DIR__) . '/.github/workflows';
$files = array_merge(glob($dir . '/*.yml') ?: [], glob($dir . '/*.yaml') ?: []);
if (!$files) { echo "FAIL no workflow files found in $dir\n"; exit(1); }
$seen = 0;
foreach ($files as $f) {
    foreach (preg_split('/\R/', (string) file_get_contents($f)) as $n => $line) {
        if (!preg_match('/^\s*-?\s*uses:\s*["\']?([^\s"\'#]+)/', $line, $m)) continue;
        $ref = $m[1];
        if (strpos($ref, './') === 0 || strpos($ref, 'docker://') === 0) continue;
        $seen++;
        if (!preg_match('/^[\w.-]+\/[\w.\/-]+@[0-9a-f]{40}$/', $ref)) {
            echo 'FAIL ' . basename($f) . ':' . ($n + 1) . " uses $ref (pin it to a full commit SHA, with the version in a comment)\n";
            $fail++;
        }
    }
}
if ($seen === 0) { echo "FAIL no 'uses:' lines found (the check would pass on nothing)\n"; $fail++; }
if (is_file($dir . '/deploy.yml')) { echo "FAIL .github/workflows/deploy.yml is back (the SFTP upload was retired; the server pulls main)\n"; $fail++; }

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: $seen action references, all pinned to a commit SHA\n";
