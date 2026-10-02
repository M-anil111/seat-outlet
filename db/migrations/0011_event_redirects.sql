-- Remembers which performer (and category) an event page belonged to, so that when TicketNetwork later drops the event
-- the old URL can be redirected to the performer's page instead of answering 404 (see soEventRedirectTarget()).
CREATE TABLE IF NOT EXISTS `event_redirects` (
  `event_id` bigint NOT NULL,
  `performer_id` bigint DEFAULT NULL,
  `performer_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category_path` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
