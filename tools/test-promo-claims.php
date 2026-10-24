<?php
// Unverified promo codes (TAKE5, TAKE10) never reach public markup unless SO_PROMOS_VERIFIED=1. Run: php tools/test-promo-claims.php
require __DIR__ . '/../inc/page-spec.php';
$fail = 0;
function pcCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$bad = '/TAKE\s?(5|10)|\d+\s?% off|\$\s?(199|349)/i';

putenv('SO_PROMOS_VERIFIED');
pcCheck('no codes by default', soPromoCodes() === []);
foreach (['0', '', 'true', 'yes'] as $v) { putenv('SO_PROMOS_VERIFIED=' . $v); pcCheck("SO_PROMOS_VERIFIED=\"$v\" keeps codes off", soPromoCodes() === []); }
putenv('SO_PROMOS_VERIFIED');
foreach ([false, true] as $wide) {
    $h = soSpecPromoMarkup('Latest Taylor Swift Promo Codes in Austin, TX', 'Taylor Swift', $wide);
    pcCheck('block names no code, percentage or minimum' . ($wide ? ' (wide)' : ''), !preg_match($bad, $h));
    pcCheck('block has no Copy code button' . ($wide ? ' (wide)' : ''), stripos($h, 'offer-copy-btn') === false && stripos($h, 'data-code') === false);
    pcCheck('block says promotions appear at checkout' . ($wide ? ' (wide)' : ''), stripos($h, 'appear at checkout') !== false);
    pcCheck('heading no longer promises codes' . ($wide ? ' (wide)' : ''), stripos($h, '<h2') !== false && stripos(strip_tags(substr($h, 0, strpos($h, '</h2>'))), 'promo code') === false);
    pcCheck('promocode anchor kept' . ($wide ? ' (wide)' : ''), strpos($h, 'id="promocode"') !== false);
    pcCheck('link to the promo page kept' . ($wide ? ' (wide)' : ''), strpos($h, 'href="/tickets-promo-code"') !== false);
}
// every public template and copy file: no claim outside inc/page-spec.php's candidate list
$root = dirname(__DIR__);
$hits = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $path = $f->getPathname(); $rel = substr($path, strlen($root) + 1);
    if (preg_match('#^(\.git|vendor|node_modules|cache|docs|tools)/#', $rel) || !preg_match('/\.(php|js|sql|json|html)$/', $rel) || $rel === 'inc/page-spec.php') continue;
    if (preg_match('/TAKE(5|10)\b/', (string) file_get_contents($path))) $hits[] = $rel;
}
pcCheck('no TAKE5/TAKE10 outside inc/page-spec.php' . ($hits ? ': ' . implode(', ', $hits) : ''), !$hits);
$seed = (string) file_get_contents($root . '/db/seeds/seo_focus_keywords.sql');
pcCheck('seed titles claim no percentage off', !preg_match('/Save \d+%/i', $seed));

putenv('SO_PROMOS_VERIFIED=1');
pcCheck('SO_PROMOS_VERIFIED=1 enables the confirmed list', count(soPromoCodes()) === count(SO_PROMO_CANDIDATES));
$h = soSpecPromoMarkup('Promo codes for X', 'X', true);
pcCheck('verified mode renders the code cards', stripos($h, 'offer-copy-btn') !== false);
putenv('SO_PROMOS_VERIFIED');
exit($fail ? 1 : 0);
