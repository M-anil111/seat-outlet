<?php
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
|   thesportsdb  team fanart/banner/badge + stadium thumb. Free key "3" is
|                their public test key (30 req/min); set THESPORTSDB_KEY for
|                a paid key. Images are user-contributed; logos are
|                trademarks whichever API supplies them - see the PR notes.
|   openverse    CC0 / CC BY / CC BY-SA photos with the attribution string
|                Openverse builds. Festivals (Wikimedia rarely has them).
|   pexels       optional, only when PEXELS_API_KEY is set. City skylines.
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
    'city'     => ['wikidata', 'pexels'],
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

function imageRecordGet($key, $mysqli = MYSQLI) {
    $stmt = $mysqli->prepare('SELECT * FROM images WHERE imgkey = ? LIMIT 1');
    if (!$stmt) return null;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function imageRecordUpsert(array $r, $mysqli = MYSQLI) {
    $stmt = $mysqli->prepare('
        INSERT INTO images (imgkey, entity_type, entity_name, url, status, source, source_url, license, attribution, attempts, resolved_at, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            entity_type = VALUES(entity_type), entity_name = VALUES(entity_name),
            url = VALUES(url), status = VALUES(status), source = VALUES(source),
            source_url = VALUES(source_url), license = VALUES(license), attribution = VALUES(attribution),
            attempts = VALUES(attempts), resolved_at = VALUES(resolved_at), expires_at = VALUES(expires_at)
    ');
    if (!$stmt) return false;
    $key = $r['imgkey']; $type = $r['entity_type'] ?? null; $name = $r['entity_name'] ?? null;
    $url = (string) ($r['url'] ?? ''); $status = $r['status'] ?? 'ok';
    $source = $r['source'] ?? null; $sourceUrl = $r['source_url'] ?? null;
    $license = $r['license'] ?? null; $attribution = $r['attribution'] ?? null;
    $attempts = (int) ($r['attempts'] ?? 0); $resolvedAt = $r['resolved_at'] ?? null; $expiresAt = $r['expires_at'] ?? null;
    $stmt->bind_param('sssssssssiss', $key, $type, $name, $url, $status, $source, $sourceUrl, $license, $attribution, $attempts, $resolvedAt, $expiresAt);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/** Queue an entity for the cron resolver. No-op if a row already exists. */
function imageQueue($type, $name, $fallbackUrl = '', $mysqli = MYSQLI) {
    $name = trim((string) $name);
    if ($name === '') return false;
    $key = imageEntityKey($type, $name);
    $stmt = $mysqli->prepare('INSERT IGNORE INTO images (imgkey, entity_type, entity_name, url, status) VALUES (?, ?, ?, ?, \'pending\')');
    if (!$stmt) return false;
    $stmt->bind_param('ssss', $key, $type, $name, $fallbackUrl);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
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
    }
    return $out;
}

function imageRowToResult(array $row) {
    return [
        'url'     => (string) $row['url'],
        'credit'  => (string) ($row['attribution'] ?? ''),
        'license' => (string) ($row['license'] ?? ''),
        'source'  => (string) ($row['source'] ?? ''),
        'status'  => (string) ($row['status'] ?? ''),
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
        }
        if ($result === 'RATE_LIMITED') { $rateLimited = true; continue; }
        if (is_array($result) && !empty($result['image_url'])) { $found = $result + ['source' => $source]; break; }
    }

    $storeFailed = false;
    if ($found) {
        $cdnUrl = processAndStoreImage($found['image_url'], $name, imageStorageFolder($type));
        $storeFailed = ($cdnUrl === '');
        if ($cdnUrl) {
            $row = [
                'imgkey' => $key, 'entity_type' => $type, 'entity_name' => $name,
                'url' => $cdnUrl, 'status' => 'ok', 'source' => $found['source'],
                'source_url' => mb_substr((string) ($found['source_url'] ?? ''), 0, 1000),
                'license' => mb_substr((string) ($found['license'] ?? ''), 0, 100),
                'attribution' => mb_substr((string) ($found['attribution'] ?? ''), 0, 500),
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
    $n = preg_replace('/\s+-\s+.*$/', '', $n);
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

function imageSourceWikidata($type, $name, $defaultCategory = []) {
    $query = $type === 'city' ? $name : imageCleanName($name);
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
        if ($type === 'artist' && in_array('Q5', $p31, true) && !$descMatch && !$p106) $typeMatch = false;
        if (!$typeMatch && !$descMatch) continue;
        // Label must at least start with the query for anything but exact hits.
        $label = strtolower((string) ($hit['label'] ?? ''));
        if ($label !== $queryNorm && strpos($label, $queryNorm) !== 0 && strpos($queryNorm, $label) !== 0) continue;

        $file = $ent['claims']['P18'][0]['mainsnak']['datavalue']['value'] ?? '';
        if ($file === '') continue;

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

function imageTheSportsDbKey() {
    $k = getenv('THESPORTSDB_KEY');
    return $k !== false && $k !== '' ? $k : '3';
}

function imageSourceTheSportsDb($name) {
    $data = imageHttpJson('https://www.thesportsdb.com/api/v1/json/' . rawurlencode(imageTheSportsDbKey()) . '/searchteams.php?t=' . rawurlencode(imageCleanName($name)));
    if ($data === 'RATE_LIMITED') return 'RATE_LIMITED';
    $want = strtolower(imageCleanName($name));
    foreach ($data['teams'] ?? [] as $team) {
        if (strtolower((string) ($team['strTeam'] ?? '')) !== $want) continue;
        // Photo-style assets first; the badge/logo is a trademark and only a last resort.
        foreach (['strFanart1', 'strBanner', 'strStadiumThumb', 'strBadge'] as $field) {
            if (!empty($team[$field])) {
                return [
                    'image_url'   => $team[$field],
                    'source_url'  => 'https://www.thesportsdb.com/team/' . ($team['idTeam'] ?? ''),
                    'license'     => 'TheSportsDB (user-contributed)' . ($field === 'strBadge' ? ' - logo' : ''),
                    'attribution' => 'Image: TheSportsDB.com',
                ];
            }
        }
    }
    return null;
}

function imageSourceTheSportsDbVenue($name) {
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

/* --------------------------------------------------------------- render */

/** Small visible credit under an image, for CC BY / BY-SA compliance. */
function renderImageCredit(array $img, $class = 'img-credit') {
    if (empty($img['credit']) || empty($img['url'])) return;
    $credit = htmlspecialchars($img['credit'], ENT_QUOTES, 'UTF-8');
    echo '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . $credit . '</div>';
}
