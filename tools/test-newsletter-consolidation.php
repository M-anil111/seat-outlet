<?php
// One newsletter sign-up path: every form is soLeadForm() posting to /ajax/subscribe.php, nothing public reports whether an address is
// already subscribed, and the retired endpoints do no lookups. No database needed. Run: php tools/test-newsletter-consolidation.php
define('SO_TEST', 1);
require __DIR__ . '/../inc/leads-core.php';
$fail = 0;
function nlCheck($label, $ok) { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $label . "\n"; if (!$ok) $fail++; }
$root = dirname(__DIR__);

// What the visitor is told
$new  = ['status' => 'success',   'message' => 'You are in. Watch your inbox.', 'lead_id' => 5, 'new' => true];
$dup  = ['status' => 'duplicate', 'message' => 'You are already on the list. Thank you!', 'lead_id' => 5, 'new' => false];
$pNew = soLeadPublic($new); $pDup = soLeadPublic($dup);
nlCheck('a repeat sign-up is answered exactly like a new one', $pNew['status'] === $pDup['status'] && $pNew['message'] === $pDup['message']);
nlCheck('the public answer never says "already"', stripos(json_encode($pDup), 'already') === false && $pDup['status'] === 'success');
$priceNew = soLeadPublic(['status' => 'success', 'message' => 'Done. We will email you if the price drops.']);
$priceDup = soLeadPublic(['status' => 'duplicate', 'message' => 'Done. We will email you if the price drops.']);
nlCheck('price alerts: repeat and new read the same', $priceNew['message'] === $priceDup['message'] && $priceDup['status'] === 'success');
foreach (['invalid', 'recaptcha', 'rate', 'error'] as $st) nlCheck("$st passes through unchanged", soLeadPublic(['status' => $st, 'message' => 'x'])['status'] === $st);
nlCheck('the server-side result still says duplicate (handling is preserved)', $dup['status'] === 'duplicate' && strpos((string) file_get_contents($root . '/inc/leads-core.php'), "'status' => 'duplicate'") !== false);

// The retired endpoints
$ce = (string) file_get_contents($root . '/ajax/check-email.php');
nlCheck('check-email.php reads no table', stripos($ce, 'MYSQLI') === false && stripos($ce, 'FROM leads') === false && stripos($ce, 'db/config') === false);
nlCheck('check-email.php calls no outside service', stripos($ce, 'recaptcha') === false && stripos($ce, 'curl') === false);
nlCheck("check-email.php can only answer success or invalid", preg_match_all("/'status' => /", $ce) === 2 && strpos($ce, "'duplicate'") === false);
$ne = (string) file_get_contents($root . '/newsletter-email.php');
nlCheck('newsletter-email.php sends a GET to the home page', strpos($ne, "'Location: ' . rtrim(HOME_URL, '/') . '/', true, 301") !== false);
nlCheck('newsletter-email.php logs its remaining posts', strpos($ne, 'legacy newsletter-email.php POST') !== false);
nlCheck('newsletter-email.php uses the shared handler', strpos($ne, 'soLeadSubmit(') !== false);

// Nothing on the site posts to them, and every form is the shared component
$skip = '#^(\.git|vendor|node_modules|cache|docs|tools|db/migrations)/#';
$refs = []; $forms = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($root) + 1);
    if (preg_match($skip, $rel) || !preg_match('/\.(php|js|html)$/', $rel)) continue;
    if (in_array($rel, ['newsletter-email.php', 'ajax/check-email.php', 'inc/sitemap-build.php', 'inc/css-groups.php', 'inc/leads-core.php', 'inc/contact.php', 'inc/geoip.php', 'db/config.php'], true)) continue;
    $src = (string) file_get_contents($f->getPathname());
    if (preg_match('/newsletter-email|check-email/', $src)) $refs[] = $rel;
    if (preg_match_all('/<form[^>]*action="([^"]*)"/i', $src, $m)) foreach ($m[1] as $a) if (preg_match('#subscribe\.php|newsletter#', $a)) $forms[] = $rel . ' -> ' . $a;
}
nlCheck('no page or script references the retired endpoints' . ($refs ? ': ' . implode(', ', $refs) : ''), !$refs);
nlCheck('the only subscribe form is soLeadForm -> /ajax/subscribe.php' . ($forms ? ': ' . implode('; ', array_unique($forms)) : ''), array_unique($forms) === ['inc/leads.php -> /ajax/subscribe.php']);
nlCheck('the blog box is built from soLeadForm', strpos((string) file_get_contents($root . '/inc/blog-render.php'), 'soLeadForm(') !== false);
nlCheck('the script posts only to /ajax/subscribe.php', substr_count((string) file_get_contents($root . '/js/lead-capture.js'), "fetch('/ajax/subscribe.php'") === 1);
nlCheck('/ajax/subscribe.php keeps the honeypot and the shared submit', ($s = (string) file_get_contents($root . '/ajax/subscribe.php')) && strpos($s, "soPost('website')") !== false && strpos($s, 'soLeadSubmit(') !== false);
nlCheck('unsubscribe page still exists', is_file($root . '/unsubscribe.php'));
exit($fail ? 1 : 0);
