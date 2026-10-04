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

/** The TAKE5 / TAKE10 block that every entity page carried. Unchanged copy, one place. */
function soRenderPromoBlock(): void {
    $pill = function ($pct, $code) { ?>
        <div class="col-md-6">
            <div class="offer-pill d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="offer-icon me-3 d-flex align-items-center justify-content-center">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12.5V5.8A1.8 1.8 0 0 1 4.8 4h6.7L21 13.5l-6.4 6.4L3 12.5Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"></path><circle cx="8.2" cy="8.2" r="1.1" fill="white"></circle></svg>
                    </div>
                    <div class="offer-text"><div class="offer-title"><?php echo (int) $pct; ?>% OFF</div><div class="offer-subtitle"><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></div></div>
                </div>
                <div class="offer-copy text-end">
                    <button type="button" class="btn btn-primary text-white offer-copy-btn btn-sm px-4 rounded-pill" data-code="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>">Copy</button>
                </div>
            </div>
        </div>
    <?php };
    ?>
    <div class="tab-section content-section-detail mb-0" id="promocode">
        <h2 class="so-heading fw-bold fs-4 mb-4 text-black">Ticket promo codes</h2>
        <p>Have a promo code? Enter it in the promo code field at checkout when one is offered. Codes apply only where the checkout accepts them, and savings vary by event.</p>
        <div class="row g-3 mt-2"><?php $pill(5, 'TAKE5'); $pill(10, 'TAKE10'); ?></div>
    </div>
    <?php
}

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
                <?php foreach ($nearby as $v) { ?><a class="so-linkchip" href="/venue/<?php echo $h(soEntitySlug($v['text']['name'] ?? '', $v['id'])); ?>"><?php echo $h($v['text']['name'] ?? ''); ?></a><?php } ?>
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
    $kindNoun = ['venue' => 'Venue', 'city' => 'City', 'state' => 'State', 'country' => 'Country'][$kind];

    // A feed that failed is not an empty page: 503 (the API counted as down), never a thin 200 that gets indexed or noindexed.
    if ($total === 0 && $events === [] && soApiDegraded()) { renderUnavailablePage($kindNoun); }

    $pageRobots = null;
    if ($isZero || !empty($c['isFiltered'])) { $pageRobots = 'noindex, follow'; }
    if (empty($c['isFiltered'])) { soZeroPageNote($c['path'], $isZero); }

    $top = soTopFromEvents($events, 6);
    $cheap = soCheapestEvent($events);
    $pricedDates = 0; foreach ($events as $ev) { if (eventFromPrice($ev) !== '') $pricedDates++; }
    $cheapestId = ($pricedDates >= 2 && $cheap) ? $cheap['eventId'] : 0;
    $next = $events[0] ?? null;

    // ---- head: titles follow "<place> <what> | <what you get>" and never repeat the word Tickets twice
    if ($kind === 'venue') {
        $pageMetaTitle = $c['cityLabel'] !== ''
            ? soTitle("$name Tickets\u{2014}{$c['cityLabel']} Events & Seats", "$name Tickets\u{2014}{$c['cityLabel']}", "$name Tickets")
            : soTitle("$name Tickets\u{2014}Events & Seating", "$name Tickets");
        $pageMetaDescription = $total > 0
            ? soMetaFit("Buy tickets to " . soCountWord($total, 'upcoming event') . " at $name" . ($c['cityLabel'] !== '' ? " in {$c['cityLabel']}" : '') . ($cheap ? ", from {$cheap['formatted']}" : '') . '. Pick seats on live seat maps and buy with our 100% buyer guarantee.', 'Prices from many sellers in one place.')
            : soMetaFit("See upcoming events at $name" . ($c['cityLabel'] !== '' ? " in {$c['cityLabel']}" : '') . '. Nothing is on sale right now: get a price alert or browse nearby venues on Seat Outlet.', 'Every order has a 100% buyer guarantee.');
        $h1 = "$name Tickets";
    } else {
        $pageMetaTitle = soTitle("$label Events & Concerts\u{2014}Tickets & Dates", "$label Events\u{2014}" . date('Y') . " Tickets", "$label Event Tickets", "$label Tickets");
        $pageMetaDescription = $total > 0
            ? soMetaFit("Find tickets to " . soCountWord($total, 'upcoming event') . " in $label" . ($cheap ? ", from {$cheap['formatted']}" : '') . ': concerts, sports and theater. Compare prices and buy with our 100% buyer guarantee.', 'Live seat maps and secure checkout.')
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
                        <p class="so-ent-intro">Seat Outlet lists <?php echo $h(soCountWord($total, 'upcoming event')); ?> at <?php echo $h($name); ?><?php if ($next) { ?>. The next is <a href="/event/<?php echo $h(soEntitySlug($next['text']['name'] ?? '', $next['id'] ?? 0)); ?>"><?php echo $h($next['text']['name'] ?? ''); ?></a> on <?php echo $h(date('F j, Y', strtotime((string) ($next['date']['date'] ?? 'now')))); ?><?php } ?>. Compare seats and prices for each date before you buy.</p>
                    <?php } ?>
                    <div class="mb-3 mb-md-4 mb-lg-4" id="eventsHead">
                        <div class="d-flex justify-content-between align-items-center results-header">
                            <div class="results-title">
                                <span class="active-indicator"></span>
                                <h2><?php echo $kind === 'venue' ? 'Upcoming events at ' : 'Upcoming events in '; ?><?php echo $h($name); ?> <span class="dot">·</span>
                                    <span class="count" id="results_count"><?php echo number_format($total); ?> <?php echo $total === 1 ? 'result' : 'results'; ?></span>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <?php renderListingFilters($c['path'], $c['when'], $c['sort'], $total, $c['defaultSort']); ?>
                    <div class="list-category-bg pb-3">
                        <?php if (!empty($events)) { ?>
                            <div id="eventsSection" class="section-artist-content event-row-all">
                                <?php foreach ($events as $event) { soRenderEventRow($event, ['cheapestId' => $cheapestId, 'showVenue' => true, 'showPlace' => true]); } ?>
                            </div>
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

                    <?php if ($top['performers']) { ?>
                        <div class="tab-section content-section-detail" id="top-performers">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black">Top performers <?php echo $kind === 'venue' ? 'at' : 'in'; ?> <?php echo $h($name); ?></h2>
                            <div class="so-linkchips">
                                <?php foreach ($top['performers'] as $p) { ?><a class="so-linkchip" href="/artist/<?php echo $h(soEntitySlug($p['name'], $p['id'])); ?>"><?php echo $h($p['name']); ?></a><?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($kind !== 'venue' && count($top['venues']) > 1) { ?>
                        <div class="tab-section content-section-detail" id="top-venues">
                            <h2 class="so-heading fw-bold fs-4 mb-3 text-black">Popular venues <?php echo $kind === 'city' ? 'in' : 'across'; ?> <?php echo $h($name); ?></h2>
                            <div class="so-linkchips">
                                <?php foreach ($top['venues'] as $v) { ?><a class="so-linkchip" href="/venue/<?php echo $h(soEntitySlug($v['name'], $v['id'])); ?>"><?php echo $h($v['name']); ?></a><?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php renderLocationCategoryLinks($kind, $c['id'], $label); ?>
                    <?php soBuyerGuaranteeSection(['events' => $events]); ?>
                    <?php
                    $soAddr = trim((string) ($c['entity']['address']['text']['address1'] ?? ''));
                    $soWhere = $kind === 'venue'
                        ? [ "Where is $name?", $name . ($soAddr !== '' ? " is at $soAddr" : '') . ($c['cityLabel'] !== '' ? ($soAddr !== '' ? ', ' : ' is in ') . $c['cityLabel'] : '') . '. Check the event page for the start time and any venue rules before you go.' ]
                        : [ "What events are on in $name?", $total > 0 ? 'There are ' . soCountWord($total, 'upcoming event') . " in $name on Seat Outlet right now, including concerts, sports and theater. Use the date and price filters above to narrow the list." : "Nothing is on sale in $name right now. New dates are added every day, so leave your email above and we will tell you when tickets go on sale." ];
                    soMiniFaq(($kind === 'venue' ? $name : $label) . ' tickets FAQ', [
                        $soWhere,
                        [ ($kind === 'venue' ? "How do I buy tickets for events at $name?" : "How do I buy event tickets in $name?"), 'Pick a date above, choose how many tickets you need, compare sections and prices on the seat map and check out securely. Tickets ship in time for at least one delivery attempt before the event.' ],
                        [ 'What happens if an event is canceled?', 'You get a full refund (delivery fees excluded). If the event is rescheduled, your tickets stay valid for the new date. Every order is covered by our 100% guarantee.' ],
                    ]);
                    ?>
                    <?php soRenderPromoBlock(); ?>
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
                                    <?php foreach ($sidebarNearby as $v) { ?><li><a href="/venue/<?php echo $h(soEntitySlug($v['text']['name'] ?? '', $v['id'])); ?>"><?php echo $h($v['text']['name'] ?? ''); ?></a></li><?php } ?>
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
