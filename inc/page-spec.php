<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The page spec for event pages and performer-in-location pages (artist-city and its state, country and venue twins): one place for
 * the wording of titles, descriptions, headings, image alt text and schema type per kind of event (concert, sports, theater, festival),
 * and for the sections the spec asks for (promo codes, city info, tour dates, guide, similar events).
 *
 * Every sentence is built from data the page already holds. Nothing here states a fact the ticket API did not give: a section with no
 * data behind it returns '' and the page leaves it out.
 *
 * Which page owns which keyword (one keyword, one page):
 *   performer in a city (/artist-city/...)  "<Performer> Concert Tickets in <City>"           (concerts; "Tickets in" for the rest)
 *   one event (/event/...)                  "<Event> Tickets at <Venue> on <Mon D>"           (venue and day make it its own)
 *   performer (/artist/...)                 "<Performer> Tickets"
 */

/** The two site-wide promo codes and their terms (docs/launch-checklist.md: TicketNetwork confirms them). */
const SO_PROMO_CODES = [
    ['code' => 'TAKE5',  'pct' => 5,  'min' => 199],
    ['code' => 'TAKE10', 'pct' => 10, 'min' => 349],
];

/** concert, sports, theater (comedy is listed with the shows) or festival, from the event's or performer's category path. */
function soSpecKind(string $path): string {
    if (strpos($path, TN_CATEGORY_PATH_SPORTS) === 0) return 'sports';
    if (strpos($path, '.1872.') !== false || strpos($path, TN_CATEGORY_PATH_THEATER) === 0) return 'theater';
    if (strpos($path, TN_CATEGORY_PATH_FESTIVAL) === 0) return 'festival';
    return 'concert';
}

/** The key of LOCATION_CATEGORY_PATHS for a kind (the city pages of that kind). */
function soSpecCategoryKey(string $kind): string {
    return ['concert' => 'concerts', 'sports' => 'sports', 'theater' => 'theater', 'festival' => 'festivals'][$kind] ?? 'events';
}

/** The name as the spec writes it: a game "A at B" or "A vs. B" reads "A vs B". */
function soSpecLabel(string $name, string $kind): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($kind === 'sports' && preg_match('/^(.+?)\s+(?:at|vs\.?|v\.?)\s+(.+)$/i', $name, $m)) return $m[1] . ' vs ' . $m[2];
    return $name;
}

/** "Metallica Concert Tickets" for a concert, "Hamilton Tickets" for the rest. */
function soSpecTickets(string $label, string $kind): string {
    return $label . ($kind === 'concert' ? ' Concert' : '') . ' Tickets';
}

/** Image alt text: what the picture is for, with the place. */
function soSpecAlt(string $label, string $kind, string $city): string {
    $what = ['concert' => 'live concert', 'sports' => 'game', 'theater' => 'live show', 'festival' => 'festival'][$kind] ?? 'event';
    return trim($label . ' ' . $what . ($city !== '' ? ' in ' . $city : ''));
}

/** The first candidate that fits $max characters, else the last one cut at a word. */
function soSpecPick(int $max, string ...$candidates): string {
    $candidates = array_values(array_filter($candidates, 'strlen'));
    foreach ($candidates as $c) { if (mb_strlen($c) <= $max) return $c; }
    return seoClampDescription((string) end($candidates), $max);
}

/** schema.org type for the kind (most specific one Google accepts as an Event). */
function soSpecSchemaType(string $kind, string $path): string {
    if (strpos($path, '.1872.') !== false) return 'ComedyEvent';
    return ['concert' => 'MusicEvent', 'sports' => 'SportsEvent', 'theater' => 'TheaterEvent', 'festival' => 'Festival'][$kind] ?? 'Event';
}

/** The H2 above the promo codes. */
function soSpecPromoHeading(string $label, string $kind, string $city, string $state): string {
    $place = $city . ($state !== '' ? ', ' . $state : '');
    if ($kind === 'sports') return $label . ' Promo Codes for ' . $city . ' Game';
    if ($kind === 'theater') return $label . ' Promo Codes for ' . $city . ' Show';
    if ($kind === 'festival') return $label . ' Promo Codes for ' . $city . ' Festival';
    return 'Latest ' . $label . ' Concert Promo Codes in ' . $place;
}

