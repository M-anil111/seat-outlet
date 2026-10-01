# Server rewrite rules (nginx / CloudPanel)

Pretty URLs are rewritten by the web server, not by the app (no `.htaccess` ships in this repo). Checked on beta on 2026-09-30 after the web root moved to the new `seat-outlet` directory:

| Works (200) | Returns 404 "File not found." |
|---|---|
| `/city/`, `/venue/`, `/category/`, `/artist/`, `/event/` | `/event-city`, `/concerts-city`, `/sports-city`, `/theater-city`, `/festivals-city` and every other `*-city`, `*-state`, `*-country`, `*-venue` page, `/artist-city`, `/artist-state`, `/artist-country`, `/artist-venue`, `/state/`, `/country/` |

The site's own sitemap and the `/cities` page link to the `*-city` URLs, so 150 sitemap URLs currently 404 on beta. The same pages work when called as `/event-city.php?slug=...`, so only the rewrite rules are missing. Whatever rules the old site config had for `/city/` and friends need the same treatment for these prefixes.

## Rules to add (CloudPanel: site, Vhost editor, inside the `server { }` block, before the `location ~ \.php$` block)

```nginx
# One slug: /event-city/las-vegas-nv-2355 -> /event-city.php?slug=las-vegas-nv-2355
rewrite ^/(event-city|concerts-city|concerts-state|concert-country|concert-venue|events-state|festivals-city|festivals-country|festivals-state|festivals-venue|sports-city|sports-state|theater-city|theater-country|theater-state|theater-venue|theatre-city|theatre-country|theatre-state|theatre-venue|state|country)/([^/]+)/?$ /$1.php?slug=$2 last;

# Two parts: /artist-city/taylor-swift-1234/austin-tx-247 -> slug (performer) + loc (location)
rewrite ^/(artist-city|artist-state|artist-country|artist-venue)/([^/]+)/([^/]+)/?$ /$1.php?slug=$2&loc=$3 last;
```

nginx keeps the visitor's own `?when=...&sort=...` and appends it after the rewritten arguments, so filters keep working. The app also recovers the original query string from `REQUEST_URI` (`functions.php`), so a config that drops it is handled too.

Test after reloading nginx: `/event-city/las-vegas-nv-2355`, `/concerts-city/new-york-ny-3027`, `/state/nevada-34` should return 200, and the sitemap URLs should stop returning 404. Not tested against your nginx: apply on a copy first or keep the old config file to roll back.

## Keep repo files out of the web root

The whole repository was copied into the web root, so `docs/`, `deploy/`, `composer.lock`, `.env.example` and `CONTRIBUTING.md` are downloadable. None contain secrets, but they are not meant to be public. Preferred fix: in nginx, add

```nginx
location ~ ^/(docs|deploy|tools|db/migrations|db/seeds|\.github)/ { return 404; }
location ~ \.(md|lock|example)$ { return 404; }
```

Maintenance scripts (`cron/*`, `db/migrate.php`, `tools/*`) now refuse web requests in the code itself (`inc/cli-guard.php`): run them with `php`, not by URL.

## robots.txt and sitemap

`robots.php` generates robots.txt for the host it is served on (the static `robots.txt` hard-codes the beta sitemap URL, and on a live domain would point search engines at beta). To use it, serve `/robots.txt` from it:

```nginx
location = /robots.txt { rewrite ^ /robots.php last; }
```

Beta/staging/dev hosts then return `Disallow: /`; the production host returns the normal rules plus `Sitemap: https://<host>/sitemap.php`. `sitemap.php` now lists top performers, venues and categories as well as events, cities and static pages (about 3,500 URLs); submit it in Google Search Console and Bing Webmaster Tools after launch.

## Caching and compression (server settings the code cannot set)

- Versioned, never-changing files can be cached for a year: `/lib/`, `/fonts/` (the file name or path changes when the content does), and `*.min.css` / `*.min.js` (they carry `?v=` stamps).

  ```nginx
  location ~ ^/(lib|fonts)/ { add_header Cache-Control "public, max-age=31536000, immutable"; }
  ```
- Turn on gzip or brotli for `text/html`, `text/css`, `application/javascript`, `application/json` and `image/svg+xml`. Local lab runs have no compression; with it the CSS, JS and HTML shrink by 70% or more. Cloudflare does this automatically when it proxies the site.

## Caching the HTML at Cloudflare (the biggest remaining speed gain)

Public pages are identical for every visitor: the PHP never reads cookies or sessions, and the saved location, recently viewed and recent searches are applied by JavaScript in the browser (checked: pages render byte-for-byte the same on repeat requests). So Cloudflare can serve the HTML itself and skip PHP, which removes the server wait (TTFB) from most page views.

The code already sends the right headers (`sendPageCacheHeaders()` in functions.php):

| Pages | Header |
|---|---|
| All public pages, 200 | `public, max-age=0, s-maxage=120, stale-while-revalidate=600, stale-if-error=3600` |
| 404 pages | `public, max-age=0, s-maxage=60` |
| `/checkout`, `/order-confirmation`, `/thank-you`, `/admin`, anything that is not GET | `private, no-store` |

Cloudflare ignores these for HTML unless a cache rule says otherwise. Setup (Cloudflare dashboard, Caching, Cache Rules), one rule:

- If hostname equals the site and URI path does **not** start with `/admin`, `/ajax`, `/cron`, `/checkout`, `/order-confirmation`, `/thank-you`:
- Cache eligibility: **Eligible for cache**; Edge TTL: **Use cache-control header if present**; Browser TTL: **Respect origin**.

Notes: shown prices and inventory can be up to 2 minutes old (the hosted checkout always re-prices); purge the cache after each deploy (Caching, Purge Everything) so new code shows at once; `HTML_EDGE_CACHE_SECONDS` in `inc/env.local.php` changes the 120 seconds (0 turns the headers off). Test with `curl -sI https://<host>/concerts` and look for `cf-cache-status: HIT` on the second request.
