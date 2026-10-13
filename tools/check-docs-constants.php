<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * The reliability docs must state the same windows and limits as the code. The constants are read from the source, turned into the
 * wording the docs use, and looked for in docs/reliability.md and CONTRIBUTING.md. Run: php tools/check-docs-constants.php
 */
$root = dirname(__DIR__);
$read = function (string $file, string $const) use ($root) {
    $src = file_get_contents($root . '/' . $file);
    if (!preg_match('/^const ' . $const . ' = (\d+);/m', $src, $m)) { fwrite(STDERR, "FAIL: $const not found in $file\n"); exit(1); }
    return (int) $m[1];
};
$human = function (int $seconds): string {
    if ($seconds % 86400 === 0 && $seconds > 86400) return ($seconds / 86400) . ' days';
    if ($seconds % 3600 === 0) return ($seconds / 3600) . ' hour' . ($seconds === 3600 ? '' : 's');
    return $seconds . ' seconds';
};

$budget = file_get_contents($root . '/inc/tn-budget.php');
if (!preg_match('/\? (\d+(?:\.\d+)?) : max\(0\.0, \(float\) \$v\)/', $budget, $m)) { fwrite(STDERR, "FAIL: default TN_CALLS_PER_SECOND not found\n"); exit(1); }
$defaultRate = (float) $m[1];

$expect = [
    'docs/reliability.md' => [
        '| ' . $human($read('inc/tn-listing-copy.php', 'TN_STALE_LISTING')) . ' |',
        '| ' . $human($read('inc/tn-listing-copy.php', 'TN_LISTING_COPY_TTL')) . ' |',
        '| ' . $human($read('inc/tn-listing-copy.php', 'TN_STALE_ON_ERROR')) . ' |',
        'Default ' . rtrim(rtrim(number_format($defaultRate, 1), '0'), '.') . ' call' . ($defaultRate == 1 ? '' : 's') . ' a second',
        'open for ' . $read('functions.php', 'TN_BREAKER_THROTTLE_SECONDS') . ' seconds',
    ],
    'CONTRIBUTING.md' => [
        $human($read('inc/tn-listing-copy.php', 'TN_STALE_LISTING')),
        $human($read('inc/tn-listing-copy.php', 'TN_LISTING_COPY_TTL')),
        $human($read('inc/tn-listing-copy.php', 'TN_STALE_ON_ERROR')),
        $read('functions.php', 'TN_BREAKER_SECONDS') . ' seconds',
        $read('functions.php', 'TN_BREAKER_THROTTLE_SECONDS') . ' seconds',
    ],
];

$fails = 0;
foreach ($expect as $file => $needles) {
    $doc = file_get_contents($root . '/' . $file);
    foreach ($needles as $n) {
        $ok = preg_match('/(?<!\w)' . preg_quote($n, '/') . '/u', $doc) === 1;   // "2 hours" must not be satisfied by "12 hours"
        echo ($ok ? 'ok   ' : 'FAIL ') . "$file states \"$n\"\n";
        if (!$ok) $fails++;
    }
}
echo $fails ? "\n$fails failed: update the docs (or the code) so they agree\n" : "\ndocs match the code constants\n";
exit($fails ? 1 : 0);
