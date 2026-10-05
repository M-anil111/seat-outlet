-- Small shared counter table for rate limits (sign-ups, admin login, image queue). Keys are hashes, so no
-- address or email is stored. Rows expire by themselves (expires_at) and are swept by the code that writes them.
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `k` char(40) COLLATE utf8mb4_general_ci NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`k`),
  KEY `rate_limits_expires_idx` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
