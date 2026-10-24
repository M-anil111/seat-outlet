<?php
// The "Photo credits" link: in the footer of every page, keyboard reachable, in the HTML sitemap, and the credits page makes no blanket
// licensing claim of its own. No database. Run: php tools/test-photo-credits-link.php
$root = dirname(__DIR__);
$fail = 0;
function phCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$footer = (string) file_get_contents($root . '/footer.php');
phCheck('the footer links /image-credits as "Photo credits"', preg_match('#<li><a href="/image-credits">Photo credits</a></li>#', $footer) === 1);
phCheck('it is a plain link (focusable and activated from the keyboard): no tabindex, no hiding, no role override', preg_match('#<a href="/image-credits"[^>]*(tabindex|aria-hidden|role=|hidden)#', $footer) === 0);
// the link sits inside the policies list that is not marked aria-hidden or display:none
$pos = strpos($footer, '/image-credits'); $before = substr($footer, 0, $pos);
$open = strrpos($before, '<div class="policies"');
phCheck('it sits with the other policy links', $open !== false && strpos(substr($before, $open), '</div>') === false);
phCheck('the footer has one such link', substr_count($footer, 'href="/image-credits"') === 1);
$map = (string) file_get_contents($root . '/sitemap.php');
phCheck('the HTML sitemap lists it (the page is noindex, so the scan would miss it)', strpos($map, "'/image-credits' => 'Photo credits'") !== false);
$page = (string) file_get_contents($root . '/image-credits.php');
phCheck('the credits page makes no blanket "open licences" claim', stripos($page, 'open licen') === false);
phCheck('it says the rows are as recorded and not independently checked', stripos($page, 'recorded when we stored the picture') !== false && stripos($page, 'have not checked them independently') !== false);
phCheck('every row still shows its own licence and source', strpos($page, "imageLicenseUrl(\$r['license'])") !== false && strpos($page, "\$r['source_url']") !== false);
phCheck('it gives a way to report a mistake', strpos($page, 'href="/contact-us"') !== false && is_file($root . '/contact-us.php'));
phCheck('the page itself is still noindex', strpos($page, "\$pageRobots = 'noindex, follow'") !== false);
exit($fail ? 1 : 0);
