<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline test for the date strip (inc/date-strip.php): calendar mode prints "All dates", today and the next days as plain links
 * carrying ?when=YYYY-MM-DD (and the sort / price cap), a picker for later days, and the chosen day as the one selected pill;
 * events mode prints only the hidden shell the script fills. Every page type that lists events calls it, and every event row
 * carries data-so-day for the browser-side filter. Run: php tools/test-date-strip.php
 */
$root = dirname(__DIR__);
$fail = 0;
function dateStripCheck(string $name, bool $ok): void { global $fail; if (!$ok) { echo "FAIL $name\n"; $fail++; } }

// The component on its own: its constants and the two functions it needs from functions.php.
$src = (string) file_get_contents($root . '/functions.php');
if (!preg_match('/^const SO_WHEN_DAY_MAX_DAYS = .*?;$/m', $src, $m)) { echo "FAIL: SO_WHEN_DAY_MAX_DAYS not found\n"; exit(1); }
eval($m[0]);
require_once $root . '/inc/date-strip.php';

$render = function (array $o): string { ob_start(); soRenderDateStrip($o); return (string) ob_get_clean(); };
$today = date('Y-m-d');
$day3 = date('Y-m-d', strtotime('+3 days'));

$html = $render(['mode' => 'calendar', 'basePath' => '/city/austin-tx', 'when' => '', 'sort' => 'soonest', 'defaultSort' => 'soonest', 'noun' => 'events in Austin, TX']);
dateStripCheck('calendar: All dates is selected when no day is chosen', preg_match('/so-date so-date--all so-date--on" href="\/city\/austin-tx" aria-current="true"/', $html) === 1);
dateStripCheck('calendar: today is the first day pill and says Today', strpos($html, 'href="/city/austin-tx?when=' . $today . '"') !== false && strpos($html, 'so-date--today') !== false && strpos($html, '>Today</span>') !== false);
dateStripCheck('calendar: ' . SO_DATE_STRIP_DAYS . ' day links', substr_count($html, '?when=') === SO_DATE_STRIP_DAYS);
dateStripCheck('calendar: the last day link is ' . (SO_DATE_STRIP_DAYS - 1) . ' days out', strpos($html, '?when=' . date('Y-m-d', strtotime('+' . (SO_DATE_STRIP_DAYS - 1) . ' days')) . '"') !== false);
dateStripCheck('calendar: day links are nofollow (filtered views stay out of the index)', substr_count($html, 'rel="nofollow"') === SO_DATE_STRIP_DAYS);
dateStripCheck('calendar: a date picker form submits when as GET to the page', preg_match('/<form class="so-date so-date--pick" method="get" action="\/city\/austin-tx"/', $html) === 1 && strpos($html, 'type="date" name="when" min="' . $today . '"') !== false);
dateStripCheck('calendar: the picker max is the same window soWhenParam accepts', strpos($html, 'max="' . date('Y-m-d', strtotime('+' . SO_WHEN_DAY_MAX_DAYS . ' days')) . '"') !== false);
dateStripCheck('calendar: the default sort is not written into the links', strpos($html, 'sort=') === false);
dateStripCheck('calendar: scroll arrows and the shared scroller hooks', strpos($html, 'data-so-subs-prev') !== false && strpos($html, 'data-so-subs-next') !== false && strpos($html, 'data-so-subs-track') !== false);
dateStripCheck('calendar: accessible label names the list', strpos($html, 'aria-label="Pick a date for events in Austin, TX"') !== false);

$html = $render(['mode' => 'calendar', 'basePath' => '/concert-tickets-for-sale', 'when' => $day3, 'sort' => 'price', 'defaultSort' => 'popular', 'max' => 50, 'noun' => 'concert tickets']);
dateStripCheck('calendar: the chosen day is the one selected pill', substr_count($html, 'so-date--on') === 1 && preg_match('/so-date--on" href="\/concert-tickets-for-sale\?when=' . $day3 . '&amp;sort=price&amp;max=50" aria-current="true"/', $html) === 1);
dateStripCheck('calendar: sort and price cap travel with every day link', substr_count($html, '&amp;sort=price&amp;max=50') === SO_DATE_STRIP_DAYS && strpos($html, 'href="/concert-tickets-for-sale?sort=price&amp;max=50"') !== false);
dateStripCheck('calendar: sort and price cap travel with the picker', strpos($html, '<input type="hidden" name="sort" value="price">') !== false && strpos($html, '<input type="hidden" name="max" value="50">') !== false);

$far = date('Y-m-d', strtotime('+60 days'));
$html = $render(['mode' => 'calendar', 'basePath' => '/venue/x', 'when' => $far, 'sort' => 'soonest', 'defaultSort' => 'soonest']);
dateStripCheck('calendar: a day beyond the strip is shown as its own selected pill', substr_count($html, 'so-date--on') === 1 && strpos($html, 'href="/venue/x?when=' . $far . '" aria-current="true"') !== false);

$html = $render(['mode' => 'calendar', 'basePath' => '/city/x', 'when' => 'weekend', 'sort' => 'soonest', 'defaultSort' => 'soonest']);
dateStripCheck('calendar: a quick-filter word selects no day pill and not All', strpos($html, 'so-date--on') === false);

