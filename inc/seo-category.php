<?php
/**
 * Data-driven SEO content for category / genre pages (/hip-hop-tickets, /nba-tickets, /category/<name>-<id>).
 *
 * Everything that states a fact about listings (artists, venues, cities, prices, next date) comes from the live
 * TicketNetwork catalog, cached for 30 minutes, so the copy stays true as inventory changes and each genre page reads
 * differently. The only hand-written genre facts live in soCategoryProfile() and were checked against the cited sources.
 */

/** One cached catalog read per category: popular events (for who/where) plus the soonest event (for the next date). */
function soCategoryData($id) {
    $id = (int) $id;
    $out = ['total' => 0, 'artists' => [], 'venues' => [], 'cities' => [], 'min' => null, 'next' => null, 'path' => ''];
    try {
        $filter = "contains(defaultCategory/path, '." . $id . ".') and country/alphaCode eq 'US'";
        $r = tnRequest('/catalog/v2/events/', locationListingParams($filter, 100, 1, '', 'popular'), 'GET', 1800);
        $out['total'] = (int) ($r['totalCount'] ?? count($r['results'] ?? []));
        $artists = []; $venues = []; $cities = [];
        $out['path'] = (string) ($r['results'][0]['defaultCategory']['path'] ?? '');
        foreach ($r['results'] ?? [] as $i => $e) {
            $p = $e['performers'][0] ?? null;
            if ($p && !empty($p['id']) && !empty($p['name'])) {
                $k = (int) $p['id'];
                $artists[$k] = $artists[$k] ?? ['id' => $k, 'name' => $p['name'], 'n' => 0, 'first' => $i, 'cat' => $e['defaultCategory'] ?? []];
                $artists[$k]['n']++;
            }
            $v = $e['venue'] ?? null;
            if ($v && !empty($v['id']) && !empty($v['text']['name'])) {
                $k = (int) $v['id'];
                $venues[$k] = $venues[$k] ?? ['id' => $k, 'name' => $v['text']['name'], 'city' => trim(($e['city']['text']['name'] ?? '') . ', ' . ($e['stateProvince']['text']['abbr'] ?? ''), ', '), 'n' => 0];
                $venues[$k]['n']++;
            }
            $c = $e['city'] ?? null;
            if ($c && !empty($c['id']) && !empty($c['text']['name'])) {
                $k = (int) $c['id'];
                $cities[$k] = $cities[$k] ?? ['id' => $k, 'label' => trim($c['text']['name'] . ', ' . ($e['stateProvince']['text']['abbr'] ?? ''), ', '), 'n' => 0];
                $cities[$k]['n']++;
            }
        }
        $by = function ($a, $b) { return [$b['n'], $a['first'] ?? 0] <=> [$a['n'], $b['first'] ?? 0]; };
        uasort($artists, $by); uasort($venues, function ($a, $b) { return $b['n'] <=> $a['n']; }); uasort($cities, function ($a, $b) { return $b['n'] <=> $a['n']; });
        $out['artists'] = array_slice(array_values($artists), 0, 12);
        $out['venues']  = array_slice(array_values($venues), 0, 8);
        $out['cities']  = array_slice(array_values($cities), 0, 10);

        // The cheapest listed price across the whole category (the 100-event sample above is only the popular ones).
        $cheap = tnRequest('/catalog/v2/events/', locationListingParams($filter, 1, 1, '', 'price'), 'GET', 1800);
        $cv = $cheap['results'][0]['pricingInfo']['lowPrice']['value'] ?? null;
        if (is_numeric($cv) && $cv > 0) $out['min'] = (float) $cv;

        $soon = tnRequest('/catalog/v2/events/', locationListingParams($filter, 1, 1, '', 'soonest'), 'GET', 1800);
        $e = $soon['results'][0] ?? null;
        if ($e && ($ts = strtotime($e['date']['date'] ?? ''))) {
            $out['next'] = ['name' => $e['text']['name'] ?? '', 'ts' => $ts, 'venue' => $e['venue']['text']['name'] ?? '', 'city' => trim(($e['city']['text']['name'] ?? '') . ', ' . ($e['stateProvince']['text']['abbr'] ?? ''), ', ')];
        }
    } catch (Throwable $ex) {
        // Leave the arrays empty: the page then simply shows less data-driven text.
    }
    return $out;
}

