<?php
/**
 * CI: the critical CSS files keep the rules the first paint of each page type needs. Entity and browse pages (venue, city, artist,
 * category) shifted 0.11 to 0.2 CLS in Lighthouse (16 Nov 2026) because their critical files were made from pages with no ticket data;
 * regenerating them from seeded pages (tools/seed-critical-fixtures.php) added the missing rules. This fails if a later regeneration
 * drops them. Run: php tools/test-critical-coverage.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$fail = 0;
function ccCheck(string $name, bool $ok): void { global $fail; if (!$ok) { echo "FAIL $name\n"; $fail++; } }
$need = [
    'entity' => ['.so-phero', '.so-evhero__card', '.so-evhero__main', '.so-evhero__venue', '.so-dates-wrap', '.so-filterbar', '.so-ad', '.img-credit', '.so-evhero__media img'],   // the last two: a real photo and its credit (artist pages shifted 0.16 on a phone without them)
    'browse' => ['.so-dates-wrap', '.so-filterbar', '.so-ad', '.so-allhead', '.so-chip'],
    'home'   => ['.so-feed', '.so-evc', '.tm-topbar'],
    'blog'   => ['.so-art__hero', '.so-art__wrap', '.so-art__img'],
    'content' => ['.tm-topbar', '.fs-2'],
];
foreach ($need as $group => $selectors) {
    $css = (string) @file_get_contents(__DIR__ . '/../css/critical/' . $group . '.css');
    ccCheck("critical/$group.css exists", $css !== '');
    foreach ($selectors as $sel) ccCheck("critical/$group.css has $sel", strpos($css, $sel) !== false);
}
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: critical CSS keeps its first-paint rules\n";
