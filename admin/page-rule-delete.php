<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_csrf_verify($_POST['csrf_token'] ?? null)) {
    admin_flash_set('error', 'Invalid request.');
    header('Location: page-rules');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    deletePageRule($id);
    admin_flash_set('success', 'Page rule deleted.');
}

header('Location: page-rules');
exit;
