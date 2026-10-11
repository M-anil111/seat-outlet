# Reported Sentry issues: fixes and deployment checks

The screenshot contains repeated instances of several causes. A code change does not close historical Sentry issues or establish that beta has deployed it.

| Issues | Cause and change | Server action |
| --- | --- | --- |
| SEAT-OUTLET-W, V, T | Event headings and supplemental feeds were initialized only by the SEO include. An admin metadata override skipped it, leaving null headings and array offsets. `event.php` now initializes content independently and reuses the event fetched for SEO. | Deploy and open an event with an admin title override. |
| SEAT-OUTLET-R, S | An idle MySQL connection can expire while external API calls are running. FAQ, biography, image, keyword and county SELECTs retry once on a fresh connection for errors 2006/2013. Writes and schema errors are never replayed or hidden. | Check database uptime, timeout and server logs if losses continue. |
| SEAT-OUTLET-D, 3, E | Missing `blog_posts.category`, `images.updated_at` and `images.miss_hits` indicate unapplied schema changes. Existing migrations add these. Migration 0048 additionally repairs a partially skipped multi-column image ALTER. | Run `php db/migrate.php`, then `php db/migrate.php --status`. Check every migration is applied. |
| SEAT-OUTLET-6, B, 7, C | Undefined FAQ artist names were already fixed on main: the listing uses `Live` and search derives a display name. | Deploy current code; inspect new occurrences after deployment. |
| SEAT-OUTLET-Q, H, J, K | TicketNetwork quota responses can have non-JSON bodies. They now consistently trigger stale-data fallback and respect `Retry-After` instead of being mistaken for valid data. | Confirm quota with TicketNetwork and keep crawler concurrency low. A cold cache still cannot supply unavailable tickets. |
| SEAT-OUTLET-F, G | A prematurely closed TLS connection gets one bounded retry for GET only, with normal TLS verification retained. | Investigate upstream/network faults if persistent. |
| SEAT-OUTLET-8, 4 | Failed OAuth requests are reported without logging response bodies; empty tokens never trigger catalog calls. Refreshing requires the shared lock and successful persistence. Process/APCu caches cannot retain an expired or rejected token. The token file is scoped to the OAuth consumer key, so live and sandbox clients with the same key share it. | Set `TN_TOKEN_DIR` to one private directory shared by all web and cron processes using that consumer key, including across hosts. Provision it before starting PHP. |
| SEAT-OUTLET-1, 2, 5 | Deliberate smoke tests from `/tmp/sentry-smoke-test.php` and `tools/sentry-test.php`. | After verifying delivery, resolve these historical test events in Sentry. The CLI test tool remains available. |

## Regression validation

```
php tools/test-event-page.php
php tools/test-tn-reliability.php
# Only a disposable development/CI database: this test kills its own DB connections.
php tools/test-db-recovery.php
```

The first two use local fixtures, not live API credentials. The database test uses a temporary image table and does not alter application data. All three run in CI.

After deployment, check an event with an admin metadata override, search, the ticket listing and images, then watch for new Sentry occurrences. Do not mark quota, database uptime or real API authentication resolved until their affected operations succeed on the server. The new token filename replaces the old cache on first use; deploy all consumers together to avoid old workers refreshing an independent token file.
