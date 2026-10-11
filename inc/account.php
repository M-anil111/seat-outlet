<?php // Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }

/**
 * Accounts: sign in and register are one step, an emailed single-use link. No passwords exist, so there is nothing to leak, reuse or reset.
 *
 *   1. /login or /register: the visitor gives an email address (reCAPTCHA v3 and a honeypot guard the form).
 *   2. We email a link to /account-verify?t=<token>. The token is 256 random bits, valid 15 minutes, stored only as a SHA-256 hash.
 *   3. The link opens a page with a button. The button (a POST, so mail scanners that fetch links cannot use it up) signs the person in.
 *   4. A session cookie (HttpOnly, Secure, SameSite=Lax; 256 random bits, stored only as a hash) keeps them signed in for 30 days.
 *
 * An account is created the first time a link is used, so asking for a link never tells anyone whether an address is registered.
 * What an account holds: events saved from any device, and the list of price alerts already tied to the address. Orders are placed and
 * managed with the ticket partner at checkout; nothing about orders or payment is stored here.
 *
 * Every function that touches the database uses prepared statements. Secrets are never logged.
 */

const SO_ACCT_LINK_MINUTES = 15;
const SO_ACCT_SESSION_DAYS = 30;
const SO_ACCT_COOKIE = 'so_session';
const SO_ACCT_MAX_SAVED = 100;

