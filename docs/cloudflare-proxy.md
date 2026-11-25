# Cloudflare proxy for seatoutlet.com (no Worker)

Written 25 Nov 2026. Goal: Cloudflare answers most page views, all static files and a large part of the crawler traffic, so the server does far less work. This replaces the old Worker design. There is no Worker, so no Worker request limit applies: only the proxy and the cache rules below are used.

Status: **prepared, not switched on.** The code and the Vhost are ready (this pull request). The dashboard steps below need the Cloudflare login (the account that owns the `seatoutlet.com` zone); the nameservers are already Cloudflare's, so turning the proxy on is a toggle on the DNS records.

Why it matters: with DNS only (grey cloud) every request reaches nginx and PHP. The pages already send `Cache-Control: public, max-age=0, s-maxage=120, stale-while-revalidate=600` (`sendPageCacheHeaders()` in `functions.php`), which a CDN would use, but nothing is in front to use it. Measured on 25 Nov 2026: the server stopped answering page, artist, event and even `/robots.txt` requests within 30 seconds.

## What is already safe (checked in the code)

| Concern | State |
|---|---|
| Visitor address | `soClientIp()` believes `CF-Connecting-IP` only when the connection comes from Cloudflare's published ranges. The 22 ranges in `inc/request-guard.php` were compared with Cloudflare's current list (`https://www.cloudflare.com/ips-v4`, `-v6`) on 25 Nov 2026: identical. |
| Rate limits | The contact form and the image lookup now use `soClientIp()` (this pull request). Before, they used the raw connection address, which behind the proxy is a Cloudflare edge address shared by many visitors. The crawl guard and the health counters already used `soClientIp()` / the request Host. |
| Host header | The proxy passes the visitor's Host unchanged. The code reads only `Host` (never `X-Forwarded-Host`). |
| Cookies | Anonymous pages set no cookie and do not vary by cookie (checked live on the home, artist, event, search and location endpoints). `order-confirmation` uses a cookie and is `no-store`. |
| Never cached | `/checkout`, `/order-confirmation`, `/thank-you`, `/admin` and every non-GET send `Cache-Control: private, no-store`. A page built without ticket data (feed down) sends `no-store` too, and answers 503. |
| nginx logs and limits | Both server blocks of the Vhost (port 80 and port 443) set `set_real_ip_from` (Cloudflare's ranges) and `real_ip_header CF-Connecting-IP`, so the access log shows the visitor, not Cloudflare. Harmless while the proxy is off. |

## Steps (owner or whoever has the Cloudflare login; about 20 minutes)

Do this only when the server is answering normally (`node tools/live-watchdog.mjs https://seatoutlet.com` passes). The cache is empty at the start, so the first requests per page still reach the server.

1. **Vhost.** Paste `deploy/nginx/seatoutlet.com.vhost` into CloudPanel > Sites > seatoutlet.com > Vhost and Save. CloudPanel tests the nginx syntax on save; if it reports an unknown directive, the nginx build has no `realip` module: remove the `set_real_ip_from` and `real_ip_header` lines and save again (the application does not depend on them).
2. **SSL/TLS mode.** Cloudflare > SSL/TLS > Overview: set the mode to **Full (strict)**. The origin already has a valid certificate (CloudPanel's Let's Encrypt). Not "Flexible" (redirect loop) and not "Full" (accepts an invalid certificate). Certificate renewal keeps working: the Vhost allows `/.well-known/acme-challenge/` on ports 80 and 443.
3. **Cache Rule for pages.** Cloudflare > Caching > Cache Rules > Create rule, name "Pages, respect origin":
   - If incoming requests match (Edit expression):
     `(http.host eq "seatoutlet.com") and (http.request.method eq "GET") and not starts_with(http.request.uri.path, "/admin") and not starts_with(http.request.uri.path, "/ajax") and not starts_with(http.request.uri.path, "/checkout") and not starts_with(http.request.uri.path, "/order-confirmation") and not starts_with(http.request.uri.path, "/thank-you")`
   - Then: Cache eligibility **Eligible for cache**. Edge TTL: **Use cache-control header if present, bypass cache if not** (the pages send their own `s-maxage`; anything without a header is not cached). Browser TTL: **Respect origin**. Leave "Serve stale content while revalidating" on.
   - Deploy. (Cloudflare caches static files such as CSS, JS and images by default. The Vhost sends one-year `immutable` headers for fonts, `lib/` files and minified CSS and JS, and 30-day headers for other static files; Cloudflare follows them.)
4. **Cache Rule to bypass the dynamic paths** (belt and braces, put it above the rule in step 3): name "Never cache dynamic paths", expression `starts_with(http.request.uri.path, "/admin") or starts_with(http.request.uri.path, "/ajax") or starts_with(http.request.uri.path, "/checkout") or starts_with(http.request.uri.path, "/order-confirmation") or starts_with(http.request.uri.path, "/thank-you")`, Cache eligibility **Bypass cache**.
5. **Visitor location (optional).** Cloudflare > Rules > Managed Transforms > "Add visitor location headers": On. The site already falls back to MaxMind on the server when the headers are missing.
6. **Do not challenge the order webhook.** `/ajax/tn-order-webhook.php` receives TicketNetwork's order webhook. If Bot Fight Mode or a WAF rule is on, add a skip rule only for POST requests to `/ajax/tn-order-webhook.php`, so the webhook is never challenged while every other `/ajax/` endpoint stays protected.
7. **Turn the proxy on.** Cloudflare > DNS > Records: the `A` record for `@` and the one for `www` (if it exists): click the grey cloud so it becomes orange (**Proxied**). Leave `beta` as **DNS only**, and leave `checkout`, `cdn-beta` and mail records as they are.
8. **Verify** (from any computer):
   ```bash
   # GET requests that discard the body (the cache rule covers GET; a HEAD request is not a reliable test)
   curl -s -o /dev/null -D - https://seatoutlet.com/artist/menopause-the-musical | grep -i 'cf-cache-status\|server\|cache-control'
   curl -s -o /dev/null -D - https://seatoutlet.com/artist/menopause-the-musical | grep -i cf-cache-status      # second request: HIT
   curl -s -o /dev/null -D - https://seatoutlet.com/checkout | grep -i 'cf-cache-status\|cache-control'          # DYNAMIC or BYPASS, never HIT
   curl -s  https://seatoutlet.com/ajax/get_ip_details.php                                     # a city for your own address
   node tools/live-watchdog.mjs https://seatoutlet.com                                          # passes
   ```
   Expected: `server: cloudflare`, the first page request `MISS` then `HIT`, `/checkout` never `HIT`.

## What changes for visitors and for you

- A page can be up to about 2 minutes old at the edge (`s-maxage=120`), and up to 12 minutes while Cloudflare refreshes it in the background (`stale-while-revalidate=600`). Ticket prices in the page body come from the same caches the server already used (10 minutes for events); the live ticket list and seat map load from TicketNetwork in the browser, not from this HTML.
- After a deploy the new HTML appears within those minutes. For an urgent change: Cloudflare > Caching > Configuration > Purge Everything.
- The 503 page for a feed that is down is `no-store` and is not kept at the edge.
- The watchdog and the health endpoint now reach the site through Cloudflare. A server that is down will show Cloudflare error pages (520 to 524) instead of a plain timeout; the watchdog fails on both.
- The server's firewall can optionally be limited to Cloudflare's ranges on port 443 so nobody can bypass the proxy. Do not do this while `beta.seatoutlet.com` is DNS only on the same server, or it becomes unreachable.

## Rollback

Cloudflare > DNS > Records: click the orange cloud on `@` (and `www`) so it becomes grey (**DNS only**). Traffic goes straight to the server again within a few minutes (the records' TTL is automatic). Nothing else has to be undone: the Vhost lines and the code are harmless without the proxy, and the cache rules only act on proxied traffic. If a page looks wrong first, use Purge Everything.

## Maintenance

- Cloudflare's address ranges change rarely. When they do, update `soTrustedProxyRanges()` in `inc/request-guard.php` and the `set_real_ip_from` lines in the Vhost together. `tools/test-cloudflare-proxy.php` fails if the two lists differ.
- `tools/test-cloudflare-proxy.php` is part of CI.
