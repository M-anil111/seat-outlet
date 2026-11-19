<?php
/**
 * CI: the SE Ranking audit of 12 Nov 2026 (seatoutlet.com, audit 603554548) stays fixed.
 * Covers what can be checked without a database or the ticket API: titles, H1, descriptions of same-day showings, image alt text,
 * anchor text, external link targets and the category page's name fallback. The listing filters the bulk copy answers are covered by
 * tools/test-tn-bulk-read.php, the stored slug name by tools/test-slugs.php.
 * Run: php tools/test-seranking-nov12.php
 */
require_once __DIR__ . '/../inc/cli-guard.php';
require_once __DIR__ . '/../inc/title.php';
require_once __DIR__ . '/../inc/page-spec.php';
if (!function_exists('seoClampDescription')) { function seoClampDescription($d, $max = 155) { return mb_strlen($d) <= $max ? $d : mb_substr($d, 0, $max); } }   // functions.php needs the database

$fail = 0;
function srCheck(string $name, bool $ok, string $detail = ''): void { global $fail; if (!$ok) { echo "FAIL $name" . ($detail !== '' ? ": $detail" : '') . "\n"; $fail++; } }
function srSrc(string $rel): string { return (string) file_get_contents(__DIR__ . '/../' . $rel); }

// ---- title_long: no page title over 65 characters, whatever the page type allows
srCheck('one site-wide title limit of 65', defined('SO_TITLE_MAX') && SO_TITLE_MAX === 65);
$header = srSrc('header.php');
srCheck('header clamps page titles to the site limit even when a page allows more', strpos($header, 'min(SO_TITLE_MAX') !== false);
srCheck('header clamps admin page-rule titles too', (bool) preg_match('/seoClampTitle\(\s*\(string\)\s*\$pageRule\[.meta_title.\]\s*,\s*SO_TITLE_MAX\s*\)/', $header));
foreach ([
    'Buy Kenny Chesney Concert Tickets in Minneapolis, MN at Seat Outlet',
    'Buy Slipknot Concert Tickets in Washington, DC 2026 at Seat Outlet',
    'Shaftman Performance Hall at Jefferson Center Tickets at Seat Outlet',
] as $t) {
    $c = seoClampTitle($t, SO_TITLE_MAX);
    srCheck("clamped to 65 and keeps Tickets: $t", mb_strlen($c) <= 65 && mb_strpos($c, 'Tickets') !== false, $c);
}

// ---- h1_long: an event H1 fits 100 characters (SE Ranking's limit) and keeps the day
$jim = soSpecEventText('concert', ['label' => 'Jim Brickman', 'perf' => 'Jim Brickman', 'venue' => 'Jeanne Wagner Theatre At Rose Wagner PAC', 'city' => 'Salt Lake City', 'state' => 'UT', 'day' => 'Nov 11', 'hasTickets' => true]);
srCheck('long venue: H1 fits 100 characters', mb_strlen($jim['h1']) <= 100, $jim['h1'] . ' (' . mb_strlen($jim['h1']) . ')');
srCheck('long venue: H1 keeps the performer and the day', mb_strpos($jim['h1'], 'Buy Jim Brickman Tickets') === 0 && mb_strpos($jim['h1'], 'Nov 11') !== false, $jim['h1']);
$kenny = soSpecEventText('concert', ['label' => 'Kenny Chesney', 'perf' => 'Kenny Chesney', 'venue' => "The Dome at America's Center", 'city' => 'St. Louis', 'state' => 'MO', 'day' => 'May 15', 'hasTickets' => true]);
srCheck('a short H1 is unchanged', $kenny['h1'] === "Buy Kenny Chesney Tickets for The Dome at America's Center Show in St. Louis, MO on May 15", $kenny['h1']);
$longVenue = 'The Extraordinarily Long Named Performing Arts Center Grand Hall And Pavilion';
foreach (['concert', 'sports', 'theater', 'festival'] as $kind) {
    $s = soSpecEventText($kind, ['label' => 'Zq Act', 'perf' => 'Zq Act', 'venue' => $longVenue, 'city' => 'North Little Rock', 'state' => 'AR', 'day' => 'Dec 26', 'hasTickets' => true]);
    srCheck("$kind: H1 fits 100 characters", mb_strlen($s['h1']) <= 100, $s['h1'] . ' (' . mb_strlen($s['h1']) . ')');
    srCheck("$kind: H1 keeps the day", mb_strpos($s['h1'], 'Dec 26') !== false, $s['h1']);
}

// ---- h1_duplicate and description_duplicate: a second showing on the same day names its time in the H1 and the description
$tso = fn(string $clock) => soSpecEventText('concert', ['label' => 'Trans-Siberian Orchestra', 'perf' => 'Trans-Siberian Orchestra', 'venue' => 'Prudential Center', 'city' => 'Newark', 'state' => 'NJ', 'day' => 'Dec 26', 'clock' => $clock, 'hasTickets' => true]);
$first = $tso(''); $second = $tso('7:30 PM');
srCheck('second showing: H1 names the time', $first['h1'] !== $second['h1'] && mb_strpos($second['h1'], 'Dec 26 at 7:30 PM') !== false, $second['h1']);
$d1 = soSpecPick(155, ...$first['descs']); $d2 = soSpecPick(155, ...$second['descs']);
srCheck('second showing: description names the time', $d1 !== $d2 && mb_strpos($d2, '7:30 PM') !== false, $d2);
srCheck('no time given: H1 and description as before', mb_strpos($first['h1'], ' at 7:30') === false && mb_strpos($first['h1'], 'on Dec 26') !== false);
$seo = srSrc('inc/seo-event.php');
srCheck('event page passes the time of a second showing to the spec', (bool) preg_match("/'clock'\s*=>\s*\\\$evTwin\s*\?\s*\\\$evClock\s*:\s*''/", $seo));
srCheck('event page decides "second showing" before it builds the spec', strpos($seo, '$evTwin = ') !== false && strpos($seo, '$evTwin = ') < strpos($seo, 'soSpecEventText('));

