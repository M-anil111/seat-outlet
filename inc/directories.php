<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * The A to Z directories: one page for every kind of act, instead of one list for everything.
 *
 * Each directory is the same page (all-artists-and-teams.php) with a different category filter, headline, title, description and
 * SEO copy. A small stub file in the web root (concert-artists.php ...) sets $soDirKey and includes it, like the genre pages.
 * Only names that have tickets on sale are listed (getTnPerformers() adds that filter), so no directory page is a list of dead links.
 *
 *   key => [path, category path prefix (null = everything), short name, h1, eyebrow, lead, title stem, description, focus keyword, volume, KD, copy key]
 *
 * Category path prefixes are TicketNetwork's own (see TN_CATEGORY_PATH_* and the league paths in functions.php). Volume and KD are
 * SE Ranking, US, 4 Oct 2026 (null where the tool had no data).
 */
const SO_DIRECTORIES = [
    'all' => [
        'path' => '/all-artists-and-teams', 'category' => null, 'short' => 'Artists, Teams and Shows',
        'h1' => 'Artists, Teams & Shows', 'eyebrow' => 'A to Z',
        'lead' => 'Browse all artists, teams and shows on Seat Outlet, from A to Z. Pick a letter to jump straight in.',
        'noun' => 'artists, teams and shows', 'copy' => 'all-artists-and-teams', 'focus' => 'all artists',
    ],
    'concert-artists' => [
        'path' => '/concert-artists', 'category' => '.1859.1986.', 'short' => 'Artists on Tour',
        'h1' => 'Artists on Tour', 'eyebrow' => 'Concerts A to Z',
        'lead' => 'Every artist and band with concert tickets on sale, from A to Z. Pick a name to see tour dates and compare seats and prices.',
        'noun' => 'artists and bands', 'copy' => 'concert-artists', 'focus' => 'artists on tour',
        'titles' => ['Artists on Tour A to Z with Tickets', 'Artists on Tour with Tickets', 'Artists on Tour'],
        'desc' => 'Artists on tour with tickets on sale, A to Z. Pick a name to see tour dates and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 1600, 'kd' => 69,
    ],
    'sports-teams' => [
        'path' => '/sports-teams', 'category' => '.1859.1988.', 'short' => 'Sports Teams',
        'h1' => 'Sports Teams', 'eyebrow' => 'Sports A to Z',
        'lead' => 'Every team with game tickets on sale, from A to Z. Pick a team to see its schedule and compare seats and prices.',
        'noun' => 'teams', 'copy' => 'sports-teams', 'focus' => 'sports teams',
        'titles' => ['Sports Teams A to Z with Game Tickets', 'Sports Teams with Game Tickets', 'Sports Teams'],
        'desc' => 'Sports teams with game tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 1500, 'kd' => 66,
    ],
    'nfl-teams' => [
        'path' => '/nfl-teams', 'category' => '.1859.1988.1879.1959.', 'short' => 'NFL Teams',
        'h1' => 'NFL Teams', 'eyebrow' => 'Football A to Z',
        'lead' => 'Every NFL team with tickets on sale, from A to Z. Pick a team to see its schedule and compare seats and prices.',
        'noun' => 'NFL teams', 'copy' => 'nfl-teams', 'focus' => 'nfl teams', 'league' => '/nfl-tickets',
        'titles' => ['NFL Teams with Tickets and Schedules', 'NFL Teams with Tickets', 'NFL Teams'],
        'desc' => 'NFL teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 165000, 'kd' => 94,
    ],
    'nba-teams' => [
        'path' => '/nba-teams', 'category' => '.1859.1988.1865.1971.', 'short' => 'NBA Teams',
        'h1' => 'NBA Teams', 'eyebrow' => 'Basketball A to Z',
        'lead' => 'Every NBA team with tickets on sale, from A to Z. Pick a team to see its schedule and compare seats and prices.',
        'noun' => 'NBA teams', 'copy' => 'nba-teams', 'focus' => 'nba teams', 'league' => '/nba-tickets',
        'titles' => ['NBA Teams with Tickets and Schedules', 'NBA Teams with Tickets', 'NBA Teams'],
        'desc' => 'NBA teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 135000, 'kd' => 92,
    ],
    'mlb-teams' => [
        'path' => '/mlb-teams', 'category' => '.1859.1988.1864.1969.', 'short' => 'MLB Teams',
        'h1' => 'MLB Teams', 'eyebrow' => 'Baseball A to Z',
        'lead' => 'Every MLB team with tickets on sale, from A to Z. Pick a team to see its schedule and compare seats and prices.',
        'noun' => 'MLB teams', 'copy' => 'mlb-teams', 'focus' => 'mlb teams', 'league' => '/mlb-tickets',
        'titles' => ['MLB Teams with Tickets and Schedules', 'MLB Teams with Tickets', 'MLB Teams'],
        'desc' => 'MLB teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 60500, 'kd' => 95,
    ],
    'nhl-teams' => [
        'path' => '/nhl-teams', 'category' => '.1859.1988.1883.1972.', 'short' => 'NHL Teams',
        'h1' => 'NHL Teams', 'eyebrow' => 'Hockey A to Z',
        'lead' => 'Every NHL team with tickets on sale, from A to Z. Pick a team to see its schedule and compare seats and prices.',
        'noun' => 'NHL teams', 'copy' => 'nhl-teams', 'focus' => 'nhl teams', 'league' => '/nhl-tickets',
        'titles' => ['NHL Teams with Tickets and Schedules', 'NHL Teams with Tickets', 'NHL Teams'],
        'desc' => 'NHL teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 49500, 'kd' => 80,
    ],
    'mls-teams' => [
        'path' => '/mls-teams', 'category' => '.1859.1988.1913.1970.', 'short' => 'MLS Teams',
        'h1' => 'MLS Teams', 'eyebrow' => 'Soccer A to Z',
        'lead' => 'Every MLS team with tickets on sale, from A to Z. Pick a team to see its schedule and compare seats and prices.',
        'noun' => 'MLS teams', 'copy' => 'mls-teams', 'focus' => 'mls teams', 'league' => '/mls-tickets',
        'titles' => ['MLS Teams with Tickets and Schedules', 'MLS Teams with Tickets', 'MLS Teams'],
        'desc' => 'MLS teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 12100, 'kd' => 93,
    ],
    'broadway-shows' => [
        'path' => '/broadway-shows', 'category' => '.1859.1989.', 'short' => 'Broadway and Theater Shows',
        'h1' => 'Broadway Shows List', 'eyebrow' => 'Theater A to Z',
        'lead' => 'Every Broadway show, musical and play with tickets on sale, from A to Z. Pick a show to see dates and compare seats and prices.',
        'noun' => 'shows', 'copy' => 'broadway-shows', 'focus' => 'broadway shows list',
        'titles' => ['Broadway Shows List A to Z with Tickets', 'Broadway Shows List with Tickets', 'Broadway Shows List'],
        'desc' => 'Broadway shows list A to Z with tickets for musicals and plays. Pick a show for dates and compare prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 440, 'kd' => 16,
    ],
    'comedians-on-tour' => [
        'path' => '/comedians-on-tour', 'category' => '.1859.1986.1872.', 'short' => 'Comedians on Tour',
        'h1' => 'Comedians on Tour', 'eyebrow' => 'Comedy A to Z',
        'lead' => 'Every comedian with tickets on sale, from A to Z. Pick a name to see tour dates and compare seats and prices.',
        'noun' => 'comedians', 'copy' => 'comedians-on-tour', 'focus' => 'comedians on tour',
        'titles' => ['Comedians on Tour A to Z with Tickets', 'Comedians on Tour with Tickets', 'Comedians on Tour'],
        'desc' => 'Comedians on tour with tickets on sale, A to Z. Pick a name to see tour dates and compare seats and prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 2400, 'kd' => 61,
    ],
    'music-festivals-list' => [
        'path' => '/music-festivals-list', 'category' => '.1859.1986.1877.', 'short' => 'Music Festivals',
        'h1' => 'Music Festivals List', 'eyebrow' => 'Festivals A to Z',
        'lead' => 'Every music festival with passes or tickets on sale, from A to Z. Pick a festival to see dates and compare prices.',
        'noun' => 'festivals', 'copy' => 'music-festivals-list', 'focus' => 'music festivals list',
        'titles' => ['Music Festivals List A to Z with Tickets', 'Music Festivals List with Tickets', 'Music Festivals List'],
        'desc' => 'Music festivals list A to Z, with passes and tickets on sale. Pick a festival to see dates and compare prices. Orders carry the TicketNetwork guarantee.',
        'vol' => 260, 'kd' => 36,
    ],
];

/** The directory configuration for a key, falling back to the combined A to Z list. */
function soDirectoryConfig(string $key): array {
    return SO_DIRECTORIES[$key] ?? SO_DIRECTORIES['all'];
}

/** [path, label] of every directory except the current one, for the "Browse by type" chips. */
function soDirectoryLinks(string $currentKey): array {
    $out = [];
    foreach (SO_DIRECTORIES as $k => $c) {
        if ($k === $currentKey) continue;
        $out[] = [$c['path'], $k === 'all' ? 'Everything A to Z' : $c['short']];
    }
    return $out;
}
