<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Regression checks for the local penetration test findings that are fixed in code: the rate limiter trusts a client-IP header only
 * from a trusted proxy, the rate-limit counter works without the beta home path, admin self-registration needs an explicit switch,
 * the retired email-check endpoint is gone, and the server does not announce PHP. Run: php tools/test-pen-retest.php
 */
$root = dirname(__DIR__);
$fail = 0;
function prCheck(string $what, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $what . "\n"; if (!$ok) $fail++; }
function prSrc(string $rel): string { global $root; return (string) file_get_contents($root . '/' . $rel); }

// F5: a client IP header from an untrusted peer is ignored.
$crawl = prSrc('inc/crawl-guard.php');
prCheck('rate limit: the client IP comes from the trusted-proxy helper, not from any CF-Connecting-IP header (F5)', strpos($crawl, 'soClientIp()') !== false && strpos($crawl, "'HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP'") === false);

// F6: the counter directory falls back to the app root when the configured home path is missing.
prCheck('rate limit: a missing HOME_PATH falls back to the app directory, so the counter still works (F6)', strpos($crawl, 'is_dir(HOME_PATH)') !== false || strpos($crawl, "is_dir(\$home)") !== false);

// F2: admin self-registration is closed unless the owner opts in.
$auth = prSrc('admin/includes/auth.php');
prCheck('admin: registration while no admin exists needs ADMIN_REGISTRATION_OPEN=1 (F2)', strpos($auth, "ADMIN_REGISTRATION_OPEN") !== false);

// F13: the retired email-check endpoint is removed.
prCheck('ajax/check-email.php is removed (F13)', !file_exists($root . '/ajax/check-email.php'));

// F14: PHP does not announce its version.
prCheck('X-Powered-By is removed on public pages (F14)', strpos(prSrc('functions.php'), "header_remove('X-Powered-By')") !== false);

echo $fail ? "\npen retest: $fail failed\n" : "\npen retest: all passed\n";
exit($fail ? 1 : 0);
