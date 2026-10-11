<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test for accounts (inc/account.php). Needs the migrated database (run after db/migrate.php). No mail server is needed: the
 * mailer is replaced by a function that keeps the link. Every row it makes uses addresses @zq-acct.test and is deleted at the end.
 * Run: php tools/test-account.php
 */
require_once __DIR__ . '/../db/config.php';
if (!defined('HOME_URL')) define('HOME_URL', 'https://beta.example.test');   // the live install can have HOME_URL on the beta host
if (!defined('SO_PUBLIC_ORIGIN')) define('SO_PUBLIC_ORIGIN', 'https://seatoutlet.com');
if (!defined('SO_CONTACT_SUPPORT')) define('SO_CONTACT_SUPPORT', 'support@seatoutlet.com');
require_once __DIR__ . '/../inc/account.php';

$fail = 0;
function acctCheck(string $what, $got, $want): void {
    global $fail;
    if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else { echo "ok   $what\n"; }
}
function acctClean(): void {
    MYSQLI->query("DELETE FROM login_links WHERE email LIKE '%@zq-acct.test'");
    MYSQLI->query("DELETE FROM users WHERE email LIKE '%@zq-acct.test'");
}
acctClean();
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

// ---- Pure helpers
acctCheck('email lower-cased and trimmed', soAcctEmail('  Zq.One@ZQ-acct.test '), 'zq.one@zq-acct.test');
acctCheck('email with a header injection is refused', soAcctEmail("a@b.test\r\nBcc: x@y.test"), '');
acctCheck('email with two addresses is refused', soAcctEmail('a@b.test,c@d.test'), '');
acctCheck('email without a domain is refused', soAcctEmail('nobody'), '');
acctCheck('overlong email is refused', soAcctEmail(str_repeat('a', 250) . '@b.test'), '');
acctCheck('next: own path is kept', soAcctSafeNext('/event/some-show-2026-10-10'), '/event/some-show-2026-10-10');
acctCheck('next: other host is refused', soAcctSafeNext('https://evil.example/x'), '/account');
acctCheck('next: protocol-relative is refused', soAcctSafeNext('//evil.example/x'), '/account');
acctCheck('next: backslash trick is refused', soAcctSafeNext('/\\evil.example'), '/account');
acctCheck('next: javascript is refused', soAcctSafeNext('javascript:alert(1)'), '/account');
acctCheck('next: the sign-in pages themselves are not a destination', soAcctSafeNext('/login?x=1'), '/account');
acctCheck('mask hides the name', soAcctMask('jane.doe@example.com'), 'j******@example.com');

// ---- Asking for a link
$sent = [];
$mailer = function (string $to, string $subject, string $html, string $text) use (&$sent): bool { $sent[] = ['to' => $to, 'html' => $html, 'text' => $text]; return true; };
acctCheck('invalid address is not mailed', soAcctRequestLink('nope', '', $mailer)['status'], 'invalid');
acctCheck('no mail for an invalid address', count($sent), 0);
$r = soAcctRequestLink('Zq.One@zq-acct.test', '/event/x-show-2026-10-10', $mailer);
acctCheck('a valid address gets a link', $r['status'], 'sent');
acctCheck('exactly one mail, to that address', [count($sent), $sent[0]['to'] ?? ''], [1, 'zq.one@zq-acct.test']);
acctCheck('the emailed link uses the public address, not HOME_URL', strpos($sent[0]['text'] ?? '', 'https://seatoutlet.com/account-verify?t=') !== false, true);
preg_match('#/account-verify\?t=([a-f0-9]{64})#', $sent[0]['text'] ?? '', $m);
$token = $m[1] ?? '';
acctCheck('the mail carries a 64-character token', strlen($token), 64);
$stored = MYSQLI->query("SELECT token_hash FROM login_links WHERE email = 'zq.one@zq-acct.test'")->fetch_row()[0] ?? '';
acctCheck('only the hash of the token is stored', [$stored === hash('sha256', $token), $stored === $token], [true, false]);
acctCheck('asking again at once is told to wait', soAcctRequestLink('zq.one@zq-acct.test', '', $mailer)['status'], 'wait');
acctCheck('and no second mail went out', count($sent), 1);

// A failed mail leaves no usable link behind.
$bad = function () { return false; };
acctCheck('a mail that fails reports an error', soAcctRequestLink('zq.two@zq-acct.test', '', $bad)['status'], 'error');
acctCheck('and leaves no link', (int) MYSQLI->query("SELECT COUNT(*) FROM login_links WHERE email = 'zq.two@zq-acct.test'")->fetch_row()[0], 0);

