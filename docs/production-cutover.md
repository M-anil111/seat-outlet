# Production cutover: separate live and beta, with no paid tools

Goal: seatoutlet.com (live) and beta.seatoutlet.com (beta) are two separate sites on the same server. They run the same code today
and can differ from now on, so you can build and test on beta and only then put it live.

Everything here is free: the existing server, GitHub Actions (unlimited for a public repository), Cloudflare's free plan
(proxy, Cache Rules, Redirect Rules, free Origin CA certificate) and the scripts in this repository. The Cloudflare Worker
`seatoutlet-blog-proxy` is not needed afterwards and is deleted at the end, so the paid Workers plan is never required.

## How the two sites relate

| | Beta | Live |
|---|---|---|
| Address | beta.seatoutlet.com | seatoutlet.com (www redirects to it) |
| Code branch | `main` (every merged change, within 2 minutes) | `production` (only when you promote) |
| Database | its own, `seatoutlet_beta` | its own, `seatoutlet_live` |
| Search engines | blocked (noindex, robots Disallow) | indexed |
| Customer email | off (unless `SO_ALLOW_BETA_MAIL=1`) | on |
| Analytics tag (`GTM_ID`) | `off` | the real container |
| Database changes | applied automatically on deploy | applied when you promote |

Day to day:
1. Build on a branch, open a pull request, CI runs, merge to `main`. Beta updates itself in about 2 minutes.
2. Test on beta.
3. GitHub > Actions > **Promote to production** > Run workflow (leave the box empty to promote the tip of `main`). It refuses a
   commit whose CI is not green. Live updates itself in about 2 minutes.
4. Rollback: run the same workflow with an older commit SHA in the box.

Keep database changes **add-only** (new tables or columns, nothing dropped or renamed in the same release). The migration runs
at the moment live deploys, so the old code must still work against it for those two minutes and for a rollback.

## Part 1: the server (whoever has SSH; about 45 minutes)

Names below follow the beta layout (`/home/seatoutlet-beta/htdocs/beta.seatoutlet.com`). Adjust paths to your panel.

1. **Create the live site.** In the hosting panel add the site `seatoutlet.com` (PHP, same version as beta), document root
   `.../htdocs/seatoutlet.com`. Apply the same web server rules as beta (`docs/server-rewrites.md`, `docs/beta-runbook.md` section 4).
2. **Create the live database** and a database user that can only use it:
   ```
   mysql -e "CREATE DATABASE seatoutlet_live CHARACTER SET utf8mb4"
   mysqldump --single-transaction seatoutlet_beta | mysql seatoutlet_live      # start live from today's beta data
   ```
   After this the two databases are independent. Admin logins, blog posts, page rules and pictures come across; from here on
   they diverge, which is the point.
3. **Live settings.** Copy beta's `inc/env.local.php` to the live document root and change only:
   ```
   putenv('HOME_URL=https://seatoutlet.com');
   putenv('HOME_PATH=<live document root>');
   putenv('DB_NAME=seatoutlet_live');   // and DB_USER / DB_PASS for the live user
   putenv('SITE_INDEXABLE=1');
   putenv('GTM_ID=GTM-W2XCB423');
   putenv('SENTRY_ENVIRONMENT=production');
   ```
   Then `php tools/check-env.php --production` in the live folder: fix every FAIL.
4. **Beta settings.** In beta's `inc/env.local.php` make sure these are set, so tests never touch live analytics or customers:
   ```
   putenv('GTM_ID=off');
   putenv('SITE_INDEXABLE=0');
   putenv('SENTRY_ENVIRONMENT=beta');
   ```
   Do not set `SO_ALLOW_BETA_MAIL` (beta then sends no customer email).
5. **Live deploy job.** One more cron entry, same script, different branch and folders:
   ```
   */2 * * * * DEPLOY_BRANCH=production DEPLOY_WEBROOT=/home/<user>/htdocs/seatoutlet.com DEPLOY_WORKDIR=/home/<user>/deploy/checkout-live /home/<user>/deploy/pull-deploy.sh >> /home/<user>/deploy/deploy-live.log 2>&1
   ```
   (The beta line stays as it is.) The same read-only deploy key works for both.
