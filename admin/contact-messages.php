<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
require_once __DIR__ . '/../functions.php';
$db = MYSQLI;

// Messages sent through the contact and partnership forms (inc/contact.php). Mark one handled or reopen it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_csrf_verify($_POST['csrf_token'] ?? null)) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new', 'read', 'closed'], true) ? $_POST['status'] : 'read';
    $st = $db->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
    $st->bind_param('si', $status, $id);
    $st->execute();
    $st->close();
    header('Location: contact-messages');
    exit;
}
$rows = [];
try {
    $res = $db->query('SELECT * FROM contact_messages ORDER BY (status = \'closed\'), created_at DESC LIMIT 200');
    while ($res && ($r = $res->fetch_assoc())) { $rows[] = $r; }
} catch (Throwable $e) { $rows = []; }
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$pageTitle = 'Contact Messages — Seat Outlet Admin';
$currentPage = 'contact-messages';
include __DIR__ . '/includes/app-header.php';
?>
        <div class="row mb-3"><div class="col">
            <h2 class="page-title">Contact Messages</h2>
            <div class="text-secondary">Newest first, closed ones last. Reply from your mail program: the sender's address is shown. "Email" shows whether the notification and the auto-reply went out.</div>
        </div></div>
        <?php if (!$rows) { ?><div class="card"><div class="card-body text-secondary">No messages yet.</div></div><?php } ?>
        <?php foreach ($rows as $r) { ?>
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo $h($r['name']); ?> &middot; <a href="mailto:<?php echo $h($r['email']); ?>"><?php echo $h($r['email']); ?></a></h3>
                <div class="card-actions"><span class="badge <?php echo $r['status'] === 'new' ? 'bg-red-lt' : ($r['status'] === 'read' ? 'bg-yellow-lt' : 'bg-green-lt'); ?>"><?php echo $h($r['status']); ?></span></div>
            </div>
            <div class="card-body">
                <div class="text-secondary small mb-2"><?php echo $h($r['created_at']); ?> &middot; <?php echo $h($r['subject']); ?> &middot; from <?php echo $h($r['page']); ?><?php echo $r['order_ref'] ? ' &middot; order ' . $h($r['order_ref']) : ''; ?><?php echo $r['phone'] ? ' &middot; ' . $h($r['phone']) : ''; ?></div>
                <div style="white-space:pre-wrap"><?php echo $h($r['message']); ?></div>
                <div class="text-secondary small mt-2">Email: <?php echo $h($r['email_status']); ?></div>
            </div>
            <div class="card-footer">
                <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo admin_csrf_token(); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                    <?php foreach (['new' => 'Mark new', 'read' => 'Mark read', 'closed' => 'Close'] as $k => $l) { if ($k !== $r['status']) { ?>
                    <button class="btn btn-sm btn-outline-secondary" name="status" value="<?php echo $k; ?>"><?php echo $l; ?></button>
                    <?php } } ?>
                </form>
            </div>
        </div>
        <?php } ?>
<?php include __DIR__ . '/includes/app-footer.php'; ?>