$html = $render(['mode' => 'events', 'noun' => 'Taylor Swift']);
dateStripCheck('events: a hidden shell the script fills, no links', strpos($html, 'data-so-dates="events"') !== false && strpos($html, ' hidden>') !== false && strpos($html, '<a ') === false && strpos($html, 'aria-label="Pick a date for Taylor Swift"') !== false);
dateStripCheck('events: no HTML from the noun', strpos($render(['mode' => 'events', 'noun' => '<b>x</b>']), '<b>') === false);

// Every events list has the strip, and every row carries the day.
$f = $src;
dateStripCheck('artist location pages render the strip (events mode) before the rows', substr_count($f, "soRenderDateStrip(['mode' => 'events'") === 2);
dateStripCheck('performer page renders the strip unless weekend chips are shown', strpos((string) file_get_contents($root . '/performer.php'), "if (!\$weekendGroups) { soRenderDateStrip(['mode' => 'events'") !== false);
dateStripCheck('entity pages (city, venue, county, state, country) render the calendar strip', strpos((string) file_get_contents($root . '/inc/entity-listing.php'), "soRenderDateStrip(['mode' => 'calendar', 'basePath' => \$c['path'], 'when' => \$c['when']") !== false);
dateStripCheck('listing pages (hubs, genres, leagues, discovery) render the calendar strip with the filters', strpos($f, "\$strip = ['mode' => 'calendar', 'basePath' => \$basePath, 'when' => \$when") !== false && strpos($f, "if (!\$explored) soRenderDateStrip(\$strip);") !== false);
dateStripCheck('with the explore bar the strip sits under the filter chips, above the list, not above the category tabs', strpos($f, "if (!empty(\$opts['dateStrip']) && is_array(\$opts['dateStrip'])) soRenderDateStrip(\$opts['dateStrip']);") !== false && strpos($f, "\$dd('sort', \$icSort, 'Sort', \$nearSorts, \$sort);") < strpos($f, "soRenderDateStrip(\$opts['dateStrip'])") && strpos($f, "soRenderDateStrip(\$opts['dateStrip'])") < strpos($f, '<section class="so-near" data-so-near'));
foreach (['inc/entity-pages.php', 'inc/listing.php', 'performer.php'] as $file) {
    dateStripCheck("$file rows carry data-so-day", strpos((string) file_get_contents($root . '/' . $file), "data-so-day=\"' . date('Y-m-d'") !== false);
}
dateStripCheck('artist location rows carry data-so-day', substr_count($f, "data-so-day=\"' . date('Y-m-d', \$timestamp)") === 2);
$js = (string) file_get_contents($root . '/js/events-listing.js');
dateStripCheck('browser-rendered rows carry data-so-day', strpos($js, "data-so-day=\"' + soDay + '\"") !== false);
dateStripCheck('the script filters rows by data-so-day and never fetches on its own', strpos($js, "row.classList.toggle('so-day--off', !!selected && d !== selected)") !== false && strpos((string) file_get_contents($root . '/css/style.css'), '.performer-event-item.so-day--off { display: none !important; }') !== false && strpos($js, "data-so-dates-more") !== false && preg_match('/data-so-dates\][\s\S]*fetch\(/', substr($js, strpos($js, 'Date strip (inc/date-strip.php)'))) === 0);
dateStripCheck('the picked date submits the form', strpos($js, "pick.submit()") !== false);
dateStripCheck('every reader of ?when validates it the same way', !preg_match('/isset\(LISTING_WHEN\[\$_GET/', $src . file_get_contents($root . '/inc/discovery-pages.php') . file_get_contents($root . '/inc/holidays.php') . file_get_contents($root . '/search.php')));
dateStripCheck('the nationwide / near-you feed reads a picked day (it used to drop it)', strpos((string) file_get_contents($root . '/ajax/get-home-feed.php'), "\$when = soWhenParam(soQs('when'));") !== false && strpos((string) file_get_contents($root . '/ajax/get-home-feed.php'), 'isset(LISTING_WHEN[$whenIn])') === false);
foreach (glob($root . '/ajax/*.php') as $endpoint) {
    $code = (string) file_get_contents($endpoint);
    dateStripCheck(basename($endpoint) . ' does not validate ?when with the quick-word list alone', !preg_match('/isset\(LISTING_WHEN\[\$\w+\]\)\s*\?\s*\$\w+\s*:\s*\x27\x27/', $code));
}

dateStripCheck('a degraded feed never prints "0 results" as a count', strpos((string) file_get_contents($root . '/inc/listing.php'), "Listings loading") !== false && strpos((string) file_get_contents($root . '/inc/listing.php'), "soApiDegraded()") !== false);
foreach (['performer.php', 'functions.php', 'inc/listing.php', 'inc/entity-listing.php'] as $pf) {
    dateStripCheck("$pf no longer prints the loaded-of-total progress bar", strpos((string) file_get_contents($root . '/' . $pf), 'load-progress') === false);
}
dateStripCheck('city, venue and other entity lists print an ad card between rows (after the 4th and 9th), never after the last row', strpos((string) file_get_contents($root . '/inc/entity-listing.php'), "soAdSlot('list', 'rectangle')") !== false && strpos((string) file_get_contents($root . '/inc/entity-listing.php'), 'count($events) > $evi + 2') !== false);
dateStripCheck('the list ad has its own placement name, so it shares the banner unit and never prints empty', strpos((string) file_get_contents($root . '/inc/adsense.php'), "'side2', 'listing', 'list', 'content']") !== false);
if ($fail) { echo "$fail failure(s)\n"; exit(1); }
echo "ok: date strip\n";
