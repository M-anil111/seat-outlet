<?php
// Offline test for inc/crawl-guard.php. Run: php tools/test-ratelimit.php
require __DIR__ . '/../inc/crawl-guard.php';
$fail = 0;
function rlOk($c, $m) { global $fail; if (!$c) { $fail++; echo "FAIL: $m\n"; } }
$dir = sys_get_temp_dir() . '/so-rl-test-' . getmypid();
$t = 1700000040;
for ($i = 1; $i <= 5; $i++) rlOk(soRlCount('203.0.113.5', $dir, $t) === $i, "count $i");
rlOk(soRlCount('203.0.113.6', $dir, $t) === 1, 'other address counted separately');
rlOk(soRlCount('203.0.113.5', $dir, $t + 60) === 1, 'new minute resets');
rlOk(soRlCount('127.0.0.1', $dir, $t) === 0, 'localhost exempt');
rlOk(soRlCount('', $dir, $t) === 0, 'unknown address exempt');
putenv('SO_RL_PER_MIN'); rlOk(soRlLimit('Mozilla/5.0') === 120, 'default 120');
rlOk(soRlLimit('Mozilla/5.0 (compatible; Googlebot/2.1)') === 360, 'googlebot x3');
putenv('SO_RL_PER_MIN=10'); rlOk(soRlLimit('x') === 10, 'env override');
putenv('SO_RL_PER_MIN=0'); rlOk(soRlLimit('x') === 0, 'zero disables');
rlOk(!soCrawlGuardApplies('/ajax/health.php') && !soCrawlGuardApplies('/sitemap.xml') && !soCrawlGuardApplies('/robots.txt'), 'private paths skipped');
rlOk(soCrawlGuardApplies('/event/foo') && soCrawlGuardApplies('/'), 'pages counted');
// Crawlers on a host that is not indexable (beta) are refused; the same crawlers on the live host, visitors anywhere, and allowed
// audit tools are not.
$semrush = 'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)';
rlOk(soBotBlocked($semrush, false), 'SemrushBot is refused on a non-indexable host');
rlOk(soBotBlocked('ShapBot 0.1.0', false) && soBotBlocked('Mozilla/5.0 (compatible; Googlebot/2.1)', false), 'any crawler is refused there');
rlOk(!soBotBlocked($semrush, true), 'the same crawler is not refused on the live host');
rlOk(!soBotBlocked('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1', false), 'a visitor is never refused');
rlOk(!soBotBlocked('', false), 'an empty user agent is not treated as a crawler');
rlOk(!soBotBlocked('SeatOutletSnapshotWarmer/1.0', false), 'the snapshot warmer is not a crawler');
rlOk(!soBotBlocked($semrush, false, 'SE Ranking, semrushbot'), 'an allowed audit tool (SO_BOT_ALLOW, case-insensitive) passes');
rlOk(soBotBlocked($semrush, false, 'SE Ranking'), 'the allow list only passes the tools it names');
$cg = file_get_contents(__DIR__ . '/../inc/crawl-guard.php');
rlOk(strpos($cg, "in_array(\$_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)") !== false, 'HEAD requests are guarded like GET');
rlOk(preg_match('/soBotBlocked\(\$userAgent, .*\) \{\s*http_response_code\(403\);/s', $cg) === 1 && strpos($cg, "getenv('SO_BOT_ALLOW')") !== false, 'a refused crawler gets 403 before the page is built');
foreach (glob("$dir/*") ?: [] as $f) unlink($f); @rmdir($dir);
echo $fail ? "$fail failure(s)\n" : "ratelimit: all passed\n"; exit($fail ? 1 : 0);
