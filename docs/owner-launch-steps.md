# Owner steps after the SEO, URL and reliability releases

Do these in order. Each step says who can do it, what to change and how to check it. Nothing here needs a code change; the code is already merged.

## 1. Deploy and migrate (server access, 5 minutes)

The pull-based deploy copies the merged code to beta within about 2 minutes (docs/pull-deploy.md). Then, on the server, in the site folder:

```bash
php db/migrate.php          # creates url_slugs (0042) and applies the blog migrations (0040, 0041)
php db/migrate.php --status # every line should say applied
```

Check: `php db/migrate.php --status | tail -5` shows 0042_url_slugs as applied.

## 2. Make /blog/<slug> work (server access, nginx, 5 minutes)

Without this rule the server answers 404 for every article, and the Cloudflare Worker `seatoutlet-blog-proxy` then shows its own short placeholder page. That placeholder is why SE Ranking saw blog titles that were too long and no X (Twitter) card tags.

1. CloudPanel: Sites, your site, Vhost, open the editor.
2. Inside the `server { }` block, before the `location ~ \.php$` block, add:

   ```nginx
   rewrite ^/blog/([a-z0-9-]+)/?$ /blog.php?slug=$1 last;
   ```

3. Save. CloudPanel reloads nginx. If it does not, run `sudo nginx -t && sudo systemctl reload nginx`.
4. Check:

   ```bash
   curl -s -o /dev/null -w "%{http_code}\n" https://beta.seatoutlet.com/blog/upcoming-concert-tours   # 200
   curl -s https://seatoutlet.com/blog/upcoming-concert-tours | grep -c "<h2"                          # many, not 2 or 3
   ```

If you cannot touch nginx yet, the Worker can fetch `https://beta.seatoutlet.com/blog.php/<slug>` instead (that address already works). That is a Worker edit, so the nginx rule is the better fix.

## 2b. City discovery pages (server access, nginx, 2 minutes)

In the same `server { }` block add the rule from docs/server-rewrites.md that serves `/last-minute-tickets/<city>`, `/weekend-events/<city>`, `/cheap-tickets/<city>` and `/best-events/<city>`:

```nginx
rewrite ^/(last-minute-tickets|weekend-events|cheap-tickets|best-events)/([^/]+)/?$ /$1.php?slug=$2 last;
```

Also add the holiday page rule (the line under "City holiday pages" in docs/server-rewrites.md, one rewrite that lists every holiday):

```nginx
rewrite ^/(christmas-shows-near-me|new-years-eve-events|valentines-day-events|st-patricks-day-events|easter-weekend-events|mothers-day-weekend-events|memorial-day-weekend-events|victoria-day-weekend-events|fathers-day-weekend-events|canada-day-events|july-4th-events|labor-day-weekend-events|labour-day-weekend-events|halloween-events|thanksgiving-weekend-events|canadian-thanksgiving-weekend-events|boxing-day-events)-in-([^/]+)/?$ /holiday-city.php?holiday=$1&slug=$2 last;
```

Check: `curl -s -o /dev/null -w "%{http_code}\n" https://beta.seatoutlet.com/best-events/austin-tx` and `https://beta.seatoutlet.com/july-4th-events-in-austin-tx` both answer 200 (a page with few events is still 200 and says noindex).

## 3. One sitemap address (server access, nginx, 3 minutes)

In the same `server { }` block add:

```nginx
location = /sitemap.xml { return 301 /sitemaps/sitemap.xml; }
location = /sitemaps/sitemap-index.xml { return 301 /sitemaps/sitemap.xml; }
location = /sitemap.xsl { default_type text/xsl; expires 1d; }   # the stylesheet that makes the XML sitemaps readable in a browser
```

There are two sitemaps and only two: the XML one for search engines at `/sitemaps/sitemap.xml` and the page for people at `/sitemap`. **Do not** add a rule that redirects `/sitemap.php`: `/sitemap` is served by `sitemap.php` internally, so a `location = /sitemap.php` rule would break it. If you added one earlier, remove it. The page itself sends any visit to `/sitemap.php` or `/sitemap-page` to `/sitemap`.

