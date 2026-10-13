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
rlOk(!soBotBlocked('Mozilla/5.0 (Linux; Android 7.0; Cubot X19) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36', false) && !soBotBlocked('UptimeRobot/2.0; http://www.uptimerobot.com/', false) && !soBotBlocked('NewRelicPingerBot/1.0', false), 'a phone or an uptime monitor whose name contains "bot" is not a crawler');
// Request level: the decision on a whole made-up request, the way the bootstrap sees it (no SAPI needed).
$req = fn(string $ua, string $uri, string $method = 'GET') => soCrawlGuardDecision(['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, 'HTTP_USER_AGENT' => $ua], false, '');
rlOk(($req($semrush, '/event/foo')['status'] ?? 0) === 403, 'a crawler GET on a page of a non-indexable host is refused with 403');
rlOk(($req($semrush, '/event/foo', 'HEAD')['status'] ?? 0) === 403, 'a crawler HEAD is refused the same way (it built the whole page)');
rlOk(($req('ShapBot 0.1.0', '/ajax/get-home-feed.php')['status'] ?? 0) === 403 && ($req($semrush, '/ajax/load-events.php?x=1')['status'] ?? 0) === 403, 'the data endpoints that call the ticket API are refused too, not only pages');
rlOk($req($semrush, '/robots.txt') === null && $req($semrush, '/sitemap-events-1.xml') === null && $req($semrush, '/favicon.ico') === null, 'robots.txt, the sitemaps and the favicon are still served (a crawler may read what it may not crawl)');
rlOk($req($semrush, '/event/foo', 'POST') === null, 'only GET and HEAD are judged');
rlOk($req('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1', '/event/foo') === null, 'a visitor request is never refused');
rlOk(soCrawlGuardDecision(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/event/foo', 'HTTP_USER_AGENT' => $semrush], true, '') === null, 'the same request on the live host is not refused');
rlOk(soCrawlGuardDecision(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/event/foo', 'HTTP_USER_AGENT' => $semrush], false, 'semrushbot') === null, 'an allowed tool passes at request level');
// Wiring: the bootstrap runs the guard before it registers any shutdown hook (exit would still run a hook registered earlier, and
// the top-performers and sitemap hooks call the ticket API), and the guard sends the decision with no page work in between.
$fn = file_get_contents(__DIR__ . '/../functions.php');
$guardAt = strpos($fn, "\nsoCrawlGuardPublic();");
rlOk($guardAt !== false && $guardAt < strpos($fn, "register_shutdown_function('soTopPerformersMaybeRun')"), 'the bootstrap runs the guard before any shutdown hook is registered');
$cg = file_get_contents(__DIR__ . '/../inc/crawl-guard.php');
rlOk(preg_match('/\$refuse = soCrawlGuardDecision\(\$_SERVER, .*?\);\s*if \(\$refuse !== null && !headers_sent\(\)\) \{\s*http_response_code\(\$refuse\[\'status\'\]\);.*?exit;/s', $cg) === 1 && strpos($cg, "getenv('SO_BOT_ALLOW')") !== false, 'the bootstrap sends the decision and exits before any page work');
foreach (glob("$dir/*") ?: [] as $f) unlink($f); @rmdir($dir);
echo $fail ? "$fail failure(s)\n" : "ratelimit: all passed\n"; exit($fail ? 1 : 0);
