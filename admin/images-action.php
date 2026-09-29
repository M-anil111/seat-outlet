<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_csrf_verify($_POST['csrf_token'] ?? null)) {
    admin_flash_set('error', 'Your session expired. Please try again.');
    header('Location: images');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$do = (string) ($_POST['do'] ?? '');

if ($id > 0 && $do === 'retry') {
    // Back to the queue: the next cron run re-resolves it from the sources.
    // A manual image is deliberately included - "Re-resolve" is how you
    // drop an override.
    $stmt = MYSQLI->prepare("UPDATE images SET status = 'pending', expires_at = NULL, source = NULL, source_url = NULL, license = NULL, attribution = NULL WHERE ID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    admin_flash_set('success', 'Queued for re-resolution.');
}

header('Location: images');
exit;
