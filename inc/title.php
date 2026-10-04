<?php
// Include-only file: answer 404 if it is requested directly over the web.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Page title rules, in one place with no dependencies (so CI can load it without a database): the brand, soNormalizeTitle(),
 * soTitle() and seoClampTitle(). functions.php requires this file.
 */

/**
 * The brand at the end of every page title, joined with a plain word ("Dallas Mavericks Tickets 2026 Schedule & Prices at Seat Outlet").
 * Site rule: no page title contains a pipe, a hyphen used as a separator, a colon or any dash. soNormalizeTitle() enforces it for every
 * title that reaches the page (templates, the keyword plan, admin page rules and blog meta titles), and tools/check-seo-titles.php checks the plan.
 */
const SO_TITLE_BRAND = " at Seat Outlet";

/** Words a trimmed title must not end on ("... Comedy Club at" reads as broken). */
const SO_TITLE_DANGLING = ['at', 'of', 'the', 'and', 'in', 'for', 'to', 'with', 'a', 'an', 'on', 'by', 'or', '&', 'vs', 'from'];

/**
 * One title in its final form. Separators ("|", "-", en and em dashes, ":") become a space, an old brand suffix is removed and the brand
 * is added once, unless the title already names it ("About Seat Outlet"). Hyphens inside words (Winston-Salem, Hip-Hop, R&B) stay.
 */
function soNormalizeTitle($title) {
    $t = trim(preg_replace('/\s+/u', ' ', (string) $title));
    if ($t === '') return $t;
    $t = preg_replace('/\s*[|\x{2013}\x{2014}:-]\s*Seat Outlet(?: Network| Blog)?$/u', '', $t);   // "... | Seat Outlet", "...—Seat Outlet"
    $t = preg_replace('/\s+at Seat Outlet$/u', '', $t);
    $t = preg_replace('/\s*[|\x{2013}\x{2014}]\s*/u', ' ', $t);                                  // pipe, en dash, em dash
    $t = preg_replace('/\s+-\s+/u', ' ', $t);                                                      // spaced hyphen
    $t = preg_replace('/:(?=\s|$)/u', '', $t);                                                      // colon, but not "7:30"
    $t = str_replace(["\u{2026}", '...'], '', $t);
    $t = trim(preg_replace('/\s+/u', ' ', $t), " ,;");
    if ($t === '') return $t;
    return mb_stripos($t, 'Seat Outlet') !== false ? $t : $t . SO_TITLE_BRAND;
}

/**
 * First candidate that fits a search result with the brand (59 characters at most). Candidates are titles without the brand,
 * most specific first; the last one is shortened at a word boundary (keeping "Tickets") when nothing fits.
 */
function soTitle(...$candidates) {
    $candidates = array_values(array_filter(array_map('strval', $candidates), 'strlen'));
    foreach ($candidates as $c) {
        $full = soNormalizeTitle($c);
        if (mb_strlen($full) <= 59) return $full;
    }
    return seoClampTitle(soNormalizeTitle((string) end($candidates)));
}

/** Shorten a title to $max characters without an ellipsis: whole words only, never a dangling "at", and "Tickets" is always kept. */
function seoClampTitle($title, $max = 59) {
    $title = soNormalizeTitle($title);
    if (mb_strlen($title) <= $max) return $title;
    $brand = mb_substr($title, -mb_strlen(SO_TITLE_BRAND)) === SO_TITLE_BRAND ? SO_TITLE_BRAND : '';
    $base = $brand !== '' ? mb_substr($title, 0, -mb_strlen($brand)) : $title;
    $tail = '';
    if (preg_match('/^(.*\S)(\s+Tickets)$/u', $base, $m)) { $base = $m[1]; $tail = $m[2]; }   // the search word survives the trim
    $room = $max - mb_strlen($brand) - mb_strlen($tail);
    if (mb_strlen($base) > $room) {
        $cut = mb_substr($base, 0, $room);
        if (mb_substr($base, $room, 1) !== ' ') {                                                 // cut landed inside a word: back up to a word end
            $sp = mb_strrpos($cut, ' ');
            if ($sp !== false && $sp > $room * 0.4) $cut = mb_substr($cut, 0, $sp);
        }
        $words = preg_split('/\s+/u', trim($cut, " ,;"));
        while (count($words) > 1 && in_array(mb_strtolower(end($words)), SO_TITLE_DANGLING, true)) array_pop($words);
        $base = rtrim(implode(' ', $words), " ,;");
    }
    return $base . $tail . $brand;
}
