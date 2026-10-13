-- Last good copy of each TicketNetwork event the site has listed or opened. The event page falls back to it when the live detail call
-- fails, so an event that was once loaded keeps its page through a throttle or an outage. Unlike the file cache, rows are not
-- deleted by a cache sweep, and the nightly database dump (cron/backup-db.php) includes them. payload is the event row as TicketNetwork
-- returned it (JSON). seen_at is when the copy was last refreshed; old rows are pruned by cron/warm-event-details.php.
CREATE TABLE IF NOT EXISTS `tn_event_snapshot` (
  `event_id` int unsigned NOT NULL,
  `payload` mediumtext NOT NULL,
  `seen_at` datetime NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `tn_event_snapshot_seen_idx` (`seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