function soAcctH($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

/** Every redirect from the account pages carries no-store, so a shared cache can never replay one visitor's redirect to another. */
function soAcctRedirect(string $to, int $code = 302): void {
    header('Cache-Control: no-store');
    header('Location: ' . $to, true, $code);
    exit;
}

/** Forget what is no longer needed: links older than a day (the rate limit looks back one hour) and expired sessions. */
function soAcctPrune(): void {
    MYSQLI->query('DELETE FROM login_links WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    MYSQLI->query('DELETE FROM user_sessions WHERE expires_at < NOW()');
}

function soAcctNewToken(): string { return bin2hex(random_bytes(32)); }

function soAcctHash(string $secret): string { return hash('sha256', $secret); }

/** A valid, lower-cased address of at most 254 characters, or '' when it is not one. */
function soAcctEmail($raw): string {
    $e = strtolower(trim((string) $raw));
    if ($e === '' || strlen($e) > 254 || preg_match('/[\x00-\x1f\x7f<>\s,;"\\\\]/', $e)) return '';
    return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : '';
}

/** Where to go after signing in: a path on this site only. Anything else (another host, //host, a scheme, a backslash) becomes /account. */
function soAcctSafeNext($next): string {
    $n = (string) $next;
    if ($n === '' || strlen($n) > 255 || $n[0] !== '/' || strpos($n, '//') === 0 || strpos($n, '\\') !== false || preg_match('/[\x00-\x1f\x7f]/', $n)) return '/account';
    if (preg_match('#^/(login|register|account-verify)(\?|$)#', $n)) return '/account';
    return $n;
}

function soAcctIpHash(): string {
    $ip = function_exists('soClientIp') ? (string) soClientIp() : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return hash('sha256', 'so-acct|' . (string) getenv('DB_PASS') . '|' . $ip);
}

/** Links in the sign-in email use the public address (seatoutlet.com), not HOME_URL: on the live install HOME_URL can be the beta host, and the session cookie would then be set on the wrong site. */
function soAcctBaseUrl(): string {
    $base = defined('SO_PUBLIC_ORIGIN') ? (string) SO_PUBLIC_ORIGIN : (defined('HOME_URL') ? (string) HOME_URL : '');
    return rtrim($base, '/');
}

function soAcctLinkUrl(string $token): string { return soAcctBaseUrl() . '/account-verify?t=' . rawurlencode($token); }

/** A masked address for the confirmation page ("j***@gmail.com"): enough to recognise, not enough to harvest. */
function soAcctMask(string $email): string {
    $at = strrpos($email, '@');
    if ($at === false) return '';
    $local = substr($email, 0, $at);
    return substr($local, 0, 1) . str_repeat('*', max(2, min(6, strlen($local) - 1))) . substr($email, $at);
}

/* ---------- Asking for a link ---------- */

/**
 * Is another link allowed for this address and visitor? One a minute per address, 5 an hour per address, 20 an hour per visitor.
 * @return string '' when allowed, else 'wait' (too soon) or 'limit' (too many)
 */
function soAcctRateCheck(string $email, string $ipHash): string {
    $db = MYSQLI;
    $q = function (string $sql, string $a, int $secs) use ($db): int {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $a, $secs);
        $stmt->execute();
        $stmt->bind_result($n);
        $stmt->fetch();
        $stmt->close();
        return (int) $n;
    };
    if ($q('SELECT COUNT(*) FROM login_links WHERE email = ? AND created_at > (NOW() - INTERVAL ? SECOND)', $email, 60) > 0) return 'wait';
    if ($q('SELECT COUNT(*) FROM login_links WHERE email = ? AND created_at > (NOW() - INTERVAL ? SECOND)', $email, 3600) >= 5) return 'limit';
    if ($q('SELECT COUNT(*) FROM login_links WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL ? SECOND)', $ipHash, 3600) >= 20) return 'limit';
    return '';
}

/** The sign-in email. $send is injectable so tests never need a mail server. */
function soAcctSendLinkMail(string $email, string $url, ?callable $send = null): bool {
    $mins = SO_ACCT_LINK_MINUTES;
    $text = "Hi,\n\nUse this link to sign in to Seat Outlet. It works once and expires in $mins minutes:\n\n$url\n\n"
        . "If you did not ask for it, you can ignore this email: nobody can sign in without the link.\n\nSeat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.\n";
    $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#1c2333;max-width:560px">'
        . '<p>Hi,</p><p>Use this button to sign in to Seat Outlet. It works once and expires in ' . $mins . ' minutes.</p>'
        . '<p><a href="' . soAcctH($url) . '" style="display:inline-block;background:#2556e0;color:#fff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:10px">Sign in to Seat Outlet</a></p>'
        . '<p style="font-size:13px;color:#5b6478">Or paste this address into your browser:<br>' . soAcctH($url) . '</p>'
        . '<p style="color:#5b6478;font-size:13px">If you did not ask for it, you can ignore this email: nobody can sign in without the link.<br>Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.</p></div>';
    if ($send === null && isset($GLOBALS['soAcctMailer']) && is_callable($GLOBALS['soAcctMailer'])) $send = $GLOBALS['soAcctMailer'];
    if ($send !== null) return (bool) $send($email, 'Your Seat Outlet sign-in link', $html, $text);
    try {
        require_once __DIR__ . '/contact.php';
        $mail = soContactMailer();
        $mail->setFrom(SO_CONTACT_SUPPORT, 'Seat Outlet');
        $mail->addAddress($email);
        $mail->addReplyTo(SO_CONTACT_SUPPORT, 'Seat Outlet');
        $mail->isHTML(true);
        $mail->Subject = 'Your Seat Outlet sign-in link';
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('account: sign-in mail failed: ' . mb_substr($e->getMessage(), 0, 160));   // never the address or the link
        return false;
    }
}

/**
 * Store a link and email it.
 * @return array{status:string} 'sent' | 'invalid' | 'wait' | 'limit' | 'error'. 'sent' is also the answer for an address nobody has used before.
 */
function soAcctRequestLink($emailRaw, $nextRaw = '', ?callable $send = null): array {
    $email = soAcctEmail($emailRaw);
    if ($email === '') return ['status' => 'invalid'];
    if (random_int(1, 20) === 1) soAcctPrune();
    $ipHash = soAcctIpHash();
    $rate = soAcctRateCheck($email, $ipHash);
    if ($rate !== '') return ['status' => $rate];
    $token = soAcctNewToken();
    $hash = soAcctHash($token);
    $next = soAcctSafeNext($nextRaw);
    $mins = SO_ACCT_LINK_MINUTES;
    $stmt = MYSQLI->prepare('INSERT INTO login_links (email, token_hash, next_path, ip_hash, expires_at) VALUES (?, ?, ?, ?, NOW() + INTERVAL ? MINUTE)');
    $stmt->bind_param('ssssi', $email, $hash, $next, $ipHash, $mins);
    $stmt->execute();
    $stmt->close();
    if (!soAcctSendLinkMail($email, soAcctLinkUrl($token), $send)) {
        $stmt = MYSQLI->prepare('DELETE FROM login_links WHERE token_hash = ?');
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
        return ['status' => 'error'];
    }
    return ['status' => 'sent'];
}

/**
 * The account tables are missing or the database refused a sign-in query (for example db/migrate.php has not been run since 0047).
 * Signing in must not take a page down with a 500: log it, tell Sentry once an hour as a warning, and let the caller treat it as "no".
 */
function soAcctDbFail(Throwable $e): void {
    error_log('accounts: ' . $e->getMessage());
    if (!function_exists('\\Sentry\\captureMessage')) return;
    $flag = sys_get_temp_dir() . '/so_acct_dbfail_' . md5(__FILE__);
    if (@filemtime($flag) > time() - 3600) return;
    $lock = @fopen($flag . '.lock', 'c');   // check and touch under a lock: concurrent failures send one warning, not a burst
    if ($lock) @flock($lock, LOCK_EX);
    if (@filemtime($flag) > time() - 3600) { if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); } return; }
    @touch($flag);
    if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); }
    \Sentry\captureMessage('Accounts unavailable (run db/migrate.php if migration 0047 is not applied): ' . substr($e->getMessage(), 0, 200), \Sentry\Severity::warning());
}