// Per-address hourly limit: back-date the first request's rows so only the hourly rule can trigger.
for ($i = 0; $i < 5; $i++) {
    MYSQLI->query("INSERT INTO login_links (email, token_hash, ip_hash, expires_at, created_at) VALUES ('zq.rate@zq-acct.test', '" . hash('sha256', "r$i") . "', 'x', NOW() + INTERVAL 15 MINUTE, NOW() - INTERVAL 10 MINUTE)");
}
acctCheck('five links in an hour is the limit for one address', soAcctRequestLink('zq.rate@zq-acct.test', '', $mailer)['status'], 'limit');

// ---- Using a link
acctCheck('a made-up token is refused', soAcctConsumeLink(str_repeat('a', 64)), null);
acctCheck('a malformed token is refused', soAcctConsumeLink("' OR 1=1 --"), null);
$peek = soAcctPeekLink($token);
acctCheck('looking at a link does not use it up', [$peek['email'] ?? '', $peek['next'] ?? ''], ['zq.one@zq-acct.test', '/event/x-show-2026-10-10']);
acctCheck('looking again still works', soAcctPeekLink($token) !== null, true);
$res = soAcctConsumeLink($token);
acctCheck('the link signs the person in and creates the account', [$res['email'] ?? '', $res['new'] ?? null, ($res['user_id'] ?? 0) > 0], ['zq.one@zq-acct.test', true, true]);
acctCheck('the same link cannot be used twice', soAcctConsumeLink($token), null);
acctCheck('and cannot be peeked once used', soAcctPeekLink($token), null);

// A second link for the same person finds the same account (no duplicate).
MYSQLI->query("UPDATE login_links SET created_at = NOW() - INTERVAL 5 MINUTE WHERE email = 'zq.one@zq-acct.test'");
soAcctRequestLink('zq.one@zq-acct.test', '', $mailer);
preg_match('#t=([a-f0-9]{64})#', $sent[count($sent) - 1]['text'], $m2);
$res2 = soAcctConsumeLink($m2[1] ?? '');
acctCheck('the second sign-in is the same account, not a new one', [$res2['user_id'] ?? 0, $res2['new'] ?? null], [$res['user_id'], false]);

// Expired links.
MYSQLI->query("UPDATE login_links SET created_at = NOW() - INTERVAL 5 MINUTE WHERE email = 'zq.one@zq-acct.test'");
soAcctRequestLink('zq.one@zq-acct.test', '', $mailer);
preg_match('#t=([a-f0-9]{64})#', $sent[count($sent) - 1]['text'], $m3);
MYSQLI->query("UPDATE login_links SET expires_at = NOW() - INTERVAL 1 SECOND WHERE token_hash = '" . hash('sha256', $m3[1] ?? '') . "'");
acctCheck('an expired link is refused', soAcctConsumeLink($m3[1] ?? ''), null);

// ---- Sessions
$uid = (int) $res['user_id'];
$sess = soAcctSessionStart($uid, false);
acctCheck('session token is 64 hex characters', (bool) preg_match('/^[a-f0-9]{64}$/', $sess['token']), true);
$stored = MYSQLI->query("SELECT token_hash FROM user_sessions WHERE user_id = $uid")->fetch_row()[0] ?? '';
acctCheck('only the hash of the session token is stored', [$stored === hash('sha256', $sess['token']), $stored === $sess['token']], [true, false]);
$user = soAcctUser($sess['token']);
acctCheck('the session finds the person', [$user['id'] ?? 0, $user['email'] ?? ''], [$uid, 'zq.one@zq-acct.test']);
acctCheck('a wrong session token finds nobody', soAcctUser(str_repeat('b', 64)), null);
acctCheck('a malformed session token finds nobody', soAcctUser('x'), null);
acctCheck('CSRF: the right value passes', soAcctCsrfOk($user, $sess['csrf']), true);
acctCheck('CSRF: a wrong value fails', soAcctCsrfOk($user, 'nope'), false);
acctCheck('CSRF: nobody signed in fails', soAcctCsrfOk(null, $sess['csrf']), false);
MYSQLI->query("UPDATE user_sessions SET expires_at = NOW() - INTERVAL 1 SECOND WHERE user_id = $uid");
acctCheck('an expired session finds nobody (memo bypassed with a fresh token)', (function () use ($uid) { $s = soAcctSessionStart($uid, false); MYSQLI->query("UPDATE user_sessions SET expires_at = NOW() - INTERVAL 1 SECOND WHERE token_hash = '" . hash('sha256', $s['token']) . "'"); return soAcctUser($s['token']); })(), null);
$sess2 = soAcctSessionStart($uid, false);
acctCheck('a new session works', soAcctUser($sess2['token'])['id'] ?? 0, $uid);
soAcctSignOut($sess2['token']);
acctCheck('signing out removes the session row', (int) MYSQLI->query("SELECT COUNT(*) FROM user_sessions WHERE token_hash = '" . hash('sha256', $sess2['token']) . "'")->fetch_row()[0], 0);

