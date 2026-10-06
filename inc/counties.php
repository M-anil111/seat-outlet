<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * County pages, United States only: /county/<slug>, for example /county/travis-county-tx.
 *
 * The ticket API has no county. Each city is placed in a county once from its coordinates with the free U.S. Census Bureau geocoder
 * (https://geocoding.geo.census.gov, no key), stored in `city_counties` (migration 0044) and read from there. A county page lists the
 * events of the cities in that county (an OR filter on city ids) and is noindex and out of the sitemap with fewer than SO_SITEMAP_CITYPAGE_MIN
 * events. Canada has no counties, so Canadian cities are never queued.
 */
const SO_COUNTY_STATES = ['AL','AK','AZ','AR','CA','CO','CT','DE','DC','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'];
/** Canadian provinces and territories: no county lookup, but their cities are listed on the province page (status 'na' in city_counties). */
const SO_PROVINCES = ['AB','BC','MB','NB','NL','NS','NT','NU','ON','PE','QC','SK','YT'];
const SO_COUNTY_MAX_CITIES = 25;   // a county page asks the API for at most this many cities (the busiest first)

/** County from a Census geocoder response: ['fips' => 48453, 'name' => 'Travis County'] or null. */
function soCountyParseCensus(string $json): ?array {
    $j = json_decode($json, true);
    $c = $j['result']['geographies']['Counties'][0] ?? null;
    if (!is_array($c) || empty($c['GEOID']) || !ctype_digit((string) $c['GEOID']) || empty($c['NAME'])) return null;
    return ['fips' => (int) $c['GEOID'], 'name' => trim((string) $c['NAME'])];
}

/** Ask the Census geocoder which county a point is in. null when it does not know or the service is down. */
function soCountyFromCensus(float $lat, float $lng): ?array {
    if ($lat == 0.0 && $lng == 0.0) return null;
    $url = 'https://geocoding.geo.census.gov/geocoder/geographies/coordinates?' . http_build_query([
        'x' => $lng, 'y' => $lat, 'benchmark' => 'Public_AR_Current', 'vintage' => 'Current_Current', 'layers' => 'Counties', 'format' => 'json',
    ]);
    $ctx = stream_context_create(['http' => ['timeout' => 12, 'header' => "User-Agent: SeatOutlet/1.0 (county lookup)\r\n"]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? null : soCountyParseCensus($body);
}

/** "Austin, TX" to ['Austin', 'TX', true] (US, county lookup) or "Toronto, ON" to ['Toronto', 'ON', false] (Canada, listed only); null for anything else. */
function soCountyPlace(string $label): ?array {
    if (!preg_match('/^(.+),\s*([A-Z]{2})$/', $label, $m)) return null;
    if (in_array($m[2], SO_COUNTY_STATES, true)) return [$m[1], $m[2], true];
    if (in_array($m[2], SO_PROVINCES, true)) return [$m[1], $m[2], false];
    return null;
}

/**
 * Queue cities: rows of [cityId, "Austin, TX"]. US cities wait for a county lookup (cron/resolve-counties.php); Canadian cities are stored
 * as 'na' (no lookup) so their province page can list them. Cities already known are left alone. Returns how many were new.
 */
function soCountyEnqueue(array $cities): int {
    $new = 0;
    $us = MYSQLI->prepare("INSERT IGNORE INTO city_counties (city_id, city_name, state_abbr, status) VALUES (?, ?, ?, 'pending')");
    $ca = MYSQLI->prepare("INSERT IGNORE INTO city_counties (city_id, city_name, state_abbr, status) VALUES (?, ?, ?, 'na')");
    if (!$us || !$ca) return 0;
    foreach ($cities as [$id, $label]) {
        $id = (int) $id;
        $place = $id > 0 ? soCountyPlace((string) $label) : null;
        if (!$place) continue;
        [$name, $abbr, $isUs] = $place;
        $st = $isUs ? $us : $ca;
        $st->bind_param('iss', $id, $name, $abbr);
        if ($st->execute() && $st->affected_rows > 0) $new++;
    }
    $us->close(); $ca->close();
    return $new;
}

/** Record how many upcoming events each city had in the sitemap build: [cityId => count]. */
function soCountySetCounts(array $counts): void {
    $st = MYSQLI->prepare("UPDATE city_counties SET events_n = ? WHERE city_id = ?");
    if (!$st) return;
    foreach ($counts as $id => $n) { $n = (int) $n; $id = (int) $id; $st->bind_param('ii', $n, $id); $st->execute(); }
    $st->close();
}

/** Look up pending (and old missed) cities. Returns [resolved, missed]. At most $limit calls, one per second. */
function soCountyResolvePending(int $limit = 150): array {
    $res = MYSQLI->query("SELECT city_id FROM city_counties WHERE status = 'pending' OR (status = 'miss' AND checked_at < (NOW() - INTERVAL 30 DAY)) ORDER BY status ASC, city_id ASC LIMIT " . max(1, $limit));
    $ok = 0; $miss = 0;
    while ($res && ($row = $res->fetch_assoc())) {
        $id = (int) $row['city_id'];
        $city = function_exists('getTnCityById') ? getTnCityById($id) : [];
        $geo = $city['geoLocation'] ?? [];
        $county = isset($geo['latitude'], $geo['longitude']) ? soCountyFromCensus((float) $geo['latitude'], (float) $geo['longitude']) : null;
        if ($county) {
            $st = MYSQLI->prepare("UPDATE city_counties SET county_fips = ?, county_name = ?, status = 'ok', checked_at = NOW() WHERE city_id = ?");
            $fips = $county['fips']; $nm = $county['name'];
            $st->bind_param('isi', $fips, $nm, $id); $st->execute(); $st->close(); $ok++;
        } else {
            MYSQLI->query("UPDATE city_counties SET status = 'miss', checked_at = NOW() WHERE city_id = " . $id); $miss++;
        }
        sleep(1);
    }
    return [$ok, $miss];
}

/** [fips, "Travis County, TX"] for a city, or null (not US, not looked up yet, or no county). */
function soCountyOfCity(int $cityId): ?array {
    static $memo = [];
    if (array_key_exists($cityId, $memo)) return $memo[$cityId];
    $r = MYSQLI->query("SELECT county_fips, county_name, state_abbr FROM city_counties WHERE city_id = " . (int) $cityId . " AND status = 'ok' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $memo[$cityId] = $row ? [(int) $row['county_fips'], $row['county_name'] . ', ' . $row['state_abbr']] : null;
}

/** ["Travis County, TX", [cityIds...]] for a county code, or null when no city is in it. Cities come busiest first (events_n from the last sitemap build). */
function soCountyInfo(int $fips): ?array {
    $r = MYSQLI->query("SELECT city_id, county_name, state_abbr FROM city_counties WHERE county_fips = " . (int) $fips . " AND status = 'ok' ORDER BY events_n DESC, city_id ASC");
    $ids = []; $label = '';
    while ($r && ($row = $r->fetch_assoc())) { $ids[] = (int) $row['city_id']; $label = $row['county_name'] . ', ' . $row['state_abbr']; }
    return $ids ? [$label, $ids] : null;
}

/** The OData filter fragment for a county's cities: (city/id eq 1 or city/id eq 2 ...). */
function soCountyFilter(array $cityIds): string {
    $cityIds = array_slice(array_values(array_unique(array_map('intval', $cityIds))), 0, SO_COUNTY_MAX_CITIES);
    return '(' . implode(' or ', array_map(fn($i) => "city/id eq $i", $cityIds)) . ')';
}

/** Cities in a state that have a page of their own (busiest first), as [cityId, "Austin, TX"]. Gives every city page an inbound link from its state page. */
function soCitiesInState(string $abbr, int $limit = 60): array {
    if (!in_array($abbr, SO_COUNTY_STATES, true) && !in_array($abbr, SO_PROVINCES, true)) return [];
    $st = MYSQLI->prepare("SELECT city_id, city_name FROM city_counties WHERE state_abbr = ? AND events_n >= " . SO_SITEMAP_CITYPAGE_MIN . " ORDER BY events_n DESC, city_name ASC LIMIT " . max(1, $limit));
    if (!$st) return [];
    $st->bind_param('s', $abbr); $st->execute();
    $out = [];
    $r = $st->get_result();
    while ($r && ($row = $r->fetch_assoc())) $out[] = [(int) $row['city_id'], $row['city_name'] . ', ' . $abbr];
    $st->close();
    return $out;
}

/** Counties in a state, as [fips, label] sorted by name (state abbreviation like "TX"). */
function soCountiesInState(string $abbr, int $limit = 60): array {
    if (!in_array($abbr, SO_COUNTY_STATES, true)) return [];
    $st = MYSQLI->prepare("SELECT county_fips, county_name, state_abbr, SUM(events_n) AS n FROM city_counties WHERE state_abbr = ? AND status = 'ok' GROUP BY county_fips, county_name, state_abbr HAVING SUM(events_n) >= " . SO_SITEMAP_CITYPAGE_MIN . " ORDER BY county_name ASC LIMIT " . max(1, $limit));
    $st->bind_param('s', $abbr); $st->execute();
    $out = [];
    $r = $st->get_result();
    while ($r && ($row = $r->fetch_assoc())) $out[] = [(int) $row['county_fips'], $row['county_name'] . ', ' . $row['state_abbr']];
    $st->close();
    return $out;
}