/* ---------- Using a link ---------- */

/** The unused, unexpired link for this token, without using it up (the confirmation page shows it). */
function soAcctPeekLink($token): ?array {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $hash = soAcctHash($token);
    try {
        $stmt = MYSQLI->prepare('SELECT id, email, next_path FROM login_links WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()');
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->bind_result($id, $email, $next);
        $row = $stmt->fetch() ? ['id' => (int) $id, 'email' => (string) $email, 'next' => soAcctSafeNext($next)] : null;
        $stmt->close();
    } catch (Throwable $e) { soAcctDbFail($e); return null; }
    return $row;
}

/**
 * Use the link up and return the person (created on first use). A link can be used exactly once even if two requests race:
 * the UPDATE only succeeds for the request that finds used_at still empty.
 * @return array{user_id:int,email:string,next:string,new:bool}|null
 */
function soAcctConsumeLink($token): ?array {
    $link = soAcctPeekLink($token);
    if ($link === null) return null;
    $stmt = MYSQLI->prepare('UPDATE login_links SET used_at = NOW() WHERE id = ? AND used_at IS NULL AND expires_at > NOW()');
    $stmt->bind_param('i', $link['id']);
    $stmt->execute();
    $won = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$won) return null;
    $stmt = MYSQLI->prepare('INSERT IGNORE INTO users (email) VALUES (?)');
    $stmt->bind_param('s', $link['email']);
    $stmt->execute();
    $isNew = $stmt->affected_rows === 1;
    $stmt->close();
    $stmt = MYSQLI->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $link['email']);
    $stmt->execute();
    $stmt->bind_result($uid);
    $ok = $stmt->fetch();
    $stmt->close();
    if (!$ok) return null;
    $stmt = MYSQLI->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();
    return ['user_id' => (int) $uid, 'email' => $link['email'], 'next' => $link['next'], 'new' => $isNew];
}

/* ---------- Sessions ---------- */

function soAcctCookieOptions(int $expires, string $path = '/'): array {
    return ['expires' => $expires, 'path' => $path, 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
            'httponly' => true, 'samesite' => 'Lax'];
}

/** Start a session for a person and set the cookie. Returns the CSRF value for the page that follows. */
function soAcctSessionStart(int $userId, bool $setCookie = true): array {
    $token = soAcctNewToken();
    $hash = soAcctHash($token);
    $csrf = bin2hex(random_bytes(20));
    $days = SO_ACCT_SESSION_DAYS;
    $stmt = MYSQLI->prepare('INSERT INTO user_sessions (user_id, token_hash, csrf, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL ? DAY)');
    $stmt->bind_param('issi', $userId, $hash, $csrf, $days);
    $stmt->execute();
    $stmt->close();
    if ($setCookie && !headers_sent()) setcookie(SO_ACCT_COOKIE, $token, soAcctCookieOptions(time() + $days * 86400));
    return ['token' => $token, 'csrf' => $csrf];
}

