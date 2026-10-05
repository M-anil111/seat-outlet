<?php
// Receives Content-Security-Policy-Report-Only violation reports (sent by the browser, see soSendSecurityHeaders in functions.php).
// Each report is written to the PHP error log as one short line so the allowed-hosts list can be tightened before the policy is enforced.
// Nothing is stored in the database and nothing is shown to visitors.
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$raw = file_get_contents('php://input', false, null, 0, 8192);
$j = json_decode((string) $raw, true);
$r = is_array($j) ? ($j['csp-report'] ?? ($j[0]['body'] ?? $j['body'] ?? null)) : null;
if (is_array($r)) {
    $pick = function ($k1, $k2 = '') use ($r) { $v = $r[$k1] ?? ($k2 !== '' ? ($r[$k2] ?? '') : ''); return substr(preg_replace('/[^\x20-\x7e]/', '', (string) $v), 0, 160); };
    error_log('csp-report-only: ' . $pick('effective-directive', 'effectiveDirective') . ' blocked=' . $pick('blocked-uri', 'blockedURL') . ' page=' . $pick('document-uri', 'documentURL'));
}
http_response_code(204);
