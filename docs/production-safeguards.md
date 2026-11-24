# Production: served directly by CloudPanel, nginx and PHP (no Cloudflare Worker)

## How production is built (since 24 Nov 2026)

| | Live | Beta |
|---|---|---|
| Address | `https://seatoutlet.com` (`www` and `http` answer 301 to it) | `https://beta.seatoutlet.com` |
| CloudPanel site and user | `www.seatoutlet.com`, user `seatoutlet` | `beta.seatoutlet.com`, user `seatoutlet-beta` |
| Web root | `/home/seatoutlet/htdocs/www.seatoutlet.com` (confirmed 24 Nov 2026: files there are served; keep no archive or second copy inside it) | `/home/seatoutlet-beta/htdocs/beta.seatoutlet.com` |
| Settings | its own `inc/env.local.php`: `HOME_URL=https://seatoutlet.com`, `HOME_PATH=<live web root>/`, `SITE_INDEXABLE=1` | beta's own file, noindex |
| DNS | `seatoutlet.com`: A record to the server, DNS only. `www`: proxied through Cloudflare at the last check (no Worker), redirected to the bare domain | A record to the server |
| Vhost | `deploy/nginx/seatoutlet.com.vhost` (in this repository; paste it whole) | beta's own |

The Cloudflare Worker (`seatoutlet-blog-proxy`) and its routes were deleted by the owner on 24 Nov 2026 and must not be restored. Its source was removed from
this repository in the same change. Everything it did is now done by the live install itself:

