<?php
/**
 * Read side of the bulk event catalog (tn_bulk_events, filled by cron/tn-bulk-import.php). OFF unless TN_BULK_ENABLED=1, and then only
 * while the last full refresh is recent: a missing or stale copy is never used. What it is for:
 *   - the fallback for event LISTINGS and SEARCH when the live call cannot be made (outage, throttle, open circuit, our own call
 *     budget): tnBulkFallback() answers a /catalog/v2/events list from the table instead of an empty page;
 *   - the sitemap: which category pages have events at all (tnBulkCategoryCounts());
 *   - removed-event redirects: soEventRedirectLookup() (inc/event-redirects.php) reads the performer and category from it.
 * It never answers: event detail pages, ticket groups, seat maps, prices at checkout. Those stay live (a bulk price is the catalog's
 * low price at the time of the import, not a quote).
 *
 * The filter a listing sends is parsed here; any clause that is not understood means "no answer from the bulk copy", never a guess.
 * The bulk field shapes follow the Swagger model and have NOT been checked against a live bulk response (the role is not granted yet):
 * run tools/test-tn-bulk-read.php against a real import before turning TN_BULK_ENABLED on.
 */

const TN_BULK_MAX_AGE_SECONDS = 129600;   // the last full refresh may be at most 36 hours old (it is meant to run daily)

/** True when the bulk copy may be read at all (the feature switch; freshness is checked separately). */
function tnBulkReadEnabled(): bool {
    return tnBulkEnabled() && strtolower(trim((string) getenv('TN_BULK_READ'))) !== '0';
}

/** Pure: is the copy fresh enough to read, from the importer's state rows (name => value) and the clock? */
function tnBulkFresh(array $state, int $now, int $maxAge = TN_BULK_MAX_AGE_SECONDS): bool {
    $ok = (int) ($state['last_full_ok'] ?? 0);
    $n = (int) ($state['last_full_count'] ?? 0);
    return $ok > 0 && $n > 0 && $now - $ok <= $maxAge && $now >= $ok - 300;
}

/** The importer's state rows, read once per request. [] when the table cannot be read. */
function tnBulkState(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    if (!defined('MYSQLI') || !(MYSQLI instanceof mysqli)) return $cache;
    try {
        $res = MYSQLI->query('SELECT name, value FROM tn_bulk_state');
        while ($res && ($r = $res->fetch_row())) $cache[$r[0]] = $r[1];
    } catch (\Throwable $e) { $cache = []; }
    return $cache;
}

/** Feature switch on AND a fresh full refresh. */
function tnBulkReady(): bool {
    static $ready = null;
    if ($ready === null) $ready = tnBulkReadEnabled() && tnBulkFresh(tnBulkState(), time());
    return $ready;
}

/**
 * Parses a listing request into conditions the bulk table can answer, or null when any part is not understood.
 * Understood clauses, joined by " and ": city/id, stateProvince/id, venue/id eq N; country/alphaCode eq 'XX';
 * startswith(defaultCategory/path, '...'); date/date ge|le YYYY-MM-DD; _metadata/hasTickets eq true; pricingInfo/lowPrice/value le N;
 * and performerFilter "id eq N". Sorts: date/date, pricingInfo/lowPrice/value, -salesRank (ticket count stands in for popularity).
 * @return array|null ['where' => [sql...], 'types' => 'is..', 'values' => [...], 'order' => sql, 'perPage' => n, 'page' => n]
 */
