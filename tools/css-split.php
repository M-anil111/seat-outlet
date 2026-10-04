<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Writes, for each page type in inc/css-groups.php, one text file holding everything that page type can put on the screen:
 * its templates, the PHP functions those templates (transitively) call, the files they include, the scripts the footer loads
 * for it, the endpoints whose markup is inserted into it, and the HTML stored in the database seeds where that applies.
 * tools/build-assets.sh feeds each file to PurgeCSS, which keeps only the rules whose selectors can match that text.
 *
 *   php tools/css-split.php <output dir>
 *
 * Over-including is always safe (a rule stays in the file); under-including would hide a style, so the function scan is
 * deliberately loose: every word in the text that is the name of a function we define pulls that function in, whether or not it
 * is really called. Page code that sits outside any function (constants, markup in includes) is part of the file it lives in.
 */
require_once __DIR__ . '/../inc/css-groups.php';

$out = $argv[1] ?? '';
if ($out === '') { fwrite(STDERR, "Usage: php tools/css-split.php <output dir>\n"); exit(1); }
if (!is_dir($out) && !mkdir($out, 0777, true)) { fwrite(STDERR, "Cannot create $out\n"); exit(1); }
$root = dirname(__DIR__);

/** Every PHP file that can define functions or hold markup, relative to the root. */
$files = array_merge(glob($root . '/*.php'), glob($root . '/inc/*.php'), glob($root . '/inc/seo-copy/*.php'));
$text = [];
foreach ($files as $f) { $text[substr($f, strlen($root) + 1)] = (string) file_get_contents($f); }

/** Split each file into its functions (name => source) and the rest (top level code and markup). */
$funcs = [];      // name => source
$funcFile = [];   // name => file
$residue = [];    // file => text outside functions
foreach ($text as $rel => $src) {
    $tokens = token_get_all($src);
    $n = count($tokens);
    $rest = '';
    $i = 0;
    while ($i < $n) {
        $t = $tokens[$i];
        if (is_array($t) && $t[0] === T_FUNCTION) {
            // a named function declaration (not a closure): function name (
            $j = $i + 1;
            while ($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT], true)) $j++;
            if ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $name = $tokens[$j][1];
                $k = $j; $depth = 0; $started = false; $body = '';
                for ($q = $i; $q < $n; $q++) {
                    $tk = $tokens[$q];
                    $str = is_array($tk) ? $tk[1] : $tk;
                    if (!(is_array($tk) && in_array($tk[0], [T_COMMENT, T_DOC_COMMENT], true))) $body .= $str;
                    if ($tk === '{' || (is_array($tk) && in_array($tk[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) { $depth++; $started = true; }
                    elseif ($tk === '}') { $depth--; if ($started && $depth === 0) { $k = $q; break; } }
                    $k = $q;
                }
                $funcs[$name] = ($funcs[$name] ?? '') . "\n" . $body;
                $funcFile[$name] = $rel;
                $i = $k + 1;
                continue;
            }
        }
        if (!(is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true))) $rest .= is_array($t) ? $t[1] : $t;
        $i++;
    }
    $residue[$rel] = $rest;
}

/** Basenames of files a text may include: "inc/menu.php", "menu.php". */
$byBase = [];
foreach ($text as $rel => $_) { $byBase[basename($rel)][] = $rel; }

/** Files matching one of the group's template patterns. */
function so_matching(array $patterns, array $rels): array {
    $r = [];
    foreach ($rels as $rel) {
        if (strpos($rel, '/') !== false) continue;
        foreach ($patterns as $p) { if (fnmatch($p, $rel)) { $r[] = $rel; break; } }
    }
    return $r;
}

$pageFiles = array_keys($text);
$groupsNames = array_keys(SO_CSS_GROUPS);
$seoCopy = array_filter($pageFiles, fn($r) => strpos($r, 'inc/seo-copy/') === 0);

