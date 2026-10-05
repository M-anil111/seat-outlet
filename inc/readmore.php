<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * "Read more" blocks. A long text stays whole in the HTML (search engines and visitors without JavaScript see all of it) and is clamped to a few
 * lines for visitors with JavaScript, with a button that opens it. The button only appears when the text is actually longer than the clamp.
 */

/** Wrap HTML in a clamped block with a Read more button. $id must be unique on the page. */
function soReadMoreBlock(string $innerHtml, string $id, int $lines = 4, string $more = 'Read more', string $less = 'Show less'): string {
    if (trim($innerHtml) === '') return '';
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $out = '<div class="so-clamp" data-so-clamp style="--so-lines:' . max(2, $lines) . '">'
        . '<div class="so-clamp__body" id="' . $e($id) . '">' . $innerHtml . '</div>'
        . '<button type="button" class="so-clamp__btn" hidden aria-expanded="false" aria-controls="' . $e($id) . '" data-more="' . $e($more) . '" data-less="' . $e($less) . '">' . $e($more) . '</button>'
        . '</div>';
    if (empty($GLOBALS['soClampScript'])) {
        $GLOBALS['soClampScript'] = true;
        $out .= '<script>(function(){function init(){document.querySelectorAll("[data-so-clamp]").forEach(function(b){var body=b.querySelector(".so-clamp__body"),btn=b.querySelector(".so-clamp__btn");if(!body||!btn||b.classList.contains("so-clamp--js"))return;b.classList.add("so-clamp--js");'
            . 'if(body.scrollHeight<=body.clientHeight+6){b.classList.remove("so-clamp--js");return;}btn.hidden=false;'
            . 'btn.addEventListener("click",function(){var open=!b.classList.contains("is-open");b.classList.toggle("is-open",open);btn.setAttribute("aria-expanded",open?"true":"false");btn.textContent=open?btn.dataset.less:btn.dataset.more;});});}'
            . 'if(document.readyState==="complete"){init();}else{window.addEventListener("load",init);}})();</script>';   // after the stylesheets (some load late), so the height check is true
    }
    return $out;
}

/**
 * A short, data-driven "About <place>" text for city, state, country and venue pages. Every sentence comes from the events the page holds
 * (counts, dates, kinds, top performers and venues); nothing is guessed. Returns '' when there is nothing true to say.
 */
function soPlaceAboutHtml(string $kind, string $name, array $events, int $total, array $top, ?array $cheap): string {
    if ($total <= 0 || !$events) return '';
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $in = $kind === 'venue' ? 'at' : 'in';
    $kinds = ['concerts' => 0, 'sports' => 0, 'theater' => 0];
    $first = null; $last = null;
    foreach ($events as $ev) {
        $path = (string) ($ev['defaultCategory']['path'] ?? '');
        if (strpos($path, '.1986.') !== false) $kinds['concerts']++;
        elseif (strpos($path, '.1988.') !== false) $kinds['sports']++;
        elseif (strpos($path, '.1989.') !== false) $kinds['theater']++;
        $t = strtotime((string) ($ev['date']['date'] ?? ''));
        if ($t) { $first = $first === null ? $t : min($first, $t); $last = $last === null ? $t : max($last, $t); }
    }
    $parts = [];
    foreach ($kinds as $k => $n) { if ($n > 0) $parts[] = soCountWord($n, $k === 'concerts' ? 'concert' : ($k === 'sports' ? 'sports event' : 'theater show')); }
    $p1 = $e($name) . ' has ' . soCountWord($total, 'upcoming event') . ' on Seat Outlet right now';
    if ($first && $last) $p1 .= $first === $last ? ', on ' . date('F j, Y', $first) : ', with dates running from ' . date('F j', $first) . ' to ' . date('F j, Y', $last);
    $p1 .= '.';
    if ($parts) $p1 .= ' Among the dates listed on this page: ' . $e(implode(', ', $parts)) . '.';
    if ($cheap && !empty($cheap['formatted'])) $p1 .= ' The lowest listed price at the moment is ' . $e($cheap['formatted']) . ', and prices change as sellers add and remove tickets.';
    $html = '<p>' . $p1 . '</p>';
    $links = [];
    foreach (array_slice($top['performers'] ?? [], 0, 5) as $p) { $links[] = '<a href="/artist/' . $e(soSlug('performer', $p['name'], $p['id'])) . '">' . $e($p['name']) . '</a>'; }
    if ($links) $html .= '<p>Names with dates ' . $in . ' ' . $e($name) . ' include ' . implode(', ', $links) . '. Open any of them to see every date and compare seats.</p>';
    if ($kind !== 'venue') {
        $vl = [];
        foreach (array_slice($top['venues'] ?? [], 0, 5) as $v) { $vl[] = '<a href="/venue/' . $e(soSlug('venue', $v['name'], $v['id'])) . '">' . $e($v['name']) . '</a>'; }
        if ($vl) $html .= '<p>Venues with upcoming events: ' . implode(', ', $vl) . '.</p>';
    }
    $html .= '<p>To buy, pick a date, choose how many tickets you need and compare sections and prices on the seat map. Every order is covered by our <a href="/worry-free-guarantee">buyer guarantee</a>, and orders are fulfilled through TicketNetwork.</p>';
    return $html;
}

/**
 * "<Performer> dates at a glance" for a performer page: counts, date range, cities and lowest listed price, all from the events the page holds.
 * Returns '' when there are no events. Shown behind Read more so the page stays short.
 */
function soPerformerGlanceHtml(string $name, array $events): string {
    if (!$events) return '';
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $cities = []; $first = null; $last = null; $low = null; $lowFmt = '';
    foreach ($events as $ev) {
        $c = function_exists('soPlaceLabel') ? trim((string) soPlaceLabel($ev)) : '';
        if ($c !== '') $cities[$c] = ($cities[$c] ?? 0) + 1;
        $t = strtotime((string) ($ev['date']['date'] ?? ''));
        if ($t) { $first = $first === null ? $t : min($first, $t); $last = $last === null ? $t : max($last, $t); }
        $pv = $ev['pricingInfo']['lowPrice']['value'] ?? null; $pf = (string) ($ev['pricingInfo']['lowPrice']['text']['formatted'] ?? '');
        if (is_numeric($pv) && $pv > 0 && ($low === null || $pv < $low)) { $low = (float) $pv; $lowFmt = $pf; }
    }
    arsort($cities);
    $n = count($events);
    $p = '<p>' . $e($name) . ' has ' . soCountWord($n, 'date') . ' on sale at Seat Outlet'
        . ($first && $last ? ($first === $last ? ', on ' . date('F j, Y', $first) : ', from ' . date('F j', $first) . ' to ' . date('F j, Y', $last)) : '') . '.';
    if ($cities) $p .= ' Dates are in ' . soCountWord(count($cities), 'city', 'cities') . ', most often ' . $e(array_key_first($cities)) . '.';
    if ($lowFmt !== '') $p .= ' The lowest price listed right now is ' . $e($lowFmt) . '; prices change as sellers add and remove tickets.';
    $p .= '</p><p>Open a date to see the seat map and compare sections and prices. Each order is covered by our <a href="/worry-free-guarantee">buyer guarantee</a>, and orders are fulfilled through TicketNetwork. Set a price alert on a date to get an email if the price drops.</p>';
    return $p;
}
