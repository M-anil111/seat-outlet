<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Email lead capture: the server half of soLeadForm() (inc/leads.php) and js/lead-capture.js.
 *
 * One entry point, soLeadSubmit(), used by /ajax/subscribe.php and by the two legacy endpoints
 * (newsletter-email.php, ajax/check-email.php). Order of work, on purpose:
 *   1. validate and normalize the input, rate limit per address and per email
 *   2. verify the reCAPTCHA v3 token on the server
 *   3. INSERT INTO leads first (a duplicate email adds an interest and answers 'duplicate')
 *   4. only then mail: the welcome email and the Brevo upsert run after the JSON answer was sent,
 *      are best-effort, and are logged on failure. A mail outage can never lose a lead or break the page.
 *
 * Statuses: success | duplicate | invalid | recaptcha | rate | error
 */
require_once __DIR__ . '/request-guard.php';

const SO_LEAD_INTEREST_TYPES = ['performer', 'event', 'city', 'category'];

/** Trim, drop control characters, collapse spaces, cut to $max characters. */
function soLeadText($v, $max) {
    $v = is_string($v) ? $v : '';
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';
    $v = trim(preg_replace('/\s+/u', ' ', $v) ?? '');
    return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
}

/** A person's name: no markup, no URL-looking text. Stored as plain text; every output escapes it again. */
function soLeadName($v) {
    $v = soLeadText(strip_tags((string) $v), 70);
    if (preg_match('#https?:|www\.|@#i', $v)) return '';
    return $v;
}

/** @return string|null normalized (lower case) email, or null when it is not a usable address */
function soLeadEmail($v) {
    $v = is_string($v) ? trim($v) : '';
    if ($v === '' || strlen($v) > 254 || preg_match('/[\x00-\x1F\x7F\s,;<>"\']/', $v)) return null;
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) return null;
    $domain = substr($v, strrpos($v, '@') + 1);
    if (strpos($domain, '.') === false) return null;
    return strtolower($v);
}

/** Path of the page the form sat on (never a full URL, never a query string). */
function soLeadPage($v) {
    $v = soLeadText($v, 255);
    if ($v === '' || $v[0] !== '/' || substr($v, 0, 2) === '//') return '';
    $q = strpos($v, '?');
    return $q === false ? $v : substr($v, 0, $q);
}

/**
 * Server-side reCAPTCHA v3 check. A token that was already verified a moment ago (same visitor, legacy two-step form:
 * check-email.php then newsletter-email.php) is accepted again for 10 minutes, because Google lets a token be verified once.
 * Local development: SO_RECAPTCHA_SKIP=1 skips the call, but only when the secret is the placeholder 'dummy' or the site is not indexable.
 */
function soLeadVerifyRecaptcha($token, $ip, $actions = null) {
    $actions = is_array($actions) ? $actions : ['lead', 'newsletter'];
    if (getenv('SO_RECAPTCHA_SKIP') === '1' && (RECAPTCHA_SECRET_KEY === 'dummy' || !SITE_INDEXABLE)) return true;
    $token = is_string($token) ? $token : '';
    if ($token === '' || strlen($token) > 4096) return false;
    $memo = rtrim(sys_get_temp_dir(), '/') . '/so_rc_' . substr(hash('sha256', $token), 0, 32);
    if (is_file($memo) && (time() - (int) @filemtime($memo)) < 600) return true;
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => RECAPTCHA_SECRET_KEY, 'response' => $token, 'remoteip' => $ip]),
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $r = $raw !== false ? json_decode((string) $raw, true) : null;
    if (!is_array($r) || empty($r['success'])) return false;
    if (isset($r['action']) && !in_array($r['action'], $actions, true)) return false;
    if (isset($r['score']) && (float) $r['score'] < 0.3) return false;
    @file_put_contents($memo, '1');
    if (mt_rand(1, 40) === 1) {   // sweep old markers
        foreach (glob(rtrim(sys_get_temp_dir(), '/') . '/so_rc_*') ?: [] as $f) { if (time() - (int) @filemtime($f) > 900) @unlink($f); }
    }
    return true;
}

