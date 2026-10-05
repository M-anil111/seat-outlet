<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| URL slugs without ids
|--------------------------------------------------------------------------
| Every entity page used to end in the TicketNetwork id (/city/austin-tx-247). The URLs are now plain words:
|
|     /artist/taylor-swift          /venue/madison-square-garden        /city/austin-tx
|     /state/texas                 /country/united-states-of-america   /category/rap-hip-hop
|     /event/taylor-swift-austin-tx-2026-10-12
|
| How it works
|   * The table `url_slugs` (db/migrations/0042_url_slugs.sql) holds one row per entity. A slug is chosen once, the first time
|     the site links to the entity, and never changes afterwards, so a URL is stable even when the name is edited upstream.
|   * soSlug($type, $name, $id, $ctx) is the only way to build a slug. It returns the stored slug, or creates the row.
|     If the database cannot be reached it returns the old name-and-id form instead, which still resolves (see below).
|   * soSlugResolve($type, $slug) is the only way to turn a requested slug back into an id. It looks the slug up in the table
|     and, failing that, accepts the old name-and-id form so every old URL keeps working. The page then answers 301 to the
|     clean URL, because the canonical slug is no longer the requested one.
|   * Collisions: a second entity with the same words gets a qualifier (the city for a venue), then -2, -3.
|
| Never build a slug by hand and never print an id into a URL. tools/check-url-slugs.php (run by CI) fails on createSlug( and
| on id-suffixed paths in templates.
*/

const SO_SLUG_TYPES = ['performer', 'venue', 'city', 'state', 'country', 'category', 'event', 'county'];
const SO_SLUG_MAX = 120;

/** Lower case words joined by single hyphens: accents folded, everything that is not a letter, digit, space or hyphen removed. */
function soSlugWords($s): string {
    $s = strtolower(trim(soAsciiFold((string) $s)));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    $s = trim($s, '-');
    if (strlen($s) > SO_SLUG_MAX) {
        $s = substr($s, 0, SO_SLUG_MAX);
        $cut = strrpos($s, '-');
        if ($cut !== false && $cut > 40) $s = substr($s, 0, $cut);
    }
    return $s;
}

/** The old slug form, name plus id ("austin-tx-247"). Only for the fallback when the table is unreachable and for reading old URLs. */
function soLegacySlug($name, $id): string {
    if (is_string($id) && !ctype_digit($id)) $id = strtolower($id);
    return soSlugWords($name) . '-' . $id;
}

function soSlugDb(): ?mysqli {
    return defined('MYSQLI') && MYSQLI instanceof mysqli ? MYSQLI : null;
}

/** Per request memory: ['type|id' => slug, 'type|slug' => id]. */
function &soSlugMemory(): array {
    static $mem = [];
    return $mem;
}

/** Normalize an id for the table: digits as int text, country codes upper case. */
function soSlugKeyId($id): string {
    if (is_int($id) || (is_string($id) && ctype_digit($id))) return (string) (int) $id;
    return strtoupper(trim((string) $id));
}

/**
 * Load many slugs with one query per type, so a listing of 100 events does not run 400 single lookups.
 * @param array<int,array{0:string,1:int|string}> $pairs [type, id] pairs
 */
function soSlugWarm(array $pairs): void {
    $db = soSlugDb();
    if (!$db) return;
    $mem = &soSlugMemory();
    $byType = [];
    foreach ($pairs as [$type, $id]) {
        if (!in_array($type, SO_SLUG_TYPES, true) || $id === '' || $id === 0 || $id === null) continue;
        $k = soSlugKeyId($id);
        if (isset($mem[$type . '|' . $k])) continue;
        $byType[$type][$k] = true;
    }
    foreach ($byType as $type => $ids) {
        foreach (array_chunk(array_keys($ids), 400) as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            try {
                $st = $db->prepare("SELECT ext_id, slug FROM url_slugs WHERE type = ? AND ext_id IN ($in)");
                if (!$st) return;
                $st->bind_param(str_repeat('s', count($chunk) + 1), $type, ...$chunk);
                $st->execute();
                $res = $st->get_result();
                while ($row = $res->fetch_row()) {
                    $mem[$type . '|' . $row[0]] = $row[1];
                    $mem[$type . '#' . $row[1]] = $row[0];
                }
                $st->close();
            } catch (Throwable $e) { return; }
        }
    }
}

/** Warm the slugs of everything an event list links to: events, performers, venues and cities. */
function soSlugWarmEvents(array $events): void {
    $pairs = [];
    foreach ($events as $e) {
        if (!is_array($e)) continue;
        if (!empty($e['id'])) $pairs[] = ['event', (int) $e['id']];
        if (!empty($e['venue']['id'])) $pairs[] = ['venue', (int) $e['venue']['id']];
        if (!empty($e['city']['id'])) $pairs[] = ['city', (int) $e['city']['id']];
        foreach (($e['performers'] ?? []) as $p) { if (!empty($p['id'])) $pairs[] = ['performer', (int) $p['id']]; }
    }
    soSlugWarm($pairs);
}

/**
 * The slug for an entity: stored one, or a new one. $ctx (all optional):
 *   place  "City, ST" (venue: tells apart venues with one name; event: part of the slug)
 *   date   an event's date ("2026-10-12" or a full timestamp)
 */
function soSlug(string $type, $name, $id, array $ctx = []): string {
    $name = (string) $name;
    if (!in_array($type, SO_SLUG_TYPES, true) || $id === '' || $id === 0 || $id === null || $id === '0') return soLegacySlug($name, $id);
    $k = soSlugKeyId($id);
    $mem = &soSlugMemory();
    if (isset($mem[$type . '|' . $k])) return $mem[$type . '|' . $k];
    $db = soSlugDb();
    if (!$db) return soLegacySlug($name, $id);

    try {
        $st = $db->prepare('SELECT slug FROM url_slugs WHERE type = ? AND ext_id = ?');
        if ($st) {
            $st->bind_param('ss', $type, $k);
            $st->execute();
            $row = $st->get_result()->fetch_row();
            $st->close();
            if ($row) {
                $mem[$type . '|' . $k] = $row[0];
                $mem[$type . '#' . $row[0]] = $k;
                return $row[0];
            }
        }
    } catch (Throwable $e) { return soLegacySlug($name, $id); }

    // Not stored yet: needs a real name (and for an event, a place and a date) to make a good slug. Without them keep the
    // old form for this one link; the entity's own page registers the clean slug and the old form 301s to it.
    $base = soSlugBase($type, $name, $ctx);
    if ($base === null) return soLegacySlug($name, $id);

    $qualifier = in_array($type, ['venue', 'state', 'category'], true) ? soSlugWords((string) ($ctx['place'] ?? '')) : '';
    $candidates = [$base];
    if ($qualifier !== '' && strpos($base, $qualifier) === false) $candidates[] = soSlugCut($base . '-' . $qualifier);
    for ($i = 2; $i <= 30; $i++) $candidates[] = soSlugCut($base) . '-' . $i;

    try {
        $now = date('Y-m-d H:i:s');
        $nm = mb_substr($name, 0, 255);
        $ins = $db->prepare('INSERT IGNORE INTO url_slugs (type, ext_id, slug, name, created_at) VALUES (?, ?, ?, ?, ?)');
        if (!$ins) return soLegacySlug($name, $id);
        foreach (array_values(array_unique($candidates)) as $cand) {
            $ins->bind_param('sssss', $type, $k, $cand, $nm, $now);
            $ins->execute();
            if ($ins->affected_rows === 1) {
                $mem[$type . '|' . $k] = $cand;
                $mem[$type . '#' . $cand] = $k;
                $ins->close();
                return $cand;
            }
            // Either another request just stored this very entity, or the slug belongs to another one: check which.
            $sel = $db->prepare('SELECT slug FROM url_slugs WHERE type = ? AND ext_id = ?');
            $sel->bind_param('ss', $type, $k);
            $sel->execute();
            $row = $sel->get_result()->fetch_row();
            $sel->close();
            if ($row) {
                $mem[$type . '|' . $k] = $row[0];
                $mem[$type . '#' . $row[0]] = $k;
                $ins->close();
                return $row[0];
            }
        }
        $ins->close();
    } catch (Throwable $e) { /* fall through to the old form */ }
    return soLegacySlug($name, $id);
}

function soSlugCut(string $s): string {
    return strlen($s) > SO_SLUG_MAX ? rtrim(substr($s, 0, SO_SLUG_MAX), '-') : $s;
}

/** The wanted slug for a new row, or null when the data is not enough to make a good one. */
function soSlugBase(string $type, string $name, array $ctx): ?string {
    $w = soSlugWords($name);
    if ($w === '') return null;
    if ($type === 'event') {
        $place = soSlugWords((string) ($ctx['place'] ?? ''));
        $date = (string) ($ctx['date'] ?? '');
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $date, $m)) $d = $m[1];
        elseif ($date === 'tbd') $d = 'date-tbd';   // the event exists but has no date yet
        else return null;                            // unknown date: not enough to make a slug
        return soSlugCut(trim($w . '-' . $place, '-') . '-' . $d);
    }
    return $w;
}

