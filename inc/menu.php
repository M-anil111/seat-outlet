<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * One definition of the site menu, used by the desktop mega menu and the phone menu (header.php).
 * Each section: key, label, link, icon (inline SVG path data, 24x24 stroke icons), tagline, groups of links.
 * All links are pages that exist; category ids come from the site's own category pages.
 */
$soMenuDef = [
    ['key' => 'concerts', 'label' => 'Concerts', 'href' => '/concert-tickets-for-sale', 'tag' => 'Tours, shows and live music',
     'icon' => '<path d="M9 18V5l11-2v13"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="17.5" cy="16" r="2.5"/>',
     'groups' => [
        ['title' => 'Popular genres', 'links' => [['Pop / Rock', '/pop-rock-concert-tickets'], ['Country / Folk', '/country-music-tickets'], ['Rap / Hip Hop', '/hip-hop-tickets'], ['R&B / Soul', '/rnb-soul-concert-tickets'], ['Latin', '/latin-music-tickets'], ['Alternative', '/alternative-concert-tickets']]],
        ['title' => 'More to explore', 'links' => [['Hard Rock / Metal', '/metal-concert-tickets'], ['Jazz / Blues', '/jazz-and-blues-tickets'], ['Techno / Electronic', '/electronic-music-tickets'], ['Comedy', '/comedy-show-tickets'], ['Classical', '/classical-music-tickets'], ['Children / Family', '@category:2094']]],
     ], 'all' => 'All concerts'],
    ['key' => 'sports', 'label' => 'Sports', 'href' => '/game-day-tickets', 'tag' => 'Game day, every league',
     'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/>',
     'groups' => [
        ['title' => 'Leagues', 'links' => [['NFL', '/nfl-tickets'], ['NBA', '/nba-tickets'], ['MLB', '/mlb-tickets'], ['NHL', '/nhl-tickets'], ['MLS', '/mls-tickets'], ['Tennis', '/tennis-tickets']]],
        ['title' => 'More sports', 'links' => [['Basketball', '@category:1865'], ['Baseball', '@category:1864'], ['Hockey', '@category:1883'], ['Soccer', '/soccer-tickets'], ['Boxing', '/boxing-tickets'], ['Racing', '/racing-tickets']]],
     ], 'all' => 'All sports'],
    ['key' => 'theater', 'label' => 'Theater', 'href' => '/buy-broadway-tickets', 'tag' => 'Broadway, musicals and more',
     'icon' => '<path d="M4 5h16v6a8 8 0 0 1-16 0Z"/><path d="M9 10h.01M15 10h.01M9 14.5c1.6 1.4 4.4 1.4 6 0"/>',
     'groups' => [
        ['title' => 'On stage', 'links' => [['Broadway', '@category:1868'], ['Musical / Play', '@category:1894'], ['Off-Broadway', '@category:1896'], ['Las Vegas', '@category:1887']]],
        ['title' => 'More shows', 'links' => [['Cirque du Soleil', '@category:2031'], ['Ballet', '@category:1863'], ['Opera', '@category:1898'], ['Dance', '@category:1875'], ['Children / Family', '@category:1869']]],
     ], 'all' => 'All theater'],
    ['key' => 'festivals', 'label' => 'Festivals', 'href' => '/upcoming-music-festivals', 'tag' => 'Passes and lineups',
     'icon' => '<path d="m12 3 2.2 5.3 5.8.5-4.4 3.8 1.4 5.6L12 15l-5 3.2 1.4-5.6L4 8.8l5.8-.5Z"/>',
     'groups' => [
        ['title' => 'Festivals', 'links' => [['Upcoming music festivals', '/upcoming-music-festivals'], ['Festival tours', '@category:1877'], ['Christmas shows near me', '/christmas-shows-near-me']]],
     ], 'all' => 'All festivals'],
    ['key' => 'artists', 'label' => 'Artists & Teams', 'href' => '/all-artists-and-teams', 'tag' => 'Find your favorite performers and teams',
     'icon' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"/>',
     'groups' => [
        ['title' => 'Music and shows', 'links' => [['Artists on tour', '/concert-artists'], ['Comedians on tour', '/comedians-on-tour'], ['Broadway shows list', '/broadway-shows'], ['Music festivals list', '/music-festivals-list']]],
        ['title' => 'Sports teams', 'links' => [['All sports teams', '/sports-teams'], ['NFL teams', '/nfl-teams'], ['NBA teams', '/nba-teams'], ['MLB teams', '/mlb-teams'], ['NHL teams', '/nhl-teams'], ['MLS teams', '/mls-teams']]],
        ['title' => 'Browse', 'links' => [['All artists, teams and shows', '/all-artists-and-teams']]],
     ], 'all' => 'Browse all'],
    ['key' => 'cities', 'label' => 'Cities', 'href' => '/city-events', 'tag' => 'Events near you',
     'icon' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
     'groups' => [
        ['title' => 'Top cities', 'links' => [['New York', '@city:3027:New York, NY'], ['Los Angeles', '@city:2551:Los Angeles, CA'], ['Las Vegas', '@city:2355:Las Vegas, NV'], ['Chicago', '@city:915:Chicago, IL'], ['Nashville', '@city:2970:Nashville, TN'], ['Austin', '@city:247:Austin, TX']]],
        ['title' => 'More cities', 'links' => [['Atlanta', '@city:223:Atlanta, GA'], ['Dallas', '@city:1121:Dallas, TX'], ['Houston', '@city:2013:Houston, TX'], ['Boston', '@city:559:Boston, MA'], ['Seattle', '@city:3997:Seattle, WA'], ['Miami', '@city:2784:Miami, FL']]],
     ], 'all' => 'All cities'],
];

// A link written "@category:<id>" or "@city:<id>:<label>" is turned into the clean registry slug (see inc/slugs.php), so no id is ever
// written into a URL: the id only looks the entity up.
if (function_exists('soSlug')) {
    foreach ($soMenuDef as $i => $m) {
        foreach ($m['groups'] as $g => $grp) {
            foreach ($grp['links'] as $l => $link) {
                if (preg_match('/^@category:(\d+)$/', $link[1], $mm)) {
                    $soMenuDef[$i]['groups'][$g]['links'][$l][1] = '/category/' . soSlug('category', $link[0], (int) $mm[1]);
                } elseif (preg_match('/^@city:(\d+):(.+)$/', $link[1], $mm)) {
                    $soMenuDef[$i]['groups'][$g]['links'][$l][1] = '/city/' . soSlug('city', $mm[2], (int) $mm[1]);
                }
            }
        }
    }
}

// The second group of Concerts, Sports and Theater lists every other sub-category the ticket API has (Boxing, Rodeo, Ballet ...),
// busiest first; the first group stays a hand-picked headline set. If the API is unreachable the hand-written lists above stay.
if (function_exists('soHubSubcategories')) {
    foreach ($soMenuDef as $i => $m) {
        if (!isset(SO_HUB_ROOTS[$m['href']]) || count($m['groups']) < 2) continue;
        try { $subs = soHubSubcategories($m['href']); } catch (Throwable $e) { $subs = []; }
        if (!$subs) continue;
        $shown = array_column($m['groups'][0]['links'], 1);
        $more = [];
        foreach ($subs as $sub) { if (!in_array($sub['href'], $shown, true) && $sub['href'] !== $m['href']) $more[] = [$sub['label'], $sub['href']]; }
        if ($more) $soMenuDef[$i]['groups'][1]['links'] = array_slice($more, 0, 14);
    }
}
return $soMenuDef;
