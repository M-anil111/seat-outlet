<?php
/**
 * Helpers for the TicketNetwork event copies and for reading visitor query strings. Kept apart from functions.php so
 * tools/test-tn-listing-copy.php can load them without the rest of the site.
 */

/** How long a saved listing copy of an event may answer for it when its detail call fails (14 days, as the stale-on-error cache). */
const TN_LISTING_COPY_TTL = 1209600;

/**
 * parse_str() for a visitor's query string. A nested-bracket flood (a[a][a]...) runs into PHP's nesting limit and logs a warning
 * (Sentry SEAT-OUTLET-Y), so an unreasonable string is ignored instead of parsed.
 */
function soSafeParseQuery($query): array {
    $query = (string) $query;
    if ($query === '' || strlen($query) > 2000 || substr_count($query, '[') > 16) return [];
    $out = [];
    parse_str($query, $out);
    return is_array($out) ? $out : [];
}

/** The event rows in a TicketNetwork list answer: {"events": [...]} or a plain list. Anything else is no rows. */
function tnListRows($data): array {
    if (!is_array($data)) return [];
    if (isset($data['events']) && is_array($data['events'])) return $data['events'];
    return array_is_list($data) ? $data : [];
}

/** True when a listing row has what the event page renders: an id, a name, a venue, a city and a date. */
function tnListingCopyComplete($row): bool {
    if (!is_array($row) || (int) ($row['id'] ?? 0) <= 0) return false;
    return trim((string) ($row['text']['name'] ?? '')) !== ''
        && trim((string) ($row['venue']['text']['name'] ?? '')) !== ''
        && trim((string) ($row['city']['text']['name'] ?? '')) !== ''
        && trim((string) ($row['date']['date'] ?? '')) !== '';
}
