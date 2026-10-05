# XML sitemaps: Google requirements and how we meet them

Source: Google Search Central, "Build and submit a sitemap" (developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap, last updated 2026-07-08).

| Google requirement | Status | How |
|---|---|---|
| UTF-8 encoding | Met | Every file starts with `<?xml version="1.0" encoding="UTF-8"?>` |
| 50,000 URLs and 50 MB (uncompressed) per file | Met | 5,000 URLs per file (`SO_SITEMAP_CHUNK`), about 0.6 MB each; a sitemap index lists the files |
| Fully qualified, absolute URLs | Met | Every `<loc>` is `https://seatoutlet.com/...` (host from `HOME_URL`) |
| Canonical URLs only, nothing you do not want in results | Met | Events with tickets on sale in the US, zero-result pages are left out (`soZeroPages()`), artist, venue and city pages come from the same events |
| Entity-escaped values | Met | `soSitemapEsc()` (`&` becomes `&amp;`) |
| `<lastmod>` only if consistently and verifiably accurate | Met, with a note | Value is TicketNetwork's own update time for the event (`metadataInclusiveUpdatedAt`, else `updatedAt`); an artist, venue or city gets the newest of its events. Pages with no real change time (static pages) have no `<lastmod>` at all instead of a guessed one |
| `<lastmod>` in W3C datetime | Done in this release | Full timestamp with the time of day and a UTC offset, for example `2026-10-04T17:49:46+00:00` (was date only). A stamp with no zone is read as UTC |
| `<priority>` and `<changefreq>` | Not used | Google ignores both |
| Sitemap at the site root, submitted via Search Console or robots.txt | See open item | Index is at `/sitemaps/sitemap.xml`; submit it in Search Console |

## Readable in a browser

`sitemap.xsl` (served as `text/xsl` from `/sitemap.xsl`, a static file) styles every sitemap file for people: header, counts, newest and oldest change, type labels, UTC timestamps, a filter box, mobile and dark mode. Search engines ignore it and read the XML.

Why `/sitemaps/events-9.xml` looked broken: it is a static file the builder wrote before the stylesheet existed, so it had no `<?xml-stylesheet?>` line. This release:

1. Adds the line to every existing file the first time the builder runs (`soSitemapRestyleExisting()`), so files are readable at once.
2. Bumps `SO_SITEMAP_FORMAT` to 2, which makes the next background run rebuild every file with full timestamps. Until that rebuild finishes the old files keep serving.

## Open item (not fixable in code)

While live is served through the Cloudflare Worker, `https://seatoutlet.com/robots.txt` says `Sitemap: https://seatoutlet.com/sitemaps/sitemap.xml`, and the Worker answers `/sitemap.xml` with its own list of about 20 URLs, not the real index. Until the Worker is removed (see docs/production-cutover.md) or its robots.txt and `/sitemap.xml` are changed to point at `/sitemaps/sitemap.xml`, submit `https://seatoutlet.com/sitemaps/sitemap.xml` directly in Search Console.

City pages (United States and Canada)
- Every city page works for any city the ticket API knows (`/event-city/`, `/concerts-city/`, `/festivals-city/`, `/sports-city/`, `/theater-city/` plus the slug). A page with no upcoming events is `noindex` and kept out of the sitemap.
- The sitemap crawl covers United States and Canada events. For each event it records the city and which pages the event belongs to (all events, concerts, festivals, sports, theater; festivals also count as concerts, as on the pages themselves). A city's page of a kind is listed in `cities-N.xml` only when the city has at least `SO_SITEMAP_CITYPAGE_MIN` (3) upcoming events of that kind.
- `/city-events` lists up to 150 top cities (United States and Canada, grouped by state or province), each with links to all five city pages.

Dynamic pages (5 Oct 2026)
- The list of the site's own pages is no longer typed by hand. `soSitemapStaticPaths()` reads the web root: a script is a page when it prints the page frame (or is a small genre or list stub), is not a template behind a slug, not a redirect, not `noindex` and not under a robots.txt Disallow. A new page file shows up in `pages-1.xml` with the next rebuild; `tools/check-sitemap-pages.php` (CI) keeps the scan honest. Events, performers, venues, cities (all five city page kinds and the four discovery kinds), blog posts and categories were already read from the catalog and the database.
- `/sitemap` (the page for people) reads the same sources: the menu sections, the blog index with its categories (not each post), and every other page the scan finds. A new page or blog category appears without editing the page.
- There is no `.php` sitemap address. `sitemap-style.php` is gone (the stylesheet is the static `/sitemap.xsl`), `/sitemap-page` and `/sitemap.php` send visitors to `/sitemap`, and `deploy/retired-files.txt` makes the pull deploy delete retired files from the server.

Holiday pages (5 Oct 2026)
- `/<holiday>-in-<city>` for 17 US and Canadian holidays (`inc/holidays.php`: Christmas, New Year's Eve, Valentine's Day, St. Patrick's Day, Easter weekend, Mother's Day weekend, Memorial Day weekend, Victoria Day weekend, Father's Day weekend, Canada Day, July 4th, Labor Day and Labour Day weekend, Halloween, Thanksgiving weekend, Canadian Thanksgiving weekend, Boxing Day), for example `/july-4th-events-in-austin-tx` and `/christmas-shows-near-me-in-austin-tx`. A holiday only has pages for the countries it applies to (July 4th: US cities; Canada Day: Canadian cities). The dates are computed each year (`tools/test-holidays.php` checks 2026 to 2028).
- The hub `/holiday-events` lists every holiday with its next dates and the biggest cities. The sitemap crawl adds a holiday page to its own `holiday-events-N.xml` (child of `/sitemaps/sitemap.xml`) for a city with at least `SO_HOLIDAY_MIN` (3) events in the holiday's dates; the page is noindex under that.
- `php tools/list-holiday-urls.php [--urls]` prints the real count per holiday and every URL from the built `holiday-events-N.xml` (run after `php cron/build-sitemaps.php --force`).
