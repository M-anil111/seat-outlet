<?php
// Include-only file: answer 404 if it is requested directly over the web (it would render a fragment or an error).
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/**
 * <title> and meta description for the static pages that do not set their own.
 * Keyed by URL path. header.php uses these when a page has no $pageMetaTitle,
 * so the page also gets og:/twitter: tags, a canonical URL and the baseline
 * Organization/WebSite schema. Titles get " | Seat Outlet" appended and are
 * kept near 60 characters, descriptions near 150, and each one restates what
 * the page itself says (no claims that are not on the page).
 */
return [
    '/about-us' => ['Our Story: A Smarter Way to Buy Event Tickets',
        'Learn how Seat Outlet helps fans reach the concerts, sports and theater events they love, with smarter tools, trusted partners and our guarantee.'],
    '/contact' => ['Contact Seat Outlet Support',
        'Contact Seat Outlet by email, phone or in person for help with ticket issues, order questions and account support. Our support team is here to help.'],
    '/faq' => ['Ticket FAQs: Delivery, Entry and Event Info',
        'Answers about buying tickets for concerts, sports, theater and festivals, including ticket delivery, entry requirements and event-specific details.'],
    '/buyer-protection' => ['Buyer Protection for Every Ticket Order',
        'Every Seat Outlet ticket order is covered by buyer protection. If there is an issue with ticket validity, we work to provide replacement tickets or a refund.'],
    '/privacy-policy' => ['Privacy Policy',
        'How Seat Outlet collects, uses, discloses and protects your personal information when you discover, buy and sell tickets for live events on our marketplace.'],
    '/terms-and-conditions' => ['Terms of Use',
        'The terms that govern your use of the Seat Outlet websites, mobile apps and services, including buying, selling, transferring and using tickets.'],
    '/cookie-policy' => ['Cookie Policy',
        'How Seat Outlet uses cookies and similar technologies on our websites, mobile apps and services, and what each type of cookie is used for.'],
    '/why-us' => ['Why Partner with Seat Outlet',
        'Learn why businesses choose to partner with Seat Outlet: insights, reach and streamlined tools for the modern live event ticketing industry.'],
    '/cities' => ['City Guides: Live Events by City',
        'Browse Seat Outlet city guides to find upcoming concert, sports, theater and festival tickets in top cities across the country.'],
];
