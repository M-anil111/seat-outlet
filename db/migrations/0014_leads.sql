-- Email leads (WS1). One row per email address; what the visitor wants alerts for goes into lead_interests,
-- so a second sign-up from the same address adds an interest instead of failing.
-- ip_hash is a salted SHA-256 of the visitor address (never the address itself).
-- unsubscribe_token is a random token that the unsubscribe link carries; rows imported from the old
-- newsletter_leads table get theirs the first time a mail is built for them.
CREATE TABLE IF NOT EXISTS `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(254) COLLATE utf8mb4_general_ci NOT NULL,
  `fname` varchar(70) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `lname` varchar(70) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `source` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'site',
  `interest_type` varchar(12) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `interest_id` int unsigned NOT NULL DEFAULT 0,
  `interest_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `page` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `ip_hash` char(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `unsubscribe_token` char(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `welcome_sent_at` datetime DEFAULT NULL,
  `notified_at` datetime DEFAULT NULL,
  `brevo_synced_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_email_unique` (`email`),
  UNIQUE KEY `leads_unsubscribe_token_unique` (`unsubscribe_token`),
  KEY `leads_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `lead_interests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `interest_type` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  `interest_id` int unsigned NOT NULL DEFAULT 0,
  `interest_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `source` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'site',
  `page` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_interests_unique` (`lead_id`,`interest_type`,`interest_id`),
  KEY `lead_interests_type_idx` (`interest_type`,`interest_id`),
  CONSTRAINT `lead_interests_lead_fk` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bring the old newsletter signups over (the old table stays as it is). Re-runnable: INSERT IGNORE on the unique email.
INSERT IGNORE INTO `leads` (`email`, `fname`, `lname`, `source`, `page`, `created_at`)
SELECT LOWER(TRIM(`email`)), LEFT(IFNULL(`first_name`, ''), 70), LEFT(IFNULL(`last_name`, ''), 70), 'legacy-newsletter', '', `created_at`
FROM `newsletter_leads` WHERE `email` <> '' AND CHAR_LENGTH(`email`) <= 254;