/**
 * The promo code block for performer, venue, city, category and holiday pages: TAKE5 and TAKE10 with their terms, named for the
 * page's subject, as the same cards the event page uses (one column here, because these pages have a sidebar). $heading '' leaves
 * the heading to the caller. js/events-listing.js wires the Copy code buttons.
 */
function soSpecPromoHtml(string $heading, string $subject): string {
    return soSpecPromoMarkup($heading, $subject, false);
}

/** One promo card: ticket icon, % off, code, minimum order and a Copy code button. $p is a SO_PROMO_CODES entry. */
function soSpecPromoCard(array $p, string $subject): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $icon = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2556e0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9a2 2 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a2 2 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1Z"/><path d="M14 5v2M14 11v2M14 17v2"/></svg>';
    return '<div class="so-evp-promo__card"><span class="so-evp-promo__icon">' . $icon . '</span>'
        . '<div class="so-evp-promo__txt"><strong>' . (int) $p['pct'] . '% OFF</strong><span>Code: <b>' . $e($p['code']) . '</b></span>'
        . '<small>Take ' . (int) $p['pct'] . '% off your ' . $e($subject) . ' tickets when you spend $' . (int) $p['min'] . ' or more.</small></div>'
        . '<button type="button" class="btn btn-primary text-white offer-copy-btn so-evp-promo__btn" data-code="' . $e($p['code']) . '" aria-label="Copy code ' . $e($p['code']) . '">Copy code</button></div>';
}

/** The whole promo block. $wide: two cards side by side under the event page's own heading style; otherwise one card per row. */
function soSpecPromoMarkup(string $heading, string $subject, bool $wide): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $out = '<div class="so-evp-promo' . ($wide ? '' : ' so-evp-promo--stack') . '" id="promocode">'
        . ($heading !== '' ? '<h2 class="' . ($wide ? 'so-evp__h' : 'so-heading fw-bold fs-4 mb-3 text-black') . '">' . $e($heading) . '</h2>' : '')
        . '<p class="so-evp-promo__sub">Two promo codes are available for ' . $e($subject) . ' tickets. Enter the code in the promo code field at checkout.</p><div class="so-evp-promo__grid">';
    foreach (SO_PROMO_CODES as $p) $out .= soSpecPromoCard($p, $subject);
    return $out . '</div><p class="so-evp-promo__note">Codes apply only where the checkout accepts them, minimum order amounts apply, and codes can change or stop working without notice. See <a href="/tickets-promo-code">all ticket promo codes</a>.</p></div>';
}

/** Events of one kind in one city for the sections below (soonest first). [] when the feed has nothing or is down. */
function soSpecCityEvents(string $kind, $cityId, int $limit = 24): array {
    if ((int) $cityId <= 0) return [];
    try {
        $r = getCategoryEventsByLocation(soSpecCategoryKey($kind), 'city', (int) $cityId, ['perPage' => $limit, 'page' => 1, 'sort' => 'date/date', 'includeTotalCount' => 'true']);
    } catch (Throwable $t) { return []; }
    return ['events' => is_array($r['results'] ?? null) ? $r['results'] : [], 'total' => (int) ($r['totalCount'] ?? 0)];
}

/** A plain list of events: date, name, venue, linked. $skipIds are left out; at most $max rows. */
function soSpecEventList(array $events, array $skipIds, int $max = 6): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $rows = '';
    $n = 0;
    foreach ($events as $ev) {
        if ($n >= $max) break;
        if (in_array((int) ($ev['id'] ?? 0), $skipIds, true) || empty($ev['text']['name'])) continue;
        $ts = !empty($ev['date']['date']) ? strtotime($ev['date']['date']) : false;
        $where = trim((string) ($ev['venue']['text']['name'] ?? '') . ((string) ($ev['city']['text']['name'] ?? '') !== '' ? ', ' . $ev['city']['text']['name'] : ''), ', ');
        $rows .= '<li><a href="/event/' . $e(soEventSlug($ev)) . '">' . ($ts ? $e(date('M j', $ts)) . ': ' : '') . $e($ev['text']['name']) . ($where !== '' ? ' at ' . $e($where) : '') . '</a></li>';
        $n++;
    }
    return $rows === '' ? '' : '<ul class="so-speclist">' . $rows . '</ul>';
}

