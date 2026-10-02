<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
// Sign-in lifetime: a normal sign-in ends after SO_ADMIN_IDLE_SECONDS without activity; "Keep me signed in" lasts up to SO_ADMIN_REMEMBER_SECONDS.
// PHP only keeps a session file for session.gc_maxlifetime (24 minutes by default, and Debian/Ubuntu clean the shared folder on their
// own schedule), so the checkbox could never work. Admin sessions therefore live in their own private folder with a matching lifetime.
const SO_ADMIN_IDLE_SECONDS = 7200;
const SO_ADMIN_REMEMBER_SECONDS = 2592000;
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) {
        ini_set('session.cookie_secure', '1');
    }
    $so_sess_dir = rtrim(sys_get_temp_dir(), '/') . '/so_admin_sessions';
    if (!is_dir($so_sess_dir)) { @mkdir($so_sess_dir, 0700, true); }
    if (is_dir($so_sess_dir) && is_writable($so_sess_dir)) { session_save_path($so_sess_dir); }
    ini_set('session.gc_maxlifetime', (string) SO_ADMIN_REMEMBER_SECONDS);
    session_name('admin_session');
    session_start();
    unset($so_sess_dir);
}
// Admin pages are per-user: never let a browser, proxy or CDN store them. They are also never framed, sniffed or indexed.
if (!headers_sent()) {
    header('Cache-Control: private, no-store');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Robots-Tag: noindex, nofollow');
    header('Permissions-Policy: camera=(), microphone=(), payment=(), geolocation=()');
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

require_once __DIR__ . '/../../db/config.php';
require_once __DIR__ . '/../../inc/constants.php';
require_once __DIR__ . '/../../inc/request-guard.php';

function admin_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_csrf_verify(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && !empty($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function admin_flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function admin_flash_get(): ?array {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** A short fingerprint of the account's current password hash: a password change makes every other session's fingerprint stale. */
function admin_password_stamp(string $passwordHash): string {
    return substr(hash('sha256', $passwordHash), 0, 32);
}

function admin_end_session(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_stamp'], $_SESSION['admin_remember'], $_SESSION['admin_last_seen'], $_SESSION['admin_since']);
}

function admin_is_logged_in(): bool {
    static $checked = null;
    if ($checked !== null) return $checked;
    if (empty($_SESSION['admin_id'])) return $checked = false;
    $now = time();
    $remember = !empty($_SESSION['admin_remember']);
    $idle = $now - (int) ($_SESSION['admin_last_seen'] ?? 0);
    $age = $now - (int) ($_SESSION['admin_since'] ?? 0);
    if ($idle > ($remember ? SO_ADMIN_REMEMBER_SECONDS : SO_ADMIN_IDLE_SECONDS) || $age > SO_ADMIN_REMEMBER_SECONDS) {
        admin_end_session();
        return $checked = false;
    }
    // Still the same password as when this session signed in? (a reset or change signs every other session out)
    $stmt = MYSQLI->prepare('SELECT password FROM admin_users WHERE ID = ? LIMIT 1');
    $id = (int) $_SESSION['admin_id'];
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($hash);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found || !hash_equals((string) ($_SESSION['admin_stamp'] ?? ''), admin_password_stamp((string) $hash))) {
        admin_end_session();
        return $checked = false;
    }
    $_SESSION['admin_last_seen'] = $now;
    return $checked = true;
}

/** Start an authenticated session for this admin (after the password was verified). */
function admin_sign_in(array $admin, bool $remember): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['ID'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_stamp'] = admin_password_stamp((string) $admin['password']);
    $_SESSION['admin_remember'] = $remember ? 1 : 0;
    $_SESSION['admin_since'] = $_SESSION['admin_last_seen'] = time();
    if ($remember) {
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), [
            'expires' => time() + SO_ADMIN_REMEMBER_SECONDS,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

// Sign-in throttle: failed attempts are counted per address and per email in 15 minute windows; past the limit the
// form refuses to even check the password, so guessing is slow whatever the attacker tries.
const SO_ADMIN_LOGIN_WINDOW = 900;
const SO_ADMIN_LOGIN_MAX_PER_EMAIL = 5;
const SO_ADMIN_LOGIN_MAX_PER_IP = 20;

function admin_login_locked(string $email): bool {
    return soRateCount('admin-login-email', strtolower($email), SO_ADMIN_LOGIN_WINDOW) >= SO_ADMIN_LOGIN_MAX_PER_EMAIL
        || soRateCount('admin-login-ip', soIpHash(soClientIp()), SO_ADMIN_LOGIN_WINDOW) >= SO_ADMIN_LOGIN_MAX_PER_IP;
}

/** Record a failed sign-in and slow the answer down a little more for each recent failure (up to 3 seconds). */
function admin_login_failed(string $email): void {
    soRateHit('admin-login-email', strtolower($email), 1000000, SO_ADMIN_LOGIN_WINDOW);
    soRateHit('admin-login-ip', soIpHash(soClientIp()), 1000000, SO_ADMIN_LOGIN_WINDOW);
    $n = soRateCount('admin-login-email', strtolower($email), SO_ADMIN_LOGIN_WINDOW);
    usleep((int) min(3000000, 400000 * $n));
}

function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        header('Location: login');
        exit;
    }
}

function admin_require_guest(): void {
    if (admin_is_logged_in()) {
        header('Location: dashboard');
        exit;
    }
}

// Self-service registration (admin/register.php) has no invite/approval
// step - it only ever checked that the visitor wasn't already logged in,
// which does nothing to stop the public from creating themselves a fresh
// admin account. This makes registration a one-time bootstrap step: once
// any admin_users row exists, further registration is blocked. Adding a
// second admin from here on is a manual DB insert (or a future
// admin-invite feature) until one is built.
function admin_registration_is_open(mysqli $mysqli = MYSQLI): bool {
    $result = $mysqli->query('SELECT COUNT(*) AS c FROM admin_users');
    $row = $result ? $result->fetch_assoc() : null;
    return (int) ($row['c'] ?? 1) === 0;
}

function admin_password_meets_policy(string $password): bool {
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}$/', $password) === 1;
}

function admin_find_by_email(string $email, mysqli $mysqli = MYSQLI): ?array {
    $stmt = $mysqli->prepare('SELECT ID, name, email, password FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function admin_find_valid_reset(string $token, mysqli $mysqli = MYSQLI): ?array {
    $tokenHash = hash('sha256', $token);
    $stmt = $mysqli->prepare(
        'SELECT r.ID AS reset_id, u.ID AS admin_id, u.name, u.email
         FROM admin_password_resets r
         JOIN admin_users u ON u.ID = r.admin_id
         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
