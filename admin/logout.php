<?php
require_once __DIR__ . '/includes/auth.php';

// Signing out changes state, so it needs a POST with the session's CSRF token (a link or an image on another site could otherwise sign an
// admin out). A plain visit just goes back to the dashboard (or to the sign-in page when nobody is signed in).
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !admin_csrf_verify($_POST['csrf_token'] ?? null)) {
    header('Location: ' . (admin_is_logged_in() ? 'dashboard' : 'login'));
    exit;
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], true);
}
session_destroy();

header('Location: login');
exit;