/** Two opening sentences of the category's Wikipedia summary (inc/category-facts.json, refreshed by tools/fetch-category-facts.php). */
function soCategoryFact($id) {
    static $all = null;
    if ($all === null) $all = json_decode((string) @file_get_contents(__DIR__ . '/category-facts.json'), true) ?: [];
    $f = $all[(int) $id] ?? null;
    if (!$f || empty($f['extract'])) return null;
    $text = '';
    foreach (preg_split('/(?<=[.!?])\s+(?=[A-Z"])/', trim($f['extract'])) as $i => $sent) {
        if ($i >= 2 || ($text !== '' && strlen($text . ' ' . $sent) > 420)) break;
        $text = trim($text . ' ' . $sent);
    }
    return ['text' => $text, 'title' => $f['title'], 'url' => $f['url']];
}

/** Hand-written, source-checked facts for genres that have a profile. */
function soCategoryProfile($key) {
    if ($key === 'hiphop') {
        return [
            'about_title' => 'About hip hop live shows',
            'about' => [
                'Hip hop emerged in the early 1970s in New York City, created by African-American and Afro-Caribbean communities. Its four principal elements are rapping, DJing, breakdancing and graffiti art. The party DJ Kool Herc played at 1520 Sedgwick Avenue in the Bronx on August 11, 1973 is widely cited as the moment hip hop began.',
                'On stage that history shows up in how a hip hop concert is built: a DJ or live band behind the rapper, call-and-response with the crowd, and a mix of arena tours, festival slots and club nights. Where you sit changes the night. Floor tickets put you closest to the stage and the energy of the crowd, while lower-level seats give a fuller view of staging and screens.',
            ],
            'sources' => [
                ['Hip-hop (Wikipedia)', 'https://en.wikipedia.org/wiki/Hip-hop'],
                ['1520 Sedgwick Avenue (Wikipedia)', 'https://en.wikipedia.org/wiki/1520_Sedgwick_Avenue'],
                ['Billboard Hip-Hop/R&B Songs chart', 'https://www.billboard.com/charts/r-b-hip-hop-songs/'],
                ['The Recording Academy (Grammy Awards)', 'https://www.recordingacademy.com/'],
            ],
            'blog' => [['Best concert venues in the US', '/blog/best-concert-venues-in-the-us'], ['Upcoming concert tours', '/blog/upcoming-concert-tours'], ['How to avoid ticket scams', '/blog/how-to-avoid-ticket-scams']],
        ];
    }
    return null;
}

