<?php
/**
 * Purchase verification for /order-confirmation. Anyone can open that URL with any parameters, so a GA4 `purchase` event (revenue) is
 * written only for an order that was verified outside the visitor's browser:
 *   - a signed return link: oid, total, eid, ts and sig = HMAC-SHA256 over "oid|total|eid|ts" with TN_RETURN_SECRET, or
 *   - a webhook from the order system: POST /ajax/tn-order-webhook.php with header X-Signature = HMAC-SHA256 of the raw body with
 *     TN_WEBHOOK_SECRET.
 * Both are OUR formats. They work only if TicketNetwork can send them (docs/launch-checklist.md); until a secret is set, nothing verifies
 * and the confirmation page reports an unverified `order_return` instead of a `purchase`.
 * Verified orders live in verified_orders (migration 0051); ga4_fired_at is claimed atomically, so one order is counted once.
 */

const SO_ORDER_SIG_MAX_AGE = 172800;     // a signed return link is honoured for 48 hours
const SO_ORDER_MAX_TOTAL = 25000;        // dollars; a verified amount above this is not credible and is stored without a value

/** A plausible order number: 4 to 40 characters with at least one digit; '' otherwise. */
function soOrderNumber($raw): string {
    if (!is_scalar($raw)) return '';
    $n = preg_replace('/[^A-Za-z0-9\-]/', '', trim((string) $raw));
    return preg_match('/^(?=.*\d)[A-Za-z0-9\-]{4,40}$/', $n) ? $n : '';
}

/** A money amount as a float rounded to cents, or null when it is not a sane positive amount. */
function soOrderAmount($raw): ?float {
    if (!is_string($raw) && !is_int($raw) && !is_float($raw)) return null;
    $s = (string) $raw;
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $s)) return null;
    $f = (float) $s;
    return ($f > 0 && $f <= SO_ORDER_MAX_TOTAL) ? round($f, 2) : null;
}

function soOrderSignPayload(string $oid, string $total, int $eid, int $ts): string {
    return $oid . '|' . $total . '|' . $eid . '|' . $ts;
}

function soOrderSign(string $oid, string $total, int $eid, int $ts, string $secret): string {
    return hash_hmac('sha256', soOrderSignPayload($oid, $total, $eid, $ts), $secret);
}

/**
 * Checks the signed return parameters in $q (usually $_GET). Returns ['ok' => bool, 'reason' => string, 'oid', 'total', 'eid'].
 * A missing secret, a missing or wrong signature, a stale or future timestamp, or an unusable order number or amount never verifies.
 */
function soOrderVerifySigned(array $q, string $secret, ?int $now = null): array {
    $bad = fn(string $why) => ['ok' => false, 'reason' => $why, 'oid' => '', 'total' => null, 'eid' => 0];
    if ($secret === '') return $bad('no_secret');
    $sig = $q['sig'] ?? '';
    if (!is_string($sig) || !preg_match('/^[0-9a-f]{64}$/', $sig)) return $bad('no_signature');
    $oid = soOrderNumber($q['oid'] ?? $q['orderId'] ?? $q['order'] ?? '');
    $totalRaw = $q['total'] ?? null;
    $total = soOrderAmount($totalRaw);
    $eidRaw = $q['eid'] ?? '0';
    $tsRaw = $q['ts'] ?? '';
    if ($oid === '' || $total === null || !is_string($totalRaw) || !is_string($eidRaw) || !ctype_digit($eidRaw) || !is_string($tsRaw) || !ctype_digit($tsRaw)) return $bad('bad_params');
    $eid = (int) $eidRaw; $ts = (int) $tsRaw;
    if (!hash_equals(soOrderSign($oid, $totalRaw, $eid, $ts, $secret), $sig)) return $bad('bad_signature');
    $age = ($now ?? time()) - $ts;
    if ($age < -300 || $age > SO_ORDER_SIG_MAX_AGE) return $bad('stale');
    return ['ok' => true, 'reason' => 'ok', 'oid' => $oid, 'total' => $total, 'eid' => $eid];
}

