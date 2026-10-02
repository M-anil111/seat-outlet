<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * Focus keyword plan for the site's own pages: one unique keyword per URL, with its SEO title and meta description.
 *
 * How it is used
 *  - header.php applies the title, description, canonical and focus keyword of the current URL (soSeoPlan()).
 *  - soFocusKeyword() prints the keyword in the top strip (the page's one H1) and in the footer strip.
 *  - admin/seo-scores.php scores every row with 'score' => true against the site's own SEO engine.
 *  - A page_rules row (Admin, Page Rules) for the same path still wins, so an admin can override anything here.
 *  - $LEGACY_URLS lists the old URLs that were renamed so the keyword is in the URL; they 301 to the new URL.
 *
 * Research (SE Ranking, US, 30 Sep 2026; volume = searches per month, KD = keyword difficulty 0-100). The site is a new
 * domain with no organic rankings, so head terms (KD 66-99: "concert tickets", "nfl tickets", "broadway tickets",
 * "ticket marketplace") were set aside for terms a new domain can realistically win (KD 15-55) that still match what the
 * page sells. 'vol'/'kd' of null means the tool had no data (usually a very small query): those are brand or long-tail
 * phrases chosen because they describe the page exactly and nobody competes for them.
 *
 * Titles are written without the brand: header.php appends " | Seat Outlet" and keeps the result near 60 characters.
 * "{Y}" is replaced with the current year. 'score' => false pages get a keyword (for the strip) but are not scored:
 * they are other companies' profiles with too little real content to score fairly.
 */

$SEO_PLAN = [
    // path => [keyword, title, description, volume, KD, score]
    '/' => ['buy event tickets', 'Buy Event Tickets: Trusted {Y} Live Events',
        'Buy event tickets for concerts, sports, theater and festivals on Seat Outlet. Compare seats and prices, check out securely and get our worry-free guarantee.', 170, 0, true],

    // Category hubs
    '/concert-tickets-for-sale' => ['concert tickets for sale', 'Concert Tickets for Sale: Top {Y} Tours & Shows',
        'Browse concert tickets for sale to upcoming tours and shows near you. Compare seats and prices, filter by date and buy with our worry-free guarantee.', 590, 51, true],
    '/game-day-tickets' => ['game day tickets', 'Game Day Tickets: Top {Y} NFL, NBA & MLB Seats',
        'Find game day tickets for NFL, NBA, MLB, NHL, college and soccer games. Compare seats and prices, filter by date, and check out securely on Seat Outlet.', 990, 38, true],
    '/buy-broadway-tickets' => ['buy broadway tickets', 'Buy Broadway Tickets: Top {Y} Shows & Musicals',
        'Buy Broadway tickets and theater tickets for musicals, plays and touring shows. Compare seats and prices, filter by date and shop with our guarantee.', 810, 45, true],
    '/upcoming-music-festivals' => ['upcoming music festivals', 'Upcoming Music Festivals: Top {Y} Lineups',
        'Explore upcoming music festivals and festival tickets across the country. Compare passes and prices, filter by date, and buy securely with Seat Outlet.', 540, 42, true],
    '/city-events' => ['city events', 'City Events: Top {Y} Concerts, Sports & Shows',
        'Find city events and tickets in top cities. Pick a city to see upcoming concerts, sports, theater and festivals, then compare seats and prices.', 390, 55, true],
    '/all-artists-and-teams' => ['all artists', 'All Artists, Teams & Shows: Discover {Y}',
        'Browse all artists, sports teams and shows A to Z on Seat Outlet. Pick a name to see upcoming dates, compare seats and prices, and buy tickets securely.', 390, 38, true],
    '/buy-tickets-online' => ['buy tickets online', 'Buy Tickets Online: Easy Checkout for {Y}',
        'Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out securely on Seat Outlet.', 1300, 75, true],

    // Offers and the Seat Outlet network sites
    '/tickets-promo-code' => ['tickets promo code', 'Tickets Promo Code: Save on {Y} Event Tickets',
        'Find a tickets promo code for concerts, sports and live events. See current Seat Outlet offers, how to apply a code at checkout, and tips to save on tickets.', 660, 35, true],
    '/ticket-deals' => ['ticket deals', 'Ticket Deals: Save on Concerts & Sports in {Y}',
        'Ticket deals on concerts, sports and live events from the Seat Outlet network. Compare prices across upcoming events and find seats that fit your budget.', 480, 51, true],
    '/hunt-tickets' => ['hunt tickets', 'Hunt Tickets: Easy Event Ticket Search for {Y}',
        'Hunt Tickets is part of the Seat Outlet network. Search events, compare ticket prices online and find seats for sports, concerts and theater in a few clicks.', null, null, true],
    '/grab-tickets-now' => ['grab tickets now', 'Grab Tickets Now: Simple Ticket Buying {Y}',
        'Grab Tickets Now is part of the Seat Outlet network. Buy sports tickets and concert tickets online through a streamlined, trusted event ticket marketplace.', null, null, true],
    '/ticket-scanner' => ['ticket scanner', 'Ticket Scanner: Easy Travel Booking for {Y}',
        'Ticket Scanner is part of the Seat Outlet network, an online travel booking website for flights, hotels and car rentals to plan the trip around your event.', 720, 44, true],

    // Trust and service pages
    '/worry-free-guarantee' => ['worry free guarantee', 'Worry Free Guarantee: Our Trusted 100% Promise',
        'Our worry free guarantee backs every Seat Outlet order with a 100% guarantee: valid tickets, delivery before the event and a refund if the event is canceled.', 90, 20, true],
    '/ticket-buyer-protection' => ['ticket buyer protection', 'Ticket Buyer Protection: Complete {Y} Guide',
        'Ticket buyer protection on every Seat Outlet order: valid tickets, on-time delivery and secure payment. See what is covered and how to get help with an order.', 0, 24, true],
    '/customer-testimonials' => ['customer testimonials', 'Customer Testimonials: Discover Fan Stories {Y}',
        'Read customer testimonials from fans who bought concert, sports and theater tickets on Seat Outlet, and see how our guarantee and support work in practice.', 660, 30, true],
    '/seat-outlet-reviews' => ['seat outlet reviews', 'Seat Outlet Reviews: Discover Fan Feedback {Y}',
        'Read Seat Outlet reviews and customer feedback from fans who bought concert, sports and theater tickets, and see how we handle orders and support.', null, null, true],
    '/seat-outlet-bbb' => ['seat outlet bbb', 'Seat Outlet BBB: Trusted Commitment {Y}',
        'Seat Outlet BBB profile and our commitment to customers: transparent policies, secure checkout and responsive support on every order.', null, null, true],
    '/about-seat-outlet' => ['about seat outlet', 'About Seat Outlet: Discover Our Story & Mission',
        'About Seat Outlet: how we help fans reach the concerts, sports and theater events they love, with smarter tools, trusted partners and our guarantee.', null, null, true],
    '/how-to-buy-tickets-online' => ['how to buy tickets online', 'How to Buy Tickets Online: A Simple Guide',
        'Learn how to buy tickets online: find your event, compare seats and prices, check out securely and get your tickets. A simple guide from Seat Outlet.', 210, 28, true],
    '/ticket-partner-program' => ['ticket partner program', 'Ticket Partner Program: Trusted Partnerships',
        'Join the Seat Outlet ticket partner program. See why businesses partner with us for reach, insight and streamlined tools in live event ticketing.', null, null, true],
    '/ticket-faq' => ['ticket faq', 'Ticket FAQ: Easy Answers on Delivery & Entry',
        'The Seat Outlet ticket FAQ answers common questions on buying tickets, delivery, entry requirements and event details for concerts, sports and theater.', null, null, true],
    '/ticket-customer-service' => ['ticket customer service', 'Ticket Customer Service: Easy Help & Support',
        'Ticket customer service from Seat Outlet: contact us by email, phone or in person for help with ticket orders, delivery, changes and account questions.', 70, 29, true],
    '/why-are-concert-tickets-so-expensive' => ['why are concert tickets so expensive', 'Why Are Concert Tickets So Expensive? Top Facts',
        'Why are concert tickets so expensive? See who sets ticket prices, fees and release dates, why tickets appear on resale marketplaces, and where money goes.', 20, 32, true],

    // Legal
    '/terms-and-conditions' => ['terms and conditions', 'Terms and Conditions: Complete Guide for Buyers',
        'Seat Outlet terms and conditions for using our websites, apps and services, including buying, selling, transferring and using tickets for live events.', 9900, 77, true],
    '/privacy-policy' => ['privacy policy', 'Privacy Policy: Complete Guide to Your Data',
        'The Seat Outlet privacy policy explains how we collect, use, share and protect your personal information when you buy or sell tickets for live events.', 22400, 97, true],
    '/cookie-policy' => ['cookie policy', 'Cookie Policy: Complete Guide to Our Cookies',
        'The Seat Outlet cookie policy explains how we use cookies and similar technologies on our websites, apps and services, and what each type is used for.', 810, 55, true],

    // Keyword shown in the strip only (not scored): index pages and other companies' profiles
    '/blog' => ['ticket buying tips', 'Ticket Buying Tips: Guides, News & Event Picks',
        'Ticket buying tips, event guides and live entertainment news from Seat Outlet. Learn how to find better seats, compare prices and buy tickets with confidence.', 10, 13, false],
    '/our-network' => ['seat outlet network', null, null, null, null, false],
    '/dotbooker' => ['dotbooker', null, null, null, null, false],
    '/wingcms' => ['wingcms', null, null, null, null, false],
    '/salespeep' => ['salespeep', null, null, null, null, false],
    '/signs-n-more' => ['signs n more', null, null, null, null, false],
    '/it-sprinkles' => ['it sprinkles', null, null, null, null, false],
    '/austin-sign-masters' => ['austin sign masters', null, null, null, null, false],
    '/viralpep' => ['viralpep', null, null, null, null, false],
    '/mindshare-consulting' => ['mindshare consulting', null, null, null, null, false],
];

/** Renamed URLs: old path => new path (301). The new path carries the focus keyword. */
$LEGACY_URLS = [
    '/concerts' => '/concert-tickets-for-sale',
    '/sports' => '/game-day-tickets',
    '/theater' => '/buy-broadway-tickets',
    '/festival' => '/upcoming-music-festivals',
    '/cities' => '/city-events',
    '/performers' => '/all-artists-and-teams',
    '/tickets' => '/buy-tickets-online',
    '/deals-promotions' => '/tickets-promo-code',
    '/guarantee' => '/worry-free-guarantee',
    '/buyer-protection' => '/ticket-buyer-protection',
    '/trust' => '/ticket-buyer-protection',
    '/testimonials' => '/customer-testimonials',
    '/reviews' => '/seat-outlet-reviews',
    '/bbb' => '/seat-outlet-bbb',
    '/about-us' => '/about-seat-outlet',
    '/what-we-do' => '/how-to-buy-tickets-online',
    '/why-us' => '/ticket-partner-program',
    '/faq' => '/ticket-faq',
    '/contact' => '/ticket-customer-service',
    '/ticketing-truths' => '/why-are-concert-tickets-so-expensive',
];

return ['plan' => $SEO_PLAN, 'legacy' => $LEGACY_URLS];
