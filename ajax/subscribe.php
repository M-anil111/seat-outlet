<?php
/**
 * Email sign-up endpoint behind soLeadForm() (inc/leads.php) and js/lead-capture.js.
 * POST: email (required), fname, lname, source, interest_type, interest_id, interest_name, page, website (honeypot), token (reCAPTCHA v3, action "lead").
 * Answers JSON {status: success|duplicate|invalid|recaptcha|rate|error, message}. All the work is in inc/leads-core.php.
 */
ini_set('display_errors', '0');
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/leads-core.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    soLeadRespond(['status' => 'error', 'message' => 'Please use the sign-up form.'], 405);
    exit;
}
if (soPost('website') !== '') {   // honeypot: look successful, store nothing
    soLeadRespond(['status' => 'success', 'message' => 'You are in. Watch your inbox.']);
    exit;
}
$res = soLeadSubmit([
    'email' => soPost('email'), 'fname' => soPost('fname'), 'lname' => soPost('lname'), 'source' => soPost('source'),
    'interest_type' => soPost('interest_type'), 'interest_id' => soPost('interest_id'), 'interest_name' => soPost('interest_name'),
    'page' => soPost('page'), 'token' => soPost('token'), 'alert_kind' => soPost('alert_kind'), 'baseline_price' => soPost('baseline_price'),
]);
soLeadRespond($res);
