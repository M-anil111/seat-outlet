<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/*
|--------------------------------------------------------------------------
| Entity images: performers, teams, venues, festivals, cities
|--------------------------------------------------------------------------
| Loaded by functions.php. Replaces the old request-time chain of
| Wikipedia pageimages -> Google Knowledge Graph -> Openverse, which
|
|   - returned Wikipedia's lead image regardless of license (team logos and
|     show posters there are non-free / fair-use, which does not cover a
|     commercial site) and never captured attribution for the CC photos;
|   - cached whatever it ended up with, including the generic category
|     fallback after a transient 429 or timeout, forever, with no admin
|     override;
|   - ran every lookup synchronously inside page requests / card AJAX
|     calls with no timeouts.
|
| Design now:
|
|   getEntityImage()    serve-only by default: returns the stored image, or
|                       the type's fallback, and queues a resolution for
|                       cron/resolve-images.php. Single-entity pages
|                       (performer.php, venue.php) pass resolve=true so the
|                       first visitor still gets a real image, bounded by
|                       tight timeouts.
|   resolveEntityImage() one source chain per entity type (see
|                       IMAGE_SOURCE_CHAIN). Every source returns the image
|                       URL *and* its license + attribution, and only
|                       licenses that allow commercial reuse and derivatives
|                       (we resize and re-encode) are accepted.
|   images table        holds url, status (ok / manual / fallback / pending),
|                       source, license, attribution, expires_at. Fallbacks
|                       expire and are retried; manual rows are never
|                       auto-replaced. See db/migrations/0008.
|
| Sources:
|   wikidata     wbsearchentities -> P31/P106 type check -> P18 -> Commons
|                imageinfo (license, artist, 1200px thumb; SVGs come back as
|                PNG). People, notable venues, cities, big festivals.
|   thesportsdb  team fanart/banner + stadium thumb. ONLY used when
|                THESPORTSDB_KEY is set to a real (paid) key: their public test
|                key is not for commercial use, the art is user-contributed
|                and logos are trademarks, so the badge is never used and
|                teams fall back to Wikidata. See docs/launch-checklist.md.
|   openverse    CC0 / CC BY / CC BY-SA photos with the attribution string
|                Openverse builds. Festivals (Wikimedia rarely has them).
|   pexels       optional, only when PEXELS_API_KEY is set. City skylines.
|   unsplash     optional, only when UNSPLASH_ACCESS_KEY is set. City photos. Unsplash's API terms require the photo to be HOTLINKED from
|                images.unsplash.com (never copied to our own storage), a call to the photo's download endpoint when we pick it, and a
|                visible credit with links to the photographer and Unsplash carrying utm_source. See imageSourceUnsplash().
*/

const IMAGE_FALLBACK_TTL_DAYS   = 7;      // retry a miss after a week
const IMAGE_ERROR_TTL_MINUTES   = 90;     // retry sooner after a 429/timeout
const IMAGE_HTTP_TIMEOUT        = 6;
const IMAGE_MAX_DOWNLOAD_BYTES  = 15 * 1024 * 1024;

const IMAGE_SOURCE_CHAIN = [
    'artist'   => ['wikidata'],
    'team'     => ['thesportsdb', 'wikidata'],
    'venue'    => ['wikidata', 'thesportsdb_venue'],
    'festival' => ['wikidata', 'openverse'],
    'city'     => ['unsplash', 'wikidata', 'pexels'],
];

/* ------------------------------------------------------------------ keys */

function imageEntityKey($type, $name) {
    // Same 'so_img_' + md5('type|name') scheme the old code used, so rows
    // resolved before this rewrite are still found.
    return 'so_img_' . md5($type . '|' . trim((string) $name));
}

function imageUserAgent() {
    return 'SeatOutletBot/1.0 (+' . HOME_URL . '/contact)';
}

/**
 * Which image chain a TicketNetwork performer belongs to, from its
 * defaultCategory: sports -> team, festivals subtree -> festival, else artist.
 */
function imageEntityTypeForPerformer($defaultCategory) {
    $path = (string) ($defaultCategory['path'] ?? '');
    if ($path !== '') {
        if (strpos($path, TN_CATEGORY_PATH_FESTIVAL) === 0) return 'festival';
        if (strpos($path, TN_CATEGORY_PATH_SPORTS) === 0)   return 'team';
    }
    $root = strtolower((string) ($defaultCategory['ancestors'][0]['text']['name'] ?? $defaultCategory['text']['name'] ?? ''));
    if ($root === 'sports') return 'team';
    return 'artist';
}

function imageFallbackUrl($type, $defaultCategory = [], $tab = '') {
    switch ($type) {
        case 'venue': return AWS_CDN_URL . 'images/venue.webp';
        case 'city':  return '';
        default:      return getCategoryFallbackImage($defaultCategory ?: [], $tab ?: ($type === 'team' ? 'sports' : 'concerts'));
    }
}

/* -------------------------------------------------------------- storage */

/**
 * Image storage is decoration: if the database is behind the code (a migration that did not run yet) or briefly unavailable,
 * the page shows the initials tile instead of failing. Every helper below that touches the images table therefore
 * catches the database error, logs it once per request and answers "nothing stored".
 */
function imageDbFailed(\Throwable $e) {
    static $logged = false;
    if (!$logged) { $logged = true; error_log('images table unavailable (run php db/migrate.php --status): ' . $e->getMessage()); }
}

