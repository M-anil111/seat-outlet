# Reliability: TicketNetwork throttling and outages

## What was happening

Every dynamic page (artist, venue, city, state, country, category, category by location, event) is built from TicketNetwork's catalog API. The API answers HTTP 429 ("throttled") when it gets too many requests. Sentry shows it: `TicketNetwork API unavailable, circuit opened for 60s: /catalog/v2/events throttled (HTTP 429)`, 9 events in 2 hours on 2026-10-04, and earlier ones on category and NFL pages. When that happens the site opens a circuit for 60 seconds. A page whose data is not already in the cache then had nothing to show and answered "temporarily unavailable" (HTTP 503). That is what `/theater-city/greenville-sc-1767` showed. It is not caused by the id in the URL.

Cold pages are the ones that fail: long-tail pages that nobody has opened lately, and everything a crawler touches for the first time. A crawl of a few hundred new URLs is exactly the traffic that triggers the throttle.

## Which pages can be hit

All of them depend on the API when they are not cached, so the exposure is every page type below. Before this change only the first column had an error guard; the second column is what each type does now when the API fails.

| Page type | Example | Guard before | Now |
|---|---|---|---|
| Artist, venue, city, state, country | /artist/..., /city/... | 503 page | last good copy, else 503 |
| Event | /event/... | 503 | last good copy, else 503 |
| Category by location | /theater-city/... | 503 page | last good copy, else 503 |
| Genre and league pages | /mls-tickets, /nfl-tickets | none (showed "0 results") since PR #91: 503 | last good copy, else 503 |
| Hubs | /concert-tickets-for-sale, /game-day-tickets, /buy-broadway-tickets, /upcoming-music-festivals, /buy-tickets-online | none (showed "0 results") | last good copy, else 503 |
| Directories | /concert-artists, /sports-teams ... | empty list | unchanged: they read the long data cache (6 h, kept 14 days when the API fails) |

## What changed

1. **Page snapshots.** Every page that renders completely (HTTP 200, with data) is stored gzipped under `cache/pages/` (at most one rewrite per page per 10 minutes). When a page cannot be built, `renderUnavailablePage()` serves that stored copy, up to 14 days old, as a normal 200 with `X-Page-Snapshot: stale` and a 60 second CDN lifetime. Only a page that was never built before and cannot be built now still answers 503.
2. **Longer data memory.** A stale API answer now stands in for a failed call for 14 days (it was 24 hours), so a throttle window cannot empty a page that was fine yesterday.
3. **Never "0 results" for a failed feed.** The shared listing renderer and the category pages answer with the snapshot, or 503 with `Retry-After`, instead of a 200 page that says 0 results.
4. **Warmer.** `cron/warm-snapshots.php` visits sitemap URLs one per second (400 per run, every 30 minutes), missing and oldest first, and stops by itself when the API starts throttling. Run `php cron/warm-snapshots.php --status` to see how many pages are covered.
5. **The 503 page retries itself** every 15 seconds for people (crawlers ignore it).

Empty categories and empty location pages are indexable again; an empty page is a real answer, only a failed API call is not.

## Not fixable in code

- **TicketNetwork's request limit.** Ask TicketNetwork support for the per-second and daily limits on our key and whether they can be raised; the number decides how fast the warmer and crawlers can safely run. Until then the defaults are conservative.
- **Cloudflare.** HTML is cached for 120 seconds with `stale-if-error=3600`. A Cache Rule that keeps public pages (not /checkout, /admin, /ajax) at the edge for 1 hour with "serve stale while revalidating" cuts origin and API traffic much further.
- Keep Search Console crawl rate on automatic, and do not run site audits with more than a few threads.

## SE Ranking audit of 4 to 5 Oct 2026 (what it showed and what was fixed)
- 12,567 "5XX" pages, 7,827 "redirect to 4xx or 5xx", 3,495 "5XX pages in the sitemap" and 3,496 "noindex pages in the sitemap" are one problem: the audit crawled about 58,000 pages in a day, the ticket API throttled, and every uncached page answered 503 "Temporarily unavailable" (which is noindex and has no canonical, so it also shows up as noindex and non-canonical). On 5 Oct the live site still answered 503 for event, venue and city pages that had not been cached. Check Sentry and `php cron/warm-snapshots.php --status` on the server to see whether the API is still throttling.
- Run site audits with 1 or 2 threads and a delay (not the default speed), and ask TicketNetwork for the limits on our key (see above).
- Fixed in code: internal links still used the API's old name-and-id address (`/artist/arizona-cardinals-57`, `/category/other-1900`) on directory pages, related-performer cards, the home venue and nearby-venue feeds and the category breadcrumb. Each answered 301, which is the 6,456 "internal links to 3XX". They now use the clean slugs.
- Not code: old address forms in the audit that now 301 (`/event/<name>-<id>`, `/concert-venue/<name>-<id>`) will clear on the next audit; the "CSS not minified" count (6,004) was the unminified carousel CSS fixed on 5 Oct.

