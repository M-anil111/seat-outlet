<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Contact form, one component for the whole site (/ticket-customer-service and the partnership enquiry on /ticket-partner-program).
 *
 *   require_once __DIR__ . '/inc/contact.php';
 *   soContactRoute();                                   // first thing in the page, before header.php: handles the POST
 *   echo soContactForm(['subject' => 'partnership']);   // where the form goes
 *
 * What the POST handler does, in order: same-origin check, signed form token (stateless, so cached pages work),
 * honeypot, validation and length limits, reCAPTCHA v3 checked on the server (SO_RECAPTCHA_SKIP=1 switches the check
 * off for local development only), rate limits, INSERT into contact_messages, an email to support, an auto-reply to
 * the sender. The message is stored before any email is attempted, so a mail outage never loses a message.
 *
 * Mail uses the same Brevo SMTP relay and SMTP_USER / SMTP_PASS settings as newsletter-email.php. Optional
 * environment settings: CONTACT_TO (default support@seatoutlet.com), CONTACT_TO_PARTNERSHIP (default = CONTACT_TO).
 */

if (!defined('SO_CONTACT_SUPPORT')) { define('SO_CONTACT_SUPPORT', 'support@seatoutlet.com'); }

function soContactH($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

/** Topics in the drop-down: key => label. */
function soContactSubjects(): array {
    return ['order' => 'Order inquiry', 'refund' => 'Refund request', 'technical' => 'Technical support', 'partnership' => 'Partnership', 'other' => 'Other'];
}

function soContactSecret(): string {
    $k = defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : (string) getenv('RECAPTCHA_SECRET_KEY');
    return hash('sha256', 'so-contact|' . $k . '|' . (string) getenv('DB_PASS') . '|' . (defined('HOME_URL') ? HOME_URL : ''));
}

/** Signed timestamp. Stateless on purpose: the page may be served from a shared cache. */
function soContactToken(): string {
    $t = time();
    return $t . '.' . substr(hash_hmac('sha256', 'contact|' . $t, soContactSecret()), 0, 32);
}

/** Valid, and not sent faster than a person can type (3 seconds) or older than a day. */
function soContactTokenOk($token): bool {
    if (!is_string($token) || !preg_match('/^(\d{9,11})\.([a-f0-9]{32})$/', $token, $m)) return false;
    if (!hash_equals(substr(hash_hmac('sha256', 'contact|' . $m[1], soContactSecret()), 0, 32), $m[2])) return false;
    $age = time() - (int) $m[1];
    return $age >= 3 && $age <= 86400;
}

/** The Origin (or Referer) header, when the browser sends one, must be this site. */
function soContactSameOrigin(): bool {
    $own = array_filter([strtolower((string) parse_url(defined('HOME_URL') ? HOME_URL : '', PHP_URL_HOST)), strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')))]);
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
        $v = (string) ($_SERVER[$h] ?? '');
        if ($v === '' || $v === 'null') continue;
        $host = strtolower((string) parse_url($v, PHP_URL_HOST));
        return $host !== '' && in_array($host, $own, true);
    }
    return true;   // no header at all (some privacy settings strip both): the signed token still has to be valid
}

function soContactClientIp(): string { return (string) ($_SERVER['REMOTE_ADDR'] ?? ''); }

function soContactIpHash(): string { return hash('sha256', soContactSecret() . '|' . soContactClientIp()); }

/** One-line text: no control characters, collapsed spaces, capped length. */
function soContactLine($v, int $max): string {
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $v);
    $v = trim(preg_replace('/\s+/u', ' ', (string) $v));
    return mb_substr($v, 0, $max);
}

/** Server-side reCAPTCHA v3 check. Returns [ok, score|null]. */
function soContactRecaptcha(string $token): array {
    if (getenv('SO_RECAPTCHA_SKIP') === '1') return [true, null];
    if ($token === '' || !function_exists('curl_init')) return [false, null];
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => defined('RECAPTCHA_SECRET_KEY') ? RECAPTCHA_SECRET_KEY : (string) getenv('RECAPTCHA_SECRET_KEY'), 'response' => $token, 'remoteip' => soContactClientIp()]),
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $d = $raw !== false ? json_decode((string) $raw, true) : null;
    if (empty($d['success'])) return [false, null];
    $score = isset($d['score']) ? (float) $d['score'] : null;
    if (isset($d['action']) && $d['action'] !== 'contact') return [false, $score];
    return [$score === null || $score >= 0.3, $score];
}