function imageRecordGet($key, $mysqli = MYSQLI) {
    try {
        $stmt = $mysqli->prepare('SELECT * FROM images WHERE imgkey = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    } catch (\Throwable $e) {
        imageDbFailed($e);
        return null;
    }
}

function imageRecordUpsert(array $r, $mysqli = MYSQLI) {
    try {
        return imageRecordUpsertRun($r, $mysqli);
    } catch (\Throwable $e) {
        imageDbFailed($e);
        return false;
    }
}

function imageRecordUpsertRun(array $r, $mysqli) {
    $stmt = $mysqli->prepare('
        INSERT INTO images (imgkey, entity_type, entity_name, url, status, source, source_url, license, attribution, store_key, attempts, resolved_at, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            entity_type = VALUES(entity_type), entity_name = VALUES(entity_name),
            url = VALUES(url), status = VALUES(status), source = VALUES(source),
            source_url = VALUES(source_url), license = VALUES(license), attribution = VALUES(attribution), store_key = VALUES(store_key),
            attempts = VALUES(attempts), resolved_at = VALUES(resolved_at), expires_at = VALUES(expires_at)
    ');
    if (!$stmt) return false;
    $key = $r['imgkey']; $type = $r['entity_type'] ?? null; $name = $r['entity_name'] ?? null;
    $url = (string) ($r['url'] ?? ''); $status = $r['status'] ?? 'ok';
    $source = $r['source'] ?? null; $sourceUrl = $r['source_url'] ?? null;
    $license = $r['license'] ?? null; $attribution = $r['attribution'] ?? null; $storeKey = $r['store_key'] ?? null;
    $attempts = (int) ($r['attempts'] ?? 0); $resolvedAt = $r['resolved_at'] ?? null; $expiresAt = $r['expires_at'] ?? null;
    $stmt->bind_param('ssssssssssiss', $key, $type, $name, $url, $status, $source, $sourceUrl, $license, $attribution, $storeKey, $attempts, $resolvedAt, $expiresAt);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/** Queue an entity for the cron resolver. No-op if a row already exists. */
function imageQueue($type, $name, $fallbackUrl = '', $mysqli = MYSQLI) {
    $name = trim((string) $name);
    if ($name === '') return false;
    $key = imageEntityKey($type, $name);
    try {
        $stmt = $mysqli->prepare('INSERT IGNORE INTO images (imgkey, entity_type, entity_name, url, status) VALUES (?, ?, ?, ?, \'pending\')');
        if (!$stmt) return false;
        $stmt->bind_param('ssss', $key, $type, $name, $fallbackUrl);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (\Throwable $e) {
        imageDbFailed($e);
        return false;
    }
}

/* --------------------------------------------------------------- public */

/**
 * @param string $type   artist | team | venue | festival | city
 * @param array  $opts   category => TN defaultCategory (for fallback + hints)
 *                       tab      => 'concerts'|'sports'|... (fallback hint)
 *                       resolve  => true to resolve synchronously if unknown
 * @return array url, credit, license, source, status
 */
function getEntityImage($type, $name, array $opts = []) {
    $name = trim((string) $name);
    $fallback = imageFallbackUrl($type, $opts['category'] ?? [], $opts['tab'] ?? '');
    $out = ['url' => $fallback, 'credit' => '', 'license' => '', 'source' => '', 'status' => 'fallback'];
    if ($name === '') return $out;

    $row = imageRecordGet(imageEntityKey($type, $name));

    if ($row && in_array($row['status'], ['ok', 'manual'], true) && $row['url'] !== '') {
        return imageRowToResult($row);
    }
    if ($row && $row['status'] === 'fallback' && !empty($row['expires_at']) && strtotime($row['expires_at']) > time()) {
        imageNoteMiss($row['imgkey']);
        return $out; // known miss, not due for retry yet
    }
    if (!empty($opts['resolve'])) {
        try {
            $resolved = resolveEntityImage($type, $name, $opts['category'] ?? [], $fallback);
        } catch (\Throwable $e) {
            // Image lookup is decoration: never let it take the page down.
            error_log('Image resolve failed (' . $type . '/' . $name . '): ' . $e->getMessage());
            $resolved = null;
        }
        return $resolved ? imageRowToResult($resolved) : $out;
    }
    if (!$row) {
        imageQueue($type, $name, $fallback);
    } else {
        imageNoteMiss($row['imgkey']);
    }
    return $out;
}

/**
 * "Pictures first": reorder a list so items whose performer, team or venue has a real stored picture come before the ones that
 * would show an initials tile. Stable, and it only reads what is already stored (no lookups, no network).
 *   $describe($item) => [type, name, category]       what to look the picture up by
 *   $primary($item)  => scalar|null                  optional sort key that still wins: pictures only break ties
 *                                                    (distance, date, price), so a "nearest first" list stays nearest first
 * An image database that is behind or down ranks nobody, so the original order is kept.
 */
function soImageFirst(array $items, callable $describe, ?callable $primary = null): array {
    if (count($items) < 2) return $items;
    $has = [];
    foreach ($items as $i => $item) {
        $has[$i] = 0;
        try {
            [$type, $name, $cat] = $describe($item);
            if ((string) $name !== '') {
                $info = getEntityImage($type, $name, ['category' => $cat ?: [], 'resolve' => false]);
                $has[$i] = (in_array($info['status'] ?? '', ['ok', 'manual'], true) && ($info['url'] ?? '') !== '') ? 1 : 0;
            }
        } catch (\Throwable $e) { /* decoration only */ }
    }
    if (!array_filter($has)) return $items;
    $idx = array_keys($items);
    usort($idx, function ($a, $b) use ($items, $has, $primary) {
        $pa = $primary ? ($primary($items[$a]) ?? PHP_INT_MAX) : 0;
        $pb = $primary ? ($primary($items[$b]) ?? PHP_INT_MAX) : 0;
        return [$pa, -$has[$a], $a] <=> [$pb, -$has[$b], $b];
    });
    return array_map(function ($i) use ($items) { return $items[$i]; }, $idx);
}

/**
 * Count (sampled 1 in 10, so a busy page costs almost no writes) that a page was served the initials tile for this key.
 * The admin "misses" view sorts by it: the gaps people actually see come first.
 */
function imageNoteMiss($key, $mysqli = MYSQLI) {
    if (mt_rand(1, 10) !== 1) return;
    try {
        $stmt = $mysqli->prepare('UPDATE images SET miss_hits = miss_hits + 10 WHERE imgkey = ? AND status IN (\'pending\', \'fallback\')');
        if (!$stmt) return;
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $stmt->close();
    } catch (\Throwable $e) {
        imageDbFailed($e);
    }
}

/**
 * Team pictures stored before the hash-keyed layout have a slug-keyed address under /artistteams/, but the files for many teams (all
 * of the NBA) live under /teams/ (the SE Ranking audit counted 157 broken images). A slug-keyed /artistteams/ address is served from
 * /teams/ instead; hash-keyed files (32 hex characters) stay where processAndStoreImage wrote them.
 */
function imageNormalizeUrl(string $url): string {
    if (preg_match('#^(.*)/artistteams/([a-z0-9-]+)\.webp$#', $url, $m) && !preg_match('/^[0-9a-f]{32}$/', $m[2])) {
        return $m[1] . '/teams/' . $m[2] . '.webp';
    }
    return $url;
}

function imageRowToResult(array $row) {
    return [
        'url'     => imageNormalizeUrl((string) $row['url']),
        'credit'  => (string) ($row['attribution'] ?? ''),
        'license' => (string) ($row['license'] ?? ''),
        'source'  => (string) ($row['source'] ?? ''),
        'status'  => (string) ($row['status'] ?? ''),
        'source_url' => (string) ($row['source_url'] ?? ''),
    ];
}

/**
 * Run the source chain for one entity and persist the outcome. Returns the
 * stored row (ok) or null (fallback stored with an expiry).
 */
function resolveEntityImage($type, $name, $defaultCategory = [], $fallbackUrl = '') {
    $name = trim((string) $name);
    $key  = imageEntityKey($type, $name);
    $existing = imageRecordGet($key);
    if ($existing && $existing['status'] === 'manual') {
        return $existing;
    }
    $attempts = (int) ($existing['attempts'] ?? 0) + 1;
    $rateLimited = false;
    $found = null;

    foreach (IMAGE_SOURCE_CHAIN[$type] ?? ['wikidata'] as $source) {
        $result = null;
        switch ($source) {
            case 'wikidata':          $result = imageSourceWikidata($type, $name, $defaultCategory); break;
            case 'thesportsdb':       $result = imageSourceTheSportsDb($name); break;
            case 'thesportsdb_venue': $result = imageSourceTheSportsDbVenue($name); break;
            case 'openverse':         $result = imageSourceOpenverse($name, $type); break;
            case 'pexels':            $result = imageSourcePexels($name, $type); break;
            case 'unsplash':          $result = imageSourceUnsplash($name, $type); break;
        }
        if ($result === 'RATE_LIMITED') { $rateLimited = true; continue; }
        if (is_array($result) && !empty($result['image_url'])) { $found = $result + ['source' => $source]; break; }
    }

    $storeFailed = false;
    if ($found) {
        $storeKey = imageStoreKey($type, $key);
        if (!empty($found['hotlink'])) {
            // Unsplash: the picture stays on Unsplash's server (their terms), we store only the address and the credit, and tell
            // Unsplash once that the photo was picked.
            $storeKey = null;
            $cdnUrl = (string) $found['image_url'];
            imageUnsplashTrackDownload((string) ($found['download_location'] ?? ''));
        } else {
            $cdnUrl = processAndStoreImage($found['image_url'], $name, imageStorageFolder($type), $storeKey);
        }
        $storeFailed = ($cdnUrl === '');
        if ($cdnUrl) {
            $row = [
                'imgkey' => $key, 'entity_type' => $type, 'entity_name' => $name,
                'url' => $cdnUrl, 'status' => 'ok', 'source' => $found['source'],
                'source_url' => mb_substr((string) ($found['source_url'] ?? ''), 0, 1000),
                'license' => mb_substr((string) ($found['license'] ?? ''), 0, 100),
                'attribution' => mb_substr((string) ($found['attribution'] ?? ''), 0, 500),
                'store_key' => $storeKey,
                'attempts' => $attempts, 'resolved_at' => date('Y-m-d H:i:s'), 'expires_at' => null,
            ];
            imageRecordUpsert($row);
            return $row;
        }
    }

    // A source *had* an image but download/storage failed: that's our
    // problem, not a missing image, so retry soon rather than in a week.
    $ttl = ($rateLimited || $storeFailed) ? ('+' . IMAGE_ERROR_TTL_MINUTES . ' minutes') : ('+' . IMAGE_FALLBACK_TTL_DAYS . ' days');
    imageRecordUpsert([
        'imgkey' => $key, 'entity_type' => $type, 'entity_name' => $name,
        'url' => $fallbackUrl !== '' ? $fallbackUrl : ($existing['url'] ?? ''), 'status' => 'fallback',
        'source' => null, 'source_url' => null, 'license' => null, 'attribution' => null,
        'attempts' => $attempts, 'resolved_at' => null, 'expires_at' => date('Y-m-d H:i:s', strtotime($ttl)),
    ]);
    return null;
}

/**
 * Where a resolved file is stored: "<folder>/<hash>.webp", the hash being the imgkey's md5 (type + name). Never the slug:
 * two entities that slugify alike can no longer share a file, and an existing object is never adopted under a new
 * licence label (processAndStoreImage always downloads what the source gave us and writes it here). Rows resolved before
 * this change keep their old slug-keyed url and are still served; "Re-verify legacy" in admin queues them again.
 */
function imageStoreKey($type, $imgKey) {
    $hash = strpos((string) $imgKey, 'so_img_') === 0 ? substr((string) $imgKey, 7) : md5((string) $imgKey);
    return imageStorageFolder($type) . '/' . $hash . '.webp';
}

function imageStorageFolder($type) {
    // Keep the folders the old code used where they exist so already-uploaded
    // objects are reused instead of re-downloaded.
    return ['artist' => 'artists', 'team' => 'artistteams', 'venue' => 'venues', 'festival' => 'festivals', 'city' => 'cities'][$type] ?? 'misc';
}

/* ------------------------------------------------------------------ http */

/** @return array [httpCode, body] ; code 0 on transport failure */
function imageHttpGet($url, array $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => IMAGE_HTTP_TIMEOUT,
        CURLOPT_USERAGENT      => imageUserAgent(),
        CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$body === false ? 0 : $code, (string) $body];
}

function imageHttpJson($url, array $headers = []) {
    // Test seam: a CLI test can answer the Wikidata / Commons calls without the network.
    if (isset($GLOBALS['so_image_http_stub']) && is_callable($GLOBALS['so_image_http_stub'])) return ($GLOBALS['so_image_http_stub'])($url);
    [$code, $body] = imageHttpGet($url, $headers);
    if ($code === 429) return 'RATE_LIMITED';
    if ($code !== 200) return null;
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

/* ------------------------------------------------------------- licensing */

/** Commons/Openverse license strings we may resize, re-encode and host. */
function imageLicenseIsUsable($license) {
    $l = strtolower(trim((string) $license));
    if ($l === '') return false;
    if (preg_match('/non-?free|fair use|nc\b|-nc|nd\b|-nd|no derivatives|noncommercial/', $l)) return false;
    return (bool) preg_match('/^(cc0|cc-0|public domain|pd|cc[ -]?by(-sa)?[ -]?[0-9.]*|by(-sa)?$|attribution|no restrictions|gfdl|mit|bsd|apache)/', $l);
}

/* -------------------------------------------------------------- wikidata */

/** Strip the tour/feature suffixes TN puts on performer names before searching. */
function imageCleanName($name) {
    $n = preg_replace('/\s*\(.*?\)/', '', (string) $name);
    $n = preg_replace('/\s+(feat\.?|featuring|with|vs\.?|&|and)\s+.*$/i', '', $n);
    $n = preg_replace('/(?:\s+-\s+|:\s+).*$/', '', $n);   // "& Juliet - Musical", shown as "& Juliet: Musical" since soCleanTnName()
    return trim($n);
}

const WD_TYPES_PERFORMER = [
    'Q5', 'Q215380', 'Q2088357', 'Q5741069', 'Q9212979', // human, musical group, ensemble, rock band, duo
    'Q58483083', 'Q2743', 'Q25379',                       // dramatico-musical work, musical, play
];
const WD_TYPES_TEAM  = ['Q12973014', 'Q476028', 'Q4438121', 'Q847017'];       // sports team, football club, sports org, sports club
const WD_TYPES_VENUE = ['Q483110', 'Q1076486', 'Q24354', 'Q641226', 'Q153562', 'Q17350442', 'Q1329623', 'Q57660343']; // stadium, sports venue, theater, arena, opera house, venue, concert hall, performing arts venue
const WD_TYPES_CITY  = ['Q515', 'Q1093829', 'Q3957', 'Q1549591', 'Q62049', 'Q15284'];   // city, US city, town, big city, county seat, municipality
const WD_TYPES_FESTIVAL = ['Q868557', 'Q132241', 'Q1751626', 'Q2416217'];   // music festival, festival, art festival, theatre festival
const WD_TYPES_REJECT = ['Q11424', 'Q7889', 'Q482994', 'Q7366', 'Q571', 'Q5398426', 'Q13442814', 'Q4167410', 'Q134556', 'Q8261']; // film, video game, album, song, book, TV series, article, disambiguation, single, novel

const IMAGE_STATE_NAMES = [
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut',
    'DE' => 'Delaware', 'DC' => 'Washington, D.C.', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois',
    'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
    'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana',
    'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
    'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania',
    'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
    'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    'PR' => 'Puerto Rico', 'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
    'NL' => 'Newfoundland and Labrador', 'NS' => 'Nova Scotia', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec', 'SK' => 'Saskatchewan',
];

/**
 * Is this Wikidata item inside the named state? Follows "located in the administrative territorial entity" (P131) up to
 * three levels (city -> county -> state), because many cities point at a county, not the state. Costs one request per level.
 * Without this "Springfield, MO" accepted the first Springfield on the list.
 */
function imageWdLocatedInState(array $ent, $stateName) {
    $want = strtolower((string) $stateName);
    $ids = imageWdIds($ent, 'P131');
    for ($level = 0; $level < 3 && $ids; $level++) {
        $data = imageHttpJson('https://www.wikidata.org/w/api.php?' . http_build_query([
            'action' => 'wbgetentities', 'ids' => implode('|', array_slice($ids, 0, 5)), 'props' => 'labels|claims', 'languages' => 'en', 'format' => 'json',
        ]));
        if (!is_array($data)) return false;
        $next = [];
        foreach ($data['entities'] ?? [] as $e) {
            if (strtolower((string) ($e['labels']['en']['value'] ?? '')) === $want) return true;
            $next = array_merge($next, imageWdIds($e, 'P131'));
        }
        $ids = array_values(array_unique($next));
    }
    return false;
}

function imageSourceWikidata($type, $name, $defaultCategory = []) {
    $query = $type === 'city' ? $name : imageCleanName($name);
    $stateName = '';
    if ($type === 'city' && preg_match('/^(.*\S)\s*,\s*([A-Za-z]{2})$/', $name, $m)) {
        // "Springfield, MO": search the city name, then require the match to lie in Missouri.
        $query = trim($m[1]);
        $stateName = IMAGE_STATE_NAMES[strtoupper($m[2])] ?? '';
        if ($stateName === '') return null;   // a state we cannot verify: no picture beats the wrong city's picture
    }
    if ($query === '') return null;

    $search = imageHttpJson('https://www.wikidata.org/w/api.php?' . http_build_query([
        'action' => 'wbsearchentities', 'search' => $query, 'language' => 'en', 'type' => 'item', 'limit' => 6, 'format' => 'json',
    ]));
    if ($search === 'RATE_LIMITED') return 'RATE_LIMITED';
    $hits = $search['search'] ?? [];
    if (!$hits) return null;

    $ids = array_slice(array_column($hits, 'id'), 0, 6);
    $ents = imageHttpJson('https://www.wikidata.org/w/api.php?' . http_build_query([
        'action' => 'wbgetentities', 'ids' => implode('|', $ids), 'props' => 'claims|descriptions', 'languages' => 'en', 'format' => 'json',
    ]));
    if ($ents === 'RATE_LIMITED') return 'RATE_LIMITED';

    $keywords = array_map('strtolower', imageHintKeywords($type, $defaultCategory));
    $queryNorm = strtolower($query);

    foreach ($hits as $hit) {
        $ent = $ents['entities'][$hit['id']] ?? null;
        if (!$ent) continue;
        $p31  = imageWdIds($ent, 'P31');
        $p106 = imageWdIds($ent, 'P106');
        if (array_intersect($p31, WD_TYPES_REJECT)) continue;

        $desc = strtolower((string) ($ent['descriptions']['en']['value'] ?? $hit['description'] ?? ''));
        $descMatch = false;
        foreach ($keywords as $kw) { if ($kw !== '' && strpos($desc, $kw) !== false) { $descMatch = true; break; } }

        $typeMatch = false;
        switch ($type) {
            case 'artist':   $typeMatch = (bool) array_intersect($p31, WD_TYPES_PERFORMER); break;
            case 'team':     $typeMatch = (bool) array_intersect($p31, WD_TYPES_TEAM); break;
            case 'venue':    $typeMatch = (bool) array_intersect($p31, WD_TYPES_VENUE); break;
            case 'city':     $typeMatch = (bool) array_intersect($p31, WD_TYPES_CITY); break;
            case 'festival': $typeMatch = (bool) array_intersect($p31, WD_TYPES_FESTIVAL); break;
        }
        // A human with no performer-ish occupation/description (e.g. a
        // politician sharing the name) is not a match.
        // A person must have an occupation (P106): a politician or athlete sharing a band's name is not the band.
        if ($type === 'artist' && in_array('Q5', $p31, true) && !$p106) continue;
        if (!$typeMatch && !$descMatch) continue;
        // Label must at least start with the query for anything but exact hits.
        $label = strtolower((string) ($hit['label'] ?? ''));
        if ($label !== $queryNorm && strpos($label, $queryNorm) !== 0 && strpos($queryNorm, $label) !== 0) continue;

        $file = $ent['claims']['P18'][0]['mainsnak']['datavalue']['value'] ?? '';
        if ($file === '') continue;
        if ($stateName !== '' && !imageWdLocatedInState($ent, $stateName)) continue;   // right name, wrong state

        $info = imageCommonsFileInfo($file);
        if ($info === 'RATE_LIMITED') return 'RATE_LIMITED';
        if (!$info) continue;
        return $info + ['wikidata_id' => $hit['id']];
    }
    return null;
}

function imageWdIds(array $ent, $prop) {
    $ids = [];
    foreach ($ent['claims'][$prop] ?? [] as $claim) {
        $id = $claim['mainsnak']['datavalue']['value']['id'] ?? null;
        if ($id) $ids[] = $id;
    }
    return $ids;
}

function imageHintKeywords($type, $defaultCategory) {
    $generic = [
        'artist'   => ['singer', 'musician', 'band', 'rapper', 'comedian', 'group', 'orchestra', 'dj', 'songwriter', 'musical', 'play', 'show', 'circus', 'magician', 'wrestler', 'performer', 'actor', 'duo', 'ensemble', 'choir', 'pianist', 'violinist', 'conductor', 'entertainer'],
        'team'     => ['team', 'club', 'franchise', 'football', 'basketball', 'baseball', 'hockey', 'soccer', 'rugby', 'lacrosse', 'sports', 'racing', 'nascar', 'rodeo'],
        'venue'    => ['stadium', 'arena', 'theatre', 'theater', 'hall', 'center', 'centre', 'amphitheater', 'amphitheatre', 'pavilion', 'coliseum', 'ballpark', 'venue', 'auditorium', 'casino', 'racetrack', 'speedway', 'fairgrounds', 'field', 'park', 'dome', 'club'],
        'city'     => ['city', 'town', 'capital', 'municipality', 'village', 'borough'],
        'festival' => ['festival', 'music festival', 'fair', 'carnival', 'expo', 'convention'],
    ];
    $cat = ''; $sub = '';
    if (!empty($defaultCategory)) {
        $cat = strtolower((string) ($defaultCategory['ancestors'][0]['text']['name'] ?? $defaultCategory['text']['name'] ?? ''));
        $sub = (string) ($defaultCategory['ancestors'][1]['text']['name'] ?? ($defaultCategory['depth'] == 2 ? $defaultCategory['text']['name'] : ''));
    }
    $catWords = function_exists('getKgKeywordsByCategory') ? (array) getKgKeywordsByCategory($cat, $sub) : [];
    return array_values(array_unique(array_merge($generic[$type] ?? [], $catWords)));
}

/**
 * Commons imageinfo: 1200px thumb URL (SVG -> PNG), license, artist.
 * Rejects anything not usable commercially with derivatives.
 */
function imageCommonsFileInfo($file) {
    $data = imageHttpJson('https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action' => 'query', 'titles' => 'File:' . $file, 'prop' => 'imageinfo',
        'iiprop' => 'url|extmetadata|size|mime', 'iiurlwidth' => 1200,
        'iiextmetadatafilter' => 'LicenseShortName|Artist|Credit|LicenseUrl|Restrictions',
        'format' => 'json',
    ]));
    if ($data === 'RATE_LIMITED') return 'RATE_LIMITED';
    $pages = $data['query']['pages'] ?? [];
    $page = $pages ? reset($pages) : null;
    $ii = $page['imageinfo'][0] ?? null;
    if (!$ii) return null;
    $meta = $ii['extmetadata'] ?? [];
    $license = trim(strip_tags((string) ($meta['LicenseShortName']['value'] ?? '')));
    if (!imageLicenseIsUsable($license)) return null;
    if (!empty($meta['Restrictions']['value']) && stripos($meta['Restrictions']['value'], 'trademark') !== false) {
        // Commons flags trademarked logos; a photo is fine, a logo is not ours to reuse.
        return null;
    }
    $mime = (string) ($ii['mime'] ?? '');
    if ($mime !== '' && strpos($mime, 'image/') !== 0) return null;
    $artist = trim(strip_tags((string) ($meta['Artist']['value'] ?? $meta['Credit']['value'] ?? '')));
    $artist = preg_replace('/\s+/', ' ', $artist);
    return [
        'image_url'   => $ii['thumburl'] ?? $ii['url'] ?? '',
        'source_url'  => $ii['descriptionurl'] ?? ('https://commons.wikimedia.org/wiki/File:' . rawurlencode($file)),
        'license'     => $license,
        'attribution' => trim(($artist !== '' ? $artist . ' / ' : '') . 'Wikimedia Commons, ' . $license),
    ];
}

/* ----------------------------------------------------------- thesportsdb */

/**
 * The TheSportsDB key, or '' when none usable is configured. Their free/test keys ("3", "1", "123") are not licensed for
 * a commercial site, so without a real THESPORTSDB_KEY this source is skipped and teams use Wikidata only.
 */
function imageTheSportsDbKey() {
    $k = trim((string) getenv('THESPORTSDB_KEY'));
    return ($k === '' || in_array($k, ['1', '2', '3', '123'], true)) ? '' : $k;
}

function imageSourceTheSportsDb($name) {
    if (imageTheSportsDbKey() === '') return null;
    $data = imageHttpJson('https://www.thesportsdb.com/api/v1/json/' . rawurlencode(imageTheSportsDbKey()) . '/searchteams.php?t=' . rawurlencode(imageCleanName($name)));
    if ($data === 'RATE_LIMITED') return 'RATE_LIMITED';
    $want = strtolower(imageCleanName($name));
    foreach ($data['teams'] ?? [] as $team) {
        if (strtolower((string) ($team['strTeam'] ?? '')) !== $want) continue;
        // Photo-style assets only. The badge/logo is a trademark: never used, not even as a last resort.
        foreach (['strFanart1', 'strBanner', 'strStadiumThumb'] as $field) {
            if (!empty($team[$field])) {
                return [
                    'image_url'   => $team[$field],
                    'source_url'  => 'https://www.thesportsdb.com/team/' . ($team['idTeam'] ?? ''),
                    'license'     => 'TheSportsDB (user-contributed)',
                    'attribution' => 'Image: TheSportsDB.com',
                ];
            }
        }
    }
    return null;
}

function imageSourceTheSportsDbVenue($name) {
    if (imageTheSportsDbKey() === '') return null;   // test key: not licensed for commercial use
    // Venue search is a paid-tier endpoint; on the free key it returns
    // nothing and we fall through. Teams carry their home stadium thumb, so
    // try the venue name against team stadiums as a cheap second chance.
    $data = imageHttpJson('https://www.thesportsdb.com/api/v1/json/' . rawurlencode(imageTheSportsDbKey()) . '/searchvenues.php?t=' . rawurlencode($name));
    if ($data === 'RATE_LIMITED') return 'RATE_LIMITED';
    foreach ($data['venues'] ?? [] as $v) {
        if (!empty($v['strThumb'])) {
            return ['image_url' => $v['strThumb'], 'source_url' => 'https://www.thesportsdb.com/venue/' . ($v['idVenue'] ?? ''), 'license' => 'TheSportsDB (user-contributed)', 'attribution' => 'Image: TheSportsDB.com'];
        }
    }
    return null;
}

/* ------------------------------------------------------------- openverse */

function imageSourceOpenverse($name, $type) {
    $q = imageCleanName($name);
    if ($q === '') return null;
    $data = imageHttpJson('https://api.openverse.org/v1/images/?' . http_build_query([
        'q' => $q, 'license' => 'cc0,by,by-sa', 'mature' => 'false', 'page_size' => 10,
    ]));
    if ($data === 'RATE_LIMITED') return 'RATE_LIMITED';
    $words = array_filter(preg_split('/\W+/', strtolower($q)), fn($w) => strlen($w) > 3);
    foreach ($data['results'] ?? [] as $r) {
        if (!imageLicenseIsUsable((string) ($r['license'] ?? ''))) continue;
        if ((int) ($r['width'] ?? 0) < 800 || (int) ($r['height'] ?? 0) < 450) continue;
        $title = strtolower((string) ($r['title'] ?? '') . ' ' . implode(' ', array_column($r['tags'] ?? [], 'name')));
        $hit = 0; foreach ($words as $w) { if (strpos($title, $w) !== false) $hit++; }
        if ($words && $hit < max(1, (int) ceil(count($words) / 2))) continue;
        $lic = strtoupper((string) $r['license']) . ' ' . (string) ($r['license_version'] ?? '');
        return [
            'image_url'   => $r['url'],
            'source_url'  => $r['foreign_landing_url'] ?? ($r['url'] ?? ''),
            'license'     => trim(($r['license'] === 'cc0' ? 'CC0' : 'CC ' . $lic)),
            'attribution' => trim((string) ($r['attribution'] ?? (($r['creator'] ?? 'Unknown') . ' via Openverse'))),
        ];
    }
    return null;
}

/* ---------------------------------------------------------------- pexels */

function imageSourcePexels($name, $type) {
    $key = getenv('PEXELS_API_KEY');
    if ($key === false || $key === '') return null;
    $q = $type === 'city' ? $name . ' skyline' : $name;
    $data = imageHttpJson('https://api.pexels.com/v1/search?' . http_build_query(['query' => $q, 'per_page' => 5, 'orientation' => 'landscape']), ['Authorization: ' . $key]);
    if ($data === 'RATE_LIMITED') return 'RATE_LIMITED';
    $photo = $data['photos'][0] ?? null;
    if (!$photo) return null;
    return [
        'image_url'   => $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? '',
        'source_url'  => $photo['url'] ?? '',
        'license'     => 'Pexels License',
        'attribution' => 'Photo by ' . ($photo['photographer'] ?? 'Unknown') . ' on Pexels',
    ];
}

/* -------------------------------------------------------------- unsplash */

/** Query-string tags Unsplash asks for on every link back to them. */
const IMAGE_UNSPLASH_UTM = 'utm_source=seatoutlet&utm_medium=referral';

/**
 * One Unsplash API call. Returns [http code, decoded body or null, requests left this hour or null]. A CLI test can answer it
 * through $GLOBALS['so_unsplash_stub'] (callable(url) => [code, array, remaining]).
 */
function imageUnsplashGet(string $url): array {
    if (isset($GLOBALS['so_unsplash_stub']) && is_callable($GLOBALS['so_unsplash_stub'])) return ($GLOBALS['so_unsplash_stub'])($url);
    $key = (string) getenv('UNSPLASH_ACCESS_KEY');
    $remaining = null;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => IMAGE_HTTP_TIMEOUT, CURLOPT_USERAGENT => imageUserAgent(),
        CURLOPT_HTTPHEADER => ['Authorization: Client-ID ' . $key, 'Accept-Version: v1', 'Accept: application/json'],
        CURLOPT_HEADERFUNCTION => function ($c, $h) use (&$remaining) {
            if (stripos($h, 'x-ratelimit-remaining:') === 0) $remaining = (int) trim(substr($h, 22));
            return strlen($h);
        },
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = $body === false ? null : json_decode((string) $body, true);
    return [$body === false ? 0 : $code, is_array($data) ? $data : null, $remaining];
}

/**
 * City photo from Unsplash (UNSPLASH_ACCESS_KEY). The free tier allows 50 requests an hour, so this runs only from the cron
 * resolver (never during a page view), stops asking for the rest of the hour once the quota is nearly used, and a city that
 * cannot be matched confidently gets no photo (the page keeps its initials tile) instead of a wrong one.
 * A photo is accepted only when its own text (description, alt text, location, tags) names the city and the place is in the same
 * state or in the US or Canada, so "Paris, TX" is never answered with the Eiffel Tower.
 */
function imageSourceUnsplash($name, $type) {
    if ($type !== 'city' || (string) getenv('UNSPLASH_ACCESS_KEY') === '') return null;
    $pause = function_exists('cache_get') ? (int) cache_get('unsplash_pause_until', 7200) : 0;
    if ($pause > time()) return 'RATE_LIMITED';
    if (!preg_match('/^(.+?),\s*([A-Z]{2})$/', trim((string) $name), $m)) return null;
    [$cityName, $abbr] = [trim($m[1]), $m[2]];
    $stateName = IMAGE_STATE_NAMES[$abbr] ?? '';
    $url = 'https://api.unsplash.com/search/photos?' . http_build_query(['query' => $cityName . ($stateName !== '' ? ' ' . $stateName : '') . ' skyline', 'orientation' => 'landscape', 'content_filter' => 'high', 'per_page' => 10]);
    [$code, $data, $left] = imageUnsplashGet($url);
    if ($left !== null && $left <= 4 && function_exists('cache_set')) cache_set('unsplash_pause_until', time() + 3600);   // keep a few calls for the download notices
    if ($code === 429 || ($code === 403 && $left === 0)) { if (function_exists('cache_set')) cache_set('unsplash_pause_until', time() + 3600); return 'RATE_LIMITED'; }
    if ($code !== 200 || !is_array($data)) return null;

    $cityLc = mb_strtolower($cityName);
    foreach ($data['results'] ?? [] as $p) {
        if ((int) ($p['width'] ?? 0) < 1600 || (int) ($p['height'] ?? 0) <= 0 || ($p['width'] / $p['height']) < 1.3) continue;
        $loc = (array) ($p['location'] ?? []);
        $tags = implode(' ', array_map(fn($t) => (string) ($t['title'] ?? ''), (array) ($p['tags'] ?? [])));
        $text = mb_strtolower(implode(' ', [(string) ($p['description'] ?? ''), (string) ($p['alt_description'] ?? ''), (string) ($loc['name'] ?? ''), (string) ($loc['city'] ?? ''), $tags]));
        if (!preg_match('/(?<![a-z])' . preg_quote($cityLc, '/') . '(?![a-z])/u', $text)) continue;
        $country = mb_strtolower((string) ($loc['country'] ?? ''));
        $sameState = $stateName !== '' && mb_strpos($text, mb_strtolower($stateName)) !== false;
        if (!$sameState && $country !== '' && !in_array($country, ['united states', 'united states of america', 'usa', 'us', 'canada'], true)) continue;
        $user = (array) ($p['user'] ?? []);
        $raw = (string) ($p['urls']['raw'] ?? '');
        if ($raw === '' || empty($user['name']) || empty($user['username']) || empty($p['links']['download_location']) || empty($p['links']['html'])) continue;
        return [
            'hotlink'           => true,   // image_url is a 220px square shown at 480 for sharp screens
            'image_url'         => $raw . (strpos($raw, '?') === false ? '?' : '&') . 'w=480&h=480&fit=crop&crop=entropy&q=75&auto=format',
            'source_url'        => $p['links']['html'] . '?' . IMAGE_UNSPLASH_UTM,
            'license'           => 'Unsplash License',
            'attribution'       => mb_substr((string) $user['name'], 0, 120) . '|' . preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $user['username']),
            'download_location' => (string) $p['links']['download_location'],
        ];
    }
    return null;
}

/** Tell Unsplash the photo was picked (required by their API terms). Failure is logged, never fatal: the photo still shows. */
function imageUnsplashTrackDownload(string $downloadLocation): void {
    if ($downloadLocation === '' || strpos($downloadLocation, 'https://api.unsplash.com/') !== 0) return;
    [$code] = imageUnsplashGet($downloadLocation);
    if ($code !== 200) error_log('Unsplash download notice answered ' . $code);
}

/* --------------------------------------------------------------- render */

/** URL of the licence text for the licence strings we store ("CC BY-SA 4.0", "CC0", "Pexels License"), or ''. */
function imageLicenseUrl($license) {
    $l = strtolower(trim((string) $license));
    if (preg_match('/^cc[ -]?0|public domain|pd\b/', $l)) return 'https://creativecommons.org/publicdomain/zero/1.0/';
    if (preg_match('/^cc[ -]?by(-sa)?[ -]?(\d\.\d)/', $l, $m)) return 'https://creativecommons.org/licenses/by' . ($m[1] ? '-sa' : '') . '/' . $m[2] . '/';
    if (preg_match('/^cc[ -]?by(-sa)?\b/', $l, $m)) return 'https://creativecommons.org/licenses/by' . ($m[1] ? '-sa' : '') . '/4.0/';
    if (strpos($l, 'pexels') === 0) return 'https://www.pexels.com/license/';
    if (strpos($l, 'unsplash') === 0) return 'https://unsplash.com/license';
    return '';
}

/**
 * Visible credit under an image: "Photo: creator, <licence link>, <source link>. Cropped and resized." CC BY and BY-SA
 * need the creator, a link to the licence, and a note that the file was changed (we resize and re-encode). Used wherever
 * the image appears (hero, About copy, venue and city heroes). Manual and legacy rows without a licence show the credit
 * text alone. Cards use imageCreditShort().
 */
function renderImageCredit(array $img, $class = 'img-credit') {
    if (empty($img['url']) || (empty($img['credit']) && empty($img['license']))) return;
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    if (($img['source'] ?? '') === 'unsplash') {   // Unsplash's required form: photographer and Unsplash, each linked, with utm_source
        [$who, $user] = array_pad(explode('|', (string) ($img['credit'] ?? ''), 2), 2, '');
        if (trim($who) !== '' && $user !== '') {
            echo '<div class="' . $h($class) . '">Photo by <a href="' . $h('https://unsplash.com/@' . rawurlencode($user) . '?' . IMAGE_UNSPLASH_UTM) . '" rel="noopener nofollow" target="_blank">' . $h(trim($who)) . '</a> on <a href="' . $h('https://unsplash.com/?' . IMAGE_UNSPLASH_UTM) . '" rel="noopener nofollow" target="_blank">Unsplash</a>. <a href="/image-credits">All photo credits</a></div>';
            return;
        }
    }
    $credit  = trim((string) ($img['credit'] ?? ''));
    $license = trim((string) ($img['license'] ?? ''));
    $licUrl  = imageLicenseUrl($license);
    if (mb_strlen($credit) > 160) { $credit = rtrim(mb_substr($credit, 0, 157)) . '...'; }
    $html = '';
    if ($license !== '' && $licUrl !== '' && ($pos = stripos($credit, $license)) !== false) {
        // The stored credit already names the licence ("Author / Wikimedia Commons, CC BY-SA 4.0"): link that part.
        $html = $h(substr($credit, 0, $pos)) . '<a href="' . $h($licUrl) . '" rel="license noopener nofollow" target="_blank">' . $h(substr($credit, $pos, strlen($license))) . '</a>' . $h(substr($credit, $pos + strlen($license)));
    } else {
        $html = $h($credit);
        if ($license !== '') {
            $html .= ($html !== '' ? ', ' : '') . ($licUrl !== '' ? '<a href="' . $h($licUrl) . '" rel="license noopener nofollow" target="_blank">' . $h($license) . '</a>' : $h($license));
        }
    }
    $src = trim((string) ($img['source_url'] ?? ''));
    if ($src !== '' && preg_match('#^https?://#i', $src)) {
        $html .= ' <a href="' . $h($src) . '" rel="noopener nofollow" target="_blank">Source</a>';
    }
    $changed = ($license !== '' && $licUrl !== '') ? ' Cropped and resized.' : '';
    echo '<div class="' . $h($class) . '">Photo: ' . $html . '.' . $changed . ' <a href="/image-credits">All photo credits</a></div>';
}

/** Short text for a card caption / tooltip: "Photo: CC BY-SA 4.0". Cards link to /image-credits for the full notice. */
function imageCreditShort(array $img) {
    if (($img['source'] ?? '') === 'unsplash') return 'Photo: Unsplash';
    $license = trim((string) ($img['license'] ?? ''));
    if ($license !== '') return 'Photo: ' . $license;
    $credit = trim((string) ($img['credit'] ?? ''));
    return $credit !== '' ? 'Photo: ' . mb_substr($credit, 0, 60) : '';
}


/**
 * Numbers for the admin Images screen and dashboard: rows by status, the age of the oldest queued row (a growing age means
 * the cron / background worker is not keeping up), the share of resolved rows, and how many "ok" rows are legacy objects
 * (stored under the old slug key, resolved before licences were recorded).
 */
function imageQueueStats($mysqli = MYSQLI) {
    $out = ['ok' => 0, 'manual' => 0, 'fallback' => 0, 'pending' => 0, 'total' => 0, 'oldest_pending_age' => null, 'legacy_ok' => 0, 'resolved_pct' => 0];
    $res = $mysqli->query("SELECT status, COUNT(*) AS c FROM images WHERE entity_type IS NOT NULL GROUP BY status");
    while ($res && ($r = $res->fetch_assoc())) { $out[$r['status']] = (int) $r['c']; $out['total'] += (int) $r['c']; }
    $res = $mysqli->query("SELECT TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) AS age FROM images WHERE entity_type IS NOT NULL AND status = 'pending'");
    $row = $res ? $res->fetch_assoc() : null;
    if ($row && $row['age'] !== null) $out['oldest_pending_age'] = (int) $row['age'];
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM images WHERE entity_type IS NOT NULL AND status = 'ok' AND store_key IS NULL AND (source IS NULL OR source <> 'admin')");
    $row = $res ? $res->fetch_assoc() : null;
    $out['legacy_ok'] = (int) ($row['c'] ?? 0);
    $denom = $out['ok'] + $out['manual'] + $out['fallback'] + $out['pending'];
    $out['resolved_pct'] = $denom > 0 ? (int) round(100 * ($out['ok'] + $out['manual']) / $denom) : 0;
    return $out;
}

/** "3 days", "5 hours", "12 minutes" for an age in seconds. */
function imageHumanAge($seconds) {
    $seconds = (int) $seconds;
    if ($seconds >= 172800) return floor($seconds / 86400) . ' days';
    if ($seconds >= 7200) return floor($seconds / 3600) . ' hours';
    return max(1, (int) floor($seconds / 60)) . ' minutes';
}

/* ------------------------------------------------------------------ the queue worker */

/**
 * Work the image queue: pending rows first, then fallbacks whose retry time has passed, oldest first. Stops early when a
 * source rate-limits us, or when the deadline (unix time) is reached. Shared by cron/resolve-images.php and the
 * self-scheduling web worker below.
 *
 * @return array ['processed' => n, 'resolved' => n, 'miss' => n, 'rateLimited' => n]
 */
function imageWorkQueue($batch, $pauseMicros = 1500000, $deadline = null, $mysqli = MYSQLI) {
    $out = ['processed' => 0, 'resolved' => 0, 'miss' => 0, 'rateLimited' => 0];
    $res = $mysqli->query("
        SELECT imgkey, entity_type, entity_name, url, status
          FROM images
         WHERE entity_type IS NOT NULL AND entity_name IS NOT NULL
           AND (status = 'pending' OR (status = 'fallback' AND (expires_at IS NULL OR expires_at <= NOW())))
         ORDER BY (status = 'pending') DESC, ID ASC
         LIMIT " . (int) $batch);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    foreach ($rows as $i => $row) {
        if ($deadline !== null && time() >= $deadline) break;
        if ($i > 0) usleep($pauseMicros);
        $out['processed']++;
        if (resolveEntityImage($row['entity_type'], $row['entity_name'], [], $row['url'] ?: imageFallbackUrl($row['entity_type']))) { $out['resolved']++; continue; }
        $after = imageRecordGet($row['imgkey']);
        // A short expiry means the chain saw a 429; stop hammering for this run.
        if ($after && !empty($after['expires_at']) && strtotime($after['expires_at']) - time() < IMAGE_ERROR_TTL_MINUTES * 60 + 60) {
            $out['rateLimited']++;
            if ($out['rateLimited'] >= 3) break;
        }
        $out['miss']++;
    }
    return $out;
}

/**
 * Self-scheduling worker, so pictures keep resolving even if nobody has set up the cron job.
 *
 * Runs as a shutdown function on public page requests, AFTER the page has been sent to the visitor (fastcgi_finish_request;
 * without it, e.g. the PHP built-in server, it does nothing so no visitor is ever kept waiting). At most one run every
 * IMAGE_WORKER_INTERVAL seconds across all requests (a stamp file, plus a lock so two requests never overlap), and only when
 * the queue has work. The scheduled cron job stays the better option; this is the safety net. Turn it off with the
 * environment variable IMAGE_WEB_WORKER=0.
 */
const IMAGE_WORKER_INTERVAL = 60;    // seconds between runs (it only runs when something is queued)
const IMAGE_WORKER_BATCH    = 15;
const IMAGE_WORKER_BUDGET   = 40;    // seconds of work after the response has been sent

function imageWorkerMaybeRun($forTest = false) {
    if (!$forTest) {   // $forTest skips only the environment checks, so the scheduling rules below can be tested from the command line
        if (PHP_SAPI === 'cli' || getenv('IMAGE_WEB_WORKER') === '0' || !function_exists('fastcgi_finish_request')) return;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    }
    $dir = __DIR__ . '/../cache';
    if (!is_dir($dir) || !is_writable($dir)) return;
    $stamp = $dir . '/image_worker.stamp';
    if (is_file($stamp) && time() - (int) @filemtime($stamp) < IMAGE_WORKER_INTERVAL) return;
    $lock = @fopen($dir . '/image_worker.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return;
    clearstatcache(true, $stamp);
    if (is_file($stamp) && time() - (int) @filemtime($stamp) < IMAGE_WORKER_INTERVAL) { flock($lock, LOCK_UN); return; }
    @touch($stamp);   // even when the queue is empty: look again in a few minutes, not on every request
    try {
        $has = MYSQLI->query("SELECT 1 FROM images WHERE entity_type IS NOT NULL AND entity_name IS NOT NULL AND (status = 'pending' OR (status = 'fallback' AND (expires_at IS NULL OR expires_at <= NOW()))) LIMIT 1");
        if (!$has || !$has->fetch_row()) { flock($lock, LOCK_UN); return; }
        ignore_user_abort(true);
        @set_time_limit(IMAGE_WORKER_BUDGET + 20);
        fastcgi_finish_request();   // the visitor already has the whole page; everything below is background work
        $t0 = microtime(true);
        $stats = imageWorkQueue(IMAGE_WORKER_BATCH, 1200000, time() + IMAGE_WORKER_BUDGET);
        // Shown by /ajax/health.php: how the last run went (so a slow or rate-limited queue is visible without server access).
        @file_put_contents($dir . '/image_worker_last.json', json_encode($stats + ['at' => time(), 'seconds' => round(microtime(true) - $t0, 1)]));
    } catch (Throwable $e) {
        error_log('image worker: ' . $e->getMessage());
    }
    flock($lock, LOCK_UN);
}
