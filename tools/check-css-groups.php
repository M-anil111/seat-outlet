<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * CI check for the per page type stylesheets (inc/css-groups.php, tools/build-assets.sh):
 *   - every page type, and "full", has stylesheet files, none over 150 KB (SE Ranking flags a CSS file over that size),
 *   - every page type has critical CSS (css/critical/<page type>.css), under 100 KB because it is printed in the page,
 *   - every script in the repo root maps to a page type or deliberately keeps the whole stylesheet (listed below),
 *   - the critical CSS only uses rules that still exist: it starts with the root clip rule and has no licence comments or @charset.
 * Run: php tools/check-css-groups.php
 */
require_once __DIR__ . '/../inc/css-groups.php';
$root = dirname(__DIR__);
$fail = 0;
$bad = function (string $m) use (&$fail) { echo $m . "\n"; $fail++; };

foreach (array_merge(array_keys(SO_CSS_GROUPS), ['full']) as $g) {
    $files = glob($root . '/css/style.' . $g . '.[0-9]*.min.css') ?: [];
    if (is_file($root . '/css/style.' . $g . '.min.css')) $files[] = $root . '/css/style.' . $g . '.min.css';
    if (!$files) { $bad("no stylesheet for page type $g (run tools/build-assets.sh)"); continue; }
    foreach ($files as $f) { if (filesize($f) > 150000) $bad(basename($f) . ' is ' . filesize($f) . ' bytes, over the 150 KB limit'); }
    if ($g === 'full') continue;
    $c = $root . '/css/critical/' . $g . '.css';
    if (!is_file($c)) { $bad("no critical CSS for page type $g (node tools/critical-css.cjs, see docs/css-and-speed.md)"); continue; }
    $t = (string) file_get_contents($c);
    if (strlen($t) > 100000) $bad("critical CSS for $g is " . strlen($t) . ' bytes, over 100 KB');
    if (strpos($t, 'html,body{overflow-x:clip}') !== 0) $bad("critical CSS for $g does not start with the root clip rule");
    if (strpos($t, '@charset') !== false || strpos($t, '/*') !== false) $bad("critical CSS for $g has a comment or @charset");
}

// Scripts that keep the whole stylesheet on purpose: not pages people land on, redirects, or tiny partners' pages.
$full = ['404.php', 'austin-sign-masters.php', 'dotbooker.php', 'footer.php', 'functions.php', 'grab-tickets-now.php', 'header.php', 'it-sprinkles.php',
    'mindshare-consulting.php', 'newsletter-email.php', 'robots.php', 'salespeep.php', 'signs-n-more.php', 'sitemap-style.php', 'sports.php', 'theater.php',
    'ticketing-truths.php', 'tickets.php', 'viralpep.php', 'wingcms.php'];
foreach (glob($root . '/*.php') as $p) {
    $b = basename($p);
    if (soCssGroupFor($b) === null && !in_array($b, $full, true)) $bad("$b belongs to no page type: add it to inc/css-groups.php, or to the list in tools/check-css-groups.php if it should keep the whole stylesheet");
}
echo $fail ? "$fail problem(s)\n" : "stylesheets and critical CSS ok\n";
exit($fail ? 1 : 0);