/** Mailer on the same Brevo relay as newsletter-email.php. Throws when SMTP_USER / SMTP_PASS are not set. */
function soContactMailer() {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer', false)) {
        require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../phpmailer/src/Exception.php';
    }
    $user = getenv('SMTP_USER');
    $pass = getenv('SMTP_PASS');
    if ($user === false || $user === '' || $pass === false || $pass === '') {
        throw new RuntimeException('SMTP_USER/SMTP_PASS are not set');
    }
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp-relay.brevo.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 15;
    return $mail;
}

function soContactRecipient(string $subject): string {
    $to = trim((string) getenv('CONTACT_TO')) ?: SO_CONTACT_SUPPORT;
    if ($subject === 'partnership' && trim((string) getenv('CONTACT_TO_PARTNERSHIP')) !== '') $to = trim((string) getenv('CONTACT_TO_PARTNERSHIP'));
    return filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : SO_CONTACT_SUPPORT;
}

/** Email to the support inbox (plain text, so nothing a visitor typed is ever interpreted as markup). */
function soContactSendSupport(int $id, array $m): string {
    try {
        $mail = soContactMailer();
        $mail->setFrom(SO_CONTACT_SUPPORT, 'Seat Outlet website');
        $mail->addAddress(soContactRecipient($m['subject']));
        $mail->addReplyTo($m['email'], $m['name']);
        $mail->isHTML(false);
        $labels = soContactSubjects();
        $mail->Subject = '[Contact form #' . $id . '] ' . ($labels[$m['subject']] ?? 'Message') . ' from ' . $m['name'];
        $mail->Body = "New message from the website contact form (reference C-$id)\n\n"
            . 'Topic: ' . ($labels[$m['subject']] ?? $m['subject']) . "\n"
            . 'Name: ' . $m['name'] . "\n"
            . 'Email: ' . $m['email'] . "\n"
            . 'Phone: ' . ($m['phone'] !== '' ? $m['phone'] : '-') . "\n"
            . 'Order ID: ' . ($m['order_ref'] !== '' ? $m['order_ref'] : '-') . "\n"
            . 'Sent from: ' . $m['page'] . "\n\n"
            . "Message:\n" . $m['message'] . "\n\n"
            . "Reply to this email to answer the sender.\n";
        $mail->send();
        return 'sent';
    } catch (Throwable $e) {
        error_log('contact form: support email failed for #' . $id . ': ' . $e->getMessage());
        return 'support email failed: ' . mb_substr($e->getMessage(), 0, 120);
    }
}

