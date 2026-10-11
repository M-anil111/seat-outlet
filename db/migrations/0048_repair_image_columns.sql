-- Migration 0030 used a single multi-column ALTER. On an older database with store_key already present,
-- the duplicate-column skip could mark the whole migration applied without adding miss_hits or its index.
-- Separate statements let the migration runner skip only the individual change that already exists.
ALTER TABLE `images` ADD COLUMN `store_key` VARCHAR(255) NULL AFTER `attribution`;
ALTER TABLE `images` ADD COLUMN `miss_hits` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `attempts`;
ALTER TABLE `images` ADD INDEX `images_miss_hits` (`status`, `miss_hits`);
