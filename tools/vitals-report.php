<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Real-user page speed summary from the web_vitals table: the 75th percentile per page type and device, which is how
 * Google judges Core Web Vitals, plus the share of "good" page views.
 *
 *   php tools/vitals-report.php             last 7 days
 *   php tools/vitals-report.php --days=30
 */
require_once __DIR__ . '/../db/config.php';

$days = 7;
foreach ($argv as $a) { if (preg_match('/^--days=(\d+)$/', $a, $m)) $days = max(1, (int) $m[1]); }

$stmt = $mysqli->prepare('SELECT page_type, device, metric, metric_value, rating FROM web_vitals WHERE created_at >= (NOW() - INTERVAL ? DAY)');
$stmt->bind_param('i', $days);
$stmt->execute();
$groups = [];
foreach ($stmt->get_result() as $r) {
    $groups[$r['metric']][$r['page_type'] . ' / ' . $r['device']][] = [(float) $r['metric_value'], $r['rating']];
}
if (!$groups) { echo "No measurements in the last $days days.\n"; exit(0); }

$units = ['LCP' => 'ms', 'FCP' => 'ms', 'TTFB' => 'ms', 'INP' => 'ms', 'CLS' => ''];
foreach (['LCP', 'INP', 'CLS', 'FCP', 'TTFB'] as $metric) {
    if (empty($groups[$metric])) continue;
    printf("\n%s (75th percentile; %s)\n", $metric, $metric === 'CLS' ? 'good <= 0.1' : ['LCP' => 'good <= 2500 ms', 'INP' => 'good <= 200 ms', 'FCP' => 'good <= 1800 ms', 'TTFB' => 'good <= 800 ms'][$metric]);
    ksort($groups[$metric]);
    foreach ($groups[$metric] as $label => $rows) {
        $vals = array_column($rows, 0); sort($vals);
        $p75 = $vals[(int) max(0, ceil(0.75 * count($vals)) - 1)];
        $good = count(array_filter($rows, fn($r) => $r[1] === 'good'));
        printf("  %-26s p75 %8s%s   good %3d%%   n=%d\n", $label, $metric === 'CLS' ? number_format($p75, 3) : number_format($p75), $units[$metric] ? ' ' . $units[$metric] : '', round(100 * $good / count($rows)), count($rows));
    }
}
echo "\n";
