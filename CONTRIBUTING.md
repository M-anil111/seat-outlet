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
`RECAPTCHA_SECRET_KEY`, `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`.

Optional: `SENTRY_DSN`, `SENTRY_ENVIRONMENT` - error monitoring is simply
not initialized if these are left unset.

Optional: `SMTP_USER`, `SMTP_PASS` (Brevo SMTP) - without them no email is
sent: newsletter signups are still saved to `newsletter_leads` but neither
the team notification nor the subscriber confirmation goes out, and the
admin "forgot password" email is skipped (logged to the PHP error log).

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

## TicketNetwork calls, caching and profiling

Every catalog call goes through `tnRequest()` in `functions.php`. GET
responses are cached as `cache/tn_*.json` (gitignored) for a TTL chosen per
endpoint: reference data (performers, venues, cities, categories,
postal codes) 6 hours, event lists 10 minutes, `events/search` and per-user
geo queries 5 minutes, `suggest` 1 hour. Pass `$ttl = 0` to bypass. The OAuth
token is cached in the system temp dir for its real lifetime (the host has
no APCu, so the APCu-only caches never worked in production).

**Resilience built into `tnRequest()`** (verified by simulating each case):

- *Stale-while-revalidate.* An entry past its TTL but inside a grace window
  (3 × TTL, max 24 h) is served immediately and refreshed after the response
  (`fastcgi_finish_request` on PHP-FPM), behind a non-blocking lock, so the
  visitor after an expiry does not pay API latency and a crowd cannot stampede
  the API.
- *Circuit breaker.* After a live failure (network error, invalid JSON, HTTP
  5xx) live calls are skipped for 20 seconds and stale data (up to 24 h) or an
  empty result is served, instead of every request waiting out a 20-second
  timeout. Scoped per `BASE_URL`.
- *Stale on error.* A failed live call falls back to any cached copy younger
  than 24 h.
- *Parallel prefetch.* `tnRequestMulti()` fetches independent requests with
  `curl_multi` and fills the cache; pages call it once at the top and the
  normal calls below hit the cache (performer, artist-by-location, sitemap,
  warm cron). Autocomplete uses `getTnPerformersByIds()` (`filter=id in (...)`),
  one call instead of one per suggestion.
- *Token rotation.* TicketNetwork invalidates an app's previous token a few
  seconds after issuing a new one (verified). Every process that talks to the
  API must therefore share one token store, and refreshes are serialized with
  a lock. A 401 triggers one refresh-and-retry. If web (PHP-FPM) and cron (CLI)
  use different temp directories, or there is more than one server, set
  `TN_TOKEN_DIR` to a directory they all share, otherwise they will keep
  invalidating each other's tokens (Sentry logs "token was rejected (401) and
  replaced" when that happens).

Set the `TN_PROFILE` environment variable to a writable file path and every
HTTP request appends one JSON line: URI, number of catalog calls, cache
hits, total milliseconds and the list of live calls. That is how the
per-page numbers in the PR were produced.

Crons (all safe to run concurrently with traffic):

| Script | Schedule | Purpose |
|---|---|---|
| `cron/home-events.php`, `home-top-performers.php`, `home-venues.php`, `home-categories.php` | hourly | homepage feeds |
| `cron/warm-listings.php` | every 5 min | keeps /tickets, /concerts, /sports, /theater, /festival, top category and top city feeds warm so no visitor waits on the API |
| `cron/resolve-images.php` | every 10–15 min | entity image queue |
| `cron/prune-vitals.php` | weekly | deletes real-user speed measurements older than 90 days |

Card images on the homepage and search suggestions load through one
batched `POST /ajax/get-images.php` per slider (was one GET per card,
sequentially).

## Purchase flow: seat map, checkout, confirmation

- **Seat map** is the Seatics MapWidget3 on `event.php`, loaded with
  `websiteConfigId=WEBSITE_CONFIG_ID`. Catalog event IDs and the website
  config must come from the same TicketNetwork environment: sandbox IDs
  with the sandbox config (12498), live IDs with the live config (27773).
  Mixing them makes the widget report every event as expired, which is what
  beta showed. Hooks added in `event.php`: GA4 `view_item` /
  `select_item` / `begin_checkout` on the dataLayer, and our own
  "no tickets / event has passed" block via `noTicketsHandler` /
  `noEventHandler`.