/** True when the webhook's X-Signature header is the HMAC-SHA256 (hex) of the raw body. An empty secret never matches. */
function soOrderWebhookValid(string $rawBody, string $header, string $secret): bool {
    if ($secret === '' || !preg_match('/^[0-9a-f]{64}$/', $header)) return false;
    return hash_equals(hash_hmac('sha256', $rawBody, $secret), $header);
}

/** Stores a verified order; a second call for the same order number changes nothing. Returns true when the order is now on file. */
function soOrderRecordVerified(mysqli $db, string $oid, int $eid, ?float $total, string $source, string $currency = 'USD'): bool {
    if (soOrderNumber($oid) !== $oid || $oid === '') return false;
    $cur = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'USD';
    $st = $db->prepare('INSERT IGNORE INTO verified_orders (order_id, event_id, total, currency, source, verified_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())');
    if (!$st) return false;
    $st->bind_param('sidss', $oid, $eid, $total, $cur, $source);
    $ok = $st->execute();
    $st->close();
    return $ok;
}

/** The verified record for an order number, or null. */
function soOrderLookup(mysqli $db, string $oid): ?array {
    $st = $db->prepare('SELECT order_id, event_id, total, currency, source, ga4_fired_at FROM verified_orders WHERE order_id = ?');
    if (!$st) return null;
    $st->bind_param('s', $oid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ?: null;
}

/** Claims the one purchase event for a verified order. True for exactly one caller; false when it was already fired or is not verified. */
function soOrderClaimPurchase(mysqli $db, string $oid): bool {
    $st = $db->prepare('UPDATE verified_orders SET ga4_fired_at = UTC_TIMESTAMP() WHERE order_id = ? AND ga4_fired_at IS NULL');
    if (!$st) return false;
    $st->bind_param('s', $oid);
    $st->execute();
    $n = $st->affected_rows;
    $st->close();
    return $n === 1;
}

/**
 * Decides what /order-confirmation may report, from the URL and the Buy-click state. Pure: the database is reached through the callables.
 *   $q               the query parameters
 *   $eventCookieOk   the so_checkout marker is present, fresh and (when the URL names an event) for the same event
 *   $retSecret       TN_RETURN_SECRET
 *   $record          fn(string $oid, int $eid, float $total): void   stores a signed-link order
 *   $lookup          fn(string $oid): ?array                         the verified row (webhook or earlier signed link)
 * Returns ['oid', 'verified' (bool), 'value' (?float), 'eid' (int), 'reason'].
 * `verified` is true only for an order known outside the browser; `value` then comes from the verified record, never from the URL.
 */
function soOrderDecide(array $q, bool $eventCookieOk, string $retSecret, callable $record, callable $lookup, ?int $now = null): array {
    $oid = soOrderNumber($q['oid'] ?? $q['orderId'] ?? $q['order'] ?? '');
    $out = ['oid' => $oid, 'verified' => false, 'value' => null, 'eid' => 0, 'reason' => 'no_order'];
    if ($oid === '') return $out;
    $out['reason'] = 'unverified';
    $urlEid = (int) ($q['eid'] ?? 0);
    if (isset($q['sig'])) {
        $s = soOrderVerifySigned($q, $retSecret, $now);
        if (!$s['ok']) { $out['reason'] = $s['reason']; return $out; }   // a bad signature is rejected, not downgraded to the webhook path
        if ($s['oid'] !== $oid) { $out['reason'] = 'bad_params'; return $out; }
        $record($s['oid'], $s['eid'], $s['total']);
    }
    $row = $lookup($oid);
    if (!$row) return $out;
    $rowEid = (int) ($row['event_id'] ?? 0);
    if ($urlEid > 0 && $rowEid > 0 && $urlEid !== $rowEid) { $out['reason'] = 'event_mismatch'; return $out; }
    if (!$eventCookieOk) { $out['reason'] = 'no_buy_click'; return $out; }
    $out['verified'] = true;
    $out['reason'] = 'ok';
    $out['eid'] = $rowEid > 0 ? $rowEid : $urlEid;
    $out['value'] = isset($row['total']) && $row['total'] !== null ? soOrderAmount((string) $row['total']) : null;
    return $out;
}
