<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| Entity pages: shared helpers for artist, venue, city, state, country pages
|--------------------------------------------------------------------------
| Loaded by functions.php. Everything here is about URLs and indexation:
|
|   THE SLUG RULE (one rule, used for every canonical, link and redirect)
|     soEntitySlug($name, $id) = createSlug(): the name folded to ASCII
|     ("Beyonce" for the accented spelling), lowercased, every character that
|     is not a letter, digit, space or hyphen REMOVED (not turned into a
|     hyphen, so "AC/DC" is "acdc" and "Guns N' Roses" is "guns-n-roses"),
|     whitespace runs become one hyphen, then "-" and the numeric id. Country
|     slugs end in the lowercase alpha code ("united-states-of-america-us").
|     The id is the only thing that identifies the entity; the name part is
|     decoration, so any other spelling gets a 301 to the canonical one.
|
|   IDS: strict. The slug must end in digits only and fit a signed 32-bit int
|     (the TicketNetwork API answers an error, not a 404, above that), so
|     junk ids are rejected before any API call.
|
|   ZERO-EVENT PAGES: noindex,follow, and remembered in a small cache file so
|     sitemap.php can leave them out without calling the API per URL.
*/

const SO_MAX_ENTITY_ID = 2147483647;

/** Fold accents to plain ASCII so "Beyonce" with an accent still produces a readable slug. */
function soAsciiFold($s) {
    $s = (string) $s;
    if ($s === '' || !preg_match('/[^\x20-\x7e]/', $s)) return $s;
    $s = str_replace(["\u{00A0}", "\u{2013}", "\u{2014}", "\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}"], [' ', '-', '-', "'", "'", '"', '"'], $s);
    if (class_exists('Transliterator')) {
        $t = \Transliterator::create('Any-Latin; Latin-ASCII');
        if ($t) {
            $r = $t->transliterate($s);
            if (is_string($r)) return $r;
        }
    }
    return strtr($s, [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
    ]);
}

/** The slug rule (see the file header). Country codes are lowercased so the canonical never has mixed case. */
function soEntitySlug($name, $id) {
    return createSlug($name, $id);
}

/**
 * The numeric id at the end of a slug, or null. Strict: digits only after the last hyphen ("wicked-1145abc" is not an
 * id), between 1 and 2147483647 (bigger ids make the API answer an error, which must never reach it).
 */
function soSlugTrailingId($slug): ?int {
    $slug = trim((string) $slug, '/');
    if (!preg_match('/(?:^|-)(\d{1,10})$/', $slug, $m)) return null;
    $id = (int) $m[1];
    return ($id >= 1 && $id <= SO_MAX_ENTITY_ID) ? $id : null;
}

/** Same, for country slugs: the two-letter code (upper case) or null. */
function soSlugCountryCode($slug): ?string {
    if (!preg_match('/-([A-Za-z]{2})$/', trim((string) $slug, '/'), $m)) return null;
    return strtoupper($m[1]);
}

/** 301 to $path, keeping the visitor's own query string (filters, page). Never returns. */
function soRedirect301(string $path): void {
    $q = $_GET;
    unset($q['slug'], $q['loc']);
    if (headers_sent()) return;
    http_response_code(301);
    header('Location: ' . $path . ($q ? '?' . http_build_query($q) : ''));
    header('Cache-Control: public, max-age=3600');
    exit;
}

/**
 * 301 to the canonical URL when the requested slug is not exactly the canonical one (wrong name part, upper case,
 * trailing junk). Call before any output. $loc* are the second path segment of the artist-city style pages.
 */
function soRedirectToCanonicalSlug(string $prefix, string $requested, string $canonical, string $locRequested = '', string $locCanonical = ''): void {
    $requested = trim($requested, '/');
    $locRequested = trim($locRequested, '/');
    if ($requested === $canonical && ($locCanonical === '' || $locRequested === $locCanonical)) return;
    soRedirect301('/' . $prefix . '/' . $canonical . ($locCanonical !== '' ? '/' . $locCanonical : ''));
}

/** True when the last TicketNetwork call had to be skipped or failed (outage, throttling, open circuit). */
function soApiDegraded(): bool {
    return !empty($GLOBALS['tn_degraded']);
}

