<?php
/**
 * Public reference facts for performers and venues, from Wikidata.
 *
 * soEntityFacts('Daniel Sloss', 'performer') returns
 *   ['wikidata' => 'https://www.wikidata.org/wiki/Q5218786', 'wikipedia' => 'https://en.wikipedia.org/wiki/Daniel_Sloss',
 *    'website' => 'https://...', 'description' => 'Scottish comedian', 'sameAs' => [...]]
 * or [] when nothing is known yet.
 *
 * The page never waits on Wikidata: a miss is looked up after the response has gone out (the same
 * fastcgi_finish_request pattern tnScheduleRefresh uses) and the answer, found or not, is cached for 30 days.
 * Matching is strict so a page never links to the wrong entity: the English label must equal the name,
 * the description must read like the right kind of thing (a band, a team, an arena), and exactly one result
 * may pass. Anything ambiguous is treated as "not found".
 */

const SO_FACTS_TTL = 2592000;   // 30 days, hits and misses alike
const SO_FACTS_MAX_PER_REQUEST = 2;

/** Description words that mark a Wikidata item as the right kind of entity. */
function soFactsKindWords(string $kind): array {
    if ($kind === 'venue') {
        return ['arena', 'stadium', 'theatre', 'theater', 'venue', 'amphitheatre', 'amphitheater', 'hall', 'center', 'centre',
            'auditorium', 'ballpark', 'field', 'coliseum', 'colosseum', 'dome', 'pavilion', 'casino', 'speedway', 'raceway',
            'opera house', 'concert', 'nightclub', 'music club', 'bowl', 'garden', 'park', 'building', 'fairground', 'racecourse'];
    }
    return ['singer', 'band', 'musician', 'rapper', 'comedian', 'stand-up', 'group', 'duo', 'trio', 'team', 'club', 'franchise',
        'songwriter', 'composer', 'dj', 'disc jockey', 'record producer', 'orchestra', 'musical', 'play', 'opera', 'ballet',
        'circus', 'magician', 'entertainer', 'performer', 'actor', 'actress', 'drag', 'ensemble', 'choir', 'tour', 'show',
        'wrestling', 'wrestler', 'boxer', 'fighter', 'festival', 'artist', 'vocalist', 'guitarist', 'pianist', 'drummer'];
}

/** The name as Wikidata would label it: TicketNetwork's "Toyota Center - TX" becomes "Toyota Center". */
function soFactsSearchName(string $name, string $kind): string {
    $n = trim(preg_replace('/\s+/', ' ', $name));
    if ($kind === 'venue') {
        $n = preg_replace('/(?:\s+[-\x{2013}\x{2014}]\s+|,\s*)[A-Z]{2}$/u', '', $n);   // "Toyota Center - TX" / "Toyota Center, TX"
        $n = preg_replace('/\s*\((?:formerly|fka)[^)]*\)$/i', '', $n);
    }
    return trim($n);
}

function soFactsKey(string $name, string $kind): string {
    return 'wdfacts2_' . $kind . '_' . md5(mb_strtolower(soFactsSearchName($name, $kind)));
}

/** Cached facts for an entity, or [] (and a background lookup is queued) when not known yet. */
function soEntityFacts(string $name, string $kind = 'performer', string $context = ''): array {
    $name = soFactsSearchName($name, $kind);
    if ($name === '' || mb_strlen($name) < 3) return [];
    $key = soFactsKey($name . '|' . mb_strtolower($context), $kind);
    $cached = cache_get($key, SO_FACTS_TTL);
    if (is_array($cached)) return empty($cached['none']) ? $cached : [];
    soFactsSchedule($name, $kind, $key, $context);
    return [];
}

