-- Admin authentication tables for the Seat Outlet Admin Panel.
-- Applied directly against the beta database; kept here for reference and
-- so the same schema can be reproduced on other environments.

CREATE TABLE IF NOT EXISTS `admin_users` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `admin_users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `admin_password_resets` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `admin_password_resets_admin_id_idx` (`admin_id`),
  CONSTRAINT `fk_admin_password_resets_admin` FOREIGN KEY (`admin_id`)
    REFERENCES `admin_users` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Light per-URL SEO/redirect/schema override table (the "light admin table"
-- CMS). One row per site path. A front-end page can look up a row for its
-- own REQUEST_URI and use it to override the hardcoded defaults it would
-- otherwise render, or to 301/302 redirect before rendering anything.
-- Front-end wiring (reading this table from header.php) is a follow-up step
-- and is intentionally not part of this migration - this only adds the data
-- layer and the admin screens that manage it.
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
