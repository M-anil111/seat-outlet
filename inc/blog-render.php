<?php
/**
 * Blog article rendering: shortcodes an editor can type into a post, a table of contents built from the headings, and
 * the newsletter / ticket blocks that sit in every article.
 *
 * Shortcodes (put them on their own line in the post body in the admin panel):
 *   [events performer="Taylor Swift" limit="6" title="Taylor Swift tickets"]   live events for one performer
 *   [events category="concerts" limit="6"]                                     popular events (concerts|sports|theatre|festival)
 *   [events venue="Madison Square Garden" limit="6"]                           upcoming events at one venue (exact name, case ignored)
 *   [events city="New York" category="theatre" limit="6"]                      popular events in a city ("Austin, TX" also works); category is optional
 *   [newsletter title="..." text="..."]                                        email sign-up box
 *   [cta title="..." text="..." button="..." url="/concert-tickets-for-sale"]  call-to-action box
 *
 * "events" reads TicketNetwork's catalog (the same data as the rest of the site), so what shows is what is listed right
 * now. When nothing is listed it says so and offers the newsletter instead of showing an empty box.
 */

/** An image path or URL as an absolute https URL (link previews and structured data need one). Empty when there is no image. */
function soBlogAbsUrl($u) {
    $u = trim((string) $u);
    if ($u === '') return '';
    if (preg_match('#^https?://#i', $u)) return $u;
    if (substr($u, 0, 2) === '//') return 'https:' . $u;
    return rtrim(HOME_URL, '/') . '/' . ltrim($u, '/');
}

function soBlogH($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

/** Only internal paths and https links are allowed in a CTA button. */
function soBlogSafeUrl($url) {
    $url = trim((string) $url);
    if ($url !== '' && $url[0] === '/' && substr($url, 0, 2) !== '//') return $url;
    return preg_match('#^https://#i', $url) ? $url : '/buy-tickets-online';
}

/**
 * Email sign-up box: the shared soLeadForm (inc/leads.php, bound by js/lead-capture.js, posts to /ajax/subscribe.php).
 * Options: id, title, text, source (where it sits: blog, blog-end, blog-index), interest_type / interest_name (what the
 * article is about, so alerts can be targeted).
 */
function soBlogNewsletterBox(array $a = []) {
    $o = ['source' => preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($a['source'] ?? 'blog'))) ?: 'blog', 'class' => 'so-nl--blog'];
    foreach (['id', 'title', 'text', 'interest_type', 'interest_id', 'interest_name'] as $k) {
        if (isset($a[$k]) && $a[$k] !== '') $o[$k] = $a[$k];
    }
    return soLeadForm($o);
}

function soBlogCtaBox(array $a) {
    $title = $a['title'] ?? 'Find tickets';
    $text  = $a['text'] ?? '';
    $btn   = $a['button'] ?? 'Browse tickets';
    return '<div class="so-cta"><p class="so-cta__title">' . soBlogH($title) . '</p>' . ($text !== '' ? '<p>' . soBlogH($text) . '</p>' : '')
        . '<a class="so-cta__btn" href="' . soBlogH(soBlogSafeUrl($a['url'] ?? '')) . '">' . soBlogH($btn) . '</a></div>';
}

/** The best catalog row for a name: exact match first, otherwise the listed one with most tickets. Never throws. */
function soBlogPickByName(array $rows, string $name, string $stateAbbr = '') {
    $best = null; $bestScore = -1;
    foreach ($rows as $r) {
        $n = (string) ($r['text']['name'] ?? '');
        if ($n === '') continue;
        if ($stateAbbr !== '' && strcasecmp((string) ($r['stateProvince']['text']['abbr'] ?? ''), $stateAbbr) !== 0) continue;
        $score = (strcasecmp($n, $name) === 0 ? 1000000 : 0) + (int) ($r['_metadata']['ticketCount'] ?? 0);
        if ($score > $bestScore) { $best = $r; $bestScore = $score; }
    }
    return $best;
}

