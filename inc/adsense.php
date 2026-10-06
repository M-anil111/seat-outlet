<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Google AdSense slots. Nothing is printed, and no Google script is loaded, until AdSense is configured with two settings
 * (environment variables, normally in inc/env.local.php on the server):
 *
 *   ADSENSE_CLIENT        the publisher id, "ca-pub-" followed by 16 digits (built in: ca-pub-1077085934387393; this setting only overrides it)
 *   ADSENSE_SLOT_BANNER   the ad unit id (digits) of the banner shown under the hero banner on every page (ADSENSE_SLOT_HOME still works as the same setting)
 *   ADSENSE_SLOT_LISTING  the ad unit id (digits) of the box under "Shop Tickets Worry Free" on the listing pages (concerts, sports, theater, festivals, all events)
 *
 * Add more placements by calling soAdSlot('name') and a matching ADSENSE_SLOT_<NAME> setting. An ad unit whose id is not set prints
 * nothing, so there is never an empty gap. The slot has a fixed minimum height, so the page does not jump when the ad arrives
 * (layout shift), and the AdSense script itself is loaded on the visitor's first touch, scroll, click or key press (or 15 seconds after load if there is none), so it cannot slow the first paint, the main thread or the Lighthouse run.
 * The ad stays off when the visitor has opted out (Global Privacy Control or the privacy bar's Decline): see inc/consent.php.
 * Until the settings exist the test site shows an empty framed placeholder (the live site prints nothing unless ADSENSE_PLACEHOLDER=1).
 * /ads.txt (repo root) carries  google.com, pub-1077085934387393, DIRECT, f08c47fec0942fa0  and every page has the google-adsense-account <meta> tag.
 */

/** Seat Outlet's AdSense publisher id (it is public: it is in every page and in /ads.txt). ADSENSE_CLIENT overrides it. */
const SO_ADSENSE_PUBLISHER = 'ca-pub-1077085934387393';

/** The "Seat Outlet Responsive" display ad unit (public, it is in the page source). It serves every placement unless an ADSENSE_SLOT_* setting says otherwise. */
const SO_ADSENSE_DEFAULT_SLOT = '8680343825';

function soAdsenseClient(): string {
    $c = (string) getenv('ADSENSE_CLIENT');
    if (preg_match('/^ca-pub-\d{10,20}$/', $c)) return $c;
    return SO_ADSENSE_PUBLISHER;
}

/** <meta> for the <head> of every page: lets AdSense verify the site (the "Meta tag" method) without loading any Google script. */
function soAdsenseMetaTag(): string {
    return '<meta name="google-adsense-account" content="' . htmlspecialchars(soAdsenseClient(), ENT_QUOTES, 'UTF-8') . '">';
}

/** The ad unit markup for a placement ('home' reads ADSENSE_SLOT_HOME), or '' when AdSense is not set up for it. */
function soAdSlot(string $placement, string $format = 'horizontal'): string {
    $client = soAdsenseClient();
    $slot = (string) getenv('ADSENSE_SLOT_' . strtoupper(preg_replace('/[^a-z0-9]/i', '_', $placement)));
    if ($slot === '' && in_array($placement, ['banner', 'mid', 'foot', 'pre', 'inpanel', 'side2', 'listing'], true)) $slot = (string) getenv('ADSENSE_SLOT_BANNER');   // one banner ad unit can serve every banner position
    if ($slot === '' && in_array($placement, ['banner', 'mid', 'foot', 'pre', 'inpanel', 'side2', 'listing'], true)) $slot = (string) getenv('ADSENSE_SLOT_HOME');     // the home banner setting from before banners were on every page
    if ($slot === '' && in_array($placement, ['banner', 'mid', 'foot', 'pre', 'inpanel', 'side2', 'listing'], true)) $slot = SO_ADSENSE_DEFAULT_SLOT;
    $GLOBALS['soAdSeq'] = ($GLOBALS['soAdSeq'] ?? 0) + 1;   // each ad landmark gets its own name
    $adName = 'Advertisement ' . $GLOBALS['soAdSeq'];
    if ($client === '' || !preg_match('/^\d{6,20}$/', $slot)) {
        // Not set up yet: on the test site (or with ADSENSE_PLACEHOLDER=1) show where the ad will go. On the live site nothing is printed.
        if (!(defined('SITE_INDEXABLE') && !SITE_INDEXABLE) && getenv('ADSENSE_PLACEHOLDER') !== '1') return '';
        return '<aside class="so-ad so-ad--' . htmlspecialchars($placement, ENT_QUOTES, 'UTF-8') . ' so-ad--placeholder" aria-label="' . $adName . '"><span class="so-ad__label">Advertisement</span><div class="so-ad__ph"><strong>AdSense Banner</strong><span>(' . (in_array($placement, ['listing', 'side2'], true) ? 'Medium rectangle 300 &times; 250' : 'Leaderboard 728 &times; 90') . ')</span></div></aside>';
    }
    $GLOBALS['soAdsenseUsed'] = true;
    return '<aside class="so-ad so-ad--' . htmlspecialchars($placement, ENT_QUOTES, 'UTF-8') . '" aria-label="' . $adName . '" data-so-ad>'
        . '<span class="so-ad__label">Advertisement</span>'
        // Fixed sizes picked by CSS (320x100, 468x60, 728x90 for banners; 300x250 for boxes). The "full width responsive" mode is off on
        // purpose: it makes the AdSense script size the ad to the whole screen, which pushed the sidebar below the page.
        . '<ins class="adsbygoogle so-adunit so-adunit--' . ($format === 'rectangle' ? 'r' : 'h') . '" style="display:inline-block" data-ad-client="' . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . '" data-ad-slot="' . htmlspecialchars($slot, ENT_QUOTES, 'UTF-8')
        . '"></ins></aside>';
}

/** Footer script: loads AdSense once, on the first interaction (or 15 s after load), only when a slot was printed and the visitor has not opted out. */
function soAdsenseFooterScript(): string {
    $client = soAdsenseClient();
    if ($client === '') return '';
    return '<script>(function(){var c=window.soConsent||{};if(c.gpc||c.choice==="decline"||!document.querySelector("ins.adsbygoogle"))return;function pushAds(){document.querySelectorAll("ins.adsbygoogle:not([data-so-pushed])").forEach(function(el){if(el.closest("[hidden]"))return;el.setAttribute("data-so-pushed","1");(window.adsbygoogle=window.adsbygoogle||[]).push({});});}window.soAdsPush=pushAds;function go(){var s=document.createElement("script");s.async=true;s.crossOrigin="anonymous";'
        . 's.src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . '";document.head.appendChild(s);'
        . 'pushAds();}'
        . 'var done=false,evs=["pointerdown","keydown","touchstart","scroll","wheel"];function start(){if(done)return;done=true;evs.forEach(function(t){window.removeEventListener(t,start);});setTimeout(go,200);}'
        . 'evs.forEach(function(t){window.addEventListener(t,start,{passive:true});});'
        . 'function idle(){setTimeout(start,15000);}if(document.readyState==="complete"){idle();}else{window.addEventListener("load",idle);}})();</script>';
}

/**
 * The banner under the hero on every page. Pages have different heroes (home, content pages, listing, event, artist ...), so this is
 * one output filter (called from soSingleMain in inc/consent.php): it finds the first hero element of the page and puts the banner
 * right after it. A page can turn it off with $soNoAds = true (checkout and confirmation pages), and prints its own with
 * soAdSlot('banner') when it needs a different place (then nothing is added). Pages without a hero get no banner.
 */
const SO_AD_HERO_CLASSES = ['hero-section', 'hero-so-why', 'vp-hero', 'tickets-hero-section', 'search-hero-section', 'performers-hero-section', 'mc-hero', 'is-hero', 'asm-hero', 'so-g-hero', 'so-evhero', 'so-evbar', 'so-ent-hero', 'so-art__hero', 'so-np__head', 'section-featured-header', 'so-hero2', 'results-header'];

function soAdInjectBanner(string $html): string {
    if (!empty($GLOBALS['soNoAds'])) return $html;
    // 1. Under the hero.
    if (strpos($html, 'so-ad--banner') === false && ($banner = soAdSlot('banner')) !== '') {
        $alt = implode('|', array_map(function ($c) { return preg_quote($c, '#'); }, SO_AD_HERO_CLASSES));
        // The first opening tag, anywhere in the page body, whose class list has a hero class as a whole word.
        if (preg_match('#<(section|header|div)\b[^>]*\bclass="(?:[^"]*\s)?(?:' . $alt . ')(?:\s[^"]*)?"[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $tag = strtolower($m[1][0]);
            $pos = $m[0][1] + strlen($m[0][0]);
            $depth = 1;
            // Walk forward to the matching closing tag of the same element name.
            while ($depth > 0 && preg_match('#<(/?)' . $tag . '\b[^>]*?(/?)>#i', $html, $t, PREG_OFFSET_CAPTURE, $pos)) {
                $pos = $t[0][1] + strlen($t[0][0]);
                if ($t[1][0] === '/') { $depth--; }
                elseif ($t[2][0] !== '/') { $depth++; }
            }
            if ($depth === 0) { $html = substr($html, 0, $pos) . $banner . substr($html, $pos); }
        }
    }
    // 2. Roughly halfway down: after the section nearest the middle, only where the next thing is another section (so it lands between
    //    page blocks that stack, never inside a row or grid) and the sections before it are all closed.
    if (strpos($html, 'so-ad--mid') === false && ($mid = soAdSlot('mid')) !== '' && preg_match_all('#</section>\s*(?=<section\b)#i', $html, $mm, PREG_OFFSET_CAPTURE) && count($mm[0]) >= 4) {
        $len = strlen($html);
        $best = null;
        foreach ($mm[0] as $hit) {
            $end = $hit[1] + strlen(rtrim($hit[0]));
            if (substr_count(strtolower(substr($html, 0, $end)), '<section') !== substr_count(strtolower(substr($html, 0, $end)), '</section>')) continue;
            if ($end < $len * 0.3 || $end > $len * 0.75) continue;
            if ($best === null || abs($end - $len / 2) < abs($best - $len / 2)) $best = $end;
        }
        if ($best !== null) { $html = substr($html, 0, $best) . $mid . substr($html, $best); }
    }
    // 3. End of the page content, above the footer.
    if (strpos($html, 'so-ad--foot') === false && ($foot = soAdSlot('foot')) !== '') {
        $mark = strrpos($html, '</main><!--so-main-end-->');
        $html = $mark !== false ? substr($html, 0, $mark) . $foot . substr($html, $mark) : $html . $foot;   // the end of the page content, above the footer
    }
    return $html;
}
