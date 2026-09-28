# Developing on Seat Outlet

## Local setup

1. Copy `.env.example` to `.env` and fill in real values (TicketNetwork
   sandbox credentials, a local MySQL database, etc.) - this is for local
   development only. Production/beta never reads `.env`; see **Deploy**
   below.
2. Install PHP dependencies: `composer install`.
3. Apply the database schema: `php db/migrate.php` (see **Database
   migrations**).

## Before you push

Run the same checks CI runs, so you find out locally instead of waiting on
a red PR:

```bash
# Every PHP file parses
find . -name '*.php' -not -path './vendor/*' -not -path './phpmailer/*' -print0 \
  | xargs -0 -n1 php -l

# Static checks that have caught real, previously-shipped bugs:
php tools/check-arg-counts.php .        # calls missing a required argument
php tools/check-undefined-functions.php . # calls to functions that don't exist

# Dependencies
composer validate --no-check-publish --no-check-all
composer audit
```

All four should come back clean. `tools/check-arg-counts.php` and
`tools/check-undefined-functions.php` are exactly what found the bug where
25 listing pages were fatal-erroring before this repo had any CI at all -
run them, they're fast.

## Database migrations

Schema lives in `db/migrations/*.sql`, applied in filename order, tracked
in a `schema_migrations` table so re-running is always safe:

```bash
php db/migrate.php            # apply anything pending
php db/migrate.php --status   # see what's applied vs. pending
```

**Adding a schema change**: add a new `db/migrations/NNNN_description.sql`
file (next number after the highest existing one), written so it's safe to
run more than once (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT
EXISTS` where your MySQL version supports it, etc.). Don't edit an already-
merged migration file - once it's merged, treat it as immutable and add a
new one instead, the same way you would with any other migrations tool.

**Sample/demo data** lives separately in `db/seeds/*.sql` and is never
applied automatically - `php db/migrate.php --seed=NAME` applies
`db/seeds/NAME.sql` on request. Seeds should also be safe to re-run
(`INSERT ... ON DUPLICATE KEY UPDATE`), since they're not tracked the way
migrations are.

CI applies every migration (and the sample seed) against a fresh MySQL
container on every PR - a broken migration fails CI before it can ever
reach beta.

## CI/CD

- **`.github/workflows/ci.yml`** runs on every pull request and every push
  to a branch other than `main`: lints every PHP file, runs the two static
  checks above, validates `composer.lock`, runs `composer audit`, and
  applies every migration (twice, to prove idempotency) plus the sample
  seed against a throwaway MySQL service container.
- **`.github/workflows/deploy.yml`** runs on push to `main`: lints again as
  a final gate, then deploys to beta over SFTP using the secrets below.
  This is the only workflow that touches the real server.
- **`.github/workflows/lighthouse.yml`** runs weekly (or on demand from the
  Actions tab): fetches the live sitemap and runs real Google Lighthouse
  (Performance/Accessibility/Best Practices/SEO) against every URL in it
  except individual `/event/...` pages, then commits the results to
  `data/lighthouse-scores.json`, which `admin/lighthouse-scores.php`
  displays. This runs in CI rather than as a PHP admin feature because
  real Lighthouse needs a real headless Chrome to measure actual page-load
  performance - something this app's production host doesn't run, and
  GitHub-hosted runners come with Chrome preinstalled. Needs the
  `LIGHTHOUSE_SITE_URL` repository *variable* (not secret - it's just the
  public site URL) set below.

### GitHub Actions secrets (Settings → Secrets and variables → Actions)

Required for deploy: `DB_PASS`, `CONSUMER_KEY`, `CONSUMER_SECRET`,
`GAPI_KEY`, `GKGSAPI_KEY`, `AWS_ACCESS_KEY`, `AWS_SECRET_KEY`,
`RECAPTCHA_SECRET_KEY`, `SMTP_USER`, `SMTP_PASS`, `FTP_HOST`,
`FTP_USERNAME`, `FTP_PASSWORD`.

Optional: `SENTRY_DSN`, `SENTRY_ENVIRONMENT` - error monitoring is simply
not initialized if these are left unset.

### GitHub Actions variables (Settings → Secrets and variables → Actions → Variables tab)

`LIGHTHOUSE_SITE_URL` - the site's real public URL (e.g.
`https://beta.seatoutlet.com`), used only by `lighthouse.yml` to know what
to run Lighthouse against. Not a secret (it's the site's own public
address), which is why it's a repository *variable* rather than a secret.

## Admin panel

`/admin` is a small custom PHP admin (session auth, CSRF-protected forms,
parameterized queries throughout - see `admin/includes/auth.php`), styled
with [Tabler](https://tabler.io) (MIT-licensed, Bootstrap-5-based) for the
post-login dashboard shell. Self-registration at `/admin/register` is a
one-time bootstrap step: it only works when zero admin accounts exist yet
(`admin_registration_is_open()` in `admin/includes/auth.php`). Adding a
second admin after that needs a manual database insert until an invite flow
exists.

## Optional performance layer: APCu

Several hot paths (`page_rules` lookups, TicketNetwork's OAuth token,
performer lookups, S3/R2 image-existence checks) are cached via
[APCu](https://www.php.net/manual/en/book.apcu.php) when it's installed,
and fall back to the exact uncached behavior when it isn't - nothing to
configure, nothing that breaks if your environment doesn't have it. If your
host supports installing PHP extensions, enabling APCu is worth it: it
roughly halves the external API round-trips most page loads make.
