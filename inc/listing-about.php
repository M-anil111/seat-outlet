<?php
/**
 * Unique "About" copy for the location listing pages (county, city, theater-in-city): built only from the events the page lists.
 * Each sentence is written only when its fact exists, so no page gets invented copy. The event count is left to the FAQ on the page,
 * which already states it, so the count is not repeated here.
 */

/** Lowest listed price among the events, as the API formats it (with cents kept when present). */
function soListingLowPriceText(array $events): string {
    $low = null; $text = '';
    foreach ($events as $e) {
        $lp = (float) ($e['pricingInfo']['lowPrice']['value'] ?? 0);
        if ($lp > 0 && ($low === null || $lp < $low)) {
            $low = $lp;
            $text = (string) ($e['pricingInfo']['lowPrice']['text']['formatted'] ?? '');
            if ($text === '') { $text = '$' . preg_replace('/\.00$/', '', number_format($lp, 2)); }
        }
    }
    return $text;
}

/**
 * $o: heading (e.g. "About Baldwin County, AL"), intro (one plain sentence about the place or category, written by the caller),
 * events (the page's listed events), total (every match), and optional 'cities' (true when the list spans several towns).
 */
function soListingAboutBlock(callable $h, array $o): void {
    $events = $o['events'] ?? []; $total = (int) ($o['total'] ?? 0);
    $partial = $total > count($events);   // the page holds only its first page of results
    $scope = $partial ? 'The events listed on this page' : 'The events on this page';
    $venues = []; $cities = []; $dates = [];
    foreach ($events as $e) {
        $vn = trim((string) ($e['venue']['text']['name'] ?? ''));
        if ($vn !== '' && !in_array($vn, $venues, true)) $venues[] = $vn;
        $cn = trim((string) ($e['city']['text']['name'] ?? ''));
        if ($cn !== '' && !in_array($cn, $cities, true)) $cities[] = $cn;
        $d = (string) ($e['date']['date'] ?? '');
        if ($d !== '') $dates[] = $d;
    }
    sort($dates);
    $paras = [];
    if (($o['intro'] ?? '') !== '') $paras[] = (string) $o['intro'];
    if (!empty($o['cities']) && count($cities) >= 2) {
        $list = array_slice($cities, 0, 3);
        $paras[] = $scope . ' take place in ' . implode(', ', array_slice($list, 0, -1)) . (count($list) > 1 ? ' and ' . end($list) : $list[0]) . ($partial || count($cities) <= 3 ? '.' : ', among other towns.');
    }
    if ($venues) {
        $vCount = count($venues);
        if (!$partial && $vCount <= 3) $where = implode(', ', array_slice($venues, 0, -1)) . ($vCount > 1 ? ' and ' . end($venues) : $venues[0]);
        elseif (!$partial) $where = $vCount . ' venues, including ' . implode(', ', array_slice($venues, 0, 3));
        else $where = 'venues such as ' . implode(', ', array_slice($venues, 0, 3));
        $paras[] = $scope . ' are at ' . $where . '.';
    }
    if (($price = soListingLowPriceText($events)) !== '') {
        $paras[] = 'Tickets for ' . ($partial ? 'the events listed here' : 'the events on this page') . ' start from ' . $price . ', so compare seats and prices before you choose.';
    }
    if (count($dates) >= 2) {
        $paras[] = ($partial ? 'Of the events listed here, the earliest is on ' : 'The earliest event on this page is on ') . date('F j, Y', strtotime($dates[0])) . ' and the latest is on ' . date('F j, Y', strtotime(end($dates))) . '.';
    }
    if (!$paras) return;
    echo '<div class="container py-4"><h2 class="h4">' . $h($o['heading']) . '</h2>';
    foreach ($paras as $p) echo '<p>' . $h($p) . '</p>';
    echo '</div>';
}
