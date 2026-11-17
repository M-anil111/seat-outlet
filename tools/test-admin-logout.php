<?php
/**
 * CI: signing out of the admin needs a POST with the CSRF token (a GET only redirects), the header sends one, and an upload failure does not
 * show the raw exception text. Audit 16 Nov 2026. Offline source checks. Run: php tools/test-admin-logout.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function alCheck(string $name, bool $ok): void { global $fail; if (!$ok) { echo "FAIL $name\n"; $fail++; } }
$root = dirname(__DIR__);
$lo = (string) file_get_contents($root . '/admin/logout.php');
alCheck('logout requires POST', strpos($lo, "REQUEST_METHOD'] ?? 'GET') !== 'POST'") !== false);
alCheck('logout verifies the CSRF token', strpos($lo, 'admin_csrf_verify($_POST[\'csrf_token\'] ?? null)') !== false);
alCheck('the redirect for a GET comes before the session is destroyed', strpos($lo, "header('Location: ' . (admin_is_logged_in()") < strpos($lo, 'session_destroy()'));
$hd = (string) file_get_contents($root . '/admin/includes/app-header.php');
alCheck('the header signs out with a POST form carrying the token', preg_match('#<form method="post" action="logout"[^>]*>\s*<input type="hidden" name="csrf_token" value="<\?php echo htmlspecialchars\(admin_csrf_token\(\)#', $hd) === 1);
alCheck('the header no longer has a plain sign-out link', strpos($hd, '<a href="logout"') === false);
$img = (string) file_get_contents($root . '/admin/images-form.php');
alCheck('an upload failure is logged, not shown with the exception text', strpos($img, "'Upload to storage failed: ' . \$e->getMessage()") === false && strpos($img, "error_log('admin image upload to storage failed") !== false);
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: admin sign-out needs a POST\n";
