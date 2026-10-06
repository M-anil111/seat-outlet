<?php
// Offline test for inc/crawl-guard.php. Run: php tools/test-ratelimit.php
require __DIR__ . '/../inc/crawl-guard.php';
$fail = 0;
function ok($c, $m) { global $fail; if (!$c) { $fail++; echo "FAIL: $m\n"; } }
$dir = sys_get_temp_dir() . '/so-rl-test-' . getmypid();
$t = 1700000040;
for ($i = 1; $i <= 5; $i++) ok(soRlCount('203.0.113.5', $dir, $t) === $i, "count $i");
ok(soRlCount('203.0.113.6', $dir, $t) === 1, 'other address counted separately');
ok(soRlCount('203.0.113.5', $dir, $t + 60) === 1, 'new minute resets');
ok(soRlCount('127.0.0.1', $dir, $t) === 0, 'localhost exempt');
ok(soRlCount('', $dir, $t) === 0, 'unknown address exempt');
putenv('SO_RL_PER_MIN'); ok(soRlLimit('Mozilla/5.0') === 120, 'default 120');
ok(soRlLimit('Mozilla/5.0 (compatible; Googlebot/2.1)') === 360, 'googlebot x3');
putenv('SO_RL_PER_MIN=10'); ok(soRlLimit('x') === 10, 'env override');
putenv('SO_RL_PER_MIN=0'); ok(soRlLimit('x') === 0, 'zero disables');
ok(!soCrawlGuardApplies('/ajax/health.php') && !soCrawlGuardApplies('/sitemap.xml') && !soCrawlGuardApplies('/robots.txt'), 'private paths skipped');
ok(soCrawlGuardApplies('/event/foo') && soCrawlGuardApplies('/'), 'pages counted');
foreach (glob("$dir/*") ?: [] as $f) unlink($f); @rmdir($dir);
echo $fail ? "$fail failure(s)\n" : "ratelimit: all passed\n"; exit($fail ? 1 : 0);
