<?php
/**
 * Hand-written, keyword-targeted copy for the genre and league pages that are tracked for ranking
 * (see docs/seo/top-15-pages.md). soCategorySeo() puts the answer-first lead above the data-driven blocks, the sections
 * before "How to buy", and the extra questions at the end of the FAQ (they become FAQPage data automatically).
 *
 * Rules for this copy: no dates, prices, counts or rules that can go stale; those come from the live catalog elsewhere on
 * the page. Seat Outlet does not sell tickets itself: it compares listings and sends the buyer to the guaranteed checkout.
 *
 *   slug => ['lead' => html, 'sections' => [[h2, html], ...], 'faqs' => [[question, answer], ...]]
 */
function soGenreFocus($slug) {
    static $all = null;
    if ($all === null) $all = [
        'mls-tickets' => [
            'lead' => '<p class="so-cseo__lead"><strong>MLS tickets are tickets to Major League Soccer matches</strong>, the top professional soccer league in the United States and Canada. Compare MLS game tickets by team, date and city, see the lowest listed price, and check seats on the map before you buy.</p>',
            'sections' => [
                ['MLS ticket prices: what changes the cost', '<p>MLS ticket prices are set by sellers, so the same match can have a wide range. The usual drivers are the opponent, the home club, the stadium, the section, the day of the week and how close the match is. A rivalry or a late-season match with a lot at stake tends to cost more than a midweek game between mid-table clubs. Compare a few sections and look at the total at checkout, not only the first price you see.</p><p>Looking for cheap MLS tickets? Check the upper-level and corner sections first, open the date filter to see midweek matches, and compare the same section across several dates. Our <a href="/ticket-deals">ticket deals</a> page lists current savings ideas.</p>'],
                ['MLS playoff tickets and MLS Cup tickets', '<p>After the regular season, the MLS Cup Playoffs decide the league champion and the final is MLS Cup. Playoff matches are scheduled once the regular-season table is settled, so MLS playoff tickets and MLS Cup tickets appear here as the matches are confirmed and ticketed. For the official MLS playoffs schedule and the bracket, use the league\'s own site, <a href="https://www.mlssoccer.com/" target="_blank" rel="noopener">MLSsoccer.com</a>, then come back to compare seats for the match you want.</p>'],
                ['MLS tickets by team', '<p>Every club has its own page with all of its home matches. Fans looking for Inter Miami tickets, LAFC tickets, LA Galaxy tickets, Atlanta United tickets, Austin FC tickets, NYCFC tickets, Seattle Sounders tickets or Orlando City tickets can open the team from the list above, or browse every club on <a href="/mls-teams">MLS teams A to Z</a>. Each team page shows the next match, venue and seats on sale.</p><p>New to the league? <a href="/sports-teams">Sports teams by league</a> also lists the NFL, NBA, MLB and NHL, and our <a href="/soccer-tickets">soccer game tickets</a> page covers soccer beyond MLS.</p>'],
                ['MLS games today, this weekend and near you', '<p>Use the date filter to jump to matches today or this weekend, and set your location to see the closest matches first. Schedules change, so confirm the date, kickoff time and venue on the event page before you buy.</p>'],
            ],
            'faqs' => [
                ['Where can I buy MLS tickets?', 'You can compare MLS tickets on Seat Outlet by team, date and city, then choose seats on the map and check out through the guaranteed checkout. Seat Outlet is a resale marketplace, so sellers set the prices.'],
                ['Are MLS playoff tickets and MLS Cup tickets available?', 'Playoff and MLS Cup tickets are listed once the matches are confirmed. If you do not see the match yet, check back or set a price alert on a related event. The official playoff schedule is published by MLS.'],
                ['Are MLS tickets cheaper than other sports tickets?', 'It depends on the match. Prices depend on the club, opponent, venue and section, so compare the lowest listed price on the matches you are considering rather than assuming.'],
            ],
        ],
        'soccer-tickets' => [
            'lead' => '<p class="so-cseo__lead"><strong>Soccer game tickets on Seat Outlet cover professional matches across the United States</strong>, from club games to international matches when they are on sale. Compare soccer match tickets by date, city and venue, then choose your seats on the map.</p>',
            'sections' => [
                ['Cheap soccer tickets and soccer tickets near me', '<p>Soccer ticket prices are set by sellers and move with the teams, the competition, the stadium and the section. For cheap soccer tickets, compare the upper-level and corner sections, look at midweek matches and check the same section on a few dates. To find soccer tickets near me, set your location at the top of the listing and the closest matches come first.</p>'],
                ['How to buy soccer game tickets', '<ol class="so-steps"><li><span><strong>Pick a match.</strong> Browse the list above, or open a club from <a href="/sports-teams">sports teams by league</a>.</span></li><li><span><strong>Choose seats.</strong> Compare sections and the total price on the seat map.</span></li><li><span><strong>Check out.</strong> Orders go through the guaranteed checkout. First time buying online? Read <a href="/how-to-buy-tickets-online">how to buy tickets online</a>.</span></li></ol>'],
                ['MLS and other soccer', '<p>Looking for one league? See <a href="/mls-tickets">MLS tickets</a> for Major League Soccer clubs and the <a href="/mls-teams">MLS teams</a> list. International and other club matches appear on this page when tickets are listed, so check back as the calendar fills.</p>'],
                ['Match day tips', '<p>Stadium rules differ, including bag size, re-entry and how tickets are scanned, so read the venue page and your order email before you travel. Most venues scan mobile tickets at the gate. Arrive early enough for security and your seat, and keep your order confirmation handy.</p>'],
            ],
            'faqs' => [
                ['Where can I find soccer tickets near me?', 'Set your location at the top of the listing to see the closest soccer matches first, then use the date filter for matches this weekend.'],
                ['Are soccer match tickets on Seat Outlet legit?', 'Seat Outlet is a resale marketplace and orders are covered by our 100% guarantee: valid tickets, delivery before the event and a refund if the event is canceled and not rescheduled.'],
            ],
        ],
        'comedy-show-tickets' => [
            'lead' => '<p class="so-cseo__lead"><strong>Comedy show tickets on Seat Outlet cover stand-up tours, comedy clubs and theater shows.</strong> Search by comedian, city or date, compare seats and prices for upcoming comedy shows, and check out with our 100% guarantee.</p>',
            'sections' => [
                ['Upcoming comedy shows and comedy tours', '<p>Comedy tours move from city to city, and new dates are added as tours are announced. Open any comedian from the list above to see every tour date, or browse <a href="/comedians-on-tour">comedians on tour A to Z</a> for the full list. Tour dates and venues change, so confirm the details on the event page before you buy.</p>'],
                ['Stand-up comedy near me', '<p>Set your location at the top of the listing to see stand-up comedy near you first, and use the date filter for comedy shows this weekend or tonight. Smaller rooms such as comedy clubs often have fewer, closer seats, while theater and arena shows have larger seat maps with price differences between sections.</p>'],
                ['What comedy ticket prices depend on', '<p>Prices are set by sellers and depend on the comedian, the venue, the day of the week and the seat. A weeknight show in a theater usually has more choice than a weekend night for a popular tour. Compare a few sections and read the total at checkout. See <a href="/ticket-deals">ticket deals</a> for other ways to save.</p>'],
            ],
            'faqs' => [
                ['Are there comedy shows this weekend or tonight?', 'Use the date filter on the listing to see comedy shows happening tonight or this weekend near your location. Availability changes daily.'],
                ['Do comedy shows have age limits?', 'Some shows and venues are 18+ or 21+, and policies are set by the venue or promoter. Check the event page and your order email before you go.'],
            ],
        ],
        'latin-music-tickets' => [
            'lead' => '<p class="so-cseo__lead"><strong>Latin concerts on Seat Outlet include Latin pop, reggaeton, bachata, salsa, regional Mexican and more</strong>, depending on what is on sale. Compare Latin concert tickets by artist, city and date, then pick your seats on the map.</p>',
            'sections' => [
                ['Upcoming Latin concerts near me', '<p>Set your location at the top of the listing to see Latin concerts near you first, and use the date filter for shows this weekend. Tours add dates through the year, so check back after an artist announces a new run. Tour dates and venues change; confirm them on the event page.</p>'],
                ['Reggaeton, bachata, salsa and regional Mexican concerts', '<p>Whatever your sound, the artist list above links to every tour date. Reggaeton and Latin pop tours often play arenas and stadiums, while salsa, bachata and regional Mexican shows also fill theaters, clubs and festivals. Open a venue page to see what else is on in the same building.</p>'],
                ['Seats or floor at a Latin concert', '<p>Many arena shows sell both reserved seats and floor tickets. Floor tickets put you closest to the stage, and some are standing only, so check the seat map and event notes. Compare a few sections and the total price before you check out.</p>'],
            ],
            'faqs' => [
                ['Where can I find Latin concert tickets?', 'Compare Latin concert tickets on Seat Outlet by artist, city and date, then choose seats on the map and check out through the guaranteed checkout.'],
                ['Are there Latin music festivals?', 'Some Latin acts play festivals. The upcoming music festivals page lists what is on sale.'],
            ],
        ],
    ];
    return $all[$slug] ?? null;
}

