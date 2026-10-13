<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Offline checks for the TicketNetwork listing copies (event page fallback), the query-string guard and the wiring around them.
 * Run: php tools/test-tn-listing-copy.php
 */
require_once __DIR__ . '/../inc/tn-listing-copy.php';

$fails = 0;
function tlCheck(string $name, bool $ok): void { global $fails; echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; if (!$ok) $fails++; }

// 1. Query strings: normal ones parse, floods and oversized strings are ignored with no warning
$warnings = 0;
set_error_handler(function () use (&$warnings) { $warnings++; return true; });
tlCheck('a normal query string parses', soSafeParseQuery('when=weekend&sort=soonest') === ['when' => 'weekend', 'sort' => 'soonest']);
tlCheck('an empty query string gives nothing', soSafeParseQuery('') === [] && soSafeParseQuery(null) === []);
$flood = str_repeat('a[', 70) . 'b=1';
tlCheck('a bracket flood is ignored', soSafeParseQuery($flood) === []);
tlCheck('a bracket flood logs no PHP warning', $warnings === 0);
tlCheck('an encoded bracket flood (a%5B...) is ignored too, with no PHP warning', soSafeParseQuery(str_repeat('a%5B', 70) . 'b=1') === [] && $warnings === 0);
tlCheck('a bracket inside a value does not hide a normal parameter (page 2 keeps its query)', soSafeParseQuery('page=2&x=' . str_repeat('%5B', 17)) === ['page' => '2', 'x' => str_repeat('[', 17)] && $warnings === 0);
tlCheck('an oversized query string is ignored', soSafeParseQuery('x=' . str_repeat('a', 2001)) === []);
restore_error_handler();

// 2. List answers: both shapes give rows, anything else gives none
$row = ['id' => 42, 'text' => ['name' => 'Staind'], 'venue' => ['text' => ['name' => 'Cynthia Woods Mitchell Pavilion']],
        'city' => ['text' => ['name' => 'The Woodlands']], 'date' => ['date' => '2026-10-17']];
tlCheck('a {"events": [...]} answer gives its rows', tnListRows(['events' => [$row]]) === [$row]);
tlCheck('a plain list answer gives its rows', tnListRows([$row]) === [$row]);
tlCheck('the live Catalog shape {count, results: [...]} gives its rows (checked against the sandbox)', tnListRows(['count' => 1, 'page' => 1, 'results' => [$row], 'facets' => []]) === [$row]);
tlCheck('an error object gives no rows', tnListRows(['Message' => 'throttled']) === []);
tlCheck('a non-array gives no rows', tnListRows(null) === [] && tnListRows('x') === []);

// 3. A copy is used only when the event page can render it
tlCheck('a complete row is a usable copy', tnListingCopyComplete($row));
tlCheck('a row with no venue is not a copy', !tnListingCopyComplete(array_diff_key($row, ['venue' => 1])));
tlCheck('a row with no city is not a copy', !tnListingCopyComplete(array_merge($row, ['city' => []])));
tlCheck('a row with no date is not a copy', !tnListingCopyComplete(array_merge($row, ['date' => ['date' => '']])));
tlCheck('a nested name that is an array is not a copy (fails closed)', !tnListingCopyComplete(array_merge($row, ['text' => ['name' => ['Staind']]])));
tlCheck('a scalar where a nested object belongs is not a copy', !tnListingCopyComplete(array_merge($row, ['venue' => 'Arena'])));
tlCheck('a row with no id is not a copy', !tnListingCopyComplete(array_merge($row, ['id' => 0])));
tlCheck('a copy window of 24 hours (an event TicketNetwork drops leaves within a day)', TN_LISTING_COPY_TTL === 86400);
$fnSrc = file_get_contents(__DIR__ . '/../functions.php');
tlCheck('event lists and details stop standing in for a failed call after 2 hours', TN_STALE_LISTING === 7200);
tlCheck('reference data (performers, venues) may stand in for 14 days', TN_STALE_ON_ERROR === 14 * 86400);
$evSrc = file_get_contents(__DIR__ . '/../event.php');
tlCheck('a copy-served event page drops its prices and says when the copy was saved', strpos($evSrc, "unset(\$event['pricingInfo']);") !== false && strpos($evSrc, 'Event details saved on') !== false);
tlCheck('the copy time is recorded when a copy or snapshot answers', strpos($fnSrc, "\$GLOBALS['so_event_as_of'] = (int) @filemtime") !== false && strpos($fnSrc, "\$GLOBALS['so_event_as_of'] = (int) (\$GLOBALS['so_snapshot_seen']") !== false);
tlCheck('event list endpoint gets the 2-hour limit', tnStaleLimit('/catalog/v2/events/') === 7200);
tlCheck('event detail endpoint gets the 2-hour limit', tnStaleLimit('/catalog/v2/events/8139366') === 7200);
tlCheck('event search endpoint gets the 2-hour limit', tnStaleLimit('/catalog/v2/events/search') === 7200);
tlCheck('performer endpoint gets the 14-day limit', tnStaleLimit('/catalog/v2/performers/1451') === 14 * 86400);
tlCheck('venue endpoint gets the 14-day limit', tnStaleLimit('/catalog/v2/venues/') === 14 * 86400);
// five stale fallbacks plus the empty-answer guard in tnFetchStore all pick the limit by endpoint
tlCheck('no stale check uses the fixed 14-day limit (each picks its limit by endpoint)', strpos($fnSrc, "['age'] <= TN_STALE_ON_ERROR") === false && substr_count($fnSrc, "['age'] <= tnStaleLimit(\$endpoint)") === 6);

