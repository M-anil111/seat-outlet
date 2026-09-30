<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * CI/local check: flags calls to functions that are neither PHP builtins
 * nor defined anywhere in this repo.
 *
 * This is how the getTnPerformerEventsCount() / getArtistBioFromWikipedia()
 * / getArtistImageFromWikimedia() bugs were found - calls to functions that
 * simply don't exist anywhere, which is a fatal error the moment that code
 * path runs. Run on every PR (see .github/workflows/ci.yml) so a bug like
 * that never reaches beta again.
 *
 * Usage: php tools/check-undefined-functions.php [path]
 * Exit code: 0 if clean, 1 if any real (non-allowlisted) issue is found.
 */

$root = $argv[1] ?? '.';

// Functions this app calls only when function_exists() confirms they're
// available (optional PHP extensions - APCu isn't installed everywhere,
// including the environment these checks often run in). Real usage sites
// are already guarded; flagging them here would just be permanent noise.
$allowlist = [
    'fastcgi_finish_request', // PHP-FPM SAPI only; always called behind function_exists()
    'apcu_fetch', 'apcu_store', 'apcu_delete', 'apcu_exists', 'apcu_clear_cache',
];

$internal = get_defined_functions()['internal'];
$internalSet = array_flip(array_map('strtolower', $internal));
$allowlistSet = array_flip(array_map('strtolower', $allowlist));

$files = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
    if ($f->getExtension() !== 'php') continue;
    $p = $f->getPathname();
    if (strpos($p, '/vendor/') !== false || strpos($p, '/phpmailer/') !== false) continue;
    $files[] = $p;
}
sort($files);

$defined = [];
foreach ($files as $file) {
    $code = file_get_contents($file);
    if (preg_match_all('/function\s+&?\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $code, $m)) {
        foreach ($m[1] as $n) $defined[strtolower($n)] = true;
    }
}

$calls = [];
foreach ($files as $file) {
    $toks = token_get_all(file_get_contents($file));
    for ($i = 0; $i < count($toks); $i++) {
        $t = $toks[$i];
        if (!is_array($t) || $t[0] !== T_STRING) continue;

        $name = $t[1];
        $lname = strtolower($name);

        $prev = $i - 1;
        while ($prev >= 0 && is_array($toks[$prev]) && $toks[$prev][0] === T_WHITESPACE) $prev--;
        if ($prev >= 0 && is_array($toks[$prev])) {
            $prevType = $toks[$prev][0];
            if ($prevType === T_FUNCTION) continue; // this IS the definition
            if ($prevType === T_OBJECT_OPERATOR || $prevType === T_DOUBLE_COLON || $prevType === T_NEW) continue;
            if (defined('T_NULLSAFE_OBJECT_OPERATOR') && $prevType === T_NULLSAFE_OBJECT_OPERATOR) continue;
        }

        $next = $i + 1;
        while ($next < count($toks) && is_array($toks[$next]) && $toks[$next][0] === T_WHITESPACE) $next++;
        if (!($next < count($toks) && $toks[$next] === '(')) continue;

        if (isset($internalSet[$lname]) || isset($defined[$lname]) || isset($allowlistSet[$lname])) continue;

        $line = $t[2];
        $calls[] = "$file:$line  $name(...)";
    }
}

$calls = array_values(array_unique($calls));
echo "=== Calls to functions not defined anywhere in repo and not PHP builtins ===\n";
foreach ($calls as $c) echo $c . "\n";
echo "\nTotal unique: " . count($calls) . "\n";

exit(count($calls) > 0 ? 1 : 0);
