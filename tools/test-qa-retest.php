<?php
require_once __DIR__ . '/../inc/cli-guard.php';
/**
 * Regression checks for the QA retest findings that can be proved from the source: markup, link targets, landmark names, heading
 * structure, copy and titles. Layout and contrast are checked in a real browser (see the QA audit notes in the PR). Run: php tools/test-qa-retest.php
 */
$root = dirname(__DIR__);
$fail = 0;
function qaCheck(string $what, bool $ok): void { global $fail; echo ($ok ? 'ok   ' : 'FAIL ') . $what . "\n"; if (!$ok) $fail++; }
function qaSrc(string $rel): string { global $root; return (string) file_get_contents($root . '/' . $rel); }

// Footer (81, 82): the ampersand is escaped and the unlabelled div carries no aria-label.
$footer = qaSrc('footer.php');
qaCheck('footer: "Deals & Promotions" is escaped as &amp; (81)', strpos($footer, 'Deals &amp; Promotions') !== false && strpos($footer, '>Deals & Promotions<') === false);
qaCheck('footer: the artist links block is not a labelled div (82)', strpos($footer, '<div class="policies" aria-label=') === false);

// Legal links that open a new tab (56-62): every target=_blank has noopener.
foreach (['privacy-policy.php', 'cookie-policy.php'] as $f) {
    preg_match_all('/<a\b[^>]*target="_blank"[^>]*>/i', qaSrc($f), $m);
    $bad = array_filter($m[0], fn($a) => stripos($a, 'noopener') === false);
    qaCheck("$f: every new-tab link has rel=noopener (56-62)", !$bad);
}

// Holiday navigation names (40-55): the accessible name carries the country.
$hol = qaSrc('holiday-events.php');
qaCheck('holidays: each nav name includes the country (40-55)', strpos($hol, 'visually-hidden') !== false && strpos($hol, '$ccName') !== false);

// Blog author asides (29-38) and the reviews sidebar (39): no unnamed duplicate complementary landmark.
$bv = qaSrc('inc/blog-post-view.php');
qaCheck('blog: the author box is not an unnamed aside (29-38)', strpos($bv, '<aside class="so-art__author">') === false);
qaCheck('reviews: the sidebar aside has an accessible name (39)', strpos(qaSrc('seat-outlet-reviews.php'), '<aside class="col-md-4">') === false);

// Card markup (69, 70-73): no block element inside a span.
qaCheck('blog: feature-card body is not a span wrapping an h2 (69)', strpos(qaSrc('blog.php'), '<span class="so-np__fbody">') === false);
qaCheck('how-to-buy: category body is not a span wrapping a paragraph (70-73)', strpos(qaSrc('how-to-buy-tickets-online.php'), '<span class="so-htb-cat__body">') === false);

// Fragment IDs (77-80): an article's heading IDs cannot start with a digit.
require_once $root . '/inc/blog-render.php';
qaCheck('blog: a digit-leading heading ID is prefixed and its link follows (77-80)', function_exists('soBlogSafeIds') && soBlogSafeIds('<h2 id="1-the-box-office">A</h2><a href="#1-the-box-office">x</a>') === '<h2 id="s1-the-box-office">A</h2><a href="#s1-the-box-office">x</a>');
qaCheck('blog: a letter-leading ID is left alone', function_exists('soBlogSafeIds') && soBlogSafeIds('<h2 id="intro">A</h2><a href="#intro">x</a>') === '<h2 id="intro">A</h2><a href="#intro">x</a>');

// Copy (84) and title (83).
qaCheck('privacy copy: no stray comma before the full stop (84)', strpos(qaSrc('inc/seo-copy/privacy-policy.php'), 'secure, .') === false);

// Steps (24): the horizontal steps can take keyboard focus.
qaCheck('how-to-buy: horizontal steps are keyboard reachable (24)', (bool) preg_match('/class="so-htb-steps"[^>]*tabindex="0"/', qaSrc('how-to-buy-tickets-online.php')));

// Home title (83): the live row is changed by migration 0053, and the new title is within the limit.
$mig = qaSrc('db/migrations/0053_home_title_length.sql');
preg_match("/SET `meta_title` = '([^']+)'/", $mig, $tt);
qaCheck('home title: migration 0053 sets a title within 70 characters (83)', !empty($tt[1]) && mb_strlen($tt[1]) <= 70 && strpos($tt[1], 'Concert & Sports Tickets') !== false);
qaCheck('home title: the migration only touches the row with the old title (83)', strpos($mig, "AND `meta_title` = 'Seat Outlet – Verified Ticket Marketplace Network") !== false);

// Ticket-policy widget (8): not deferred, because it writes its markup with document.write.
preg_match('/<script[^>]*tickettransaction\\.com\\/\\?https=true[^>]*>/', qaSrc('policies.php'), $pt);
qaCheck('policies: the document.write widget is not deferred or async (8, see notes)', !empty($pt[0]) && !preg_match('/\\b(defer|async)\\b/', $pt[0]));

// GTM (9): the default container is used only on the live site.
$cons = qaSrc('inc/constants.php');
qaCheck('gtm: the default container needs the live API and the live host (9)', strpos($cons, 'soGtmLiveSite') !== false && strpos($cons, "? 'GTM-W2XCB423' : ''") !== false);

echo $fail ? "\nqa retest: $fail failed\n" : "\nqa retest: all passed\n";
exit($fail ? 1 : 0);