- **Checkout** is TicketNetwork's hosted white-label checkout at
  `TN_CHECKOUT_URL` (the widget deep-links to it with `?tgid=&qty=&prc=`;
  the host needs a DNS CNAME set up with TicketNetwork). `checkout.php`
  (`/checkout?eid=&tgid=&qty=&prc=`) is the branded order-review step that
  hands off to it. A self-hosted checkout (ticket hold, payment, order
  creation) needs the Mercury API, which returns 403 "API Subscription
  validation failed" on the current subscription.
- **Confirmation**: `/order-confirmation?oid=&total=&email=&eid=` is the
  purchase-complete page; ask TicketNetwork to set it as the post-purchase
  redirect for the website config. It fires the GA4 `purchase` event once
  per order number.
- `GTM_ID` env var injects Google Tag Manager; without it the dataLayer
  events still fire so a container can be attached later.

## Smart search and personalization

- **Typo tolerance.** TicketNetwork's search and suggest are prefix/exact
  matchers (verified: "adelle" and "carot top" return nothing). `cron/build-search-vocab.php`
  (daily) writes `cache/search_vocab.json`: ~1,800 top performers by sales rank plus top
  venues and cities. `inc/smart.php` matches a mistyped query against it
  (transposition-aware edit distance, single words also matched against name
  words, ranked by popularity; ~20 ms, only run on zero-result searches).
  Search results auto-correct a confident single-edit performer typo ("Showing
  results for Adele") and otherwise offer "Did you mean"; autocomplete shows
  "Did you mean" instead of "No results". Empty vocabulary = feature off, never an error.
- **Zero-result recovery.** A search with no results shows close names and
  "Popular right now" events (from the cached homepage feeds) instead of a dead end.
- **Listing filters.** `/tickets`, `/concerts`, `/sports`, `/theater`, `/festival` and the
  city, venue, state, country and category pages (via `locationListingParams()`; venues default to
  soonest, everything else to popular) take
  `?when=today|weekend|week|month` and `?sort=popular|soonest|price` (plain links,
  no JS). Filtered variants are `noindex, follow`. Price sort uses the
  verified `pricingInfo/lowPrice/value` sort key. "More Events" carries the same
  filter and sort; the endpoint pins `sort` and `salesRankOptions` to known values.
- **Personalization (client-side only).** Recently viewed performers and recent
  searches live in the visitor's own `localStorage` (`soLocal` in `main.js`):
  homepage "Pick up where you left off", and recent searches + trending
  performers in the search box before typing. Nothing is sent to the server.
  If you add a consent banner, list this under functional storage.
- **Price signals** (factual, from the list response): "Cheapest date" badge on
  the performer's cheapest priced date (2+ priced dates), "Only N listed" when
  TicketNetwork lists 20 or fewer tickets for an event.
- **API quirk:** on `/catalog/v2/performers`, `eventFilter` combined with a
  sales-rank sort returns HTTP 500 after 30 s from `perPage=50` upward; use
  `filter=_metadata/hasTickets eq true` for large pages.

## Geo-IP (MaxMind GeoLite2)

`inc/geoip.php` resolves a visitor's city/state/coordinates from the
GeoLite2-City database with the vendored `maxmind-db/reader` (no network
call per lookup). The database file is not in the repo: create a free
MaxMind account, generate a license key, set `MAXMIND_ACCOUNT_ID` and
`MAXMIND_LICENSE_KEY` in the environment, and run `php cron/geoip-update.php`
weekly (MaxMind publishes updates twice a week). The file is written to
`GEOIP_DB_PATH` (default: a `geoip/` directory beside the web root, never
served). The GeoLite2 EULA requires the attribution line in `footer.php` and
forbids redistributing the file. Until the file exists, lookups return null
and the site does not pre-select a location.

## Entity images (performers, teams, venues, festivals, cities)

`inc/images.php` resolves images through one source chain per entity type
and records where each image came from:

| Type | Sources, in order | Notes |
|---|---|---|
| artist | Wikidata → Commons | People and shows. Only CC0 / CC BY / CC BY-SA / public domain files are accepted; the photographer credit is stored and rendered under the image. |
| team | TheSportsDB → Wikidata | Photo assets (fanart, banner, stadium) before the badge. `THESPORTSDB_KEY` env var; the public free key `3` is used when unset (30 req/min). |
| venue | Wikidata → TheSportsDB venues | Venue search on TheSportsDB is a paid-tier endpoint and returns nothing on the free key. |
| festival | Wikidata → Openverse | Openverse is filtered to `cc0,by,by-sa` and its attribution string is stored. |
| city | Wikidata → Pexels | Pexels only when `PEXELS_API_KEY` is set. |

Lookups never run inside a card/AJAX request: `ajax/get-image.php` serves
what is stored (or the category fallback) and queues the entity. Single
entity pages (`performer.php`, `venue.php`) resolve synchronously with
6-second timeouts so the first visitor gets a real image.

Run `php cron/resolve-images.php` every 10–15 minutes. It pre-warms
everything in the homepage caches and works the queue in a bounded batch
with a pause between entities (Wikimedia allows 200 req/min with a
User-Agent that carries contact info; the agent string is
`SeatOutletBot/1.0 (+HOME_URL/contact)`). Misses are retried after 7 days,
rate limits and storage errors after 90 minutes; nothing is cached forever.

Admin → Images lists every row with its status, source, license and credit,
lets you upload or paste a replacement (`manual`, never auto-replaced), or
send a row back to the queue. Migration `0008_images_provenance.sql` adds
the provenance columns.

Google Knowledge Graph is no longer used for images and `GKGSAPI_KEY` is
optional.

## Front-end assets, SEO and page speed

- **Minified assets.** Edit `css/style.css`, `css/skeleton.css` and `js/*.js` as usual, then run
  `tools/build-assets.sh` and commit the generated `css/style.min.css` and `js/*.min.js`. The pages load
  the `.min` files through `soAsset()` (functions.php), falling back to the source if a `.min` is missing.
  CI runs `tools/build-assets.sh --check` and fails when a minified file is stale. Needs Node (`npx`).
- **One H1 per page: the keyword strip.** The strip above the header is the page's only `<h1>` and shows its focus keyword
  (admin > Page rules > Focus keyword; blog posts and entity pages set `$pageFocusKeyword`; entity pages fall back to
  "<name> Tickets" from the meta title; everything else shows "Buy Concert Tickets"). The same keyword closes the footer.
  Page templates keep writing `<h1>`: `soSingleH1()` (functions.php, started in header.php) turns any other `<h1>` into
  `<h2 class="h1 ...">` on output, and `css/style.css` styles `.h1` like `h1`. A new CSS rule that styles a page title with the
  tag selector `h1` also needs `h2.h1` next to it. `KEYWORD_H1=0` in `inc/env.local.php` turns the whole thing off.
- **Old URL shapes.** `soRedirectLegacyUrl()` (header.php) sends `/name.php` to `/name` and `/event.php?id=N` or `/event?id=N`
  to `/event/<slug>-N` with a 301, and answers 404 for an id that does not exist.
- **Bootstrap CSS.** Pages load `css/bootstrap.min.css`, which `tools/build-assets.sh` builds from `lib/bootstrap/5.3.8/bootstrap.min.css`
  keeping only the classes found in the PHP and `js/*.js` (rules in `tools/purgecss.config.cjs`). Using a new Bootstrap class in a PHP
  file or script? Just rebuild and commit the result (CI fails when it is stale). A class that is only assembled at runtime
  (`'btn-' + name`) cannot be found: write it out in full somewhere, or add it to the safelist in that config. Classes typed into
  blog posts or editable page blocks (stored in the database) also need to be in the safelist.
- **Sentry check.** `php tools/sentry-test.php` on the server sends one test message and says whether delivery worked.
- **Icons.** Only the Bootstrap Icons the code uses are shipped (`fonts/bootstrap-icons-subset.woff2` + `css/icons.css`, folded into
  `style.min.css`). Using a new `bi-*` icon? Run `python3 tools/build-icons.py` (needs `pip install fonttools brotli`) then
  `tools/build-assets.sh`. CI runs `python3 tools/build-icons.py --check`. The admin panel still uses the full CDN font.
- **Fonts.** Inter is self-hosted (`fonts/inter-latin*.woff2`, declared in `css/fonts.css`, folded into `style.min.css`) so the first
  text paint does not wait on Google. It is deliberately not preloaded (the preload competed with the render-blocking CSS and delayed
  first paint). `css/style.css` defines an `Inter Fallback` font (Arial scaled to Inter's metrics) and reserves icon boxes (`.bi`), which keeps
  layout shift near zero while the font arrives. Keep both when changing typography.
- **Third-party libraries** (Bootstrap, jQuery, flatpickr, slick) are served from `lib/` at pinned versions: no CDN connection on the critical
  path. Change a version in `tools/vendor-assets.sh`, run it, update the paths in header.php/footer.php, commit the files.
- **Titles and descriptions.** A page sets `$pageMetaTitle` / `$pageMetaDescription` / `$pageCanonicalUrl`
  before `include 'header.php'`; static pages without their own get theirs from `inc/page-meta.php`.
  `header.php` trims titles to about 60 characters and descriptions to about 155. Never hard-code the
  domain: use `HOME_URL`.
- **One `<h1>` per page.** Listing pages use the `.results-title h1`; keep it descriptive.
- **Structured data.** `seoOffer()` builds an Offer only from a real price; never default a missing price.
- **Which scripts load.** footer.php decides from the URL path (not the query string). New listing-style pages
  need their path pattern added to `$soHasEventList` or "More Events" will not work.
- **Not-found pages.** `renderNotFoundPage('City')` answers 404 + noindex; use `tnEntityMissing($r)` (not `empty()`) on a
  TicketNetwork get-one result, because a missing id returns `{"Message": ...}`.
- **robots.txt.** `robots.php` builds it from the host (non-production hosts get `Disallow: /`); route `/robots.txt` to it, see docs/server-rewrites.md.
- **Edge caching.** `sendPageCacheHeaders()` marks public pages cacheable by a CDN for 120 seconds and checkout, confirmation, thank-you
  and admin pages `no-store`. Public pages must stay identical for every visitor: never read `$_COOKIE`/`$_SESSION` or print per-visitor
  data in them (do that in JavaScript). Setup and Cloudflare rule: docs/server-rewrites.md.
- **Date picker.** flatpickr is not on any page load. `soLoadFlatpickr()` (main.js) loads it on the first touch of a date field; header.php
  prefetches it at idle priority. Initialise pickers inside `soLoadFlatpickr().then(...)`.
- **Real-user speed (RUM).** `js/vitals.js` measures LCP, CLS, INP (approximated), FCP and TTFB, pushes `web_vitals` events to the dataLayer
  (GA4 through GTM) and sends a 25% sample to `ajax/vitals.php`, which stores page type, metric, value, rating, device and connection type
  (no IP, cookie or URL) in `web_vitals` (migration 0009). Read it with `php tools/vitals-report.php [--days=30]`; delete rows older
  than 90 days with `php cron/prune-vitals.php` (weekly). Mention anonymous performance measurement in the privacy policy.
- **Maintenance scripts** (`cron/*`, `db/migrate.php`, `tools/*`) include `inc/cli-guard.php`: command line only.

## Optional performance layer: APCu

Several hot paths (`page_rules` lookups, TicketNetwork's OAuth token,
performer lookups, S3/R2 image-existence checks) are cached via
[APCu](https://www.php.net/manual/en/book.apcu.php) when it's installed,
and fall back to the exact uncached behavior when it isn't - nothing to
configure, nothing that breaks if your environment doesn't have it. If your
host supports installing PHP extensions, enabling APCu is worth it: it
roughly halves the external API round-trips most page loads make.