function tnBulkParseListing(array $params): ?array {
    $filter = trim((string) ($params['filter'] ?? ''));
    if ($filter === '') return null;
    $where = []; $types = ''; $values = [];
    $add = function (string $sql, string $t, $v) use (&$where, &$types, &$values) { $where[] = $sql; if ($t !== '') { $types .= $t; $values[] = $v; } };
    // split on " and " outside quotes (a category path or country never contains the word, but stay strict)
    $clauses = preg_split('/\s+and\s+(?=(?:[^\']*\'[^\']*\')*[^\']*$)/i', $filter);
    foreach ($clauses as $c) {
        $c = trim($c);
        if (preg_match('#^city/id eq (\d{1,10})$#', $c, $m)) $add('city_id = ?', 'i', (int) $m[1]);
        elseif (preg_match('#^stateProvince/id eq (\d{1,10})$#', $c, $m)) $add('state_id = ?', 'i', (int) $m[1]);
        elseif (preg_match('#^venue/id eq (\d{1,10})$#', $c, $m)) $add('venue_id = ?', 'i', (int) $m[1]);
        elseif (preg_match("#^country/alphaCode eq '([A-Za-z]{2,3})'$#", $c, $m)) $add('country_code = ?', 's', strtoupper($m[1]));
        elseif (preg_match("#^startswith\\(defaultCategory/path, '(\\.[0-9.]*)'\\)$#", $c, $m)) $add('category_path LIKE ?', 's', $m[1] . '%');
        elseif (preg_match('#^date/date ge (\d{4}-\d{2}-\d{2})$#', $c, $m)) $add('starts_local >= ?', 's', $m[1] . ' 00:00:00');
        elseif (preg_match('#^date/date le (\d{4}-\d{2}-\d{2})$#', $c, $m)) $add('starts_local <= ?', 's', $m[1] . ' 23:59:59');
        elseif ($c === '_metadata/hasTickets eq true') $add('has_tickets = 1', '', null);
        elseif (preg_match('#^pricingInfo/lowPrice/value le (\d{1,7})$#', $c, $m)) $add('low_price <= ?', 'i', (int) $m[1]);
        else return null;
    }
    $pf = trim((string) ($params['performerFilter'] ?? ''));
    if ($pf !== '') {
        if (!preg_match('#^id eq (\d{1,10})$#', $pf, $m)) return null;
        $add('performers LIKE ?', 's', '%{"id":' . (int) $m[1] . ',%');
    }
    $sort = (string) ($params['sort'] ?? 'date/date');
    $order = ['date/date' => 'starts_local ASC, event_id ASC', 'pricingInfo/lowPrice/value' => 'low_price IS NULL, low_price ASC, event_id ASC', '-salesRank' => 'ticket_count DESC, starts_local ASC, event_id ASC'][$sort] ?? null;
    if ($order === null) return null;
    $perPage = max(1, min(200, (int) ($params['perPage'] ?? 20)));
    $page = max(1, (int) ($params['page'] ?? 1));
    return ['where' => $where, 'types' => $types, 'values' => $values, 'order' => $order, 'perPage' => $perPage, 'page' => $page];
}

/** One tn_bulk_events row as the API-shaped event the listing pages read (only what the bulk import holds). */
function tnBulkRowToEvent(array $r): array {
    $starts = (string) ($r['starts_local'] ?? '');
    $iso = $starts !== '' ? str_replace(' ', 'T', $starts) : '';
    $perf = json_decode((string) ($r['performers'] ?? ''), true);
    $low = $r['low_price'] ?? null;
    $e = [
        'id' => (int) $r['event_id'],
        'text' => ['name' => (string) ($r['name'] ?? '')],
        'date' => ['date' => $iso, 'datetime' => $iso, 'datetimeOffset' => (string) ($r['starts_offset'] ?? ''), 'time' => $starts !== '' ? substr($starts, 11, 8) : ''],
        'venue' => ['id' => (int) ($r['venue_id'] ?? 0), 'text' => ['name' => (string) ($r['venue_name'] ?? '')]],
        'city' => ['id' => (int) ($r['city_id'] ?? 0), 'text' => ['name' => (string) ($r['city_name'] ?? '')]],
        'stateProvince' => ['id' => (int) ($r['state_id'] ?? 0), 'text' => ['abbr' => (string) ($r['state_abbr'] ?? '')]],
        'country' => ['alphaCode' => (string) ($r['country_code'] ?? '')],
        'defaultCategory' => ['path' => (string) ($r['category_path'] ?? '')],
        'performers' => is_array($perf) ? $perf : [],
        'pricingInfo' => ['lowPrice' => ['value' => $low !== null ? (float) $low : null, 'text' => ['formatted' => $low !== null ? '$' . number_format((float) $low, 0) : '']],
                          'averagePrice' => ['value' => isset($r['avg_price']) ? (float) $r['avg_price'] : null], 'highPrice' => ['value' => isset($r['high_price']) ? (float) $r['high_price'] : null]],
        '_metadata' => ['ticketCount' => (int) ($r['ticket_count'] ?? 0), 'hasTickets' => !empty($r['has_tickets'])],
        'uriComponent' => (string) ($r['uri_component'] ?? ''),
        '_bulk' => true,
    ];
    if (!empty($r['latitude']) || !empty($r['longitude'])) $e['geoLocation'] = ['latitude' => (float) $r['latitude'], 'longitude' => (float) $r['longitude']];
    return $e;
}

