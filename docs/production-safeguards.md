# Watching the live site: what happened on 24 Nov 2026 and what now checks it

## What was reported and what was observed

The owner's team reported that event pages answered "Invalid event." and that artist pages did not work. Checks from outside on the morning of 24 Nov (UTC):

| Time | Observation |
|---|---|
| about 07:00 to 08:20 | `seatoutlet.com` answered from a server setup that had no live data: real events answered 404 "Invalid event.", artist pages listed no events, the home page had no events, every page was `noindex, nofollow` with the beta address in its links, `/ajax/health.php` had no deploy commit. Responses came straight from the origin (`server: nginx`, no `cf-ray`); public DNS returned the origin server's own address (A record, TTL 300) while the nameservers were still Cloudflare's. |
| about 08:25 | The same hostname answered normally: `index, follow`, real artist pages with events, the event from the report opened (200). DNS and the `server: nginx` header were unchanged. Health still had no deploy commit. |
| 08:30 onward | The watchdog below passes against the live site (home 200, health ok, an artist page with 20 events, 3 of 3 events open). |

The origin serves the real application under its beta hostname (`beta.seatoutlet.com`) and also under the public hostname, but the two did not behave the same that morning. `docs/production-cutover.md` describes a planned change that matches this: live and beta become two separate installs on the same server (live on its own database and code branch, the Cloudflare Worker retired). **Whether that change was what happened is not confirmed**: the repository has no `production` branch and the promote workflow has never run, so how the live install gets its code is not visible from here. Nothing in the repository changed between 20 and 24 Nov until the pull requests of 24 Nov.

## Corrections to earlier work on 24 Nov

The first versions of PR 300 treated the morning as an outage caused by Cloudflare's proxy being switched off and lost server settings. That was a misreading: it was most likely a cutover in progress. PR 300 had added a guard that answered 503 to any request for the public hostname that did not come through the Worker. With the Worker retired that would have taken the live site down if that code had reached it. It was merged for a short time (`a2edfe8d`) and removed in the next change; whether the live install ever received it is not confirmed.

## What stays: the outside watchdog

`.github/workflows/watchdog.yml` runs `tools/live-watchdog.mjs` every 15 minutes against `https://seatoutlet.com`. Read-only (a handful of GET requests, no credentials). It fails when:

- the home page does not answer 200 within 12 s, or its robots meta says `noindex` or `nofollow`, or it still carries the origin's beta address, or its canonical is not the live host;
- `/ajax/health.php` is not JSON or says `ok:false` (a missing deploy commit is only noted);
- `robots.txt` says `Disallow: /`, or the sitemap lists no pages;
- the first artist page in the sitemap does not answer 200, lists no events and has no "no upcoming events" message, or has events but is `noindex`;
- none of up to three events linked from that artist page opens (an error or "Invalid event.");
- search answers something other than 200 twice in a row (one 503 from a throttled ticket feed is ignored).

It deliberately does not require the home page to link events or artists (the home feed is filled by the browser) and does not require Cloudflare (the live design may not use it). A failed run emails the person who last edited the schedule (GitHub's rule) and shows red in the Actions tab; run it by hand from Actions, Production watchdog, Run workflow, or with `node tools/live-watchdog.mjs`. `tools/test-live-watchdog.mjs` tests it against fixture sites in CI. Against the broken state of that morning it would have failed within 15 minutes.

## Not covered (needs the owner, the host or whoever did the cutover)

- What happened that morning and when it ended: the Cloudflare audit log, the server's deploy output and the people involved.
- A deploy marker for the live install: `/ajax/health.php` still reports no commit for it, so a release cannot be told from outside.
- An external uptime monitor from a second vendor (the watchdog runs on GitHub's schedule, which can be delayed a few minutes).
