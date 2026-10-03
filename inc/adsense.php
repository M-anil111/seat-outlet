<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Google AdSense slots. Nothing is printed, and no Google script is loaded, until AdSense is configured with two settings
 * (environment variables, normally in inc/env.local.php on the server):
 *
 *   ADSENSE_CLIENT        the publisher id, "ca-pub-" followed by 16 digits
 *   ADSENSE_SLOT_HOME     the ad unit id (digits) of the banner shown under the home page hero
 *   ADSENSE_SLOT_LISTING  the ad unit id (digits) of the box under "Shop Tickets Worry Free" on the listing pages (concerts, sports, theater, festivals, all events)
 *
 * Add more placements by calling soAdSlot('name') and a matching ADSENSE_SLOT_<NAME> setting. An ad unit whose id is not set prints
 * nothing, so there is never an empty gap. The slot has a fixed minimum height, so the page does not jump when the ad arrives
 * (layout shift), and the AdSense script itself is loaded after the page has finished loading, so it cannot slow the first paint.
 * The ad stays off when the visitor has opted out (Global Privacy Control or the privacy bar's Decline): see inc/consent.php.
 * Until the settings exist the test site shows an empty framed placeholder (the live site prints nothing unless ADSENSE_PLACEHOLDER=1).
 * Also needed on the live domain: a file /ads.txt containing  google.com, pub-<16 digits>, DIRECT, f08c47fec0942fa0
 */

function soAdsenseClient(): string {
    $c = (string) getenv('ADSENSE_CLIENT');
    return preg_match('/^ca-pub-\d{10,20}$/', $c) ? $c : '';
}

/** The ad unit markup for a placement ('home' reads ADSENSE_SLOT_HOME), or '' when AdSense is not set up for it. */
function soAdSlot(string $placement, string $format = 'horizontal'): string {
    $client = soAdsenseClient();
    $slot = (string) getenv('ADSENSE_SLOT_' . strtoupper(preg_replace('/[^a-z0-9]/i', '_', $placement)));
    if ($client === '' || !preg_match('/^\d{6,20}$/', $slot)) {
        // Not set up yet: on the test site (or with ADSENSE_PLACEHOLDER=1) show where the ad will go. On the live site nothing is printed.
        if (!(defined('SITE_INDEXABLE') && !SITE_INDEXABLE) && getenv('ADSENSE_PLACEHOLDER') !== '1') return '';
        return '<aside class="so-ad so-ad--' . htmlspecialchars($placement, ENT_QUOTES, 'UTF-8') . ' so-ad--placeholder" aria-label="Advertisement"><span class="so-ad__label">Advertisement</span><div class="so-ad__ph"><strong>Google AdSense</strong><span>Ad space (code coming soon)</span></div></aside>';
    }
    $GLOBALS['soAdsenseUsed'] = true;
    return '<aside class="so-ad so-ad--' . htmlspecialchars($placement, ENT_QUOTES, 'UTF-8') . '" aria-label="Advertisement" data-so-ad>'
        . '<span class="so-ad__label">Advertisement</span>'
        . '<ins class="adsbygoogle" style="display:block" data-ad-client="' . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . '" data-ad-slot="' . htmlspecialchars($slot, ENT_QUOTES, 'UTF-8')
        . '" data-ad-format="' . ($format === 'rectangle' ? 'rectangle' : 'horizontal') . '" data-full-width-responsive="true"></ins></aside>';
}

/** Footer script: loads AdSense once, after the page has loaded, only when a slot was printed and the visitor has not opted out. */
function soAdsenseFooterScript(): string {
    if (empty($GLOBALS['soAdsenseUsed'])) return '';
    $client = soAdsenseClient();
    if ($client === '') return '';
    return '<script>(function(){var c=window.soConsent||{};if(c.gpc||c.choice==="decline")return;function go(){var s=document.createElement("script");s.async=true;s.crossOrigin="anonymous";'
        . 's.src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . '";document.head.appendChild(s);'
        . 'document.querySelectorAll("ins.adsbygoogle").forEach(function(){(window.adsbygoogle=window.adsbygoogle||[]).push({});});}'
        . 'if(document.readyState==="complete"){setTimeout(go,1500);}else{window.addEventListener("load",function(){setTimeout(go,1500);});}})();</script>';
}
