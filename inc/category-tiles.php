<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * "Browse by category": a grid of white cards, each a pale blue icon tile, a name and a chevron, linking to a category page.
 * One component for the home page and for the foot of artist, venue, city, event, search and category pages
 * (soRenderCategoryTiles(), printed by footer.php on those pages). Every link is a page that exists.
 */

/** Inline icon (line style, currentColor) for a tile; unknown keys get a ticket-like tag. */
function soCategoryTileIcon($key) {
    $a = 'width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';
    $p = [
        'football'   => '<ellipse cx="12" cy="12" rx="10" ry="6.2" transform="rotate(-35 12 12)"/><path d="M9 15l6-6M10.5 9.8l1.7 1.7M12.5 7.8l1.7 1.7M8.2 13.2l1.7 1.7"/>',
        'basketball' => '<circle cx="12" cy="12" r="9.5"/><path d="M12 2.5v19M2.5 12h19M5 5.5c3 3 3 10 0 13M19 5.5c-3 3-3 10 0 13"/>',
        'baseball'   => '<circle cx="12" cy="12" r="9.5"/><path d="M6.2 4.6c2.6 2.6 2.6 12.2 0 14.8M17.8 4.6c-2.6 2.6-2.6 12.2 0 14.8"/>',
        'hockey'     => '<ellipse cx="12" cy="9.5" rx="8" ry="3.5"/><path d="M4 9.5v5c0 2 3.6 3.5 8 3.5s8-1.5 8-3.5v-5"/>',
        'soccer'     => '<circle cx="12" cy="12" r="9.5"/><path d="M12 8l3.6 2.6-1.4 4.2H9.8l-1.4-4.2L12 8zM12 8V2.8M15.6 10.6l5-1.6M14.2 14.8l3 4M9.8 14.8l-3 4M8.4 10.6l-5-1.6"/>',
        'trophy'     => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4zM8 6H4.5a2 2 0 0 0 2 3.5M16 6h3.5a2 2 0 0 1-2 3.5M12 13v4M8.5 20h7"/>',
        'music'      => '<path d="M9 17.5V6l10-2v11.5"/><circle cx="6.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="15.5" r="2.5"/>',
        'mic'        => '<rect x="9" y="2.5" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21M8.5 21h7"/>',
        'masks'      => '<path d="M3.5 5.5h10v6a5 5 0 0 1-10 0v-6zM6.5 9h.01M10.5 9h.01M6.5 13c1 1 3 1 4 0"/><path d="M10.5 18.5a5 5 0 0 0 10-1.5v-6h-5.5M15.5 14h.01M19 14h.01"/>',
        'smile'      => '<circle cx="12" cy="12" r="9.5"/><path d="M8 14c1 1.6 2.4 2.4 4 2.4s3-.8 4-2.4M9 9.5h.01M15 9.5h.01"/>',
        'tent'       => '<path d="M3 20L12 4l9 16H3zM12 4v16M8 20l4-7 4 7"/>',
        'family'     => '<circle cx="8" cy="8" r="3"/><circle cx="17" cy="9.5" r="2.4"/><path d="M2.5 20c0-3.3 2.5-5.5 5.5-5.5s5.5 2.2 5.5 5.5M14.5 14.8c.8-.3 1.6-.4 2.5-.4 2.5 0 4.5 1.8 4.5 4.6"/>',
        'star'       => '<path d="M12 3l2.6 5.6 6 .7-4.4 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.4 9.3l6-.7L12 3z"/>',
        'ticket'     => '<path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 0 0 4v1.5h18V16a2 2 0 0 0 0-4v0a2 2 0 0 0 0-4V6.5H3V8zM14 6.5v11"/>',
        'flag'       => '<path d="M5 21V4M5 4h12l-2.5 3.5L17 11H5"/><path d="M8.5 4v7M12 4v7M5 7.5h10.5"/>',
        'golf'       => '<path d="M12 18V3l6.5 3L12 9"/><ellipse cx="12" cy="19" rx="6.5" ry="2"/>',
        'boxing'     => '<path d="M7 11V7.5a5 5 0 0 1 10 0V12a4 4 0 0 1-4 4H9.5A2.5 2.5 0 0 1 7 13.5V11z"/><path d="M7 11a2.5 2.5 0 0 0 0 5M9 16v4h7.5v-4"/>',
        'rodeo'      => '<path d="M2.5 14.5c3.5 3 15.5 3 19 0"/><path d="M6 15.5c0-4.5 1.2-8.5 3.6-8.5 1 0 1.5.8 2.4.8s1.4-.8 2.4-.8c2.4 0 3.6 4 3.6 8.5"/>',
        'wrestling'  => '<circle cx="7.5" cy="4.8" r="2"/><circle cx="16.5" cy="4.8" r="2"/><path d="M4 21l3-7.5 2.5 2M20 21l-3-7.5-2.5 2M7 13.5L12 9.5l5 4"/>',
        'mma'        => '<path d="M6 9.5A3.5 3.5 0 0 1 9.5 6h6A3.5 3.5 0 0 1 19 9.5v2.5a5 5 0 0 1-5 5h-3a5 5 0 0 1-5-5V9.5z"/><path d="M9.5 6v4.5M12.5 6v4.5M15.5 6v4.5M8.5 17v3.5h8V17"/>',
        'volleyball' => '<circle cx="12" cy="12" r="9.5"/><path d="M12 2.5c-2.2 4-2.2 7.6 0 9.5s6.4 2 9.2-.9M12 12c-4.2.9-7 3.8-7.6 7M12 12c2.1 3.1 5.6 5 8.4 4.5"/>',
        'skate'      => '<path d="M5.5 3.5H10v6.5l6.8 2.4a2.6 2.6 0 0 1 1.7 2.4v1.2H5.5V3.5z"/><path d="M3 19.5h16.5a2.5 2.5 0 0 0 2-1"/><path d="M8 16v3.5M15.5 16v3.5"/>',
        'olympics'   => '<circle cx="5.8" cy="10" r="3.3"/><circle cx="12" cy="10" r="3.3"/><circle cx="18.2" cy="10" r="3.3"/><circle cx="8.9" cy="14" r="3.3"/><circle cx="15.1" cy="14" r="3.3"/>',
        'lacrosse'   => '<path d="M3.5 20.5l9-9"/><path d="M12.5 11.5c-1.6-3 0-7.2 3.6-8.2s5.2 2.6 3.6 5.6-5.2 4.1-7.2 2.6z"/><path d="M14.8 5.2l3.4 3.4M14.4 8l2.4 2.4"/>',
        'cricket'    => '<path d="M7 7v14M12 7v14M17 7v14M5.5 4.5h5M13.5 4.5h5"/>',
        'tennis'     => '<circle cx="12" cy="12" r="9.5"/><path d="M5.2 5.4c3.6 3 3.6 10.2 0 13.2M18.8 5.4c-3.6 3-3.6 10.2 0 13.2"/>',
        'rugby'      => '<ellipse cx="12" cy="12" rx="10" ry="6.2" transform="rotate(-45 12 12)"/><path d="M8.6 15.4c2.2-1.1 4.6-3.4 6.8-6.8"/>',
        'gymnastics' => '<path d="M2.5 8h19M4.5 8v3h15V8M7.5 11L5 20.5M16.5 11l2.5 9.5"/>',
    ];
    return '<svg ' . $a . '>' . ($p[$key] ?? $p['ticket']) . '</svg>';
}

