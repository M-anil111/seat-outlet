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

# City holiday pages: /july-4th-events-in-austin-tx -> /holiday-city.php?holiday=july-4th-events&slug=austin-tx
rewrite ^/(christmas-shows-near-me|new-years-eve-events|valentines-day-events|st-patricks-day-events|easter-weekend-events|mothers-day-weekend-events|memorial-day-weekend-events|victoria-day-weekend-events|fathers-day-weekend-events|canada-day-events|july-4th-events|labor-day-weekend-events|labour-day-weekend-events|halloween-events|thanksgiving-weekend-events|canadian-thanksgiving-weekend-events|boxing-day-events)-in-([^/]+)/?$ /holiday-city.php?holiday=$1&slug=$2 last;

# City discovery pages: /last-minute-tickets/austin-tx -> /last-minute-tickets.php?slug=austin-tx
rewrite ^/(last-minute-tickets|weekend-events|cheap-tickets|best-events)/([^/]+)/?$ /$1.php?slug=$2 last;

# Two parts: /artist-city/taylor-swift-1234/austin-tx-247 -> slug (performer) + loc (location)
rewrite ^/(artist-city|artist-state|artist-country|artist-venue)/([^/]+)/([^/]+)/?$ /$1.php?slug=$2&loc=$3 last;
```

nginx keeps the visitor's own `?when=...&sort=...` and appends it after the rewritten arguments, so filters keep working. The app also recovers the original query string from `REQUEST_URI` (`functions.php`), so a config that drops it is handled too.

Test after reloading nginx: `/event-city/las-vegas-nv-2355`, `/concerts-city/new-york-ny-3027`, `/state/nevada-34` should return 200, and the sitemap URLs should stop returning 404. Not tested against your nginx: apply on a copy first or keep the old config file to roll back.

## Keep repo files out of the web root

The deploy used to copy the whole repository into the web root, so `docs/`, `deploy/`, `composer.json`, `composer.lock`, `.github/`, `db/migrations/*.sql`, `vendor/composer/installed.json`, `cache/*.json` and `CONTRIBUTING.md` were downloadable (they show exact dependency versions, the schema and CI details; none holds a password). `deploy/pull-deploy.sh` now stops copying `.github`, `docs`, `CONTRIBUTING.md`, `composer.json/lock` and `.env.example`, but copies already on the server stay until removed once by hand. Block everything below in nginx either way (add inside the `server { }` block, before the `location ~ \.php$` block):

```nginx
# Folders that are never meant to be requested by a browser.
location ~ ^/(docs|deploy|tools|db|cron|cache|vendor|phpmailer|\.git|\.github|inc)(/|$) { return 404; }
# Dependency and config files at the top level, and anything that looks like a secrets or backup file.
location ~* ^/(composer\.(json|lock)|\.env.*|CONTRIBUTING\.md|README.*)$ { return 404; }
location ~* \.(md|lock|example|sql|bak|old|orig|swp|log)$ { return 404; }
```

Notes: `inc/` holds include-only PHP files that already answer 404 when requested directly, and `inc/env.local.php` holds every secret; if PHP-FPM stops or a vhost edit makes nginx serve `.php` as text, that file would be readable, so also move it one level above the web root (`db/config.php` already looks there, as `../../inc/env.local.php`) or keep the `inc` block above. `ajax/` and the page files must stay reachable. `cron/*`, `db/migrate.php` and `tools/*` also refuse web requests in the code itself (`inc/cli-guard.php`): run them with `php`, not by URL.

If the hosting panel allows it, set the web root to a `public/` subfolder in a later restructure: that removes this whole class of problem.

## Unsubscribe, sign-up and other clean URLs

`/unsubscribe` (file `unsubscribe.php`) is a normal clean URL like `/thank-you`: it needs the same `try_files $uri $uri.php` style rule that the other top-level pages already use. Test with `curl -sI https://<host>/unsubscribe?t=x` (expect 200 and `X-Robots-Tag`/`noindex` in the page). The sign-up endpoint is `/ajax/subscribe.php` (POST only).

## Rate limit /ajax/ (the quickest protection for the ticket API quota)

The app limits sign-ups, image requests and live feed builds per visitor itself, but a server-level limit stops floods before PHP starts. In `http { }` (CloudPanel: global nginx settings) add the zone, then use it in the site:

```nginx
limit_req_zone $binary_remote_addr zone=so_ajax:10m rate=10r/s;
```

```nginx
location ^~ /ajax/ {
    limit_req zone=so_ajax burst=30 nodelay;
    limit_req_status 429;
    try_files $uri =404;
    # then the same fastcgi/PHP handling as the site's `location ~ \.php$` block
}
```

Behind Cloudflare, `$binary_remote_addr` is Cloudflare's address unless the real client address is restored first (`set_real_ip_from` for each Cloudflare range plus `real_ip_header CF-Connecting-IP;`), otherwise everyone shares one bucket. Cloudflare can also do this without nginx: Security, WAF, Rate limiting rules, path starts with `/ajax/`, for example 120 requests per minute per IP. Not tested against your server: apply on a copy first.

## robots.txt and sitemap

`robots.php` generates robots.txt for the host it is served on (the static `robots.txt` hard-codes the beta sitemap URL, and on a live domain would point search engines at beta). To use it, serve `/robots.txt` from it:

```nginx
location = /robots.txt { rewrite ^ /robots.php last; }
```

Beta/staging/dev hosts then return `Disallow: /`; the production host returns the normal rules plus a `Sitemap:` line for the sitemap index. The sitemap is a **sitemap index** (`/sitemaps/sitemap.xml`) pointing at typed files (`pages-1`, `events-1..N`, `performers-1..N`, `venues-1..N`, `cities-1..N`, 5,000 URLs each). The site builds and refreshes them itself in the background (see `inc/sitemap-build.php`; a fresh crawl every 6 hours on the live host, 24 on beta, `<lastmod>` from TicketNetwork's own update times). It writes static files into `<web root>/sitemaps/` when that folder is writable by PHP (nothing else to configure; the web server serves them as plain files) and otherwise into `cache/sitemaps/`, served through `/sitemap.php` and `/sitemap-serve.php?f=NAME`. Optional: `php cron/build-sitemaps.php` from cron does the same crawl without waiting for page traffic (`--status` shows progress). Until the first crawl finishes, `/sitemap.php` serves the older single-file sitemap.

### One sitemap address: /sitemaps/sitemap.xml

The only XML index is `/sitemaps/sitemap.xml`, a static file the site writes itself (robots.txt and Search Console use it); the page for people is `/sitemap`. The old names are retired: `/sitemaps/sitemap-index.xml` is deleted by the next build, and `/sitemap.xml` should 301 to the new address. Do not redirect `/sitemap.php`: `/sitemap` is served by `sitemap.php` internally (the page sends direct visits to `/sitemap.php` or `/sitemap-page` to `/sitemap` itself). Add inside the `server { }` block, then reload nginx:

```nginx
location = /sitemap.xml { return 301 /sitemaps/sitemap.xml; }
location = /sitemaps/sitemap-index.xml { return 301 /sitemaps/sitemap.xml; }
location = /sitemap.xsl { default_type text/xsl; expires 1d; }   # browser stylesheet for the XML files (without it the XML shows plain; search engines are not affected)
```

If the Cloudflare Worker still answers `/sitemap.xml` itself (the old 21-URL list), remove that route from the Worker, or have it 301 to `/sitemaps/sitemap.xml`.

## Blog: one entry point, /blog

`blog.php` is the whole blog: `/blog` is the list and `/blog/<slug>` is an article. There is no separate article script and no `?slug=` address to share; `/blog-post` and `/blog-post.php?slug=x` answer 301 to `/blog/x`.

The web server needs one rule so `/blog/<slug>` reaches `blog.php` (without it, `/blog/upcoming-concert-tours` answers 404 "File not found." on beta, and the Cloudflare Worker `seatoutlet-blog-proxy` then replaces the article with its own short placeholder page, which is what seatoutlet.com showed). Add inside the `server { }` block, before the `location ~ \.php$` block, and reload nginx:

```nginx
rewrite ^/blog/([a-z0-9-]+)/?$ /blog.php?slug=$1 last;
```

Test: `curl -s -o /dev/null -w "%{http_code}\n" https://beta.seatoutlet.com/blog/upcoming-concert-tours` prints 200 and the page has an `<h1>` and many `<h2>` headings.

If the rule cannot be added yet, `https://beta.seatoutlet.com/blog.php/upcoming-concert-tours` already works (PATH_INFO), so the Worker can fetch that address for every `/blog/<slug>`. The Worker is deleted at cutover (docs/production-cutover.md), after which only the nginx rule matters.

## Caching and compression (server settings the code cannot set)

- Versioned, never-changing files can be cached for a year: `/lib/`, `/fonts/` (the file name or path changes when the content does), the minified bundles `*.min.css` / `*.min.js` (they carry `?v=` stamps) and `/images/`:

  ```nginx
  location ~ ^/(lib|fonts)/ { add_header Cache-Control "public, max-age=31536000, immutable"; }
  location ~* \.min\.(css|js)$ { add_header Cache-Control "public, max-age=31536000, immutable"; }
  location ~* ^/images/.*\.(webp|png|jpg|jpeg|svg|gif|ico)$ { add_header Cache-Control "public, max-age=2592000"; }
  ```
  (an `add_header` inside a `location` replaces the ones set outside it, so repeat any security headers you add at server level.)
- The web app manifest must be served as `application/manifest+json` (today it is typically `application/octet-stream`):

  ```nginx
  types { application/manifest+json webmanifest; }
  ```
- Turn on gzip or brotli for `text/html`, `text/css`, `application/javascript`, `application/json`, `application/manifest+json` and `image/svg+xml`. Local lab runs have no compression; with it the CSS, JS and HTML shrink by 70% or more. Cloudflare does this automatically when it proxies the site. Verify: `curl -sI -H 'Accept-Encoding: br, gzip' https://<host>/css/style.min.css` shows `content-encoding`.
- Branded 404 for files nginx cannot find (the app already shows its own 404 for page URLs): `error_page 404 /404.php;` inside the `server { }` block. Test with `curl -si https://<host>/missing.png` (expect status 404 with the site layout).

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

## Branded 404 for unknown URLs

Unknown URLs on the server currently print the bare web server "File not found." page. The app has a branded 404 page (`/404.php`: HTTP 404, noindex, search box and category links). Add inside the `server { }` block:

```nginx
error_page 404 /404.php;
location = /404.php { internal; fastcgi_param REDIRECT_STATUS 404; include fastcgi_params; fastcgi_pass <same upstream as the other .php locations>; fastcgi_param SCRIPT_FILENAME $document_root/404.php; }
```

Also make sure `fastcgi_intercept_errors on;` is set if PHP answers 404 for a missing script. Test: `curl -I https://<host>/no-such-page` must return 404 with the branded body, and `curl -I https://<host>/404` must also return 404 (it no longer returns 200).

## CDN (Cloudflare): what is cached today and the rules that cache the rest

Checked on beta on 2026-10-03 with `curl -I`. Cloudflare already caches static files by extension (images, `.css`, `.js`, fonts: `cf-cache-status: HIT` after the first request). It does **not** cache HTML or the JSON feeds: every page shows `cf-cache-status: DYNAMIC`, even though the app already sends `Cache-Control: public, max-age=0, s-maxage=120, stale-while-revalidate=600, stale-if-error=3600` on public pages. Cloudflare ignores those headers for HTML until a Cache Rule makes the page eligible.

Dashboard steps (Caching, then Cache Rules; about 10 minutes, nothing to deploy):

1. **HTML and feeds.** Rule "Cache public pages": when the request URI path does not start with `/admin`, `/checkout`, `/ajax/` (add a second rule for `/ajax/get-home-feed.php`, `/ajax/get-location-category-events.php`, `/ajax/get-top-performers.php`), `/unsubscribe`, `/thank-you`, `/order-confirmation`, `/search`, `/newsletter` and the method is GET: **Eligible for cache**, Edge TTL **Use cache-control header if present**, Browser TTL **Respect origin**. The origin headers then decide: pages 2 minutes at the edge, served stale for 10 more while it refreshes, and kept for an hour if the origin is down. The app sets no session cookie on public pages (checked), so there is nothing to bypass on.
2. **Static files.** Rule "Static files": path starts with `/css/`, `/js/`, `/fonts/`, `/lib/`, `/images/`: Edge TTL 1 month, Browser TTL respect origin. (The `?v=` stamp on our bundles changes with the content, so nothing stale is served after a deploy.) For the browser side, also add the nginx rules in "Caching and compression" above: `*.min.css` and `*.min.js` are currently `max-age=2592000` without `immutable`.
3. **Speed settings** (Speed, Optimization): Brotli on, HTTP/3 on, Early Hints on, 0-RTT on. Leave Rocket Loader OFF (it breaks the seat-map widget) and Auto Minify off (the build already minifies).
4. **Purge after a deploy** that changes HTML only (CSS and JS bundles are versioned): Caching, Configuration, Purge Everything, or purge by URL. With the 2 minute edge TTL pages refresh by themselves within about 2 to 12 minutes anyway.

Check: `curl -sI https://<host>/ | grep -i cf-cache-status` should read `MISS` once and then `HIT` (or `REVALIDATED`/`UPDATING` inside the stale window).

## Security headers
The app sends `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin` and `Permissions-Policy: camera=(), microphone=(), geolocation=(self)` on every public page (`soSendSecurityHeaders()` in `functions.php`). Static files (CSS, JS, images, fonts) do not go through PHP, so add the same first three in nginx or a Cloudflare Transform Rule if you want them there too. HSTS is a Cloudflare setting. There is no Content-Security-Policy yet: AdSense, GTM and the ticket widgets load from many hosts, so run one in report-only mode first.

## Static file caching and CSP report-only (added with the Worker)
- The Worker file now sets `Cache-Control: public, max-age=31536000, immutable` on `/fonts/`, `/lib/` and `*.min.css|js` (bundles carry a `?v=` stamp), `max-age=2592000` on `/images/` files, and adds `X-Content-Type-Options` and `Referrer-Policy` to static files. Redeploy the Worker to apply it. After cutover (Worker removed) use the nginx lines in "Caching and compression" above.
- Pages send `Content-Security-Policy-Report-Only`. Nothing is blocked. The browser posts violations to `/ajax/csp-report.php`, which writes one line each to the PHP error log (`grep csp-report-only`). After about a week, add the hosts that appear, then rename the header to `Content-Security-Policy` in `soSendSecurityHeaders()` (`functions.php`).

To enforce the CSP: after about a week, run `php tools/csp-report-summary.php <php-error.log>` on the server, add any host you recognise to the policy in `soSendSecurityHeaders()`, then set `putenv('CSP_ENFORCE=1');` in `inc/env.local.php`. Set it back to remove enforcement if something breaks.
