<?php
// Offline source checks for the account pages (no database): nothing here may weaken the sign-in design. Run: php tools/test-account-pages.php
$root = dirname(__DIR__);
$r = function (string $f) use ($root): string { return (string) file_get_contents($root . '/' . $f); };
$fails = 0;
function apCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

$verify = $r('account-verify.php');
apCheck('the emailed link page only consumes the link on POST', preg_match("/REQUEST_METHOD'\] \?\? 'GET'\) === 'POST'\) \{.*?soAcctConsumeLink.*?\} else \{.*?soAcctPeekLink/s", $verify) === 1);
apCheck('the POST must come from this site (login CSRF)', strpos($verify, 'soContactSameOrigin()') !== false);
apCheck('the redirect after sign-in goes through the safe-path check', strpos($verify, 'soAcctSafeNext($res[\'next\'])') !== false);

$acct = $r('account.php');
apCheck('every account POST checks the CSRF value and the origin', preg_match("/soContactSameOrigin\(\) \|\| !soAcctCsrfOk/", $acct) === 1);
apCheck('account page sends nobody away without a session', strpos($acct, "soAcctUser();\nif (\$user === null)") !== false);
apCheck('the CSRF value goes into the page script as JSON with the tag-safe flags', strpos($acct, 'JSON_HEX_TAG | JSON_HEX_AMP') !== false);

$link = $r('ajax/account-link.php');
apCheck('link endpoint: POST only, same origin, honeypot, reCAPTCHA, no-store', strpos($link, "!== 'POST'") !== false && strpos($link, 'soContactSameOrigin()') !== false && strpos($link, "post('website')") !== false && strpos($link, 'soLeadVerifyRecaptcha') !== false && strpos($link, 'no-store') !== false);
apCheck('link endpoint gives the same answer for new and known addresses', substr_count($link, "'sent'") >= 2 && stripos($link, 'FROM users') === false && stripos($link, 'already') === false);
$saved = $r('ajax/account-saved.php');
apCheck('saved-events endpoint needs a session, the CSRF value and the origin', strpos($saved, 'soAcctUser()') !== false && strpos($saved, 'soAcctCsrfOk') !== false && strpos($saved, 'soContactSameOrigin()') !== false);

$lib = $r('inc/account.php');
apCheck('secrets are only stored hashed', strpos($lib, 'soAcctHash($token)') !== false && preg_match('/INSERT INTO (login_links|user_sessions)[^;]*\$token\b/', $lib) === 0);
apCheck('the session cookie is HttpOnly, SameSite=Lax and Secure on https', strpos($lib, "'httponly' => true") !== false && strpos($lib, "'samesite' => 'Lax'") !== false && strpos($lib, "'secure'") !== false);
apCheck('a link is used with a conditional UPDATE, so two requests cannot both win', strpos($lib, 'WHERE id = ? AND used_at IS NULL') !== false);
apCheck('no SQL is built by string concatenation with input', preg_match('/->(query|prepare)\([^)]*\$_(GET|POST|COOKIE|REQUEST)/', $lib) === 0);

// The header "Sign in" link must not reuse the mega-menu class: the menu scripts read every .so-mega-top as a menu item with a parent.
$hdr = $r('header.php');
apCheck('header: so-mega-top is used only by the menu loop', substr_count($hdr, 'so-mega-top') === 1);
apCheck('header: the Sign in link has its own class', strpos($hdr, 'class="so-header-signin') !== false);

// Private pages stay out of search results and the sitemap.
$robots = $r('robots.txt');
apCheck('robots.txt does not block the account pages (so the noindex header is readable)', strpos($robots, 'Disallow: /login') === false && strpos($robots, 'Disallow: /account') === false && strpos($robots, 'Disallow: /register') === false);
apCheck('the account pages send an X-Robots-Tag noindex header', strpos($r('inc/account-pages.php'), "header('X-Robots-Tag: noindex, nofollow')") !== false);
apCheck('the emailed token is not echoed into the page (cookie handoff)', strpos($r('account-verify.php'), 'so_verify') !== false);
apCheck('the confirm POST is bound to the link shown on the page (a newer link cannot be consumed by an older page)', strpos($r('account-verify.php'), 'hash_equals(substr(soAcctHash($cookieTok), 0, 16)') !== false && strpos($r('inc/account-pages.php'), 'name="h"') !== false);
apCheck('cleanup columns are indexed', strpos($r('db/migrations/0047_accounts.sql'), 'login_links_created_idx') !== false && strpos($r('db/migrations/0047_accounts.sql'), 'user_sessions_expires_idx') !== false);
apCheck('a missing account table degrades to "no" instead of a 500', substr_count($lib, 'soAcctDbFail($e)') >= 2);
apCheck('CSP allows reCAPTCHA for the account forms', strpos($r('functions.php'), 'google.com/recaptcha') !== false);
foreach (['login.php', 'register.php', 'account-verify.php', 'account.php'] as $f) { apCheck("$f is noindex and not cached", strpos($r($f), "\$pageRobots = 'noindex, nofollow'") !== false && strpos($r($f), '$pageNoCache = true') !== false); }
echo $fails ? "$fails failed\n" : "account pages: all passed\n";
exit($fails ? 1 : 0);