/**
 * "About <City>, <State>": what Seat Outlet lists there, from the city's own feed. Venue names come from the events listed.
 * $cityData is soSpecCityEvents(). '' when the city has no events listed.
 */
function soSpecCityInfoHtml(string $city, string $state, array $cityData, string $kind, string $cityHref, bool $cards = false): string {
    $events = $cityData['events'] ?? [];
    $total = (int) ($cityData['total'] ?? count($events));
    if ($total <= 0 || !$events) return '';
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $venues = [];
    foreach ($events as $ev) { $v = trim((string) ($ev['venue']['text']['name'] ?? '')); if ($v !== '') $venues[$v] = ($venues[$v] ?? 0) + 1; }
    arsort($venues);
    $top = array_slice(array_keys($venues), 0, 4);
    $noun = ['concert' => 'concerts', 'sports' => 'games', 'theater' => 'shows', 'festival' => 'festivals'][$kind] ?? 'events';
    $place = $city . ($state !== '' ? ', ' . $state : '');
    $p = '<p>Seat Outlet lists ' . number_format($total) . ' upcoming ' . $e($noun) . ' in ' . $e($place) . '.'
        . ($top ? ' They take place at ' . $e(implode(', ', array_slice($top, 0, -1)) . (count($top) > 1 ? ' and ' : '') . end($top)) . '.' : '')
        . ' Prices come from many sellers, so compare sections before you buy.</p>';
    if ($cards) {
        return '<section class="so-evv-venue"><div class="so-evv-venue__head"><span class="so-evv-ico so-evv-ico--lg">' . soEvvIcon('pin') . '</span><div><h2>About ' . $e($place) . '</h2>' . $p . '</div></div>'
            . '<ul class="so-evv-links"><li><a href="' . $e($cityHref) . '"><span class="so-evv-ico">' . soEvvIcon('tix') . '</span><span class="so-evv-lt">See all ' . $e($noun) . ' in ' . $e($place) . '</span><span class="so-evv-chev" aria-hidden="true">&rsaquo;</span></a></li></ul></section>';
    }
    return '<h2>About ' . $e($place) . '</h2>' . $p . '<p><a href="' . $e($cityHref) . '">See all ' . $e($noun) . ' in ' . $e($place) . '</a></p>';
}

/** Event page promo block: the same two codes and wording as soSpecPromoHtml(), two cards side by side. id="promocode" stays the anchor. */
function soSpecPromoCards(string $heading, string $subject): string {
    return soSpecPromoMarkup($heading, $subject, true);
}

/**
 * The guide: the facts the event page holds, in the order a buyer needs them. $f keys (all optional): when, venue, venueHref, address,
 * tickets (count), low (formatted price), eventsInCity, venueHref. Steps are the same three the page's own "how to buy" lists.
 */
function soSpecGuideHtml(string $heading, string $label, array $f, bool $note = true, bool $cards = false): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    if ($cards) return soSpecGuideCard($heading, $f, $note ? $label : '');
    $facts = [];
    if (!empty($f['when'])) $facts[] = '<li><strong>When:</strong> ' . $e($f['when']) . '</li>';
    if (!empty($f['venue'])) {
        $v = !empty($f['venueHref']) ? '<a href="' . $e($f['venueHref']) . '">' . $e($f['venue']) . '</a>' : $e($f['venue']);
        $facts[] = '<li><strong>Where:</strong> ' . $v . (!empty($f['address']) ? ', ' . $e($f['address']) : '') . '</li>';
    }
    if (!empty($f['tickets']) && (int) $f['tickets'] > 0) $facts[] = '<li><strong>Tickets listed:</strong> ' . number_format((int) $f['tickets']) . (!empty($f['low']) ? ', from ' . $e($f['low']) . ' each' : '') . '</li>';
    if (!empty($f['dates']) && (int) $f['dates'] > 1) $facts[] = '<li><strong>Dates in this city:</strong> ' . (int) $f['dates'] . ', so you can pick the night that suits you</li>';
    if (!$facts) return '';
    return '<h2>' . $e($heading) . '</h2><ul class="so-speclist">' . implode('', $facts) . '</ul>'
        . (!$note ? '' : '<p>Choose how many tickets you need, pick seats on the map, then check out. Prices are set by sellers and can be above or below face value. '
        . 'Read how <a href="/ticket-buyer-protection">ticket buyer protection</a> works before you order ' . $e($label) . ' tickets.</p>');
}