// ---- image_no_alt: the six menu tiles on every page, and the promo code page tiles, describe their picture
require __DIR__ . '/../inc/menu.php';
foreach ($soMenuDef as $m) srCheck("menu tile {$m['key']} has alt text", trim((string) ($m['alt'] ?? '')) !== '');
srCheck('menu tiles print their alt text', strpos($header, 'alt="<?php echo htmlspecialchars((string) ($m[\'alt\'] ?? \'\'), ENT_QUOTES, \'UTF-8\'); ?>"') !== false);
srCheck('no menu tile with an empty alt', !preg_match('#home-cat-<\?php echo \$m\[.key.\]; \?>\.webp" alt=""#', $header));
srCheck('promo code page tiles have alt text', !preg_match('#home-cat-[a-z]+\.webp" alt=""#', srSrc('inc/seo-copy/tickets-promo-code.php')));

srCheck('blog hero image has alt text (audit 603557644)', strpos(srSrc('blog.php'), 'blog-hero.webp" alt=""') === false && (bool) preg_match('#blog-hero\.webp" alt="[^"]{10,}"#', srSrc('blog.php')));

// ---- extlinks_no_anchor: icon links carry text a crawler reads (aria-label alone is not anchor text)
$event = srSrc('event.php');
srCheck('event map link has text inside the link', (bool) preg_match('#class="so-evp-venue__map"[^>]*>(?:(?!</a>).)*<span class="visually-hidden">#s', $event));
$reviews = srSrc('seat-outlet-reviews.php');
foreach (['Facebook', 'YouTube', 'Instagram'] as $net) {
    srCheck("reviews page $net icon link has text", (bool) preg_match('#aria-label="Seat Outlet on ' . $net . '"><i [^>]*></i><span class="visually-hidden">Seat Outlet on ' . $net . '</span></a>#', $reviews));
}

// ---- extlinks3xx: external links point at the final address, not at a redirect
// The MaxMind link is the GeoLite2 licence attribution, whose required wording names https://www.maxmind.com: it stays as written (the 3XX is a notice).
srCheck('MaxMind attribution keeps the licence wording', strpos(srSrc('footer.php'), 'This product includes GeoLite2 data created by MaxMind, available from <a href="https://www.maxmind.com" rel="noopener" target="_blank">https://www.maxmind.com</a>.') !== false);
srCheck('Tumblr link is the public blog, not the dashboard path', strpos(srSrc('inc/social.php'), "'https://www.tumblr.com/seatoutlet'") !== false && strpos(srSrc('inc/social.php'), 'tumblr.com/blog/') === false);
$index = srSrc('index.php');
srCheck('home page links the www Mindshare address', strpos($index, 'href="https://mindshare.consulting/"') === false && strpos($index, 'href="https://www.mindshare.consulting/"') !== false);
srCheck('home page links the www Grab Tickets Now address', strpos($index, 'href="https://grabticketsnow.com/"') === false && strpos($index, 'href="https://www.grabticketsnow.com/"') !== false);

// ---- http5xx on /category/<name>: a refused category lookup falls back to the name stored with the slug, not a 503
$cat = srSrc('category.php');
srCheck('category page names itself from the stored slug name when the lookup is refused', strpos($cat, "soSlugStoredName('category', \$id)") !== false);

// ---- http5xx on /venue, /city and /artist pages (audit 603557644, 14 Nov 2026): a refused first lookup of the record is answered from the
// bulk copy (tools/test-tn-bulk-read.php covers the stand-in itself)
srCheck('the bulk copy stands in for a venue, city or performer record when the live lookup fails', strpos(srSrc('inc/tn-bulk-read.php'), 'function tnBulkEntity(') !== false);

// ---- external link to 4XX (audit of 18 Nov 2026): Reddit answers 403 to crawlers, so it stays in the schema but is not a visible link
require_once __DIR__ . '/../inc/social.php';
$visible = soSocialIconsHtml() . soSocialPillsHtml();
srCheck('no visible Reddit link (403 to crawlers)', stripos($visible, 'reddit.com') === false);
srCheck('Reddit stays in the schema sameAs list', in_array('https://www.reddit.com/user/SeatOutlet/', soSocialSameAs(), true));
srCheck('the other profiles are still linked', strpos($visible, 'x.com/SeatOutlet') !== false && strpos($visible, 'medium.com/@seatoutlet') !== false);

if ($fail) { echo "$fail check(s) failed\n"; exit(1); }
echo "ok: SE Ranking audit 12 Nov 2026\n";