/** The tile icon for a sub-category of a hub (by hub, with a few sports and genres picked out by name). */
function soCategoryTileIconFor($hub, $label) {
    $l = strtolower($label);
    foreach (['performance series' => 'music', 'racing' => 'flag', 'motorsport' => 'flag', 'nascar' => 'flag', 'golf' => 'golf', 'boxing' => 'boxing', 'rodeo' => 'rodeo',
              'wrestling' => 'wrestling', 'mixed martial' => 'mma', 'mma' => 'mma', 'ufc' => 'mma', 'volleyball' => 'volleyball', 'skating' => 'skate', 'olympic' => 'olympics',
              'lacrosse' => 'lacrosse', 'cricket' => 'cricket', 'tennis' => 'tennis', 'rugby' => 'rugby', 'gymnastic' => 'gymnastics',
              'baseball' => 'baseball', 'softball' => 'baseball', 'basketball' => 'basketball', 'hockey' => 'hockey', 'soccer' => 'soccer', 'football' => 'football', 'comedy' => 'smile', 'children' => 'family', 'family' => 'family', 'festival' => 'tent', 'hip hop' => 'mic', 'rap' => 'mic', 'soul' => 'mic'] as $k => $icon) {
        if (strpos($l, $k) !== false) return $icon;
    }
    return $hub === '/game-day-tickets' ? 'trophy' : ($hub === '/buy-broadway-tickets' ? 'masks' : 'music');
}

/**
 * The categories the grid opens with, in reading order (4 per row, 5 rows). Matched by name against the live category list
 * (soHubSubcategories()), so each links to its real category page; one with nothing on sale has no page and is left out.
 */
function soCategoryTileLead() {
    return [
        'Performance Series', 'Racing', 'Golf', 'Boxing',
        'Baseball', 'Rodeo', 'Wrestling', 'Mixed Martial Arts',
        'Basketball', 'Volleyball', 'Softball', 'Skating',
        'Hockey', 'Olympics', 'Lacrosse', 'Cricket',
        'Soccer', 'Tennis', 'Rugby', 'Gymnastics',
    ];
}