/** Events for a live block: [name, url|null, events[], link label]. Never throws. */
function soBlogLoadEvents(array $a) {
    $limit = max(1, min(12, (int) ($a['limit'] ?? 6)));
    $name = trim((string) ($a['performer'] ?? ''));
    $venue = trim((string) ($a['venue'] ?? ''));
    $city = trim((string) ($a['city'] ?? ''));
    $cat = strtolower((string) ($a['category'] ?? ''));
    $map = ['concerts' => TN_CATEGORY_PATH_CONCERTS, 'sports' => TN_CATEGORY_PATH_SPORTS, 'theatre' => TN_CATEGORY_PATH_THEATER, 'festival' => TN_CATEGORY_PATH_FESTIVAL];
    $sort = in_array($a['sort'] ?? '', ['popular', 'soonest', 'price'], true) ? $a['sort'] : 'popular';
    try {
        if ($name !== '') {
            $found = getTnPerformers(['filter' => "startswith(text/name,'" . tnEscapeFilterValue($name) . "')", 'perPage' => 8, 'sort' => 'text/name']);
            $pick = null;
            foreach ($found['results'] ?? [] as $p) {
                if (strcasecmp($p['text']['name'] ?? '', $name) === 0) { $pick = $p; break; }
            }
            if (!$pick) return [$name, null, [], $name . ' tickets'];
            $pname = $pick['text']['name'];
            [$endpoint, $params] = performerPageEventsSpec((int) $pick['id'], $limit);
            $data = tnRequest($endpoint, $params, 'GET', 600);
            return [$pname, '/artist/' . createSlug($pname, (int) $pick['id']), array_slice($data['results'] ?? [], 0, $limit), $pname . ' tickets'];
        }
        // Venue and city blocks: the catalog's venue / city search by name, then the events at that id (verified against the sandbox API).
        if ($venue !== '' || $city !== '') {
            $frag = []; $label = ''; $url = null; $all = '';
            if ($venue !== '') {
                $res = tnRequest('/catalog/v2/venues/', ['filter' => "startswith(text/name,'" . tnEscapeFilterValue($venue) . "')", 'perPage' => 12], 'GET', 3600);
                $v = soBlogPickByName($res['results'] ?? [], $venue);
                if (!$v || stripos((string) $v['text']['name'], $venue) !== 0) return [$venue, null, [], $venue . ' tickets'];
                $frag[] = 'venue/id eq ' . (int) $v['id'];
                $label = $v['text']['name']; $url = '/venue/' . createSlug($label, (int) $v['id']); $all = $label . ' tickets';
            } else {
                $abbr = '';
                $cname = $city;
                if (strpos($city, ',') !== false) { [$cname, $abbr] = array_map('trim', explode(',', $city, 2)); }
                $res = tnRequest('/catalog/v2/cities/', ['filter' => "startswith(text/name,'" . tnEscapeFilterValue($cname) . "') and country/alphaCode eq 'US'", 'perPage' => 12], 'GET', 3600);
                $c = soBlogPickByName($res['results'] ?? [], $cname, $abbr);
                if (!$c) return [$city, null, [], 'events in ' . $city];
                $frag[] = 'city/id eq ' . (int) $c['id'];
                $label = $c['text']['name']; $url = '/city/' . createSlug($label, (int) $c['id']); $all = 'events in ' . $label;
            }
            if (isset($map[$cat])) $frag[] = "startswith(defaultCategory/path, '" . tnEscapeFilterValue($map[$cat]) . "')";
            elseif (preg_match('/^\d{1,6}$/', $cat)) $frag[] = "contains(defaultCategory/path, '." . (int) $cat . ".')";   // a TicketNetwork category id, e.g. 2094 = Children / Family (concerts)
            $data = tnRequest('/catalog/v2/events/', locationListingParams(implode(' and ', $frag), $limit, 1, '', $sort), 'GET', 600);
            return [$label, $url, array_slice($data['results'] ?? [], 0, $limit), $all];
        }
        if (!isset($map[$cat])) return ['', null, [], 'tickets'];
        $data = tnRequest('/catalog/v2/events/', categoryListingParams($map[$cat], $limit, 1, '', 'popular'), 'GET', 600);
        $hub = ['concerts' => '/concert-tickets-for-sale', 'sports' => '/game-day-tickets', 'theatre' => '/buy-broadway-tickets', 'festival' => '/upcoming-music-festivals'][$cat];
        return ['', $hub, array_slice($data['results'] ?? [], 0, $limit), 'tickets'];
    } catch (Throwable $e) {
        return [$name !== '' ? $name : ($venue !== '' ? $venue : $city), null, [], 'tickets'];
    }
}

