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

$sum = tnStatsSummary(tnStatsToday());   // locked read: never a half-written file read as an empty day
echo sprintf("today: %d pages needed an answer, %d got none (%d%%); visitors only: %d pages, %d got none (%d%%); %d kept a stored list over an empty answer (%d%%)\n",
    $sum['pages'], $sum['noData'], (int) round($sum['rate'] * 100), $sum['visitorPages'], $sum['visitorNoData'], (int) round($sum['visitorRate'] * 100), $sum['suspect'], (int) round($sum['suspectRate'] * 100));
if ($sum['alert']) {
    $problems[] = sprintf('%d%% of %d visitor pages got no TicketNetwork data today (crawlers excluded), %d%% of all pages kept a stored list over an empty live answer',
        (int) round($sum['visitorRate'] * 100), $sum['visitorPages'], (int) round($sum['suspectRate'] * 100));
}

// Three listing pages that always have events: a 503 means the feed is down, "0 results" with a 200 means the empty-answer guard
// failed, and a 200 without the result count is an error page or a broken template.
foreach (['/concert-tickets-for-sale', '/game-day-tickets', '/buy-broadway-tickets'] as $path) {
    $ch = curl_init(HOME_URL . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 40, CURLOPT_USERAGENT => 'SeatOutlet health check', CURLOPT_HEADER => false]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $count = preg_match('/results_count">\s*([\d,]+)/', $body, $m) ? (int) str_replace(',', '', $m[1]) : null;
    echo sprintf("%s: HTTP %d, %s\n", $path, $code, $count === null ? 'no result count' : "$count results");
    if ($code !== 200) $problems[] = "$path answered HTTP $code";
    elseif ($count === null) $problems[] = "$path answered 200 without a result count (an error page, or the template changed)";
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
