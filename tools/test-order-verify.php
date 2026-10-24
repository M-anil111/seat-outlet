<?php
// Purchase verification (inc/order-verify.php) and the /order-confirmation page's rules. No database needed. Run: php tools/test-order-verify.php
require __DIR__ . '/../inc/order-verify.php';
$fail = 0;
function ovCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$secret = 'test-secret-not-real'; $now = 1800000000;
$signed = function (string $oid, string $total, int $eid, int $ts, string $sec = null) use ($secret) {
    return ['oid' => $oid, 'total' => $total, 'eid' => (string) $eid, 'ts' => (string) $ts, 'sig' => soOrderSign($oid, $total, $eid, $ts, $sec ?? $secret)];
};

// signed return links
$q = $signed('ORD12345', '249.50', 777, $now - 60);
$r = soOrderVerifySigned($q, $secret, $now);
ovCheck('a correct signature verifies', $r['ok'] && $r['oid'] === 'ORD12345' && $r['total'] === 249.5 && $r['eid'] === 777);
ovCheck('no secret configured never verifies', !soOrderVerifySigned($q, '', $now)['ok']);
ovCheck('wrong secret is rejected', !soOrderVerifySigned($q, 'other', $now)['ok']);
foreach (['total' => '9999', 'oid' => 'ORD99999', 'eid' => '778', 'ts' => (string) ($now - 30)] as $k => $v) {
    $t = $q; $t[$k] = $v;
    ovCheck("changing $k invalidates the signature", !soOrderVerifySigned($t, $secret, $now)['ok']);
}
$t = $q; unset($t['sig']);
ovCheck('missing signature is rejected', soOrderVerifySigned($t, $secret, $now)['reason'] === 'no_signature');
$t = $q; $t['sig'] = strtoupper($q['sig']);
ovCheck('malformed (upper-case) signature is rejected', !soOrderVerifySigned($t, $secret, $now)['ok']);
ovCheck('an old link is stale', soOrderVerifySigned($signed('ORD12345', '249.50', 777, $now - SO_ORDER_SIG_MAX_AGE - 10), $secret, $now)['reason'] === 'stale');
ovCheck('a link from the future is rejected', !soOrderVerifySigned($signed('ORD12345', '249.50', 777, $now + 3600), $secret, $now)['ok']);
ovCheck('an absurd total is rejected even when signed', !soOrderVerifySigned($signed('ORD12345', '9999999', 777, $now), $secret, $now)['ok']);
ovCheck('array parameters are rejected', !soOrderVerifySigned(['oid' => ['x'], 'total' => ['1'], 'eid' => '1', 'ts' => (string) $now, 'sig' => str_repeat('a', 64)], $secret, $now)['ok']);

// webhook signature
$body = '{"order_id":"ORD12345","event_id":777,"total":"249.50"}';
$hdr = hash_hmac('sha256', $body, $secret);
ovCheck('webhook: correct signature is valid', soOrderWebhookValid($body, $hdr, $secret));
ovCheck('webhook: altered body is invalid', !soOrderWebhookValid($body . ' ', $hdr, $secret));
ovCheck('webhook: empty secret is never valid', !soOrderWebhookValid($body, hash_hmac('sha256', $body, ''), ''));
ovCheck('webhook: garbage header is invalid', !soOrderWebhookValid($body, 'nope', $secret));

// the decision, with an in-memory order store
$store = [];
$record = function ($o, $e, $t) use (&$store) { if (!isset($store[$o])) $store[$o] = ['event_id' => $e, 'total' => $t]; };
$lookup = function ($o) use (&$store) { return $store[$o] ?? null; };
$d = soOrderDecide(['oid' => 'ORD12345', 'total' => '99999', 'eid' => '777'], true, $secret, $record, $lookup, $now);
ovCheck('forged ?oid&total with a Buy-click cookie is NOT verified', !$d['verified'] && $d['value'] === null && $d['reason'] === 'unverified');
ovCheck('...and nothing was stored', $store === []);
$d = soOrderDecide(['oid' => 'ORD12345', 'total' => '1.00', 'eid' => '777', 'sig' => str_repeat('0', 64), 'ts' => (string) $now], true, $secret, $record, $lookup, $now);
ovCheck('an invalid signature is rejected and stores nothing', !$d['verified'] && $d['reason'] === 'bad_signature' && $store === []);
$d = soOrderDecide($q, true, $secret, $record, $lookup, $now);
ovCheck('a valid signed link verifies with the SIGNED total', $d['verified'] && $d['value'] === 249.5 && $d['eid'] === 777);
$d = soOrderDecide(['oid' => 'ORD12345', 'total' => '1.00', 'eid' => '777'], true, $secret, $record, $lookup, $now);
ovCheck('later, an unsigned link for a verified order reports the stored total, never the URL total', $d['verified'] && $d['value'] === 249.5);
$d = soOrderDecide(['oid' => 'ORD12345', 'eid' => '555'], true, $secret, $record, $lookup, $now);
ovCheck('an event id that differs from the verified order is rejected', !$d['verified'] && $d['reason'] === 'event_mismatch');
$d = soOrderDecide(['oid' => 'ORD12345', 'eid' => '777'], false, $secret, $record, $lookup, $now);
ovCheck('a verified order without a Buy click in this browser stays pending', !$d['verified'] && $d['reason'] === 'no_buy_click');
$t = $signed('ORD55555', '10.00', 1, $now); $t['oid'] = 'ORD12345';
ovCheck('a signature made for another order is rejected', !soOrderDecide($t, true, $secret, $record, $lookup, $now)['verified'] && !isset($store['ORD55555']));
ovCheck('no order number: nothing', soOrderDecide([], true, $secret, $record, $lookup, $now)['reason'] === 'no_order');
ovCheck('implausible order number (no digit) is dropped', soOrderNumber('ABCDEFG') === '' && soOrderNumber('<b>1234</b>') === 'b1234b');

// the page itself
$src = (string) file_get_contents(__DIR__ . '/../order-confirmation.php');
ovCheck("page never builds a purchase event from the URL total", strpos($src, "\$_GET['total']") === false && strpos($src, '$totalRaw') === false);
ovCheck("page writes purchase only after the claim", substr_count($src, "event: 'purchase'") === 1 && strpos($src, 'soOrderClaimPurchase($soDb') !== false && strpos($src, '$soVerdict[\'verified\'] && $soDb') !== false);
ovCheck('page marks purchases value_verified true and returns false', strpos($src, 'value_verified: true') !== false && strpos($src, 'value_verified: false') !== false);
ovCheck('page stays noindex and no-store', strpos($src, "'noindex, nofollow'") !== false && strpos($src, "header('Cache-Control: private, no-store')") !== false && strpos($src, "X-Robots-Tag: noindex, nofollow") !== false);
ovCheck('page emits order_return / purchase_pending', strpos($src, "'order_return'") !== false && strpos($src, "'purchase_pending'") !== false);
exit($fail ? 1 : 0);
