<?php
// /account-verify: the page the emailed link opens.
//
//  1. GET /account-verify?t=<token>: checks the token (without using it up), hands it to this browser in a short-lived HttpOnly cookie that
//     only this page receives, and redirects to /account-verify with no token in the address. A sign-in token must not stay in the address
//     bar: analytics tags and the Referer header read the full address.
//  2. GET /account-verify: a confirm button (mail scanners and link previews fetch links, so nothing is used up here).
//  3. POST /account-verify (the button): same-site only. Uses the token up once, starts the session, removes the handoff cookie.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/inc/contact.php';   // soContactSameOrigin()
require_once __DIR__ . '/inc/account.php';
require_once __DIR__ . '/inc/account-pages.php';

const SO_ACCT_HANDOFF = 'so_verify';
$cookieTok = isset($_COOKIE[SO_ACCT_HANDOFF]) && is_string($_COOKIE[SO_ACCT_HANDOFF]) ? $_COOKIE[SO_ACCT_HANDOFF] : '';
$clearHandoff = function () { if (!headers_sent()) setcookie(SO_ACCT_HANDOFF, '', soAcctCookieOptions(time() - 3600, '/account-verify')); };
$ok = null; $why = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // A page on another site must not be able to sign a visitor in to someone else's account (login CSRF): the POST has to come from here,
    // and it carries no token of its own: only the cookie this site set for this browser (a cross-site POST does not send it).
    if (!soContactSameOrigin()) {
        http_response_code(403); $why = 'forbidden';
    } else {
        $res = soAcctConsumeLink($cookieTok);
        $clearHandoff();
        if ($res === null) { http_response_code(410); $why = 'expired'; }
        else {
            soAcctSessionStart($res['user_id']);
            $dest = soAcctSafeNext($res['next']);
            soAcctRedirect($dest . ($res['new'] && $dest === '/account' ? '?welcome=1' : ''), 303);
        }
    }
} elseif (isset($_GET['t'])) {
    $link = soAcctPeekLink((string) $_GET['t']);
    if ($link === null) { http_response_code(410); $why = 'expired'; }
    else {
        setcookie(SO_ACCT_HANDOFF, (string) $_GET['t'], soAcctCookieOptions(time() + SO_ACCT_LINK_MINUTES * 60, '/account-verify'));
        header('Referrer-Policy: no-referrer');
        soAcctRedirect('/account-verify', 303);
    }
} else {
    $link = soAcctPeekLink($cookieTok);
    if ($link === null) { http_response_code(410); $why = $cookieTok === '' ? 'nocookie' : 'expired'; }
    else { $ok = ['email' => $link['email']]; }
}
$pageNoCache = true; $pageRobots = 'noindex, nofollow';
[$pageMetaTitle, $pageMetaDescription] = soAcctMeta('Confirm sign in', 'Confirm your sign-in to Seat Outlet.');
include 'header.php';
echo soAcctStyle();
soAcctVerifyBody($ok, $why);
include 'footer.php';
