<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Raises a Sentry warning when TicketNetwork is failing visitors: today's no-data share is over the threshold (tnStatsSummary), or a
 * key listing page answers with no events. Run every 15 minutes. Prints what it checked; exits 1 when something is wrong, so a cron
 * mailer notices as well.
 *
 *   php cron/check-tn-health.php
 */
require_once __DIR__ . '/../functions.php';

$problems = [];

$stats = json_decode((string) @file_get_contents(cache_dir() . 'tn_stats_' . date('Y-m-d') . '.json'), true);
$sum = tnStatsSummary(is_array($stats) ? $stats : []);
echo sprintf("today: %d pages needed an answer, %d got none (%d%%)\n", $sum['needed'], $sum['noData'], (int) round($sum['rate'] * 100));
if ($sum['alert']) $problems[] = sprintf('%d%% of %d visitor pages got no TicketNetwork data today', (int) round($sum['rate'] * 100), $sum['needed']);

// Three listing pages that always have events: a 503 means the feed is down, "0 results" with a 200 means the empty-answer guard failed.
foreach (['/concert-tickets-for-sale', '/game-day-tickets', '/buy-broadway-tickets'] as $path) {
    $ch = curl_init(HOME_URL . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_USERAGENT => 'SeatOutlet health check', CURLOPT_HEADER => false]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $count = preg_match('/results_count">\s*([\d,]+)/', $body, $m) ? (int) str_replace(',', '', $m[1]) : null;
    echo sprintf("%s: HTTP %d, %s\n", $path, $code, $count === null ? 'no result count' : "$count results");
    if ($code !== 200) $problems[] = "$path answered HTTP $code";
    elseif ($count === 0) $problems[] = "$path shows 0 results";
    sleep(2);
}

if ($problems) {
    $message = 'TicketNetwork health check: ' . implode('; ', $problems);
    if (function_exists('\Sentry\captureMessage')) {
        \Sentry\withScope(function ($scope) use ($message) {
            $scope->setLevel(\Sentry\Severity::warning());
            $scope->setFingerprint(['tn-health-check']);
            \Sentry\captureMessage($message);
        });
    }
    echo "PROBLEM: $message\n";
    exit(1);
}
echo "ok\n";
