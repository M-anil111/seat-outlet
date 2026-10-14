<?php
/**
 * Pure helpers for the bulk event importer (cron/tn-bulk-import.php): what to ask /catalog/v2/events/bulk for, how one returned event
 * maps to a tn_bulk_events row (migration 0050), and the decisions about which run to start and when old rows may be deleted. No
 * network and no database in here, so tools/test-tn-bulk.php loads it on its own.
 *
 * The endpoint rules come from TicketNetwork's Catalog API Starter Documentation and the Catalog API Swagger (docs/ticketnetwork-api.md,
 * section 9): sort by createdAt then id, one request at a time, a full refresh about once a day, then only what changed since the last
 * run (metadataInclusiveUpdatedAt, because we keep _metadata). The field shapes below follow the Swagger model; they have not been
 * checked against a live response, because the bulk role is not granted yet.
 */

/** Fields we ask for (names from the Swagger "Fields" list); everything else is left out to keep each page small. */
const TN_BULK_FIELDS = [
    'id', 'text/name', 'date/datetime', 'date/datetimeOffset', 'takedownAt', 'isTest', 'scheduleStatus',
    'geoLocation/latitude', 'geoLocation/longitude',
    '_metadata/ticketCount', '_metadata/hasTickets',
    'country/alphaCode', 'stateProvince/id', 'stateProvince/text/abbr', 'city/id', 'city/text/name',
    'venue/id', 'venue/text/name', 'defaultCategory/path',
    'performers/id', 'performers/name', 'performers/isPerformance',
    'pricingInfo/lowPrice/value', 'pricingInfo/lowPrice/currencyCode', 'pricingInfo/averagePrice/value', 'pricingInfo/highPrice/value',
    'uriComponent', 'createdAt', 'updatedAt', 'metadataInclusiveUpdatedAt', 'version',
];

/** Columns of tn_bulk_events in insert order (seen_at is set by the database). */
const TN_BULK_COLUMNS = [
    'event_id', 'name', 'starts_local', 'starts_offset', 'takedown_at', 'is_test', 'schedule_status', 'venue_id', 'venue_name',
    'city_id', 'city_name', 'state_id', 'state_abbr', 'country_code', 'category_path', 'latitude', 'longitude', 'ticket_count',
    'has_tickets', 'low_price', 'avg_price', 'high_price', 'currency', 'uri_component', 'performers', 'tn_created_at',
    'tn_updated_at', 'tn_meta_updated_at', 'tn_version',
];

/** Off by default: the endpoint needs a role TicketNetwork grants on request, and nothing reads the table yet. */
function tnBulkEnabled(): bool {
    $v = strtolower(trim((string) getenv('TN_BULK_ENABLED')));
    return in_array($v, ['1', 'true', 'on', 'yes'], true);
}

/** Events per request. TicketNetwork's guide says up to 10,000 (about 20 s); the Swagger pager text says 200. 5000 unless overridden. */
function tnBulkPerPage(): int {
    $v = getenv('TN_BULK_PER_PAGE');
    $n = ($v === false || $v === '' || !ctype_digit((string) $v)) ? 5000 : (int) $v;
    return max(100, min(10000, $n));
}

/** "2026-10-14T15:33:00Z" for the changes filter: the last successful start, minus an overlap so a slow update is not missed. */
function tnBulkSinceIso(int $timestamp, int $overlapSeconds = 600): string {
    return gmdate('Y-m-d\TH:i:s\Z', max(0, $timestamp - $overlapSeconds));
}

/** Query parameters for one page of /events/bulk. $sinceIso null = every event (full refresh). */
function tnBulkParams(int $page, int $perPage, ?string $sinceIso = null): array {
    $p = ['fields' => implode(',', TN_BULK_FIELDS), 'sort' => 'createdAt,id', 'page' => max(1, $page), 'perPage' => $perPage];
    if ($sinceIso !== null && $sinceIso !== '') $p['filter'] = 'metadataInclusiveUpdatedAt ge ' . $sinceIso;
    return $p;
}