**The Worker is what made the sitemaps static.** The deployed Cloudflare Worker `seatoutlet-blog-proxy` answers `/sitemap` with a hand-written HTML page (7 blog posts typed in), `/sitemaps/sitemap.xml` with a fixed list of 17 child files, and 410s the old addresses. It overrides the site's dynamic code, so new files such as `holiday-events-1.xml` never appear. Fix: deploy `deploy/cloudflare-worker/seatoutlet-blog-proxy.js` (Cloudflare dashboard, Workers, seatoutlet-blog-proxy, Edit code, paste, Deploy; or `npx wrangler deploy`). It passes `/sitemap`, `/sitemaps/*.xml` and `/sitemap.xsl` through to the site, 301s the retired addresses, and keeps robots.txt with the Disallow list. After cutover (docs/production-cutover.md) the Worker is deleted.

## 4. Rebuild the sitemaps so they list clean URLs (server access, 2 minutes)

The old files still list URLs with ids. On the server:

```bash
php cron/build-sitemaps.php --force
php cron/build-sitemaps.php --status
```

Check: `curl -s https://seatoutlet.com/sitemaps/cities-1.xml | head -20` shows `/city/austin-tx`, not `/city/austin-tx-247`.

Then in Google Search Console, Sitemaps, submit `https://seatoutlet.com/sitemaps/sitemap.xml` (once).

## 5. Start the warmer cron (server access, 2 minutes)

This is what stops the 427 server errors SE Ranking saw: it keeps a good copy of every page so a TicketNetwork throttle never shows an error. Add to the site user's crontab (`crontab -e`):

```cron
*/30 * * * * cd /home/<site-user>/htdocs/<site> && php cron/warm-snapshots.php >> /tmp/warm-snapshots.log 2>&1
```

Check after an hour: `php cron/warm-snapshots.php --status` shows the fresh count rising. Also ask TicketNetwork to raise the rate limit on your key (questions are at the end of docs/ticketnetwork-api.md).

## 6. www to non-www redirect (Cloudflare dashboard, 3 minutes)

1. Cloudflare, the `seatoutlet.com` zone, Rules, Redirect Rules, Create rule.
2. Name: `www to apex`. Choose Custom filter expression and use the editor:

   ```
   (http.host eq "www.seatoutlet.com")
   ```

3. Then: Dynamic, expression `concat("https://seatoutlet.com", http.request.uri.path)`, status code 301, tick Preserve query string.
4. DNS: make sure a `www` record exists and is proxied (orange cloud), otherwise the rule never sees the request.
5. Deploy. Check: `curl -sI https://www.seatoutlet.com/blog | grep -i location` prints `https://seatoutlet.com/blog`.

## 7. Cloudflare settings that affect speed and the audit (dashboard, 5 minutes)

- Speed, Optimization, Rocket Loader: Off.
- Scrape Shield, Email Address Obfuscation: Off (the code also wraps pages in `email_off`, so this is belt and braces).
- Caching, Browser Cache TTL: 1 year for static files, or use the nginx rules in docs/server-rewrites.md.
- Compression: Brotli on.

## 8. The Worker pins two JavaScript files (read this before editing the Worker)

The Worker serves `/js/main.min.js` and `/js/home.min.js` from a fixed GitHub commit (`LIVE_REPO_COMMIT`), so new releases of those two files never reach seatoutlet.com through that path. The site already avoids it by linking `/js//main.min.js` (double slash), so the live pages get the deployed file. Do not remove that double slash. At cutover the Worker goes away and the issue with it.

## 9. Recheck and read the result

1. Wait until step 5 has run at least twice (an hour).
2. SE Ranking, Website Audit, Recheck audit for seatoutlet.com.
3. What should be gone: duplicate titles, H1s and descriptions on event pages, noindex filter pages, the email-protection 404, missing alt text, blog title and X card errors, most 5XX.
4. What can remain: pages TicketNetwork is still throttling at the moment of the crawl (rerun later), and informational notices such as external links that point to other sites.
5. If a new issue type appears, send the list to me with the audit date and I will map it to a fix.

## Rollback

All code changes are small and revertible per pull request. The nginx rules are additive: remove the lines you added and reload nginx. The migrations only add a table (`url_slugs`) and blog rows.
