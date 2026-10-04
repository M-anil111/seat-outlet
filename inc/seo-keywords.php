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
 * Titles follow "Keyword—Hook" (em dash, no spaces) without the brand: header.php appends "—Seat Outlet" and keeps the whole title under 60
 * characters. Descriptions are 120 to 155 characters and end on a reason to click (guarantee, live seat maps). Three tested variants per page
 * and the reasoning are in docs/seo/titles-and-metas.md; the one in use is the first.
 * "{Y}" is replaced with the current year. 'score' => false pages get a keyword (for the strip) but are not scored:
 * they are other companies' profiles with too little real content to score fairly.
 */

$SEO_PLAN = [
    // path => [keyword, title ("Keyword—Hook", the brand is added by header.php), description, volume, KD, score]
    '/' => ['event tickets', 'Event Tickets for Concerts, Sports and Shows',
        'Event tickets for concerts, sports, theater and festivals. Compare seats and prices from many sellers. Orders carry the TicketNetwork guarantee.', 1600, 71, true],
    '/concert-tickets-for-sale' => ['concert tickets', 'Concert Tickets for Sale {Y} Tours & Shows',
        'Concert tickets for sale to {Y} tours and shows near you. Compare seats and prices by date and genre, with orders covered by TicketNetwork\'s guarantee.', 74000, 88, true],
    '/game-day-tickets' => ['sports tickets', 'Sports Tickets for NFL, NBA, MLB and NHL',
        'Sports tickets and game day tickets for NFL, NBA, MLB, NHL, college and soccer. Compare seats and prices, with orders covered by TicketNetwork\'s guarantee.', 4400, 67, true],
    '/buy-broadway-tickets' => ['how to buy broadway tickets', 'How to Buy Broadway Tickets and Seats',
        'Learn how to buy Broadway tickets and compare orchestra, mezzanine and balcony prices for musicals and plays. Orders carry the TicketNetwork guarantee.', 390, 6, true],
    '/upcoming-music-festivals' => ['upcoming music festivals', 'Upcoming Music Festivals and Tickets',
        'Festival tickets for sale to upcoming music festivals with {Y} dates and passes. Compare GA, VIP and day prices. Orders carry the TicketNetwork guarantee.', 540, 42, true],
    '/city-events' => ['city events', 'Events Near Me for Concerts and Sports',
        'Find events near me in any major US city. Pick a city for concerts, games and shows and compare prices. Orders carry the TicketNetwork guarantee.', 390, 55, true],
    '/all-artists-and-teams' => ['all artists', 'All Artists, Teams and Shows A to Z',
        'All artists, teams and shows with tickets, A to Z. Pick a name for upcoming dates and compare seats and prices. Orders carry the TicketNetwork guarantee.', 390, 38, true],
    '/concert-artists' => ['artists on tour', 'Artists on Tour A to Z with Tickets',
        'Artists on tour with tickets on sale, A to Z. Pick a name to see tour dates and compare seats and prices. Orders carry the TicketNetwork guarantee.', 1600, 69, true],
    '/sports-teams' => ['sports teams', 'Sports Teams A to Z with Game Tickets',
        'Sports teams with game tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.', 1500, 66, true],
    '/nfl-teams' => ['nfl teams', 'NFL Teams with Tickets and Schedules',
        'NFL teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.', 165000, 94, true],
    '/nba-teams' => ['nba teams', 'NBA Teams with Tickets and Schedules',
        'NBA teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.', 135000, 92, true],
    '/mlb-teams' => ['mlb teams', 'MLB Teams with Tickets and Schedules',
        'MLB teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.', 60500, 95, true],
    '/nhl-teams' => ['nhl teams', 'NHL Teams with Tickets and Schedules',
        'NHL teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.', 49500, 80, true],
    '/mls-teams' => ['mls teams', 'MLS Teams with Tickets and Schedules',
        'MLS teams with tickets on sale, A to Z. Pick a team to see its schedule and compare seats and prices. Orders carry the TicketNetwork guarantee.', 12100, 93, true],
    '/broadway-shows' => ['broadway shows list', 'Broadway Shows List A to Z with Tickets',
        'Broadway shows list A to Z with tickets for musicals and plays. Pick a show for dates and compare prices. Orders carry the TicketNetwork guarantee.', 440, 16, true],
    '/comedians-on-tour' => ['comedians on tour', 'Comedians on Tour A to Z with Tickets',
        'Comedians on tour with tickets on sale, A to Z. Pick a name to see tour dates and compare seats and prices. Orders carry the TicketNetwork guarantee.', 2400, 61, true],
    '/music-festivals-list' => ['music festivals list', 'Music Festivals List A to Z with Tickets',
        'Music festivals list A to Z, with passes and tickets on sale. Pick a festival to see dates and compare prices. Orders carry the TicketNetwork guarantee.', 260, 36, true],
    '/buy-tickets-online' => ['buy tickets online', 'Find and Buy Tickets Online for Any Event',
        'Find and buy tickets online for concerts, sports, theater and festivals. Compare seats and prices from sellers. Orders carry the TicketNetwork guarantee.', 1300, 75, true],
    '/tickets-promo-code' => ['ticket promo code', 'Ticket Promo Code and Ways to Save',
        'Looking for a ticket promo code? See current Seat Outlet offers, how to apply a code at checkout, and honest ways to pay less for concerts and games.', 760, 29, true],
    '/ticket-deals' => ['ticket deals', 'Ticket Deals on Concerts and Sports',
        'Ticket deals on concerts, sports and live events. Compare prices from many sellers, sort by lowest price and find seats that fit your budget today.', 480, 51, true],
    '/hunt-tickets' => ['hunt tickets', 'Hunt Tickets Event Ticket Search',
        'Hunt Tickets is part of the Seat Outlet network. Search events, compare ticket prices and find seats for concerts, sports and theater in a few clicks.', null, null, true],
    '/grab-tickets-now' => ['grab tickets now', 'Grab Tickets Now Event Ticket Search',
        'Grab Tickets Now is part of the Seat Outlet network. Buy sports and concert tickets online through a simple, trusted event ticket marketplace.', null, null, true],
    '/ticket-scanner' => ['ticket scanner', 'Ticket Scanner Travel Booking for Events',
        'Ticket Scanner is part of the Seat Outlet network: an online travel booking site for flights, hotels and car rentals to plan the trip around your event.', 720, 44, true],
    '/worry-free-guarantee' => ['worry free guarantee', 'Worry Free Guarantee and What It Covers',
        'Our worry free guarantee backs every order: valid tickets, delivery before the event, seats as good or better, and a full refund if the event is canceled.', 90, 20, true],
    '/ticket-buyer-protection' => ['verified resale tickets', 'Verified Resale Tickets and Buyer Protection',
        'Verified resale tickets and ticket buyer protection on every Seat Outlet order: valid tickets, on time delivery and a refund if the event is canceled.', 540, 20, true],
    '/customer-testimonials' => ['customer testimonials', 'Customer Testimonials and Reviews',
        'Seat Outlet does not publish customer testimonials yet. See what we can show today, how our guarantee works and where to find independent reviews.', 660, 30, true],
    '/seat-outlet-reviews' => ['seat outlet reviews', 'Seat Outlet Reviews and What We Can Show',
        'Looking for Seat Outlet reviews? We do not show reviews on our own site yet. See how the guarantee works and how to judge a ticket seller before you buy.', null, null, true],
    '/seat-outlet-bbb' => ['seat outlet bbb', 'Seat Outlet BBB and Our Customer Commitments',
        'Looking for Seat Outlet on the Better Business Bureau? This page does not show a BBB rating. It explains our customer commitments and how to reach us.', null, null, true],
    '/about-seat-outlet' => ['about seat outlet', 'About Seat Outlet and How It Works',
        'About Seat Outlet: an independent resale ticket marketplace for concerts, sports, theater and festivals, with orders fulfilled through TicketNetwork.', null, null, true],
    '/how-to-buy-tickets-online' => ['how to buy tickets online', 'How to Buy Tickets Online the Safe Way',
        'Learn how to buy tickets online and where to buy event tickets safely: find your event, compare seats and prices, check out securely and get them on time.', 260, 9, true],
    '/ticket-partner-program' => ['ticket partner program', 'Ticket Partner Program with Seat Outlet',
        'Run a venue, festival, team or business? Send Seat Outlet a partnership enquiry and see what we are, what we are not and what to ask any marketplace.', null, null, true],
    '/ticket-faq' => ['ticket faq', 'Ticket FAQ on Delivery, Entry and Refunds',
        'The Seat Outlet ticket FAQ answers common questions on buying tickets, delivery times, entry rules, transfers and refunds for concerts, sports and theater.', null, null, true],
    '/ticket-customer-service' => ['ticket customer service', 'Ticket Customer Service and Order Help',
        'Ticket customer service from Seat Outlet. Email us or use the contact form for help with orders, delivery, transfers and event changes. We reply fast.', 70, 29, true],
    '/why-are-concert-tickets-so-expensive' => ['why are concert tickets so expensive', 'Why Are Concert Tickets So Expensive?',
        'Why are concert tickets so expensive? See who sets ticket prices, fees and release dates, why tickets reach resale marketplaces, and where the money goes.', 20, 32, true],
    '/terms-and-conditions' => ['terms and conditions', 'Terms and Conditions for Ticket Buyers',
        'Seat Outlet terms and conditions for using our website and buying tickets for live events through our resale marketplace, including orders and refunds.', 9900, 77, true],
    '/privacy-policy' => ['privacy policy', 'Privacy Policy and How We Use Your Data',
        'The Seat Outlet privacy policy explains how we collect, use, share and protect your personal information when you use our site and buy event tickets.', 22400, 97, true],
    '/cookie-policy' => ['cookie policy', 'Cookie Policy and How We Use Cookies',
        'The Seat Outlet cookie policy explains how we use cookies and similar technologies on our website, what each type does and how to change your choices.', 810, 55, true],
    '/blog' => ['ticket buying tips', 'Ticket Buying Tips, Guides and Event Picks',
        'Ticket buying tips, event guides and city guides from Seat Outlet. Learn how to find better seats, compare prices and buy tickets with confidence.', 10, 13, false],
    '/our-network' => ['seat outlet network', 'Seat Outlet Network of Partner Sites',
        'The Seat Outlet network: partner sites for tickets, travel and business software, and how each one connects to our live event ticket marketplace.', null, null, false],
    '/dotbooker' => ['dotbooker', 'Dotbooker Booking and Appointment Software',
        'Dotbooker is booking and appointment software for studios, salons and wellness providers, and part of the Seat Outlet network of partner sites.', null, null, false],
    '/wingcms' => ['wingcms', 'WingCMS Real Estate Technology',
        'WingCMS is end to end real estate technology for agents and brokers, and part of the Seat Outlet network of partner websites and services.', null, null, false],
    '/salespeep' => ['salespeep', 'Salespeep CRM for Sales and Marketing',
        'Salespeep is a CRM for sales, marketing and service teams, and part of the Seat Outlet network of partner websites, tools and services.', null, null, false],
    '/signs-n-more' => ['signs n more', 'Signs N More Branding, Web and Marketing',
        'Signs N More offers branding, signs, web design and digital marketing for businesses, and is part of the Seat Outlet network of partner sites.', null, null, false],
    '/it-sprinkles' => ['it sprinkles', 'It Sprinkles Custom Cakes in Austin',
        'It Sprinkles makes custom cakes, cupcakes and desserts in Austin for birthdays, weddings and celebrations, and is part of the Seat Outlet network.', null, null, false],
    '/austin-sign-masters' => ['austin sign masters', 'Austin Sign Masters Custom Signs in Austin',
        'Austin Sign Masters makes custom signs and printing for businesses in Austin, Texas, and is part of the Seat Outlet network of partner sites.', null, null, false],
    '/viralpep' => ['viralpep', 'Viralpep Social Media Management Tool',
        'Viralpep is a social media management tool to create, schedule, collaborate on and track posts from one dashboard. Part of the Seat Outlet network.', null, null, false],
    '/mindshare-consulting' => ['mindshare consulting', 'Mindshare Consulting Austin Marketing Agency',
        'Mindshare Consulting is a full service marketing agency in Austin for SEO, advertising, branding and web design, and the team behind this website.', null, null, false],
    '/image-credits' => ['photo credits', 'Photo Credits and Image Licenses',
        'Photo credits for the images used on Seat Outlet: the source, the photographer or rights holder and the license for every picture on the site.', null, null, false],
    '/sitemap-page' => ['seat outlet sitemap', 'Seat Outlet Sitemap and Site Sections',
        'The Seat Outlet sitemap lists every section of the site: event categories, genres, cities, artists and teams, guides and customer service pages.', null, null, false],
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
