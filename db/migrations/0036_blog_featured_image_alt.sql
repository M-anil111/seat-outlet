-- Alt text for a post's featured image (hero on the article, card on /blog, og:image:alt).
-- The launch posts get a plain description of what their picture shows.
ALTER TABLE `blog_posts` ADD COLUMN `featured_image_alt` varchar(255) NULL DEFAULT NULL AFTER `featured_image`;
UPDATE `blog_posts` SET `featured_image_alt` = 'Concert tickets on a laptop and a phone, with a green check mark on the phone ticket' WHERE `slug` = 'how-to-avoid-ticket-scams' AND `featured_image_alt` IS NULL;
UPDATE `blog_posts` SET `featured_image_alt` = 'Musicians performing on a stage lit by beams of light, with the crowd in the foreground' WHERE `slug` = 'upcoming-concert-tours' AND `featured_image_alt` IS NULL;
UPDATE `blog_posts` SET `featured_image_alt` = 'Packed crowd at an indoor concert under red and blue stage lights' WHERE `slug` = 'taylor-swift-tour-dates' AND `featured_image_alt` IS NULL;
UPDATE `blog_posts` SET `featured_image_alt` = 'Large crowd at an outdoor festival stage with a city skyline behind it' WHERE `slug` = 'kids-events-in-austin' AND `featured_image_alt` IS NULL;
UPDATE `blog_posts` SET `featured_image_alt` = 'Empty arena with rows of seats facing a lit stage' WHERE `slug` = 'best-concerts-in-nyc' AND `featured_image_alt` IS NULL;
UPDATE `blog_posts` SET `featured_image_alt` = 'Front of a historic theater with a clock tower and a lit marquee' WHERE `slug` = 'things-to-do-in-nyc-in-december' AND `featured_image_alt` IS NULL;
UPDATE `blog_posts` SET `featured_image_alt` = 'Crowd at a large arena concert with stage lights and rigging overhead' WHERE `slug` = 'best-concert-venues-in-the-us' AND `featured_image_alt` IS NULL;
