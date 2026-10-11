<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the thin-page work: the holiday-city text is built from the page's own events, and listing pages have no ad
 * directly above the ticket list (the mid-page and footer ads stay). Run: php tools/test-thin-pages.php
 */
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/test.php';
require_once __DIR__ . '/../inc/holidays.php';
require_once __DIR__ . '/../inc/adsense.php';

$fails = 0;
function tpCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// 1. Text from real facts only
$events = [
    ['venue' => ['text' => ['name' => 'Red Rocks']], 'pricingInfo' => ['lowPrice' => ['value' => 49.5]], 'date' => ['date' => '2026-12-20']],
    ['venue' => ['text' => ['name' => 'Ball Arena']], 'pricingInfo' => ['lowPrice' => ['value' => 35]], 'date' => ['date' => '2026-12-05']],
    ['venue' => ['text' => ['name' => 'Red Rocks']], 'pricingInfo' => [], 'date' => ['date' => '2026-12-12']],
];
ob_start(); soHolidayAboutBlock($h, 'Denver, CO', 'Christmas shows', $events, 3, ['from' => '2026-12-01', 'to' => '2026-12-25'], 'Dec 1 to Dec 25, 2026'); $out = ob_get_clean();
tpCheck('states the event count, the date window and the venues', strpos($out, 'Seat Outlet lists 3 events in Denver, CO for Christmas shows on Dec 1 to Dec 25, 2026 at Red Rocks, Ball Arena.') !== false);
tpCheck('gives the lowest price from the listed events', strpos($out, 'start from $35, so compare') !== false);
tpCheck('gives the earliest and latest listed dates', strpos($out, 'earliest event on this page is on December 5, 2026 and the latest is on December 20, 2026') !== false);

ob_start(); soHolidayAboutBlock($h, 'Tiny, XX', 'Christmas shows', [], 0, null, ''); $none = ob_get_clean();
tpCheck('writes nothing when there are no events (no invented copy)', trim($none) === '');

ob_start(); soHolidayAboutBlock($h, 'Denver, CO', 'Christmas shows', [['venue' => ['text' => ['name' => '<b>X</b>']]]], 1, null, ''); $esc = ob_get_clean();
tpCheck('escapes venue names', strpos($esc, '&lt;b&gt;X&lt;/b&gt;') !== false && strpos($esc, '<b>X</b>') === false);

// 2. Banner: listing pages skip it, mid and footer ads are not affected by that flag
$html = '<html><body><main><section class="hero-section"><h1>T</h1></section><section>a</section></main><!--so-main-end--></main></body></html>';
$GLOBALS['soNoBannerAd'] = true;
$GLOBALS['soNoAds'] = false;
$off = soAdInjectBanner($html);
tpCheck('a listing page gets no banner under its hero', strpos($off, 'so-ad--banner') === false);
unset($GLOBALS['soNoBannerAd']);
$source = file_get_contents(__DIR__ . '/../inc/adsense.php');
tpCheck('the banner flag is checked only for the banner (mid and footer still run)', strpos($source, "empty(\$GLOBALS['soNoBannerAd']) && strpos(\$html, 'so-ad--banner')") !== false && substr_count($source, 'soNoBannerAd') === 2);

foreach (['inc/listing.php', 'inc/entity-listing.php'] as $f) {
    tpCheck("$f sets the banner-off flag for listing pages", strpos(file_get_contents(__DIR__ . '/../' . $f), "\$GLOBALS['soNoBannerAd'] = true;") !== false);
}

echo $fails ? "\n$fails failed\n" : "\nthin pages: all passed\n";
exit($fails ? 1 : 0);
