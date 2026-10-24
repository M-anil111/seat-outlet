-- What the removed-event redirect needs to remember about an event, filled from event page visits, performer pages (source 'catalog')
-- and the bulk importer (source 'bulk'): the canonical slug, the date, the name and when the row was last refreshed. One statement per
-- column, so a run that stopped half way can be run again.
ALTER TABLE `event_redirects` ADD COLUMN `event_slug` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `event_redirects` ADD COLUMN `event_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `event_redirects` ADD COLUMN `event_date` datetime DEFAULT NULL;
ALTER TABLE `event_redirects` ADD COLUMN `source` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'visit';
ALTER TABLE `event_redirects` ADD COLUMN `updated_at` datetime DEFAULT NULL;
