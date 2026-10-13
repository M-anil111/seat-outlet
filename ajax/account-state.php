<?php
/**
 * Header state for the Sign in / Sign out link. Public pages are cached for everyone, so they always print "Sign in"; a small script asks
 * this endpoint (only when the so_in marker cookie is present) and swaps in Account and Sign out. Same-origin, never cached, no address.
 *
 *   GET /ajax/account-state.php  ->  {"signedIn":false}  or  {"signedIn":true,"csrf":"..."}
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/account.php';
require_once __DIR__ . '/../inc/contact.php';   // soContactSameOrigin()

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
header('Vary: Cookie');
if (!soContactSameOrigin()) { http_response_code(403); echo '{"signedIn":false}'; exit; }
$user = soAcctUser();
if ($user === null && !headers_sent() && isset($_COOKIE[SO_ACCT_MARKER])) {   // the session ended elsewhere: drop the marker so pages stop asking
    setcookie(SO_ACCT_MARKER, '', ['httponly' => false] + soAcctCookieOptions(time() - 3600));
}
echo json_encode(soAcctState($user));
