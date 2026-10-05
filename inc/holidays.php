<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Holiday pages for every city, United States and Canada: /<holiday>-in-<city>, for example /july-4th-events-in-austin-tx or
 * /christmas-shows-near-me-in-austin-tx, and the hub /holiday-events that links them.
 *
 * Each page lists the city's events that fall in the holiday's dates for the next time it comes round (the dates are worked out from the
 * rule below, so they stay right year after year, Easter and the moving weekend holidays included). Christmas is the exception: the
 * ticket API files holiday shows in its own "Holiday" category, so that page lists the city's events in that category.
 * A page with fewer than SO_HOLIDAY_MIN events is noindex and out of the sitemap, so an off-season holiday does not put thin pages in front of search engines.
 *
 * Rule keys: fixed (month, day, days before, days after), nth (month, weekday 1=Mon..7=Sun, which one, days before, days after),
 * last (month, weekday, days before, days after), easter (days before, days after), category (no dates).
 */
const SO_HOLIDAY_MIN = 3;
const SO_HOLIDAYS = [
    'christmas-shows-near-me'               => ['label' => 'Christmas Shows',                'countries' => ['US', 'CA'], 'rule' => ['category', 1884], 'name' => 'Christmas'],
    'new-years-eve-events'                  => ['label' => "New Year's Eve Events",          'countries' => ['US', 'CA'], 'rule' => ['fixed', 12, 31, 1, 1], 'name' => "New Year's Eve"],
    'valentines-day-events'                 => ['label' => "Valentine's Day Events",         'countries' => ['US', 'CA'], 'rule' => ['fixed', 2, 14, 1, 1], 'name' => "Valentine's Day"],
    'st-patricks-day-events'                => ['label' => "St. Patrick's Day Events",       'countries' => ['US', 'CA'], 'rule' => ['fixed', 3, 17, 2, 0], 'name' => "St. Patrick's Day"],
    'easter-weekend-events'                 => ['label' => 'Easter Weekend Events',          'countries' => ['US', 'CA'], 'rule' => ['easter', 2, 1], 'name' => 'Easter'],
    'mothers-day-weekend-events'            => ['label' => "Mother's Day Weekend Events",    'countries' => ['US', 'CA'], 'rule' => ['nth', 5, 7, 2, 2, 0], 'name' => "Mother's Day"],
    'memorial-day-weekend-events'           => ['label' => 'Memorial Day Weekend Events',    'countries' => ['US'],       'rule' => ['last', 5, 1, 3, 0], 'name' => 'Memorial Day'],
    'victoria-day-weekend-events'           => ['label' => 'Victoria Day Weekend Events',    'countries' => ['CA'],       'rule' => ['victoria', 3, 0], 'name' => 'Victoria Day'],
    'fathers-day-weekend-events'            => ['label' => "Father's Day Weekend Events",    'countries' => ['US', 'CA'], 'rule' => ['nth', 6, 7, 3, 2, 0], 'name' => "Father's Day"],
    'canada-day-events'                     => ['label' => 'Canada Day Events',              'countries' => ['CA'],       'rule' => ['fixed', 7, 1, 1, 1], 'name' => 'Canada Day'],
    'july-4th-events'                       => ['label' => 'July 4th Events',                'countries' => ['US'],       'rule' => ['fixed', 7, 4, 1, 1], 'name' => 'July 4th'],
    'labor-day-weekend-events'              => ['label' => 'Labor Day Weekend Events',       'countries' => ['US'],       'rule' => ['nth', 9, 1, 1, 3, 0], 'name' => 'Labor Day'],
    'labour-day-weekend-events'             => ['label' => 'Labour Day Weekend Events',      'countries' => ['CA'],       'rule' => ['nth', 9, 1, 1, 3, 0], 'name' => 'Labour Day'],
    'halloween-events'                      => ['label' => 'Halloween Events',               'countries' => ['US', 'CA'], 'rule' => ['fixed', 10, 31, 6, 0], 'name' => 'Halloween'],
    'thanksgiving-weekend-events'           => ['label' => 'Thanksgiving Weekend Events',    'countries' => ['US'],       'rule' => ['nth', 11, 4, 4, 1, 3], 'name' => 'Thanksgiving'],
    'canadian-thanksgiving-weekend-events'  => ['label' => 'Canadian Thanksgiving Weekend Events', 'countries' => ['CA'], 'rule' => ['nth', 10, 1, 2, 3, 0], 'name' => 'Canadian Thanksgiving'],
    'boxing-day-events'                     => ['label' => 'Boxing Day Events',              'countries' => ['CA'],       'rule' => ['fixed', 12, 26, 0, 1], 'name' => 'Boxing Day'],
];

