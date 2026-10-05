# Beta server runbook (for tech support)

Everything here is run on the beta server as the site user, from the site folder (the folder that contains `db/migrate.php`).
Send the full output of every command back, including errors. Do not paste passwords or keys into emails or tickets.

## 1. Bring the database up to date (do this first)

The code on beta is ahead of its database. Until this is done, pictures do not load and some pages can fail.

```
cd <site folder>
php db/migrate.php --status        # lists every migration and whether it is applied
php db/migrate.php                 # applies the pending ones
php db/migrate.php --status        # run again: nothing should be pending
```

Expected: the last line of the second `--status` shows all migrations applied (0001 to 0039 at the time of writing).
If `php db/migrate.php` prints an error, send the whole output. The runner stops at the first statement that really fails and says which file and statement.

Also run, once, and send the output:

```
php tools/check-env.php
mysql -e "SHOW CREATE TABLE images\G"        # use the site's database name and user
```

## 2. Settings in `inc/env.local.php`

```
TN_TOKEN_DIR=/home/<user>/tn-token        # a folder every PHP process can write; stops TicketNetwork login tokens fighting each other
SO_BACKUP_DIR=/home/<user>/seatoutlet-backups   # OUTSIDE the web root
SO_BACKUP_KEEP_DAYS=14
# optional, copies each backup to a private bucket:
# SO_BACKUP_S3=s3://<private-bucket>/seatoutlet
```

Create the folders with mode 700 and the site user as owner.

## 3. Scheduled jobs (cron)

| Job | Command | How often |
|---|---|---|
| Pictures | `php cron/resolve-images.php` | every 10 minutes |
| Performer alerts | `php cron/send-alerts.php` | once a day |
| Price-drop alerts | `php cron/send-price-alerts.php` | every 3 hours |
| Sitemap files (optional; the site also rebuilds them itself after page requests) | `php cron/build-sitemaps.php` | every 6 hours |
| New-event announcements (IndexNow, production only) | `php cron/indexnow.php` | every 30 minutes (needs `SO_INDEXNOW_KEY`) |
| Database backup | `php cron/backup-db.php` | once a day, at night |
| GeoIP update | the weekly job already added | weekly |
| Home and list caches | the existing cache-rebuild crons | as already scheduled |

Test each once by hand first. `send-alerts.php` and `send-price-alerts.php` accept `--dry-run` (prints what would be emailed, sends nothing).
Email needs `SMTP_USER`, `SMTP_PASS` and `SO_MAIL_ADDRESS` in `inc/env.local.php`.

Check a backup after the first run: `php cron/backup-db.php --verify=<path to the .sql.gz file>`. It must print `OK`.

## 4. Web server rules

- Do not serve `composer.json`, `composer.lock`, `cache/`, `vendor/`, `docs/`, `db/`, `cron/`, `inc/` or `tools/` over the web (403 or 404). Add the same rule for any `*.sql`, `*.sql.gz`, `*.log`.
- Serve `/manifest.webmanifest` as `application/manifest+json`.
- `/sw.js` must not be cached for long (`Cache-Control: no-cache`), and neither should `/offline.html`.
- Turn on gzip or brotli for text, CSS, JS, JSON and SVG.

## 5. After any deploy

1. `php db/migrate.php` (the deploy script already does this; check `deploy.log` for the line `MIGRATION FAILED`).
2. Purge the Cloudflare cache.
3. Open one event page, one artist page and the home page and confirm they load without an error.

## 6. Making the GitHub repository private

The repository is currently public. If it is made private, the beta server's automatic pull from `main` stops working until the server has its own read access:

1. On the server, create a read-only deploy key: `ssh-keygen -t ed25519 -f ~/.ssh/seatoutlet_deploy -N ""`.
2. In GitHub (repository > Settings > Deploy keys) add the public key (`seatoutlet_deploy.pub`) with "Allow write access" OFF.
3. Point the server's checkout at the SSH address (`git remote set-url origin git@github.com:M-anil111/seat-outlet.git`) and make sure the deploy script uses that key.
4. Test: `git fetch origin main` as the site user.
5. Only then switch the repository to private (GitHub > Settings > General > Danger Zone > Change visibility).

Old commits in this repository contain values that look like credentials. Making it private does not remove them. Rotate every key listed in `docs/launch-checklist.md` regardless.
