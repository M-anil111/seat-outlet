-- FAQ content backing getFaqs() (see functions.php). This table was already
-- assumed to exist by performer.php/tickets.php/search.php/performer-new.php
-- (all query it via getFaqs('performer')) but had no tracked migration -
-- CREATE TABLE IF NOT EXISTS here so re-running this is a no-op wherever the
-- table's already there, and a real starting point wherever it isn't.
CREATE TABLE IF NOT EXISTS `faq` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'Matched with LIKE %type% by getFaqs() - e.g. "performer", "concerts", "sports"',
  `question` varchar(500) COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'May contain [artist_name]/[category]/[location] tokens, substituted at render time',
  `answer` text COLLATE utf8mb4_general_ci NOT NULL
    COMMENT 'May contain [artist_name]/[category]/[location] tokens, substituted at render time',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `faq_type_question_unique` (`type`, `question`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
