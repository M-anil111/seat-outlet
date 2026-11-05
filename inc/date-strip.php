<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The date strip: a row of day pills under the page header, the way cinema booking apps show dates, on every page that lists
 * events. No extra feed call in either mode.
 *
 *   calendar mode  city, venue, county, state, country, category hubs, genre and league pages, discovery pages: "All dates",
 *                  today and the next SO_DATE_STRIP_DAYS - 1 days as plain links (?when=YYYY-MM-DD), then a date picker for a
 *                  later day. The server filters (listingRequestState / listingDateRange), so the results are exact, "More events"
 *                  keeps working, and nothing needs JavaScript. A filtered view is noindex and canonical to the base address, the
 *                  same as the quick filters.
 *   events mode    artist pages, where every upcoming date for the artist is already on the page: pills for the days the loaded
 *                  rows fall on, each with its count. The browser (js/events-listing.js, data-so-dates) builds them from the rows'
 *                  data-so-day and hides the other rows; rows that "More events" adds extend the strip. The shell stays hidden
 *                  without JavaScript, so nothing is promised that cannot work.
 */

const SO_DATE_STRIP_DAYS = 14;

/**
 * $o: mode ('calendar' | 'events'); calendar also takes basePath, when, sort, defaultSort, max (price cap, 0 = none);
 *     noun (what the list holds, for the accessible label, e.g. "events in Austin, TX").
 */
function soRenderDateStrip(array $o): void {
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $mode = ($o['mode'] ?? 'calendar') === 'events' ? 'events' : 'calendar';
    $noun = trim((string) ($o['noun'] ?? 'events'));
    $prev = '<button type="button" class="so-subcats__arrow so-subcats__arrow--prev" data-so-subs-prev aria-label="Earlier dates" hidden><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>';
    $next = '<button type="button" class="so-subcats__arrow so-subcats__arrow--next" data-so-subs-next aria-label="Later dates" hidden><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>';

    if ($mode === 'events') {
        // The pills are built in the browser from the rows (data-so-day); the shell is hidden until the script has filled it.
        echo '<div class="so-dates-wrap" data-so-subs data-so-dates="events" data-so-dates-noun="' . $h($noun) . '" hidden>' . $prev
            . '<div class="so-dates" data-so-subs-track role="group" aria-label="Pick a date for ' . $h($noun) . '"></div>' . $next . '</div>';
        return;
    }

    $basePath = (string) ($o['basePath'] ?? '');
    $when = (string) ($o['when'] ?? '');
    $sort = (string) ($o['sort'] ?? '');
    $defaultSort = (string) ($o['defaultSort'] ?? 'popular');
    $max = (int) ($o['max'] ?? 0);
    $url = function (string $w) use ($basePath, $sort, $defaultSort, $max) {
        $q = array_filter(['when' => $w, 'sort' => $sort === $defaultSort ? '' : $sort, 'max' => $max ?: '']);
        return $basePath . ($q ? '?' . http_build_query($q) : '');
    };
    $today = new DateTimeImmutable('today');
    $isDay = preg_match('/^\d{4}-\d{2}-\d{2}$/', $when) === 1;
    $inStrip = false;
    $pills = '';
    for ($i = 0; $i < SO_DATE_STRIP_DAYS; $i++) {
        $d = $today->modify("+$i days");
        $ymd = $d->format('Y-m-d');
        $on = $isDay && $when === $ymd;
        if ($on) $inStrip = true;
        $sub = $i === 0 ? 'Today' : ($i === 1 ? 'Tmrw' : $d->format('M'));
        $pills .= '<a class="so-date' . ($on ? ' so-date--on' : '') . ($i === 0 ? ' so-date--today' : '') . '" href="' . $h($url($ymd)) . '"' . ($on ? ' aria-current="true"' : '')
            . ' aria-label="' . $h($d->format('l, F j')) . '" rel="nofollow">'
            . '<span class="so-date__top">' . $d->format('D') . '</span><span class="so-date__num">' . $d->format('d') . '</span><span class="so-date__sub">' . $sub . '</span></a>';
    }
    // A picked day beyond the strip shows as its own selected pill, so the chosen date is always visible.
    if ($isDay && !$inStrip) {
        $d = new DateTimeImmutable($when);
        $pills .= '<a class="so-date so-date--on" href="' . $h($url($when)) . '" aria-current="true" aria-label="' . $h($d->format('l, F j, Y')) . '" rel="nofollow">'
            . '<span class="so-date__top">' . $d->format('D') . '</span><span class="so-date__num">' . $d->format('d') . '</span><span class="so-date__sub">' . $d->format('M') . '</span></a>';
    }
    $allOn = $when === '';
    $all = '<a class="so-date so-date--all' . ($allOn ? ' so-date--on' : '') . '" href="' . $h($url('')) . '"' . ($allOn ? ' aria-current="true"' : '') . '>'
        . '<span class="so-date__top">All</span><span class="so-date__num"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></span><span class="so-date__sub">dates</span></a>';
    // Any later day: a native date field that submits on change (and a Go button without JavaScript).
    $pick = '<form class="so-date so-date--pick" method="get" action="' . $h($basePath) . '" data-so-dates-pick>'
        . '<label class="so-date__top" for="soDatePick">Other</label>'
        . '<input id="soDatePick" class="so-date__input" type="date" name="when" min="' . $today->format('Y-m-d') . '" max="' . $today->modify('+' . SO_WHEN_DAY_MAX_DAYS . ' days')->format('Y-m-d') . '" required aria-label="Pick any date">'
        . ($sort !== '' && $sort !== $defaultSort ? '<input type="hidden" name="sort" value="' . $h($sort) . '">' : '')
        . ($max > 0 ? '<input type="hidden" name="max" value="' . $max . '">' : '')
        . '<button type="submit" class="so-date__go">Go</button></form>';
    echo '<div class="so-dates-wrap" data-so-subs data-so-dates="calendar">' . $prev
        . '<nav class="so-dates" data-so-subs-track aria-label="Pick a date for ' . $h($noun) . '">' . $all . $pills . $pick . '</nav>' . $next . '</div>';
}