/** Confirmation to the sender. Promises a reply, never a time frame. */
function soContactSendReply(int $id, array $m): string {
    try {
        require_once __DIR__ . '/guarantee.php';
        $first = soContactLine(explode(' ', $m['name'])[0], 60);
        $help = [
            'order'       => 'Your order confirmation email has your order number and the delivery details for your tickets. If you have not already, please reply with the order number and the event name.',
            'refund'      => 'Please reply with your order number if you did not include it. How refunds work is explained on our guarantee page: ' . soGuaranteeSentence() . ' Tickets are not refundable for a change of plans.',
            'technical'   => 'If something on the site is not working, it helps if you reply with the page address and the device and browser you were using.',
            'partnership' => 'Thank you for your interest in working with Seat Outlet. We will read your note and reply by email.',
            'other'       => '',
        ][$m['subject']] ?? '';
        $home = defined('HOME_URL') ? rtrim(HOME_URL, '/') : '';
        $text = "Hi $first,\n\nThanks for contacting Seat Outlet. We received your message (reference C-$id) and a member of our team will reply to you by email.\n\n"
            . ($help !== '' ? $help . "\n\n" : '')
            . "Guarantee and buyer protection: $home/worry-free-guarantee\nCommon questions: $home/ticket-faq\n\n"
            . "If you did not send this message, you can ignore this email.\n\nSeat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.\n";
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.55;color:#1c2333;max-width:560px">'
            . '<p>Hi ' . soContactH($first) . ',</p>'
            . '<p>Thanks for contacting Seat Outlet. We received your message (reference C-' . $id . ') and a member of our team will reply to you by email.</p>'
            . ($help !== '' ? '<p>' . soContactH($help) . '</p>' : '')
            . '<p><a href="' . soContactH($home) . '/worry-free-guarantee">Guarantee and buyer protection</a> &middot; <a href="' . soContactH($home) . '/ticket-faq">Common questions</a></p>'
            . '<p style="color:#5b6478;font-size:13px">If you did not send this message, you can ignore this email.<br>Seat Outlet is an independent resale marketplace and is not affiliated with any venue, team or artist.</p></div>';
        $mail = soContactMailer();
        $mail->setFrom(SO_CONTACT_SUPPORT, 'Seat Outlet');
        $mail->addAddress($m['email'], $m['name']);
        $mail->addReplyTo(SO_CONTACT_SUPPORT, 'Seat Outlet');
        $mail->isHTML(true);
        $mail->Subject = 'We received your message (reference C-' . $id . ')';
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
        return 'sent';
    } catch (Throwable $e) {
        error_log('contact form: auto-reply failed for #' . $id . ': ' . $e->getMessage());
        return 'reply failed: ' . mb_substr($e->getMessage(), 0, 120);
    }
}

