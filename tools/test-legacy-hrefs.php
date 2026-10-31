<?php
/**
 * CI: no page links to an address that only exists as a 301 alias (soGuessRedirect() in functions.php, e.g. /terms-of-use). Such a link
 * works for a visitor but is a redirect on every page that carries it (SE Ranking: "internal links to 3XX"). Run: php tools/test-legacy-hrefs.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';

$root = dirname(__DIR__);
$fn = (string) file_get_contents($root . '/functions.php');
if (!preg_match('/\$aliases = \[(.*?)\];/s', $fn, $m)) { echo "FAIL: alias map not found in functions.php\n"; exit(1); }
preg_match_all("/'(\\/[a-z0-9\\/-]+)'\\s*=>/", $m[1], $keys);
$legacy = array_values(array_unique($keys[1]));
if (count($legacy) < 3) { echo "FAIL: alias map looks wrong (" . count($legacy) . " keys)\n"; exit(1); }

$fail = 0;
$skip = ['vendor', 'tools', 'node_modules', '.git', 'phpmailer'];
$it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    fn($f) => !in_array($f->getFilename(), $skip, true)));
foreach ($it as $f) {
    if (substr($f->getFilename(), -4) !== '.php') continue;
    $src = (string) file_get_contents($f->getPathname());
    foreach ($legacy as $k) {
        if (preg_match('/href=["\']' . preg_quote($k, '/') . '["\'\\/?#]/', $src)) {
            echo 'FAIL ' . substr($f->getPathname(), strlen($root) + 1) . " links to the 301 alias $k\n"; $fail++;
        }
    }
}
if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo 'ok: no page links to a 301 alias (' . count($legacy) . " aliases checked)\n";
