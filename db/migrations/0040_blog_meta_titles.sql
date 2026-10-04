-- Search titles for the launch posts without a colon (the site's title rule: no | - or : in a title). The old ones had a colon,
-- so the normalizer dropped it and then cut words to fit, which produced titles like "Upcoming Concert Tours 12 Announced Tickets".
-- Each title is at most 44 characters so it fits with the brand name. One description was over 155 characters.
UPDATE `blog_posts` SET `meta_title` = 'Ticket Scams and 15 Warning Signs to Know' WHERE `slug` = 'how-to-avoid-ticket-scams';
UPDATE `blog_posts` SET `meta_title` = 'Upcoming Concert Tours 2026 and 2027' WHERE `slug` = 'upcoming-concert-tours';
UPDATE `blog_posts` SET `meta_title` = 'Taylor Swift Tour Dates and Ticket Guide' WHERE `slug` = 'taylor-swift-tour-dates';
UPDATE `blog_posts` SET `meta_title` = 'Kids Events in Austin and Family Shows' WHERE `slug` = 'kids-events-in-austin';
UPDATE `blog_posts` SET `meta_title` = 'Best Concerts in NYC and Where to Go' WHERE `slug` = 'best-concerts-in-nyc';
UPDATE `blog_posts` SET `meta_title` = 'Things to Do in NYC in December 2026' WHERE `slug` = 'things-to-do-in-nyc-in-december';
UPDATE `blog_posts` SET `meta_title` = 'Best Concert Venues in the US to Visit' WHERE `slug` = 'best-concert-venues-in-the-us';
UPDATE `blog_posts` SET `meta_description` = 'A practical guide to the best concerts in NYC, from arenas and historic theaters to indie clubs and jazz halls, with seat advice and ticket tips.' WHERE `slug` = 'best-concerts-in-nyc';
