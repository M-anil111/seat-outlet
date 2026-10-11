<?php
// /account: saved events, price alerts, sign out and delete. Signed-in visitors only.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/contact.php';   // soContactSameOrigin()
require_once __DIR__ . '/inc/account.php';
require_once __DIR__ . '/inc/account-pages.php';

$user = soAcctUser();
if ($user === null) soAcctRedirect('/login?next=%2Faccount');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Cache-Control: no-store');
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if (!soContactSameOrigin() || !soAcctCsrfOk($user, $_POST['csrf'] ?? null)) { http_response_code(403); echo 'Request could not be confirmed. Go back and try again.'; exit; }
    if ($action === 'signout') { soAcctSignOut(); soAcctRedirect('/?signed_out=1', 303); }
    if ($action === 'delete' && ($_POST['confirm'] ?? '') === 'yes') { soAcctDelete($user['id'], $user['email']); soAcctRedirect('/?account_deleted=1', 303); }
    if ($action === 'remove' && isset($_POST['event_id']) && is_string($_POST['event_id']) && ctype_digit($_POST['event_id'])) { soAcctRemoveSaved($user['id'], (int) $_POST['event_id']); }
    soAcctRedirect('/account', 303);
}

$saved = soAcctSavedList($user['id']);
$alerts = soAcctAlerts($user['email']);
$welcome = !empty($_GET['welcome']);
$today = date('Y-m-d');
$pageNoCache = true; $pageRobots = 'noindex, nofollow';
[$pageMetaTitle, $pageMetaDescription] = soAcctMeta('Your account', 'Your saved events and alerts.');
include 'header.php';
echo soAcctStyle();
$csrf = soAcctH($user['csrf']);
?>
<section class="so-acct">
  <div class="container">
    <div class="so-acct__card" style="max-width:720px">
      <h1><?php echo $welcome ? 'Welcome to Seat Outlet' : 'Your account'; ?></h1>
      <p class="so-acct__lead">Signed in as <strong><?php echo soAcctH($user['email']); ?></strong></p>

      <h2 class="so-acct__h2" id="saved">Saved events</h2>
      <?php if ($saved): ?>
        <ul class="so-acct__list">
        <?php foreach ($saved as $e):
            $past = $e['date'] !== null && $e['date'] < $today;
            $meta = array_filter([$e['date'] !== null ? date('D, M j, Y', strtotime($e['date'])) : '', $e['venue'], $e['city']]); ?>
          <li class="<?php echo $past ? 'so-acct__past' : ''; ?>">
            <span><a href="<?php echo soAcctH($e['path']); ?>"><?php echo soAcctH($e['name']); ?></a><span class="so-acct__meta"><?php echo soAcctH(implode(' · ', $meta)); ?><?php echo $past ? ' · past event' : ''; ?></span></span>
            <form method="post" action="/account"><input type="hidden" name="csrf" value="<?php echo $csrf; ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="event_id" value="<?php echo (int) $e['id']; ?>">
              <button type="submit" class="so-acct__btn so-acct__btn--ghost" style="min-height:44px" aria-label="Remove <?php echo soAcctH($e['name']); ?> from saved events">Remove</button></form>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="so-acct__lead">Nothing saved yet. Tap the heart on any event page to save it.</p>
      <?php endif; ?>
      <div id="soAcctImport" hidden>
        <p class="so-acct__note" id="soAcctImportText"></p>
        <button type="button" class="so-acct__btn so-acct__btn--ghost" id="soAcctImportBtn" style="min-height:44px;margin-top:8px">Add them to my account</button>
        <p class="so-acct__msg" id="soAcctImportMsg" role="status" aria-live="polite" hidden></p>
      </div>

      <h2 class="so-acct__h2" id="alerts">Your alerts</h2>
      <?php if ($alerts):
          $unsub = ''; foreach ($alerts as $a) { if ($a['unsubscribe'] !== '' && !$a['off']) { $unsub = $a['unsubscribe']; break; } } ?>
        <ul class="so-acct__list">
        <?php foreach ($alerts as $a): ?>
          <li><span><?php echo soAcctH($a['name'] !== '' ? $a['name'] : ucfirst($a['type'])); ?><span class="so-acct__meta"><?php echo $a['off'] ? 'Emails are off' : 'We email you when something changes'; ?></span></span></li>
        <?php endforeach; ?>
        </ul>
        <?php if ($unsub !== ''): ?><p class="so-acct__note"><a href="<?php echo soAcctH($unsub); ?>">Turn off all emails to this address</a> (this stops every alert above).</p><?php endif; ?>
      <?php else: ?>
        <p class="so-acct__lead">No alerts for this address. Use "Notify me" on an event or artist page to add one.</p>
      <?php endif; ?>

      <h2 class="so-acct__h2">Account</h2>
      <form method="post" action="/account" style="display:inline"><input type="hidden" name="csrf" value="<?php echo $csrf; ?>"><input type="hidden" name="action" value="signout">
        <button type="submit" class="so-acct__btn so-acct__btn--ghost" style="min-height:48px">Sign out</button></form>
      <details style="margin-top:22px"><summary style="cursor:pointer;font-weight:700">Delete my account</summary>
        <p class="so-acct__note">This removes your account and saved events from Seat Outlet and signs you out everywhere. Alert emails are turned off with the "Turn off all emails" link above. Orders are with our ticket partner and are not affected.</p>
        <form method="post" action="/account"><input type="hidden" name="csrf" value="<?php echo $csrf; ?>"><input type="hidden" name="action" value="delete">
          <label style="font-weight:400"><input type="checkbox" name="confirm" value="yes" required> Yes, delete my account</label>
          <button type="submit" class="so-acct__btn so-acct__btn--danger" style="min-height:48px;margin-top:12px">Delete my account</button></form>
      </details>
      <p class="so-acct__note">Orders are placed and managed with our ticket partner at checkout, so they are not listed here. See <a href="/privacy-policy">how we handle your data</a>.</p>
    </div>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var box = document.getElementById('soAcctImport'); if (!box) return;
  var list = [];
  try { var a = JSON.parse(localStorage.getItem('so_saved_events') || '[]'); list = Array.isArray(a) ? a.filter(function (x) { return x && x.id && x.slug; }) : []; } catch (e) { list = []; }
  if (!list.length) return;
  document.getElementById('soAcctImportText').textContent = list.length + (list.length === 1 ? ' event is' : ' events are') + ' saved in this browser.';
  box.hidden = false;
  var btn = document.getElementById('soAcctImportBtn'), msg = document.getElementById('soAcctImportMsg');
  btn.addEventListener('click', function () {
    btn.disabled = true;
    fetch('/ajax/account-saved.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ csrf: <?php echo json_encode($user['csrf'], JSON_HEX_TAG | JSON_HEX_AMP); ?>, events: list.slice(0, 100) }) })
      .then(function (r) { return r.json(); })
      .then(function (d) { msg.hidden = false; msg.className = 'so-acct__msg' + (d.status === 'ok' ? ' is-ok' : ''); var done = d.status === 'ok' || d.status === 'partial'; msg.className = 'so-acct__msg' + (d.status === 'ok' ? ' is-ok' : ''); msg.textContent = d.status === 'ok' ? 'Added. Reloading your list...' : (d.message || 'Could not add them.'); if (done) setTimeout(function () { location.reload(); }, d.status === 'partial' ? 3500 : 700); else btn.disabled = false; })
      .catch(function () { btn.disabled = false; msg.hidden = false; msg.className = 'so-acct__msg'; msg.textContent = 'Could not reach the server. Try again.'; });
  });
});
</script>
<?php include 'footer.php'; ?>
