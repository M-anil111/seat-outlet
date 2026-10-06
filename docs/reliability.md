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
