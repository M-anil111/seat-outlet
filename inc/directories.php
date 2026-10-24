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
        'lead' => 'Browse all artists, teams and shows on Seat Outlet, from A to Z. Browse the pages or search for a name.',
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

/**
 * The directories that belong with a category path (a page's own place in the TicketNetwork category tree), most specific first:
 * the ones on the same branch above it (an MLB team page: MLB teams, then all sports teams) and the ones below it (the sports hub:
 * sports teams, then each league). The combined A to Z list is never in the result; callers add it last.
 *
 * @return array<int, array> directory configs (SO_DIRECTORIES entries) with their key added as 'key'
 */
function soDirectoriesForPath(string $path, int $max = 6): array {
    $path = trim($path);
    if ($path === '') return [];
    $above = []; $below = [];
    foreach (SO_DIRECTORIES as $key => $cfg) {
        $prefix = (string) ($cfg['category'] ?? '');
        if ($prefix === '') continue;
        $cfg['key'] = $key;
        if (strpos($path, $prefix) === 0) $above[strlen($prefix)] = $cfg;               // same branch, at or above the page
        elseif (strpos($prefix, $path) === 0) $below[] = $cfg;                          // same branch, below the page
    }
    krsort($above);
    return array_slice(array_merge(array_values($above), $below), 0, $max);
}

/**
 * A short "Browse A to Z" line linking the directories that fit a category path, then the combined list. Plain bootstrap classes only,
 * so it renders the same on every page type without touching the page-type stylesheets. Returns '' when nothing fits.
 */
function soDirectoryLinksHtml(string $path, int $max = 6): string {
    $dirs = soDirectoriesForPath($path, $max);
    if (!$dirs) return '';
    $h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $links = [];
    foreach ($dirs as $d) $links[] = '<a href="' . $h($d['path']) . '">' . $h($d['short']) . ' A to Z</a>';
    $links[] = '<a href="' . $h(SO_DIRECTORIES['all']['path']) . '">All artists, teams and shows</a>';
    return '<nav class="container py-4" aria-label="Browse by name, A to Z"><p class="mb-0"><strong>Browse by name:</strong> ' . implode(' &middot; ', $links) . '</p></nav>';
}

/* ------------------------------------------------------------------ A to Z order */

const SO_DIR_FETCH_PER_PAGE = 200;
const SO_DIR_FETCH_MAX_PAGES = 60;       // 12,000 names for one letter; far above any real letter
const SO_DIR_INDEX_TTL = 6 * 3600;

/** The name without a leading "The " (display, links and slugs keep the full name). */
function soDirStripArticle(string $name): string {
    $n = trim($name);
    $s = preg_replace('/^the\s+(?=\S)/i', '', $n);
    return $s === null ? $n : $s;
}

/** Sort key: the name without a leading "The ", accents folded, lower case. "The 1975" sorts as "1975", "The Beatles" as "beatles". */
function soDirSortKey(string $name): string {
    $s = soDirStripArticle($name);
    if (function_exists('soAsciiFold')) $s = soAsciiFold($s);
    return strtolower($s);
}

/** The directory letter a name belongs under: 'A' to 'Z', '0-9', or '' (starts with something else, listed under no letter). */
function soDirLetterOf(string $name): string {
    $c = strtoupper(substr(soDirStripArticle($name), 0, 1));
    if ($c !== '' && ctype_digit($c)) return '0-9';
    return ($c !== '' && $c >= 'A' && $c <= 'Z') ? $c : '';
}

/** The API filter that fetches everything that could belong under a letter: names starting with it, and "The ..." names starting with it after "The ". */
function soDirLetterFilter(string $letter): string {
    $starts = $letter === '0-9' ? range(0, 9) : [$letter];
    $parts = [];
    foreach ($starts as $d) {
        $d = tnEscapeFilterValue((string) $d);
        $parts[] = "startswith(text/name,'$d')";
        $parts[] = "startswith(text/name,'The $d')";
    }
    return '(' . implode(' or ', $parts) . ')';
}

/** Test and placeholder entries ("000000_TL", names with no letters, an underscore) are not artists, teams or shows. */
function soDirIsJunk(string $name): bool {
    return !preg_match('/\p{L}/u', $name) || strpos($name, '_') !== false || preg_match('/^0{3,}/', $name);
}

/**
 * Everything under one letter of a directory, from the API, as plain rows [name, id, uri, category]. All pages are read (200 a call), because
 * the order is the name without "The ", which the API cannot sort on: a page of 48 can only be cut from the whole letter. Stored for six hours
 * under one key, so the pages of a letter cost the calls of one pass (fewer than paging 48 at a time). A failed call throws: a part of a
 * letter is never stored or shown. $fetch(array $params): array defaults to getTnPerformers().
 * @return array<int,array{name:string,id:int,uri:string,category:array}>
 */
function soDirLetterRows(array $dir, string $letter, ?callable $fetch = null): array {
    $key = 'dir_letter_' . md5(($dir['path'] ?? '') . '|' . $letter);
    $useCache = $fetch === null;   // a fetcher passed in (tests) is never stored
    if ($useCache) {
        $hit = cache_get($key, SO_DIR_INDEX_TTL);
        if (is_array($hit)) return $hit;
        $fetch = 'getTnPerformers';
    }
    $filter = soDirLetterFilter($letter);
    if (!empty($dir['category'])) $filter .= " and startswith(defaultCategory/path, '" . tnEscapeFilterValue($dir['category']) . "')";
    $rows = [];
    for ($page = 1; $page <= SO_DIR_FETCH_MAX_PAGES; $page++) {
        $r = $fetch(['filter' => $filter, 'sort' => 'text/name', 'perPage' => SO_DIR_FETCH_PER_PAGE, 'page' => $page, 'includeTotalCount' => 'true']);
        $results = is_array($r['results'] ?? null) ? $r['results'] : [];
        foreach ($results as $p) {
            $name = trim((string) ($p['text']['name'] ?? ''));
            $uri = (string) ($p['uriComponent'] ?? '');
            if ($name === '' || $uri === '' || soDirIsJunk($name) || soDirLetterOf($name) !== $letter) continue;
            $rows[] = ['name' => $name, 'id' => (int) ($p['id'] ?? 0), 'uri' => $uri, 'category' => (array) ($p['defaultCategory'] ?? [])];
        }
        $total = (int) ($r['totalCount'] ?? 0);
        if (!$results || $page * SO_DIR_FETCH_PER_PAGE >= $total) break;
    }
    $rows = soDirSortRows($rows);
    if ($useCache) cache_set($key, $rows);
    return $rows;
}

/** Rows in directory order: sort key, then the full name, then id (so two pages never show a name twice or skip one). */
function soDirSortRows(array $rows): array {
    usort($rows, function ($a, $b) {
        return strcmp(soDirSortKey($a['name']), soDirSortKey($b['name'])) ?: strcmp($a['name'], $b['name']) ?: ($a['id'] <=> $b['id']);
    });
    return array_values($rows);
}

/** One page of a sorted letter: [rows, total]. */
function soDirPageOf(array $rows, int $page, int $perPage): array {
    return [array_slice($rows, (max(1, $page) - 1) * $perPage, $perPage), count($rows)];
}