## Crawl guard (per-address page limit)

`inc/crawl-guard.php` counts public page requests per address per minute (files in `cache/ratelimit/`). Over `SO_RL_PER_MIN` (default 120; search engine bots get 3x; 0 turns it off) the visitor gets `429` with `Retry-After`, and no page is built and no ticket API call is made. This stops one site audit or scraper (the 4 to 5 Oct 2026 audit loaded 58,000 pages) from using up the ticket API allowance and turning pages into 503 for everyone. Data endpoints, checkout, admin, cron, sitemaps and robots.txt are not counted. Set audit tools to 1 to 2 threads with a delay, or raise the limit for a known audit address in `inc/env.local.php`. Behind Cloudflare the address comes from `CF-Connecting-IP`.

## Shared call budget (TN_CALLS_PER_SECOND)

Every live TicketNetwork call takes a token from one bucket shared by the PHP processes that use the same `cache/` directory (web requests, crons, crawlers on one host). Separate hosts have separate buckets.

- Default 1 call a second (`TN_CALLS_PER_SECOND`; 0 turns the budget off).
- The circuit breaker is kept per family: event lists, details and search (`events`), and performers, venues, cities and categories (`reference`). A throttle on one family does not stop the other. The default was 4 until production kept returning 429 (HTTP throttling) under it. On 13 Oct 2026 the health page measured ten minutes at 1 (286 calls held back by our own budget, 2 throttled by TicketNetwork) and ten minutes at 2 (37 throttled, 38 breaker openings, the same 460 live calls, artist pages back to 503). The 429 body carries code **900804**, the WSO2 gateway's *subscription-tier* throttle (900800 is the API level, 900803 the application level): the tier TicketNetwork attached to our application's Catalog API subscription lets about one call a second through: at a steady 1 a second 2 calls in ten minutes were refused, at 2 a second with bursts of 4 it was 37, with the same number of calls getting through. Only TicketNetwork can raise that tier (questions 10 and 11 in `docs/ticketnetwork-api.md`); a higher rate here only turns budget holds into 429s and open circuits. Watch `throttled` against `overBudget` on `/ajax/health.php`.
- A visitor (web request) may use the whole bucket and waits up to 1.5 seconds for a token. Over budget, the page answers from its stored copy, like an outage, and no call is made.
- A cron (CLI) may use only the upper half of the bucket, so a warmer cannot use up the calls visitors need. The snapshot warmer's own page visits (`cron/warm-snapshots.php`, which fetches site URLs over HTTP) send `X-SO-Warm: 1` with the warmer's own user agent and are treated the same way, and they are left out of the visitor counts on the health page (`tnBackgroundCaller()`; both the header and the user agent are required, so a bot cannot take its failures out of the counts by sending one header).
- The bucket holds a burst of twice the rate, at least two tokens (two at the default of 1 a second). A page that prefetches several calls at once (`tnRequestMulti()`, for example performer + events + venue) waits up to 1.5 seconds in all for tokens, like a single call does. Before 13 Oct 2026 the third call of such a batch failed on the spot, and that, not TicketNetwork's 429s, was most of the "no data" pages after the breakers were split by family (the health page showed about 70% of pages without data while the breakers tripped once).
- Concurrent requests for the same cold key share one call (single-flight). The others wait up to 3 seconds for its answer.
- A 429 with a numeric `Retry-After` sets the breaker from that value (5 to 300 seconds). Without it, the breaker stays open for 60 seconds.
- Code: `inc/tn-budget.php`, `tnBucketAllow()` and `tnFetchStore()` in `functions.php`. Test: `tools/test-tn-budget.php`.
- Test result (local stub, CLI): 20 simultaneous requests for one cold event made 1 call. 40 requests for 40 cold events made 7 calls at the default rate. Web-request visitor waiting has not been tested.

## Event data backup (tn_event_snapshot)

