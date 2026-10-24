<?php
/**
 * Retired. This was step one of the old two-step newsletter form and answered "duplicate" for an address that was already subscribed,
 * which let anyone test whether a person is on the list. Nothing on the site calls it any more (forms use soLeadForm() and
 * /ajax/subscribe.php). It no longer reads the database or Google: it only says whether the text looks like an email address, so a
 * stale cached page does not break, and logs the call so the file can be deleted once the log is quiet.
 * Answers JSON {status: success|invalid}.
 */
ini_set('display_errors', '0');
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/leads-core.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('Deprecation: true');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'invalid']);
    exit;
}
error_log('legacy ajax/check-email.php called (retired; delete the file when this stops)');
echo json_encode(['status' => soLeadEmail(soPost('email')) === null ? 'invalid' : 'success']);