/** @return array<int,array{0:string,1:string,2:string}> [label, url, icon] */
function soCategoryTileList() {
    return [
        ['NFL', '/nfl-tickets', 'football'], ['Concerts', '/concert-tickets-for-sale', 'music'],
        ['NBA', '/nba-tickets', 'basketball'], ['Hip hop', '/hip-hop-tickets', 'mic'],
        ['NHL', '/nhl-tickets', 'hockey'], ['Country', '/country-music-tickets', 'music'],
        ['MLB', '/mlb-tickets', 'baseball'], ['Pop & rock', '/pop-rock-concert-tickets', 'music'],
        ['MLS', '/mls-tickets', 'soccer'], ['R&B and soul', '/rnb-soul-concert-tickets', 'mic'],
        ['All sports', '/game-day-tickets', 'trophy'], ['Latin music', '/latin-music-tickets', 'music'],
        ['Theater', '/buy-broadway-tickets', 'masks'], ['Electronic', '/electronic-music-tickets', 'music'],
        ['Comedy', '/comedy-show-tickets', 'smile'], ['Metal', '/metal-concert-tickets', 'music'],
        ['Festivals', '/upcoming-music-festivals', 'tent'], ['Jazz & blues', '/jazz-and-blues-tickets', 'music'],
        ['Family shows', '/category/' . soSlug('category', 'Children / Family', 1869), 'family'], ['Classical', '/classical-music-tickets', 'music'],
    ];
}

/**
 * @param array $o  title (heading text, '' for none), intro, class (extra CSS class), id (heading id)
 */
function soRenderCategoryTiles(array $o = []) {
    $o += ['title' => 'Browse by category', 'intro' => '', 'class' => '', 'id' => 'soCatTilesTitle'];
    $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    $cur = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $out = '<section class="so-cattiles ' . $h($o['class']) . '" aria-labelledby="' . $h($o['id']) . '"><div class="container">';
    if ($o['title'] !== '') $out .= '<h2 id="' . $h($o['id']) . '" class="so-cattiles__title">' . $h($o['title']) . '</h2>';
    if ($o['intro'] !== '') $out .= '<p class="so-cattiles__intro">' . $h($o['intro']) . '</p>';
    // The lead categories first (soCategoryTileLead(), in their 4 x 5 reading order), then the hand-picked list
    // (soCategoryTileList()), then every other sub-category the ticket API lists under concerts, sports and theater that has
    // tickets on sale (inc/category-tree.php). The grid is 5 rows high and scrolls sideways, so each column is written top to
    // bottom: the first 20 are placed column by column so that they read row by row.
    $norm = function ($v) { return preg_replace('/[^a-z0-9]+/', '', strtolower((string) $v)); };
    $live = [];   // normalised label => [label, url, icon], in the API's order
    if (function_exists('soHubSubcategories')) {
        foreach (array_keys(SO_HUB_ROOTS) as $hub) {
            foreach (soHubSubcategories($hub) as $sub) {
                $k = $norm($sub['label']);
                if ($k !== '' && !isset($live[$k])) $live[$k] = [$sub['label'], $sub['href'], soCategoryTileIconFor($hub, $sub['label'])];
            }
        }
    }
    $lead = [];
    foreach (soCategoryTileLead() as $name) {
        if (isset($live[$norm($name)])) $lead[] = $live[$norm($name)];
    }
    // Page one is always a full 4 x 5 when there are 20 categories: a lead category with nothing on sale is left out and the
    // next ones (the hand-picked list, then the rest) fill in, so the column-by-column placement never leaves a hole.
    $seen = [];
    $page1 = [];
    foreach (array_merge($lead, soCategoryTileList(), array_values($live)) as $t) {
        if (count($page1) >= 20) break;
        if (isset($seen[$t[1]])) continue;
        $seen[$t[1]] = true;
        $page1[] = $t;
    }
    $tiles = $page1;
    if (count($page1) === 20) {
        $columns = [];
        foreach ($page1 as $i => $t) { $columns[($i % 4) * 5 + intdiv($i, 4)] = $t; }
        ksort($columns);
        $tiles = array_values($columns);
    }
    foreach (array_merge(soCategoryTileList(), array_values($live)) as $t) {
        if (isset($seen[$t[1]])) continue;
        $seen[$t[1]] = true;
        $tiles[] = $t;
    }
    $arrow = function ($dir) { return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . ($dir === 'prev' ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7') . '"/></svg>'; };
    $out .= '<div class="so-cattiles__wrap" data-so-subs><button type="button" class="so-subcats__arrow so-subcats__arrow--prev" data-so-subs-prev aria-label="Previous categories" hidden>' . $arrow('prev') . '</button>';
    $out .= '<ul class="so-cattiles__grid" data-so-subs-track>';
    foreach ($tiles as [$label, $url, $icon]) {
        $here = $url === $cur;
        $out .= '<li><a class="so-cattile' . ($here ? ' is-current' : '') . '" href="' . $h($url) . '"' . ($here ? ' aria-current="page"' : '') . '>'
            . '<span class="so-cattile__icon">' . soCategoryTileIcon($icon) . '</span><span class="so-cattile__name">' . $h($label) . '</span>'
            . '<svg class="so-cattile__chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7"/></svg></a></li>';
    }
    $out .= '</ul><button type="button" class="so-subcats__arrow so-subcats__arrow--next" data-so-subs-next aria-label="More categories" hidden>' . $arrow('next') . '</button></div>';
    return $out . '</div></section>';
}