foreach (SO_CSS_GROUPS as $group => $cfg) {
    // entry points: the group's templates, the page frame, and the includes the frame always loads
    $entry = array_merge(so_matching($cfg['templates'], $pageFiles), ['header.php', 'footer.php', 'functions.php']);
    if (!empty($cfg['seo_copy'])) $entry = array_merge($entry, $seoCopy);
    $fileSet = array_fill_keys($entry, true);
    $funcSet = [];
    $queue = $entry;
    $body = '';
    $mark = []; $why = [];
    // functions.php top level code (constants, arrays with class names) and the other includes' top level code are read as their files are pulled in
    while ($queue) {
        $rel = array_shift($queue);
        $chunk = $residue[$rel] ?? '';
        $body .= "\n" . $chunk;
        // included files: any quoted name that is a file we know
        if (preg_match_all('#[\'"]((?:[\w./-]*/)?[\w.-]+\.php)[\'"]#', $chunk, $m)) {
            foreach ($m[1] as $ref) {
                $base = basename($ref);
                foreach ($byBase[$base] ?? [] as $cand) {
                    if (!isset($fileSet[$cand]) && (strpos($cand, 'inc/') === 0 || strpos($cand, '/') === false) && strpos($cand, 'inc/seo-copy/') !== 0) { $fileSet[$cand] = true; $queue[] = $cand; }
                }
            }
        }
        // functions: every word that names a function we define
        if (preg_match_all('/[A-Za-z_]\w*/', $chunk, $words)) {
            foreach (array_unique($words[0]) as $w) {
                if (isset($funcs[$w]) && !isset($funcSet[$w])) {
                    $funcSet[$w] = true; $why[$w] = $rel;
                    $fileOfFunc = $funcFile[$w];
                    // the function lives in a file (functions.php or an inc file): its own include of other files is followed through the text below
                    $queue[] = '@func:' . $w;
                }
            }
        }
        if (strpos($rel, '@func:') === 0) { /* handled below */ }
    }
    $mark['entry+includes'] = strlen($body);
    // second pass for functions: queue items "@func:name" were skipped above because they are not in $residue; resolve them now
    $pending = array_keys($funcSet);
    $done = [];
    while ($pending) {
        $name = array_shift($pending);
        if (isset($done[$name])) continue;
        $done[$name] = true;
        $src = $funcs[$name];
        $body .= "\n" . $src;
        if (preg_match_all('/[A-Za-z_]\w*/', $src, $words)) {
            foreach (array_unique($words[0]) as $w) { if (isset($funcs[$w]) && !isset($done[$w])) { $pending[] = $w; $why[$w] ??= $name; } }
        }
        // a function that includes a file (include 'inc/x.php'): pull the file's text and its functions in
        if (preg_match_all('#[\'"]((?:[\w./-]*/)?[\w.-]+\.php)[\'"]#', $src, $m)) {
            foreach ($m[1] as $ref) {
                foreach ($byBase[basename($ref)] ?? [] as $cand) {
                    if (!isset($fileSet[$cand]) && strpos($cand, 'inc/seo-copy/') !== 0) {
                        $fileSet[$cand] = true;
                        $body .= "\n" . ($residue[$cand] ?? '');
                        if (preg_match_all('/[A-Za-z_]\w*/', $residue[$cand] ?? '', $w2)) {
                            foreach (array_unique($w2[0]) as $w) { if (isset($funcs[$w]) && !isset($done[$w])) $pending[] = $w; }
                        }
                    }
                }
            }
        }
        // soSeoCopy('name'): the copy file of that name
        if (preg_match_all('/soSeoCopy\(\s*[\'"]([\w-]+)[\'"]/', $src, $m)) {
            foreach ($m[1] as $nm) { $f = 'inc/seo-copy/' . $nm . '.php'; if (isset($text[$f]) && !isset($fileSet[$f])) { $fileSet[$f] = true; $body .= "\n" . $text[$f]; } }
        }
    }
    $mark['functions'] = strlen($body);
    // soSeoCopy('name') in templates and includes
    foreach (array_keys($fileSet) as $rel) {
        if (preg_match_all('/soSeoCopy\(\s*[\'"]([\w-]+)[\'"]/', $residue[$rel] ?? '', $m)) {
            foreach ($m[1] as $nm) { $f = 'inc/seo-copy/' . $nm . '.php'; if (isset($text[$f]) && !isset($fileSet[$f])) { $fileSet[$f] = true; $body .= "\n" . $text[$f]; } }
        }
    }
    $mark['seo_copy'] = strlen($body);
    // scripts and endpoints
    foreach (array_unique(array_merge(SO_CSS_SHARED_JS, $cfg['js'] ?? [])) as $js) {
        $p = $root . '/js/' . $js . '.js';
        if (is_file($p)) $body .= "\n" . file_get_contents($p);
    }
    foreach (array_unique(array_merge(SO_CSS_SHARED_AJAX, $cfg['ajax'] ?? [])) as $ajax) {
        $p = $root . '/ajax/' . $ajax . '.php';
        if (is_file($p)) {
            $src = (string) file_get_contents($p);
            $body .= "\n" . $src;
            // the functions an endpoint calls
            if (preg_match_all('/[A-Za-z_]\w*/', $src, $words)) {
                $pending = [];
                foreach (array_unique($words[0]) as $w) { if (isset($funcs[$w])) $pending[] = $w; }
                while ($pending) {
                    $name = array_shift($pending);
                    if (isset($done[$name])) continue;
                    $done[$name] = true;
                    $body .= "\n" . $funcs[$name];
                    if (preg_match_all('/[A-Za-z_]\w*/', $funcs[$name], $w3)) foreach (array_unique($w3[0]) as $w) { if (isset($funcs[$w]) && !isset($done[$w])) $pending[] = $w; }
                }
            }
        }
    }
    $mark['js+ajax'] = strlen($body);
    if (!empty($cfg['sql'])) {
        foreach (array_merge(glob($root . '/db/migrations/*.sql'), glob($root . '/db/seeds/*.sql')) as $sql) { $body .= "\n" . file_get_contents($sql); }
    }
    $mark['all'] = strlen($body);
    if (getenv('CSS_SPLIT_DEBUG') === 'why' && $group === (getenv('CSS_SPLIT_GROUP') ?: 'checkout')) { $big = []; foreach ($done as $nm => $_) { $big[$nm] = strlen($funcs[$nm]); } arsort($big); foreach (array_slice($big, 0, 30, true) as $nm => $sz) { fwrite(STDERR, sprintf("   %6d %-34s <- %s\n", $sz, $nm, $why[$nm] ?? '?')); } }
    if (getenv('CSS_SPLIT_DEBUG')) fwrite(STDERR, '  ' . json_encode($mark) . "\n");
    file_put_contents($out . '/' . $group . '.txt', $body);
    fwrite(STDERR, sprintf("%-10s %4d files, %4d functions, %7d bytes\n", $group, count($fileSet), count($done), strlen($body)));
}