function soCatH($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function soCatList(array $names, $max = 3) {
    $names = array_values(array_filter(array_slice($names, 0, $max)));
    $n = count($names);
    if ($n === 0) return '';
    if ($n === 1) return $names[0];
    return implode(', ', array_slice($names, 0, $n - 1)) . ' and ' . $names[$n - 1];
}

/**
 * @param array $cfg ['label','long','kind','profile','url'] ('label' like "Hip Hop", 'long' like "hip hop and rap")
 * @return array ['html' => string, 'faqs' => [[question, answer]], 'description' => string]
 */
function soCategorySeo(array $cfg, array $d) {
    $label = $cfg['label']; $long = $cfg['long']; $kind = $cfg['kind'] ?? 'other';
    if ($kind === 'other') {   // categories without a registry entry: infer the family from the TicketNetwork category path
        if (strpos($d['path'] ?? '', '.1988.') !== false) $kind = 'sports';
        elseif (strpos($d['path'] ?? '', '.1986.') !== false) $kind = 'concerts';
    }
    $lbl = soListingInline($label);   // acronyms (NBA, MLB, R&B) keep their capitals inside sentences
    $kw = $lbl . ' tickets';
    $unit = $kind === 'sports' ? 'games' : ($kind === 'concerts' ? 'concerts' : 'shows');
    $cityPrefix = $kind === 'sports' ? 'sports-city' : ($kind === 'concerts' ? 'concerts-city' : 'city');
    $hub = $kind === 'sports' ? ['/game-day-tickets', 'game day tickets'] : ($kind === 'concerts' ? ['/concert-tickets-for-sale', 'concert tickets for sale'] : ['/buy-tickets-online', 'tickets for every event']);
    $profile = soCategoryProfile($cfg['profile'] ?? '');
    $cityNames = array_map(function ($c) { return $c['label']; }, $d['cities']);
    $topCities = soCatList($cityNames, 3);
    $artistNames = array_map(function ($a) { return $a['name']; }, $d['artists']);
    $venueNames = array_map(function ($v) { return $v['name']; }, $d['venues']);
    $price = $d['min'] !== null ? '$' . rtrim(rtrim(number_format($d['min'], 2), '0'), '.') : '';
    $total = (int) $d['total'];

    $h = 'soCatH';
    $o = '<section class="so-seo-copy so-cseo"><div class="container"><div class="so-cseo__wrap">';
    $focus = !empty($cfg['slug']) && function_exists('soGenreFocus') ? soGenreFocus($cfg['slug']) : null;   // keyword-targeted copy, inc/genre-focus.php
    if ($focus) $o .= $focus['lead'];

    // 1. Artists with upcoming shows
    $o .= '<h2>' . $h($label) . ' tickets: ' . ($kind === 'sports' ? 'teams' : 'artists') . ' with upcoming ' . $h($unit) . '</h2>';
    $o .= '<p>Looking for ' . $h($kw) . '? Seat Outlet lets you compare seats and prices for ' . ($total > 0 ? number_format($total) . ' upcoming ' . $h($long) . ($total === 1 ? ' event' : ' events') : 'upcoming ' . $h($long) . ' events')
        . ($topCities !== '' ? ' in ' . $h($topCities) . ' and more' : '') . ($price !== '' ? ', with tickets listed from ' . $h($price) : '')
        . '. Choose ' . ($kind === 'sports' ? 'a team' : 'an artist') . ', venue or city, pick your seats on the map and check out securely with our <a href="/worry-free-guarantee">100% guarantee</a>.</p>';
    if ($d['artists']) {
        // Acts with a real, licensed picture get a photo tile. The rest are a plain text list: a grey circle with two letters says
        // nothing, and a wall of them made the page long and empty.
        $withImg = []; $noImg = [];
        foreach ($d['artists'] as $a) {
            $im = getEntityImage(imageEntityTypeForPerformer($a['cat'] ?: []), $a['name'], ['category' => $a['cat'] ?: [], 'resolve' => false]);
            if (!empty($im['real']) && !empty($im['url'])) { $withImg[] = [$a, $im['url']]; } else { $noImg[] = $a; }
        }
        if ($withImg) {
            $o .= '<ul class="so-cseo__artists">';
            foreach ($withImg as [$a, $img]) {
                $o .= '<li><a href="/artist/' . $h(soSlug('performer', $a['name'], $a['id'])) . '">'
                    . '<img src="' . $h($img) . '" alt="' . $h($a['name'] . ' ' . $lbl . ' tickets') . '" width="96" height="96" loading="lazy" decoding="async">'
                    . '<span>' . $h($a['name']) . ' tickets</span></a></li>';
            }
            $o .= '</ul>';
        }
        if ($noImg) {
            $o .= ($withImg ? '<h3 class="so-cseo__sub">More ' . $h($lbl) . ' ' . ($kind === 'sports' ? 'teams' : 'artists') . ' on sale</h3>' : '')
                . '<div class="so-cseo__chips so-cseo__chips--scroll so-cseo__chips--names" role="region" aria-label="' . $h($label) . ' ' . ($kind === 'sports' ? 'teams' : 'artists') . ', scrollable" tabindex="0">';
            foreach ($noImg as $a) {
                $o .= '<a class="so-linkchip" href="/artist/' . $h(soSlug('performer', $a['name'], $a['id'])) . '">' . $h($a['name']) . '</a>';
            }
            $o .= '</div>';
        }
    }

    // 2. Venues (from the popular events in the catalog; no per-venue counts are claimed)
    if ($d['venues']) {
        $vCities = [];
        foreach ($d['venues'] as $v) { if ($v['city'] !== '' && !in_array($v['city'], $vCities, true)) $vCities[] = $v['city']; }
        $vWhere = soCatList($vCities, 3);
        $o .= '<h2>Best venues for ' . $h($long) . ' ' . $h($unit) . '</h2>';
        $o .= '<p>These venues are hosting some of the most popular ' . $h($long) . ' events right now' . ($vWhere !== '' ? ', including ' . ($vCities && count($vCities) > 1 ? 'venues in ' : 'a venue in ') . $h($vWhere) : '') . '. Each venue page shows every upcoming date and seat options for that building.</p><ul class="so-cseo__venues">';
        foreach ($d['venues'] as $v) {
            $o .= '<li><a href="/venue/' . $h(soVenueSlug($v['name'], $v['id'], $v['city'])) . '"><strong>' . $h($v['name']) . ' tickets</strong><span>' . $h($v['city']) . '</span></a></li>';
        }
        $o .= '</ul>';
    }

    // 3. Cities. The city pages list every event of that family in the city (all concerts, all sports...), not only this genre,
    //    so the link text says exactly that instead of promising a genre-filtered view.
    if ($d['cities']) {
        $cityWord = $kind === 'sports' ? 'Sports' : ($kind === 'concerts' ? 'Concerts' : 'Events');
        $o .= '<h2>' . $h($label) . ' ' . $h($unit) . ' by city</h2><p>These cities have the most ' . $h($long) . ' events on sale right now. Each city page lists every upcoming ' . ($kind === 'sports' ? 'sporting event' : ($kind === 'concerts' ? 'concert' : 'event')) . ' there, not only ' . $h($long) . '.</p><div class="so-cseo__chips so-cseo__chips--scroll" role="region" aria-label="' . $h($label) . ' cities, scrollable" tabindex="0">';
        foreach ($d['cities'] as $c) {
            $o .= '<a class="so-linkchip" href="/' . $cityPrefix . '/' . $h(soSlug('city', $c['label'], $c['id'])) . '">' . $h($cityWord) . ' in ' . $h($c['label']) . '</a>';
        }
        $o .= '</div>';
    }

    // 4. About / what to know
    if ($profile) {
        $o .= '<h2>' . $h($profile['about_title']) . '</h2>';
        foreach ($profile['about'] as $p) $o .= '<p>' . $h($p) . '</p>';
    } elseif (($fact = soCategoryFact($cfg['id'] ?? 0)) !== null) {
        $o .= '<h2>About ' . $h($lbl) . ' ' . $h($unit) . '</h2><p>' . $h($fact['text']) . '</p>'
            . '<p class="so-cseo__src">Source: <a href="' . $h($fact['url']) . '" target="_blank" rel="noopener">' . $h($fact['title']) . ' (Wikipedia)</a>, text available under CC BY-SA 4.0.</p>';
    }
    if (!$profile) {
        $o .= '<h2>What to know before you buy ' . $h($kw) . '</h2>'
            . '<p>Seat Outlet is a resale marketplace, so ' . $h($long) . ' ticket prices are set by sellers and can be above or below face value. Prices move with demand, the artist or team, the venue and how close the date is, so compare a few sections before you decide.</p>'
            . '<p>Seat maps differ from building to building. Use the map on each event page to compare sections, rows and total price, and check the venue\'s own page for doors time and entry rules before you go.</p>';
    }

    // Hand-written sections for the pages tracked for ranking (inc/genre-focus.php)
    foreach ($focus['sections'] ?? [] as [$fh, $fb]) $o .= '<h2>' . $h($fh) . '</h2>' . $fb;

    // 5. How to buy
    $o .= '<h2>How to buy ' . $h($kw) . '</h2><ol class="so-steps"><li><span><strong>Pick an event.</strong> Choose a date, city or venue from the list above or from the <a href="' . $h($hub[0]) . '">' . $h($hub[1]) . '</a> page.</span></li>'
        . '<li><span><strong>Choose your seats.</strong> Select how many tickets you need, then compare sections, rows and prices on the map.</span></li>'
        . '<li><span><strong>Check out securely.</strong> Every order is covered by our <a href="/ticket-buyer-protection">buyer protection</a>. Read <a href="/how-to-buy-tickets-online">how to buy tickets online</a> if it is your first time.</span></li></ol>';

    // FAQs: built from real data, plain answers
    $faqs = [];
    $faqs[] = ['question' => 'How much are ' . $kw . '?', 'answer' => ($price !== '' ? 'As of today, ' . $kw . ' on Seat Outlet start from ' . $price . ' for the lowest-priced listing across all ' . ($total > 0 ? number_format($total) . ' ' : '') . 'events on sale. ' : '') . 'Prices are set by sellers and vary by ' . ($kind === 'sports' ? 'team, opponent' : 'artist') . ', venue, seat location and date, and they can be above or below face value. Compare sections on the seat map to find the price that fits.'];
    if ($d['next']) {
        $n = $d['next'];
        $faqs[] = ['question' => 'When is the next ' . $lbl . ' ' . rtrim($unit, 's') . '?', 'answer' => 'The soonest ' . $long . ' event listed right now is ' . $n['name'] . ' on ' . date('l, F j, Y', $n['ts']) . ($n['venue'] !== '' ? ' at ' . $n['venue'] : '') . ($n['city'] !== '' ? ' in ' . $n['city'] : '') . '. Dates and inventory change often, so check the event page for the latest.'];
    }
    if ($artistNames) {
        $faqs[] = ['question' => 'Which ' . ($kind === 'sports' ? 'teams' : 'artists') . ' have ' . $kw . ' available?', 'answer' => 'Popular ' . $long . ' listings right now include ' . soCatList($artistNames, 6) . '. Open any name above to see every date and the seats on sale.'];
    }
    if ($venueNames) {
        $faqs[] = ['question' => 'Where are the best places to see ' . $long . ' live?', 'answer' => 'Venues hosting popular ' . $long . ' events right now include ' . soCatList($venueNames, 4) . '. Each venue page lists its upcoming dates.'];
    }
    if ($topCities !== '') {
        $faqs[] = ['question' => 'How do I find ' . $long . ' ' . $unit . ' near me?', 'answer' => 'Set your location at the top of the listing to see the closest events first, nearest to farthest. Popular right now in ' . soCatList($cityNames, 5) . '.'];
    }
    $faqs[] = ['question' => 'Are ' . $kw . ' on Seat Outlet legit?', 'answer' => 'Yes. Every order is covered by our 100% guarantee: valid tickets, delivery before the event, and a refund if the event is canceled and not rescheduled. Seat Outlet is a resale marketplace, not the venue box office, so prices can be above or below face value.'];
    $faqs[] = ['question' => 'Can I buy ' . $kw . ' at the last minute?', 'answer' => 'Yes, as long as tickets are still listed. Listings change daily, and some events have tickets available right up to the start. Use the date filter to see events happening this week.'];

    foreach ($focus['faqs'] ?? [] as [$fq, $fa]) $faqs[] = ['question' => $fq, 'answer' => $fa];

    $o .= '<h2>' . $h($label) . ' tickets FAQ</h2><div class="so-cseo__faq so-qa">';
    foreach ($faqs as $f) $o .= '<details class="so-faq" name="so-qa-cat"><summary><h3 style="display:inline;font:inherit;margin:0">' . $h($f['question']) . '</h3></summary><p>' . $h($f['answer']) . '</p></details>';
    $o .= '</div>';

    // 6. Keep exploring: internal + external links
    $o .= '<h2>Keep exploring</h2><ul class="so-cseo__links"><li><a href="' . $h($hub[0]) . '">Browse ' . $h($hub[1]) . '</a></li><li><a href="/city-events">Events by city</a></li><li><a href="/all-artists-and-teams">All artists and teams</a></li><li><a href="/ticket-deals">Ticket deals</a></li>';
    foreach ($profile['blog'] ?? [] as $b) $o .= '<li><a href="' . $h($b[1]) . '">' . $h($b[0]) . '</a></li>';
    $o .= '</ul>';
    if (!empty($profile['sources'])) {
        $o .= '<p class="so-cseo__src">Sources and further reading: ';
        $links = [];
        foreach ($profile['sources'] as $s) $links[] = '<a href="' . $h($s[1]) . '" target="_blank" rel="noopener">' . $h($s[0]) . '</a>';
        $o .= implode(' &middot; ', $links) . '</p>';
    }
    $o .= '<p class="so-cseo__note">Seat Outlet is a resale marketplace. Prices are set by sellers and can be above or below face value. Listings and prices change often; confirm details on the event page before you buy.</p>';
    $o .= '</div></div></section>';

    $two = soCatList($cityNames, 2);
    $desc = 'Compare ' . $kw . ($total > 0 ? ' for ' . number_format($total) . ($total === 1 ? ' upcoming event' : ' upcoming events') : '') . ($two !== '' ? ' in ' . $two . ' and more' : '') . '. See dates, venues and prices, backed by our 100% guarantee.';
    if (strlen($desc) > 158) $desc = 'Compare ' . $kw . ($total > 0 ? ' for ' . number_format($total) . ($total === 1 ? ' upcoming event' : ' upcoming events') : '') . '. See dates, venues and prices, backed by our 100% guarantee.';
    return ['html' => $o, 'faqs' => $faqs, 'description' => $desc];
}
