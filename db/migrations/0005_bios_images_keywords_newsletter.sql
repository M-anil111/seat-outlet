-- Four more tables this codebase already queries (get_bio()/set_bio(),
-- get_image()/set_image(), get_keyword()/set_keyword() in functions.php;
-- ajax/check-email.php + newsletter-email.php for newsletter_leads) but
-- that had no tracked migration anywhere - found by actually executing the
-- app end to end against a real local database rather than just php -l:
-- index.php fatal-errored on a missing `images` table the moment it tried
-- to render for real. CREATE TABLE IF NOT EXISTS throughout, so this is a
-- no-op wherever any of these tables already exist on beta.

-- Performer bio cache (get_bio()/set_bio() - Wikipedia extract, keyed by
-- TicketNetwork performer ID).
CREATE TABLE IF NOT EXISTS `bios` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `performerId` bigint NOT NULL,
  `bio` mediumtext COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `bios_performer_id_unique` (`performerId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Generic image URL cache (get_image()/set_image() - e.g. artist/venue
-- images resolved once and reused, keyed by an arbitrary cache key string).
CREATE TABLE IF NOT EXISTS `images` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `imgkey` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `images_imgkey_unique` (`imgkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Search/suggestion result cache (get_keyword()/set_keyword() - keyed by
-- the search keyword string, `results` holds a serialized/JSON blob per
-- the existing set_keyword() call sites).
CREATE TABLE IF NOT EXISTS `keywords` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `results` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `keywords_keyword_unique` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Newsletter signups (newsletter-email.php's INSERT, ajax/check-email.php's
-- duplicate-email lookup). Column list matches newsletter-email.php's
-- INSERT exactly.
CREATE TABLE IF NOT EXISTS `newsletter_leads` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `page_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `newsletter_leads_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
