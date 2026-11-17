<?php
/**
 * CI: a blog article gets the "blog" page type (stylesheet and critical CSS) whatever its slug looks like. On the live server SCRIPT_NAME
 * follows the slug, so /blog/ticket-price-tracker got "content", /blog/concert-tickets-no-fees got "browse", and the article hero jumped
 * (CLS 0.49 mobile, Lighthouse 16 Nov 2026). Run: php tools/test-css-group-blog.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/css-groups.php';
$fail = 0;
function cgCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

// Without the blog marker, SCRIPT_NAME decides, and a slug-shaped name lands in other groups (the live bug).
$_SERVER['SCRIPT_NAME'] = '/blog/ticket-price-tracker.php';
cgCheck('premise: a ticket-* slug would be "content" by name', soCssGroupName() === 'content', soCssGroupName());
$_SERVER['SCRIPT_NAME'] = '/blog/concert-tickets-no-fees.php';
cgCheck('premise: a concert* slug would be "browse" by name', soCssGroupName() === 'browse', soCssGroupName());
$_SERVER['SCRIPT_NAME'] = '/event.php';
cgCheck('an ordinary page still uses its own script', soCssGroupName() === 'event', soCssGroupName());

// The real blog entry point sets SO_BLOG_ENTRY: run in a fresh process for each slug-shaped SCRIPT_NAME.
$root = dirname(__DIR__);
foreach (['/blog/ticket-price-tracker.php', '/blog/concert-tickets-no-fees.php', '/blog/where-to-buy-cheap-concert-tickets.php', '/blog.php', '/blog'] as $name) {
    $code = 'define("SO_BLOG_ENTRY", true); $_SERVER["SCRIPT_NAME"] = ' . var_export($name, true) . '; require ' . var_export($root . '/inc/css-groups.php', true) . '; echo soCssGroupName(), "|", implode(",", soCssBundleFiles()), "|", strlen(soCriticalCss()) > 0 ? "critical" : "none";';
    $out = trim((string) shell_exec('php -r ' . escapeshellarg($code) . ' 2>&1'));
    cgCheck("blog entry with SCRIPT_NAME $name: blog group, blog stylesheets, blog critical CSS", (bool) preg_match('/^blog\|css\/style\.blog\.[0-9]+\.min\.css(,css\/style\.blog\.[0-9]+\.min\.css)*\|critical$/', $out), $out);
}
$src = (string) file_get_contents($root . '/blog.php');
cgCheck('blog.php still defines SO_BLOG_ENTRY before it loads anything else', preg_match("/^define\('SO_BLOG_ENTRY', true\);/m", $src) === 1 && strpos($src, "define('SO_BLOG_ENTRY'") < strpos($src, "require_once __DIR__ . '/functions.php'"));
cgCheck('no other code reads SCRIPT_NAME to pick the page type', strpos((string) file_get_contents($root . '/header.php'), "soCssGroupFor(") === false);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: blog articles get the blog page type\n";