/**
 * The slug of an event from whatever array the caller has: a TicketNetwork catalog event (text/name, city, stateProvince, date/date) or the
 * short feed shape the site's own ajax endpoints use (name, id, iso, loc). A date is required to create a slug; without one the stored slug
 * is returned when there is one, else the old name-and-id form.
 */
function soEventSlug(array $event): string {
    $name = (string) ($event['text']['name'] ?? $event['name'] ?? '');
    $id = (int) ($event['id'] ?? 0);
    $place = trim((string) ($event['city']['text']['name'] ?? '') . ', ' . (string) ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
    if ($place === '') $place = (string) ($event['loc'] ?? $event['place'] ?? '');
    $date = '';
    foreach ([$event['date']['date'] ?? null, $event['iso'] ?? null, $event['date'] ?? null] as $cand) {
        if (is_string($cand) && preg_match('/^\d{4}-\d{2}-\d{2}/', $cand)) { $date = $cand; break; }
    }
    if ($date === '' && (isset($event['date']) || isset($event['iso']))) $date = 'tbd';   // a real event without a date
    return soSlug('event', $name, $id, ['place' => $place, 'date' => $date]);
}

/**
 * Every slug a browser-side event row needs (event, city, venue, performers), computed here so the page script never builds an address
 * from a name and an id. Attach it to the event arrays an ajax endpoint returns.
 */
function soEventSlugBundle(array $event): array {
    $place = trim((string) ($event['city']['text']['name'] ?? '') . ', ' . (string) ($event['stateProvince']['text']['abbr'] ?? ''), ', ');
    $b = ['slug' => soEventSlug($event), 'city' => '', 'venue' => '', 'performers' => []];
    if (!empty($event['city']['id']) && $place !== '') $b['city'] = soSlug('city', $place, (int) $event['city']['id']);
    if (!empty($event['venue']['id'])) $b['venue'] = soVenueSlug((string) ($event['venue']['text']['name'] ?? ''), (int) $event['venue']['id'], $place);
    foreach ($event['performers'] ?? [] as $p) {
        if (!empty($p['id']) && !empty($p['name'])) $b['performers'][(string) (int) $p['id']] = soSlug('performer', (string) $p['name'], (int) $p['id']);
    }
    return $b;
}

/** Venue slug from an event or venue array; the city tells apart venues that share a name. */
function soVenueSlug($name, $id, $place = ''): string {
    return soSlug('venue', $name, $id, ['place' => $place]);
}

/**
 * The id behind a requested slug: [id, legacy]. id is int (country: upper case code) or null when nothing matches.
 * legacy is true when the slug is the old name-and-id form; the page then redirects to the clean slug.
 */
function soSlugResolve(string $type, $slug): array {
    $slug = strtolower(trim((string) $slug, '/'));
    if ($slug === '' || !in_array($type, SO_SLUG_TYPES, true)) return [null, false];
    $mem = &soSlugMemory();
    $db = soSlugDb();
    if (isset($mem[$type . '#' . $slug])) return [soSlugOutId($type, $mem[$type . '#' . $slug]), false];
    if ($db) {
        try {
            $st = $db->prepare('SELECT ext_id FROM url_slugs WHERE type = ? AND slug = ?');
            if ($st) {
                $st->bind_param('ss', $type, $slug);
                $st->execute();
                $row = $st->get_result()->fetch_row();
                $st->close();
                if ($row) {
                    $mem[$type . '#' . $slug] = $row[0];
                    $mem[$type . '|' . $row[0]] = $slug;
                    return [soSlugOutId($type, $row[0]), false];
                }
            }
        } catch (Throwable $e) { /* fall through to the old form */ }
    }
    // The old form: words, a hyphen and the id (a country: the two letter code).
    if ($type === 'country') {
        return preg_match('/^.+-([a-z]{2})$/', $slug, $m) ? [strtoupper($m[1]), true] : [null, false];
    }
    if (preg_match('/^.+-(\d{1,10})$/', $slug, $m)) {
        $id = (int) $m[1];
        if ($id >= 1 && $id <= SO_MAX_ENTITY_ID) return [$id, true];
    }
    return [null, false];
}

function soSlugOutId(string $type, $ext) {
    return $type === 'country' ? (string) $ext : (int) $ext;
}

/**
 * A short id inside an old-form slug can be part of a real name ("blink-182"). The old form is only trusted for an id of one
 * to three digits when the words before it match the entity's own name.
 */
function soSlugLegacyPlausible(string $slug, bool $legacy, string $entityName): bool {
    if (!$legacy) return true;
    if (!preg_match('/^(.+)-(\d{1,10})$/', strtolower(trim($slug, '/')), $m)) return true;
    if (strlen($m[2]) >= 4) return true;
    return $m[1] === soSlugWords($entityName);
}
