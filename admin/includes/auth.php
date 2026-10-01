<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_name('admin_session');
    session_start();
}
// Admin pages are per-user: never let a browser, proxy or CDN store them.
if (!headers_sent()) { header('Cache-Control: private, no-store'); }

require_once __DIR__ . '/../../db/config.php';

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

function admin_is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
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
    return preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password) === 1;
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
