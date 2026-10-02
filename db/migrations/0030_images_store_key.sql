-- Entity images: record where the stored file lives, and how often a miss was requested.
--
--   store_key   the object key the resolver wrote ("artists/<md5>.webp"). New files are keyed by the imgkey hash, never
--               by the entity slug, so two entities with the same slug cannot overwrite or inherit each other's file and
--               an old object is never re-used under a new licence label. NULL = a legacy slug-keyed object (still read).
--   miss_hits   sampled count of page views that got the initials tile because no licensed picture was stored yet; the
--               admin "misses" view sorts by it so the most-seen gaps are fixed first.
ALTER TABLE `images`
  ADD COLUMN `store_key` VARCHAR(255) NULL AFTER `attribution`,
  ADD COLUMN `miss_hits` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `attempts`,
  ADD INDEX `images_miss_hits` (`status`, `miss_hits`);
