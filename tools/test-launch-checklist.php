<?php
// docs/launch-checklist.md stays honest: every bullet carries a status ([FIXED], [EXTERNAL] or [OPEN]), and the facts the checklist states about the
// code are checked against the code, so a statement cannot go stale unnoticed. No database. Run: php tools/test-launch-checklist.php
$root = dirname(__DIR__);
$doc = (string) file_get_contents($root . '/docs/launch-checklist.md');
$fail = 0;
function lcCheck($label, $ok, $detail = '') { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . ($ok || $detail === '' ? '' : ': ' . $detail) . "\n"; if (!$ok) $fail++; }
$bad = [];
foreach (explode("\n", $doc) as $n => $l) {
    if (strpos($l, '- ') !== 0) continue;
    if (!preg_match('/^- \[(FIXED|EXTERNAL|OPEN)\b[^\]]*\] /', $l)) $bad[] = 'line ' . ($n + 1) . ': ' . substr($l, 0, 50);
}
lcCheck('every top-level bullet starts with a status tag', !$bad, implode('; ', $bad));
lcCheck('the status key is present', strpos($doc, '**Status key') !== false);
lcCheck('no EXTERNAL item is also tagged FIXED alone', !preg_match('/^- \[FIXED\] .*(TicketNetwork has to|confirm with TicketNetwork)/m', $doc));

// facts the checklist states
$footer = (string) file_get_contents($root . '/footer.php');
lcCheck('the footer links /sitemap (not the XML file) and /image-credits', strpos($footer, 'href="/sitemap"') !== false && strpos($footer, 'href="/sitemap.xml"') === false && strpos($footer, 'href="/image-credits"') !== false);
lcCheck('a privacy bar exists (the checklist says so)', is_file($root . '/js/consent.js') && is_file($root . '/inc/consent.php') && stripos($doc, 'no consent banner exists') === false);
lcCheck('the service worker, the idle nudge and price alerts exist (S3, S4, S5)', is_file($root . '/sw.js') && strpos($footer, "register('/sw.js')") !== false && is_file($root . '/js/idle-nudge.js') && is_file($root . '/db/migrations/0039_price_alerts.sql'));
lcCheck('the legacy newsletter files are marked retired, not live', stripos($doc, 'newsletter-email.php can be retired') === false && strpos(file_get_contents($root . '/newsletter-email.php'), 'Retired newsletter form target') !== false);
$hdr = (string) file_get_contents($root . '/header.php');
lcCheck('the header wording the checklist still flags is still there (remove the flag when it changes)', (strpos($hdr, 'Trusted resale marketplace') !== false) === (strpos($doc, 'Trusted resale marketplace') !== false) && (strpos($footer, 'By continuing past this page') !== false) === (strpos($doc, 'By continuing past this page') !== false));
lcCheck('the checkout review page is still not linked from the site (F1)', !preg_match('#href="/checkout#', $footer . $hdr));
lcCheck('promo codes: the checklist and the code agree they are shown', strpos($doc, 'TAKE5') !== false && strpos((string) file_get_contents($root . '/inc/page-spec.php'), "function soPromoCodes()") !== false);
lcCheck('the artist directory fix is described as it is: letter pages only', strpos($doc, 'unfiltered A to Z view') !== false && strpos((string) file_get_contents($root . '/inc/directories.php'), 'function soDirSortKey') !== false);
lcCheck('the precheckout switch the checklist names exists', strpos($doc, 'SO_PRECHECKOUT=review') !== false && strpos((string) file_get_contents($root . '/event.php'), "getenv('SO_PRECHECKOUT')") !== false);
lcCheck('the CI jobs the checklist names exist', strpos((string) file_get_contents($root . '/.github/workflows/ci.yml'), 'mariadb:10.11') !== false && strpos((string) file_get_contents($root . '/.github/workflows/ci.yml'), 'csp-browser:') !== false);
exit($fail ? 1 : 0);
