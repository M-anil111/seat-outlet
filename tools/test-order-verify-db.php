<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Database test for verified orders: idempotent recording and a purchase that can be claimed exactly once.
 * Needs the migrated database (run after db/migrate.php). Uses order numbers beginning ZQ and deletes them at the end.
 * Run: php tools/test-order-verify-db.php
 */
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../inc/order-verify.php';
$fail = 0;
function odCheck(string $what, $got, $want): void { global $fail; if ($got !== $want) { echo "FAIL $what: got " . var_export($got, true) . ", wanted " . var_export($want, true) . "\n"; $fail++; } else echo "ok   $what\n"; }
MYSQLI->query("DELETE FROM verified_orders WHERE order_id LIKE 'ZQ%'");

odCheck('an unknown order has no record', soOrderLookup(MYSQLI, 'ZQ-1001'), null);
odCheck('claiming an order that is not verified fires nothing', soOrderClaimPurchase(MYSQLI, 'ZQ-1001'), false);
odCheck('recording a verified order succeeds', soOrderRecordVerified(MYSQLI, 'ZQ-1001', 777, 249.5, 'webhook'), true);
odCheck('a second record with another total does not overwrite', soOrderRecordVerified(MYSQLI, 'ZQ-1001', 777, 1.0, 'signed_return'), true);
$row = soOrderLookup(MYSQLI, 'ZQ-1001');
odCheck('the first verified total and source are kept', [(float) $row['total'], $row['source'], (int) $row['event_id']], [249.5, 'webhook', 777]);
odCheck('the purchase is claimed once', soOrderClaimPurchase(MYSQLI, 'ZQ-1001'), true);
odCheck('a replay cannot claim it again', soOrderClaimPurchase(MYSQLI, 'ZQ-1001'), false);
odCheck('a reload after the claim still cannot', soOrderClaimPurchase(MYSQLI, 'ZQ-1001'), false);
odCheck('an invalid order number is not stored', soOrderRecordVerified(MYSQLI, "ZQ'; DROP TABLE x;--", 1, 1.0, 'webhook'), false);
odCheck('a missing total is stored as no value', soOrderRecordVerified(MYSQLI, 'ZQ-1002', 5, null, 'webhook'), true);
odCheck('...and reads back as null', soOrderLookup(MYSQLI, 'ZQ-1002')['total'], null);

// two claimers racing: only one affected row
soOrderRecordVerified(MYSQLI, 'ZQ-1003', 9, 10.0, 'webhook');
$a = soOrderClaimPurchase(MYSQLI, 'ZQ-1003'); $b = soOrderClaimPurchase(MYSQLI, 'ZQ-1003');
odCheck('exactly one of two claims wins', [$a, $b], [true, false]);

MYSQLI->query("DELETE FROM verified_orders WHERE order_id LIKE 'ZQ%'");
exit($fail ? 1 : 0);
