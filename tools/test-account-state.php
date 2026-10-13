<?php
// Header sign in / sign out: the state endpoint's answer and the pieces the header script needs. Run: php tools/test-account-state.php
require __DIR__ . '/../inc/account.php';
$fail = 0;
function asCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
asCheck('signed out answers only signedIn false', soAcctState(null) === ['signedIn' => false]);
$st = soAcctState(['id' => 7, 'email' => 'a@b.test', 'csrf' => 'tok', 'session_id' => 1]);
asCheck('signed in answers the csrf value and nothing about the person', $st === ['signedIn' => true, 'csrf' => 'tok']);
asCheck('the marker cookie is not a secret name', SO_ACCT_MARKER === 'so_in' && SO_ACCT_MARKER !== SO_ACCT_COOKIE);
$hdr = file_get_contents(__DIR__ . '/../header.php');
asCheck('header marks both sign in links for the script', strpos($hdr, 'data-so-signin>Sign in</a>') !== false && strpos($hdr, 'data-so-signin-menu>Sign in or create account') !== false);
$js = file_get_contents(__DIR__ . '/../js/main.js');
asCheck('script asks the endpoint only when the marker cookie is set and posts the account page sign out', strpos($js, "indexOf('so_in=1') === -1") !== false && strpos($js, "form.action = '/account'") !== false && strpos($js, "['action', 'signout']") !== false);
$acct = file_get_contents(__DIR__ . '/../inc/account.php');
asCheck('sign in sets and sign out clears the marker', substr_count($acct, 'SO_ACCT_MARKER') >= 4);
$ep = file_get_contents(__DIR__ . '/../ajax/account-state.php');
asCheck('the endpoint loads the file that defines soContactSameOrigin()', strpos($ep, "inc/contact.php") !== false && strpos(file_get_contents(__DIR__ . '/../inc/contact.php'), 'function soContactSameOrigin') !== false);
exit($fail ? 1 : 0);