/** Validates and stores one submission. Returns ['status' => success|invalid|recaptcha|rate|spam|error, 'message' => string, 'errors' => [field => text], 'values' => [...]]. */
function soContactHandle(array $in): array {
    $labels = soContactSubjects();
    $v = [
        'first'     => soContactLine($in['firstName'] ?? '', 60),
        'last'      => soContactLine($in['lastName'] ?? '', 60),
        'email'     => soContactLine($in['email'] ?? '', 190),
        'phone'     => soContactLine($in['phone'] ?? '', 30),
        'order_ref' => soContactLine($in['orderId'] ?? '', 60),
        'subject'   => strtolower(soContactLine($in['subject'] ?? '', 30)),
        'message'   => trim(preg_replace("/[^\P{C}\n\t]+/u", '', str_replace("\r\n", "\n", (string) ($in['message'] ?? '')))),
    ];
    $v['message'] = mb_substr($v['message'], 0, 3000);
    $keep = $v;
    $result = function ($status, $message, $errors = []) use ($keep) {
        return ['status' => $status, 'message' => $message, 'errors' => $errors, 'values' => $keep];
    };

    if (!soContactSameOrigin() || !soContactTokenOk($in['form_token'] ?? '')) {
        return $result('error', 'This page was open for too long or could not be verified. Please reload the page and send your message again.');
    }
    if (trim((string) ($in['website'] ?? '')) !== '') {
        return $result('spam', 'Thank you. Your message has been sent.');   // bots get a success page and nothing happens
    }

    $e = [];
    if (mb_strlen($v['first']) < 1) $e['firstName'] = 'Enter your first name.';
    if (mb_strlen($v['last']) < 1) $e['lastName'] = 'Enter your last name.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Enter a valid email address, like name@example.com.';
    if ($v['phone'] !== '' && !preg_match('/^[0-9+\-().\s]{7,30}$/', $v['phone'])) $e['phone'] = 'Use digits, spaces and + - ( ) only, or leave the phone number blank.';
    if ($v['order_ref'] !== '' && !preg_match('/^[A-Za-z0-9#\-_. ]{3,60}$/', $v['order_ref'])) $e['orderId'] = 'An order ID has letters, numbers and dashes only.';
    if (!isset($labels[$v['subject']])) $e['subject'] = 'Choose a topic.';
    $len = mb_strlen($v['message']);
    if ($len < 10) $e['message'] = 'Tell us a little more (at least 10 characters).';
    if (mb_strlen((string) ($in['message'] ?? '')) > 3000) $e['message'] = 'Please keep your message under 3,000 characters.';
    if ($e) return $result('invalid', 'Please fix the highlighted fields and try again.', $e);

    [$ok, $score] = soContactRecaptcha(trim((string) ($in['recaptcha_token'] ?? '')));
    if (!$ok) {
        return $result('recaptcha', 'We could not confirm you are not a robot. Please try again, or email us at ' . SO_CONTACT_SUPPORT . '.');
    }

    $db = MYSQLI;
    $ipHash = soContactIpHash();
    $emailLc = mb_strtolower($v['email']);
    $count = function (string $sql, string $types, ...$args) use ($db) {
        $st = $db->prepare($sql);
        if (!$st) return 0;
        if ($types !== '') $st->bind_param($types, ...$args);
        $st->execute();
        $n = (int) ($st->get_result()->fetch_row()[0] ?? 0);
        $st->close();
        return $n;
    };
    try {
        $perIp   = $count('SELECT COUNT(*) FROM contact_messages WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', 's', $ipHash);
        $perMail = $count('SELECT COUNT(*) FROM contact_messages WHERE email = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', 's', $emailLc);
        $recent  = $count('SELECT COUNT(*) FROM contact_messages WHERE ip_hash = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)', 's', $ipHash);
        $all     = $count('SELECT COUNT(*) FROM contact_messages WHERE created_at > (NOW() - INTERVAL 1 HOUR)', '');
    } catch (Throwable $ex) {
        error_log('contact form: rate limit query failed: ' . $ex->getMessage());
        return $result('error', 'Something went wrong on our side. Please email us at ' . SO_CONTACT_SUPPORT . '.');
    }
    if ($perIp >= 5 || $perMail >= 3 || $recent >= 1 || $all >= 300) {
        return $result('rate', 'You have sent several messages recently. Please wait a few minutes and try again, or email us at ' . SO_CONTACT_SUPPORT . '.');
    }

    $name  = trim($v['first'] . ' ' . $v['last']);
    $page  = soContactLine(parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/', 190);
    $agent = soContactLine($_SERVER['HTTP_USER_AGENT'] ?? '', 255);
    $phone = $v['phone'] !== '' ? $v['phone'] : null;
    $oref  = $v['order_ref'] !== '' ? $v['order_ref'] : null;
    try {
        $st = $db->prepare('INSERT INTO contact_messages (created_at, name, email, phone, order_ref, subject, message, page, ip_hash, user_agent, recaptcha_score) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $st->bind_param('sssssssssd', $name, $emailLc, $phone, $oref, $v['subject'], $v['message'], $page, $ipHash, $agent, $score);
        $st->execute();
        $id = (int) $db->insert_id;
        $st->close();
    } catch (Throwable $ex) {
        error_log('contact form: insert failed: ' . $ex->getMessage());
        return $result('error', 'Something went wrong on our side and your message was not saved. Please email us at ' . SO_CONTACT_SUPPORT . '.');
    }

    $m = ['name' => $name, 'email' => $emailLc, 'phone' => (string) $phone, 'order_ref' => (string) $oref, 'subject' => $v['subject'], 'message' => $v['message'], 'page' => $page];
    $statusSupport = soContactSendSupport($id, $m);
    $statusReply = soContactSendReply($id, $m);
    try {
        $note = mb_substr('support: ' . $statusSupport . ' | reply: ' . $statusReply, 0, 190);
        $st = $db->prepare('UPDATE contact_messages SET email_status = ? WHERE id = ?');
        $st->bind_param('si', $note, $id);
        $st->execute();
        $st->close();
    } catch (Throwable $ex) { /* the message itself is stored; the status note is a convenience */ }

    $msg = 'Thank you. Your message has been sent and a member of our team will reply to you by email.'
        . ($statusReply === 'sent' ? ' We also emailed you a confirmation. If you do not see it, check your spam folder.' : '');
    return ['status' => 'success', 'message' => $msg, 'errors' => [], 'values' => [], 'id' => $id, 'email' => $emailLc];
}

/**
 * Call first thing in a page that shows the form. On a POST from the form it handles it: a fetch() request gets JSON,
 * a plain browser POST re-renders the page with the result (stored in $GLOBALS['soContactResult']). Nothing is ever put
 * in the URL.
 */
function soContactRoute(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['so_contact'])) return;
    $GLOBALS['pageNoCache'] = true;
    $wantsJson = stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
    if (!defined('MYSQLI')) { require_once __DIR__ . '/../functions.php'; }
    $res = soContactHandle($_POST);
    if ($wantsJson) {
        http_response_code($res['status'] === 'success' || $res['status'] === 'spam' ? 200 : ($res['status'] === 'rate' ? 429 : ($res['status'] === 'error' ? 400 : 422)));
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: private, no-store');
        echo json_encode(['status' => $res['status'] === 'spam' ? 'success' : $res['status'], 'message' => $res['message'], 'errors' => $res['errors']]);
        exit;
    }
    if (!in_array($res['status'], ['success', 'spam'], true)) http_response_code($res['status'] === 'rate' ? 429 : 422);
    $GLOBALS['soContactResult'] = $res;
}

