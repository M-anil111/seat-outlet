# Database migrations

`php db/migrate.php` applies every `NNNN_name.sql` in this folder that is not yet recorded in the `schema_migrations` table, in filename order, and `php db/migrate.php --status` lists what is applied. See "Database migrations" in `CONTRIBUTING.md` for the rules (next free number, re-runnable statements, never edit a merged file).

The runner skips a statement that fails only because its change already exists (duplicate column or key name, table exists, nothing to drop), so a migration that was interrupted half way, or applied by hand, can be run again.