6. **Create the `production` branch** by running the **Promote to production** workflow once. Until it exists the live job
   has nothing to pull.
7. **Cron jobs for live** (as commands, from the live folder). Beta keeps only the image and cache jobs.

   | Job | Live | Beta |
   |---|---|---|
   | `cron/resolve-images.php` (10 min), home and list cache jobs, `cron/warm-listings.php` (5 min) | yes | yes |
   | `cron/send-alerts.php` (daily), `cron/send-price-alerts.php` (3 h) | yes | **no** |
   | `cron/build-sitemaps.php` (6 h), `cron/indexnow.php` (30 min, needs `SO_INDEXNOW_KEY`) | yes | no |
   | `cron/backup-db.php` (nightly) | yes | optional |
   | `cron/geoip-update.php` (weekly) | yes | yes |

8. **Test live before any DNS change**, from your own computer (replace the IP with the server's):
   ```
   curl -sk --resolve seatoutlet.com:443:<server-ip> https://seatoutlet.com/ | grep -c "<title>"
   curl -sk --resolve seatoutlet.com:443:<server-ip> https://seatoutlet.com/robots.txt        # Allow: /, Sitemap: https://seatoutlet.com/sitemap.php
   curl -sk --resolve seatoutlet.com:443:<server-ip> https://seatoutlet.com/sitemap.php | head   # the index with about 16 files
   ```
   Open the home page, an artist, an event and the blog the same way (or temporarily add the IP to your hosts file).

## Part 2: Cloudflare (dashboard; about 15 minutes)

1. **SSL/TLS > Origin Server > Create Certificate** (free). Install it on the live site on the server, then set
   **SSL/TLS mode to Full (strict)**.
2. **DNS:** the `seatoutlet.com` record points at the server's IP, proxied (orange cloud). Keep `www` proxied too (a redirect
   needs a proxied record).
3. **Workers Routes:** remove the route for seatoutlet.com, then delete the `seatoutlet-blog-proxy` Worker. Reload the live site
   and confirm it still loads (it is now served by the live site directly).
4. **Rules > Redirect Rules (free):** if hostname equals `www.seatoutlet.com`, redirect to
   `concat("https://seatoutlet.com", http.request.uri)`, status 301, preserve query string.
5. **Caching > Cache Rules (free):** when hostname is `seatoutlet.com` and the path does not start with `/admin`, `/checkout`,
   `/order-confirmation`, `/ajax`: **Eligible for cache**, Edge TTL **Use cache-control header if present**. The site already
   sends `s-maxage=120, stale-while-revalidate=600`, and answers `no-store` for pages built without API data.
6. **Speed > Optimization > Content Optimization: Rocket Loader Off.** **SSL/TLS > Edge Certificates: Always Use HTTPS On.**
7. **Search Console:** add the domain property, then Sitemaps > submit `https://seatoutlet.com/sitemap.php`.

Rollback at any point before step 3 of Part 2: nothing changed for visitors. After: point the DNS record back to the old setup
and re-create the Worker route (keep the Worker's code until a week of live traffic has gone well).

## Keeping beta realistic

To refresh beta with live's data (it removes the sign-up and contact tables from the copy and never touches live):
```
LIVE_DB=seatoutlet_live BETA_DB=seatoutlet_beta deploy/clone-live-db-to-beta.sh
```
(needs `~/.my-live.cnf` and `~/.my-beta.cnf`; see the script header). Run it by hand before testing something that depends on real data.

## After the cutover: check

- `https://seatoutlet.com/robots.txt` shows the Disallow list and the sitemap.php line; `https://seatoutlet.com/sitemap.xml` is
  served by the site, not the Worker.
- `curl -sI https://seatoutlet.com/concert-tickets-for-sale` shows `cf-cache-status: HIT` on the second request.
- `https://beta.seatoutlet.com/robots.txt` still says `Disallow: /`, and beta pages have `noindex`.
- GTM fires on live only (Tag Assistant), never on beta.
- Home page: Top Performers and Top Venues fill in.
- `cat <live folder>/.deployed-commit` equals the `production` branch tip; beta's equals `main`.
