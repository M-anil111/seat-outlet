<?php
/**
 * Helpers for the TicketNetwork event copies and for reading visitor query strings. Kept apart from functions.php so
 * tools/test-tn-listing-copy.php can load them without the rest of the site.
 */

/** Longest a stale answer may stand in for a failed live call: reference data (performers, venues, categories), 14 days. */
const TN_STALE_ON_ERROR = 1209600;

/** Longest a stale event list or event detail may stand in for a failed live call: 2 hours. A dropped event must leave the site within hours. */
const TN_STALE_LISTING = 7200;

/** How old a stored answer may be when a live call fails. Event data is short-lived; reference data is not. */
function tnStaleLimit($endpoint): int {
    $e = trim((string) $endpoint, '/');
    return (strpos($e, 'catalog/v2/events') === 0 || strpos($e, 'events/search') !== false) ? TN_STALE_LISTING : TN_STALE_ON_ERROR;
}

/**
 * How long a saved copy of an event may answer for it when its live call fails: 24 hours. TicketNetwork drops events from its feed,
 * and an event it no longer lists must not be shown from an old copy for weeks. A confirmed "not found" deletes the copies at once.
 */
const TN_LISTING_COPY_TTL = 86400;

/**
 * parse_str() for a visitor's query string. A nested-bracket flood (a[a][a]...) runs into PHP's nesting limit and logs a warning
 * (Sentry SEAT-OUTLET-Y), so an unreasonable string is ignored instead of parsed.
 */
function soSafeParseQuery($query): array {
    $query = (string) $query;
    if ($query === '' || strlen($query) > 2000) return [];
    // Brackets count in parameter names only: a bracket inside a value is just text. Names are decoded first, so an encoded flood is caught too.
    foreach (explode('&', $query) as $pair) {
        if (substr_count(urldecode(explode('=', $pair, 2)[0]), '[') > 16) return [];
    }
    $out = [];
    parse_str($query, $out);
    return is_array($out) ? $out : [];
}

/**
 * The event rows in a TicketNetwork list answer. The live Catalog API returns {"count", "page", "results": [...], "_links", "facets"}
 * (checked against the sandbox, 2026-10-12); "events" and a plain list are accepted too. Anything else is no rows.
 */
function tnListRows($data): array {
    if (!is_array($data)) return [];
    foreach (['results', 'events'] as $key) {
        if (isset($data[$key]) && is_array($data[$key])) return $data[$key];
    }
    return array_is_list($data) ? $data : [];
}

/**
 * True when a list answer came back empty while the stored answer (still inside its stale window) had rows. TicketNetwork answers
 * with an empty list under load now and then; replacing a good list with it would show "0 results" as if it were real. The caller
 * keeps the stored list and tries again at the next refresh. Only event lists and search: a detail answer has no "results" key.
 */
function tnEmptyListSuspect($endpoint, $new, $old): bool {
    if (strpos(trim((string) $endpoint, '/'), 'catalog/v2/events') !== 0) return false;
    if (!is_array($new) || tnListRows($new) !== []) return false;
    // every list shape tnListRows() accepts counts as an empty list; a detail answer (no list key, not a list) does not
    if (!array_key_exists('results', $new) && !array_key_exists('events', $new) && !array_is_list($new)) return false;
    return is_array($old) && tnListRows($old) !== [];
}

/** True when a listing row has what the event page renders: an id, a name, a venue, a city and a date. */
function tnListingCopyComplete($row): bool {
    if (!is_array($row) || (int) ($row['id'] ?? 0) <= 0) return false;
    return is_array($row['text'] ?? null) && is_string($row['text']['name'] ?? null) && trim($row['text']['name']) !== ''
        && is_array($row['venue'] ?? null) && is_array($row['venue']['text'] ?? null) && is_string($row['venue']['text']['name'] ?? null) && trim($row['venue']['text']['name']) !== ''
        && is_array($row['city'] ?? null) && is_array($row['city']['text'] ?? null) && is_string($row['city']['text']['name'] ?? null) && trim($row['city']['text']['name']) !== ''
        && is_array($row['date'] ?? null) && is_string($row['date']['date'] ?? null) && trim($row['date']['date']) !== '';
}