/**
 * The form. Options: subject (preselect a topic and hide the chooser), title, intro, button, idp (id prefix, needed when
 * two forms share a page), class.
 */
function soContactForm(array $o = []): string {
    $o += ['subject' => '', 'title' => 'Send a Message', 'intro' => 'Fill out the form and a member of our team will reply to you by email.', 'button' => 'Send Message', 'idp' => 'cf', 'class' => ''];
    $res = $GLOBALS['soContactResult'] ?? null;
    $p = $o['idp'];
    $labels = soContactSubjects();
    $fixed = $o['subject'] !== '' && isset($labels[$o['subject']]);
    if ($res && $res['status'] === 'success' || $res && $res['status'] === 'spam') {
        return '<div class="so-cf so-cf--done ' . soContactH($o['class']) . '" id="' . $p . '-wrap"><div class="so-cf__ok" role="status" tabindex="-1"><h2>Message sent</h2><p>' . soContactH($res['message']) . '</p>'
            . '<p>We also emailed you a confirmation. If you do not see it, check your spam folder.</p></div></div>';
    }
    $val = $res['values'] ?? [];
    $err = $res['errors'] ?? [];
    $sel = $fixed ? $o['subject'] : (string) ($val['subject'] ?? '');
    $f = function (string $key) use ($val) { return soContactH($val[$key] ?? ''); };
    $field = function (string $name, string $label, string $input, string $hint = '') use ($err, $p) {
        $has = isset($err[$name]);
        return '<div class="form-group so-cf__field' . ($has ? ' has-error' : '') . '"><label for="' . $p . '-' . $name . '">' . $label . '</label>' . $input
            . ($hint !== '' ? '<small class="so-cf__hint" id="' . $p . '-' . $name . '-hint">' . $hint . '</small>' : '')
            . '<small class="so-cf__err" id="' . $p . '-' . $name . '-err"' . ($has ? ' role="alert"' : ' hidden') . '>' . ($has ? soContactH($err[$name]) : '') . '</small></div>';
    };
    $attrs = function (string $name, string $extra = '') use ($err, $p) {
        $d = $p . '-' . $name . '-err';
        return ' id="' . $p . '-' . $name . '" name="' . $name . '" aria-describedby="' . $d . '"' . (isset($err[$name]) ? ' aria-invalid="true"' : '') . $extra;
    };
    $opts = '<option value="">Select a topic</option>';
    foreach ($labels as $k => $l) { $opts .= '<option value="' . $k . '"' . ($sel === $k ? ' selected' : '') . '>' . soContactH($l) . '</option>'; }

    $h = '<div class="so-cf ' . soContactH($o['class']) . '" id="' . $p . '-wrap">';
    $h .= '<form class="contact-form so-cf__form" method="post" action="' . soContactH(strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?')) . '" novalidate data-prefix="' . $p . '" autocomplete="on">';
    $h .= '<h2>' . soContactH($o['title']) . '</h2><p class="subtitle">' . soContactH($o['intro']) . '</p>';
    $h .= '<div class="so-cf__alert" id="' . $p . '-alert" role="alert" tabindex="-1"' . ($res ? '' : ' hidden') . '>' . ($res ? soContactH($res['message']) : '') . '</div>';
    $h .= '<input type="hidden" name="so_contact" value="1"><input type="hidden" name="form_token" value="' . soContactH(soContactToken()) . '"><input type="hidden" name="recaptcha_token" value="">';
    $h .= '<div class="so-cf__hp" aria-hidden="true"><label for="' . $p . '-website">Leave this field empty</label><input type="text" id="' . $p . '-website" name="website" value="" tabindex="-1" autocomplete="off"></div>';
    $h .= '<div class="row g-3 form-row"><div class="col-12 col-sm-6">' . $field('firstName', 'First name', '<input type="text"' . $attrs('firstName', ' class="form-control" required maxlength="60" autocomplete="given-name" value="' . $f('first') . '"') . '>') . '</div>';
    $h .= '<div class="col-12 col-sm-6">' . $field('lastName', 'Last name', '<input type="text"' . $attrs('lastName', ' class="form-control" required maxlength="60" autocomplete="family-name" value="' . $f('last') . '"') . '>') . '</div></div>';
    $h .= '<div class="row g-3 form-row mt-1"><div class="col-12 col-sm-6">' . $field('email', 'Email address', '<input type="email" inputmode="email"' . $attrs('email', ' class="form-control" required maxlength="190" autocomplete="email" value="' . $f('email') . '"') . '>') . '</div>';
    $h .= '<div class="col-12 col-sm-6">' . $field('phone', 'Phone number (optional)', '<input type="tel" inputmode="tel"' . $attrs('phone', ' class="form-control" maxlength="30" autocomplete="tel" value="' . $f('phone') . '"') . '>') . '</div></div>';
    if (!$fixed) {
        $h .= '<div class="mt-2">' . $field('orderId', 'Order ID (optional)', '<input type="text"' . $attrs('orderId', ' class="form-control" maxlength="60" autocomplete="off" value="' . $f('order_ref') . '"'), 'It is in your order confirmation email.') . '</div>';
        $h .= $field('subject', 'Topic', '<select' . $attrs('subject', ' class="form-control" required') . '>' . $opts . '</select>');
    } else {
        $h .= '<input type="hidden" name="subject" value="' . soContactH($o['subject']) . '">';
    }
    $h .= $field('message', 'Message', '<textarea' . $attrs('message', ' class="form-control" required minlength="10" maxlength="3000" rows="5"') . '>' . soContactH($val['message'] ?? '') . '</textarea>');
    $h .= '<button type="submit" class="submit-btn btn w-100 so-cf__btn">' . soContactH($o['button']) . '</button>';
    $h .= '<p class="so-cf__legal">We use your details only to answer your message. See our <a href="/privacy-policy">privacy policy</a>. This form is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms of Service</a> apply.</p>';
    $h .= '<noscript><p class="so-cf__legal">This form needs JavaScript to verify you are not a robot. If it is turned off, email us at <a href="mailto:' . SO_CONTACT_SUPPORT . '">' . SO_CONTACT_SUPPORT . '</a>.</p></noscript>';
    return $h . '</form></div>';
}
