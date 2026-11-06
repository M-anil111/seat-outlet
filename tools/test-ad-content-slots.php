<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Content pages (blog posts, event pages, about pages and other article-style pages) carry at least three ad slots, placed between
 * paragraphs. Thin pages (soNoAds) and pages too short to hold three quiet slots get none. Unit checks run the helpers on fixture HTML;
 * integration checks run the same output step the site runs on every page (soAdInjectBanner, called from inc/consent.php).
 * Run: php tools/test-ad-content-slots.php
 */
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/test.php';
require_once __DIR__ . '/../inc/adsense.php';

$fails = 0;
function acsCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

/** A content page: a hero, then $paras paragraphs inside <main>, each long enough to be real copy. */
function acsArticle(int $paras, string $extra = ''): string {
    $body = '';
    for ($i = 1; $i <= $paras; $i++) {
        $body .= '<p>Paragraph ' . $i . ' of the article, with enough words in it to count as real copy for a reader who came to learn something.</p>' . "\n";
    }
    return '<!doctype html><html><head><title>Article</title></head><body>'
        . '<section class="page-hero"><h1>Article</h1></section>'
        . '<main><section class="copy"><h2>The story</h2>' . $body . $extra . '</section></main><!--so-main-end-->'
        . '<footer>Site footer</footer></body></html>';
}
function acsBalanced(string $html): bool {
    return substr_count($html, '<p>') === substr_count($html, '</p>')
        && substr_count(strtolower($html), '<section') === substr_count(strtolower($html), '</section>');
}


/** Tags whose contents must never hold an ad: a link, a button, a form, a table, navigation, a header or footer, or a script. */
const ACS_FORBIDDEN = ['a', 'button', 'form', 'table', 'nav', 'header', 'footer', 'script', 'style', 'select', 'label'];
/** True when no ad slot sits inside one of the forbidden tags (the slot itself is an aside, which is not in the list). */
function acsOutsideForbidden(string $html): bool {
    $stack = [];
    preg_match_all('#<(/?)([a-zA-Z][a-zA-Z0-9]*)\b[^>]*?(/?)>|(<aside class="so-ad)#', $html, $m, PREG_SET_ORDER);
    foreach ($m as $t) {
        if (isset($t[4])) { if (count(array_intersect($stack, ACS_FORBIDDEN)) > 0) return false; continue; }
        $name = strtolower($t[2]);
        if ($t[1] === '/') { $i = array_search($name, $stack, true); if ($i !== false) array_splice($stack, $i); continue; }
        if ($t[3] !== '/' && !in_array($name, ['br', 'img', 'hr', 'input', 'meta', 'link'], true)) $stack[] = $name;
    }
    return true;
}
/** True when every ad slot is preceded by a closed paragraph and followed by an opening paragraph (so it sits between two paragraphs). */
function acsBetweenParagraphs(string $html): bool {
    $n = preg_match_all('#<aside class="so-ad so-ad--[a-z0-9]+"#', $html, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[0] as $hit) {
        $before = rtrim(substr($html, 0, $hit[1]));
        $after = ltrim(substr($html, $hit[1]));
        if (substr($before, -4) !== '</p>') return false;
        $close = strpos($after, '</aside>');
        if ($close === false) return false;
        $rest = ltrim(substr($after, $close + strlen('</aside>')));
        if (strncmp($rest, '<p', 2) !== 0) return false;
    }
    return true;
}

// ---- Unit: the counter and the candidate rule -------------------------------------------------------------------
acsCheck('counts ad slots by their markup', soAdCountSlots('<aside class="so-ad so-ad--mid">x</aside><p>y</p><aside class="so-ad so-ad--foot">z</aside>') === 2);
acsCheck('a page with no ad markup counts zero', soAdCountSlots('<p>no ads here</p>') === 0);

$art = acsArticle(6);
acsCheck('a page that already has three slots is returned unchanged', soAdEnsureSlots(str_replace('</main>', '<aside class="so-ad so-ad--a">1</aside><aside class="so-ad so-ad--b">2</aside><aside class="so-ad so-ad--c">3</aside></main>', $art)) === str_replace('</main>', '<aside class="so-ad so-ad--a">1</aside><aside class="so-ad so-ad--b">2</aside><aside class="so-ad so-ad--c">3</aside></main>', $art));

$out = soAdEnsureSlots($art);
acsCheck('adds slots until the page has at least three', soAdCountSlots($out) >= 3);
acsCheck('adds the difference and no more (a page with one slot gets two more)', soAdCountSlots(soAdEnsureSlots(str_replace('</main>', '<aside class="so-ad so-ad--a">1</aside></main>', $art))) === 3);
acsCheck('the output keeps paragraphs and sections balanced', acsBalanced($out));
acsCheck('nothing is inserted inside a link, button, form, table, nav or script', acsOutsideForbidden($out));
acsCheck('every slot sits between two paragraphs, never at the start or end of the copy', acsBetweenParagraphs($out));
acsCheck('a page with fewer than four paragraphs gets no slots (too thin to hold them quietly)', soAdCountSlots(soAdEnsureSlots(acsArticle(3))) === 0);
acsCheck('a buy button in the copy does not stop the slots from being placed between paragraphs', soAdCountSlots(soAdEnsureSlots(acsArticle(6, '<div class="buy"><a class="btn" href="/x">Buy</a></div>'))) >= 3);

// ---- Integration: the site's output step, with the consent gate and the thin-page flag -------------------------
$GLOBALS['soNoAds'] = false;
unset($GLOBALS['soNoBannerAd']);
$page = soAdInjectBanner(acsArticle(8));
acsCheck('integration: an article page gets at least three slots from the output step', soAdCountSlots($page) >= 3);
acsCheck('integration: the page stays balanced after every insertion', acsBalanced($page));
acsCheck('integration: slots sit inside the page copy, not after the footer', strpos($page, 'so-ad') !== false && strpos($page, 'so-ad') < strpos($page, '<footer>'));
acsCheck('integration: a short page gets slots only when it can hold them', soAdCountSlots(soAdInjectBanner(acsArticle(2))) <= 1);

$GLOBALS['soNoAds'] = true;
acsCheck('integration: a thin page (soNoAds) gets no ads at all', soAdCountSlots(soAdInjectBanner(acsArticle(8))) === 0);
$GLOBALS['soNoAds'] = false;

// An event page: hero, facts, a sidebar and the copy.
$event = '<!doctype html><html><head><title>Event</title></head><body><section class="event-hero"><h1>Show</h1></section>'
    . '<main><section class="facts"><p>Date and venue</p></section><section class="copy"><h2>About the show</h2>';
for ($i = 1; $i <= 7; $i++) $event .= '<p>Copy paragraph ' . $i . ' about the show, the venue and what to expect on the night.</p>';
$event .= '</section></main><!--so-main-end--><footer>f</footer></body></html>';
acsCheck('integration: an event page gets at least three slots', soAdCountSlots(soAdInjectBanner($event)) >= 3);

if ($fails) { echo "$fails failure(s)\n"; exit(1); }
echo "ok: content ad slots\n";