function soLeadNewToken() {
    return bin2hex(random_bytes(20));
}

/** The lead's unsubscribe token, created on first use for rows that do not have one yet (imported rows). */
function soLeadEnsureToken($leadId) {
    $db = MYSQLI;
    $leadId = (int) $leadId;
    $stmt = $db->prepare('SELECT unsubscribe_token FROM leads WHERE id = ?');
    $stmt->bind_param('i', $leadId);
    $stmt->execute();
    $stmt->bind_result($tok);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) return '';
    if ($tok) return $tok;
    $tok = soLeadNewToken();
    $stmt = $db->prepare('UPDATE leads SET unsubscribe_token = ? WHERE id = ? AND unsubscribe_token IS NULL');
    $stmt->bind_param('si', $tok, $leadId);
    $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare('SELECT unsubscribe_token FROM leads WHERE id = ?');
    $stmt->bind_param('i', $leadId);
    $stmt->execute();
    $stmt->bind_result($tok);
    $stmt->fetch();
    $stmt->close();
    return (string) $tok;
}

function soLeadUnsubscribeUrl($token) {
    return rtrim(HOME_URL, '/') . '/unsubscribe?t=' . rawurlencode($token);
}

/** Add (or leave alone) one interest for a lead. */
function soLeadAddInterest($leadId, $type, $id, $name, $source, $page) {
    if (!in_array($type, SO_LEAD_INTEREST_TYPES, true)) return;
    $stmt = MYSQLI->prepare('INSERT IGNORE INTO lead_interests (lead_id, interest_type, interest_id, interest_name, source, page) VALUES (?, ?, ?, ?, ?, ?)');
    $leadId = (int) $leadId; $id = (int) $id;
    $stmt->bind_param('isisss', $leadId, $type, $id, $name, $source, $page);
    $stmt->execute();
    $stmt->close();
}

/**
 * Handle one sign-up.
 *
 * @param array $in  email, fname, lname, source, interest_type, interest_id, interest_name, page, token, [recaptcha_actions]
 * @return array     status, message, lead_id, new (bool), after (callable|null: the mail/CRM work to run once the answer has been sent)
 */
