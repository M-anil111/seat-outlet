# Server resource use: what runs automatically and what to cut

Written 24 Nov 2026 after the hosting provider reported high resource use. This is a read of the code and the documented schedules; **the actual load (which job, which bots, how many requests) is not visible from the repository.** The host's resource report (busiest processes or scripts) or `/ajax/health.php` with the `HEALTH_TOKEN` header (`ticketnetwork.demand`: bot and direct shares, live calls) settles the ranking. Schedules below are the documented ones (`CONTRIBUTING.md`, `docs/beta-runbook.md`); the server's own crontab was not read.

## 1. The biggest structural cost: every page view is a full PHP render

The pages are identical for every visitor and the app already sends `Cache-Control: public, s-maxage=120, stale-while-revalidate` (`sendPageCacheHeaders`), but Cloudflare reports the HTML as `DYNAMIC` (not cached): the Worker (`deploy/cloudflare-worker/seatoutlet-blog-proxy.js`) returns a new response and caches only one fallback page. So every visit, and every bot visit, builds the page in PHP with its database queries. Caching anonymous GET pages at the edge for one to two minutes is the largest possible cut and needs the Cloudflare account (a Cache Rule that respects the origin's headers, or the Worker's Cache API). It must honour the origin's `no-store` on degraded, checkout and admin pages (the app already sends it). Not done here: it changes how live prices are served and needs testing on the Cloudflare side.

Public DNS for `seatoutlet.com` returned the origin server's own address (not a Cloudflare address) in the 24 Nov incident window (see `docs/production-safeguards.md`), so the origin also serves every static file and all bot traffic that a Cloudflare proxy would absorb. `docs/production-cutover.md` already plans Cloudflare Cache Rules for the live site; they are the place to cache pages and static files.

## 2. Scheduled jobs (documented schedules; cost from the code)

