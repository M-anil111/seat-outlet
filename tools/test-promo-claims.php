<?php
// TAKE5 and TAKE10 render with page-specific copy, and SO_PROMOS_OFF=1 removes every claim. Run: php tools/test-promo-claims.php
require __DIR__ . '/../inc/page-spec.php';
$fail = 0;
function pcCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$bad = '/TAKE\s?(5|10)|\d+\s?% off|\$\s?(199|349)/i';

putenv('SO_PROMOS_OFF');
$codes = soPromoCodes();
pcCheck('both confirmed codes are on by default', array_column($codes, 'code') === ['TAKE5', 'TAKE10']);
pcCheck('terms: TAKE5 5% / $199, TAKE10 10% / $349', $codes[0]['pct'] === 5 && $codes[0]['min'] === 199 && $codes[1]['pct'] === 10 && $codes[1]['min'] === 349);

$contexts = ['event', 'performer', 'venue', 'city', 'category', 'holiday', 'discovery'];
$intros = [];
foreach ($contexts as $c) {
    $h = soSpecPromoMarkup('Promo codes for Metallica', 'Metallica', $c === 'event', $c);
    pcCheck("$c: shows TAKE5 and TAKE10 with Copy code buttons", substr_count($h, 'offer-copy-btn') === 2 && strpos($h, 'data-code="TAKE5"') !== false && strpos($h, 'data-code="TAKE10"') !== false);
    pcCheck("$c: states 5% / \$199 and 10% / \$349", strpos($h, '5%') !== false && strpos($h, '$199') !== false && strpos($h, '10%') !== false && strpos($h, '$349') !== false);
    pcCheck("$c: names the page subject", strpos($h, 'Metallica') !== false);
    pcCheck("$c: keeps the checkout caveat and the promo page link", stripos($h, 'accept') !== false && strpos($h, 'href="/tickets-promo-code"') !== false && strpos($h, 'id="promocode"') !== false);
    preg_match('#so-evp-promo__sub">(.*?)</p>#', $h, $m); $intros[$c] = $m[1] ?? '';
}
pcCheck('every page type has its own intro wording', count(array_unique($intros)) === count($contexts));
pcCheck('unknown context still renders both codes', substr_count(soSpecPromoMarkup('', 'X', false, ''), 'offer-copy-btn') === 2);
pcCheck('% in the subject is not treated as a placeholder', strpos(soPromoFill('Take %d%% off %s', '100%s Rock', ['pct' => 5]), 'Take 5% off 100%s Rock') !== false);

if (!function_exists('seoClampDescription')) { function seoClampDescription($d, $max = 155) { return mb_strlen($d) <= $max ? $d : mb_substr($d, 0, $max); } }
$t = soSpecEventText('concert', ['label' => 'Metallica', 'perf' => 'Metallica', 'venue' => 'Sphere', 'city' => 'Las Vegas', 'state' => 'NV', 'day' => 'Oct 13', 'cat' => 'Concerts', 'hasTickets' => true]);
pcCheck('event description mentions promo codes while they are on', stripos(soSpecPick(155, ...$t['descs']), 'promo codes') !== false);

putenv('SO_PROMOS_OFF=1');
pcCheck('SO_PROMOS_OFF=1 empties the list', soPromoCodes() === []);
foreach ($contexts as $c) {
    $h = soSpecPromoMarkup('Latest Metallica Promo Codes in Austin', 'Metallica', $c === 'event', $c);
    pcCheck("kill switch, $c: no code, percentage, minimum or button", !preg_match($bad, $h) && stripos($h, 'offer-copy-btn') === false);
    pcCheck("kill switch, $c: heading stops promising codes", stripos(strip_tags(substr($h, 0, strpos($h, '</h2>'))), 'promo code') === false);
}
$t = soSpecEventText('concert', ['label' => 'Metallica', 'perf' => 'Metallica', 'venue' => 'Sphere', 'city' => 'Las Vegas', 'state' => 'NV', 'day' => 'Oct 13', 'cat' => 'Concerts', 'hasTickets' => true]);
pcCheck('kill switch: event description names no promo codes', stripos(soSpecPick(155, ...$t['descs']), 'promo code') === false);
putenv('SO_PROMOS_OFF');
exit($fail ? 1 : 0);
