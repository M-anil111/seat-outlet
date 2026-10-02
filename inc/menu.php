<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * One definition of the site menu, used by the desktop mega menu and the phone menu (header.php).
 * Each section: key, label, link, icon (inline SVG path data, 24x24 stroke icons), tagline, groups of links.
 * All links are pages that exist; category ids come from the site's own category pages.
 */
return [
    ['key' => 'concerts', 'label' => 'Concerts', 'href' => '/concert-tickets-for-sale', 'tag' => 'Tours, shows and live music',
     'icon' => '<path d="M9 18V5l11-2v13"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="17.5" cy="16" r="2.5"/>',
     'groups' => [
        ['title' => 'Popular genres', 'links' => [['Pop / Rock', '/pop-rock-concert-tickets'], ['Country / Folk', '/country-music-tickets'], ['Rap / Hip Hop', '/hip-hop-tickets'], ['R&B / Soul', '/rnb-soul-concert-tickets'], ['Latin', '/latin-music-tickets'], ['Alternative', '/alternative-concert-tickets']]],
        ['title' => 'More to explore', 'links' => [['Hard Rock / Metal', '/metal-concert-tickets'], ['Jazz / Blues', '/jazz-and-blues-tickets'], ['Techno / Electronic', '/electronic-music-tickets'], ['Comedy', '/comedy-show-tickets'], ['Classical', '/classical-music-tickets'], ['Children / Family', '/category/children-family-2094']]],
     ], 'all' => 'All concerts'],
    ['key' => 'sports', 'label' => 'Sports', 'href' => '/game-day-tickets', 'tag' => 'Game day, every league',
     'icon' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/>',
     'groups' => [
        ['title' => 'Leagues', 'links' => [['NFL', '/nfl-tickets'], ['NBA', '/nba-tickets'], ['MLB', '/mlb-tickets'], ['NHL', '/nhl-tickets'], ['MLS', '/category/mls-1970'], ['Tennis', '/category/tennis-1916']]],
        ['title' => 'More sports', 'links' => [['Basketball', '/category/basketball-1865'], ['Baseball', '/category/baseball-1864'], ['Hockey', '/category/hockey-1883'], ['Soccer', '/category/soccer-1913'], ['Boxing', '/category/boxing-1867'], ['Racing', '/category/racing-1905']]],
     ], 'all' => 'All sports'],
    ['key' => 'theater', 'label' => 'Theater', 'href' => '/buy-broadway-tickets', 'tag' => 'Broadway, musicals and more',
     'icon' => '<path d="M4 5h16v6a8 8 0 0 1-16 0Z"/><path d="M9 10h.01M15 10h.01M9 14.5c1.6 1.4 4.4 1.4 6 0"/>',
     'groups' => [
        ['title' => 'On stage', 'links' => [['Broadway', '/category/broadway-1868'], ['Musical / Play', '/category/musical-play-1894'], ['Off-Broadway', '/category/off-broadway-1896'], ['Las Vegas', '/category/las-vegas-1887']]],
        ['title' => 'More shows', 'links' => [['Cirque du Soleil', '/category/cirque-du-soleil-2031'], ['Ballet', '/category/ballet-1863'], ['Opera', '/category/opera-1898'], ['Dance', '/category/dance-1875'], ['Children / Family', '/category/children-family-1869']]],
     ], 'all' => 'All theater'],
    ['key' => 'festivals', 'label' => 'Festivals', 'href' => '/upcoming-music-festivals', 'tag' => 'Passes and lineups',
     'icon' => '<path d="m12 3 2.2 5.3 5.8.5-4.4 3.8 1.4 5.6L12 15l-5 3.2 1.4-5.6L4 8.8l5.8-.5Z"/>',
     'groups' => [
        ['title' => 'Festivals', 'links' => [['Upcoming music festivals', '/upcoming-music-festivals'], ['Festival tours', '/category/festival-tour-1877'], ['Holiday events', '/category/holiday-1884']]],
     ], 'all' => 'All festivals'],
    ['key' => 'artists', 'label' => 'Artists & Teams', 'href' => '/all-artists-and-teams', 'tag' => 'Find your favorite, A to Z',
     'icon' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.6 3.6-5.5 7-5.5s6.2 1.9 7 5.5"/>',
     'groups' => [
        ['title' => 'Browse', 'links' => [['All artists, teams and shows', '/all-artists-and-teams'], ['Search events', '/search']]],
     ], 'all' => 'Browse A to Z'],
    ['key' => 'cities', 'label' => 'Cities', 'href' => '/city-events', 'tag' => 'Events near you',
     'icon' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
     'groups' => [
        ['title' => 'Top cities', 'links' => [['New York', '/event-city/new-york-ny-3027'], ['Los Angeles', '/event-city/los-angeles-ca-2551'], ['Las Vegas', '/event-city/las-vegas-nv-2355'], ['Chicago', '/event-city/chicago-il-915'], ['Nashville', '/event-city/nashville-tn-2970'], ['Austin', '/event-city/austin-tx-247']]],
        ['title' => 'More cities', 'links' => [['Atlanta', '/event-city/atlanta-ga-223'], ['Dallas', '/event-city/dallas-tx-1121'], ['Houston', '/event-city/houston-tx-2013'], ['Boston', '/event-city/boston-ma-559'], ['Seattle', '/event-city/seattle-wa-3997'], ['Miami', '/event-city/miami-fl-2784']]],
     ], 'all' => 'All cities'],
];