/** The signed-in person for this request, or null. Cached for the request. Pass $cookie to test without a browser. */
function soAcctUser(?string $cookie = null): ?array {
    static $memo = [];
    $tok = $cookie ?? (string) ($_COOKIE[SO_ACCT_COOKIE] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $tok)) return null;
    if (array_key_exists($tok, $memo)) return $memo[$tok];
    $hash = soAcctHash($tok);
    $row = null;
    try {
        $stmt = MYSQLI->prepare('SELECT u.id, u.email, s.csrf, s.id FROM user_sessions s JOIN users u ON u.id = s.user_id WHERE s.token_hash = ? AND s.expires_at > NOW()');
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->bind_result($uid, $email, $csrf, $sid);
        $row = $stmt->fetch() ? ['id' => (int) $uid, 'email' => (string) $email, 'csrf' => (string) $csrf, 'session_id' => (int) $sid] : null;
        $stmt->close();
        if ($row) {   // keep a person who is using the site signed in: touch the row at most once an hour
            $stmt = MYSQLI->prepare('UPDATE user_sessions SET last_seen_at = NOW() WHERE id = ? AND last_seen_at < (NOW() - INTERVAL 1 HOUR)');
            $stmt->bind_param('i', $row['session_id']);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) { soAcctDbFail($e); }
    return $memo[$tok] = $row;
}

function soAcctCsrfOk(?array $user, $posted): bool {
    return $user !== null && is_string($posted) && $posted !== '' && hash_equals($user['csrf'], $posted);
}

function soAcctSignOut(?string $cookie = null): void {
    $tok = $cookie ?? (string) ($_COOKIE[SO_ACCT_COOKIE] ?? '');
    if (preg_match('/^[a-f0-9]{64}$/', $tok)) {
        $hash = soAcctHash($tok);
        $stmt = MYSQLI->prepare('DELETE FROM user_sessions WHERE token_hash = ?');
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $stmt->close();
    }
    if ($cookie === null && !headers_sent()) setcookie(SO_ACCT_COOKIE, '', soAcctCookieOptions(time() - 3600));
}

