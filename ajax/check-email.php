<?php
/**
 * Step one of the legacy two-step newsletter form (js/home.js, js/blog-article.js): validate the email, verify the reCAPTCHA
 * token on the server and report whether the address is already subscribed. The sign-up itself happens in newsletter-email.php.
 * New forms use soLeadForm() and /ajax/subscribe.php instead. Answers JSON {status: success|duplicate|invalid|recaptcha|rate}.
 */
ini_set('display_errors', '0');
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/leads-core.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function soCheckEmailAnswer($status) {
    echo json_encode(['status' => $status]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    soCheckEmailAnswer('invalid');
}
try {
    $email = soLeadEmail(soPost('email'));
    if ($email === null) soCheckEmailAnswer('invalid');
    $ip = soClientIp();
    if (!soRateHit('lead-ip', soIpHash($ip), 12, 3600)) { http_response_code(429); soCheckEmailAnswer('rate'); }
    $token = soPost('token') !== '' ? soPost('token') : soPost('g-recaptcha-response');
    if (!soLeadVerifyRecaptcha($token, $ip)) soCheckEmailAnswer('recaptcha');

    $stmt = MYSQLI->prepare('SELECT 1 FROM leads WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $dup = $stmt->num_rows > 0;
    $stmt->close();
    soCheckEmailAnswer($dup ? 'duplicate' : 'success');
} catch (\Throwable $e) {
    error_log('check-email failed: ' . $e->getMessage());
    http_response_code(500);
    soCheckEmailAnswer('error');
}
