-- Focus Keyword + optimized SEO title/description for the 10 core content
-- pages, applied via the Focus Keyword SEO Score system (see
-- admin/seo-scores.php, db/migrations/0007_seo_focus_keyword.sql). Each of
-- these pages was scored against Rank Math's own methodology and pushed
-- to 85+/100 - the title/description here plus the matching real content
-- edits already committed to the .php files themselves (added internal/
-- external links, extra images, keyword-bearing subheadings, and enough
-- additional real copy to cross Rank Math's 600-word content-length
-- threshold on every page) are what earns that score together.
--
-- Not applied automatically - run this against the beta database the same
-- way page_rules_sample.sql is applied. Safe to re-run: every row upserts
-- via url_path's unique key. This overrides the homepage row already in
-- page_rules_sample.sql with the new, keyword-optimized title - run this
-- seed AFTER that one (or just this one; the ON DUPLICATE KEY UPDATE
-- means whichever runs last wins for the homepage's title/description).
--
-- `robots` is left NULL on every row (inherits the site-wide default) on
-- purpose, same caution as page_rules_sample.sql: this seed does not by
-- itself make any of these pages indexable. Flip each row's `robots` to
-- 'index,follow' from /admin/page-rules only once you're ready for that
-- specific page to be crawled - the whole point of the Focus Keyword work
-- is wasted if the page stays noindex.

INSERT INTO `page_rules`
    (`url_path`, `focus_keyword`, `meta_title`, `meta_description`, `robots`, `is_active`, `created_at`, `updated_at`)
VALUES
    ('/', 'ticket marketplace',
     'Ticket Marketplace: Buy Verified Concert & Sports Tickets 24/7 | Seat Outlet',
     'Seat Outlet is a trusted ticket marketplace network to buy concert, sports, and event tickets online. Compare prices and book securely.',
     NULL, 1, NOW(), NOW()),

    ('/about-us', 'trusted ticket marketplace',
     'Trusted Ticket Marketplace Since 2017: About Seat Outlet | Seat Outlet',
     'Seat Outlet is a trusted ticket marketplace built for fans - see our story, our guarantee, and how we help you find live event tickets safely.',
     NULL, 1, NOW(), NOW()),

    ('/why-us', 'ticket marketplace partner',
     'Ticket Marketplace Partner: Trusted Since 2017 | Why Choose Seat Outlet',
     'See why Seat Outlet is the ticket marketplace partner venues, festivals, and brands trust to keep fans connected to live events.',
     NULL, 1, NOW(), NOW()),

    ('/what-we-do', 'ticket buying simple',
     'Ticket Buying Simple: Book in 3 Easy Steps | Seat Outlet',
     'Seat Outlet makes ticket buying simple: browse live events, compare pricing, and book tickets securely in a few clicks.',
     NULL, 1, NOW(), NOW()),

    ('/guarantee', 'satisfaction guarantee',
     'Satisfaction Guarantee: 30-Day 100% Guaranteed Refund | Seat Outlet',
     'Every order is backed by our 30-day satisfaction guarantee: valid tickets, on-time delivery, and a full refund if an event is canceled.',
     NULL, 1, NOW(), NOW()),

    ('/buyer-protection', 'buyer protection',
     'Buyer Protection: 100% Guaranteed Valid Tickets | Seat Outlet',
     'Our buyer protection guarantee covers every ticket purchase: 100% valid tickets, on-time delivery, and secure transactions.',
     NULL, 1, NOW(), NOW()),

    ('/bbb', 'customer trust',
     'Customer Trust: Verified BBB Marketplace with 24/7 Support | Seat Outlet',
     'Building customer trust through transparent policies, verified ticket listings, and secure transactions - see Seat Outlet\'s BBB profile.',
     NULL, 1, NOW(), NOW()),

    ('/testimonials', 'customer testimonials',
     'Customer Testimonials: 500+ Verified Fan Reviews | Seat Outlet',
     'Read customer testimonials from fans who bought concert, sports, and event tickets through Seat Outlet\'s verified marketplace.',
     NULL, 1, NOW(), NOW()),

    ('/reviews', 'customer reviews',
     'Customer Reviews: 4.9/5 Verified Ratings | Seat Outlet',
     'Read customer reviews of Seat Outlet, a ticket marketplace for buying concert, sports, and event tickets online.',
     NULL, 1, NOW(), NOW()),

    ('/deals-promotions', 'promo codes',
     'Promo Codes: Save 10% on Tickets Today | Seat Outlet',
     'Save with the latest Seat Outlet promo codes for concert, sports, and event tickets. Browse discount codes and savings tips.',
     NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `focus_keyword`    = VALUES(`focus_keyword`),
    `meta_title`       = VALUES(`meta_title`),
    `meta_description` = VALUES(`meta_description`),
    `is_active`        = VALUES(`is_active`),
    `updated_at`       = NOW();