/** Event page card version of the guide: the same facts as icon rows (When, Where with the venue link and street, Tickets listed, Dates). */
function soSpecGuideCard(string $heading, array $f, string $noteLabel = ''): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $rows = [];
    if (!empty($f['when'])) $rows[] = ['cal', 'When', $e($f['when'])];
    if (!empty($f['venue'])) {
        $v = !empty($f['venueHref']) ? '<a href="' . $e($f['venueHref']) . '">' . $e($f['venue']) . '</a>' : $e($f['venue']);
        $rows[] = ['pin', 'Where', $v . (!empty($f['address']) ? '<br>' . $e($f['address']) : '')];
    }
    if (!empty($f['tickets']) && (int) $f['tickets'] > 0) $rows[] = ['tix', 'Tickets listed', number_format((int) $f['tickets']) . (!empty($f['low']) ? ', from ' . $e($f['low']) . ' each' : '')];
    if (!empty($f['dates']) && (int) $f['dates'] > 1) $rows[] = ['cal', 'Dates in this city', (int) $f['dates'] . ', so you can pick the night that suits you'];
    if (!$rows) return '';
    $out = '<div class="so-evv-card so-evv-guide"><h2>' . $e($heading) . '</h2><ul class="so-evv-facts">';
    foreach ($rows as [$ico, $t, $v]) $out .= '<li><span class="so-evv-ico">' . soEvvIcon($ico) . '</span><div><strong>' . $e($t) . '</strong><span>' . $v . '</span></div></li>';
    $out .= '</ul>';
    if ($noteLabel !== '') $out .= '<p class="so-evv-note">Choose how many tickets you need, pick seats on the map, then check out. Prices are set by sellers and can be above or below face value. '
        . 'Read how <a href="/ticket-buyer-protection">ticket buyer protection</a> works before you order ' . $e($noteLabel) . ' tickets.</p>';
    return $out . '</div>';
}

/** Line icons (24px viewBox, currentColor) for the event page venue, guide and upcoming cards. */
function soEvvIcon(string $k): string {
    $p = [
        'pin'  => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'cal'  => '<rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>',
        'tix'  => '<path d="M3 9a2 2 0 0 0 0 6v3a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-3a2 2 0 0 0 0-6V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1Z"/><path d="M14 5v2M14 11v2M14 17v2"/>',
        'route' => '<circle cx="6" cy="19" r="2"/><path d="M18 9s3-3.1 3-5.2A3 3 0 0 0 15 4c0 2 3 5 3 5Z"/><path d="M8 19h6.5a3.5 3.5 0 0 0 0-7h-5a3.5 3.5 0 0 1 0-7H12"/>',
        'bus'  => '<rect x="4" y="3" width="16" height="15" rx="3"/><path d="M4 11h16M8 18v3M16 18v3"/><circle cx="8" cy="14.5" r=".8"/><circle cx="16" cy="14.5" r=".8"/>',
        'web'  => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'cloud' => '<path d="M7 18h10a4 4 0 0 0 .5-8 6 6 0 0 0-11.3 1.5A3.3 3.3 0 0 0 7 18Z"/>',
    ][$k] ?? '';
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Event page card version of soSpecEventList(): rows with a date badge, the name, venue and city, and a chevron. '' when none. */
function soSpecEventRows(array $events, array $skipIds, int $max = 6): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $rows = ''; $n = 0;
    foreach ($events as $ev) {
        if ($n >= $max) break;
        if (in_array((int) ($ev['id'] ?? 0), $skipIds, true) || empty($ev['text']['name'])) continue;
        $ts = !empty($ev['date']['date']) ? strtotime($ev['date']['date']) : false;
        $where = trim((string) ($ev['venue']['text']['name'] ?? '') . ((string) ($ev['city']['text']['name'] ?? '') !== '' ? ', ' . $ev['city']['text']['name'] : ''), ', ');
        $rows .= '<li><a href="/event/' . $e(soEventSlug($ev)) . '">'
            . ($ts ? '<span class="so-evv-date"><small>' . $e(strtoupper(date('M', $ts))) . '</small><b>' . $e(date('j', $ts)) . '</b></span>' : '')
            . '<span class="so-evv-t"><strong>' . $e($ev['text']['name']) . '</strong>' . ($where !== '' ? '<small>' . $e($where) . '</small>' : '') . '</span>'
            . '<span class="so-evv-chev" aria-hidden="true">&rsaquo;</span></a></li>';
        $n++;
    }
    return $rows === '' ? '' : '<ul class="so-evv-rows">' . $rows . '</ul>';
}

