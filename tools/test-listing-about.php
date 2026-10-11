<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the listing About copy (county, city, theater-in-city): every sentence comes from the listed events, partial
 * results are scoped, and the copy is escaped. Run: php tools/test-listing-about.php
 */
require_once __DIR__ . '/../inc/listing-about.php';

$fails = 0;
function laCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$events = [
    ['venue' => ['text' => ['name' => 'Red Rocks']], 'city' => ['text' => ['name' => 'Morrison']], 'pricingInfo' => ['lowPrice' => ['value' => 49.5, 'text' => ['formatted' => '$49.50']]], 'date' => ['date' => '2026-12-20']],
    ['venue' => ['text' => ['name' => 'Ball Arena']], 'city' => ['text' => ['name' => 'Denver']], 'pricingInfo' => ['lowPrice' => ['value' => 35, 'text' => ['formatted' => '$35']]], 'date' => ['date' => '2026-12-05']],
    ['venue' => ['text' => ['name' => 'Red Rocks']], 'city' => ['text' => ['name' => 'Morrison']], 'pricingInfo' => [], 'date' => ['date' => '2026-12-12']],
];

// 1. Full list: venues, cities, price and dates come from the events
ob_start(); soListingAboutBlock($h, ['heading' => 'About Denver County, CO', 'intro' => 'Intro here.', 'events' => $events, 'total' => 3, 'cities' => true]); $full = ob_get_clean();
laCheck('heading is the place name', strpos($full, '<h2 class="h4">About Denver County, CO</h2>') !== false);
laCheck('lists the cities in the listed events', strpos($full, 'take place in Morrison and Denver.') !== false);
laCheck('lists the venues when all results are on the page', strpos($full, 'are at Red Rocks and Ball Arena.') !== false);
laCheck('gives the lowest listed price from the API text', strpos($full, 'start from $35, so compare seats') !== false);
laCheck('gives the earliest and latest listed dates', strpos($full, 'earliest event on this page is on December 5, 2026 and the latest is on December 20, 2026') !== false);
laCheck('keeps the caller intro', strpos($full, '<p>Intro here.</p>') !== false);
laCheck('does not repeat the event count (the FAQ owns it)', strpos($full, 'lists 3') === false && strpos($full, 'Seat Outlet lists') === false);

// 2. Partial results: 3 of 25 shown, so venue, city, price and date claims are scoped to the listed events
ob_start(); soListingAboutBlock($h, ['heading' => 'About Denver County, CO', 'intro' => '', 'events' => $events, 'total' => 25, 'cities' => true]); $partial = ob_get_clean();
laCheck('partial: venues are described as examples', strpos($partial, 'are at venues such as Red Rocks, Ball Arena.') !== false);
laCheck('partial: price is scoped to the listed events', strpos($partial, 'Tickets for the events listed here start from $35') !== false);
laCheck('partial: no unscoped "on this page" price claim', strpos($partial, 'Tickets for the events on this page') === false);
laCheck('partial: dates are scoped to the listed events', strpos($partial, 'Of the events listed here, the earliest is on December 5') !== false);

// 3. Many venues on a full page: count and first three names, not a full list
$many = [];
foreach (['A Hall', 'B Arena', 'C Center', 'D Theatre', 'E Park'] as $i => $v) { $many[] = ['venue' => ['text' => ['name' => $v]], 'date' => ['date' => '2026-12-0' . ($i + 1)]]; }
ob_start(); soListingAboutBlock($h, ['heading' => 'About X', 'intro' => '', 'events' => $many, 'total' => 5]); $manyOut = ob_get_clean();
laCheck('more than three venues: count and first three names', strpos($manyOut, 'are at 5 venues, including A Hall, B Arena, C Center.') !== false);

// 4. Cents and whole dollars: API text is kept; fallback keeps cents and drops a trailing .00
ob_start(); soListingAboutBlock($h, ['heading' => 'About X', 'intro' => '', 'events' => [['pricingInfo' => ['lowPrice' => ['value' => 49.5]]]], 'total' => 1]); $cents = ob_get_clean();
laCheck('fallback price keeps cents ($49.50, not $50)', strpos($cents, 'start from $49.50') !== false);
ob_start(); soListingAboutBlock($h, ['heading' => 'About X', 'intro' => '', 'events' => [['pricingInfo' => ['lowPrice' => ['value' => 35.0]]]], 'total' => 1]); $whole = ob_get_clean();
laCheck('fallback whole-dollar price has no cents ($35, not $35.00)', strpos($whole, 'start from $35,') !== false);

// 4b. Zero events but an intro: the intro is only shown by callers when events exist, so the helper itself prints nothing without facts
ob_start(); soListingAboutBlock($h, ['heading' => 'About Empty', 'intro' => '', 'events' => [], 'total' => 0]); $zeroIntro = ob_get_clean();
laCheck('zero events: nothing is printed when the caller passes no intro', trim($zeroIntro) === '');
$entityCall = file_get_contents(__DIR__ . '/../inc/entity-listing.php');
laCheck('entity listing drops the intro on a zero-result page', strpos($entityCall, "'intro'   => \$total === 0 ? ''") !== false);
$fnCall = file_get_contents(__DIR__ . '/../functions.php');
laCheck('category-location page drops the intro when no rows are shown', strpos($fnCall, "!empty(\$events) ? \"\$categoryLabel listings") !== false);