| The Worker did | Now done by |
|---|---|
| Fetched every page from the beta install and replaced the beta address with the live one, stripped `noindex` | The live install's own settings (`HOME_URL`, `SITE_INDEXABLE=1`): pages are built with the live address and are indexable |
| Clean addresses (`/artist/x`, `/concerts-city/x`, `/artist-city/a/b`, holiday pages, `/county/x`, `/blog/x`, pages without `.php`) | The Vhost's rewrites |
| Blocked repository paths (`/docs`, `/db`, `/vendor`, dot files, `*.md` ...) | The Vhost (404) |
| `robots.txt`, sitemap redirects, `sitemap.xsl` as `text/xsl` | The Vhost (`robots.php`, 301s, types) |
| Branded 404, `/favicon.ico`, `/.well-known/security.txt` | The Vhost (`error_page 404 /404.php`); both files exist in the web root |
| www to the bare domain, lower case, no trailing slash | The Vhost (http and www) and `soRedirectCanonicalForm()` |
| Visitor location headers (`CF-IPCity` ...) | MaxMind GeoLite2 on the server (`cron/geoip-update.php`); the Cloudflare headers are used only if a request ever comes through the Cloudflare proxy again |
| A home-page script that filled empty feeds | Not needed: `js/home.js` and `js/near-you.js` load the feeds themselves (the Worker's script ran only when they had not) |

## Code changes for this setup (this PR)

- **No 503 for the public host.** PR 300 added a guard that answered 503 to any request for `seatoutlet.com` that did not carry the Worker's `X-SO-Public-Host` header. With the Worker gone that is every request, so the live site would have gone down at its first deploy. The guard and its health flag are removed.
- **No trusted proxy headers.** Nothing sits in front of nginx, so `X-Forwarded-Host` is whatever the client sends. The www redirect used it as the redirect target (`X-Forwarded-Host: www.evil.example` sent visitors to `https://evil.example`); it now reads only the request's own Host and only ever redirects to the site's own host. The contact form's same-origin check and the health counters no longer read it either.
- **One TicketNetwork budget and token for both installs.** TicketNetwork allows about 50 calls a minute per app key and cancels an app's previous token a few seconds after a new one is issued. Beta and live are now separate installs (separate users and cache folders) with the same key, so each must not keep its own budget or token. `TN_BUDGET_DIR` (new) and `TN_TOKEN_DIR` point both at one group-writable folder; the files are created `0660` there so the other install's user can use them.
- **Health counters** count a request as live by its own Host (`soViaLiveProxy()`), not by the Worker's header; before, every live page counted as a "direct beta" hit.
- **Watchdog** (`tools/live-watchdog.mjs`, every 15 minutes): no longer requires Cloudflare's `cf-ray` header (it failed on every run); now also fails when the sitemap lists beta addresses, and notes what the location lookup answers.

## Server checklist (CloudPanel, as root; each needs the owner's go-ahead)

1. **Vhost.** CloudPanel > Sites > seatoutlet.com > Vhost: replace everything with `deploy/nginx/seatoutlet.com.vhost`, Save. If Varnish is still on for the site, turn it off (it is no longer used).
2. **Shared TicketNetwork folder.** One folder both users can write, then the same two lines in both `inc/env.local.php` files:
   ```bash
   groupadd -f so-shared && usermod -aG so-shared seatoutlet && usermod -aG so-shared seatoutlet-beta
   mkdir -p /home/so-shared/tn && chown root:so-shared /home/so-shared /home/so-shared/tn && chmod 2770 /home/so-shared /home/so-shared/tn
   # in BOTH env.local.php files:
   #   putenv('TN_TOKEN_DIR=/home/so-shared/tn');
   #   putenv('TN_BUDGET_DIR=/home/so-shared/tn');
   systemctl restart php8.3-fpm   # the pool users pick up the new group
   ```
3. **Visitor location.** Copy `MAXMIND_ACCOUNT_ID` and `MAXMIND_LICENSE_KEY` into the live `inc/env.local.php` (same values as beta), then as the live user: `php cron/geoip-update.php`, and add it weekly to the live user's crontab. The file lands at `/home/seatoutlet/htdocs/geoip/GeoLite2-City.mmdb` (next to the web root, outside it). Check: `curl -s https://seatoutlet.com/ajax/get_ip_details.php` from a home connection shows a city.
4. **Sitemaps.** The live `sitemaps/` folder holds a copy of beta's files (they list `beta.seatoutlet.com` addresses). As the live user: `php cron/build-sitemaps.php`, then schedule the app crons for the live user (`docs/resource-use.md` lists them and their schedules). Do not schedule the alert or e-mail crons on both installs while they share a database, or every alert goes out twice.
5. **Deploys.** Live runs `deploy/pull-deploy.sh` every 2 minutes as the live user, from the same `main` branch as beta (owner's decision, 24 Nov 2026): a merge to `main` reaches beta and live within minutes. `/home/seatoutlet/deploy/pull-deploy.conf` holds `DEPLOY_WEBROOT=/home/seatoutlet/htdocs/www.seatoutlet.com`, `DEPLOY_WORKDIR=/home/seatoutlet/deploy/checkout`, `DEPLOY_BRANCH=main`, `DEPLOY_SSH_KEY=/home/seatoutlet/.ssh/seatoutlet_deploy` (a read-only GitHub deploy key) `DEPLOY_MIGRATE=0` (live shares beta's database; beta's deploy applies migrations) and `DEPLOY_EXPECT_HOME_URL=https://seatoutlet.com` (the deploy refuses to copy code while the live `inc/env.local.php` does not set that `HOME_URL`: on 24 Nov 2026 the first deploy put live on beta defaults, noindex everywhere and robots.txt `Disallow: /`, because the file had no `HOME_URL`). Roll live back with `DEPLOY_REF=<commit>` in that file. `/ajax/health.php` shows the deployed commit.
6. **Crawler refusal on beta.** Beta and live are now told apart, so `putenv('SO_REFUSE_CRAWLERS=1');` in beta's file (only) keeps search engines and SEO crawlers off beta and its share of the TicketNetwork budget. Never on live.
7. **Check:** `node tools/live-watchdog.mjs https://seatoutlet.com` (or the "Production watchdog" workflow, Run workflow) and `php tools/check-env.php --production` as the live user.

## Not covered (outside the repository)

- Edge caching. With `seatoutlet.com` on DNS only, Cloudflare no longer caches pages or static files; every request reaches the server. The app's own page snapshots and the Vhost's static-file cache headers remain. Putting the apex record back on the Cloudflare proxy (without any Worker) would bring edge caching and the visitor location headers back; that is the owner's decision.
- Images are served from `cdn-beta.seatoutlet.com` (`AWS_CDN_URL`). They work; a `cdn.seatoutlet.com` name would need a new bucket or DNS record.
