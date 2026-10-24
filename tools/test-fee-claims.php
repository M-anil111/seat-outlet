<?php
// No page, e-mail or script says whether listed prices include fees until TicketNetwork confirms it (docs/ticketnetwork-api.md questions 14-15,
// docs/launch-checklist.md). The honest wording is "check the total at checkout". No database. Run: php tools/test-fee-claims.php
$root = dirname(__DIR__);
$fail = 0;
function fcCheck($label, $ok, $detail = '') { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . ($ok || $detail === '' ? '' : ': ' . $detail) . "\n"; if (!$ok) $fail++; }
$banned = '/no hidden fees|all-in price|all in price|fees (are )?included|includes? (all )?fees|including all fees|price includes|prices? (are )?shown before fees|before fees|plus fees|excluding fees|fee-free|no (service|booking) fees/i';
$hits = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($root) + 1);
    if (preg_match('#^(\.git|vendor|node_modules|cache|docs|tools|db|phpmailer|admin|worker)/#', $rel) || !preg_match('/\.(php|js|html)$/', $rel) || substr($rel, -7) === '.min.js') continue;
    if (in_array($rel, ['dotbooker.php'], true)) continue;   // a partner page about another product
    foreach (file($f->getPathname()) as $n => $line) {
        $scan = str_ireplace('final total, including all fees', '', $line);   // checkout.php: about the order's final total, which checkout shows (wording to confirm with TicketNetwork and counsel, docs/ticketnetwork-api.md)
        if (preg_match($banned, $scan, $m)) $hits[] = $rel . ':' . ($n + 1) . ' "' . $m[0] . '"';
    }
}
fcCheck('no unverified statement about fees in prices anywhere in the site copy', !$hits, implode('; ', $hits));
$mail = (string) file_get_contents($root . '/inc/lead-mail.php');
fcCheck('alert e-mails tell readers to check the total at checkout', substr_count($mail, 'Check the total at checkout before you pay.') === 3);
$ev = (string) file_get_contents($root . '/event.php');
fcCheck('the review screen is off unless SO_PRECHECKOUT=review', strpos($ev, "'precheckout' => getenv('SO_PRECHECKOUT') === 'review' ? 'review' : 'skip'") !== false);
exit($fail ? 1 : 0);