/** The Date of Easter Sunday for a year (the Meeus/Jones/Butcher rule; the calendar extension is not always installed). */
function soEasterDate(int $year): DateTimeImmutable {
    $a = $year % 19; $b = intdiv($year, 100); $c = $year % 100; $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31); $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
}

/** The holiday's own day for one year, and the days the page covers before and after it: [DateTimeImmutable, before, after]; null for Christmas. */
function soHolidayDay(string $key, int $year): ?array {
    $rule = SO_HOLIDAYS[$key]['rule'] ?? null;
    if (!$rule || $rule[0] === 'category') return null;
    switch ($rule[0]) {
        case 'fixed':    return [new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $rule[1], $rule[2])), $rule[3], $rule[4]];
        case 'nth':      // the n-th weekday of the month
            $first = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $rule[1]));
            $offset = ($rule[2] - (int) $first->format('N') + 7) % 7;
            return [$first->modify('+' . ($offset + 7 * ($rule[3] - 1)) . ' days'), $rule[4], $rule[5]];
        case 'last':     // the last weekday of the month
            $end = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $rule[1])))->modify('last day of this month');
            return [$end->modify('-' . (((int) $end->format('N') - $rule[2] + 7) % 7) . ' days'), $rule[3], $rule[4]];
        case 'easter':   return [soEasterDate($year), $rule[1], $rule[2]];
        case 'victoria': // the Monday before May 25
            $d = new DateTimeImmutable(sprintf('%04d-05-24', $year));
            return [$d->modify('-' . (((int) $d->format('N') - 1 + 7) % 7) . ' days'), $rule[1], $rule[2]];
    }
    return null;
}

/** [from, to] for one year of a holiday, or null for the category-based Christmas page. */
function soHolidayDates(string $key, int $year): ?array {
    $d = soHolidayDay($key, $year);
    return $d ? [$d[0]->modify("-{$d[1]} days"), $d[0]->modify("+{$d[2]} days")] : null;
}

/**
 * The window of the next time a holiday comes round (today counts while it is on): ['from' => 'Y-m-d', 'to' => 'Y-m-d'], where
 * "from" is never earlier than today. null for Christmas, which is read by category.
 */
function soHolidayWindow(string $key, ?DateTimeImmutable $today = null): ?array {
    $today = $today ?: new DateTimeImmutable('today');
    $y = (int) $today->format('Y');
    foreach ([$y - 1, $y, $y + 1] as $year) {   // last year's too: New Year's Eve runs into January 1
        $w = soHolidayDates($key, $year);
        if ($w === null) return null;
        if ($w[1] >= $today) return ['from' => max($w[0], $today)->format('Y-m-d'), 'to' => $w[1]->format('Y-m-d'), 'start' => $w[0]->format('Y-m-d')];
    }
    return null;
}

/** "Thursday, Nov 26 to Sunday, Nov 29, 2026" (one date when the window is one day). */
function soHolidayWindowText(array $w): string {
    $a = strtotime($w['start'] ?? $w['from']); $b = strtotime($w['to']);
    if ($a === $b) return date('l, M j, Y', $a);
    return date('l, M j', $a) . ' to ' . date('l, M j, Y', $b);
}

/** Holiday keys whose pages list events of a country, soonest holiday first. */
function soHolidaysForCountry(string $country): array {
    $out = [];
    foreach (SO_HOLIDAYS as $k => $h) {
        if (!in_array($country, $h['countries'], true)) continue;
        $w = soHolidayWindow($k);
        $out[$k] = $w ? $w['start'] : '9999-12-31';   // Christmas sorts last
    }
    asort($out);
    return array_keys($out);
}

/** The holiday pages an event belongs to (for the sitemap crawl): its country must be one the holiday covers, its date inside the window. */
function soHolidayKindsForEvent(array $e): array {
    static $windows = null;
    if ($windows === null) { $windows = []; foreach (SO_HOLIDAYS as $k => $h) { $windows[$k] = soHolidayWindow($k); } }
    $country = (string) ($e['country']['alphaCode'] ?? '');
    $day = substr((string) ($e['date']['date'] ?? ''), 0, 10);
    $path = (string) ($e['defaultCategory']['path'] ?? '');
    $out = [];
    foreach (SO_HOLIDAYS as $k => $h) {
        if (!in_array($country, $h['countries'], true)) continue;
        if ($h['rule'][0] === 'category') { if (strpos($path, '.' . $h['rule'][1] . '.') !== false) $out[] = $k; continue; }
        $w = $windows[$k];
        if ($w && $day !== '' && $day >= $w['from'] && $day <= $w['to']) $out[] = $k;
    }
    return $out;
}

