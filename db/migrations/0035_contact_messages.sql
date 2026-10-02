-- Messages sent through the contact form (/ticket-customer-service) and the partnership enquiry form (/ticket-partner-program).
-- ip_hash is a salted SHA-256 of the sender's IP address (used only for rate limiting), never the address itself.
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `name` varchar(140) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `order_ref` varchar(60) DEFAULT NULL,
  `subject` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `page` varchar(190) DEFAULT NULL,
  `ip_hash` char(64) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `recaptcha_score` decimal(3,2) DEFAULT NULL,
  `email_status` varchar(190) DEFAULT NULL,
  `status` enum('new','read','closed') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`),
  KEY `contact_messages_created` (`created_at`),
  KEY `contact_messages_ip_created` (`ip_hash`,`created_at`),
  KEY `contact_messages_email_created` (`email`,`created_at`),
  KEY `contact_messages_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
