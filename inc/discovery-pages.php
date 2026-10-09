<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The city discovery pages from the owner's page sheet: last minute, weekend, cheap and best events in a city. Each is a real view of
 * the city's events (a date window or a sort order the listing already supports), with its own address, title, headings and promo block:
 *
 *   /last-minute-tickets/<city>   the next 7 days, soonest first
 *   /weekend-events/<city>        this weekend (Friday to Sunday)
 *   /cheap-tickets/<city>         lowest listed price first
 *   /best-events/<city>           best sellers right now
 *
 * A page with fewer than SO_DISCOVERY_MIN events is noindex and out of the sitemap. There is no VIP or floor page: the ticket API
 * does not say which listings are VIP or floor, and a page that cannot filter for what its title promises would mislead.
 */
const SO_DISCOVERY_MIN = 3;
const SO_DISCOVERY = [
    'last-minute-tickets' => ['when' => 'week',    'sort' => 'soonest', 'what' => 'Last Minute Tickets', 'lower' => 'last minute tickets', 'buy' => 'Buy last minute tickets in', 'blurb' => 'events in the next 7 days'],
    'weekend-events'      => ['when' => 'weekend', 'sort' => 'popular', 'what' => 'Weekend Events',      'lower' => 'weekend events',      'buy' => 'Buy tickets for weekend events in',      'blurb' => 'events this weekend'],
    'cheap-tickets'       => ['when' => '',        'sort' => 'price',   'what' => 'Cheap Tickets',       'lower' => 'cheap tickets',       'buy' => 'Buy cheap tickets in',       'blurb' => 'the lowest listed prices first'],
    'best-events'         => ['when' => '',        'sort' => 'popular', 'what' => 'Best Events',         'lower' => 'best events',         'buy' => 'Buy tickets to the best events in',         'blurb' => 'the best selling events right now'],
];