/** The filter fragment (without the city) for a holiday page. */
function soHolidayFilter(string $key): string {
    $h = SO_HOLIDAYS[$key];
    if ($h['rule'][0] === 'category') return "contains(defaultCategory/path, '." . (int) $h['rule'][1] . ".') and date/date ge " . date('Y-m-d') . " and _metadata/hasTickets eq true";
    $w = soHolidayWindow($key);
    return "date/date ge {$w['from']} and date/date le {$w['to']} and _metadata/hasTickets eq true";
}

function renderCityHolidayPage(string $key = ''): void {
    $key = $key !== '' ? $key : (string) ($_GET['holiday'] ?? '');
    $cfg = SO_HOLIDAYS[$key] ?? null;
    if (!$cfg) { renderNotFoundPage('Page'); }
    $slug = (string) ($_GET['slug'] ?? '');
    [$id] = soSlugResolve('city', $slug);
    if ($id === null) { renderNotFoundPage('City'); }
    $city = getTnCityById($id);
    if (tnEntityMissing($city)) { renderNotFoundPage('City', $city); }
    $country = (string) ($city['country']['alphaCode'] ?? 'US');
    if (!in_array($country, $cfg['countries'], true)) { renderNotFoundPage('Page'); }   // Canada Day for a US city, July 4th for a Canadian one
    $cityName  = (string) ($city['text']['name'] ?? '');
    $st        = (string) ($city['stateProvince']['text']['abbr'] ?? '');
    $label     = trim($cityName . ($st !== '' ? ', ' . $st : ''), ', ');
    $canonSlug = soSlug('city', $label, $id);
    $prefix    = $key . '-in';
    soRedirectToCanonicalSlug($prefix, $slug, $canonSlug);

    $when = isset($_GET['when']) && isset(LISTING_WHEN[$_GET['when']]) ? $_GET['when'] : '';
    $sort = isset($_GET['sort']) && isset(LISTING_SORT[$_GET['sort']]) ? $_GET['sort'] : 'popular';
    $isFiltered = ($when !== '' || $sort !== 'popular');
    $perPage = 20;
    $fragment = 'city/id eq ' . (int) $id . ' and ' . soHolidayFilter($key);
    $params = ['filter' => $fragment] + listingSortParams($sort) + ['perPage' => $perPage, 'page' => 1, 'includeTotalCount' => 'true'];
    $resp = tnRequest('/catalog/v2/events/', $params);
    $total = (int) ($resp['totalCount'] ?? 0);
    $events = $resp['results'] ?? [];
    if ($total === 0 && !$events && soApiDegraded()) { renderUnavailablePage($cfg['label'] . ' in ' . $label); }
    $path = '/' . $key . '-in-' . $canonSlug;
    $isZero = $total < SO_HOLIDAY_MIN;
    if ($isZero || $isFiltered) { $pageRobots = 'noindex, follow'; }
    if (!$isFiltered) { soZeroPageNote($path, $isZero); }

    $win = soHolidayWindow($key);
    $dates = $win ? soHolidayWindowText($win) : '';
    $what = $cfg['label'];
    $pageTitleMax = 70;
    $pageFocusKeyword = $key === 'christmas-shows-near-me' ? "Christmas Shows Near Me in $cityName" : "$what in $cityName";
    $pageMetaTitle = soTitleUpTo(70, "Buy Tickets to $what in $label", "Buy Tickets to $what in $cityName", "$what in $cityName");
    $pageMetaDescription = $total >= SO_HOLIDAY_MIN
        ? soSpecPick(155,
            "Buy tickets to $what in $label" . ($win ? ", " . date('M j', strtotime($win['start'])) . ' to ' . date('M j', strtotime($win['to'])) : '') . ". Find great seats and book your tickets online today at Seat Outlet before they sell out.",
            "Buy tickets to $what in $label. Book online at Seat Outlet before they sell out.",
            "Buy tickets to $what in $cityName at Seat Outlet.")
        : soMetaFit("Find $what in $label. Nothing is on sale for the dates yet: browse all events in $cityName on Seat Outlet.", 'Every order has a 100% buyer guarantee.', 'New listings are added every day.');
    $pageCanonicalUrl = HOME_URL . $path;

    $nm = $cfg['name'] . (stripos($what, 'shows') !== false ? ' shows' : ' events');   // "Christmas shows", "July 4th events"
    // Questions that can be answered from the page's own data.
    $faqs = [
        ['When is ' . $cfg['name'] . ' ' . ($win ? date('Y', strtotime($win['to'])) : date('Y')) . '?', $win ? $cfg['name'] . ' events on this page run ' . $dates . '.' : 'Christmas shows run through the holiday season, and the list shows every holiday show in ' . $label . ' that has tickets on sale.'],
        ['How many ' . $nm . ' are on sale in ' . $cityName . '?', $total > 0 ? 'Seat Outlet lists ' . number_format($total) . ' ' . ($total === 1 ? 'event' : 'events') . ' in ' . $label . ' for these dates right now. The list changes as sellers add tickets.' : 'Nothing is listed for these dates in ' . $label . ' right now. New listings are added every day, so check back or browse all events in ' . $cityName . '.'],
        ['How do I buy ' . $nm . ' tickets in ' . $cityName . '?', 'Pick an event above, choose how many tickets you need, compare sections and prices on the seat map and check out. Every order is covered by our 100% guarantee.'],
    ];
    $pageJsonLdNodes = array_values(array_filter([
        buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL . '/'], ['label' => $label, 'url' => HOME_URL . '/city/' . $canonSlug]], "$what in $label"),
        $events ? soEventItemList($events, $pageCanonicalUrl) : null,
        buildFaqPageSchema(array_map(fn($f) => ['question' => $f[0], 'answer' => $f[1]], $faqs)),
    ]));
    $soSnap = get_defined_vars();
    include 'header.php';
    extract($soSnap, EXTR_OVERWRITE);
    unset($soSnap);

    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    soRenderListingPage([
        'h1'         => "$what in $label",
        'crumbs'     => [['label' => 'Home', 'url' => '/'], ['label' => $label, 'url' => '/city/' . $canonSlug], ['label' => $what]],
        'eyebrow'    => $label,
        'eyebrowUrl' => '/city/' . $canonSlug,
        'lead_text'  => $win ? $dates : '',
        'total'      => $total,
        'basePath'   => $path,
        'when'       => $when,
        'sort'       => $sort,
        'defaultSort' => 'popular',
        'max'        => 0,
        'explore'    => [],
        'body'       => [
            'events'  => $events,
            'perPage' => $perPage,
            'params'  => $params,
            'empty'   => ['basePath' => $path, 'noun' => $nm, 'when' => $when, 'max' => 0, 'fragment' => 'city/id eq ' . (int) $id, 'kind' => 'city', 'id' => $id, 'name' => $label, 'alts' => []],
        ],
        'afterSection' => function () use ($h, $label, $cityName, $canonSlug, $key, $what, $country, $faqs) {
            echo '<div class="container py-4">' . soSpecPromoHtml("Promo codes for $what in $cityName", "$cityName $what") . '</div>';
            soMiniFaq("FAQs about $what in $label", $faqs);
            // Links: the city, the other holidays coming up there, then the city's category and discovery pages.
            $links = ['<a href="/city/' . $h($canonSlug) . '">All events in ' . $h($label) . '</a>'];
            foreach (array_slice(array_diff(soHolidaysForCountry($country), [$key]), 0, 6) as $k) {
                $links[] = '<a href="/' . $h($k) . '-in-' . $h($canonSlug) . '">' . $h(SO_HOLIDAYS[$k]['label']) . ' in ' . $h($cityName) . '</a>';
            }
            foreach (['concerts-city' => 'Concerts', 'sports-city' => 'Sports', 'theater-city' => 'Theater'] as $p => $l) { $links[] = '<a href="/' . $p . '/' . $h($canonSlug) . '">' . $l . ' in ' . $h($cityName) . '</a>'; }
            $links[] = '<a href="/holiday-events">All holiday events</a>';
            echo '<nav class="container pb-4" aria-label="More in ' . $h($cityName) . '"><p class="mb-0"><strong>More in ' . $h($cityName) . ':</strong> ' . implode(' &middot; ', $links) . '</p></nav>';
        },
    ]);
    include 'footer.php';
}

/** The names of the pages /holiday-events lists for a country: key => [label, dates text]. */
function soHolidayHubRows(string $country): array {
    $rows = [];
    foreach (soHolidaysForCountry($country) as $k) {
        $w = soHolidayWindow($k);
        $rows[$k] = [SO_HOLIDAYS[$k]['label'], $w ? soHolidayWindowText($w) : 'November and December'];
    }
    return $rows;
}