/**
 * Title (brand and year handled by soTitle), H1 and meta description for the tracked genre pages. {Y} is the current year.
 * @return array|null ['title' => string, 'h1' => string, 'desc' => string]
 */
function soGenreFocusMeta($slug) {
    static $m = [
        'mls-tickets'          => ['title' => 'MLS Tickets {Y} Schedule & Prices', 'h1' => 'MLS Tickets',
            'desc' => 'Compare MLS tickets by team, date and city, including MLS playoff and MLS Cup tickets. See seats and prices, with orders covered by our 100% guarantee.'],
        'soccer-tickets'       => ['title' => 'Soccer Game Tickets {Y} Dates and Prices', 'h1' => 'Soccer Game Tickets',
            'desc' => 'Compare soccer game tickets and cheap soccer tickets near you. See dates, venues and prices for soccer matches, with orders covered by our 100% guarantee.'],
        'comedy-show-tickets'  => ['title' => 'Comedy Show Tickets {Y} Dates and Prices', 'h1' => 'Comedy Show Tickets',
            'desc' => 'Compare comedy show tickets for upcoming comedy shows and comedy tours near you. See dates, venues and prices, with every order covered by our guarantee.'],
        'latin-music-tickets'  => ['title' => 'Latin Concerts {Y} Dates and Tickets', 'h1' => 'Latin Concerts and Tickets',
            'desc' => 'Compare Latin concert tickets for reggaeton, bachata, salsa and Latin pop shows near you. See dates, venues and prices, backed by our 100% guarantee.'],
    ];
    if (!isset($m[$slug])) return null;
    $r = $m[$slug];
    foreach (['title', 'desc'] as $k) $r[$k] = str_replace('{Y}', date('Y'), $r[$k]);
    return $r;
}