/* ------------------------------------------------------- zero-event pages */

const SO_ZERO_PAGES_KEY = 'so_zero_pages';
const SO_ZERO_PAGES_TTL_DAYS = 14;

/**
 * Remember (or forget) that a page showed no events, so sitemap.php can skip it. Called by the entity renderers on every
 * render: a rare cache write only when the state changes. The sitemap never calls the API per URL; it only reads this.
 */
function soZeroPageNote(string $path, bool $isZero): void {
    $map = cache_get(SO_ZERO_PAGES_KEY, 365 * 86400);
    if (!is_array($map)) $map = [];
    $has = isset($map[$path]);
    if ($isZero === $has && (!$isZero || (time() - (int) $map[$path]) < 86400)) return;
    if ($isZero) { $map[$path] = time(); } else { unset($map[$path]); }
    $cut = time() - SO_ZERO_PAGES_TTL_DAYS * 86400;
    foreach ($map as $p => $ts) { if ((int) $ts < $cut) unset($map[$p]); }
    cache_set(SO_ZERO_PAGES_KEY, $map);
}

/** Paths (like "/sports-city/buffalo-ny-677") seen with zero events in the last two weeks. */
function soZeroPages(): array {
    $map = cache_get(SO_ZERO_PAGES_KEY, 365 * 86400);
    if (!is_array($map)) return [];
    $cut = time() - SO_ZERO_PAGES_TTL_DAYS * 86400;
    return array_keys(array_filter($map, fn($ts) => (int) $ts >= $cut));
}

/* -------------------------------------------------------------- wording */

function soCountWord(int $n, string $one = 'event', ?string $many = null): string {
    return $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));
}

/* ------------------------------------------------------ small data helpers */

/** "City, ST" for an event/venue-like array. */
function soPlaceLabel(array $x): string {
    return trim((string) ($x['city']['text']['name'] ?? '') . ', ' . (string) ($x['stateProvince']['text']['abbr'] ?? ''), ', ');
}

/**
 * Performers and venues that appear in the events already fetched, most dates first. Built from data in hand: no extra
 * API call. Returns ['performers' => [[id,name,count]], 'venues' => [[id,name,count,city]]].
 */
function soTopFromEvents(array $events, int $limit = 6): array {
    $perf = []; $ven = [];
    foreach ($events as $ev) {
        foreach ($ev['performers'] ?? [] as $p) {
            $pid = (int) ($p['id'] ?? 0); $pn = trim((string) ($p['name'] ?? ''));
            if ($pid <= 0 || $pn === '') continue;
            if (!isset($perf[$pid])) $perf[$pid] = ['id' => $pid, 'name' => $pn, 'count' => 0];
            $perf[$pid]['count']++;
        }
        $vid = (int) ($ev['venue']['id'] ?? 0); $vn = trim((string) ($ev['venue']['text']['name'] ?? ''));
        if ($vid > 0 && $vn !== '') {
            if (!isset($ven[$vid])) $ven[$vid] = ['id' => $vid, 'name' => $vn, 'count' => 0, 'city' => soPlaceLabel($ev)];
            $ven[$vid]['count']++;
        }
    }
    $sort = function (array $a) use ($limit) { usort($a, fn($x, $y) => $y['count'] <=> $x['count']); return array_slice($a, 0, $limit); };
    return ['performers' => $sort(array_values($perf)), 'venues' => $sort(array_values($ven))];
}

/** Lowest listed price among the events, as ['value' => float, 'formatted' => '$12', 'eventId' => int]. */
function soCheapestEvent(array $events): ?array {
    $best = null;
    foreach ($events as $ev) {
        $v = $ev['pricingInfo']['lowPrice']['value'] ?? null;
        if ($v === null || (float) $v <= 0) continue;
        if ($best === null || (float) $v < $best['value']) {
            $best = ['value' => (float) $v, 'formatted' => (string) ($ev['pricingInfo']['lowPrice']['text']['formatted'] ?? ('$' . number_format((float) $v, 0))), 'eventId' => (int) ($ev['id'] ?? 0)];
        }
    }
    return $best;
}

/* ------------------------------------------------------------ structured data */

