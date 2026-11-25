-- Some databases were created before the images table had a created_at column (migration 0005 skips a table that already exists;
-- 0038 added the missing updated_at the same way). On 25 Nov 2026 the live admin dashboard answered 500 because imageQueueStats()
-- reads MIN(created_at) for the age of the oldest queued picture. Adding it is a no-op where it is already there (the runner treats
-- "duplicate column" as already done). Existing rows get the time of this migration, so the first "oldest queued" age is short.
ALTER TABLE `images` ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP;
