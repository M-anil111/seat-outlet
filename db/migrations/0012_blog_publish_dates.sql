-- The launch posts (0010) were stamped a few hours into 2 Oct 2026. A server whose clock is behind UTC treats that as
-- "not yet published" and the blog index shows nothing. Move them to the day before, spread out so the newest-first
-- order is kept. Only touches the seven launch posts, and only if they exist.
UPDATE `blog_posts` SET `published_at` = '2026-10-01 09:00:00' WHERE `slug` = 'how-to-avoid-ticket-scams';
UPDATE `blog_posts` SET `published_at` = '2026-10-01 08:00:00' WHERE `slug` = 'upcoming-concert-tours';
UPDATE `blog_posts` SET `published_at` = '2026-10-01 07:00:00' WHERE `slug` = 'taylor-swift-tour-dates';
UPDATE `blog_posts` SET `published_at` = '2026-10-01 06:00:00' WHERE `slug` = 'kids-events-in-austin';
UPDATE `blog_posts` SET `published_at` = '2026-10-01 05:00:00' WHERE `slug` = 'best-concerts-in-nyc';
UPDATE `blog_posts` SET `published_at` = '2026-10-01 04:00:00' WHERE `slug` = 'things-to-do-in-nyc-in-december';
UPDATE `blog_posts` SET `published_at` = '2026-10-01 03:00:00' WHERE `slug` = 'best-concert-venues-in-the-us';