function soBlogEventsBlock(array $a) {
    [$pname, $allUrl, $events, $allLabel] = soBlogLoadEvents($a);
    $label = $pname !== '' ? $pname : ucfirst((string) ($a['category'] ?? 'event'));
    $catWord = ['concerts' => 'concert', 'sports' => 'sports', 'theatre' => 'theater', 'festival' => 'festival'][strtolower((string) ($a['category'] ?? ''))] ?? '';
    if (!empty($a['venue'])) $defaultTitle = 'Upcoming at ' . $pname;
    elseif (!empty($a['city'])) $defaultTitle = 'Popular ' . ($catWord !== '' ? $catWord . ' ' : '') . 'events in ' . $pname;
    else $defaultTitle = $pname !== '' ? $pname . ' tickets' : 'Popular ' . strtolower($label) . ' tickets right now';
    $title = $a['title'] ?? $defaultTitle;
    $out = '<section class="so-live"><p class="so-live__title">' . soBlogH($title) . '</p><p class="so-live__tag">Live from our ticket marketplace</p>';
    $rows = '';
    foreach ($events as $e) {
        $id = (int) ($e['id'] ?? 0);
        $nm = $e['text']['name'] ?? '';
        $ts = strtotime($e['date']['date'] ?? '');
        if ($id === 0 || $nm === '' || !$ts) continue;
        $where = trim(($e['venue']['text']['name'] ?? '') . ' - ' . trim(($e['city']['text']['name'] ?? '') . ', ' . ($e['stateProvince']['text']['abbr'] ?? ''), ', '), ' -');
        $price = $e['pricingInfo']['lowPrice']['text']['formatted'] ?? '';
        $rows .= '<li><a class="so-live__row" href="/event/' . soBlogH(createSlug($nm, $id)) . '" data-blog-post="' . soBlogH($GLOBALS['soBlogSlug'] ?? '') . '">'
            . '<span class="so-live__date"><b>' . soBlogH(strtoupper(date('M', $ts))) . '</b><i>' . soBlogH(date('j', $ts)) . '</i></span>'
            . '<span class="so-live__info"><strong>' . soBlogH($nm) . '</strong><small>' . soBlogH($where) . '</small></span>'
            . ($price !== '' ? '<span class="so-live__price">From ' . soBlogH($price) . '</span>' : '')
            . '<span class="so-live__go">Tickets</span></a></li>';
    }
    if ($rows === '') {
        $who = $pname !== '' ? $pname : 'These events';
        return $out . '<p class="so-live__empty">' . soBlogH($who) . ' has no dates with tickets listed right now. <a href="#subscribe">Join the newsletter</a> and we will email you when that changes.</p></section>';
    }
    $out .= '<ul class="so-live__list">' . $rows . '</ul>';
    if ($allUrl) $out .= '<a class="so-live__all" href="' . soBlogH($allUrl) . '">See all ' . soBlogH($allLabel) . '</a>';
    return $out . '<p class="so-live__note">Seat Outlet is a resale marketplace. Prices are set by sellers and can be above or below face value. Listings change often.</p></section>';
}

/** Replace the shortcodes in a post body with their HTML. */
function soBlogShortcodes($html) {
    return preg_replace_callback('/\[(events|newsletter|cta)((?:\s+[a-z_]+="[^"]*")*)\s*\]/i', function ($m) {
        $a = [];
        if (preg_match_all('/([a-z_]+)="([^"]*)"/i', $m[2], $pairs, PREG_SET_ORDER)) {
            foreach ($pairs as $p) $a[strtolower($p[1])] = html_entity_decode($p[2], ENT_QUOTES, 'UTF-8');
        }
        switch (strtolower($m[1])) {
            case 'events':     return soBlogEventsBlock($a);
            case 'newsletter': return soBlogNewsletterBox($a);
            default:           return soBlogCtaBox($a);
        }
    }, (string) $html);
}

/**
 * Give every h2/h3 an id, drop any hand-written contents box (it would duplicate the generated one), and put the
 * newsletter box before the middle section of a long article. Returns [html, toc] where toc is [[level, id, text], ...].
 */
