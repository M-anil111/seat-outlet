-- Focus Keyword SEO Score (see admin/seo-scores.php): adds a focus_keyword
-- column to the two tables that already hold a page's editable SEO fields
-- (page_rules for static pages, blog_posts for blog content), so the score
-- engine in functions.php (computeSeoScore()) has somewhere to read/write
-- it from. Nullable and additive - existing rows are unaffected until an
-- admin sets a keyword.
ALTER TABLE `page_rules`
  ADD COLUMN `focus_keyword` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `url_path`;

ALTER TABLE `blog_posts`
  ADD COLUMN `focus_keyword` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `title`;