/** The guide heading for a kind. */
function soSpecGuideHeading(string $label, string $kind, string $city, string $state): string {
    $place = $city . ($state !== '' ? ', ' . $state : '');
    if ($kind === 'concert') return 'Complete ' . $label . ' Concert Guide in ' . $place;
    if ($kind === 'sports') return 'Ultimate Guide to ' . $label . ' in ' . $city;
    return 'Your Guide to ' . $label . ' in ' . $city;
}

/** The heading above the similar events for a kind. */
function soSpecSimilarHeading(string $kind, string $city, string $state): string {
    $place = $city . ($state !== '' ? ', ' . $state : '');
    return ['concert' => 'Other Live Music Events in ', 'sports' => 'More Live Sports in ', 'theater' => 'Other Shows in ', 'festival' => 'Upcoming Festivals in '][$kind] . $place;
}

/**
 * "About <Performer>": the stored biography (the bios table; never fetched during the page request) cut at a sentence, else the
 * Wikidata description when the background lookup has stored one, else ''. Returns ['text' => ..., 'html' => ...] or [] for no section.
 */
function soSpecAboutText(int $performerId, string $name): string {
    $text = '';
    if ($performerId > 0) {
        try { $b = get_bio($performerId); $text = is_array($b) ? trim((string) ($b['bio'] ?? '')) : trim((string) $b); } catch (Throwable $t) { $text = ''; }
    }
    if ($text === '') {
        $facts = soEntityFacts($name, 'performer');
        $d = trim((string) ($facts['description'] ?? ''));
        if ($d !== '') $text = $name . ' is ' . (preg_match('/^[aeiou]/i', $d) ? 'an ' : 'a ') . $d . '.';
    }
    if ($text === '') return '';
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
    if (mb_strlen($text) > 700) {
        $cut = mb_substr($text, 0, 700);
        $dot = mb_strrpos($cut, '. ');
        $text = $dot !== false && $dot > 250 ? mb_substr($cut, 0, $dot + 1) : rtrim($cut, " ,;") . '.';
    }
    return $text;
}

/** The heading above the About section for a kind. */
function soSpecAboutHeading(string $label, string $kind): string {
    return $kind === 'sports' ? $label . ' Game Overview' : 'About ' . $label;
}

/**
 * Event page wording by kind, with the venue in it (the owner's venue sheet): focus keyword, H1, title and description candidates,
 * image alt text and the H2 of every section. $c keys: label, perf, venue, city, state, day (e.g. "May 5"), cat (sub-category name),
 * country, hasTickets. A page without a venue falls back to the city. Nothing here is a fact about the event beyond those fields.
 *
 * @return array{focus:string,h1:string,titles:string[],descs:string[],alt:string,h2:array<string,string>}
 */
