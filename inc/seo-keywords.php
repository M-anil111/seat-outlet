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
    '/' => ['buy event tickets', 'Buy Event Tickets—Concerts, Sports & Theater',
        'Buy event tickets for concerts, sports, theater and festivals. Compare seats and prices side by side, check out securely and get our 100% guarantee.', 170, 0, true],
    '/concert-tickets-for-sale' => ['concert tickets for sale', 'Concert Tickets for Sale—{Y} Tours & Shows',
        'Concert tickets for sale to {Y} tours and shows near you. Compare seats and prices, filter by date and genre, and buy with our 100% buyer guarantee.', 590, 51, true],
    '/game-day-tickets' => ['game day tickets', 'Game Day Tickets—NFL, NBA, MLB & NHL Seats',
        'Game day tickets for NFL, NBA, MLB, NHL, college and soccer. Compare seats and prices on live seat maps and buy with our 100% buyer guarantee.', 990, 38, true],
    '/buy-broadway-tickets' => ['buy broadway tickets', 'Buy Broadway Tickets—{Y} Shows & Musicals',
        'Buy Broadway tickets for musicals, plays and touring shows. Compare orchestra and mezzanine seats and prices, then buy with our 100% buyer guarantee.', 810, 45, true],
    '/upcoming-music-festivals' => ['upcoming music festivals', 'Upcoming Music Festivals—{Y} Lineups & Passes',
        'Upcoming music festivals across the country, with {Y} dates and passes. Compare GA, VIP and single day prices and buy with our 100% buyer guarantee.', 540, 42, true],
    '/city-events' => ['city events', 'City Events—{Y} Concerts, Sports & Shows',
        'City events in every major US city. Pick your city to see upcoming concerts, games and shows, compare seats and prices, and buy with a 100% guarantee.', 390, 55, true],
    '/all-artists-and-teams' => ['all artists', 'All Artists, Teams & Shows—A to Z',
        'All artists, teams and shows with tickets on sale, A to Z. Pick a name to see upcoming dates, compare seats and prices and buy with a 100% guarantee.', 390, 38, true],
    '/buy-tickets-online' => ['buy tickets online', 'Buy Tickets Online—All Events, Secure Checkout',
        'Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out with a 100% guarantee.', 1300, 75, true],
    '/tickets-promo-code' => ['tickets promo code', 'Tickets Promo Code—Current Seat Outlet Offers',
        'Looking for a tickets promo code? See current Seat Outlet offers, how to apply a code at checkout, and honest ways to pay less for concerts and games.', 660, 35, true],
    '/ticket-deals' => ['ticket deals', 'Ticket Deals—Save on Concerts & Sports',
        'Ticket deals on concerts, sports and live events. Compare prices from many sellers, sort by lowest price and find seats that fit your budget today.', 480, 51, true],
    '/hunt-tickets' => ['hunt tickets', 'Hunt Tickets—Event Ticket Search',
        'Hunt Tickets is part of the Seat Outlet network. Search events, compare ticket prices and find seats for concerts, sports and theater in a few clicks.', null, null, true],
    '/grab-tickets-now' => ['grab tickets now', 'Grab Tickets Now—Simple Ticket Buying',
        'Grab Tickets Now is part of the Seat Outlet network. Buy sports and concert tickets online through a simple, trusted event ticket marketplace.', null, null, true],
    '/ticket-scanner' => ['ticket scanner', 'Ticket Scanner—Travel Booking for Your Event',
        'Ticket Scanner is part of the Seat Outlet network: an online travel booking site for flights, hotels and car rentals to plan the trip around your event.', 720, 44, true],
    '/worry-free-guarantee' => ['worry free guarantee', 'Worry Free Guarantee—What Every Order Covers',
        'Our worry free guarantee backs every order: valid tickets, delivery before the event, seats as good or better, and a full refund if the event is canceled.', 90, 20, true],
    '/ticket-buyer-protection' => ['ticket buyer protection', 'Ticket Buyer Protection—Complete {Y} Guide',
        'Ticket buyer protection on every Seat Outlet order: valid tickets, on time delivery and secure payment. See what is covered and how to get help fast.', 0, 24, true],
    '/customer-testimonials' => ['customer testimonials', 'Customer Testimonials—Where to Read Reviews',
        'Seat Outlet does not publish customer testimonials yet. See what we can show today, how our guarantee works and where to find independent reviews.', 660, 30, true],
    '/seat-outlet-reviews' => ['seat outlet reviews', 'Seat Outlet Reviews—What We Can Show Today',
        'Looking for Seat Outlet reviews? We do not show reviews on our own site yet. See how the guarantee works and how to judge a ticket seller before you buy.', null, null, true],
    '/seat-outlet-bbb' => ['seat outlet bbb', 'Seat Outlet BBB—Our Customer Commitments',
        'Looking for Seat Outlet on the Better Business Bureau? This page does not show a BBB rating. It explains our customer commitments and how to reach us.', null, null, true],
    '/about-seat-outlet' => ['about seat outlet', 'About Seat Outlet—Who We Are & How It Works',
        'About Seat Outlet: an independent resale ticket marketplace for concerts, sports, theater and festivals, with orders fulfilled through TicketNetwork.', null, null, true],
    '/how-to-buy-tickets-online' => ['how to buy tickets online', 'How to Buy Tickets Online—A Simple Guide',
        'Learn how to buy tickets online: find your event, compare seats and prices, check out securely and get your tickets on time. A simple step by step guide.', 210, 28, true],
    '/ticket-partner-program' => ['ticket partner program', 'Ticket Partner Program—Work With Seat Outlet',
        'Run a venue, festival, team or business? Send Seat Outlet a partnership enquiry and see what we are, what we are not and what to ask any marketplace.', null, null, true],
    '/ticket-faq' => ['ticket faq', 'Ticket FAQ—Delivery, Entry & Refunds',
        'The Seat Outlet ticket FAQ answers common questions on buying tickets, delivery times, entry rules, transfers and refunds for concerts, sports and theater.', null, null, true],
    '/ticket-customer-service' => ['ticket customer service', 'Ticket Customer Service—Help With Your Order',
        'Ticket customer service from Seat Outlet. Email us or use the contact form for help with orders, delivery, transfers and event changes. We reply fast.', 70, 29, true],
    '/why-are-concert-tickets-so-expensive' => ['why are concert tickets so expensive', 'Why Are Concert Tickets So Expensive?—Facts',
        'Why are concert tickets so expensive? See who sets ticket prices, fees and release dates, why tickets reach resale marketplaces, and where the money goes.', 20, 32, true],
    '/terms-and-conditions' => ['terms and conditions', 'Terms and Conditions—Rules for Ticket Buyers',
        'Seat Outlet terms and conditions for using our website and buying tickets for live events through our resale marketplace, including orders and refunds.', 9900, 77, true],
    '/privacy-policy' => ['privacy policy', 'Privacy Policy—How Seat Outlet Uses Your Data',
        'The Seat Outlet privacy policy explains how we collect, use, share and protect your personal information when you use our site and buy event tickets.', 22400, 97, true],
    '/cookie-policy' => ['cookie policy', 'Cookie Policy—How Seat Outlet Uses Cookies',
        'The Seat Outlet cookie policy explains how we use cookies and similar technologies on our website, what each type does and how to change your choices.', 810, 55, true],
    '/blog' => ['ticket buying tips', 'Ticket Buying Tips—Guides & Event Picks',
        'Ticket buying tips, event guides and city guides from Seat Outlet. Learn how to find better seats, compare prices and buy tickets with confidence.', 10, 13, false],
    '/our-network' => ['seat outlet network', 'Seat Outlet Network—Partner Sites & Services',
        'The Seat Outlet network: partner sites for tickets, travel and business software, and how each one connects to our live event ticket marketplace.', null, null, false],
    '/dotbooker' => ['dotbooker', 'Dotbooker—Booking & Appointment Software',
        'Dotbooker is booking and appointment software for studios, salons and wellness providers, and part of the Seat Outlet network of partner sites.', null, null, false],
    '/wingcms' => ['wingcms', 'WingCMS—Real Estate Technology',
        'WingCMS is end to end real estate technology for agents and brokers, and part of the Seat Outlet network of partner websites and services.', null, null, false],
    '/salespeep' => ['salespeep', 'Salespeep—CRM for Sales & Marketing',
        'Salespeep is a CRM for sales, marketing and service teams, and part of the Seat Outlet network of partner websites, tools and services.', null, null, false],
    '/signs-n-more' => ['signs n more', 'Signs N More—Branding, Web & Marketing',
        'Signs N More offers branding, signs, web design and digital marketing for businesses, and is part of the Seat Outlet network of partner sites.', null, null, false],
    '/it-sprinkles' => ['it sprinkles', 'It Sprinkles—Custom Cakes in Austin',
        'It Sprinkles makes custom cakes, cupcakes and desserts in Austin for birthdays, weddings and celebrations, and is part of the Seat Outlet network.', null, null, false],
    '/austin-sign-masters' => ['austin sign masters', 'Austin Sign Masters—Custom Signs in Austin',
        'Austin Sign Masters makes custom signs and printing for businesses in Austin, Texas, and is part of the Seat Outlet network of partner sites.', null, null, false],
    '/viralpep' => ['viralpep', 'Viralpep—Social Media Management Tool',
        'Viralpep is a social media management tool to create, schedule, collaborate on and track posts from one dashboard. Part of the Seat Outlet network.', null, null, false],
    '/mindshare-consulting' => ['mindshare consulting', 'Mindshare Consulting—Marketing Agency in Austin',
        'Mindshare Consulting is a full service marketing agency in Austin for SEO, advertising, branding and web design, and the team behind this website.', null, null, false],
    '/image-credits' => ['photo credits', 'Photo Credits—Image Sources and Licenses',
        'Photo credits for the images used on Seat Outlet: the source, the photographer or rights holder and the license for every picture on the site.', null, null, false],
    '/sitemap-page' => ['seat outlet sitemap', 'Seat Outlet Sitemap—Every Section of the Site',
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
