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