/** Event nodes inside one ItemList, for venue/city/state/country pages. */
function soBuildEventItemListSchema(array $events, string $listName, int $limit = 20): ?array {
    $items = []; $pos = 1;
    foreach (array_slice($events, 0, $limit) as $ev) {
        $start = $ev['date']['datetime'] ?? $ev['date']['date'] ?? '';
        $name = trim((string) ($ev['text']['name'] ?? ''));
        if ($start === '' || $name === '' || empty($ev['id'])) continue;
        $url = HOME_URL . '/event/' . soEntitySlug($name, $ev['id']);
        $node = [
            '@type' => 'Event', 'name' => $name, 'startDate' => $start,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'url' => $url,
            'location' => [
                '@type' => 'Place', 'name' => (string) ($ev['venue']['text']['name'] ?? ''),
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => (string) ($ev['city']['text']['name'] ?? ''), 'addressRegion' => (string) ($ev['stateProvince']['text']['abbr'] ?? ''), 'addressCountry' => (string) ($ev['country']['alphaCode'] ?? 'US')],
            ],
        ];
        $price = $ev['pricingInfo']['lowPrice']['value'] ?? null;
        if ($price !== null && (float) $price > 0) {
            // No validFrom: we do not know when the listing went live, and "today" on every render is not a fact.
            $node['offers'] = ['@type' => 'Offer', 'url' => $url, 'price' => number_format((float) $price, 2, '.', ''), 'priceCurrency' => 'USD',
                'availability' => !empty($ev['_metadata']['hasTickets']) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut'];
        }
        $items[] = ['@type' => 'ListItem', 'position' => $pos++, 'item' => $node];
    }
    if (!$items) return null;
    return ['@type' => 'ItemList', 'name' => $listName, 'numberOfItems' => count($items), 'itemListElement' => $items];
}

