<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Sub-categories straight from the ticket API's category tree, so a new genre or sport (Boxing, Rodeo, Ballet ...) shows up on its own
 * instead of waiting for someone to edit a list. One place: the sub-category pills on the listing pages and the menu both read this.
 *
 * Each hub is a root of the tree ('.1859.1986.' concerts, '.1859.1988.' sports, '.1859.1989.' theater). Its children are the
 * sub-categories; every one links to a page that exists: the clean URL when inc/genre-pages.php has one, otherwise /category/<name>-<id>.
 * A sub-category is listed only when it has tickets on sale (the API gives ticketCount / eventCount per category), busiest first.
 * The API answer is cached by tnRequest (6 hours, refreshed after answering), so this costs nothing on most page views.
 */

require_once __DIR__ . '/genre-pages.php';

const SO_HUB_ROOTS = [
    '/concert-tickets-for-sale' => ['path' => '.1859.1986.', 'all' => 'All concerts'],
    '/game-day-tickets'         => ['path' => '.1859.1988.', 'all' => 'All sports'],
    '/buy-broadway-tickets'     => ['path' => '.1859.1989.', 'all' => 'All theater'],
];

/** Leagues sit one level deeper than the sports (NBA is under Basketball), so they are added by hand, first. [id, label, href] */
const SO_HUB_LEAGUES = [
    '/game-day-tickets' => [[1879, 'NFL', '/nfl-tickets'], [1971, 'NBA', '/nba-tickets'], [1969, 'MLB', '/mlb-tickets'], [1972, 'NHL', '/nhl-tickets'], [1970, 'MLS', '/mls-tickets']],
];

/** "TECHNO / ELECTRONIC" -> "Techno / Electronic", "CIRQUE DU SOLEIL" -> "Cirque du Soleil"; league and sport acronyms keep their capitals. */
function soCategoryDisplayName(string $name): string {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') return '';
    $keep = ['R&B', 'NFL', 'NBA', 'MLB', 'NHL', 'MLS', 'WNBA', 'AFL', 'CFL', 'UFL', 'AHL', 'ECHL', 'IHL', 'SPHL', 'CHL', 'WHL', 'KHL', 'PCL', 'MISL', 'UFC', 'MMA', 'LLWS', 'NCAA', 'USA', 'UK'];
    $small = ['du', 'de', 'of', 'and', 'the', 'a', 'in', 'on', 'at', 'to', 'for'];
    $words = explode(' ', $name);
    foreach ($words as $i => $w) {
        $up = strtoupper($w);
        if (in_array($up, $keep, true)) { $words[$i] = $up; continue; }
        $lw = strtolower($w);
        if ($i > 0 && in_array($lw, $small, true)) { $words[$i] = $lw; continue; }
        if (preg_match('/^\d+s$/', $lw)) { $words[$i] = $lw; continue; }   // 50s
        $words[$i] = implode('-', array_map('ucfirst', explode('-', $lw)));
    }
    return implode(' ', $words);
}

/** The page a sub-category links to. */
function soCategoryHref(int $id, string $name): string {
    if (function_exists('soGenreById') && ($g = soGenreById($id))) return '/' . $g['slug'];
    return '/category/' . createSlug($name, $id);
}

/**
 * Sub-categories of a hub (see SO_HUB_ROOTS), busiest first.
 * The API keeps ticket counts on the lowest level only for some branches (Basketball itself says 0 while Professional (NBA) beneath it
 * says 18,454), so a sub-category's numbers are the larger of its own and the total of everything below it.
 * @return array<int,array{id:int,label:string,href:string,events:int,tickets:int}>
 */
function soHubSubcategories(string $hubHref): array {
    static $memo = [];
    if (isset($memo[$hubHref])) return $memo[$hubHref];
    if (!isset(SO_HUB_ROOTS[$hubHref])) return $memo[$hubHref] = [];
    $root = SO_HUB_ROOTS[$hubHref]['path'];
    $depth = count(array_filter(explode('.', $root)));
    $all = [];
    for ($page = 1; $page <= 6; $page++) {
        $data = tnRequest('/catalog/v2/categories', ['filter' => "startswith(path, '" . tnEscapeFilterValue($root) . "') and path ne '" . tnEscapeFilterValue($root) . "'", 'perPage' => 200, 'page' => $page]);
        $chunk = $data['results'] ?? [];
        foreach ($chunk as $c) { $all[] = $c; }
        if (count($chunk) < 200) break;
    }
    $subs = [];   // path => row, for the direct children
    $below = [];  // path => [tickets, events] of everything beneath it
    foreach ($all as $cat) {
        $path = (string) ($cat['path'] ?? '');
        $segments = array_values(array_filter(explode('.', $path)));
        if (count($segments) < $depth + 1) continue;
        $tickets = (int) ($cat['_metadata']['ticketCount'] ?? 0);
        $events = (int) ($cat['_metadata']['eventCount'] ?? 0);
        $childPath = '.' . implode('.', array_slice($segments, 0, $depth + 1)) . '.';
        if (count($segments) === $depth + 1) {
            $subs[$childPath] = ['name' => (string) ($cat['text']['name'] ?? ''), 'id' => (int) end($segments), 'tickets' => $tickets, 'events' => $events];
        } else {
            $below[$childPath][0] = ($below[$childPath][0] ?? 0) + $tickets;
            $below[$childPath][1] = ($below[$childPath][1] ?? 0) + $events;
        }
    }
    $rows = [];
    foreach ($subs as $path => $r) {
        if ($r['name'] === '' || strtoupper($r['name']) === 'OTHER' || $r['id'] <= 0) continue;
        $tickets = max($r['tickets'], $below[$path][0] ?? 0);
        $events = max($r['events'], $below[$path][1] ?? 0);
        if ($tickets <= 0) continue;   // nothing on sale: it would be an empty page
        $rows[] = ['id' => $r['id'], 'label' => soCategoryDisplayName($r['name']), 'href' => soCategoryHref($r['id'], $r['name']), 'events' => $events, 'tickets' => $tickets];
    }
    usort($rows, function ($a, $b) { return [$b['events'], $b['tickets']] <=> [$a['events'], $a['tickets']]; });
    $lead = [];
    foreach (SO_HUB_LEAGUES[$hubHref] ?? [] as [$lid, $llabel, $lhref]) { $lead[] = ['id' => $lid, 'label' => $llabel, 'href' => $lhref, 'events' => 0, 'tickets' => 0]; }
    $seen = [];
    $out = [];
    foreach (array_merge($lead, $rows) as $r) {
        if (isset($seen[$r['href']])) continue;
        // Concerts: "Festival / Tour" is the festivals hub.
        if ($hubHref === '/concert-tickets-for-sale' && $r['id'] === 1877) { $r = ['id' => 0, 'label' => 'Festivals', 'href' => '/upcoming-music-festivals', 'events' => $r['events'], 'tickets' => $r['tickets']]; }
        $seen[$r['href']] = true;
        $out[] = $r;
    }
    return $memo[$hubHref] = $out;
}

/** The label of a hub's "all" pill ("All concerts"). */
function soHubAllLabel(string $hubHref): string { return SO_HUB_ROOTS[$hubHref]['all'] ?? 'All'; }
