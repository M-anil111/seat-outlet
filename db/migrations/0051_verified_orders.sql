-- Orders whose purchase was verified outside the visitor's browser: by a signed return link or by TicketNetwork's order webhook
-- (inc/order-verify.php). /order-confirmation fires the GA4 `purchase` event only for a row here, and claims `ga4_fired_at` with one
-- atomic UPDATE so an order is counted once no matter how often the page is opened. `total` is the verified amount, never a URL value.
CREATE TABLE IF NOT EXISTS `verified_orders` (
  `order_id` varchar(40) NOT NULL,
  `event_id` int unsigned NOT NULL DEFAULT 0,
  `total` decimal(10,2) DEFAULT NULL,
  `currency` varchar(8) NOT NULL DEFAULT 'USD',
  `source` varchar(20) NOT NULL,
  `verified_at` datetime NOT NULL,
  `ga4_fired_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `idx_verified_at` (`verified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
