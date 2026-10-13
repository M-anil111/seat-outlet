<?php
/**
 * The filter shapes a listing request may send to TicketNetwork. Kept apart from ajax/load-more-events.php so
 * tools/test-listing-filter.php can check every shape the site builds without running the endpoint.
 */

// Every clause of a listing filter must be one of these shapes. They are the forms the site generates (listing, search, city, venue,
// state, country, category, performer, date and price). Values are fixed-format: dates, ids, dotted category paths, two-letter codes.
const LOAD_MORE_FILTER_CLAUSES = [
    '#^date/date (ge|le) \d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$#',
    '#^pricingInfo/lowPrice/value (le|ge) \d+(\.\d+)?$#',
    '#^_metadata/hasTickets eq true$#',
    '#^(country/alphaCode|stateProvince/alphaCode|defaultCategory/parentCategory/path) eq \'[A-Za-z.0-9]{1,20}\'$#',
    '#^\((country/alphaCode eq \'[A-Z]{2}\'( or country/alphaCode eq \'[A-Z]{2}\')*)\)$#',
    '#^[a-zA-Z/]+/id eq \d+$#',
    '#^id eq \d+$#',
    '#^id in \(\d+(,\d+)*\)$#',
    '#^(defaultCategory/path|path) eq \'\.?[0-9.]{1,80}\'$#',
    '#^contains\((defaultCategory/path|path), ?\'\.?[0-9.]{1,80}\.?\'\)$#',
    '#^startswith\(defaultCategory/path, ?\'\.?[0-9.]{1,80}\.?\'\)$#',
    '#^depth eq \d{1,2}$#',
];
const LOAD_MORE_GEO_FILTER = '#^nearby\(-?\d{1,3}(\.\d+)?,-?\d{1,3}(\.\d+)?,\d{1,4}mi\)$#';
const LOAD_MORE_PERFORMER_FILTER = '#^id eq \d+$#';

/** True when every "and" clause of a listing filter is one of the known shapes, and the whole filter is short enough. */
function soLoadMoreFilterOk(string $filter): bool {
    if ($filter === '' || strlen($filter) > 700) return false;
    foreach (explode(' and ', $filter) as $clause) {
        $clause = trim($clause);
        $known = false;
        foreach (LOAD_MORE_FILTER_CLAUSES as $re) {
            if (preg_match($re, $clause)) { $known = true; break; }
        }
        if (!$known) return false;
    }
    return true;
}