function soFactsSchedule(string $name, string $kind, string $key, string $context = ''): void {
    static $queue = [];
    static $registered = false;
    if (isset($queue[$key]) || count($queue) >= SO_FACTS_MAX_PER_REQUEST) return;
    if (PHP_SAPI === 'cli' && !getenv('SO_FACTS_CLI')) return;   // never from cron/CLI scripts unless asked
    $backoff = cache_get('wdfacts_backoff', 3600);
    if (is_array($backoff) && (int) ($backoff['until'] ?? 0) > time()) return;
    $queue[$key] = [$name, $kind, $context];
    if ($registered) return;
    $registered = true;
    register_shutdown_function(function () use (&$queue) {
        if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
        foreach ($queue as $key => [$name, $kind, $context]) {
            $lock = @fopen(cache_file_path($key) . '.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { if ($lock) fclose($lock); continue; }
            $facts = soFactsLookup($name, $kind, $context);
            if ($facts !== null) cache_set($key, $facts ?: ['none' => 1]);   // null = network error: try again on a later view
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink(cache_file_path($key) . '.lock');
        }
    });
}

/** GET a Wikidata API url as JSON; null on any failure. Wikimedia asks for an identifying User-Agent. */
function soFactsGet(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => 'SeatOutletBot/1.0 (' . HOME_URL . '; support@seatoutlet.com)',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($code === 429 || $code >= 500) cache_set('wdfacts_backoff', ['until' => time() + 3600]);   // rate limited: pause all lookups for an hour
    if ($body === false || $code !== 200) return null;
    $data = json_decode((string) $body, true);
    return is_array($data) ? $data : null;
}

/** Look the entity up. Returns facts, [] when there is no safe match, or null when Wikidata could not be reached. */
function soFactsLookup(string $name, string $kind, string $context = ''): ?array {
    $search = soFactsGet('https://www.wikidata.org/w/api.php?' . http_build_query([
        'action' => 'wbsearchentities', 'search' => $name, 'language' => 'en', 'uselang' => 'en', 'type' => 'item', 'limit' => 10, 'format' => 'json',
    ]));
    if ($search === null) return null;
    $hit = soFactsPick($search, $name, $kind, $context);
    if ($hit === null) return [];
    $qid = (string) $hit['id'];

    $entity = soFactsGet('https://www.wikidata.org/w/api.php?' . http_build_query([
        'action' => 'wbgetentities', 'ids' => $qid, 'props' => 'claims|sitelinks/urls', 'sitefilter' => 'enwiki', 'format' => 'json',
    ]));
    if ($entity === null) return null;
    return soFactsFromEntity($qid, (string) $hit['description'], $entity['entities'][$qid] ?? []);
}

/** The one search result that safely is this entity: exact English label and a description of the right kind. Null when none or ambiguous. */
function soFactsPick(array $search, string $name, string $kind, string $context = ''): ?array {
    $want = mb_strtolower($name);
    $words = soFactsKindWords($kind);
    $hits = [];
    foreach ($search['search'] ?? [] as $r) {
        if (mb_strtolower((string) ($r['label'] ?? '')) !== $want) continue;
        $desc = mb_strtolower((string) ($r['description'] ?? ''));
        if ($desc === '' || strpos($desc, 'disambiguation') !== false) continue;
        foreach ($words as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $desc)) { $hits[] = $r; break; }
        }
    }
    // Several venues share a name (Toyota Center in Houston and in Kennewick): keep the one whose description names the city.
    if (count($hits) > 1 && $context !== '') {
        $hits = array_values(array_filter($hits, fn($r) => mb_stripos((string) $r['description'], $context) !== false));
    }
    if (count($hits) !== 1) return null;   // none, or ambiguous
    return preg_match('/^Q\d+$/', (string) ($hits[0]['id'] ?? '')) ? $hits[0] : null;
}

/** Follow redirects once, here, and return the final address when it answers 2xx; null for an error, a timeout or a dead site. */
function soFactsLiveUrl(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 6, CURLOPT_ENCODING => '',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT => 'SeatOutletBot/1.0 (' . HOME_URL . '; support@seatoutlet.com)',
        CURLOPT_RANGE => '0-0',   // headers are enough; some sites refuse HEAD
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    if (($code < 200 || $code >= 300) || !preg_match('#^https?://[^\s"<>]+$#', $final)) return null;
    return $final;
}

/** Facts from a wbgetentities item: Wikidata URI, English Wikipedia article, official website (P856). */
function soFactsFromEntity(string $qid, string $description, array $e): array {
    $facts = [
        'qid'         => $qid,
        'wikidata'    => 'https://www.wikidata.org/wiki/' . $qid,
        'description' => $description,
    ];
    $wiki = (string) ($e['sitelinks']['enwiki']['url'] ?? '');
    if (strpos($wiki, 'https://en.wikipedia.org/wiki/') === 0) $facts['wikipedia'] = $wiki;
    foreach ($e['claims']['P856'] ?? [] as $claim) {   // P856 = official website
        $site = (string) ($claim['mainsnak']['datavalue']['value'] ?? '');
        if (($claim['rank'] ?? '') === 'deprecated' || !preg_match('#^https?://[^\s"<>]+$#', $site)) continue;
        $live = soFactsLiveUrl($site);   // the address that answers 200 itself, so the page never links a redirect, an error or a dead site
        if ($live !== null) { $facts['website'] = $live; break; }
    }
    $facts['sameAs'] = array_values(array_filter([$facts['wikidata'], $facts['wikipedia'] ?? '', $facts['website'] ?? '']));
    return $facts;
}

/** A small "Sources" line for an artist or venue page: Wikipedia, Wikidata and the official site, all rel=noopener. */
function soFactsSourcesHtml(array $facts, string $name): string {
    if (empty($facts['wikidata'])) return '';
    $esc = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $links = [];
    if (!empty($facts['website'])) $links[] = '<a href="' . $esc($facts['website']) . '" rel="noopener" target="_blank">Official website</a>';
    if (!empty($facts['wikipedia'])) $links[] = '<a href="' . $esc($facts['wikipedia']) . '" rel="noopener" target="_blank">Wikipedia</a>';
    $links[] = '<a href="' . $esc($facts['wikidata']) . '" rel="noopener" target="_blank">Wikidata</a>';
    return '<p class="so-sources"><span class="so-sources__label">About ' . $esc($name) . ':</span> ' . implode('<span aria-hidden="true"> &middot; </span>', $links) . '</p>';
}
