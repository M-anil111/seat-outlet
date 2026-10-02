<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Privacy choices and the page landmark filter (WS6).
 *
 * Consent model (US opt-out): analytics and advertising tags (Google Tag Manager) load by default, unless the visitor's browser
 * sends the Global Privacy Control signal or the visitor chose Decline in the privacy bar (js/consent.js). The choice lives in the
 * first-party cookie so_consent (accept | decline). The decision is made in the browser, not on the server, because the HTML of
 * public pages is identical for every visitor and may be cached by a CDN.
 */

/**
 * Inline <script> for the <head>: records the visitor's choice in window.soConsent, tells Google Tag Manager the consent state
 * (Consent Mode defaults) and loads GTM only when tags are allowed. Returns '' when no GTM_ID is configured (then there are no
 * analytics or advertising tags to gate, and the privacy bar and footer link are not shown).
 */
function soConsentHeadScript() {
    $gtm = GTM_ID;
    $js = '(function(){'
        . 'var d=document,w=window,gtm=' . json_encode($gtm) . ';'
        . 'function gc(n){var m=d.cookie.match(new RegExp("(?:^|; )"+n+"=([^;]*)"));return m?decodeURIComponent(m[1]):"";}'
        . 'var gpc=navigator.globalPrivacyControl===true,ch=gc("so_consent"),ok=!gpc&&ch!=="decline";'
        . 'w.soConsent={tags:!!gtm,gpc:gpc,choice:ch,allowed:ok};'
        . 'w.dataLayer=w.dataLayer||[];'
        . 'function gtag(){w.dataLayer.push(arguments);}'
        . 'var s=ok?"granted":"denied";'
        . 'gtag("consent","default",{ad_storage:s,ad_user_data:s,ad_personalization:s,analytics_storage:s});'
        . 'w.soLoadGtm=function(){if(!gtm||w.soGtmLoaded)return;w.soGtmLoaded=true;'
        . 'w.dataLayer.push({"gtm.start":new Date().getTime(),event:"gtm.js"});'
        . 'var f=d.getElementsByTagName("script")[0],j=d.createElement("script");j.async=true;'
        . 'j.src="https://www.googletagmanager.com/gtm.js?id="+encodeURIComponent(gtm);f.parentNode.insertBefore(j,f);};'
        . 'if(ok)w.soLoadGtm();'
        . '})();';
    return '<script>' . $js . '</script>';
}

/**
 * Output filter for the page body: a page has exactly one <main> landmark. header.php opens <main id="main" data-so-main> and
 * footer.php closes it (followed by the marker comment); a <main> that a page template prints itself becomes a plain <div>
 * with the same attributes, so pages keep their look and screen readers get one landmark.
 */
function soSingleMain($html) {
    $html = preg_replace_callback('#<main\b([^>]*)>#i', function ($m) {
        return strpos($m[1], 'data-so-main') !== false ? $m[0] : '<div' . $m[1] . '>';
    }, $html);
    // Closing tags: keep only the one followed by the marker, turn the others into </div>.
    $html = preg_replace('#</main>(?!<!--so-main-end-->)#i', '</div>', $html);
    return str_replace('<!--so-main-end-->', '', $html);
}
