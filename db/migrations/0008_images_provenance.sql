-- Image provenance + lifecycle for the `images` cache table.
--
-- Before this, `images` held only imgkey => url, written once and never
-- expired. Anything the resolver ended up with was cached forever, including
-- the generic category fallback after a transient Wikipedia 429 or timeout,
-- and nothing recorded where an image came from or under what license.
--
-- New columns (all nullable so existing rows keep working):
--   entity_type / entity_name  what the row is for ('artist','team','venue',
--                              'festival','city') and the display name
--   status      ok        a real image resolved from a source
--               manual    uploaded/pasted in the admin; never auto-replaced
--               fallback  no image found; url is the generic fallback and
--                         expires_at says when to try again
--               pending   queued for cron/resolve-images.php
--   source / source_url / license / attribution   provenance, so a credit
--               can be rendered and a license audit is a SELECT
--   attempts / resolved_at / expires_at   retry bookkeeping
--
-- Existing rows that hold a fallback URL are flipped to status=fallback and
-- expired immediately so the next cron run re-resolves them.

ALTER TABLE `images`
  ADD COLUMN `entity_type` VARCHAR(20) NULL AFTER `imgkey`,
  ADD COLUMN `entity_name` VARCHAR(255) NULL AFTER `entity_type`,
  ADD COLUMN `status` ENUM('ok','manual','fallback','pending') NOT NULL DEFAULT 'ok' AFTER `url`,
  ADD COLUMN `source` VARCHAR(40) NULL AFTER `status`,
  ADD COLUMN `source_url` VARCHAR(1000) NULL AFTER `source`,
  ADD COLUMN `license` VARCHAR(100) NULL AFTER `source_url`,
  ADD COLUMN `attribution` VARCHAR(500) NULL AFTER `license`,
  ADD COLUMN `attempts` INT NOT NULL DEFAULT 0 AFTER `attribution`,
  ADD COLUMN `resolved_at` DATETIME NULL AFTER `attempts`,
  ADD COLUMN `expires_at` DATETIME NULL AFTER `resolved_at`,
  ADD INDEX `images_status_expires` (`status`, `expires_at`),
  ADD INDEX `images_entity` (`entity_type`, `entity_name`(100));

UPDATE `images`
   SET `status` = 'fallback', `expires_at` = NOW()
 WHERE `url` LIKE '%/categories/%'
    OR `url` LIKE '%/images/venue.webp'
    OR `url` = '';
