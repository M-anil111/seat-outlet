-- Blog categories (shown as labels and filters on /blog), an optional performer name whose live ticket listings are
-- embedded at the end of the post, and a single author profile for the launch posts.
ALTER TABLE `blog_posts` ADD COLUMN `category` varchar(60) NULL DEFAULT NULL AFTER `featured_image`;
ALTER TABLE `blog_posts` ADD COLUMN `live_search` varchar(120) NULL DEFAULT NULL AFTER `category`;
UPDATE `blog_posts` SET `live_search` = 'Taylor Swift' WHERE `slug` = 'taylor-swift-tour-dates';
UPDATE `blog_posts` SET `category` = 'Ticket Safety' WHERE `slug` = 'how-to-avoid-ticket-scams';
UPDATE `blog_posts` SET `category` = 'Concerts & Tours' WHERE `slug` IN ('upcoming-concert-tours', 'taylor-swift-tour-dates');
UPDATE `blog_posts` SET `category` = 'City Guides' WHERE `slug` IN ('kids-events-in-austin', 'best-concerts-in-nyc', 'things-to-do-in-nyc-in-december');
UPDATE `blog_posts` SET `category` = 'Venues' WHERE `slug` = 'best-concert-venues-in-the-us';
UPDATE `blog_posts` SET `author_name` = 'Jay Mehta' WHERE `author_name` IS NULL OR `author_name` IN ('', 'Seat Outlet Team');