Every complete event the site loads successfully, and every complete event row a listing returns, is also stored in the database table `tn_event_snapshot` (migration 0048). The event page uses the last stored copy when the live TicketNetwork call fails: first the file copy from a listing, then the database snapshot. Both are used only if seen within 24 hours (`TN_LISTING_COPY_TTL`). Rows are written at most once an hour per event; the snapshot is pruned after 180 days unseen (cleanup only, not a display rule).

## Removed events and stale answers

TicketNetwork is the only source of what is on sale. The site may show an older answer only while the live call cannot run, and only for a short time:

| What | Stale answer allowed when the live call fails | Where it is set |
|---|---|---|
| Live event lists (artist, city, venue, category, search, load-more) and event details | 2 hours | `TN_STALE_LISTING`, `tnStaleLimit()` in `functions.php` |
| Stored event copies and snapshots (event page fallback) | 24 hours | `TN_LISTING_COPY_TTL` in `inc/tn-listing-copy.php` |
| Reference data (performers, venues, categories) | 14 days | `TN_STALE_ON_ERROR` in `functions.php` |
| Home event lists (`home_events_*`, written by `cron/home-events.php`) | 2 days, and an empty answer does not overwrite the last list | `search.php`, `order-confirmation.php`, `cron/home-events.php` |

- A successful answer replaces the old one. An event that TicketNetwork no longer returns leaves a live list on the next successful refresh (normally within about 40 minutes, the cache TTL plus grace).
- The home lists (`home_events_*`, written by `cron/home-events.php`) are the exception. They are precomputed, so an empty refresh keeps the previous list, and every reader stops using a list older than 2 days (home pages, search, order confirmation, image warm-up, and the SEO schema file).
- A confirmed "not found" on an event detail deletes its stored copies at once (`tnForgetEventCopies()`), so no fallback can show it again.
- During a throttle or outage an event may stay visible for up to 2 hours (lists) or 24 hours (copies). That trade-off keeps pages up while the API is limited. Lower `TN_STALE_LISTING` if removals must show faster during outages.

## Empty answers, and never a "0 results" page that looks real

- An empty list answer while the stored list (inside its 2-hour window) still had rows is treated as a flapping feed (`tnEmptyListSuspect()`): the stored list stands and is tried again at the next refresh. It is counted as `empty_suspect` on the health page. A category that really empties shows that within 2 hours.
- A page whose feed failed and that has no results serves its last good page snapshot as a 200 when one exists. Without one it answers **503 with `Retry-After: 30`**, with the page still rendered and the "loading slowly" card in place of the list (`soFeedDownGate()`, also applied by `header.php` to any page that reached it degraded with no results, search included). It never answers 200 with `noindex`: a crawler would index an empty page, or drop a page that was fine an hour ago. A feed failure also overrides a page's own "0 results" noindex.
- A performer record goes into APCu only when the lookup returned a real record (live, or the 14-day stale file copy standing in for a failed call). A failed call with no usable file copy answers `[]`, and keeping that for an hour made every later visit to the artist page answer 503 from APCu without reading a file copy written since or trying the API again (`/artist/hoobastank` on 13 Oct 2026).
- `/ajax/health.php` also reports `throttled` (live calls TicketNetwork refused with 429) and `overBudget` (calls our own `TN_CALLS_PER_SECOND` budget held back), both per call. Many `overBudget` with few `throttled` means the local rate is the bottleneck; the reverse means the API's limit is below the rate we send. The 429 message in Sentry now carries the gateway's own code and text (`throttled (HTTP 429, 900804 Message throttled out)`), which says which level was hit: 900800 the API, 900803 the application, 900804 the subscription tier (the one we see).
- `/ajax/health.php` reports today's per-page `noDataRate` (pages that needed a TicketNetwork answer and got none), `emptySuspectRate` (pages that kept a stored list over an empty live answer) and an `alert` flag (either over 20% on 50+ pages). `cron/check-tn-health.php` checks those rates and three listing pages every 15 minutes and sends a Sentry alert only when a check fails.

- The nightly database dump (`cron/backup-db.php`) includes the table, so the event copies are backed up with the rest of the data. The file cache is not backed up; it is only a fast copy.
- A brand-new event that has never been listed or opened still has no copy until TicketNetwork answers.
- Restore is the usual database restore; no code step is needed. Check after a restore with `SELECT COUNT(*) FROM tn_event_snapshot`.
