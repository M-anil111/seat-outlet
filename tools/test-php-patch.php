<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/** Offline test for soPhpPatchStatus() (inc/php-security.php). Run: php tools/test-php-patch.php */
require_once __DIR__ . '/../inc/php-security.php';

$fail = 0;
function ppCheck(string $name, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fail++; }

foreach (['8.5.12', '8.5.13', '8.4.27', '8.4.30', '8.3.36', '8.3.40', '8.2.35', '8.2.99'] as $v) ppCheck("$v is patched", soPhpPatchStatus($v)['patched'] === true);
foreach (['8.5.11', '8.4.26', '8.4.19', '8.3.35', '8.2.34', '8.5.0'] as $v) ppCheck("$v is not patched", soPhpPatchStatus($v)['patched'] === false);
foreach (['8.1.99', '8.0.30', '7.4.33', '5.6.40'] as $v) { $s = soPhpPatchStatus($v); ppCheck("$v gets no fix: not patched, no minimum", $s['patched'] === false && $s['minimum'] === null); }
ppCheck('a newer branch than the table knows counts as patched', soPhpPatchStatus('8.6.0')['patched'] === true && soPhpPatchStatus('9.0.1')['patched'] === true);
ppCheck('a distribution suffix is ignored (8.4.27-1ubuntu1 patched, 8.4.19-1ubuntu1 not)', soPhpPatchStatus('8.4.27-1ubuntu1')['patched'] === true && soPhpPatchStatus('8.4.19-1ubuntu1')['patched'] === false);
ppCheck('an unreadable version is not patched', soPhpPatchStatus('banana')['patched'] === false);
ppCheck('the note names the fixed release', strpos(soPhpPatchStatus('8.4.19')['note'], '8.4.27') !== false && soPhpPatchStatus('8.4.19')['minimum'] === '8.4.27');
ppCheck('the running version is judged with no argument', is_bool(soPhpPatchStatus()['patched']));
echo $fail ? "$fail failed\n" : "php patch: all passed\n";
exit($fail ? 1 : 0);
