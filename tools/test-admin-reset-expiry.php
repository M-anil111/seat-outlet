<?php
/**
 * Admin password reset links must open while they are valid, whatever PHP's timezone. 24-25 Nov 2026: forgot-password wrote expires_at
 * with PHP's date() in SITE_TIMEZONE (America/Los_Angeles) and admin_find_valid_reset() compared it with the database's NOW() (UTC), so
 * every link was stored about 7.5 hours in the past and opened as expired. The expiry is now set by the database clock.
 * Also: admin sessions live in a folder per system user, so beta and live (two users on one server) never share or block one folder.
 * Needs the CI database (DB_* variables). Run: php tools/test-admin-reset-expiry.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
foreach (['CONSUMER_KEY', 'CONSUMER_SECRET', 'GAPI_KEY', 'AWS_ACCESS_KEY', 'AWS_SECRET_KEY', 'RECAPTCHA_SECRET_KEY'] as $k) { if ((string) getenv($k) === '') putenv($k . '=test-placeholder'); }
require_once $root . '/admin/includes/auth.php';   // db/config.php, constants (sets the PHP timezone), admin_find_valid_reset(); before any output (it starts a session)
function arCheck(string $name, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fail++; }

// 1. Source: the insert uses the database clock, and no PHP-clock expiry is left.
$fp = (string) file_get_contents($root . '/admin/forgot-password.php');
preg_match("/'INSERT INTO admin_password_resets[^']*'/", $fp, $m);
$insertSql = isset($m[0]) ? trim($m[0], "'") : '';
arCheck('forgot-password sets expires_at with the database clock', strpos($insertSql, 'DATE_ADD(NOW(), INTERVAL 30 MINUTE)') !== false);
arCheck('no PHP date() expiry is written any more', strpos($fp, "date('Y-m-d H:i:s', time() + 30 * 60)") === false);
$auth = (string) file_get_contents($root . '/admin/includes/auth.php');
arCheck('admin sessions use a folder per system user', strpos($auth, "'/so_admin_sessions_' . \$so_sess_uid") !== false && strpos($auth, "'/so_admin_sessions';") === false);

// 2. The real flow against the database, with PHP on the site's timezone (Los Angeles) and the database on its own (UTC in CI).
date_default_timezone_set('America/Los_Angeles');
$db = MYSQLI;
$email = 'reset-expiry-test-' . getmypid() . '@example.test';
$st = $db->prepare("INSERT INTO admin_users (name, email, password, created_at, updated_at) VALUES ('Reset test', ?, 'x', NOW(), NOW())");
$st->bind_param('s', $email); $st->execute(); $adminId = (int) $db->insert_id; $st->close();
try {
    $dbOffset = (int) $db->query('SELECT TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), NOW())')->fetch_row()[0];
    $phpOffset = intdiv((new DateTime('now'))->getOffset(), 60);
    arCheck('the test really runs PHP and the database on different clocks', $dbOffset !== $phpOffset);

    // The insert exactly as forgot-password.php writes it.
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $st = $db->prepare($insertSql);
    $st->bind_param('is', $adminId, $hash); $st->execute(); $st->close();
    arCheck('a fresh link opens (not expired)', admin_find_valid_reset($token) !== null);
    $mins = (int) $db->query('SELECT TIMESTAMPDIFF(MINUTE, NOW(), expires_at) FROM admin_password_resets WHERE admin_id = ' . $adminId . ' ORDER BY ID DESC LIMIT 1')->fetch_row()[0];
    arCheck('it is valid for about 30 minutes', $mins >= 29 && $mins <= 30);

    // The old way, for comparison: PHP's clock in Los Angeles against the database's NOW().
    $old = bin2hex(random_bytes(32));
    $oldHash = hash('sha256', $old);
    $oldExpiry = date('Y-m-d H:i:s', time() + 30 * 60);
    $st = $db->prepare('INSERT INTO admin_password_resets (admin_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())');
    $st->bind_param('iss', $adminId, $oldHash, $oldExpiry); $st->execute(); $st->close();
    arCheck('the old PHP-clock expiry would have opened as expired (the bug this fixes)', admin_find_valid_reset($old) === null);

    // A used or expired link stays refused.
    $db->query('UPDATE admin_password_resets SET used_at = NOW() WHERE admin_id = ' . $adminId);
    arCheck('a used link is refused', admin_find_valid_reset($token) === null);
} finally {
    $st = $db->prepare('DELETE FROM admin_users WHERE ID = ?');
    $st->bind_param('i', $adminId); $st->execute(); $st->close();   // resets go with it (ON DELETE CASCADE)
}

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "admin reset expiry: all passed\n";
