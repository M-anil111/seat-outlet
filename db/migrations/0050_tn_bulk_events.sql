-- Local copy of the TicketNetwork event catalog, filled by cron/tn-bulk-import.php from GET /catalog/v2/events/bulk (a full refresh about
-- once a day, then hourly changes). Nothing on the site reads this table yet; it only fills it. Prices are the catalog's low, average and
-- high at the time of the pull (seen_at), not live prices. Times: starts_local is the venue's local start; the other times are UTC.
CREATE TABLE IF NOT EXISTS `tn_bulk_events` (
  `event_id` int unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `starts_local` datetime DEFAULT NULL,
  `starts_offset` varchar(40) DEFAULT NULL,
  `takedown_at` datetime DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_status` varchar(40) DEFAULT NULL,
  `venue_id` int unsigned DEFAULT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `city_id` int unsigned DEFAULT NULL,
  `city_name` varchar(120) DEFAULT NULL,
  `state_id` int unsigned DEFAULT NULL,
  `state_abbr` varchar(10) DEFAULT NULL,
  `country_code` varchar(8) DEFAULT NULL,
  `category_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  `ticket_count` int unsigned DEFAULT NULL,
  `has_tickets` tinyint(1) NOT NULL DEFAULT 0,
  `low_price` decimal(10,2) DEFAULT NULL,
  `avg_price` decimal(10,2) DEFAULT NULL,
  `high_price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(8) DEFAULT NULL,
  `uri_component` varchar(255) DEFAULT NULL,
  `performers` mediumtext DEFAULT NULL,
  `tn_created_at` datetime DEFAULT NULL,
  `tn_updated_at` datetime DEFAULT NULL,
  `tn_meta_updated_at` datetime DEFAULT NULL,
  `tn_version` bigint unsigned DEFAULT NULL,
  `seen_at` datetime NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `tn_bulk_events_start_idx` (`starts_local`),
  KEY `tn_bulk_events_venue_idx` (`venue_id`),
  KEY `tn_bulk_events_city_idx` (`city_id`),
  KEY `tn_bulk_events_cat_idx` (`category_path`(100)),
  KEY `tn_bulk_events_seen_idx` (`seen_at`),
  KEY `tn_bulk_events_meta_idx` (`tn_meta_updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Progress and last-success times for the importer (name/value pairs), so a run that stops can resume and a health check can see
-- how old the copy is.
CREATE TABLE IF NOT EXISTS `tn_bulk_state` (
  `name` varchar(40) NOT NULL,
  `value` varchar(255) NOT NULL DEFAULT '',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
