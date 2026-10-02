<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Mail for the lead system: the welcome email, the owner notification and the layout the alert emails reuse
 * (cron/send-alerts.php). Transport is Brevo SMTP through PHPMailer (SMTP_USER / SMTP_PASS in inc/env.local.php).
 *
 * Every marketing mail carries a visible one-click unsubscribe link, List-Unsubscribe headers (RFC 8058 one-click) and, when
 * SO_MAIL_ADDRESS is set, the physical mailing address US law (CAN-SPAM) asks for. The address is never invented here:
 * with SO_MAIL_ADDRESS unset the line is left out and tools/check-env.php reports it.
 */
use PHPMailer\PHPMailer\PHPMailer;

function soMailEsc($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** @return PHPMailer|null null when SMTP is not configured (local development) */
function soMailer() {
    $dump = (string) getenv('SO_MAIL_DUMP');   // development: write each message to this folder instead of sending it
    if ($dump !== '' && is_dir($dump)) {
        require_once __DIR__ . '/so-mailer.php';
        $m = new SoMailer(true);
        $m->CharSet = 'UTF-8';
        return $m;
    }
    $user = getenv('SMTP_USER');
    $pass = getenv('SMTP_PASS');
    if ($user === false || $user === '' || $pass === false || $pass === '') {
        error_log('mail skipped: SMTP_USER / SMTP_PASS are not set');
        return null;
    }
    require_once __DIR__ . '/so-mailer.php';
    $m = new SoMailer(true);
    $m->isSMTP();
    $m->Host = (string) (getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com');
    $m->SMTPAuth = true;
    $m->Username = $user;
    $m->Password = $pass;
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $m->Port = (int) (getenv('SMTP_PORT') ?: 587);
    $m->CharSet = 'UTF-8';
    $m->Timeout = 20;
    return $m;
}

/** Send, or in SO_MAIL_DUMP mode write the finished MIME message to a file. */
function soMailSend(PHPMailer $m) {
    $dump = (string) getenv('SO_MAIL_DUMP');
    if ($dump !== '' && is_dir($dump)) {
        $m->preSend();
        file_put_contents(rtrim($dump, '/') . '/' . gmdate('His') . '-' . bin2hex(random_bytes(3)) . '.eml', $m->getSentMIMEMessage());
        return;
    }
    $m->send();
}

function soMailFrom() {
    return [(string) (getenv('SO_MAIL_FROM') ?: 'support@seatoutlet.com'), 'Seat Outlet'];
}

/** The postal address line for mail footers, or '' when not configured. */
function soMailAddress() {
    return trim(preg_replace('/\s+/', ' ', (string) getenv('SO_MAIL_ADDRESS')));
}

/** Wrap a body in the shared email layout (table based, inline styles, works in Gmail and Outlook). */
function soMailLayout($title, $bodyHtml, $unsubUrl) {
    $home = rtrim(HOME_URL, '/');
    $addr = soMailAddress();
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . soMailEsc($title) . '</title></head>'
        . '<body style="margin:0;padding:20px 0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#1f2937">'
        . '<table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:8px">'
        . '<tr><td align="center" style="padding:28px 20px 18px;border-bottom:1px solid #e5e7eb"><a href="' . soMailEsc($home) . '/"><img src="' . soMailEsc($home) . '/images/seatoutlet.png" alt="Seat Outlet" width="180" style="display:block;max-width:180px;height:auto;border:0"></a></td></tr>'
        . '<tr><td style="padding:26px 28px;font-size:15px;line-height:1.6;color:#374151">' . $bodyHtml . '</td></tr>'
        . '<tr><td style="padding:18px 28px 26px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280">'
        . 'Seat Outlet is a ticket resale marketplace. We are not the box office or the venue.<br>'
        . ($addr !== '' ? soMailEsc($addr) . '<br>' : '')
        . 'You are getting this email because you asked for ticket alerts or news on seatoutlet.com. '
        . '<a href="' . soMailEsc($unsubUrl) . '" style="color:#1b3bb0;text-decoration:underline">Unsubscribe in one click</a>'
        . ' &middot; <a href="' . soMailEsc($home) . '/privacy-policy" style="color:#1b3bb0">Privacy policy</a>'
        . '</td></tr></table></body></html>';
}

/** Plain-text footer matching soMailLayout(). */
function soMailTextFooter($unsubUrl) {
    $addr = soMailAddress();
    return "\n\n--\nSeat Outlet is a ticket resale marketplace. We are not the box office or the venue.\n"
        . ($addr !== '' ? $addr . "\n" : '')
        . "Unsubscribe in one click: " . $unsubUrl . "\n";
}

function soMailButton($url, $label) {
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0"><tr><td bgcolor="#1b3bb0" style="border-radius:6px">'
        . '<a href="' . soMailEsc($url) . '" style="display:inline-block;padding:13px 26px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none">' . soMailEsc($label) . '</a></td></tr></table>';
}

/**
 * Send one marketing/alert mail with the unsubscribe headers. Returns true on success, false (and logs) on any failure.
 * @param array $lead email, fname, lname
 */
function soLeadSendMail(array $lead, $subject, $html, $text, $unsubUrl) {
    $m = soMailer();
    if (!$m) return false;
    try {
        [$fromAddr, $fromName] = soMailFrom();
        $m->setFrom($fromAddr, $fromName);
        $name = trim(($lead['fname'] ?? '') . ' ' . ($lead['lname'] ?? ''));
        $m->addAddress($lead['email'], preg_replace('/[\r\n]+/', ' ', $name));
        $m->Subject = $subject;
        $m->isHTML(true);
        $m->Body = $html;
        $m->AltBody = $text;
        $m->addCustomHeader('List-Unsubscribe', '<' . $unsubUrl . '>');
        $m->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        soMailSend($m);
        return true;
    } catch (\Throwable $e) {
        error_log('lead mail failed: ' . $e->getMessage());
        return false;
    }
}

function soLeadSendWelcome(array $lead) {
    $home = rtrim(HOME_URL, '/');
    $unsub = soLeadUnsubscribeUrl($lead['token']);
    $first = trim((string) ($lead['fname'] ?? ''));
    $greet = $first !== '' ? 'Hi ' . soMailEsc($first) . ',' : 'Hi there,';
    $greetText = $first !== '' ? 'Hi ' . $first . ',' : 'Hi there,';

    $interestHtml = '';
    $interestText = '';
    $cta = $home . '/';
    $ctaLabel = 'Browse upcoming events';
    if (($lead['interest_type'] ?? '') === 'performer' && ($lead['interest_name'] ?? '') !== '') {
        $n = $lead['interest_name'];
        $interestHtml = '<p>You asked us to keep an eye on <strong>' . soMailEsc($n) . '</strong>. When there are upcoming events with tickets listed, we will email you, at most once a week for each performer.</p>';
        $interestText = 'You asked us to keep an eye on ' . $n . ". When there are upcoming events with tickets listed, we will email you, at most once a week for each performer.\n\n";
        if ((int) ($lead['interest_id'] ?? 0) > 0) {
            $slug = trim(preg_replace('/-+/', '-', preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', strtolower(trim($n))))), '-');
            $cta = $home . '/artist/' . ($slug !== '' ? $slug . '-' : '') . (int) $lead['interest_id'];
            $ctaLabel = 'See ' . $n . ' tickets';
        }
    }
    $body = '<h1 style="margin:0 0 14px;font-size:22px;color:#111827">You are on the list</h1>'
        . '<p>' . $greet . '</p>'
        . '<p>Thanks for signing up for Seat Outlet emails. Here is what to expect: ticket alerts for the performers you ask about, occasional on-sale news and plain-English ticket advice. Nothing else, and you can leave with one click at any time.</p>'
        . $interestHtml
        . soMailButton($cta, $ctaLabel);
    $text = $greetText . "\n\nThanks for signing up for Seat Outlet emails. Here is what to expect: ticket alerts for the performers you ask about, occasional on-sale news and plain-English ticket advice. Nothing else, and you can leave with one click at any time.\n\n"
        . $interestText . $ctaLabel . ': ' . $cta . soMailTextFooter($unsub);
    return soLeadSendMail($lead, 'You are on the Seat Outlet list', soMailLayout('You are on the list', $body, $unsub), $text, $unsub);
}

/** Optional note to the owner for each new lead. Only when SO_LEAD_NOTIFY_TO is set (comma separated); all values escaped. */
function soLeadNotifyOwner(array $lead) {
    $to = trim((string) getenv('SO_LEAD_NOTIFY_TO'));
    if ($to === '') return;
    $m = soMailer();
    if (!$m) return;
    try {
        [$fromAddr, $fromName] = soMailFrom();
        $m->setFrom($fromAddr, $fromName);
        foreach (explode(',', $to) as $a) {
            $a = trim($a);
            if (filter_var($a, FILTER_VALIDATE_EMAIL)) $m->addAddress($a);
        }
        $m->Subject = 'New Seat Outlet sign-up';
        $m->isHTML(true);
        $rows = ['Email' => $lead['email'], 'Name' => trim($lead['fname'] . ' ' . $lead['lname']), 'Source' => $lead['source'], 'Page' => $lead['page'],
                 'Interest' => trim($lead['interest_type'] . ' ' . $lead['interest_name'])];
        $html = '<p>A new person signed up on seatoutlet.com.</p><table cellpadding="4" style="font-family:Arial,sans-serif;font-size:14px">';
        $text = "A new person signed up on seatoutlet.com.\n";
        foreach ($rows as $k => $v) {
            $html .= '<tr><td><strong>' . soMailEsc($k) . '</strong></td><td>' . soMailEsc($v) . '</td></tr>';
            $text .= $k . ': ' . $v . "\n";
        }
        $m->Body = $html . '</table>';
        $m->AltBody = $text;
        soMailSend($m);
    } catch (\Throwable $e) {
        error_log('owner notification failed: ' . $e->getMessage());
    }
}
