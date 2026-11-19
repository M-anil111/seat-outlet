<?php
/**
 * CI: the photo credit lines (".img-credit", ".so-phero__credit") are readable: at least 4.5:1 against white (WCAG 2.1 AA for 12px
 * text), with no opacity that lowers it. axe (19 Nov 2026) found 2.87:1 (#9aa3ad at 85% opacity) and then 3.62:1 (#86868b) on venue pages.
 * Checks the source stylesheet, the built site stylesheet and the entity critical CSS (the first paint).
 * Run: php tools/test-credit-contrast.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
$root = dirname(__DIR__);
$fail = 0;
function creditContrastCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }

/** WCAG contrast ratio of a #rrggbb colour against white. */
function creditContrastRatio(string $hex): float {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    $lin = function (int $c): float { $s = $c / 255; return $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4; };
    $l = 0.2126 * $lin(hexdec(substr($hex, 0, 2))) + 0.7152 * $lin(hexdec(substr($hex, 2, 2))) + 0.0722 * $lin(hexdec(substr($hex, 4, 2)));
    return 1.05 / ($l + 0.05);
}
creditContrastCheck('the ratio formula: black on white is 21:1', abs(creditContrastRatio('#000000') - 21.0) < 0.01);
creditContrastCheck('the ratio formula: #9aa3ad is under 4.5:1 (the old colour)', creditContrastRatio('#9aa3ad') < 4.5);

$files = ['css/style.css', 'css/style.min.css', 'css/critical/entity.css'];
foreach ($files as $rel) {
    $css = (string) @file_get_contents($root . '/' . $rel);
    creditContrastCheck("$rel exists", $css !== '');
    foreach (['.img-credit', '.so-phero__credit'] as $sel) {
        // The rule whose selector is exactly this class (not ".x .img-credit" variants for dark heroes, which use light text on a photo).
        if (!preg_match_all('/(?:^|[}\s])' . preg_quote($sel, '/') . '\s*\{([^}]*)\}/m', $css, $m)) continue;
        foreach ($m[1] as $body) {
            if (preg_match('/(?:^|;)\s*color\s*:\s*(#[0-9a-f]{3,6})\b/i', $body, $c)) {
                $r = creditContrastRatio($c[1]);
                creditContrastCheck("$rel $sel colour {$c[1]} is at least 4.5:1 on white", $r >= 4.5, round($r, 2) . ':1');
            }
            if (preg_match('/opacity\s*:\s*([0-9.]+)/', $body, $o)) creditContrastCheck("$rel $sel has no opacity under 1", (float) $o[1] >= 1.0, $o[1]);
        }
    }
}
if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: photo credit text meets WCAG AA contrast\n";