// 4c. County fixture: two towns with markup in the name, escaped output, and the "among other towns" qualifier past three towns
$towns = [
    ['venue' => ['text' => ['name' => 'Hall <i>One</i>']], 'city' => ['text' => ['name' => 'Town <b>A</b>']], 'date' => ['date' => '2026-12-01']],
    ['venue' => ['text' => ['name' => 'Hall Two']], 'city' => ['text' => ['name' => 'Town C']], 'date' => ['date' => '2026-12-02']],
];
ob_start(); soListingAboutBlock($h, ['heading' => 'About Test County', 'intro' => '', 'events' => $towns, 'total' => 2, 'cities' => true]); $townOut = ob_get_clean();
laCheck('county: town names are escaped', strpos($townOut, 'Town &lt;b&gt;A&lt;/b&gt;') !== false && strpos($townOut, '<b>A</b>') === false);
laCheck('county: two towns are named with no qualifier', strpos($townOut, 'take place in Town &lt;b&gt;A&lt;/b&gt; and Town C.') !== false);
$fourTowns = [];
foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $i => $t) { $fourTowns[] = ['city' => ['text' => ['name' => $t]], 'date' => ['date' => '2026-12-0' . ($i + 1)]]; }
ob_start(); soListingAboutBlock($h, ['heading' => 'About X', 'intro' => '', 'events' => $fourTowns, 'total' => 4, 'cities' => true]); $four = ob_get_clean();
laCheck('county: more than three towns names three and says there are others', strpos($four, 'take place in Alpha, Beta and Gamma, among other towns.') !== false);
ob_start(); soListingAboutBlock($h, ['heading' => 'About X', 'intro' => '', 'events' => $fourTowns, 'total' => 40, 'cities' => true]); $fourPartial = ob_get_clean();
laCheck('county: more than three towns on a partial page keeps the qualifier', strpos($fourPartial, 'take place in Alpha, Beta and Gamma, among other towns.') !== false);
laCheck('county: entity listing intro says the rows are shown above', strpos(file_get_contents(__DIR__ . '/../inc/entity-listing.php'), 'shown above.') !== false && strpos(file_get_contents(__DIR__ . '/../inc/entity-listing.php'), 'shown below.') === false);
laCheck('category page intro refers to the rows above and is gated on rows', strpos(file_get_contents(__DIR__ . '/../functions.php'), "!empty(\$events) ? \"\$categoryLabel listings in \$locationLabel are listed above") !== false);
laCheck('category page About block is limited to city pages', strpos(file_get_contents(__DIR__ . '/../functions.php'), "if (\$dimension === 'city') {   // city pages only") !== false);
$catSrc = file_get_contents(__DIR__ . '/../functions.php');
$cityAt = strpos($catSrc, "if (\$dimension === 'city') {   // city pages only");
$beforeCity = $cityAt === false ? '' : substr($catSrc, 0, $cityAt);
laCheck('zero-result category page prints no empty About wrapper (no <div> left open before the About call)', $cityAt !== false && strrpos($beforeCity, '<div') < strrpos($beforeCity, '</div>'));

$catFlagSrc = file_get_contents(__DIR__ . '/../functions.php');
$catAt = strpos($catFlagSrc, 'function renderCategoryLocationPage(');
laCheck('category-by-city page sets the banner-off flag for city pages only, before its list', $catAt !== false && strpos($catFlagSrc, "if (\$dimension === 'city') { \$GLOBALS['soNoBannerAd'] = true; }", $catAt) !== false && strpos($catFlagSrc, "if (\$dimension === 'city') { \$GLOBALS['soNoBannerAd'] = true; }", $catAt) < strpos($catFlagSrc, 'getCategoryEventsByLocation(', $catAt));

// 5. Nothing to say: no invented copy
ob_start(); soListingAboutBlock($h, ['heading' => 'About Empty', 'intro' => '', 'events' => [], 'total' => 0]); $none = ob_get_clean();
laCheck('no events and no intro writes nothing', trim($none) === '');

// 6. Escaping of venue and city names
ob_start(); soListingAboutBlock($h, ['heading' => 'About X', 'intro' => '', 'events' => [['venue' => ['text' => ['name' => '<b>X</b>']]]], 'total' => 1]); $esc = ob_get_clean();
laCheck('escapes venue names', strpos($esc, '&lt;b&gt;X&lt;/b&gt;') !== false && strpos($esc, '<b>X</b>') === false);

// 7. Call sites are wired: county and city pages in the entity renderer, and the category-location page
$entity = file_get_contents(__DIR__ . '/../inc/entity-listing.php');
laCheck('entity listing calls the About block for county and city pages', strpos($entity, "in_array(\$kind, ['county', 'city'], true)") !== false && strpos($entity, 'soListingAboutBlock($h') !== false);
$fn = file_get_contents(__DIR__ . '/../functions.php');
$cat = strpos($fn, 'function renderCategoryLocationPage(');
laCheck('category-location page (theater-city and others) calls the About block', $cat !== false && strpos($fn, 'soListingAboutBlock(fn(', $cat) !== false);
laCheck('helper file is loaded by functions.php', strpos($fn, "inc/listing-about.php") !== false);

echo $fails ? "\n$fails failed\n" : "\nlisting about: all passed\n";
exit($fails ? 1 : 0);
