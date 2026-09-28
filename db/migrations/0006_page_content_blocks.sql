-- Page Content Management: lets an admin edit specific copy blocks on
-- static pages (About Us, Why Us, What We Do, Guarantee, Buyer Protection,
-- etc.) without a code deploy. Same shape/convention as page_rules
-- (db/migrations/0002_page_rules.sql): a page opts in by calling
-- getContentBlock($pagePath, $blockKey, $defaultHtml) around a piece of
-- copy - if no row exists yet (the common case for a page that hasn't been
-- edited), the page's own hardcoded $defaultHtml renders unchanged, so
-- adding this feature has zero effect on any page until an admin actually
-- edits something.
CREATE TABLE IF NOT EXISTS `page_content_blocks` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `page_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'Path only, no domain/query string, e.g. /about-us',
  `block_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'Stable identifier the page''s own code references, e.g. hero-story',
  `label` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'Human-readable name shown in the admin editor',
  `content` mediumtext COLLATE utf8mb4_general_ci
    COMMENT 'Raw HTML. NULL/empty means "use the page''s own default".',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `page_content_blocks_path_key_unique` (`page_path`, `block_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
