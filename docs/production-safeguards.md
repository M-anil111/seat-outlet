# Production safeguards: the live site must always be served through Cloudflare and the Worker

## How production is built (read this first)

The origin server runs one PHP install and serves the real application under its **own beta hostname**. It runs on beta-style settings on purpose (`HOME_URL` on the beta address, pages marked `noindex`). The public site, `seatoutlet.com` and `www.seatoutlet.com`, is the **Cloudflare Worker** (`deploy/cloudflare-worker/seatoutlet-blog-proxy.js`) in front of it: it fetches the origin under the beta hostname, adds `X-SO-Public-Host`, replaces the beta address with the live host in every page, strips the `noindex, nofollow` tag, rewrites the clean URLs that the origin's web server has no rule for (`/county/x`, `/state/x`, the `*-city` pages, ...), and answers the branded 404. The DNS records of the public hostnames must therefore be **proxied** (orange cloud) so that traffic reaches the Worker. Without the Worker the origin's raw pages are what visitors and search engines get.

## What happened (24 Nov 2026)

The owner's team reported that event pages answered "Invalid event." and that artist pages did not work. Checks from outside that morning established:

| Observation | Meaning |
|---|---|
| Public DNS (Google DoH): `seatoutlet.com` and `www.seatoutlet.com` returned the **origin server's own address** (A record, TTL 300). The nameservers are still Cloudflare's. | The records are not proxied (DNS only, or repointed). Cloudflare, and so the Worker, is not in the path. A proxied record returns a Cloudflare address. |
| Responses said `server: nginx` with no `cf-ray` header (earlier ones said `server: cloudflare`). | Same: traffic goes straight to the origin. |
| Every page, home included, was `noindex, nofollow`; scripts loaded from the beta address (30 references on the home page). | The origin's raw pages, which the Worker normally rewrites. |
| The origin answered correctly **under its beta hostname**: `/ajax/health.php` `ok:true`, commit `ffdc5634`, the event page opened with its real title, the artist page listed events. | The application, database and ticket data are intact. |
| The origin answered **under the public hostname** from a different server setup: "Invalid event." for real events, artists with no events, an empty home page, health `ok:false` with no commit. | Under that hostname the server's web setup is not the live application (a missing or different vhost, environment or data folder). The live application was only ever reached through the Worker. |

Nothing in the repository changed (`main` stayed at `ffdc5634`, 20 Nov). **Who changed the DNS records, when, and why are not visible from outside**; the Cloudflare account's audit log (Manage Account, Audit Log) shows it. Whether the Worker itself is still deployed is also not visible from outside.

An earlier version of this document (and the first version of PR 300) blamed lost server settings and added a guard that would have answered 503 to every request that came through the Worker, which would have taken a healthy site down. That was wrong: the beta-style settings are the origin's normal state.

## What to do when this happens (restore)

You need the Cloudflare login (the account that owns the `seatoutlet.com` zone).

1. Cloudflare dashboard, `seatoutlet.com`, DNS, Records: the `A` (or `CNAME`) records for `@` and `www` must have **Proxy status: Proxied** (orange cloud). If they are grey ("DNS only") switch them on; if they were deleted, recreate them pointing at the origin with the proxy on.
2. Workers & Pages, the Worker from `deploy/cloudflare-worker/seatoutlet-blog-proxy.js`: it must be deployed and its route must cover `seatoutlet.com/*` and `www.seatoutlet.com/*`.
3. Check: `curl -sI https://seatoutlet.com/` shows `server: cloudflare` and a `cf-ray` header; `https://seatoutlet.com/ajax/health.php` shows `ok:true` and a commit; run the watchdog from the GitHub Actions tab (Production watchdog, Run workflow). DNS answers are cached for up to 5 minutes (TTL 300).

## Safeguards added

| Safeguard | Where | What it does |
|---|---|---|
| **Misrouted traffic answers 503** | `inc/constants.php` (`soMisroutedPublicHost`) | A request whose Host is `seatoutlet.com` or `www.seatoutlet.com` and that did **not** come through the Worker (no `X-SO-Public-Host`) answers a plain 503 (never cached, `Retry-After: 60`, reason in the server log, nothing named on the page) instead of a broken page with noindex and "Invalid event.". A 503 tells search engines to come back, so pages stay indexed; `noindex, nofollow` on every page does the opposite after a few days. Requests through the Worker, direct requests on the beta hostname and local requests are unchanged (tested). |
| **Health says so** | `ajax/health.php` | Skips the guard so a monitor can read it; `ok` is false when asked under the public hostname without the Worker. The public answer keeps its four fields. |
| **Outside watchdog** | `.github/workflows/watchdog.yml`, `tools/live-watchdog.mjs` | Every 15 minutes it reads the live site like a visitor: the page came through Cloudflare (`cf-ray`), home 200 and under 12 s, robots meta not noindex/nofollow, no beta address left in the page, canonical on the live host, events linked from the home page, up to three opening without "Invalid event.", health `ok` with a deploy commit, `robots.txt` not `Disallow: /`, a sitemap with pages, an artist page and search answering. A failed run emails the person who last edited the schedule (GitHub's rule) and shows red in the Actions tab; run it by hand with Run workflow or `node tools/live-watchdog.mjs`. Against the site of 24 Nov it fails with seven problems. |
| **Tests in CI** | `tools/test-live-config-guard.php`, `tools/test-live-watchdog.mjs` | The guard against the real bootstrap (the public host without the Worker: 503; **a Worker request on the origin's beta-style settings: works**; direct beta: works; the off switch; health still answers) and the watchdog against fixture sites (healthy passes, each breakage fails). |

Switch: `SO_ALLOW_DIRECT_PUBLIC_HOST=1` in `inc/env.local.php` turns the 503 off on purpose (for example while moving the site off the Worker). Remove it afterwards.

## Not covered by this change (needs the owner, the host or the Cloudflare account)

- Re-enabling the Cloudflare proxy for the two DNS records: this is the fix for the outage itself and only the Cloudflare account can do it.
- Why the origin does not serve the live application under the public hostname (nginx vhost for `seatoutlet.com`). Adding it, and the rewrite rules in `docs/server-rewrites.md`, would let the site survive a DNS or Worker problem; it is a server configuration change.
- Who changed the DNS records: the Cloudflare audit log.
- A Cloudflare notification for DNS changes (Notifications, "DNS record changes" / account audit log alerts) and restricting who can edit DNS.
- An external uptime monitor from a second vendor (the watchdog runs on GitHub's schedule, which can be delayed a few minutes under load).