function renderCityDiscoveryPage(string $key): void {
    $cfg = SO_DISCOVERY[$key] ?? null;
    if (!$cfg) { renderNotFoundPage('Page'); }
    $slug = (string) ($_GET['slug'] ?? '');
    if ($key === 'best-events') { soRedirect301('/city/' . rawurlencode(trim($slug, '/'))); }   // the same events as the city page
    [$id] = soSlugResolve('city', $slug);
    if ($id === null) { renderNotFoundPage('City'); }
    $city = getTnCityById($id);
    if (tnEntityMissing($city)) { renderNotFoundPage('City', $city); }
    $cityName  = (string) ($city['text']['name'] ?? '');
    $st        = (string) ($city['stateProvince']['text']['abbr'] ?? '');
    $label     = trim($cityName . ($st !== '' ? ', ' . $st : ''), ', ');
    $canonSlug = soSlug('city', $label, $id);
    soRedirectToCanonicalSlug($key, $slug, $canonSlug);

    // The page's own window and order are its defaults; a visitor's own filter makes it a filtered view (noindex, canonical to the page).
    $when = isset($_GET['when']) && isset(LISTING_WHEN[$_GET['when']]) ? $_GET['when'] : $cfg['when'];
    $sort = isset($_GET['sort']) && isset(LISTING_SORT[$_GET['sort']]) ? $_GET['sort'] : $cfg['sort'];
    $isFiltered = ($when !== $cfg['when'] || $sort !== $cfg['sort']);
    $maxPrice = soListingMaxPrice();
    if ($maxPrice > 0) { $isFiltered = true; }
    $perPage = 20;
    $fragment = "city/id eq " . (int) $id;
    $resp = tnRequest('/catalog/v2/events/', locationListingParams($fragment, $perPage, 1, $when, $sort, $maxPrice));
    $total = (int) ($resp['totalCount'] ?? 0);
    $events = $resp['results'] ?? [];
    if ($total === 0 && !$events && soApiDegraded()) { soFeedDownGate($cfg['what'] . ' in ' . $label); }
    $path = '/' . $key . '/' . $canonSlug;
    $isZero = $total < SO_DISCOVERY_MIN;
    if ($isZero || $isFiltered) { $pageRobots = 'noindex, follow'; }
    if (!$isFiltered) { soZeroPageNote($path, $isZero); }

    // Spec wording: Buy in the title, the urgency line when events are on sale, "<what> in <City>, <ST>" for the heading.
    $what = $cfg['what']; $lower = $cfg['lower'];
    $pageTitleMax = 70;
    $titleLead = ['last-minute-tickets' => 'Buy Last Minute Tickets in', 'weekend-events' => 'Buy Tickets for Weekend Events in', 'cheap-tickets' => 'Buy Cheap Tickets in', 'best-events' => 'Buy Tickets to the Best Events in'][$key];
    $pageFocusKeyword = ['last-minute-tickets' => 'Last Minute Tickets in', 'weekend-events' => 'Weekend Events in', 'cheap-tickets' => 'Cheap Tickets in', 'best-events' => 'Best Events in'][$key] . ' ' . $cityName;
    $pageMetaTitle = soTitleUpTo(60, "$titleLead $label", "$titleLead $cityName", "$what in $cityName");
    $pageMetaDescription = $total >= SO_DISCOVERY_MIN
        ? soSpecPick(155,
            "{$cfg['buy']} $label: {$cfg['blurb']}. Find great seats and book your tickets online today at Seat Outlet before they sell out.",
            "{$cfg['buy']} $label. Book online at Seat Outlet before they sell out.",
            "{$cfg['buy']} $cityName at Seat Outlet.")
        : soMetaFit("Find $lower in $label. Nothing matches right now: browse all events in $cityName on Seat Outlet.", 'Every order has a 100% buyer guarantee.', 'New listings are added every day.');
    $pageCanonicalUrl = HOME_URL . $path;
    $pageJsonLdNodes = array_values(array_filter([
        buildBreadcrumbListSchema([['label' => 'Home', 'url' => HOME_URL . '/'], ['label' => $label, 'url' => HOME_URL . '/city/' . $canonSlug]], "$what in $label"),
        $events ? soEventItemList($events, $pageCanonicalUrl) : null,
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
        'total'      => $total,
        'basePath'   => $path,
        'when'       => $when,
        'sort'       => $sort,
        'defaultSort' => $cfg['sort'],
        'max'        => $maxPrice,
        'explore'    => [],
        'body'       => [
            'events'  => $events,
            'perPage' => $perPage,
            'params'  => locationListingParams($fragment, $perPage, 1, $when, $sort, $maxPrice),
            'empty'   => ['basePath' => $path, 'noun' => $lower, 'when' => $when, 'max' => $maxPrice, 'fragment' => $fragment, 'kind' => 'city', 'id' => $id, 'name' => $label, 'alts' => []],
        ],
        'afterSection' => function () use ($h, $label, $cityName, $canonSlug, $key, $lower, $what) {
            // Promo codes, then the links that tie the page to its city: the city page, each category in the city and the other views.
            echo '<div class="container py-4">' . soSpecPromoHtml('Promo codes for ' . $cityName . ' ' . $lower, $cityName . ' event') . '</div>';
            $links = ['<a href="/city/' . $h($canonSlug) . '">All events in ' . $h($label) . '</a>'];
            foreach (['concerts-city' => 'Concerts', 'sports-city' => 'Sports', 'theater-city' => 'Theater', 'festivals-city' => 'Festivals'] as $p => $l) {
                $links[] = '<a href="/' . $p . '/' . $h($canonSlug) . '">' . $l . ' in ' . $h($cityName) . '</a>';
            }
            foreach (SO_DISCOVERY as $k => $c) { if ($k !== $key && $k !== 'best-events') $links[] = '<a href="/' . $k . '/' . $h($canonSlug) . '">' . $h($c['what']) . ' in ' . $h($cityName) . '</a>'; }
            $links[] = '<a href="/city-events">Browse all cities</a>';
            echo '<nav class="container pb-4" aria-label="More in ' . $h($cityName) . '"><p class="mb-0"><strong>More in ' . $h($cityName) . ':</strong> ' . implode(' &middot; ', $links) . '</p></nav>';
        },
    ]);
    include 'footer.php';
}
