<?php
/**
 * PHP security releases the server must run. The PHP project's advisory of 21 Oct 2026 (CVE-2026-109618 and CVE-2026-109619, remote
 * code execution without logging in, fixed in 8.5.12, 8.4.27, 8.3.36 and 8.2.35) is the first entry: PHP 8.1 and older get no fix from
 * the PHP project at all. Add the next release's minimums here when one is published.
 *
 * The number alone can mislead: a distribution may ship the fix inside a package whose PHP version number stays lower (a vendor
 * backport), so "not patched" means "check the vendor's changelog or update", not proof of an open hole.
 */
const SO_PHP_MIN_PATCHED = ['8.5' => '8.5.12', '8.4' => '8.4.27', '8.3' => '8.3.36', '8.2' => '8.2.35'];

/**
 * @return array{patched: bool, branch: string, minimum: ?string, note: string}
 */
function soPhpPatchStatus(?string $version = null): array {
    $version = $version ?? PHP_VERSION;
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)/', $version, $m)) {
        return ['patched' => false, 'branch' => '', 'minimum' => null, 'note' => 'PHP version "' . $version . '" could not be read'];
    }
    $branch = $m[1] . '.' . $m[2];
    $number = $m[1] . '.' . $m[2] . '.' . $m[3];
    if (isset(SO_PHP_MIN_PATCHED[$branch])) {
        $min = SO_PHP_MIN_PATCHED[$branch];
        $ok = version_compare($number, $min, '>=');
        return ['patched' => $ok, 'branch' => $branch, 'minimum' => $min,
            'note' => $ok ? "PHP $number is at or above the fixed release $min" : "PHP $number is older than the fixed release $min (CVE-2026-109618, CVE-2026-109619)"];
    }
    if (version_compare($branch, '8.5', '>')) {
        return ['patched' => true, 'branch' => $branch, 'minimum' => null, 'note' => "PHP $number is newer than every release this check knows"];
    }
    return ['patched' => false, 'branch' => $branch, 'minimum' => null, 'note' => "PHP $branch no longer gets fixes from the PHP project (update to 8.2 or newer, 8.4 preferred)"];
}
