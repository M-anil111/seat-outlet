<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Ads only on normal, indexable content pages. A 404 or 503, a page whose ticket feed is down, a noindex page (an empty listing, an
 * artist with no upcoming events) and the pages that turn ads off carry no ad slot, and a slot printed before the page knew its status is
 * removed when the page is filtered. AdSense "low value content" rejection, 18 Nov 2026. Run: php tools/test-ad-suppress.php
 */
ob_start();   // output is held until the end, so the response code can be set while the checks run
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/test.php';
require_once __DIR__ . '/../inc/adsense.php';

$fails = 0;
function asCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
$reset = function () { unset($GLOBALS['soNoAds'], $GLOBALS['soFeedDown'], $GLOBALS['pageRobots'], $GLOBALS['pageRule'], $GLOBALS['soNoBannerAd']); http_response_code(200); $GLOBALS['soAdSeq'] = 0; };
$page = function (): string {
    $body = '';
    for ($i = 1; $i <= 6; $i++) $body .= '<p>Paragraph ' . $i . ' of the page, with enough words in it to count as real copy for a reader who came to learn something.</p>';
    return '<html><body><section class="page-hero"><h1>Page</h1></section><main><section>' . $body . '</section></main><!--so-main-end--><footer>f</footer></body></html>';
};

$reset();
asCheck('a normal page is not suppressed', soAdsSuppressed() === false);
asCheck('a normal page prints a slot', strpos(soAdSlot('banner'), 'so-ad') !== false);
asCheck('a normal page gets its ads when filtered', soAdCountSlots(soAdInjectBanner($page())) >= 3);

foreach ([
    'ads turned off (checkout, events)'      => function () { $GLOBALS['soNoAds'] = true; },
    'the ticket feed is down'                 => function () { $GLOBALS['soFeedDown'] = true; },
    'a 404 answer'                            => function () { http_response_code(404); },
    'a 503 answer'                            => function () { http_response_code(503); },
    'a noindex page (empty listing)'          => function () { $GLOBALS['pageRobots'] = 'noindex, follow'; },
    'a noindex page rule'                     => function () { $GLOBALS['pageRule'] = ['robots' => 'noindex, nofollow']; },
] as $what => $set) {
    $reset(); $set();
    asCheck("$what: suppressed", soAdsSuppressed() === true);
    asCheck("$what: no slot is printed", soAdSlot('banner') === '' && soAdSlot('listing', 'rectangle') === '');
    asCheck("$what: the page is filtered with no ad", soAdCountSlots(soAdInjectBanner($page())) === 0);
}

// A slot printed while the page rendered, before the page knew it would be a 503, is removed by the output filter.
$reset();
$printed = soAdSlot('listing', 'rectangle');
asCheck('a slot was printed while the page looked normal', $printed !== '');
$html = str_replace('<main>', '<main>' . $printed, $page());
http_response_code(503);
asCheck('the slot printed earlier is removed once the page is a 503', strpos(soAdInjectBanner($html), 'so-ad so-ad--') === false);
$reset();
asCheck('removing slots keeps the rest of the page', strpos(soAdStripSlots($html), 'Paragraph 3 of the page') !== false);

// Wiring: every printing path goes through soAdSlot, and the output filter checks the same rule.
$src = (string) file_get_contents(__DIR__ . '/../inc/adsense.php');
asCheck('soAdSlot checks the rule first', preg_match('/function soAdSlot\([^)]*\): string \{\s*if \(soAdsSuppressed\(\)\) return \'\';/', $src) === 1);
asCheck('the output filter checks the rule', strpos($src, "function soAdInjectBanner(string \$html): string {\n    if (soAdsSuppressed())") !== false);
// Wiring: an empty category page (a real answer with no events) is noindex, so it carries no ad. /category/west-end, 18 Nov 2026:
// 0 results, indexable, 4 ad units. The rule sits after the feed-down gate, so a failed feed stays a 503 and is not noindexed.
$catSrc = (string) file_get_contents(__DIR__ . '/../category.php');
$gate = strpos($catSrc, 'soFeedDownGate($catLabel');
$zero = strpos($catSrc, "if (\$total_count === 0 && !\$events && empty(\$pageRobots)) { \$pageRobots = 'noindex, follow'; }");
asCheck('an empty category page is noindex,follow (so no ads)', $zero !== false);
asCheck('after the feed-down gate (a failed feed stays a 503)', $gate !== false && $zero !== false && $gate < $zero);
asCheck('before header.php prints the robots tag', $zero !== false && $zero < strpos($catSrc, "include 'header.php'"));
ob_end_flush();
if ($fails) { echo "$fails check(s) failed\n"; exit(1); }
echo "ok: no ads on error, noindex or empty pages\n";