/** Sign out everywhere, remove the saved events and the account itself (the rows cascade), and forget the address's sign-in links. */
function soAcctDelete(int $userId, string $email): void {
    $stmt = MYSQLI->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $stmt = MYSQLI->prepare('DELETE FROM login_links WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
    if (!headers_sent()) setcookie(SO_ACCT_COOKIE, '', soAcctCookieOptions(time() - 3600));
}

/* ---------- Saved events ---------- */

/**
 * Keep the valid ones of the events a browser sent. The path is rebuilt from the slug (never taken as given), text is plain and
 * cut to length, the date must be a real date. Anything else is dropped.
 * @return array<int,array{id:int,name:string,path:string,date:?string,venue:string,city:string}>
 */
function soAcctCleanEvents($list): array {
    $out = [];
    if (!is_array($list)) return $out;
    foreach (array_slice($list, 0, SO_ACCT_MAX_SAVED) as $x) {
        if (!is_array($x)) continue;
        $id = isset($x['id']) && is_scalar($x['id']) && preg_match('/^\d{1,12}$/', (string) $x['id']) ? (int) $x['id'] : 0;
        $slug = isset($x['slug']) && is_string($x['slug']) && preg_match('/^[a-z0-9][a-z0-9-]{2,200}$/', $x['slug']) ? $x['slug'] : '';
        $name = trim(strip_tags(is_string($x['name'] ?? null) ? $x['name'] : ''));
        if ($id <= 0 || $slug === '' || $name === '' || isset($out[$id])) continue;   // the browser lists the newest first: the first copy of an event wins
        $date = null;
        if (isset($x['date']) && is_string($x['date']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $x['date'], $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) $date = "$m[1]-$m[2]-$m[3]";
        $clip = function ($v, int $n): string { return mb_substr(trim(strip_tags(is_string($v) ? $v : '')), 0, $n); };
        $out[$id] = ['id' => $id, 'name' => $clip($name, 160), 'path' => '/event/' . $slug, 'date' => $date, 'venue' => $clip($x['venue'] ?? '', 120), 'city' => $clip($x['city'] ?? '', 80)];
    }
    return array_values($out);
}

/**
 * Save events for a person, at most SO_ACCT_MAX_SAVED in all. The person's row is locked for the length of the import, so two devices
 * importing at once cannot both pass the cap check; the saved ids are read once and counted as events go in.
 * @return array{saved:int,skipped:int} saved = added or refreshed, skipped = left out because the account is full
 */
function soAcctSaveEvents(int $userId, array $events): array {
    $db = MYSQLI;
    $saved = 0; $skipped = 0;
    $db->begin_transaction();
    try {
        $stmt = $db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->store_result();
        $found = $stmt->num_rows > 0;
        $stmt->close();
        if (!$found) { $db->rollback(); return ['saved' => 0, 'skipped' => count($events)]; }
        $have = [];
        $stmt = $db->prepare('SELECT event_id FROM user_saved_events WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($eid);
        while ($stmt->fetch()) $have[(int) $eid] = true;
        $stmt->close();
        $stmt = $db->prepare('INSERT INTO user_saved_events (user_id, event_id, name, path, event_date, venue, city) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE name = VALUES(name), path = VALUES(path), event_date = VALUES(event_date), venue = VALUES(venue), city = VALUES(city)');
        foreach ($events as $e) {
            $isNew = !isset($have[$e['id']]);
            if ($isNew && count($have) >= SO_ACCT_MAX_SAVED) { $skipped++; continue; }
            $stmt->bind_param('iisssss', $userId, $e['id'], $e['name'], $e['path'], $e['date'], $e['venue'], $e['city']);
            $stmt->execute();
            if ($isNew) $have[$e['id']] = true;
            $saved++;
        }
        $stmt->close();
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
    return ['saved' => $saved, 'skipped' => $skipped];
}

function soAcctSavedCount(int $userId): int {
    $stmt = MYSQLI->prepare('SELECT COUNT(*) FROM user_saved_events WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($n);
    $stmt->fetch();
    $stmt->close();
    return (int) $n;
}

function soAcctRemoveSaved(int $userId, int $eventId): void {
    $stmt = MYSQLI->prepare('DELETE FROM user_saved_events WHERE user_id = ? AND event_id = ?');
    $stmt->bind_param('ii', $userId, $eventId);
    $stmt->execute();
    $stmt->close();
}

/** Saved events, soonest first (undated last). Past events are listed too; the page marks them. */
function soAcctSavedList(int $userId): array {
    $stmt = MYSQLI->prepare('SELECT event_id, name, path, event_date, venue, city FROM user_saved_events WHERE user_id = ? ORDER BY event_date IS NULL, event_date ASC, saved_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($id, $name, $path, $date, $venue, $city);
    $rows = [];
    while ($stmt->fetch()) $rows[] = ['id' => (int) $id, 'name' => (string) $name, 'path' => (string) $path, 'date' => $date, 'venue' => (string) $venue, 'city' => (string) $city];
    $stmt->close();
    return $rows;
}

/** The price and new-date alerts already tied to this address (set up with the "notify me" forms), with the link that turns each off. */
function soAcctAlerts(string $email): array {
    $rows = [];
    try {
        $stmt = MYSQLI->prepare('SELECT i.interest_type, i.interest_name, l.unsubscribe_token, l.unsubscribed_at FROM leads l JOIN lead_interests i ON i.lead_id = l.id WHERE l.email = ? ORDER BY i.created_at DESC LIMIT 100');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($type, $name, $tok, $unsub);
        while ($stmt->fetch()) $rows[] = ['type' => (string) $type, 'name' => (string) $name, 'unsubscribe' => $tok ? soAcctBaseUrl() . '/unsubscribe?t=' . rawurlencode((string) $tok) : '', 'off' => $unsub !== null];
        $stmt->close();
    } catch (Throwable $e) {
        return [];   // the leads tables are not there (older database): show no alerts rather than fail the page
    }
    return $rows;
}
