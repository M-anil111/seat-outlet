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

if ($do === 'resolve_now') {
    // Work 25 queue rows right now (signed-in admin, CSRF-checked above). Bounded: 25 rows, 0.8s apart, 50s deadline.
    @set_time_limit(90);
    $r = imageWorkQueue(25, 800000, time() + 50);
    admin_flash_set($r['processed'] === 0 ? 'success' : ($r['resolved'] > 0 ? 'success' : 'error'),
        sprintf('Processed %d: %d resolved, %d no image found%s.', $r['processed'], $r['resolved'], $r['miss'], $r['rateLimited'] >= 3 ? ', stopped early because a source is rate limiting us' : ''));
}

if ($do === 'requeue_legacy') {
    // Pictures stored before licences were recorded: queue them again so each is re-checked and re-labelled (or replaced by the initials tile).
    MYSQLI->query("UPDATE images SET status = 'pending', expires_at = NULL, source = NULL, source_url = NULL, license = NULL, attribution = NULL
                    WHERE entity_type IS NOT NULL AND status = 'ok' AND store_key IS NULL AND (source IS NULL OR source <> 'admin')");
    admin_flash_set('success', 'Queued ' . (int) MYSQLI->affected_rows . ' older pictures for re-verification.');
}

header('Location: images');
exit;