| Job | Schedule | Cost | Notes |
|---|---|---|---|
| `cron/warm-snapshots.php` | every 30 min | up to 400 full page builds over HTTP per run, 1 per second, time box 1,500 s (`cron/warm-snapshots.php`); up to 19,200 builds a day | the biggest scheduled consumer. Now stops early when the server is busy |
| `cron/check-tn-health.php` | every 15 min | 3 page builds per run, 288 a day | the alarm; left alone |
| `cron/warm-listings.php` | every 5 min | category feeds, cities, performers, ticket API calls on a 600 s cache | yields when busy |
| `cron/warm-event-details.php` | every 15 min | up to 40 event lookups with 0.5 s pauses (the docs say 15) | yields when busy |
| `cron/resolve-images.php` | every 10 min | batch of 60, 2 to 3 third-party requests each, GD resize and WebP | yields when busy |
| `cron/tn-bulk-import.php` | hourly | 5,000 rows a page, up to 3,000 s a run, a full refresh daily 07:00 to 10:59 UTC | **keep, do not touch** (owner's rule). The comment in the script ("nothing on the site reads the table yet") is stale: `inc/tn-bulk-read.php` and `inc/event-redirects.php` read it, and event pages and listings fall back to it |
| `cron/home-*.php` (4) | hourly | a few ticket API calls each | `home-top-performers` duplicates the web hook |
| `cron/build-sitemaps.php` | every 6 h (optional) | up to 250 pages of 200 events | the web hook also builds it |
| `cron/indexnow.php`, `send-price-alerts.php`, `send-alerts.php`, `backup-db.php`, `build-search-vocab.php`, `resolve-counties.php`, weekly and daily jobs | 30 min to weekly | small, or one per night | left alone |
| `deploy/pull-deploy.sh` | every 2 min | a `git fetch`, then exit when nothing changed | negligible |

## 3. Background work that public requests start (after the page is sent, but the PHP worker stays busy)

| Trigger | Cost | Change here |
|---|---|---|
| web image worker (`imageWorkerMaybeRun`) | up to 40 s of every minute while the queue is non-empty (a batch of 15 with downloads and resizing); duplicates `cron/resolve-images.php` | stands down for 30 minutes after the cron ran, and when the server is busy |
| web sitemap step (`soSitemapMaybeRun`) | 25 s steps while a rebuild is due (every 6 h on the live host, 24 h on beta); duplicates `cron/build-sitemaps.php` | stands down for 7 hours after the cron ran, and when the server is busy |
| top performers refresh (`soTopPerformersMaybeRun`) | one ticket API batch every 6 h at most | yields when busy |
| stale-while-revalidate refresh (`tnScheduleRefresh`) | one live ticket API call per stale key, no cap per request (per-key lock and the shared call budget only) | **not changed**: its effect is not measured yet; a cap of a few keys per request is the candidate |
| biography lookup (`soBioQueueFetch`) | one Wikipedia call, at most once per performer per 6 h, 4 s limit | already bounded |
| page snapshot store (`soSnapshotStore`) | a gzip and a file write per page, at most one per page per 10 minutes | candidate if disk I/O is the problem |

## 4. What this change does (no change to what visitors see)

- `inc/server-load.php`: `soServerBusy()` is true when the one-minute load average is above the limit (`SO_BG_MAX_LOAD`, or one per CPU core). The page warmers (`warm-snapshots`, `warm-listings`, `warm-event-details`), the image cron, and the web-triggered image worker, sitemap step and top performers refresh skip their turn when it is true; `warm-snapshots` also stops in the middle of a run. They run again at their next turn and the data they refresh stays valid for hours. With a host that hides the load figure nothing changes.
- The image and sitemap crons leave a heartbeat file in `cache/`; the matching web workers stand down while it is fresh, so the same queue is not worked twice. With no cron scheduled there is no heartbeat and the web worker works as before.
- Not touched: the ticket-catalog import, the call budget, any page output.

## 5. Decisions for the owner or the host (server or Cloudflare access needed)

1. Edge caching of anonymous page views (section 1): the largest cut.
2. Crontab: `warm-snapshots` every 60 minutes instead of 30 (or `--limit=200`) halves the heaviest scheduled job; remove the `home-top-performers` cron (the web hook does the same); check that `warm-event-details` is not run with a large argument.
3. Set `SO_BG_MAX_LOAD` in `inc/env.local.php` if the host's limit is lower than one load per core (`-1` pauses the page warmers, the image and sitemap workers and the top performers refresh).
4. Ask the host for the busiest-scripts report, and `ticketnetwork.demand` from the health detail view (bot share): if crawlers are most of the load, serve known crawlers from stored snapshots (the existing design note in `docs/reliability.md`).

## 6. Explaining a slow period afterwards (added 25 Nov 2026)

Measured locally with a warm OPcache and nothing else running, a plain page costs about 6 ms in total (`boot` under 10 ms, 7 to 11 queries). That does not show what a live page spends: the 7 to 11 bootstrap queries are part of `boot` and slow down with a busy database, and the time may also be in the disk, the ticket API budget, or PHP workers that a busy server cannot schedule. A live page that takes 2 to 50 seconds with only 20 to 80 queries is waiting on something, and this is a hypothesis to check, not a finding. To see what, these are recorded:

- `Server-Timing` has a `dbconn` entry: how long opening the database connection took. A busy or connection-limited MariaDB makes this one large. (`dbq` still counts queries; it does not time them.)
- A page that takes `SO_SLOW_LOG_MS` or more (default 5000; `0` turns it off) leaves one line in the PHP error log: `slow request: <path> status <code> took <ms> ms [load <1-minute load>] [<the Server-Timing breakdown>]` (the `load` part is left out on a host that does not report a load average). The breakdown has no `dbq` (counting queries is one more query to a database that may be the slow part); that count stays in the `Server-Timing` header. Only the path is kept (no query string, address, cookie or user agent). `grep 'slow request' <php error log>` after a slow period shows which pages, how slow, and whether `dbconn`, `budget`, `tn`, `cache` or none of them explain it (a large `boot` with all of them small means the time went to something not yet timed, possibly queries on a busy database). The hook is registered before the database connection is opened, so a slow connection that fails (the 503 page below) is logged too.
- A fast 503 on every page, including `/ajax/health.php`, is the database-down page (`soDbUnavailable()` in `db/config.php`: "We will be right back", `Retry-After: 30`). It writes `Database unavailable: <reason>` to the error log; `grep 'Database unavailable'` gives the reason (for example "Too many connections"). On the server, `SHOW GLOBAL STATUS LIKE 'Max_used_connections'` against `SHOW VARIABLES LIKE 'max_connections'` shows whether the connection limit was reached; each install and its cron jobs use the database named by `DB_NAME` in that install's `inc/env.local.php`, so inspect the instance that install uses.
