<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * CI/local check: flags calls to project-defined functions that pass fewer
 * arguments than the function's required (non-default) parameter count.
 *
 * This is how the getArtistBio()/getArtistImage() bug across 25 listing
 * pages was found - each was missing a required second argument, which is
 * a fatal ArgumentCountError in PHP 8.x the moment that page renders. Run
 * on every PR (see .github/workflows/ci.yml) so a bug like that never
 * reaches beta again.
 *
 * Usage: php tools/check-arg-counts.php [path]
 * Exit code: 0 if clean, 1 if any mismatch is found.
 */

$root = $argv[1] ?? '.';

$files = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $f) {
    if ($f->getExtension() !== 'php') continue;
    $p = $f->getPathname();
    if (strpos($p, '/vendor/') !== false || strpos($p, '/phpmailer/') !== false) continue;
    $files[] = $p;
}
sort($files);

// Pass 1: collect every function's required (non-default) parameter count,
// from every file in the repo - not just functions.php, so this also
// covers e.g. admin/includes/auth.php.
$defs = []; // name => required count
foreach ($files as $file) {
    $tokens = token_get_all(file_get_contents($file));
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;

        $j = $i + 1;
        while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING) continue; // closure, not a named function
        $name = $tokens[$j][1];
        $j++;
        while ($tokens[$j] !== '(') $j++;

        $depth = 1; $j++;
        $params = [];
        $cur = '';
        while ($depth > 0) {
            $t = $tokens[$j];
            if ($t === '(') { $depth++; $cur .= '('; }
            elseif ($t === ')') { $depth--; if ($depth > 0) $cur .= ')'; }
            elseif ($t === ',' && $depth === 1) { $params[] = $cur; $cur = ''; }
            else { $cur .= is_array($t) ? $t[1] : $t; }
            $j++;
        }
        if (trim($cur) !== '') $params[] = $cur;

        $required = 0;
        foreach ($params as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (strpos($p, '=') === false) $required++;
        }

        // If a name is defined more than once (rare, but possible in a
        // codebase this size), keep the smallest requirement so this stays
        // a "definitely too few args" check, not a false positive machine.
        $defs[$name] = isset($defs[$name]) ? min($defs[$name], $required) : $required;
    }
}

echo "Discovered " . count($defs) . " function definitions across the repo\n";

// Pass 2: check every call site.
$issues = [];
foreach ($files as $file) {
    $toks = token_get_all(file_get_contents($file));
    for ($i = 0; $i < count($toks); $i++) {
        $t = $toks[$i];
        if (!is_array($t) || $t[0] !== T_STRING || !isset($defs[$t[1]])) continue;
        $name = $t[1];

        $prev = $i - 1;
        while ($prev >= 0 && is_array($toks[$prev]) && $toks[$prev][0] === T_WHITESPACE) $prev--;
        if ($prev >= 0 && is_array($toks[$prev])) {
            $prevType = $toks[$prev][0];
            if ($prevType === T_FUNCTION) continue; // the definition itself
            if ($prevType === T_OBJECT_OPERATOR || $prevType === T_DOUBLE_COLON) continue;
        }

        $next = $i + 1;
        while ($next < count($toks) && is_array($toks[$next]) && $toks[$next][0] === T_WHITESPACE) $next++;
        if (!($next < count($toks) && $toks[$next] === '(')) continue;

        $j = $next + 1; $depth = 1; $argCount = 0; $sawContent = false;
        while ($depth > 0 && $j < count($toks)) {
            $tk = $toks[$j];
            if ($tk === '(') { $depth++; $sawContent = true; }
            elseif ($tk === ')') { $depth--; }
            elseif ($tk === ',' && $depth === 1) { $argCount++; $sawContent = false; }
            elseif ($tk === '...' && $depth === 1) { $argCount = PHP_INT_MAX; } // spread - can't count statically
            else {
                if (!(is_array($tk) && $tk[0] === T_WHITESPACE)) $sawContent = true;
            }
            $j++;
        }
        if ($sawContent && $argCount !== PHP_INT_MAX) $argCount++;

        $required = $defs[$name];
        if ($argCount < $required) {
            $line = $t[2];
            $issues[] = sprintf("%s:%d  %s(...) passed %d arg(s), needs %d", $file, $line, $name, $argCount, $required);
        }
    }
}

echo "\n=== Argument-count mismatches (calls with fewer args than required) ===\n";
foreach ($issues as $line) echo $line . "\n";
echo "\nTotal: " . count($issues) . "\n";

exit(count($issues) > 0 ? 1 : 0);
