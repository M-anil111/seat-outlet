-- Light per-URL SEO/redirect/schema override table (the "light admin
-- table" CMS - see admin/page-rules.php). One row per site path. header.php
-- looks up a row for the current REQUEST_URI and uses it to override the
-- hardcoded per-page-type SEO defaults, or to 301/302 redirect before
-- rendering anything.
CREATE TABLE IF NOT EXISTS `page_rules` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `url_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'Path only, no domain/query string, e.g. /concerts-city/austin-tx-123',
  `meta_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `canonical_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `robots` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
    COMMENT 'e.g. "index,follow" or "noindex,nofollow" - overrides the page default when set',
  `schema_json` text COLLATE utf8mb4_general_ci DEFAULT NULL
    COMMENT 'Optional raw JSON-LD to inject for this page',
  `redirect_to` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `redirect_code` smallint DEFAULT NULL COMMENT '301 or 302',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `page_rules_url_path_unique` (`url_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
