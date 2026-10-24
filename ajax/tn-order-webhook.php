<?php
// Receives verified-order notifications from the order system: POST JSON {"order_id": "...", "event_id": 123, "total": "249.50", "currency": "USD"}
// with header X-Signature = HMAC-SHA256 (hex) of the raw body using TN_WEBHOOK_SECRET. The payload format is ours: TicketNetwork has to be
// able to send it (docs/launch-checklist.md). Without TN_WEBHOOK_SECRET every request is refused. The order is stored once (verified_orders);
// a repeat delivery changes nothing and still answers 200 so the sender stops retrying. Nothing here is shown to visitors.
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../inc/order-verify.php';
$secret = (string) getenv('TN_WEBHOOK_SECRET');
$raw = (string) file_get_contents('php://input', false, null, 0, 16384);
$sig = isset($_SERVER['HTTP_X_SIGNATURE']) ? strtolower(trim((string) $_SERVER['HTTP_X_SIGNATURE'])) : '';
if (!soOrderWebhookValid($raw, $sig, $secret)) { http_response_code(401); exit; }
$j = json_decode($raw, true);
$oid = is_array($j) ? soOrderNumber($j['order_id'] ?? '') : '';
if ($oid === '') { http_response_code(422); exit; }
$eid = max(0, min(2000000000, (int) ($j['event_id'] ?? 0)));
$total = soOrderAmount($j['total'] ?? null);
$cur = strtoupper(substr((string) ($j['currency'] ?? 'USD'), 0, 3));
if (!defined('MYSQLI') || !(MYSQLI instanceof mysqli) || !soOrderRecordVerified(MYSQLI, $oid, $eid, $total, 'webhook', $cur)) { http_response_code(503); exit; }
http_response_code(200);
echo 'ok';
