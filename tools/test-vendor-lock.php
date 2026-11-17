<?php
/**
 * CI: the committed vendor/ folder is exactly what composer.lock says (same packages, same versions, same source commits). vendor/ is
 * committed on purpose (docs/pull-deploy.md: nothing is resolved at deploy time), so without this check a hand edit or a half-finished
 * `composer update` could ship code that the lock file, and `composer audit`, know nothing about. Audit 16 Nov 2026.
 * Run: php tools/test-vendor-lock.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';

/** @return string[] differences between a lock file's packages and an installed.json's packages (empty = the same) */
function vlDiff(array $lock, array $installed): array {
    $key = function (array $list): array {
        $out = [];
        foreach ($list as $p) $out[(string) $p['name']] = ($p['version'] ?? '?') . ' @' . substr((string) ($p['source']['reference'] ?? $p['dist']['reference'] ?? ''), 0, 12);
        ksort($out);
        return $out;
    };
    $a = $key($lock['packages'] ?? []);
    $b = $key($installed['packages'] ?? $installed);
    $d = [];
    foreach ($a as $n => $v) { if (!isset($b[$n])) $d[] = "in composer.lock but not in vendor/: $n"; elseif ($b[$n] !== $v) $d[] = "$n: lock says $v, vendor/ has {$b[$n]}"; }
    foreach ($b as $n => $v) if (!isset($a[$n])) $d[] = "in vendor/ but not in composer.lock: $n";
    return $d;
}

$fail = 0;
function vlCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }
$root = dirname(__DIR__);
$lock = json_decode((string) file_get_contents($root . '/composer.lock'), true);
$inst = json_decode((string) file_get_contents($root . '/vendor/composer/installed.json'), true);
vlCheck('composer.lock and vendor/composer/installed.json are readable', is_array($lock) && is_array($inst));
$diff = is_array($lock) && is_array($inst) ? vlDiff($lock, $inst) : ['unreadable'];
vlCheck('vendor/ matches composer.lock', $diff === [], implode('; ', $diff));

// The comparison itself catches each kind of drift.
$a = ['packages' => [['name' => 'x/one', 'version' => '1.0.0', 'source' => ['reference' => 'aaaaaaaaaaaaaaaa']]]];
vlCheck('same lists: no difference', vlDiff($a, $a) === []);
vlCheck('a different version is reported', count(vlDiff($a, ['packages' => [['name' => 'x/one', 'version' => '1.0.1', 'source' => ['reference' => 'aaaaaaaaaaaaaaaa']]]])) === 1);
vlCheck('a different source commit is reported', count(vlDiff($a, ['packages' => [['name' => 'x/one', 'version' => '1.0.0', 'source' => ['reference' => 'bbbbbbbbbbbbbbbb']]]])) === 1);
vlCheck('a package missing from vendor/ is reported', count(vlDiff($a, ['packages' => []])) === 1);
vlCheck('a package only in vendor/ is reported', count(vlDiff(['packages' => []], $a)) === 1);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: vendor/ matches composer.lock\n";
