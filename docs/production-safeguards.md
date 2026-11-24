# Production safeguards: the live site must never run on beta or sandbox settings

## What happened (24 Nov 2026)

The owner's team reported that event pages answered "Invalid event." and that artist pages did not work. Checks on the live site that morning showed:

| Observation | What it means |
|---|---|
| Every page, the home page included, carried `<meta name="robots" content="noindex, nofollow">` | `SITE_INDEXABLE` was off. It is derived from `HOME_URL`: a `beta.`, `staging.`, `dev.` or `localhost` address means noindex |
| Scripts loaded from `https://beta.seatoutlet.com/...` (30 references on the home page; on 20 Nov they came from `https://seatoutlet.com/...`) | `HOME_URL` had fallen back to its default, the beta address |
| `/event/<real event>` answered 404 "Invalid event."; artist pages answered 200 with no events; the home page had no event links | The ticket API calls were not returning the live catalog: `BASE_URL` defaults to the **sandbox** (`https://sandbox.tn-apis.com`, website config 12498), which has none of the real events or performers |
| `/ajax/health.php` answered `ok:false` and `commit:null` | The deploy marker `.deployed-commit` was missing or unreadable (the server's deploy folder changed) |
| The database still worked, so pages answered 200 | The database settings were still present: only part of the server's settings were lost |

Nothing in the repository changed over the weekend (`main` stayed at `ffdc5634`, 20 Nov). The cause is on the server, outside the repository: the production settings file `inc/env.local.php` (git-ignored, written by hand on the server, never touched by the deploy) or the environment it is loaded from lost the production values for `HOME_URL`, `BASE_URL` and `SITE_INDEXABLE`. **How it happened is not established from outside the server**; the people with server access (the host's file manager history, the output of the deploy cron, which `deploy/pull-deploy.sh` writes to standard output, so it goes wherever the host's cron job sends it) can answer who or what changed it and when. See "Restoring the settings" below.

## Why nothing noticed

Every setting in `inc/constants.php` falls back to a **beta/sandbox** value when it is left out, and "all of them left out" is indistinguishable from a legitimate beta server. The existing guard (`soConfigUnavailable`) caught only one mixed case (live API with a beta address). Nothing watched the live site from outside, so noindex pages, an empty catalog and a missing deploy marker ran for days.

## Safeguards added

| Safeguard | Where | What it does |
|---|---|---|
| **Fail closed** | `inc/constants.php` (`soLiveConfigProblems`) | A request that came through the live Cloudflare Worker (it sends `X-SO-Public-Host: seatoutlet.com`) must run on live settings. If the ticket API is the sandbox, `HOME_URL` is a beta/local address, or `SITE_INDEXABLE` is off, the site answers a plain 503 (never cached, `Retry-After: 60`, the reason goes to the server log, the page names no setting) instead of serving sandbox data and noindex pages. A 503 tells search engines to come back, so the pages stay indexed. A direct hit on the beta address is unchanged. |
| **Health reports it** | `ajax/health.php` | Skips the guard so a monitor can read it. `ok` is false when a live request would run on non-production settings; the detail (`configProblems`, names only) is in the token view. The public answer keeps its four fields. |
| **Outside watchdog** | `.github/workflows/watchdog.yml`, `tools/live-watchdog.mjs` | Every 15 minutes it reads the live site like a visitor: home page 200 and under 12 s, robots meta not noindex/nofollow, no `beta.seatoutlet.com` in the page, canonical on the live host, events linked from the home page, up to three of them opening without "Invalid event.", health `ok` with a deploy commit, `robots.txt` not `Disallow: /`, a sitemap with pages, an artist page and search answering. A failed run emails the person who last edited the schedule and shows red in the Actions tab. Run it now from the Actions tab (Run workflow) or with `node tools/live-watchdog.mjs`. Against the broken site of 24 Nov it fails with six problems. |
| **Tests in CI** | `tools/test-live-config-guard.php`, `tools/test-live-watchdog.mjs` | The guard against the real bootstrap (beta settings through the Worker: 503; direct beta request: 200; live settings: 200; each single wrong setting: 503; the off switch; health still answers), and the watchdog against fixture sites (healthy passes, each breakage fails). |

Switch: `SO_ALLOW_NONLIVE_ON_LIVE=1` in `inc/env.local.php` turns the fail-closed guard off on purpose (for example a planned maintenance with `SITE_INDEXABLE=0`). Remove it afterwards.

## Restoring the settings (what to do if the watchdog or the 503 shows this again)

1. `https://seatoutlet.com/ajax/health.php` shows `ok:false` or `commit:null`; with the `HEALTH_TOKEN` header (see `docs/pull-deploy.md`) `configProblems` names what is wrong. The server log has a line starting "Seat Outlet configuration problem".
2. On the server, open `inc/env.local.php` (web root). It is a list of `putenv()` lines. Compare it with `deploy/env.local.php.example`: the lines that must be present on production, besides the secrets, are `HOME_URL` (the real https address), `BASE_URL` (the live ticket API address), `SITE_INDEXABLE` (`1`), `HOME_PATH`, `DB_HOST`, `DB_NAME` and `DB_USER`. The values are the ones stored as GitHub repository secrets or kept by the owner. Never paste a value into a chat, an issue or a commit.
3. Check it without printing a value: `php tools/check-env.php --production` (it says set / not set and flags every beta/sandbox default).
4. The next request after the file is correct should be served normally (the file is loaded on every request, unless the host caches compiled PHP for a while: if it still answers 503 after a few minutes, ask the host to clear its PHP cache). `https://seatoutlet.com/ajax/health.php` should show `ok:true` and a commit; then run the watchdog from the Actions tab.
5. Keep a copy of `inc/env.local.php` outside the web root (the host's backup, or a password manager's secure note): the deploy never writes it, so a restore of the web folder from before the file existed also loses it.

## Not covered by this change (needs the owner or the host)

- Why the settings were lost: the output of the deploy cron (`deploy/pull-deploy.sh` logs to standard output, so it is in the cron job's mail or its redirected log), the host's activity or file-manager history, or whoever restored or moved the web folder that weekend.
- An external uptime monitor from a second vendor (the watchdog runs on GitHub's schedule, which can be delayed a few minutes under load).
- A deploy-time check that fails the deploy when `check-env.php --production` fails (proposed: it changes `deploy/pull-deploy.sh`, which runs on the production server).