function soSpecEventText(string $kind, array $c): array {
    $L = (string) $c['label']; $P = (string) ($c['perf'] ?? $L); $V = (string) ($c['venue'] ?? ''); $City = (string) ($c['city'] ?? ''); $St = (string) ($c['state'] ?? '');
    $place = $City . ($St !== '' ? ', ' . $St : '');
    $day = (string) ($c['day'] ?? ''); $cat = (string) ($c['cat'] ?? ''); $country = (string) ($c['country'] ?? 'the United States');
    $at = $V !== '' ? $V : $place;                       // "at <venue>" falls back to the city
    $cityAt = ($City !== '' && $V !== '' && stripos($V, $City) === false);   // add the city after the venue only when the venue's name does not already carry it ("The Rose Theatre, Phoenix")
    $sell = !empty($c['hasTickets']) ? ' before they sell out' : '';
    $out = ['h2' => []];
    switch ($kind) {
        case 'sports':
            $out['focus'] = "$L Game Tickets at $at";
            $out['h1'] = "Buy $L Game Tickets at $at";
            $out['titles'] = array_filter([$day !== '' ? "Buy $L Game Tickets at $at on $day" : '', $day !== '' ? "Buy $L Tickets in $City on $day" : '', $day !== '' ? "$L Tickets on $day" : '', "Buy $L Game Tickets at $at", $City !== '' ? "Buy $L Tickets in $City" : '', "$L Tickets"]);
            $out['descs'] = [
                "Buy $L game tickets at $at. Explore available seats and reserve your spot now at Seat Outlet$sell.",
                "Buy $L game tickets at $at. Reserve your spot now at Seat Outlet$sell.",
                "Buy $L tickets at Seat Outlet$sell.",
            ];
            $out['alt'] = "$L game at $at";
            $out['h2'] = ['tickets' => "Get Tickets for $L Game at $at", 'promo' => "$L Promo Codes for $at Game", 'about' => "$L Match Preview", 'faqs' => "FAQs about $L Tickets at $at",
                'venue' => "What to Know Before Attending at $at" . ($place !== '' ? " in $place" : ''), 'kind' => $cat !== '' ? "Upcoming $cat Events at $at" : "Upcoming Events at $at",
                'guide' => "Game Day Tips for $L at $at", 'other' => "Other Upcoming Games at $at"];
            break;
        case 'theater':
            $out['focus'] = "$L Tickets at $at";
            $out['h1'] = "Buy $L Tickets at $at" . ($cityAt ? ", $City" : '');
            $out['titles'] = array_filter([$day !== '' ? "Buy $L Tickets at $at" . ($cityAt ? ", $City" : '') . " on $day" : '', $day !== '' ? "Buy $L Tickets in $City on $day" : '', $day !== '' ? "$L Tickets on $day" : '', $V !== '' && $City !== '' ? "Buy $L Tickets at $V, $City" : '', "Buy $L Tickets at $at", "$L Tickets"]);
            $out['descs'] = [
                "Get $L tickets at $at" . ($cityAt ? " in $City" : '') . ". Find available seats, book online easily, and secure your spot for this must-see theater show$sell.",
                "Get $L tickets at $at. Find available seats and book online at Seat Outlet$sell.",
                "Get $L tickets at Seat Outlet$sell.",
            ];
            $out['alt'] = "$L theater show at $at";
            $out['h2'] = ['tickets' => "Get $L Tickets at $at Performances", 'promo' => "$L Ticket Promo Codes for $at", 'about' => "About $L", 'faqs' => "FAQs about $L Tickets at $at",
                'venue' => "About $at" . ($place !== '' && $V !== '' ? " in $place" : ''), 'kind' => $cat !== '' ? "Upcoming $cat Events at $at" : "Upcoming Events at $at",
                'guide' => "What to Expect at $L at $at", 'other' => "Upcoming Theater Events at $at"];
            break;
        case 'festival':
            $out['focus'] = "$L Passes for $at";
            $out['h1'] = "Buy $L Passes for $at" . ($cityAt ? ", $City" : '');
            $out['titles'] = array_filter([$day !== '' ? "Buy $L Passes for $at on $day" : '', $day !== '' ? "$L Passes on $day" : '', $V !== '' && $City !== '' ? "Buy $L Passes for $V, $City" : '', "Buy $L Passes for $at", "$L Tickets"]);
            $out['descs'] = [
                "Get $L passes for $at. Secure your festival access today and enjoy all the live performances and experiences at Seat Outlet$sell.",
                "Get $L passes for $at. Secure your festival access today at Seat Outlet$sell.",
                "Get $L passes at Seat Outlet$sell.",
            ];
            $out['alt'] = "$L festival at $at";
            $out['h2'] = ['tickets' => "Get $L Passes for $at", 'promo' => "$L Ticket Promo Codes at $at", 'about' => "About $L", 'faqs' => "FAQs about $L Tickets at $at",
                'venue' => "About $at" . ($place !== '' && $V !== '' ? " in $place" : ''), 'kind' => "$L Lineup and Performers",
                'guide' => "Ultimate Guide to $L at $at", 'other' => "More Events at $at"];
            break;
        default: // concert
            $out['focus'] = "$P Tickets for $at Show" . ($City !== '' && $V !== '' ? " in $City" : '');
            $out['h1'] = "Buy $P Tickets for $at Show" . ($place !== '' && $V !== '' ? " in $place" : '');
            $out['titles'] = array_filter([
                $day !== '' && $V !== '' ? "Buy $P Tickets for $V Show in $place on $day" : '',
                $day !== '' && $City !== '' ? "Buy $P Tickets for $City Show on $day" : '',
                $day !== '' ? "Buy $P Tickets on $day" : '',
                $V !== '' && $place !== '' ? "Buy $P Tickets for $V Show in $place" : '',
                $V !== '' && $City !== '' ? "Buy $P Tickets for $V Show in $City" : '',
                "Buy $P Tickets at $at", "Buy $P Concert Tickets", "$P Tickets"]);
            $out['descs'] = [
                "Buy $P tickets for $at show" . ($City !== '' && $V !== '' ? " in $City" : '') . ". Find great seats and secure your tickets online today at Seat Outlet$sell.",
                "Buy $P tickets for $at show. Secure your tickets online today at Seat Outlet$sell.",
                "Buy $P tickets at Seat Outlet$sell.",
            ];
            $out['alt'] = $P . ' live' . ($V !== '' ? " at $V" : '') . ($City !== '' ? " in $City" : '');
            $out['h2'] = ['tickets' => "Get $P Tickets for $at Show" . ($place !== '' && $V !== '' ? " in $place" : ''), 'promo' => "$P Promo Codes for $at event", 'about' => "About $P Performing at $at",
                'faqs' => "FAQs about $P Tickets at $at", 'venue' => "About $at" . ($place !== '' && $V !== '' ? " in $place" : ''), 'kind' => "$P Tour Dates Across $country",
                'guide' => "Complete $P Concert Guide at $at", 'other' => "Top Upcoming Performances at $at"];
    }
    $out['titles'] = array_values($out['titles']);
    return $out;
}