function soLeadSubmit(array $in) {
    $out = ['status' => 'error', 'message' => 'Something went wrong. Please try again in a minute.', 'lead_id' => 0, 'new' => false, 'after' => null];
    try {
        $email = soLeadEmail($in['email'] ?? '');
        if ($email === null) {
            return ['status' => 'invalid', 'message' => 'Please check your email address and try again.'] + $out;
        }
        $ip = soClientIp();
        // Per address first (cheap, protects Google and the mail account), then per email so one inbox cannot be flooded.
        if (!soRateHit('lead-ip', soIpHash($ip), 12, 3600) || !soRateHit('lead-email', $email, 4, 3600)) {
            return ['status' => 'rate', 'message' => 'Too many tries. Please wait a few minutes and try again.'] + $out;
        }
        if (!soLeadVerifyRecaptcha($in['token'] ?? '', $ip, $in['recaptcha_actions'] ?? ['lead', 'newsletter'])) {
            return ['status' => 'recaptcha', 'message' => 'We could not confirm you are human. Please reload the page and try again.'] + $out;
        }

        $fname = soLeadName($in['fname'] ?? '');
        $lname = soLeadName($in['lname'] ?? '');
        $source = preg_replace('/[^a-z0-9_-]/', '', strtolower(soLeadText($in['source'] ?? '', 40))) ?: 'site';
        $itype = is_string($in['interest_type'] ?? null) && in_array($in['interest_type'], SO_LEAD_INTEREST_TYPES, true) ? $in['interest_type'] : '';
        $iid = $itype === '' ? 0 : max(0, min(2147483647, (int) ($in['interest_id'] ?? 0)));
        $iname = $itype === '' ? '' : soLeadText(strip_tags((string) ($in['interest_name'] ?? '')), 120);
        $page = soLeadPage($in['page'] ?? '');
        $ipHash = soIpHash($ip);
        $token = soLeadNewToken();

        $db = MYSQLI;
        $leadId = 0;
        $isNew = false;
        try {
            $stmt = $db->prepare('INSERT INTO leads (email, fname, lname, source, interest_type, interest_id, interest_name, page, ip_hash, created_at, unsubscribe_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
            $stmt->bind_param('sssssissss', $email, $fname, $lname, $source, $itype, $iid, $iname, $page, $ipHash, $token);
            $stmt->execute();
            $leadId = (int) $stmt->insert_id;
            $stmt->close();
            $isNew = true;
        } catch (\mysqli_sql_exception $e) {
            if ((int) $e->getCode() !== 1062) throw $e;
        }

        if (!$isNew) {
            $stmt = $db->prepare('SELECT id, fname, lname FROM leads WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($leadId, $oldF, $oldL);
            $stmt->fetch();
            $stmt->close();
            $leadId = (int) $leadId;
            if ($leadId > 0 && $itype !== '') soLeadAddInterest($leadId, $itype, $iid, $iname, $source, $page);
            if ($leadId > 0 && ($oldF === '' && $fname !== '' || $oldL === '' && $lname !== '')) {   // fill in a name we did not have
                $stmt = $db->prepare('UPDATE leads SET fname = IF(fname = \'\', ?, fname), lname = IF(lname = \'\', ?, lname) WHERE id = ?');
                $stmt->bind_param('ssi', $fname, $lname, $leadId);
                $stmt->execute();
                $stmt->close();
            }
            return ['status' => 'duplicate', 'message' => 'You are already on the list. Thank you!', 'lead_id' => $leadId, 'new' => false, 'after' => null];
        }

        if ($itype !== '') soLeadAddInterest($leadId, $itype, $iid, $iname, $source, $page);

        $lead = ['id' => $leadId, 'email' => $email, 'fname' => $fname, 'lname' => $lname, 'token' => $token,
                 'interest_type' => $itype, 'interest_id' => $iid, 'interest_name' => $iname, 'source' => $source, 'page' => $page];
        return ['status' => 'success', 'message' => 'You are in. Watch your inbox.', 'lead_id' => $leadId, 'new' => true,
                'after' => function () use ($lead) { soLeadAfterSignup($lead); }];
    } catch (\Throwable $e) {
        error_log('lead signup failed: ' . get_class($e) . ': ' . $e->getMessage());
        return $out;
    }
}

/** Mail and CRM work for a new lead. Never throws; failures are logged. */
function soLeadAfterSignup(array $lead) {
    try {
        require_once __DIR__ . '/lead-mail.php';
        if (soLeadSendWelcome($lead)) {
            $stmt = MYSQLI->prepare('UPDATE leads SET welcome_sent_at = NOW() WHERE id = ?');
            $id = (int) $lead['id'];
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
        soLeadNotifyOwner($lead);
        if (soLeadBrevoUpsert($lead['email'], $lead['fname'], $lead['lname'])) {
            $stmt = MYSQLI->prepare('UPDATE leads SET brevo_synced_at = NOW() WHERE id = ?');
            $id = (int) $lead['id'];
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (\Throwable $e) {
        error_log('lead after-signup work failed: ' . $e->getMessage());
    }
}

/** Send the JSON answer, then run the slow work (mail, CRM) without making the visitor wait for it. */
function soLeadRespond(array $res, $httpStatus = null) {
    $codes = ['success' => 200, 'duplicate' => 200, 'invalid' => 400, 'recaptcha' => 403, 'rate' => 429, 'error' => 500];
    if (!headers_sent()) {
        http_response_code($httpStatus ?: ($codes[$res['status']] ?? 500));
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
    }
    echo json_encode(['status' => $res['status'], 'message' => $res['message']]);
    if (!empty($res['after']) && is_callable($res['after'])) {
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } else {
            @ob_end_flush();
            @flush();
        }
        ignore_user_abort(true);
        @set_time_limit(60);
        call_user_func($res['after']);
    }
}

/**
 * Create or update the contact in Brevo (https://developers.brevo.com/reference/create-contact: POST /v3/contacts,
 * header api-key, body email / attributes / listIds / updateEnabled). Silent no-op when BREVO_API_KEY is unset.
 * Attribute names default to Brevo's FIRSTNAME / LASTNAME; override with BREVO_ATTR_FIRST / BREVO_ATTR_LAST. If Brevo
 * rejects the attributes (the names must exist in the account) the contact is sent again without them.
 */
function soLeadBrevoRequest($method, $path, array $body) {
    $key = (string) getenv('BREVO_API_KEY');
    if ($key === '') return null;
    $ch = curl_init('https://api.brevo.com/v3' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['api-key: ' . $key, 'Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

function soLeadBrevoUpsert($email, $fname, $lname) {
    if ((string) getenv('BREVO_API_KEY') === '') return false;
    $body = ['email' => $email, 'updateEnabled' => true];
    $list = (int) getenv('BREVO_LIST_ID');
    if ($list > 0) $body['listIds'] = [$list];
    $attrs = [];
    if ($fname !== '') $attrs[(string) (getenv('BREVO_ATTR_FIRST') ?: 'FIRSTNAME')] = $fname;
    if ($lname !== '') $attrs[(string) (getenv('BREVO_ATTR_LAST') ?: 'LASTNAME')] = $lname;
    if ($attrs) $body['attributes'] = $attrs;
    $code = soLeadBrevoRequest('POST', '/contacts', $body);
    if ($code === 400 && $attrs) {
        unset($body['attributes']);
        $code = soLeadBrevoRequest('POST', '/contacts', $body);
    }
    if ($code === null || $code < 200 || $code >= 300) {
        error_log('Brevo contact upsert failed (HTTP ' . (int) $code . ')');
        return false;
    }
    return true;
}

/** Tell Brevo not to mail this address (PUT /v3/contacts/{email}, emailBlacklisted=true). */
function soLeadBrevoBlock($email) {
    if ((string) getenv('BREVO_API_KEY') === '') return false;
    $code = soLeadBrevoRequest('PUT', '/contacts/' . rawurlencode($email), ['emailBlacklisted' => true]);
    return $code !== null && $code >= 200 && $code < 300;
}

/**
 * Unsubscribe by token. Returns the email address when the token is known (also when it was already used), else null.
 * Idempotent, so a mail scanner or a double click does no harm.
 */
function soLeadUnsubscribe($token) {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{40}$/', $token)) return null;
    $db = MYSQLI;
    $stmt = $db->prepare('SELECT id, email FROM leads WHERE unsubscribe_token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($id, $email);
    $found = $stmt->fetch();
    $stmt->close();
    if (!$found) return null;
    $stmt = $db->prepare('UPDATE leads SET unsubscribed_at = NOW() WHERE id = ? AND unsubscribed_at IS NULL');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();
    if ($changed) soLeadBrevoBlock($email);
    return $email;
}

/** Undo an unsubscribe made by the same person through the same link ("changed my mind"). */
function soLeadResubscribe($token) {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{40}$/', $token)) return false;
    $stmt = MYSQLI->prepare('UPDATE leads SET unsubscribed_at = NULL WHERE unsubscribe_token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    if ($ok) {
        $stmt = MYSQLI->prepare('SELECT email FROM leads WHERE unsubscribe_token = ?');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();
        if (!empty($email)) soLeadBrevoRequest('PUT', '/contacts/' . rawurlencode($email), ['emailBlacklisted' => false]);
    }
    return $ok;
}
