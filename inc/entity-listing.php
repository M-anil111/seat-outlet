<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| One renderer for the plain venue, city, state and country pages
|--------------------------------------------------------------------------
| venue.php, city.php, state.php and country.php used to be four copies of the same 250-line template. They now parse
| and validate the URL, fetch the entity and its first page of events, and hand the result to soRenderEntityListing().
| Page head variables are locals here and reach header.php because it is included from inside this function (the same
| convention renderCategoryLocationPage() uses).
*/

/** Links out of an empty or sparse page: bigger places, nearby venues, popular cities. */
function soRenderEntityAlternatives(array $c, array $nearby): void {
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $links = [];
    if (!empty($c['parent'])) { $links[] = ['href' => $c['parent']['url'], 'text' => $c['parent']['text']]; }
    if (!empty($c['stateParent'])) { $links[] = ['href' => $c['stateParent']['url'], 'text' => $c['stateParent']['text']]; }
    $links[] = ['href' => '/buy-tickets-online', 'text' => 'Popular events'];
    $links[] = ['href' => '/city-events', 'text' => 'Browse by city'];
    ?>
    <div class="so-empty__alts">
        <p class="so-empty__lead">Try one of these instead:</p>
        <div class="so-linkchips">
            <?php foreach ($links as $l) { ?><a class="so-linkchip" href="<?php echo $h($l['href']); ?>"><?php echo $h($l['text']); ?></a><?php } ?>
        </div>
        <?php if ($nearby) { ?>
            <p class="so-empty__lead mt-3">Venues nearby with tickets:</p>
            <div class="so-linkchips">
                <?php foreach ($nearby as $v) { ?><a class="so-linkchip" href="/venue/<?php echo $h(soVenueSlug($v['text']['name'] ?? '', $v['id'], soPlaceLabel($v))); ?>"><?php echo $h($v['text']['name'] ?? ''); ?></a><?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php
}

/** Venues near a point that really have events, never the venue itself. */
function soNearbyVenuesWithEvents(array $venue, int $limit = 6): array {
    $geo = $venue['geoLocation'] ?? $venue['address']['geoLocation'] ?? [];
    $lat = (float) ($geo['latitude'] ?? 0); $lng = (float) ($geo['longitude'] ?? 0);
    if ($lat == 0.0 && $lng == 0.0) return [];
    $out = [];
    foreach (getNearbyVenues($lat, $lng, $limit * 3) as $v) {
        if ((int) ($v['id'] ?? 0) === (int) ($venue['id'] ?? -1)) continue;
        if (empty($v['_metadata']['hasEvents']) && (int) ($v['_metadata']['eventCount'] ?? 0) <= 0) continue;
        if (trim((string) ($v['text']['name'] ?? '')) === '') continue;
        $out[] = $v;
        if (count($out) >= $limit) break;
    }
    return $out;
}

/**
 * @param array $c kind (venue|city|state|country), id, name, label, path ('/city/slug'), slug, prefix ('city'),
 *                 events, total, count, perPage, params, when, sort, defaultSort, isFiltered,
 *                 trail [['label','url']...] (between Home and this page), entity (raw API record, venue/city),
 *                 image (getEntityImage result or null), parent/stateParent ['url','text'], cityId, cityLabel
 */
function soRenderEntityListing(array $c): void {
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $kind = $c['kind']; $label = (string) $c['label']; $name = (string) $c['name'];
    $soEntityFacts = $kind === 'venue' ? soEntityFacts($name, 'venue', (string) ($c['entity']['city']['text']['name'] ?? '')) : [];   // Wikidata / Wikipedia / official site, [] until looked up
    $events = $c['events']; $total = (int) $c['total']; $count = (int) $c['count']; $perPage = (int) $c['perPage'];
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
    $percent = $total > 0 ? ($perPage / $total) * 100 : 0;
    $isZero = $total === 0 && empty($c['isFiltered']) && ($c['when'] ?? '') === '';
    $kindNoun = ['venue' => 'Venue', 'city' => 'City', 'county' => 'County', 'state' => 'State', 'country' => 'Country'][$kind];

    // A feed that failed is not an empty page: 503 (the API counted as down), never a thin 200 that gets indexed or noindexed.
    if ($total === 0 && $events === [] && soApiDegraded()) { renderUnavailablePage($kindNoun); }

    $pageRobots = null;
    if ($isZero || !empty($c['isFiltered'])) { $pageRobots = 'noindex, follow'; }
    if ($kind === 'county' && $total < SO_SITEMAP_CITYPAGE_MIN) { $pageRobots = 'noindex, follow'; }   // thin county pages stay out of search
    if (empty($c['isFiltered'])) { soZeroPageNote($c['path'], $isZero); }

    $top = soTopFromEvents($events, 6);
    $cheap = soCheapestEvent($events);
    $pricedDates = 0; foreach ($events as $ev) { if (eventFromPrice($ev) !== '') $pricedDates++; }
    $cheapestId = ($pricedDates >= 2 && $cheap) ? $cheap['eventId'] : 0;
    $next = $events[0] ?? null;

    // ---- head: titles follow "<place> <what> | <what you get>" and never repeat the word Tickets twice
    if ($kind === 'venue') {
        // Spec wording: "Buy <venue> Tickets in <city>", and the urgency line when events are on sale (inc/page-spec.php).
        $pageTitleMax = 70;
        $pageMetaTitle = soTitleUpTo(70, $c['cityLabel'] !== '' ? "Buy $name Tickets in {$c['cityLabel']}" : '', "Buy $name Tickets", "$name Tickets");
        $pageMetaDescription = $total > 0
            ? soSpecPick(155,
                "Buy tickets to " . soCountWord($total, 'upcoming event') . " at $name" . ($c['cityLabel'] !== '' ? " in {$c['cityLabel']}" : '') . ". Find great seats and book your tickets online today at Seat Outlet before they sell out.",
                "Buy tickets to events at $name. Find great seats and book online at Seat Outlet before they sell out.",
                "Buy $name tickets at Seat Outlet before they sell out.")
            : soMetaFit("See upcoming events at $name" . ($c['cityLabel'] !== '' ? " in {$c['cityLabel']}" : '') . '. Nothing is on sale right now: get a price alert or browse nearby venues on Seat Outlet.', 'Every order has a 100% buyer guarantee.');
        $pageFocusKeyword = "$name Tickets";
        $h1 = $c['cityLabel'] !== '' && stripos($name, (string) preg_replace('/,\s*[A-Z]{2}$/', '', $c['cityLabel'])) === false ? "$name Tickets in {$c['cityLabel']}" : "$name Tickets";
    } else {
        // "events in dallas" (9.9k/month) is the search; the phrase leads, the state suffix ("Dallas, TX") is dropped.
        $soCityShort = $kind === 'county' ? $label : preg_replace('/,\s*[A-Z]{2}$/', '', $label);   // a county keeps its state: "Washington County" exists in 30 states
        $pageFocusKeyword = "Events in $soCityShort";
        // Spec wording: "Buy Tickets for Events in <place>" keeps the search phrase whole, and the urgency line is added when events are on sale.
        $pageTitleMax = 70;
        $pageMetaTitle = soTitleUpTo(70, "Buy Tickets for Events in $label", "Buy Tickets for Events in $soCityShort", "Events in $soCityShort Concerts and Sports", "Events in $soCityShort", "$soCityShort Tickets");
        $pageMetaDescription = $total > 0
            ? soSpecPick(155,
                "Buy tickets to " . soCountWord($total, 'upcoming event') . " in $label" . ($cheap ? ", from {$cheap['formatted']}" : '') . ". Find great seats and book your tickets online today at Seat Outlet before they sell out.",
                "Buy tickets to " . soCountWord($total, 'upcoming event') . " in $label. Book online at Seat Outlet before they sell out.",
                "Buy tickets for events in $label at Seat Outlet before they sell out.")
            : soMetaFit("Find concert, sports and theater tickets in $label. Nothing is on sale right now: browse nearby places on Seat Outlet.", 'Every order has a 100% buyer guarantee.', 'New listings are added every day.');
        $h1 = "$label Event Tickets";
    }
    $pageCanonicalUrl = HOME_URL . $c['path'];
    $trailFull = array_merge([['label' => 'Home', 'url' => HOME_URL]], $c['trail']);
    $nodes = [soBreadcrumbNodes($trailFull, $name)];
    if ($kind === 'venue' && !empty($c['entity'])) {
        $soPlace = soBuildVenuePlaceSchema($c['entity'], $pageCanonicalUrl);
        if (!empty($soEntityFacts['sameAs'])) $soPlace['sameAs'] = array_values($soEntityFacts['sameAs']);
        $nodes[] = $soPlace;
        $pageMainEntity = $soPlace['@id'];
    }
    if ($kind === 'city') { $nodes[] = ['@type' => 'City', '@id' => $pageCanonicalUrl . '#city', 'name' => $name, 'url' => $pageCanonicalUrl, 'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => (string) ($c['entity']['stateProvince']['text']['name'] ?? '')]]; $pageMainEntity = $pageCanonicalUrl . '#city'; }
    $nodes[] = soBuildEventItemListSchema($events, "Upcoming events: $label");
    $pageJsonLdNodes = array_values(array_filter($nodes));
    $img = $c['image'] ?? null;
    if ($img && soImageIsReal($img)) { $pageOgImage = $img['url']; }

    $nearby = [];
    if ($kind === 'venue' && ($isZero || $total < 6)) { $nearby = soNearbyVenuesWithEvents($c['entity'] ?? [], 6); }
    $sidebarNearby = ($kind === 'venue') ? ($nearby ?: soNearbyVenuesWithEvents($c['entity'] ?? [], 6)) : [];

    // header.php is included from inside this function, so its own loop variables ($label, $name, $item...) would overwrite
    // ours. Snapshot every local first and restore after the include.
    $soSnap = get_defined_vars();
    include 'header.php';
    extract($soSnap, EXTR_OVERWRITE);
    unset($soSnap);
    ?>
<?php
    $soAddr = trim((string) ($c['entity']['address']['text']['address1'] ?? ''));
    $soMeta = '';
    if ($kind === 'venue' && ($soAddr !== '' || $c['cityLabel'] !== '')) {
        $soCityUrl = $c['cityUrl'] ?? '';
        $soMeta = ($soAddr !== '' ? $h($soAddr) . ($c['cityLabel'] !== '' ? ', ' : '') : '')
            . ($c['cityLabel'] !== '' ? ($soCityUrl !== '' ? '<a href="' . $h($soCityUrl) . '">' . $h($c['cityLabel']) . '</a>' : $h($c['cityLabel'])) : '')
            . ' <span aria-hidden="true">&middot;</span> <a href="https://www.google.com/maps/search/?api=1&amp;query=' . $h(rawurlencode(trim($name . ' ' . $soAddr . ' ' . $c['cityLabel']))) . '" target="_blank" rel="noopener">Map<span class="visually-hidden"> (opens in a new tab)</span></a>';
    }
    $soReal = $img && soImageIsReal($img);
    soPageHero([
        'crumbs'  => array_merge(array_map(fn($t) => ['label' => (string) $t['label'], 'url' => (string) $t['url']], $trailFull), [['label' => $name]]),
        'image'   => $soReal ? ['url' => $img['url'], 'alt' => $kind === 'venue' ? "$name, event venue" . ($c['cityLabel'] !== '' ? " in {$c['cityLabel']}" : '') : "Photo of $name"] : null,
        'credit'  => $soReal ? $img : null,
        'name'    => $name,
        'eyebrow' => $kindNoun,
        'title'   => $h1,
        'meta'    => $soMeta,
        'stats'   => $total > 0 ? [
            $h(soCountWord($total, 'upcoming event')),
            $cheap ? 'Tickets from <strong>' . $h($cheap['formatted']) . '</strong>' : '',
            $next ? 'Next: ' . $h(date('M j', strtotime((string) ($next['date']['date'] ?? 'now')))) : '',
        ] : ['No upcoming events listed right now'],
        'cta'     => $total > 0 ? ['See dates', '#eventsHead'] : null,
    ]);
    ?>

<section>
    <div class="container">
        <div class="tab-section section-performer-content" id="default">
            <div class="row mt-3 gap-5 gap-md-2 gap-lg-4 gap-xl-5 gap-xxl-5">
                <div class="col-sm-12 col-md-8 left-bar">
                    <?php if ($total > 0 && $kind !== 'venue') {
                        $vn = array_column($top['venues'], 'name');
                        ?>
                        <p class="so-ent-intro">Seat Outlet lists <?php echo $h(soCountWord($total, 'upcoming event')); ?> in <?php echo $h($label); ?><?php if ($vn) { ?>, including dates at <?php echo $h(implode(', ', array_slice($vn, 0, 3))); ?><?php } ?>. Choose a date to compare seats and prices, then check out securely with our 100% guarantee.</p>
                    <?php } elseif ($total > 0) { ?>
                        <p class="so-ent-intro">Seat Outlet lists <?php echo $h(soCountWord($total, 'upcoming event')); ?> at <?php echo $h($name); ?><?php if ($next) { ?>. The next is <a href="/event/<?php echo $h(soEventSlug($next)); ?>"><?php echo $h($next['text']['name'] ?? ''); ?></a> on <?php echo $h(date('F j, Y', strtotime((string) ($next['date']['date'] ?? 'now')))); ?><?php } ?>. Compare seats and prices for each date before you buy.</p>
                    <?php } ?>
                    <div class="mb-3 mb-md-4 mb-lg-4" id="eventsHead">
                        <div class="d-flex justify-content-between align-items-center results-header">
                            <div class="results-title">
                                <span class="active-indicator"></span>
                                <h2><?php echo $kind === 'venue' ? 'Buy tickets for upcoming events at ' : 'Buy tickets for upcoming events in '; ?><?php echo $h($name); ?> <span class="dot">·</span>
                                    <span class="count" id="results_count"><?php echo number_format($total); ?> <?php echo $total === 1 ? 'result' : 'results'; ?></span>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <?php renderListingFilters($c['path'], $c['when'], $c['sort'], $total, $c['defaultSort']); ?>
                    <div class="list-category-bg pb-3">
                        <?php if (!empty($events)) { ?>
                            <?php $soCap = 10; $soCapped = count($events) > $soCap + 2; ?>
                            <div id="eventsSection" class="section-artist-content event-row-all<?php echo $soCapped ? ' so-rows--capped' : ''; ?>"<?php echo $soCapped ? ' data-so-cap="' . $soCap . '"' : ''; ?>>
                                <?php foreach ($events as $event) { soRenderEventRow($event, ['cheapestId' => $cheapestId, 'showVenue' => true, 'showPlace' => true]); } ?>
                            </div>
                            <?php if ($soCapped) { ?>
                            <div class="text-center mt-3 so-rows__more" data-so-rows-more hidden>
                                <button type="button" class="btn more-events-btn d-inline-flex align-items-center gap-2" data-so-rows-toggle aria-controls="eventsSection" aria-expanded="false"><span class="btn-text">Show all <?php echo (int) $total; ?> events</span><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
                            </div>
                            <script>(function(){var s=document.getElementById("eventsSection");if(!s)return;var w=document.querySelector("[data-so-rows-more]");if(!w)return;s.classList.add("so-rows--js");w.hidden=false;w.querySelector("[data-so-rows-toggle]").addEventListener("click",function(){s.classList.remove("so-rows--capped","so-rows--js");w.remove();var lm=document.querySelector(".load-more-wrapper");if(lm)lm.classList.remove("so-rows__lm-hold");});var lm=document.querySelector(".load-more-wrapper");if(lm)lm.classList.add("so-rows__lm-hold");})();</script>
                            <?php } ?>
                            <?php if ($totalPages > 1) { ?>
                                <div class="load-more-wrapper text-center mt-5">
                                    <div class="load-progress mx-auto mb-3">
                                        <div class="small mb-2">Loaded <strong id="loadedCount"><?php echo $count; ?></strong> out of <strong id="totalCount"><?php echo $total; ?></strong> events</div>
                                        <div class="progress progress-thin"><div class="progress-bar" id="progressBar" style="width: <?php echo $percent; ?>%;"></div></div>
                                    </div>
                                    <button class="btn more-events-btn d-inline-flex align-items-center gap-2" id="loadMoreBtn" data-total="<?php echo $total; ?>" data-page="2" data-params="<?php echo $h(json_encode($c['params'])); ?>" data-perpage="<?php echo $perPage; ?>">
                                        <span class="btn-text">More Events</span>
                                        <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </button>
                                    <button class="btn more-events-btn d-inline-flex align-items-center gap-2 d-none" id="backToTopJs">
                                        <span class="btn-text">Back to Top</span>
                                        <i class="bi bi-chevron-up" aria-hidden="true"></i>
                                    </button>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="so-empty" role="status">
                                <?php if (($c['when'] ?? '') !== '' || !empty($c['isFiltered'])) { ?>
                                    <h3 class="so-empty__title">No dates match those filters</h3>
                                    <p>Clear a filter above to see everything on sale at <?php echo $h($name); ?>.</p>
                                <?php } else { ?>
                                    <h3 class="so-empty__title">No upcoming events at <?php echo $h($name); ?> right now</h3>
                                    <p>New dates are added all the time. <?php echo ($kind === 'venue' || $kind === 'city') ? 'Leave your email and we will tell you when tickets go on sale.' : 'Try a nearby place or a category below.'; ?></p>
                                    <?php
                                    if (($kind === 'venue' || $kind === 'city') && !empty($c['cityId'])) {
                                        echo soLeadForm(['source' => 'city-empty', 'class' => 'so-nl--compact', 'title' => 'Get an alert for new events in ' . ($kind === 'venue' ? $c['cityLabel'] : $label), 'text' => 'One email when tickets go on sale. No spam.', 'button' => 'Alert me', 'interest_type' => 'city', 'interest_id' => (int) $c['cityId'], 'interest_name' => $kind === 'venue' ? $c['cityLabel'] : $label, 'names' => false]);
                                    }
                                    soRenderEntityAlternatives($c, $nearby);
                                    ?>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                    <?php $soAbout = soPlaceAboutHtml($kind, $kind === 'venue' ? $name : $label, $events, $total, $top, $cheap); if ($soAbout !== '') { ?>
                        <div class="tab-section content-section-detail" id="about-place">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black">About events <?php echo $kind === 'venue' ? 'at' : 'in'; ?> <?php echo $h($kind === 'venue' ? $name : $label); ?></h2>
                            <?php echo soReadMoreBlock($soAbout, 'about-place-text', 4); ?>
                        </div>
                    <?php } ?>
                    <?php if ($top['performers']) { ?>
                        <div class="tab-section content-section-detail" id="top-performers">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black"><?php echo $kind === 'venue' ? 'Top Upcoming Performances' : 'Top performers'; ?> <?php echo $kind === 'venue' ? 'at' : 'in'; ?> <?php echo $h($name); ?></h2>
                            <div class="so-linkchips">
                                <?php foreach ($top['performers'] as $p) { ?><a class="so-linkchip" href="/artist/<?php echo $h(soSlug('performer', $p['name'], $p['id'])); ?>"><?php echo $h($p['name']); ?></a><?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($kind !== 'venue' && count($top['venues']) > 1) { ?>
                        <div class="tab-section content-section-detail" id="top-venues">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black">Popular venues <?php echo $kind === 'city' ? 'in' : 'across'; ?> <?php echo $h($name); ?></h2>
                            <div class="so-linkchips">
                                <?php foreach ($top['venues'] as $v) { ?><a class="so-linkchip" href="/venue/<?php echo $h(soSlug('venue', $v['name'], $v['id'])); ?>"><?php echo $h($v['name']); ?></a><?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($kind === 'city' && !empty($c['parent'])) { ?>
                        <div class="tab-section content-section-detail" id="county-link">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black">More events nearby</h2>
                            <div class="so-linkchips"><a class="so-linkchip" href="<?php echo $h($c['parent']['url']); ?>"><?php echo $h($c['parent']['text']); ?></a></div>
                        </div>
                    <?php } ?>
                    <?php
                    if ($kind === 'state') {
                        $soAbbr = (string) ($events[0]['stateProvince']['text']['abbr'] ?? '');
                        $soCounties = $soAbbr !== '' ? soCountiesInState($soAbbr) : [];
                        if ($soCounties) { ?>
                        <div class="tab-section content-section-detail" id="counties">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black">Counties in <?php echo $h($label); ?></h2>
                            <div class="so-linkchips">
                                <?php foreach ($soCounties as [$cf, $cl]) { ?><a class="so-linkchip" href="/county/<?php echo $h(soSlug('county', $cl, $cf)); ?>"><?php echo $h($cl); ?></a><?php } ?>
                            </div>
                        </div>
                    <?php } } ?>
                    <?php if ($kind !== 'county') { renderLocationCategoryLinks($kind, $c['id'], $label); } ?>
                    <?php soBuyerGuaranteeSection(['events' => $events]); ?>
                    <?php
                    $soAddr = trim((string) ($c['entity']['address']['text']['address1'] ?? ''));
                    $soWhere = $kind === 'venue'
                        ? [ "Where is $name?", $name . ($soAddr !== '' ? " is at $soAddr" : '') . ($c['cityLabel'] !== '' ? ($soAddr !== '' ? ', ' : ' is in ') . $c['cityLabel'] : '') . '. Check the event page for the start time and any venue rules before you go.' ]
                        : [ "What events are on in $name?", $total > 0 ? 'There are ' . soCountWord($total, 'upcoming event') . " in $name on Seat Outlet right now, including concerts, sports and theater. Use the date and price filters above to narrow the list." : "Nothing is on sale in $name right now. New dates are added every day, so leave your email above and we will tell you when tickets go on sale." ];
                    if ($kind === 'venue') {
                        // Spec: "About <Venue> in <City>, <ST>": address, directions, transit, official site and weather, no invented policies.
                        echo '<div class="tab-section content-section-detail">' . soSpecVenueInfoHtml('About ' . $name . ($c['cityLabel'] !== '' ? ' in ' . $c['cityLabel'] : ''), $name,
                            (string) ($c['entity']['city']['text']['name'] ?? ''), (string) ($c['entity']['stateProvince']['text']['abbr'] ?? ''), '', is_array($c['entity'] ?? null) ? $c['entity'] : [], $soEntityFacts, $total) . '</div>';
                    }
                    soMiniFaq('FAQs about ' . ($kind === 'venue' ? $name : $label) . ' Tickets', array_values(array_filter([
                        $soWhere,
                        [ ($kind === 'venue' ? "How do I buy tickets for events at $name?" : "How do I buy event tickets in $name?"), 'Pick a date above, choose how many tickets you need, compare sections and prices on the seat map and check out securely. Tickets ship in time for at least one delivery attempt before the event.' ],
                        ($kind === 'venue' ? [ "Where can I see the $name seating chart?", "Open any upcoming event at $name above. Its seat map shows every section with the tickets listed for sale and their prices, so you can compare views before you buy." ] : null),
                        [ 'What happens if an event is canceled?', 'You get a full refund (delivery fees excluded). If the event is rescheduled, your tickets stay valid for the new date. Every order is covered by our 100% guarantee.' ],
                    ])));
                    ?>
                    <?php echo soSpecPromoHtml(($kind === 'venue' ? 'Promo codes for ' . $name . ' tickets' : 'Latest promo codes for ' . $label . ' event tickets'), ($kind === 'venue' ? $name : $label . ' event')); ?>
                    <?php if ($kind === 'city') {
                        // Links that tie the city to its category and discovery pages (inc/discovery-pages.php).
                        $soCs = basename((string) $c['path']);   // the city's own slug, from /city/<slug>
                        $soCl = [];
                        foreach (['concerts-city' => 'Concerts', 'sports-city' => 'Sports', 'theater-city' => 'Theater', 'festivals-city' => 'Festivals'] as $pp => $ll) { $soCl[] = '<a href="/' . $pp . '/' . $h($soCs) . '">' . $ll . ' in ' . $h($name) . '</a>'; }
                        foreach (SO_DISCOVERY as $kk => $cc) { $soCl[] = '<a href="/' . $kk . '/' . $h($soCs) . '">' . $h($cc['what']) . ' in ' . $h($name) . '</a>'; }
                        // The next two holidays that apply to the city's country (inc/holidays.php).
                        foreach (array_slice(soHolidaysForCountry((string) ($c['entity']['country']['alphaCode'] ?? 'US')), 0, 2) as $hk) { $soCl[] = '<a href="/' . $hk . '-in-' . $h($soCs) . '">' . $h(SO_HOLIDAYS[$hk]['label']) . ' in ' . $h($name) . '</a>'; }
                        echo '<nav class="pb-3" aria-label="More in ' . $h($name) . '"><p class="mb-0"><strong>More in ' . $h($name) . ':</strong> ' . implode(' &middot; ', $soCl) . '</p></nav>';
                    } ?>
                </div>
                <div id="secondary" class="sidebar col-sm-12 col-md-4">
                    <div class="sticky-top sidebar-inner">
                        <div class="guarantee-card d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#staticBackdrop" role="button" tabindex="0">
                            <div class="guarantee"><strong>Shop Tickets Worry Free</strong><br><span>With Our 100% Guarantee</span></div>
                            <div class="guarantee-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></div>
                        </div>
                        <?php if ($total > 0 && ($kind === 'venue' || $kind === 'city') && !empty($c['cityId'])) {
                            echo soLeadForm(['source' => 'city-follow', 'class' => 'so-nl--compact mt-3', 'title' => 'New events in ' . ($kind === 'venue' ? $c['cityLabel'] : $label), 'text' => 'Get an email when new dates go on sale.', 'button' => 'Alert me', 'interest_type' => 'city', 'interest_id' => (int) $c['cityId'], 'interest_name' => $kind === 'venue' ? $c['cityLabel'] : $label, 'names' => false]);
                        } ?>
                        <?php if ($sidebarNearby && !$isZero) { ?>
                            <div class="so-side-card mt-3">
                                <h2 class="so-side-card__title">Nearby venues</h2>
                                <ul class="so-side-card__list">
                                    <?php foreach ($sidebarNearby as $v) { ?><li><a href="/venue/<?php echo $h(soVenueSlug($v['text']['name'] ?? '', $v['id'], soPlaceLabel($v))); ?>"><?php echo $h($v['text']['name'] ?? ''); ?></a></li><?php } ?>
                                </ul>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
    <?php
    include 'footer.php';
}
