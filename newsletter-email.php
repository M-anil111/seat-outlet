<?php
/**
 * Legacy newsletter form target (home page and blog article forms still post here until they move to soLeadForm()).
 * All the work is in inc/leads-core.php, shared with /ajax/subscribe.php: server-side reCAPTCHA, validation, rate limits,
 * insert first, then the welcome email after the redirect has been sent. A duplicate address is not an error.
 */
ini_set('display_errors', '0');
require_once __DIR__ . '/db/config.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/inc/leads-core.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$thanks = rtrim(HOME_URL, '/') . '/thank-you';
if (soPost('company') !== '') {   // honeypot: look successful, store nothing
    header('Location: ' . $thanks, true, 303);
    exit;
}

$ref = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
$res = soLeadSubmit([
    'email' => soPost('email'), 'fname' => soPost('fname'), 'lname' => soPost('lname'),
    'source' => (string) $ref === '/' || (string) $ref === '' ? 'home' : 'blog',
    'page' => is_string($ref) ? $ref : '', 'token' => soPost('token'),
]);

if ($res['status'] === 'success' || $res['status'] === 'duplicate') {
    header('Location: ' . $thanks, true, 303);
    header('Connection: close');
    header('Content-Length: 0');
    if (!empty($res['after'])) {
        if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request(); else { @ob_end_flush(); @flush(); }
        ignore_user_abort(true);
        call_user_func($res['after']);
    }
    exit;
}

http_response_code($res['status'] === 'rate' ? 429 : ($res['status'] === 'error' ? 500 : 400));
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Sign-up problem | Seat Outlet</title></head>'
   . '<body style="font-family:system-ui,sans-serif;text-align:center;padding:12vh 16px;color:#1f2937"><h1 style="font-size:24px;margin:0 0 8px">We could not sign you up</h1>'
   . '<p style="margin:0 0 16px;color:#5b6573">' . htmlspecialchars($res['message'], ENT_QUOTES, 'UTF-8') . '</p>'
   . '<a href="' . htmlspecialchars(rtrim(HOME_URL, '/') . '/', ENT_QUOTES, 'UTF-8') . '" style="color:#1b3bb0">Back to Seat Outlet</a></body></html>';
