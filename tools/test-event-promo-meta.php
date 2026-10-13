<?php
// Event meta descriptions mention promo codes and stay within 155 characters. Run: php tools/test-event-promo-meta.php
require __DIR__ . '/../inc/page-spec.php';
// functions.php needs the database; the clamp is the only helper page-spec.php borrows from it, so a plain cut stands in for it here
if (!function_exists('seoClampDescription')) { function seoClampDescription($d, $max = 155) { return mb_strlen($d) <= $max ? $d : mb_substr($d, 0, $max); } }
$fail = 0;
function pmCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
foreach (['concert', 'sports', 'theater', 'festival'] as $kind) {
    $t = soSpecEventText($kind, ['label' => 'Dog Man The Musical', 'perf' => 'Dog Man The Musical', 'venue' => 'Kirk Douglas Theatre', 'city' => 'Culver City', 'state' => 'CA', 'day' => 'Oct 13', 'cat' => 'Theater', 'hasTickets' => true]);
    $d = soSpecPick(155, ...$t['descs']);
    pmCheck("$kind: description mentions promo codes", stripos($d, 'promo codes') !== false);
    pmCheck("$kind: description fits 155", mb_strlen($d) <= 155);
}
$long = soSpecEventText('concert', ['label' => str_repeat('Very Long Artist Name ', 4), 'perf' => str_repeat('Very Long Artist Name ', 4), 'venue' => 'The Extremely Long Named Amphitheater and Pavilion', 'city' => 'Winston-Salem', 'state' => 'NC', 'hasTickets' => true]);
pmCheck('a very long name still fits 155', mb_strlen(soSpecPick(155, ...$long['descs'])) <= 155);
exit($fail ? 1 : 0);
