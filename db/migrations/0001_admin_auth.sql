-- Admin authentication tables for the Seat Outlet Admin Panel.
-- Applied via db/migrate.php (see db/migrations/README.md); every
-- statement is safe to run more than once.

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
