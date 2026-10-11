<?php
/**
 * POST /ajax/account-saved.php (JSON): add the events saved in this browser to the signed-in account. {csrf, events: [{id, slug, name, date, venue, city}]}
 * Needs a session, the session's CSRF value and a same-site request. Answers JSON {status: ok|partial|error, saved, skipped, total, message}.
 */
ini_set('display_errors', '0');
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/contact.php';
require_once __DIR__ . '/../inc/account.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$say = function (array $body, int $code = 200): void { http_response_code($code); echo json_encode($body); exit; };

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); $say(['status' => 'error', 'message' => 'Use POST.'], 405); }
$user = soAcctUser();
if ($user === null) $say(['status' => 'error', 'message' => 'Please sign in again.'], 401);
if (!soContactSameOrigin()) $say(['status' => 'error', 'message' => 'Request could not be confirmed.'], 403);
$raw = file_get_contents('php://input', false, null, 0, 262144);   // 256 KB is far more than 100 events
$in = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($in) || !soAcctCsrfOk($user, $in['csrf'] ?? null)) $say(['status' => 'error', 'message' => 'Request could not be confirmed. Reload the page.'], 403);
$events = soAcctCleanEvents($in['events'] ?? []);
try {
    $r = $events ? soAcctSaveEvents($user['id'], $events) : ['saved' => 0, 'skipped' => 0];
} catch (Throwable $e) {
    error_log('account: saving events failed: ' . get_class($e) . ' ' . mb_substr($e->getMessage(), 0, 160));
    $say(['status' => 'error', 'message' => 'We could not save them just now. Please try again.'], 503);
}
$total = soAcctSavedCount($user['id']);
if ($r['skipped'] > 0) {
    $say(['status' => 'partial', 'saved' => $r['saved'], 'skipped' => $r['skipped'], 'total' => $total,
          'message' => 'Added ' . $r['saved'] . ' of ' . ($r['saved'] + $r['skipped']) . '. An account holds at most ' . SO_ACCT_MAX_SAVED . ' saved events; remove some to add more.']);
}
$say(['status' => 'ok', 'saved' => $r['saved'], 'total' => $total]);
