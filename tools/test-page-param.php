<?php
// Offline test (no network, no database): ?page=N must stay on the pages that paginate (the blog and every A to Z directory)
// and be dropped everywhere else. Run: php tools/test-page-param.php
$root = dirname(__DIR__);
$src = file_get_contents($root . '/functions.php');
preg_match("/function soRedirectCanonicalForm\(.*?\n}\n/s", $src, $m);
if (!$m) { fwrite(STDERR, "FAIL: soRedirectCanonicalForm not found\n"); exit(1); }
define('SO_PUBLIC_ORIGIN', 'https://seatoutlet.com');
$f = str_replace('__DIR__', var_export($root, true), $m[0]);
$f = str_replace('function soRedirectCanonicalForm', 'function soPageParamProbe', $f);
$f = str_replace("if (PHP_SAPI === 'cli' || headers_sent()) return;", '', $f);
$f = preg_replace("/\n    http_response_code\(301\);\n    header\([^\n]*\n    header\([^\n]*\n    exit;\n}/",
    "\n    return 'REDIRECT ' . (\$wrongHost ? SO_PUBLIC_ORIGIN : '') . \$clean . (\$qs !== '' ? '?' . \$qs : '');\n}", $f);
$f = str_replace('if (!$wrongHost && $clean === $path && !$dropPage) return;', 'if (!$wrongHost && $clean === $path && !$dropPage) return "stay";', $f);
eval($f);

$fails = 0;
function pageParamCheck(string $uri, string $want): void {
    global $fails;
    $_SERVER['REQUEST_URI'] = $uri; $_SERVER['HTTP_HOST'] = 'seatoutlet.com'; $_SERVER['REQUEST_METHOD'] = 'GET';
    $got = soPageParamProbe();
    echo ($got === $want ? 'ok   ' : 'FAIL ') . "$uri => $got\n";
    if ($got !== $want) $fails++;
}
foreach (['/blog?page=2', '/all-artists-and-teams?page=5', '/concert-artists?page=2', '/broadway-shows?page=44', '/sports-teams?page=3',
          '/nfl-teams?page=2', '/nba-teams?page=2', '/comedians-on-tour?page=2', '/music-festivals-list?page=9'] as $u) pageParamCheck($u, 'stay');
pageParamCheck('/category/dance?page=2', 'REDIRECT /category/dance');
pageParamCheck('/event/x?page=2&a=1', 'REDIRECT /event/x?a=1');
echo $fails ? "$fails failed\n" : "page param: all passed\n";
exit($fails ? 1 : 0);
