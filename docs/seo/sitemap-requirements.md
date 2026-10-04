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
| Sitemap at the site root, submitted via Search Console or robots.txt | See open item | Index is at `/sitemaps/sitemap-index.xml` and `/sitemap.php`; submit it in Search Console |

## Readable in a browser

`sitemap.xsl` (served as `text/xsl` by `/sitemap.php?f=style`) styles every sitemap file for people: header, counts, newest and oldest change, type labels, UTC timestamps, a filter box, mobile and dark mode. Search engines ignore it and read the XML.

Why `/sitemaps/events-9.xml` looked broken: it is a static file the builder wrote before the stylesheet existed, so it had no `<?xml-stylesheet?>` line. This release:

1. Adds the line to every existing file the first time the builder runs (`soSitemapRestyleExisting()`), so files are readable at once.
2. Bumps `SO_SITEMAP_FORMAT` to 2, which makes the next background run rebuild every file with full timestamps. Until that rebuild finishes the old files keep serving.

## Open item (not fixable in code)

While live is served through the Cloudflare Worker, `https://seatoutlet.com/robots.txt` says `Sitemap: https://seatoutlet.com/sitemap.xml`, and the Worker answers `/sitemap.xml` with its own list of about 20 URLs, not the real index. Until the Worker is removed (see docs/production-cutover.md) or its robots.txt and `/sitemap.xml` are changed to point at `/sitemaps/sitemap-index.xml`, submit `https://seatoutlet.com/sitemaps/sitemap-index.xml` directly in Search Console.
