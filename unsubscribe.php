<?php
/**
 * Unsubscribe page: /unsubscribe?t=<token from the email link>.
 * GET unsubscribes at once (one click from the email) and offers an undo button; a POST from a mail provider's
 * one-click button (RFC 8058, body List-Unsubscribe=One-Click) does the same. Unknown tokens get a neutral message.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/leads-core.php';

$token = soQs('t');
if ($token === '' && isset($_POST['t']) && is_string($_POST['t'])) $token = trim($_POST['t']);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';

if ($method === 'POST' && ($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
    $ok = soLeadUnsubscribe($token) !== null;
    http_response_code($ok ? 200 : 404);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo $ok ? 'Unsubscribed' : 'Unknown link';
    exit;
}

$state = 'unknown';
if ($method === 'POST' && $action === 'resubscribe') {
    $state = soLeadResubscribe($token) ? 'resubscribed' : 'unknown';
} elseif (soLeadUnsubscribe($token) !== null) {
    $state = 'unsubscribed';
}

$pageNoCache = true;
$pageRobots = 'noindex, nofollow';
$pageMetaTitle = 'Email preferences | Seat Outlet';
$pageMetaDescription = 'Manage your Seat Outlet emails.';
include 'header.php';
$h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<section class="py-5">
  <div class="container" style="max-width:640px">
    <?php if ($state === 'unsubscribed'): ?>
      <h1 class="fs-2 fw-bold mb-3">You are unsubscribed</h1>
      <p class="mb-4">We will not send you any more emails. It can take a day for a message that was already on its way to stop.</p>
      <form method="post" action="/unsubscribe?t=<?php echo $h(rawurlencode($token)); ?>" class="mb-4">
        <input type="hidden" name="action" value="resubscribe">
        <p class="text-muted mb-2">Clicked by mistake?</p>
        <button type="submit" class="btn btn-outline-primary" style="min-height:44px">Keep me on the list</button>
      </form>
    <?php elseif ($state === 'resubscribed'): ?>
      <h1 class="fs-2 fw-bold mb-3">Welcome back</h1>
      <p class="mb-4">You are back on the list. You can unsubscribe again from any email we send.</p>
    <?php else: ?>
      <h1 class="fs-2 fw-bold mb-3">We could not find that link</h1>
      <p class="mb-4">This unsubscribe link is not valid. If you still get emails from us that you do not want, reply to one of them or write to <a href="mailto:support@seatoutlet.com">support@seatoutlet.com</a> and we will remove you.</p>
    <?php endif; ?>
    <a class="btn btn-primary" style="min-height:44px" href="/buy-tickets-online">Browse events</a>
  </div>
</section>
<?php include 'footer.php'; ?>
