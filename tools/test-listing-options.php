<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline smoke test for the listing options: every date window, sort and price cap the pages offer builds a request the API accepts,
 * and every filter the site builds passes the load-more allowlist (so "Load more" never empties a filtered list, QA-001).
 * Run: php tools/test-listing-options.php
 */
require_once __DIR__ . '/../inc/listing-filter.php';

$root = dirname(__DIR__);
$src = file_get_contents($root . '/functions.php');
$code = '';
foreach (['LISTING_WHEN', 'LISTING_SORT'] as $c) {
    if (!preg_match('/^const ' . $c . ' = .*?;$/m', $src, $m)) { fwrite(STDERR, "FAIL: $c not found\n"); exit(1); }
    $code .= $m[0] . "\n";
}
foreach (['soEventHorizon', 'listingDateRange', 'listingSortParams', 'locationListingParams'] as $f) {
    // a one-line function (soEventHorizon) ends on its own line; a block function ends at the first "}" in column 0
    if (!preg_match('/^function ' . $f . '\(.*\}\s*$/m', $src, $m) && !preg_match('/^function ' . $f . '\(.*?\n}\n/ms', $src, $m)) { fwrite(STDERR, "FAIL: $f not found\n"); exit(1); }
    $code .= $m[0] . "\n";
}
$lst = file_get_contents($root . '/inc/listing.php');
if (!preg_match('/^const LISTING_PRICE = .*?;$/m', $lst, $m)) { fwrite(STDERR, "FAIL: LISTING_PRICE not found\n"); exit(1); }
$code .= $m[0] . "\n";
eval($code);

$fails = 0;
function loCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

// 1. Date windows: each quick filter is a real, forward range
$today = date('Y-m-d');
loCheck('any time: no range (today to the horizon)', listingDateRange('') === null);
loCheck('today: one day', listingDateRange('today') === [$today, $today]);
foreach (['weekend' => 7, 'week' => 8, 'month' => 31] as $when => $maxDays) {
    $r = listingDateRange($when);
    $ok = is_array($r) && $r[0] >= $today && $r[1] >= $r[0] && (strtotime($r[1]) - strtotime($r[0])) <= $maxDays * 86400;
    loCheck("$when: a forward range of at most $maxDays days", $ok);
}
loCheck('the horizon is three years out (date-TBA events are parked decades out)', soEventHorizon() === date('Y-m-d', strtotime('+3 years')));

// 2. Every option combination builds a filter with a date range and the ticket flag, the right sort, the price cap when asked,
//    and a filter the load-more allowlist accepts
$fragment = "contains(defaultCategory/path, '.1859.1986.') and country/alphaCode eq 'US'";
$combos = 0;
foreach (array_merge([''], array_keys(LISTING_WHEN)) as $when) {
    foreach (array_keys(LISTING_SORT) as $sort) {
        foreach (array_merge([0], array_keys(LISTING_PRICE)) as $max) {
            $p = locationListingParams($fragment, 20, 1, $when, $sort, $max);
            $f = (string) $p['filter'];
            $label = sprintf('when=%s sort=%s max=%d', $when === '' ? 'any' : $when, $sort, $max);
            $ok = preg_match('/date\/date ge \d{4}-\d{2}-\d{2} and date\/date le \d{4}-\d{2}-\d{2}/', $f) === 1
                && strpos($f, '_metadata/hasTickets eq true') !== false
                && (($max > 0) === (strpos($f, 'pricingInfo/lowPrice/value le ' . $max) !== false))
                && ($sort === 'soonest' ? ($p['sort'] ?? '') === 'date/date' : ($sort === 'price' ? ($p['sort'] ?? '') === 'pricingInfo/lowPrice/value' : ($p['sort'] ?? '') === '-salesRank' && ($p['salesRankOptions'] ?? '') === '{"interval":"day","metric":"orderVolume"}'))
                && $p['perPage'] === 20 && $p['page'] === 1 && $p['includeTotalCount'] === 'true'
                && soLoadMoreFilterOk($f);
            if (!$ok) loCheck("$label builds a valid request", false);
            $combos++;
        }
    }
}
loCheck("all $combos option combinations build a valid, allowlisted request", $fails === 0);

// 3. The search page clamps a typed start date to today (past dates are never searched)
$search = file_get_contents($root . '/search.php');
loCheck('search: a typed start date in the past is clamped to today', strpos($search, "\$startDate = max(date('Y-m-d'), date('Y-m-d', \$startTimestamp));") !== false);
loCheck('search: the ticket flag is asked for first, the whole list only as the fallback', strpos($search, "'_metadata/hasTickets eq true'") !== false);

echo $fails ? "\n$fails failed\n" : "\nlisting options: all passed\n";
exit($fails ? 1 : 0);
