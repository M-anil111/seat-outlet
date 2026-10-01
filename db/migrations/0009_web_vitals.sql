-- Real-user page speed (js/vitals.js -> ajax/vitals.php).
--
-- One row per metric per sampled page view. Deliberately holds no personal data: no IP address, no cookie or
-- user id, no URL path or query string. Only the kind of page, the metric, the value, how it rates against the
-- published thresholds (web.dev/vitals), the device class and the connection type. Report with
-- `php tools/vitals-report.php`; `php cron/prune-vitals.php` deletes rows older than 90 days.
CREATE TABLE IF NOT EXISTS `web_vitals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_type` VARCHAR(24) NOT NULL,
  `metric` VARCHAR(8) NOT NULL,
  `metric_value` DOUBLE NOT NULL,
  `rating` VARCHAR(20) NOT NULL,
  `device` VARCHAR(8) NOT NULL,
  `connection_type` VARCHAR(8) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `web_vitals_metric_page` (`metric`, `page_type`, `created_at`),
  KEY `web_vitals_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