/** Place node for a venue page (address and coordinates only when the feed has them). */
function soBuildVenuePlaceSchema(array $venue, string $url): array {
    $addr = $venue['address']['text'] ?? [];
    $geo = $venue['geoLocation'] ?? $venue['address']['geoLocation'] ?? [];
    $node = ['@type' => 'Place', 'name' => (string) ($venue['text']['name'] ?? ''), 'url' => $url];
    $street = trim((string) ($addr['address1'] ?? ''));
    $address = ['@type' => 'PostalAddress', 'addressLocality' => (string) ($venue['city']['text']['name'] ?? ''), 'addressRegion' => (string) ($venue['stateProvince']['text']['abbr'] ?? ''), 'addressCountry' => (string) ($venue['country']['alphaCode'] ?? 'US')];
    if ($street !== '') $address['streetAddress'] = $street;
    $zip = (string) ($venue['address']['postalCode'] ?? $venue['postalCode']['code'] ?? '');
    if ($zip !== '' && $zip !== '00000') $address['postalCode'] = $zip;
    $node['address'] = $address;
    if (isset($geo['latitude'], $geo['longitude']) && (float) $geo['latitude'] != 0.0) {
        $node['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $geo['latitude'], 'longitude' => (float) $geo['longitude']];
    }
    return $node;
}

/** Breadcrumb trail nodes: [['label','url'], ...] plus the current page label. */
function soBreadcrumbNodes(array $trail, string $current) {
    return buildBreadcrumbListSchema($trail, $current);
}

/* ------------------------------------------------------------- visual bits */

/** An initials tile (no stock photo) for an entity without a real picture. Pure markup, no request. */
function soTileHtml($name, string $class = 'so-tile'): string {
    $hue = hexdec(substr(md5((string) $name), 0, 4)) % 360;
    return '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" style="--so-hue:' . (int) $hue . '" role="img" aria-label="' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') . '"><span aria-hidden="true">'
        . htmlspecialchars(soInitials($name), ENT_QUOTES, 'UTF-8') . '</span></span>';
}

/** Is this getEntityImage() result a real picture (not the generic fallback)? */
function soImageIsReal(array $img): bool {
    return in_array($img['status'] ?? '', ['ok', 'manual'], true) && ($img['url'] ?? '') !== '';
}

/**
 * One event row, the same markup the "load more" script appends, fully escaped. Used by the venue, city, state and
 * country pages. $opts: cheapestId (badge), showVenue (default true), showPlace (default true).
 */
function soRenderEventRow(array $event, array $opts = []): void {
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $year = date('Y');
    $ts = strtotime((string) ($event['date']['date'] ?? '')) ?: null;
    $name = (string) ($event['text']['name'] ?? '');
    $venueName = (string) ($event['venue']['text']['name'] ?? '');
    $place = soPlaceLabel($event);
    $slug = soEntitySlug($name, $event['id'] ?? 0);
    $venueSlug = !empty($event['venue']['id']) ? soEntitySlug($venueName, $event['venue']['id']) : '';
    $citySlug = !empty($event['city']['id']) ? soEntitySlug($place, $event['city']['id']) : '';
    $names = []; $pslugs = [];
    foreach ($event['performers'] ?? [] as $p) {
        $pn = (string) ($p['name'] ?? ''); $pid = $p['id'] ?? '';
        if ($pn !== '') $names[] = $pn;
        if ($pn !== '' && $pid !== '') $pslugs[] = soEntitySlug($pn, $pid);
    }
    $hasPrice = eventFromPrice($event) !== '';
    ?>
    <div class="d-flex align-items-center justify-content-between performer-event-item">
        <div class="date-box text-center me-3">
            <div class="month"><?php echo $ts ? $h(strtoupper(date('M', $ts))) : ''; ?></div>
            <div class="day"><?php echo $ts ? $h(date('d', $ts)) : ''; ?></div>
            <?php if ($ts && date('Y', $ts) > $year) { ?><div class="month"><?php echo $h(date('Y', $ts)); ?></div><?php } ?>
        </div>
        <div class="flex-grow-1 w-50">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold day-weeks"><?php echo $ts ? $h(date('D', $ts)) : ''; ?></span>
                <span class="dot">·</span>
                <span class="time-clock"><?php echo $h($event['date']['text']['time'] ?? ''); ?></span>
                <button type="button" class="icon-i so-info-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" aria-label="Event details for <?php echo $h($name); ?>"
                    data-id="<?php echo (int) ($event['id'] ?? 0); ?>"
                    data-date="<?php echo $ts ? $h(date('D, M d', $ts)) : ''; ?>"
                    data-venue="<?php echo $h($venueName); ?>"
                    data-venueSlug="<?php echo $h($venueSlug); ?>"
                    data-location="<?php echo $h($place); ?>"
                    data-title="<?php echo $h($name); ?>"
                    data-performers="<?php echo $h(implode('|', $names)); ?>"
                    data-performer-slugs="<?php echo $h(implode('|', $pslugs)); ?>"><i class="bi bi-info-circle text-muted" aria-hidden="true"></i></button>
            </div>
            <?php if (($opts['showVenue'] ?? true) && $venueSlug !== '') { ?><div class="ev-venue"><a href="/venue/<?php echo $h($venueSlug); ?>"><?php echo $h($venueName); ?></a></div><?php } ?>
            <?php if (($opts['showPlace'] ?? true) && $citySlug !== '') { ?><div class="ev-place"><a href="/city/<?php echo $h($citySlug); ?>"><?php echo $h($place); ?></a></div><?php } ?>
            <div class="ev-name">
                <a href="/event/<?php echo $h($slug); ?>"><?php echo $h($name); ?><span class="visually-hidden"> tickets, <?php echo $h(($ts ? date('M j', $ts) : '') . ' at ' . $venueName . ', ' . $place); ?></span></a>
            </div>
        </div>
        <div class="ms-3">
            <?php if (!empty($opts['cheapestId']) && (int) ($event['id'] ?? 0) === (int) $opts['cheapestId']) { ?><div class="so-cheapest-row"><span class="event-cheapest-badge">Cheapest date</span></div><?php } ?>
            <?php renderEventPriceTag($event); ?>
            <a href="/event/<?php echo $h($slug); ?>" class="btn <?php echo $hasPrice ? 'btn-primary' : 'btn-outline-primary'; ?> d-flex align-items-center gap-2" aria-label="<?php echo $hasPrice ? 'Buy tickets for' : 'View'; ?> <?php echo $h($name); ?>">
                <span class="d-none d-md-inline"><?php echo $hasPrice ? 'Buy Tickets' : 'View Event'; ?></span>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
    <?php
}
