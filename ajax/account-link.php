<?php
/**
 * POST /ajax/account-link.php: email a sign-in link. email (required), next (a path on this site), website (honeypot), token (reCAPTCHA v3, action "account").
 * Answers JSON {status: sent|invalid|wait|limit|recaptcha|error, message}. "sent" is also the answer for an address with no account yet
 * (the link creates it), so this endpoint never tells anyone whether an address is registered.
 */
ini_set('display_errors', '0');
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/leads-core.php';
require_once __DIR__ . '/../inc/contact.php';
require_once __DIR__ . '/../inc/account.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$say = function (string $status, string $message, int $code = 200): void { http_response_code($code); echo json_encode(['status' => $status, 'message' => $message]); exit; };

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); $say('error', 'Please use the sign-in form.', 405); }
if (!soContactSameOrigin()) $say('error', 'We could not confirm this request. Reload the page and try again.', 403);
$post = function (string $k): string { return isset($_POST[$k]) && is_string($_POST[$k]) ? trim($_POST[$k]) : ''; };
$sentMsg = 'If that address can receive email, a sign-in link is on its way. It works once and lasts 15 minutes. Check your spam folder if you do not see it.';
if ($post('website') !== '') $say('sent', $sentMsg);   // honeypot: look successful, send nothing
$ip = function_exists('soClientIp') ? (string) soClientIp() : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if (!soLeadVerifyRecaptcha($post('token'), $ip, ['account'])) $say('recaptcha', 'We could not verify that you are human. Reload the page and try again.', 400);
$res = soAcctRequestLink($post('email'), $post('next'));
switch ($res['status']) {
    case 'sent':    $say('sent', $sentMsg);
    case 'invalid': $say('invalid', 'Enter a valid email address.', 422);
    case 'wait':    $say('wait', 'A link was just sent to that address. Please wait a minute before asking for another.', 429);
    case 'limit':   $say('limit', 'Too many requests. Please try again in an hour.', 429);
    default:        $say('error', 'We could not send the email right now. Please try again in a few minutes.', 503);
}