function soBlogProcess($html, $addMidNewsletter = true, array $nlArgs = []) {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="so-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xp = new DOMXPath($doc);
    $root = $doc->getElementById('so-root');
    if (!$root) return [$html, []];

    foreach (iterator_to_array($xp->query('//nav[contains(concat(" ", normalize-space(@class), " "), " so-toc ")]')) as $old) {
        $old->parentNode->removeChild($old);
    }
    $toc = [];
    $used = [];
    $heads = iterator_to_array($xp->query('//h2|//h3'));
    foreach ($heads as $h) {
        $text = trim(preg_replace('/\s+/', ' ', $h->textContent));
        if ($text === '') continue;
        $id = $h->getAttribute('id');
        if ($id === '') {
            $id = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-') ?: 'section';
            $base = $id; $n = 2;
            while (isset($used[$id])) $id = $base . '-' . $n++;
            $h->setAttribute('id', $id);
        }
        $used[$id] = true;
        $toc[] = [strtolower($h->nodeName) === 'h2' ? 2 : 3, $id, $text];
    }
    $mid = '<!--so-mid-->';
    $h2s = array_values(array_filter($heads, function ($h) { return strtolower($h->nodeName) === 'h2' && trim($h->textContent) !== ''; }));
    $hasNl = $xp->query('//*[contains(@class,"so-nl")]')->length > 0;
    if ($addMidNewsletter && !$hasNl && count($h2s) >= 5) {
        $target = $h2s[(int) floor(count($h2s) / 2)];
        $target->parentNode->insertBefore($doc->createComment('so-mid'), $target);
    }
    $out = '';
    foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
    $out = soBlogUnentity($out);
    if ($addMidNewsletter && !$hasNl) $out = str_replace('<!--so-mid-->', soBlogNewsletterBox($nlArgs + ['source' => 'blog-mid']), $out);
    return [$out, count($toc) >= 3 ? $toc : []];
}

/** DOMDocument::saveHTML writes non-ASCII as numeric entities; turn those back into UTF-8 so the markup stays readable. */
function soBlogUnentity($s) {
    return preg_replace_callback('/&#(x[0-9a-f]+|\d+);/i', function ($m) {
        $cp = $m[1][0] === 'x' || $m[1][0] === 'X' ? hexdec(substr($m[1], 1)) : (int) $m[1];
        return $cp < 128 ? $m[0] : mb_convert_encoding('&#' . $cp . ';', 'UTF-8', 'HTML-ENTITIES');
    }, $s);
}

function soBlogTocHtml(array $toc) {
    if (!$toc) return '';
    $out = '<details class="so-art__toc" open><summary>Table of contents</summary><ol>';
    foreach ($toc as $t) {
        $out .= '<li class="so-art__toc' . ($t[0] === 3 ? '-sub' : '-top') . '"><a href="#' . soBlogH($t[1]) . '">' . soBlogH($t[2]) . '</a></li>';
    }
    return $out . '</ol></details>';
}

/** Where the "find tickets" band at the end of an article sends people, by category. */
function soBlogEndCta($category) {
    $map = [
        'Concerts & Tours' => ['Find concert tickets', 'Compare seats and prices for upcoming concerts and tours.', 'Browse concert tickets', '/concert-tickets-for-sale'],
        'City Guides'      => ['Find tickets in your city', 'See what is on in your city and compare seats before you go.', 'Browse events by city', '/city-events'],
        'Venues'           => ['Find tickets for your next show', 'Compare seats and prices for shows at the venues in this guide.', 'Browse concert tickets', '/concert-tickets-for-sale'],
        'Ticket Safety'    => ['Buy with a guarantee', 'Every order is backed by our 100% guarantee: valid tickets, delivery before the event, and a refund if the event is canceled.', 'See how the guarantee works', '/worry-free-guarantee'],
    ];
    $c = $map[$category] ?? ['Find tickets', 'Compare seats and prices for upcoming concerts, sports and theater.', 'Browse all tickets', '/buy-tickets-online'];
    return ['title' => $c[0], 'text' => $c[1], 'button' => $c[2], 'url' => $c[3]];
}

/** Pictures an editor can insert: the site's own images folder, without logos and icons. [{name, path, w, h}] */
function blogImageLibrary() {
    $out = [];
    foreach (glob(__DIR__ . '/../images/*.{webp,jpg,jpeg,png}', GLOB_BRACE) ?: [] as $f) {
        $name = basename($f);
        if (preg_match('/logo|favicon|fevicon|placeholder|^jm\.|icon|slider|adsense|lyft|patron|jimbeam|rambler|lite\.|bmi|gtn|electolit|moneyback|bbb/i', $name)) continue;
        $size = @getimagesize($f);
        if (!$size || $size[0] < 400) continue;
        $out[] = ['name' => $name, 'path' => '/images/' . $name, 'w' => $size[0], 'h' => $size[1]];
    }
    return $out;
}