/** Runs a parsed listing against the table: ['results' => events, 'totalCount' => n]. Null when the table cannot be read. */
function tnBulkRunListing(mysqli $db, array $q, ?string $nowUtc = null): ?array {
    $where = $q['where'];
    $where[] = 'is_test = 0';
    $where[] = '(takedown_at IS NULL OR takedown_at > ?)';
    $types = $q['types'] . 's';
    $values = $q['values'];
    $values[] = $nowUtc ?? gmdate('Y-m-d H:i:s');
    $sqlWhere = ' WHERE ' . implode(' AND ', $where);
    try {
        $c = $db->prepare('SELECT COUNT(*) FROM tn_bulk_events' . $sqlWhere);
        if (!$c) return null;
        $c->bind_param($types, ...$values);
        $c->execute();
        $total = (int) $c->get_result()->fetch_row()[0];
        $c->close();
        $off = ($q['page'] - 1) * $q['perPage'];
        $s = $db->prepare('SELECT * FROM tn_bulk_events' . $sqlWhere . ' ORDER BY ' . $q['order'] . ' LIMIT ' . (int) $q['perPage'] . ' OFFSET ' . (int) $off);
        if (!$s) return null;
        $s->bind_param($types, ...$values);
        $s->execute();
        $rows = [];
        $res = $s->get_result();
        while ($r = $res->fetch_assoc()) $rows[] = tnBulkRowToEvent($r);
        $s->close();
    } catch (\Throwable $e) {
        return null;
    }
    return ['results' => $rows, 'totalCount' => $total, '_bulk' => true];
}

/**
 * The read-through used by the live request layer when a call cannot be made. Only /catalog/v2/events LIST requests (not one event) and
 * only when the bulk copy is ready and the whole request is understood. Returns an API-shaped answer, or null (the caller then reports
 * the failure as before). A page served from here is counted ('bulk_served') so the health page shows how often it happens.
 */
function tnBulkFallback($endpoint, array $params): ?array {
    if (!tnBulkReady() || !defined('MYSQLI') || !(MYSQLI instanceof mysqli)) return null;
    if (rtrim((string) $endpoint, '/') !== '/catalog/v2/events') return null;
    $q = tnBulkParseListing($params);
    if ($q === null) return null;
    $r = tnBulkRunListing(MYSQLI, $q);
    if ($r === null) return null;
    if (function_exists('tnCount')) tnCount('bulk_served');
    $GLOBALS['so_bulk_served'] = true;
    return $r;
}

/** Events per category id from the bulk copy (an event counts toward every category in its path): [id => count], or null when not ready. */
function tnBulkCategoryCounts(?mysqli $db = null, ?string $nowUtc = null): ?array {
    $db = $db ?? (defined('MYSQLI') && MYSQLI instanceof mysqli ? MYSQLI : null);
    if (!$db) return null;
    try {
        $st = $db->prepare("SELECT category_path, COUNT(*) FROM tn_bulk_events WHERE is_test = 0 AND has_tickets = 1 AND starts_local >= ? AND (takedown_at IS NULL OR takedown_at > ?) GROUP BY category_path");
        if (!$st) return null;
        $today = ($nowUtc ? substr($nowUtc, 0, 10) : date('Y-m-d')) . ' 00:00:00';
        $nu = $nowUtc ?? gmdate('Y-m-d H:i:s');
        $st->bind_param('ss', $today, $nu);
        $st->execute();
        $res = $st->get_result();
        $out = [];
        while ($r = $res->fetch_row()) {
            foreach (array_filter(explode('.', (string) $r[0]), 'strlen') as $cid) $out[(int) $cid] = ($out[(int) $cid] ?? 0) + (int) $r[1];
        }
        $st->close();
        return $out;
    } catch (\Throwable $e) {
        return null;
    }
}
