<?php
/**
 * CI: the bulk importer's token asks for catalog_bulk_access on its own token; the site's token request is unchanged.
 * (TicketNetwork, 14 Oct 2026: the /catalog/v2/events/bulk endpoint needs that scope.) Offline. Run: php tools/test-tn-token-scope.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/tn-budget.php';

$fail = 0;
function tsCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name $detail\n"; $fail++; } }

$site = tnTokenScope();
tsCheck('the site token asks for the same scope as before', $site === 'default ' . tnDeviceScope(), $site);
tsCheck('the site token has no key suffix', tnTokenKeySuffix() === '');
$bulk = tnTokenScope("catalog_bulk_access");
tsCheck('the bulk token adds catalog_bulk_access', $bulk === 'default ' . tnDeviceScope() . ' catalog_bulk_access', $bulk);
tsCheck('the bulk token has its own cache key', tnTokenKeySuffix('catalog_bulk_access') === '_catalog_bulk_access');
tsCheck('two scope sets never share a token', $site !== $bulk && tnTokenKeySuffix() !== tnTokenKeySuffix('catalog_bulk_access'));
tsCheck('an odd scope is cleaned, not passed through', tnTokenScope('x y&z') === 'default ' . tnDeviceScope() . ' xyz', tnTokenScope('x y&z'));

// Wiring: the importer's bulk requests use its scope, and functions.php passes the scope through.
$root = dirname(__DIR__);
$cron = (string) file_get_contents($root . '/cron/tn-bulk-import.php');
tsCheck('the importer asks for TN_BULK_SCOPE on both token calls', substr_count($cron, "getTnAccessToken('', TN_BULK_SCOPE)") === 1 && substr_count($cron, 'getTnAccessToken($token, TN_BULK_SCOPE)') === 1);
$fn = (string) file_get_contents($root . '/functions.php');
tsCheck('the token request uses tnTokenScope()', strpos($fn, "'scope' => tnTokenScope(\$extraScope)") !== false);
tsCheck('the site token call still passes no scope', strpos($fn, "getTnAccessToken()") !== false);

if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: bulk token scope\n";