// ---- Saved events
$dirty = [
    ['id' => 111, 'slug' => 'good-show-2026-11-01', 'name' => 'Good <b>Show</b>', 'date' => '2026-11-01T20:00:00', 'venue' => 'Arena', 'city' => 'Dallas, TX'],
    ['id' => 111, 'slug' => 'good-show-2026-11-01', 'name' => 'Duplicate', 'date' => '2026-11-01'],
    ['id' => 'abc', 'slug' => 'bad-id', 'name' => 'Bad id'],
    ['id' => 222, 'slug' => '../../etc/passwd', 'name' => 'Bad slug'],
    ['id' => 333, 'slug' => 'no-name-show', 'name' => ''],
    ['id' => 444, 'slug' => 'bad-date-show', 'name' => 'Bad date', 'date' => '2026-13-45'],
    'not an array',
    ['id' => 555, 'slug' => 'javascript:alert(1)', 'name' => 'Script slug'],
];
$clean = soAcctCleanEvents($dirty);
acctCheck('only the valid, de-duplicated events are kept', array_column($clean, 'id'), [111, 444]);
acctCheck('markup in a name is stripped', $clean[0]['name'], 'Good Show');
acctCheck('the path is rebuilt from the slug', $clean[0]['path'], '/event/good-show-2026-11-01');
acctCheck('a real date is kept, a bad one is dropped', [$clean[0]['date'], $clean[1]['date']], ['2026-11-01', null]);
acctCheck('events are saved', soAcctSaveEvents($uid, $clean), 2);
acctCheck('saving again does not duplicate', [soAcctSaveEvents($uid, $clean), soAcctSavedCount($uid)], [2, 2]);
acctCheck('the list is soonest first, undated last', array_column(soAcctSavedList($uid), 'id'), [111, 444]);
soAcctRemoveSaved($uid, 111);
acctCheck('removing one leaves the other', array_column(soAcctSavedList($uid), 'id'), [444]);
$many = [];
for ($i = 1; $i <= 150; $i++) $many[] = ['id' => 7000 + $i, 'slug' => "many-show-$i", 'name' => "Many $i"];
soAcctSaveEvents($uid, soAcctCleanEvents($many));
acctCheck('a person can keep at most ' . SO_ACCT_MAX_SAVED . ' events', soAcctSavedCount($uid) <= SO_ACCT_MAX_SAVED, true);

// ---- Another person never sees these
soAcctRequestLink('zq.other@zq-acct.test', '', $mailer);
MYSQLI->query("UPDATE login_links SET created_at = NOW() - INTERVAL 5 MINUTE WHERE email = 'zq.other@zq-acct.test'");
preg_match('#t=([a-f0-9]{64})#', $sent[count($sent) - 1]['text'], $m4);
$other = soAcctConsumeLink($m4[1] ?? '');
acctCheck('a second person has an empty list', soAcctSavedList((int) $other['user_id']), []);

// ---- Deleting the account removes everything
soAcctDelete($uid, 'zq.one@zq-acct.test');
acctCheck('the account is gone', (int) MYSQLI->query("SELECT COUNT(*) FROM users WHERE id = $uid")->fetch_row()[0], 0);
acctCheck('its sessions are gone', (int) MYSQLI->query("SELECT COUNT(*) FROM user_sessions WHERE user_id = $uid")->fetch_row()[0], 0);
acctCheck('its saved events are gone', (int) MYSQLI->query("SELECT COUNT(*) FROM user_saved_events WHERE user_id = $uid")->fetch_row()[0], 0);
acctCheck('its sign-in links are gone', (int) MYSQLI->query("SELECT COUNT(*) FROM login_links WHERE email = 'zq.one@zq-acct.test'")->fetch_row()[0], 0);
acctCheck('the other person is untouched', (int) MYSQLI->query("SELECT COUNT(*) FROM users WHERE email = 'zq.other@zq-acct.test'")->fetch_row()[0], 1);

acctClean();
echo $fail ? "$fail failed\n" : "account: all passed\n";
exit($fail ? 1 : 0);
