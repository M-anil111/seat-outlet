-- Customer accounts were removed from the site (sign in, register and /account are gone). This drops their four tables and the stored
-- data in them: customer email addresses, sign-in link and session hashes, saved events. It is permanent; the nightly backup
-- (cron/backup-db.php) is the only copy. Order matters: the two tables with a foreign key to `users` go first.
-- The admin panel's own sign in uses `admin_users` and `admin_password_resets` (0001_admin_auth.sql): not touched here.
DROP TABLE IF EXISTS `user_saved_events`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `login_links`;
DROP TABLE IF EXISTS `users`;