/** Events at one venue for the "other events" sections: [] on a failed feed. $kind '' lists every kind of event there. */
function soSpecVenueEvents(string $kind, $venueId, int $limit = 24): array {
    if ((int) $venueId <= 0) return [];
    try {
        $r = getCategoryEventsByLocation($kind === '' ? 'events' : soSpecCategoryKey($kind), 'venue', (int) $venueId, ['perPage' => $limit, 'page' => 1, 'sort' => 'date/date', 'includeTotalCount' => 'true']);
    } catch (Throwable $t) { return []; }
    return ['events' => is_array($r['results'] ?? null) ? $r['results'] : [], 'total' => (int) ($r['totalCount'] ?? 0)];
}

/**
 * "About <Venue> in <City>": what the ticket API and Wikidata give about the venue, and the links a visitor needs (directions, the
 * official site for parking, bag and entry rules). Nothing about parking or policies is stated: we do not hold those facts.
 * $venueRec is the venue's own record (address, geo), $facts is soEntityFacts() for the venue.
 */
function soSpecVenueInfoHtml(string $heading, string $venue, string $city, string $state, string $venueHref, array $venueRec, array $facts, int $upcoming, bool $cards = false): string {
    if ($venue === '') return '';
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $street = trim((string) ($venueRec['address']['text']['address1'] ?? ''));
    $place = $city . ($state !== '' ? ', ' . $state : '');
    $desc = trim((string) ($facts['description'] ?? ''));
    $p = '<p>' . $e($venue) . ($desc !== '' ? ' is ' . (preg_match('/^[aeiou]/i', $desc) ? 'an ' : 'a ') . $e($desc) . '' : '') . ($place !== '' ? ($desc !== '' ? ', in ' : ' is in ') . $e($place) : '') . '.'
        . ($street !== '' ? ' The address is ' . $e($street) . ($place !== '' ? ', ' . $e($place) : '') . '.' : '')
        . ($upcoming > 0 ? ' Seat Outlet lists ' . number_format($upcoming) . ' upcoming event' . ($upcoming === 1 ? '' : 's') . ' there.' : '') . '</p>';
    $q = rawurlencode(trim($venue . ' ' . $street . ' ' . $place));
    $links = ['<a href="https://www.google.com/maps/dir/?api=1&amp;destination=' . $q . '" target="_blank" rel="noopener">Directions to ' . $e($venue) . '</a>',
        '<a href="https://www.google.com/maps/dir/?api=1&amp;destination=' . $q . '&amp;travelmode=transit" target="_blank" rel="noopener">Public transit to ' . $e($venue) . '</a>'];
    if (!empty($facts['website'])) $links[] = '<a href="' . $e($facts['website']) . '" target="_blank" rel="noopener">Official website for parking, bag and entry rules</a>';
    if ($venueHref !== '') $links[] = '<a href="' . $e($venueHref) . '">All events at ' . $e($venue) . '</a>';
    $geo = $venueRec['geoLocation'] ?? ($venueRec['address']['geoLocation'] ?? []);
    if (($state !== '' || true) && isset($geo['latitude'], $geo['longitude']) && (float) $geo['latitude'] != 0.0 && ($venueRec['country']['alphaCode'] ?? ($venueRec['address']['country']['alphaCode'] ?? 'US')) === 'US') {
        $links[] = '<a href="https://forecast.weather.gov/MapClick.php?lat=' . rawurlencode((string) round((float) $geo['latitude'], 4)) . '&amp;lon=' . rawurlencode((string) round((float) $geo['longitude'], 4)) . '" target="_blank" rel="noopener">Weather forecast for ' . $e($city) . '</a>';
    }
    if ($cards) {
        // Event page: a pale blue card, pin icon, heading and text, then each link as a small card with its icon and a chevron.
        $icons = ['Directions' => 'route', 'Public tra' => 'bus', 'Official w' => 'web', 'All events' => 'tix', 'Weather fo' => 'cloud'];
        $cardsHtml = '';
        foreach ($links as $a) {
            $label = trim(strip_tags($a));
            $ico = $icons[substr($label, 0, 10)] ?? 'pin';
            $cardsHtml .= '<li>' . preg_replace('#^(<a [^>]*>)(.*)</a>$#s', '$1<span class="so-evv-ico">' . soEvvIcon($ico) . '</span><span class="so-evv-lt">$2</span><span class="so-evv-chev" aria-hidden="true">&rsaquo;</span></a>', $a) . '</li>';
        }
        return '<section class="so-evv-venue"><div class="so-evv-venue__head"><span class="so-evv-ico so-evv-ico--lg">' . soEvvIcon('pin') . '</span><div><h2>' . $e($heading) . '</h2>' . $p . '</div></div>'
            . '<ul class="so-evv-links">' . $cardsHtml . '</ul></section>';
    }
    return '<h2>' . $e($heading) . '</h2>' . $p . '<ul class="so-speclist"><li>' . implode('</li><li>', $links) . '</li></ul>';
}