// 3b. Empty-answer guard: an empty list never replaces a stored list that still has rows (a flapping feed), but a real emptying passes
$full = ['count' => 1, 'results' => [$row]];
$none = ['count' => 0, 'results' => []];
tlCheck('an empty list answer while the stored list has rows is suspect', tnEmptyListSuspect('/catalog/v2/events/', $none, $full));
tlCheck('an empty search answer while the stored one has rows is suspect', tnEmptyListSuspect('/catalog/v2/events/search', $none, $full));
tlCheck('an empty answer with no stored rows is accepted (nothing better to keep)', !tnEmptyListSuspect('/catalog/v2/events/', $none, $none) && !tnEmptyListSuspect('/catalog/v2/events/', $none, null));
tlCheck('a non-empty answer is never suspect', !tnEmptyListSuspect('/catalog/v2/events/', $full, $full));
tlCheck('a detail answer (no results key) is never suspect', !tnEmptyListSuspect('/catalog/v2/events/42', ['id' => 42], $full));
tlCheck('reference data is never subject to the guard', !tnEmptyListSuspect('/catalog/v2/performers/', $none, $full));
tlCheck('the scalar fetch keeps the stored list and counts the suspect answer', strpos($fnSrc, "tnEmptyListSuspect(\$endpoint, \$r['data'], \$entry['data'])") !== false && strpos($fnSrc, "tnCount('empty_suspect');\n        return \$entry['data'];") !== false);
tlCheck('the bulk fetch keeps the stored list too', strpos($fnSrc, "tnEmptyListSuspect(\$endpoint, \$r['data'], \$out[\$i])") !== false);
tlCheck('event page: the H1 falls back to the event name when the spec is missing', strpos($evSrc, "trim((string) (\$evSpec['h1'] ?? '')) !== '' ? (string) \$evSpec['h1'] : (string) (\$event['text']['name'] ?? '')") !== false);
tlCheck('event page: every heading key the sections read has a default', strpos($evSrc, "array_merge(['kind' => '', 'venue' => '', 'guide' => '', 'other' => '', 'tickets' => '', 'promo' => '', 'faqs' => '', 'about' => '']") !== false);
$seoEv = file_get_contents(__DIR__ . '/../inc/seo-event.php');
$evPreStart = strpos($seoEv, '$evPre = [];');
$evPreEnd = strpos($seoEv, 'if ($evPre) tnRequestMulti($evPre);');
$evPreBlock = ($evPreStart !== false && $evPreEnd !== false && $evPreEnd > $evPreStart) ? substr($seoEv, $evPreStart, $evPreEnd - $evPreStart) : '';
tlCheck('event page: performer dates, the venue record and the venue events are all queued before the one batch call', $evPreBlock !== ''
    && strpos($evPreBlock, '$evPre[] = performerPageEventsSpec(') !== false
    && strpos($evPreBlock, "'/catalog/v2/venues/'") !== false
    && strpos($evPreBlock, '$evPreSpec = categoryEventsByLocationSpec(') !== false
    && strpos($evPreBlock, '$evPre[] = $evPreSpec;') !== false);