/** A timestamp as UTC "Y-m-d H:i:s", or null when it is empty or unreadable. */
function tnBulkUtc($value): ?string {
    if (!is_string($value) || trim($value) === '') return null;
    try {
        return (new DateTimeImmutable(trim($value), new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        return null;
    }
}

/** The venue-local date and time as written ("2026-10-20T19:30:00" or with an offset), without any conversion. */
function tnBulkLocal($value): ?string {
    if (!is_string($value) || !preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2}:\d{2})/', trim($value), $m)) return null;
    return $m[1] . ' ' . $m[2];
}

function tnBulkText($value, int $max): ?string {
    if (!is_string($value) && !is_numeric($value)) return null;
    $s = trim((string) $value);
    return $s === '' ? null : mb_substr($s, 0, $max);
}

function tnBulkInt($value): ?int {
    return (is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value >= 0 ? (int) $value : null;
}

function tnBulkPrice($value): ?string {
    return is_numeric($value) && (float) $value >= 0 && (float) $value < 100000000 ? number_format((float) $value, 2, '.', '') : null;
}

function tnBulkCoord($value, float $limit): ?string {
    return is_numeric($value) && abs((float) $value) <= $limit ? number_format((float) $value, 6, '.', '') : null;
}

/**
 * One /events/bulk result as a row keyed by TN_BULK_COLUMNS, or null when it has no usable id.
 * @return array<string,string|int|null>|null
 */
function tnBulkMapEvent($e): ?array {
    if (!is_array($e)) return null;
    $id = tnBulkInt($e['id'] ?? null);
    if ($id === null || $id < 1) return null;
    $performers = [];
    foreach ((array) ($e['performers'] ?? []) as $p) {
        $pid = is_array($p) ? tnBulkInt($p['id'] ?? null) : null;
        if ($pid === null) continue;
        $performers[] = ['id' => $pid, 'name' => tnBulkText($p['name'] ?? null, 255), 'isPerformance' => !empty($p['isPerformance'])];
    }
    $meta = (array) ($e['_metadata'] ?? []);
    $price = (array) ($e['pricingInfo'] ?? []);
    $json = $performers ? json_encode($performers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    return [
        'event_id'           => $id,
        'name'               => (string) tnBulkText($e['text']['name'] ?? null, 255),
        'starts_local'       => tnBulkLocal($e['date']['datetime'] ?? null),
        'starts_offset'      => tnBulkText($e['date']['datetimeOffset'] ?? null, 40),
        'takedown_at'        => tnBulkUtc($e['takedownAt'] ?? null),
        'is_test'            => !empty($e['isTest']) ? 1 : 0,
        'schedule_status'    => tnBulkText($e['scheduleStatus'] ?? null, 40),
        'venue_id'           => tnBulkInt($e['venue']['id'] ?? null),
        'venue_name'         => tnBulkText($e['venue']['text']['name'] ?? null, 255),
        'city_id'            => tnBulkInt($e['city']['id'] ?? null),
        'city_name'          => tnBulkText($e['city']['text']['name'] ?? null, 120),
        'state_id'           => tnBulkInt($e['stateProvince']['id'] ?? null),
        'state_abbr'         => tnBulkText($e['stateProvince']['text']['abbr'] ?? null, 10),
        'country_code'       => tnBulkText($e['country']['alphaCode'] ?? null, 8),
        'category_path'      => tnBulkText($e['defaultCategory']['path'] ?? null, 255),
        'latitude'           => tnBulkCoord($e['geoLocation']['latitude'] ?? null, 90.0),
        'longitude'          => tnBulkCoord($e['geoLocation']['longitude'] ?? null, 180.0),
        'ticket_count'       => tnBulkInt($meta['ticketCount'] ?? null),
        'has_tickets'        => !empty($meta['hasTickets']) ? 1 : 0,
        'low_price'          => tnBulkPrice($price['lowPrice']['value'] ?? null),
        'avg_price'          => tnBulkPrice($price['averagePrice']['value'] ?? null),
        'high_price'         => tnBulkPrice($price['highPrice']['value'] ?? null),
        'currency'           => tnBulkText($price['lowPrice']['currencyCode'] ?? null, 8),
        'uri_component'      => tnBulkText($e['uriComponent'] ?? null, 255),
        'performers'         => $json === false ? null : $json,
        'tn_created_at'      => tnBulkUtc($e['createdAt'] ?? null),
        'tn_updated_at'      => tnBulkUtc($e['updatedAt'] ?? null),
        'tn_meta_updated_at' => tnBulkUtc($e['metadataInclusiveUpdatedAt'] ?? null),
        'tn_version'         => tnBulkInt($e['version'] ?? null),
    ];
}

/** The insert-or-update statement for one row (positional placeholders in TN_BULK_COLUMNS order). */
function tnBulkUpsertSql(): string {
    $cols = TN_BULK_COLUMNS;
    $set = [];
    foreach ($cols as $c) { if ($c !== 'event_id') $set[] = "`$c` = VALUES(`$c`)"; }
    return 'INSERT INTO tn_bulk_events (`' . implode('`, `', $cols) . '`, `seen_at`) VALUES (' . implode(', ', array_fill(0, count($cols), '?'))
        . ', ?) ON DUPLICATE KEY UPDATE ' . implode(', ', $set) . ', `seen_at` = VALUES(`seen_at`)';
}

/**
 * Which run an "auto" call starts: a full refresh when there has never been one, or when the last good one is over a day old and it is
 * the quiet window (07:00 to 10:59 UTC, 3 to 6 am US Eastern); otherwise the hourly changes pull.
 */
function tnBulkChooseMode(?int $lastFullOk, int $now): string {
    if ($lastFullOk === null || $lastFullOk <= 0) return 'full';
    $hour = (int) gmdate('G', $now);
    return ($now - $lastFullOk >= 86400 && $hour >= 7 && $hour <= 10) ? 'full' : 'incremental';
}

/**
 * Old rows (not seen in a finished full refresh) may be deleted only when the run really reached the end of the catalog and returned
 * a believable amount: a run that stopped early, or came back with under half of the last full count, must never empty the table.
 */
function tnBulkMayDelete(bool $reachedEnd, int $rowsSeen, ?int $previousFullCount): bool {
    if (!$reachedEnd || $rowsSeen < 1) return false;
    return $previousFullCount === null || $previousFullCount < 1 || $rowsSeen >= (int) floor($previousFullCount / 2);
}
