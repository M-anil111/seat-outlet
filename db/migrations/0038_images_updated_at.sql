-- Some databases were created before the images table had an updated_at column (migration 0005 skips a table that already
-- exists), and the admin pictures view sorts by it. Adding it is a no-op where it is already there (the runner treats
-- "duplicate column" as already done).
ALTER TABLE `images` ADD COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
