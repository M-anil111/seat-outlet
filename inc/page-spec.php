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
 * The promo code block: TAKE5 and TAKE10 with their terms, named for the page's subject. $heading '' leaves the heading to the caller.
 * Uses the offer-pill markup every page group already carries (js/events-listing.js wires the Copy buttons).
 */
function soSpecPromoHtml(string $heading, string $subject): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $out = '<div class="so-specpromo" id="promocode" style="margin:32px 0">' . ($heading !== '' ? '<h2 class="so-heading fw-bold fs-4 mb-3 text-black">' . $e($heading) . '</h2>' : '')
        . '<p>Two promo codes are available for ' . $e($subject) . ' tickets. Enter the code in the promo code field at checkout.</p><div class="row g-3 mt-2">';
    foreach (SO_PROMO_CODES as $p) {
        $out .= '<div class="col-md-6"><div class="offer-pill d-flex align-items-center justify-content-between"><div class="d-flex align-items-center">'
            . '<div class="offer-icon me-3 d-flex align-items-center justify-content-center" style="flex-shrink:0"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12.5V5.8A1.8 1.8 0 0 1 4.8 4h6.7L21 13.5l-6.4 6.4L3 12.5Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"></path><circle cx="8.2" cy="8.2" r="1.1" fill="white"></circle></svg></div>'
            . '<div class="offer-text"><div class="offer-title">' . (int) $p['pct'] . '% OFF</div><div class="offer-subtitle">' . $e($p['code']) . '</div>'
            . '<div class="small">Take ' . (int) $p['pct'] . '% off your ' . $e($subject) . ' tickets when you spend $' . (int) $p['min'] . ' or more.</div></div></div>'
            . '<div class="offer-copy text-end"><button type="button" class="btn btn-primary text-white offer-copy-btn btn-sm px-4 rounded-pill" data-code="' . $e($p['code']) . '">Copy</button></div></div></div>';
    }
    return $out . '</div><p class="small mt-3 mb-0">Codes apply only where the checkout accepts them, minimum order amounts apply, and codes can change or stop working without notice. See <a href="/tickets-promo-code">all ticket promo codes</a>.</p></div>';
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
function soSpecCityInfoHtml(string $city, string $state, array $cityData, string $kind, string $cityHref): string {
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
    return '<h2>About ' . $e($place) . '</h2>' . $p . '<p><a href="' . $e($cityHref) . '">See all ' . $e($noun) . ' in ' . $e($place) . '</a></p>';
}

/**
 * The guide: the facts the event page holds, in the order a buyer needs them. $f keys (all optional): when, venue, venueHref, address,
 * tickets (count), low (formatted price), eventsInCity, venueHref. Steps are the same three the page's own "how to buy" lists.
 */
function soSpecGuideHtml(string $heading, string $label, array $f): string {
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
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
        . '<p>Choose how many tickets you need, pick seats on the map, then check out. Prices are set by sellers and can be above or below face value. '
        . 'Read how <a href="/ticket-buyer-protection">ticket buyer protection</a> works before you order ' . $e($label) . ' tickets.</p>';
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
