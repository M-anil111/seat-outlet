# Seat Outlet

Ticket marketplace site (seatoutlet.com). Plain PHP 8.1+ pages on MariaDB/MySQL, with live ticket data from the TicketNetwork API and a seat map and checkout hand-off from TicketNetwork's hosted tools. There is no framework and no build step for PHP; `vendor/` is committed on purpose.

This file is the quick start. Details live in `CONTRIBUTING.md` and `docs/`. AI assistants and new developers should also read `CLAUDE.md` (the map of the system and the rules that matter).

## Run it locally

You need PHP 8.1 or newer with `mysqli`, `curl`, `mbstring`, `gd` (with WebP support, for the image tools) and `zlib`, plus MariaDB or MySQL.

1. Create an empty database and a user for it.
2. Copy `deploy/env.local.php.example` to `inc/env.local.php` and fill it in. It is a list of `putenv()` lines, it is git-ignored, and the deploy never overwrites it. At minimum set the `DB_*` values, `HOME_URL` (for example `http://127.0.0.1:8000`), `CONSUMER_KEY`, `CONSUMER_SECRET`, `GAPI_KEY`, `AWS_ACCESS_KEY`, `AWS_SECRET_KEY` and `RECAPTCHA_SECRET_KEY`. Test values are fine for the last four if you do not use those features. A `beta.`, `staging.` or `localhost` `HOME_URL` makes the site `noindex`.
3. Check the result: `php tools/check-env.php` (it prints set or not set, never a value).
4. Create the schema: `php db/migrate.php`. Safe to run again; it applies only what is missing.
5. Start the site: `php -S 127.0.0.1:8000 -t .` and open `http://127.0.0.1:8000`. Some clean URLs (`/venue/<slug>`, `/artist/<slug>`, `/event/<slug>`) are rewritten by the web server in production (`docs/server-rewrites.md`); the built-in server needs a small router script for those. The tests do not need it.

TicketNetwork's sandbox API works with sandbox credentials (events dated 2070, one dollar prices). Live credentials point at real inventory: do not use them for experiments.

## Test it

```bash
for t in tools/test-*.php; do php "$t" || echo "FAILED: $t"; done   # needs the database from step 1 (set DB_* in the shell or in inc/env.local.php)
php tools/check-arg-counts.php . && php tools/check-undefined-functions.php .
composer audit
```

Browser tests (`tools/test-*.mjs`) need a running site and Playwright; CI runs them (`.github/workflows/ci.yml`). CI is the reference list of what must pass.

## Deploy

Merging to `main` is deployed by `deploy/pull-deploy.sh`, which runs from cron on the server every two minutes: it pulls `main`, copies the code into the web root, dumps the database if a migration is pending, applies migrations, and writes the deployed commit to `.deployed-commit`. To roll the code back, run the script once with `DEPLOY_REF=<previous commit>` (every deploy logs that command). Full details, the server paths and the config file: `docs/pull-deploy.md`.

Check what is live: `https://seatoutlet.com/ajax/health.php` shows `ok`, `time` and `commit`. The detailed view (migrations, ticket budget, image queue) needs the `X-Health-Token` header.

## Secrets (names only)

Real values live in the server's `inc/env.local.php` and, for the deploy workflow, in GitHub Actions secrets. They are never committed. The full list of names is `.env.example`; the main ones are `DB_PASS`, `CONSUMER_KEY`, `CONSUMER_SECRET`, `GAPI_KEY`, `AWS_ACCESS_KEY`, `AWS_SECRET_KEY`, `RECAPTCHA_SECRET_KEY`, `SMTP_PASS`, `SENTRY_DSN`, `HEALTH_TOKEN`, `TN_WEBHOOK_SECRET`, `BREVO_API_KEY`. Rotation steps: `docs/secret-rotation.md`.

## When something breaks

- **Sentry** receives PHP errors and warnings when `SENTRY_DSN` is set (`functions.php`).
- **The PHP error log** gets every `error_log()` line: failed lead sign-ups, failed emails, DB trouble, and `cron <name> FAILED: ...` from any cron script.
- **Health:** `/ajax/health.php` (see above) and `php tools/check-env.php` on the server.
- **Database trouble** shows visitors a short 503 page, never the error text (`db/config.php`).
- **Backups:** `cron/backup-db.php` writes a verified nightly dump outside the web root; `docs/reliability.md` covers the ticket feed, caching and what the site does when TicketNetwork is slow.
