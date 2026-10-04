<?php
/**
 * CI check for the site's title and description rules. Fails (exit 1) when a keyword plan entry breaks them:
 *   - the final title (after soNormalizeTitle, so with the brand) is over 59 characters,
 *   - the title contains a pipe, a dash, a spaced hyphen, a colon or an ellipsis,
 *   - the description is not 120 to 155 characters, or contains a dash.
 * Run: php tools/check-seo-titles.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/title.php';   // no database needed

$plan = (require __DIR__ . '/../inc/seo-keywords.php')['plan'];
$fail = 0;
$y = date('Y');
foreach ($plan as $path => $row) {
    [, $title, $desc] = $row;
    if ($title !== null) {
        $t = soNormalizeTitle(str_replace('{Y}', $y, $title));
        $raw = str_replace('{Y}', $y, $title);
        if (mb_strlen($t) > 59) { echo "TITLE TOO LONG ($path): " . mb_strlen($t) . " $t\n"; $fail++; }
        if (preg_match('/[|\x{2013}\x{2014}\x{2026}]|\s-\s|:(?=\s|$)/u', $raw)) { echo "TITLE HAS A FORBIDDEN CHARACTER ($path): $raw\n"; $fail++; }
    }
    if ($desc !== null) {
        $d = str_replace('{Y}', $y, $desc);
        $n = mb_strlen($d);
        if ($n < 120 || $n > 155) { echo "DESCRIPTION LENGTH ($path): $n\n"; $fail++; }
        if (preg_match('/[\x{2013}\x{2014}]|\s-\s/u', $d)) { echo "DESCRIPTION HAS A DASH ($path)\n"; $fail++; }
    }
}
// The normalizer itself: whatever goes in, no forbidden character comes out.
foreach (["A | B", "A \u{2014} B", "A \u{2013} B", "A - B", "A: B", "A\u{2026}", "Rock: The Tour | Seat Outlet"] as $probe) {
    $out = soNormalizeTitle($probe);
    if (preg_match('/[|\x{2013}\x{2014}\x{2026}]|\s-\s|:(?=\s|$)/u', $out)) { echo "NORMALIZER LEAK: \"$probe\" -> \"$out\"\n"; $fail++; }
}
echo $fail === 0 ? "OK: " . count($plan) . " plan entries pass\n" : "$fail problem(s)\n";
exit($fail === 0 ? 0 : 1);
