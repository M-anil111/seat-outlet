<?php
// /account-verify?t=<token>: the page the emailed link opens. A GET only shows a confirm button and uses nothing up (mail scanners and
// link previews fetch links); the POST from that button signs the person in.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/contact.php';   // soContactSameOrigin()
require_once __DIR__ . '/inc/account.php';
require_once __DIR__ . '/inc/account-pages.php';

$token = isset($_POST['t']) && is_string($_POST['t']) ? trim($_POST['t']) : (string) soQs('t');
$ok = null; $why = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // A page on another site must not be able to sign a visitor in to someone else's account (login CSRF): the POST has to come from here.
    if (!soContactSameOrigin()) {
        http_response_code(403); $why = 'forbidden';
    } else {
        $res = soAcctConsumeLink($token);
        if ($res === null) { http_response_code(410); $why = 'expired'; }
        else {
            soAcctSessionStart($res['user_id']);
            $dest = soAcctSafeNext($res['next']);
            header('Cache-Control: no-store');
            header('Location: ' . $dest . ($res['new'] && $dest === '/account' ? '?welcome=1' : ''), true, 303);
            exit;
        }
    }
} else {
    $link = soAcctPeekLink($token);
    if ($link === null) { http_response_code(410); $why = 'expired'; } else { $ok = ['token' => $token, 'email' => $link['email']]; }
}
$pageNoCache = true; $pageRobots = 'noindex, nofollow';
[$pageMetaTitle, $pageMetaDescription] = soAcctMeta('Confirm sign in', 'Confirm your sign-in to Seat Outlet.');
include 'header.php';
echo soAcctStyle();
soAcctVerifyBody($ok, $why);
include 'footer.php';
