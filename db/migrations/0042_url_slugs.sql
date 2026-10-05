-- Clean URLs without TicketNetwork ids: /artist/taylor-swift, /city/austin-tx, /event/taylor-swift-austin-tx-2026-10-12.
-- One row per entity: the slug is chosen once, the first time the site links to the entity, and never changes after that
-- (so every URL stays stable). Lookups go both ways: (type, ext_id) to build links, (type, slug) to find the entity.
--   type     performer, venue, city, state, country, category or event
--   ext_id   the TicketNetwork id (country: the two letter code), kept as text so one column fits all types
CREATE TABLE IF NOT EXISTS `url_slugs` (
  `type` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,
  `ext_id` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`type`, `ext_id`),
  UNIQUE KEY `url_slugs_type_slug` (`type`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