tlCheck('an empty answer in the "events" or plain-list shape is suspect too', tnEmptyListSuspect('/catalog/v2/events/', ['events' => []], $full) && tnEmptyListSuspect('/catalog/v2/events/', [], $full));
tlCheck('event page: the city events call is skipped when the venue has other events', strpos($seoEv, "&& empty(\$evVenueData['events'])) ? soSpecCityEvents(") !== false);

// 4. Wiring: the live code uses these helpers in the places the fix depends on
$fn = file_get_contents(__DIR__ . '/../functions.php');
tlCheck('functions.php requires the helper file before the first query-string restore', strpos($fn, "require_once __DIR__ . '/inc/tn-listing-copy.php';") !== false && strpos($fn, "require_once __DIR__ . '/inc/tn-listing-copy.php';") < strpos($fn, 'soSafeParseQuery(parse_url($_SERVER'));
tlCheck('the snapshot lookup reads the visitor query string through the guard', strpos($fn, '$q = soSafeParseQuery(parse_url($uri, PHP_URL_QUERY));') !== false);
tlCheck('only the internal legacy helper still calls parse_str directly', substr_count($fn, 'parse_str(') === 1 && strpos($fn, "parse_str(\$parts['query']") !== false);
tlCheck('the detail call falls back to a complete copy only when it is unavailable', strpos($fn, 'tnEntityUnavailable($event)') !== false && strpos($fn, "cache_get('tn_event_copy_' . \$eventId, TN_LISTING_COPY_TTL)") !== false);
tlCheck('listing answers save their rows', strpos($fn, "strpos((string) \$endpoint, '/catalog/v2/events') === 0") !== false && strpos($fn, 'tnSaveListingCopies($r[\'data\'])') !== false);
tlCheck('a fast dropped connection is retried once; throttles are not', strpos($fn, "empty(\$r['throttled']) && (microtime(true) - \$t0) < 3") !== false);
$ev = file_get_contents(__DIR__ . '/../event.php');
tlCheck('event page: the heading map is an array whatever the spec holds', strpos($ev, "\$evH2 = array_merge([") !== false && strpos($ev, "is_array(\$evSpec['h2'] ?? null) ? array_map('strval', \$evSpec['h2']) : []") !== false);
tlCheck('event page: the venue heading is a string', strpos($ev, "soSpecVenueInfoHtml((string) (\$evH2['venue'] ?? '')") !== false);
$ft = file_get_contents(__DIR__ . '/../footer.php');
tlCheck('footer: the social icons are called only when defined', strpos($ft, "if (function_exists('soSocialIconsHtml')) echo soSocialIconsHtml();") !== false);
$cron = file_get_contents(__DIR__ . '/../cron/warm-event-details.php');
tlCheck('warm-up job stops when the breaker is open', strpos($cron, 'tnBreakerOpen()') !== false);
tlCheck('warm-up job is capped at 40 per run', strpos($cron, 'min(40,') !== false);

// SEAT-OUTLET-Y: the genre redirect in category.php reads the visitor's query string through the safe parser, never parse_str() directly
$catSrc = file_get_contents(__DIR__ . '/../category.php');
tlCheck('category.php parses the query string with soSafeParseQuery, not parse_str', strpos($catSrc, "\$soQs = soSafeParseQuery(\$_SERVER['QUERY_STRING'] ?? '');") !== false && preg_match('/(?<![\w>:])parse_str\(/', preg_replace('#//[^\n]*#', '', $catSrc)) === 0);
tlCheck('a bracket flood parses to nothing (no warning) and a normal query still parses', soSafeParseQuery(str_repeat('a[', 40) . 'b' . str_repeat(']', 40) . '=1') === [] && soSafeParseQuery('page=2&x=y') === ['page' => '2', 'x' => 'y']);
echo $fails ? "\n$fails failed\n" : "\ntn listing copy: all passed\n";
exit($fails ? 1 : 0);
