<?php
/**
 * Clean URLs for the biggest genre / league pages: /hip-hop-tickets instead of /category/rap-hip-hop-1906.
 *
 * Each entry needs a small stub file in the web root (hip-hop-tickets.php) that includes category.php, so no server
 * rewrite is needed. The old /category/<name>-<id> URL 301-redirects here. Categories that are not listed keep working
 * at /category/<name>-<id> and get the same data-driven content, just without a clean URL.
 *
 *   slug  => [TicketNetwork category id, short label, long label, kind (concerts|sports|other), profile key|null]
 */
const SO_GENRE_PAGES = [
    'hip-hop-tickets'         => [1906, 'Hip Hop',          'hip hop and rap',            'concerts', 'hiphop'],
    'country-music-tickets'   => [1873, 'Country Music',    'country and folk',           'concerts', null],
    'pop-rock-concert-tickets' => [1903, 'Pop and Rock',    'pop and rock',               'concerts', null],
    'rnb-soul-concert-tickets' => [1904, 'R&B and Soul',    'R&B and soul',               'concerts', null],
    'latin-music-tickets'     => [1890, 'Latin Music',      'Latin music',                'concerts', null],
    'alternative-concert-tickets' => [1862, 'Alternative',  'alternative',                'concerts', null],
    'metal-concert-tickets'   => [1882, 'Hard Rock and Metal', 'hard rock and metal',     'concerts', null],
    'jazz-and-blues-tickets'  => [1885, 'Jazz and Blues',   'jazz and blues',             'concerts', null],
    'electronic-music-tickets' => [1915, 'Electronic Music', 'techno and electronic',      'concerts', null],
    'comedy-show-tickets'     => [1872, 'Comedy',           'stand-up comedy',            'other',    null],
    'classical-music-tickets' => [1871, 'Classical Music',  'classical music',            'concerts', null],
    'nba-tickets'             => [1971, 'NBA',              'NBA basketball',             'sports',   null],
    'nfl-tickets'             => [1879, 'NFL',              'NFL football',               'sports',   null],
    'mlb-tickets'             => [1969, 'MLB',              'MLB baseball',               'sports',   null],
    'nhl-tickets'             => [1972, 'NHL',              'NHL hockey',                 'sports',   null],
];

/** @return array|null [slug, id, label, long, kind, profile] for a clean slug */
function soGenreBySlug($slug) {
    if (!isset(SO_GENRE_PAGES[$slug])) return null;
    $g = SO_GENRE_PAGES[$slug];
    return ['slug' => $slug, 'id' => $g[0], 'label' => $g[1], 'long' => $g[2], 'kind' => $g[3], 'profile' => $g[4]];
}

function soGenreById($id) {
    foreach (SO_GENRE_PAGES as $slug => $g) {
        if ($g[0] === (int) $id) return soGenreBySlug($slug);
    }
    return null;
}

/**
 * Banner picture for a single-category page, so a circus show does not get a concert-crowd photo.
 *   1. Categories that are a named act or brand (Cirque du Soleil) use that name's real picture once the image job has
 *      resolved it (the same one the event cards show).
 *   2. Otherwise a site picture matching the family: basketball, football, theater, festival, concert stage.
 *   3. Anything else keeps the generic crowd photo.
 */
const SO_HERO_ENTITY = [2031 => 'Cirque du Soleil'];
const SO_HERO_BY_ID = [
    1865 => '/images/event-basketball.jpg', 1971 => '/images/event-basketball.jpg',
    1879 => '/images/event-football.jpg',
    1877 => '/images/festival-1.webp',
    2031 => '/images/loews-theatre.webp',
];
function soCategoryHero($id, $path = '') {
    $id = (int) $id;
    if (isset(SO_HERO_ENTITY[$id]) && function_exists('getEntityImage')) {
        $im = getEntityImage('artist', SO_HERO_ENTITY[$id], ['resolve' => false]);
        if (!empty($im['real']) && !empty($im['url'])) return $im['url'];
    }
    if (isset(SO_HERO_BY_ID[$id])) return SO_HERO_BY_ID[$id];
    if (strpos($path, '.1989.') !== false) return '/images/loews-theatre.webp';      // theater family
    if (strpos($path, '.1988.') !== false) return '/images/team-event.webp';          // other sports
    if (strpos($path, '.1986.') !== false) return '/images/event-concert.jpg';       // concerts
    return '/images/crowd-at-concert-or-event.webp';
}
