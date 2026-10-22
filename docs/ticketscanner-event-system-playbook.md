# Event system playbook: how Seat Outlet uses the TicketNetwork API, what went wrong, and what to build on TicketScanner.ca

Audience: a Claude Code chat working on TicketScanner.ca, which already has the basic TicketNetwork API calls. Paste this whole file into that chat.

Source: the Seat Outlet repository (github.com/M-anil111/seat-outlet, branch `main`), written 22 Oct 2026. Everything below was built and tested there. File names in brackets point to the working reference code. `docs/ticketscanner-port-brief.md` in the same repository covers the page, SEO and content side (URL map, titles, page spec, sitemaps). This file covers the **data and event system underneath it**: API access, caching, rate limits, resilience, event pages and their structured data, traffic control, monitoring, and every problem hit in production with the fix.

Rules for the new chat:
- Do not invent facts, limits, prices, endpoints or numbers. Where this file says "confirm", ask the owner or TicketNetwork.
- Never write credentials (consumer key, secret, tokens, passwords) into files, commits, chat or logs. Use environment variables. If a secret was ever pasted into a chat or committed, rotate it.
- The reference code is PHP. TicketScanner.ca may use a different stack. Port the behavior, not the syntax. Ask first what the stack is.
- Make small pull requests, run the tests, and verify on the live site after every deploy. Several bugs below were only visible live.

---

## 0. Ask these first (and tell the owner what you assumed if there is no answer)

1. Stack, hosting, database, cron access, CDN (Cloudflare?), staging site, deploy method. Is there PHP-FPM, a shared file cache directory, APCu? Several designs below rely on a cache directory shared by every web process and cron job on one host.
2. TicketScanner.ca's **own TicketNetwork application**: consumer key and secret (as env vars only), its own **website config id** (live and sandbox), its **throttling tier** (Seat Outlet is on 50 requests a minute), whether it already has the `bulk` role, the hosted checkout host and the post-purchase redirect.
3. Market: owner decisions already recorded in the port brief: USD only, United States and Canada both, contact email info@ticketscanner.ca.
4. Is a Cloudflare Worker, or any proxy, in front of the site? (See problems 12 and 13: a Worker on the free plan took Seat Outlet down, and a proxy made every request look like a different host.)
5. Error monitoring (Sentry or similar) and a place for alerts to go.

---

## 1. Architecture in one picture

```
visitor / crawler
      |
  page (artist, venue, city, state, category, event, hub)
      |
  tnRequest(endpoint, params)            one function every API call goes through
      |
  1. fresh cache?  yes -> answer
  2. stale but inside grace window? -> answer now, refresh after the response is sent
  3. remembered "not found"? -> answer
  4. circuit breaker open for this family? -> stale copy if allowed, else "no data"
  5. single-flight lock: one live call per cold key, others wait for its answer
  6. call budget (token bucket, by caller class) -> no token: stale copy or "no data"
  7. live call (one retry for a fast dropped connection), parse, store, count
      |
  fallback chain when the live call cannot run:
  stale API answer (2 h lists, 14 d reference) -> stored event copy (24 h) -> database event snapshot (24 h)
  -> page snapshot (up to 14 d, served as 200) -> 503 with Retry-After (never "0 results", never 200 noindex)
```

Principles that drove the design:
- TicketNetwork is the only source of what is on sale. An older answer may be shown only while the live call cannot run, and only for a bounded time.
- The API limit is low (50 a minute) and the site has hundreds of thousands of long-tail URLs. Most traffic is crawlers. Without the controls below, cold pages fail.
- A page must never lie. A failed feed is never shown as "0 results", an event nobody can buy never carries a price it does not have, and urgency copy appears only when tickets are listed.
- Every number you tune must be observable. Add the health counters first, then change limits.

---

## 2. API facts (verified on Seat Outlet; confirm each for TicketScanner.ca's own application)

### 2.1 Auth
- Token: `POST https://key-manager.tn-apis.com/oauth2/token`, HTTP Basic with consumer key and secret, body `grant_type=client_credentials` plus a `scope` (see device scopes). Response has `access_token` and `expires_in` (about 1 hour).
- Every catalog call sends `Authorization: Bearer <token>`, `Accept: application/json` and the header `X-Listing-Context: website-config-id=<id>`. The config id is issued by TicketNetwork per site and carries checkout URL, redirect and branding.
- Environments: sandbox `https://sandbox.tn-apis.com` and live `https://www.tn-apis.com`. Ids from one do not work in the other (a sandbox config id with the live API makes the seat map report every event expired). Add a startup check that refuses a sandbox config with the live API and the reverse (`tools/check-env.php`).
- **A new token revokes the previous token with the same scope.** So web requests and cron jobs must not share one token. Seat Outlet asks for scope `default device_<id>_web` for web and `default device_<id>_cli` for cron (`tnDeviceScope()`, optional `TN_DEVICE_ID` env var per host). With several web hosts on one application, give each its own `TN_DEVICE_ID`.
- Token store: cache the token in a file (and APCu if present), refresh 120 seconds before expiry (`TN_ACCESS_TOKEN_EXPIRY_BUFFER`), take a file lock while refreshing so a burst of requests after expiry issues one token, wait up to about 10 seconds for another process's refresh, and on a 401 use a newer token if another process already refreshed, otherwise fetch a fresh one (`getTnAccessToken($rejected)`).
- WSO2 quirks: "API Subscription validation failed" (403) means the application is not subscribed to that API, not that credentials are wrong. Production keys come from TicketNetwork web support, not from the DevPortal's "Get Test Key".

### 2.2 Limits
- **Confirmed by TicketNetwork on 14 Oct 2026 for Seat Outlet: 50 requests a minute per API** (the `50permin` tier). Tiers that exist: Trial 60, 50permin, 100PerMinute, 600permin, Premium 3000 a minute. Seat Outlet needs 180 to 300 a minute for normal use, so the tier to ask for is **600permin**. Ask for TicketScanner.ca's tier before choosing numbers.
- A throttle looks like HTTP 429 or a JSON body with code `900804` ("Message throttled out"). In WSO2, 900800 is the API level, 900803 the application level, 900804 the subscription level (the one seen). Only TicketNetwork can raise it. No `Retry-After` is sent today (feature request made). No written maximum age for cached prices was given, so show "prices as of" wherever a stored price appears.
- Measured on 13 Oct 2026, ten minutes each: at 1 call a second, 2 calls refused; at 2 a second with bursts of 4, 37 refused with the same number of calls getting through. Conclusion: sending faster only turns budget holds into 429s and open breakers.

### 2.3 Endpoints the site uses (`/catalog/v2/...`)
`events/` (listings, with `filter`, `sort`, `perPage`, `page`), `events/{id}`, `events/search`, `performers`, `performers/{id}`, `venues/`, `venues/{id}`, `cities`, `stateProvinces/{id}`, `categories`, `suggest`, `postalCodes/`. Not used yet but available: `events/bulk` (needs an extra role, section 9), `events/search` facets, `categoryhierarchies`, event `uriComponent` slugs and `scheduleStatus`.
- Event rows carry: `id`, `text/name`, `date/date` and `date/datetimeOffset`, `venue` (id, text/name, address), `city`, `stateProvince`, `country/alphaCode`, `performers` (name, id, role), `defaultCategory/path`, `_metadata/hasTickets`, `_metadata/ticketCount`, `pricingInfo/lowPrice/value`, `pricingInfo/highPrice/value`. There is no event end time.
- Category path ids on Seat Outlet: concerts `.1859.1986.`, sports `.1859.1988.`, theater `.1859.1989.`, festivals `.1859.1986.1877.`. Check them against TicketScanner.ca's own category responses before reusing.
- OData filters: `eq ne gt ge lt le and or not in`, grouping, `contains startswith endswith`, `ancestorsOf`, `descendantsOf` for categories. String literals use single quotes with the quote doubled to escape. Always pass user text through an escaper (`tnEscapeFilterValue()`). Sorting by distance needs a `geoFilter`. Listing filters used: `_metadata/hasTickets eq true`, `date/date ge <today>` and `le <horizon>`, `country/alphaCode eq 'US' or 'CA'` (TicketScanner.ca wants both), `contains(defaultCategory/path, '...')`.
- Ids above int32 (`2147483647`) never reach the API: answer "not found" locally (`tnIdOutOfRange()`), so junk URLs from bots cost nothing.

---

## 3. The request layer, step by step (build this first; it carries everything else)

Reference: `functions.php` (`tnRequest`, `tnRequestRaw`, `tnFetchStore`, `tnFetchLive`, `tnRequestMulti`, `tnScheduleRefresh`) and `inc/tn-budget.php`, `inc/tn-listing-copy.php`.

### 3.1 Cache
- File cache, key = hash of endpoint + params + method, GET only. TTL by endpoint (`tnDefaultTtl()`): reference data (performers, venues, cities, categories) 6 h, event lists and details 10 min, search 5 min, anything with a `geoFilter` 5 min, suggest 1 h. A caller may pass its own TTL (0 bypasses the cache).
- **Stale-while-revalidate**: an entry older than its TTL but younger than TTL + grace is returned immediately, and a refresh is queued to run after the response is sent (`fastcgi_finish_request`, then a shutdown function). Grace = `min(ttl * 3, 24 h)`. The refresh takes the per-key lock, so only one process refreshes.
- Sweep cache files older than 15 days (more than the 14-day stale limit), at random 1 in 200 stores.
- Remembered "not found": a 400/404 with a `Message` body is stored under `<key>_nf` for 300 seconds (`TN_NEGATIVE_TTL`) so bots hitting junk ids do not each cost a call.

### 3.2 Circuit breaker, per family
- Two families: `events` (lists, details, search) and `reference` (performers, venues, cities, categories). A throttle on one must not stop the other (`tnBreakerFamily()`).
- Open for 20 s on an ordinary failure (`TN_BREAKER_SECONDS`), for the server's `Retry-After` (5 to 300 s) or 60 s on a throttle (`TN_BREAKER_THROTTLE_SECONDS`). While open: serve a stale entry if inside its limit, otherwise mark the page "no data" with reason `breaker`. Do not queue more 20 second timeouts behind a dead API.

### 3.3 Single-flight on cold keys
- One live call per cold key: the first request takes a `.flight` lock and fetches, the others wait up to 3 seconds for its answer instead of calling again. Test result on a local stub: 20 simultaneous requests for one cold event made 1 call.

### 3.4 Call budget (token bucket) and caller classes
Every live call takes a token from one bucket shared by every PHP process using the same cache directory (separate hosts have separate buckets). `inc/tn-budget.php`.
- Rate: `TN_CALLS_PER_SECOND`, **default 0.75** (45 a minute, under the confirmed 50), decimals allowed, 0 turns the budget off. Burst = twice the rate, at least 2 tokens. Change it in the server environment with no deploy. Set it from TicketScanner.ca's own tier.
- **Visitor** (normal web request): may use the whole bucket and waits up to 1.5 s for a token. Over budget, the page answers from stored copies like an outage and no call is made.
- **Background** (cron, stale refresh, the snapshot warmer): may take a token only while the bucket is at least half full (the upper half), never waits. A warmer must never use up what visitors need. The warmer's own HTTP visits send a header `X-SO-Warm: 1` plus a dedicated user agent (both required) and are treated as background and left out of visitor counts.
- **Crawler classes**: a named crawler first takes a token from its own small bucket (a share of the rate, burst of at least 4 so the three or four calls of one cold page can all go out, never waits for this one), then a shared token on background terms, waiting up to 1.5 s as a visitor does. If no shared token arrives, the class token is given back. Search engines (Google, Bing, DuckDuckGo, Apple, Yandex, Baidu) share `TN_BOT_SHARE` (default 0.35, about 16 calls a minute). SEO tools, AI crawlers and scripts share `TN_TOOL_SHARE` (default 0.10, about 4 a minute). A share of 0 means stored copies only. Over its share a crawler gets the stored copy, a page snapshot, or a 503 with Retry-After.
- `tnRequestMulti()` (parallel prefetch of several independent calls at the top of a page) waits up to 1.5 s **in total** for tokens, not per call.
- Every refresh (stale-while-revalidate) takes a background token too. Skipped refreshes are counted.

### 3.5 The live call
- Connect timeout 5 s, total timeout 20 s, compression on. One retry only for a fast dropped connection (SSL EOF, reset) that failed in under 3 seconds, and only if the breaker is closed and a token is available. Slow timeouts and throttles are never retried.
- On failure: count it, open the breaker, send an alert, then serve stale if allowed, else mark "no data" with a reason.
- On success: store it, and if it was an events call, save each complete event row as a stored copy (section 4.3).
- An empty list answer while the stored list (inside 2 h) had rows is treated as a flapping feed (`tnEmptyListSuspect()`): keep the stored list, count `empty_suspect`, try again at the next refresh.

### 3.6 Stale limits (what may stand in for a failed call)
| What | Limit | Constant |
|---|---|---|
| Event lists, details, search | 2 hours | `TN_STALE_LISTING` |
| Stored event copies and database snapshot | 24 hours | `TN_LISTING_COPY_TTL` |
| Reference data (performers, venues, categories) | 14 days | `TN_STALE_ON_ERROR` |
| Home lists written by cron | 2 days, and an empty refresh does not overwrite | `cron/home-events.php` |
A confirmed "not found" on an event deletes its stored copies at once (`tnForgetEventCopies()`).
Open question for TicketNetwork: whether their terms limit how long catalog or price data may be cached. Until answered, stored event pages should say "prices as of <time>".

### 3.7 Counters (add these on day one)
`tnCount()` writes a per-day JSON file under a shared lock: `live_call`, `over_budget`, `throttled`, `stale_on_error`, `event_copy`, `event_snapshot`, `empty_suspect`, `no_data`, `no_data_why_<reason>`, `pages`, `pages_suspect`, each also under `_bot`, `_direct` and `_nobrowser` suffixes, plus crawler family splits (`_bot_google`, `_bot_seo`, ...), `refresh_call` and `refresh_skipped`. `no_data` and `pages` are counted once per page, not once per call. Reasons are a fixed set: `breaker`, `budget`, `throttled`, `api_error`, `coalesced`, `unknown`.

---

## 4. Fallbacks so a page survives a throttle or outage

### 4.1 Page snapshots
Every page that renders completely (HTTP 200 with data) is stored gzipped under `cache/pages/`, at most one rewrite per page per 10 minutes. When a page cannot be built, serve the stored copy (up to 14 days old) as a normal 200 with header `X-Page-Snapshot: stale` and a 60 second CDN lifetime. Only a page never built before and unbuildable now answers 503. (`soSnapshotStore`, `soSnapshotServe`, `renderUnavailablePage`.)

### 4.2 Never "0 results" for a failed feed
When the feed fails and there is no snapshot, answer **503 with `Retry-After: 30`**, the page still rendered, with a "loading slowly" card in place of the list and a self-retry every 15 seconds for people (`soFeedDownGate()`). Never 200 with `noindex` (a crawler would index an empty page or drop a good one). A real empty answer (an empty category) is a normal indexable page; only a failed call is not.

### 4.3 Stored event copies and the database snapshot
- Every complete event row a listing returns, and every event page loaded, is saved as a file copy and in the table `tn_event_snapshot` (written at most once an hour per event, pruned after 180 days unseen). The event page uses the file copy first, then the database snapshot, only if seen within 24 hours, when the live call fails. Include the table in the nightly database backup. Migration `0048_tn_event_snapshot.sql`.
- A performer record goes into APCu only when the lookup returned a real record. Caching a failed `[]` for an hour made every later visit 503 (`/artist/hoobastank`, 13 Oct 2026).

### 4.4 Dead performer links
An event's performer list or a long-cached directory can name a performer whose own page answers 404 (gone from the catalog, or no default category). Remember such an id for 3 days (`inc/dead-performers.php`), drop it from chip lists and performer list functions, and clear it when the page renders again. An empty, throttled or failed lookup never counts as dead.

### 4.5 Database connection dropped mid-request
An artist page makes several slow API calls before it reads database rows, and the idle connection may be closed ("MySQL server has gone away", error 2006/2013). `soDbRetry()` reconnects on the same handle (reset the charset afterwards: the reconnect falls back to latin1) and repeats the work, **up to three tries (now, +150 ms, +350 ms)**, because under a real database restart the single retry also failed ("Error while reading greeting packet", Sentry SEAT-OUTLET-13/18). Only for reads that are safe to repeat, never inside a transaction. Every public read that can run after an API call must be wrapped (a missed `get_bio()` kept the issue alive). Test: `tools/test-db-retry.php` kills the connection for real, plus a subclass that refuses reconnects.

### 4.6 Unknown addresses
The web server answered unknown addresses with a bare 16-byte `File not found.` and no content-length, so the Worker's "replace small 404 with the branded page" never matched. Set `error_page 404 /404.php;` in the web server, and make any proxy read a small body when there is no length (`docs/server-rewrites.md`).

---

## 5. Event pages and structured data (JSON-LD)

Reference: `soEventNode()`, `soEventItemList()`, `inc/seo-event.php`, `inc/seo-tickets.php`, `inc/entity-pages.php`, `inc/seo.php`; test `tools/test-event-offer.php`.

One function builds every Event node, so every page type agrees:
- Type: `Event`, or the subtype from the category (`MusicEvent`, `SportsEvent`, `TheaterEvent`, `Festival`, `ComedyEvent`). `@id` = `<event url>#event`. `eventStatus` from the feed (`scheduleStatus`), `eventAttendanceMode` offline, `startDate` with the offset (`date/datetimeOffset`), `isAccessibleForFree` false.
- **No `endDate`.** TicketNetwork gives no end time; an estimate would be a made-up fact.
- `location` Place with address (street, postal code and coordinates only when the venue record has them), `performer`, `organizer` (the venue), `image`, a factual `description`. For sports, the two teams as `competitor`.
- **`offers` only when there is a real price**: `hasTickets` true and `pricingInfo/lowPrice > 0`. `AggregateOffer` with `lowPrice`, `highPrice`, `offerCount`, `priceCurrency` USD, `availability` InStock, `url`, `validFrom` (the feed's `createdAt`, when it was first listed) when a high price exists. With no high price, a plain `Offer` with `price` (an `AggregateOffer` without `highPrice` is flagged in Search Console). Never invent a price.
- **An event with no real price is left out of the markup entirely**: out of every ItemList (hubs, categories, city, state, venue, artist and holiday pages, similar events, the home page), and the event page's own Event node is omitted (`soEventHasOffer()` decides). The page content still shows. See problem 15 for why.
- No fake reviews, ratings or counts anywhere. Organization schema has no review or aggregateRating.
- JSON-LD is printed with `JSON_HEX_TAG | JSON_HEX_AMP` so event names cannot close the script tag (`tools/test-jsonld-safe.php`).
- Event copy rules: honest urgency (sell-out language only when tickets are listed, otherwise an alert sign-up line), "prices as of", and the approved wording from `docs/ticketnetwork-api.md` section 5: comparison site, orders fulfilled by TicketNetwork, no "official tickets", no "no hidden fees" until TicketNetwork confirms. Adjust for whatever TicketScanner.ca's relationship is, and confirm with the owner.
- Event removed or over: `soEventIsOver()`, `soEventRedirectTarget()`; a confirmed "not found" shows a branded not-found, not a blank page. Test every state: on sale, no tickets, over, removed, API down.

---

## 6. Traffic control (this is what actually keeps pages up)

### 6.1 Crawl guard
`inc/crawl-guard.php` counts public page requests per address per minute (files in `cache/ratelimit/`). Over `SO_RL_PER_MIN` (default 120, search engine bots 3 times that, 0 turns it off): answer `429` with `Retry-After`, build nothing, call nothing. Not counted: data endpoints, checkout, admin, cron, sitemaps, robots.txt. Behind a CDN read the address from the CDN header. HEAD counts like GET. Fail open if the counter cannot be written. Reason: one 58,000 page audit crawl (SE Ranking, 4 to 5 Oct 2026) made 12,567 pages answer 503 for everyone. Run audits at 1 or 2 threads with a delay.

### 6.2 Crawler classification
`soCrawlerFamily()` maps the user agent to `google`, `bing`, `search_other`, `seo`, `ai`, `other` or none. A named SEO or search crawler, or a user agent saying "crawler" or "spider"; a bare "bot" is not enough (a Cubot phone or UptimeRobot is not a crawler). Test with real user-agent strings.

### 6.3 Refusing crawlers on non-indexable hosts (opt-in, and dangerous)
On beta or staging (`SITE_INDEXABLE` false) SEO crawlers can be refused with 403 before any page is built. It is **off by default** (`SO_REFUSE_CRAWLERS=1`) because of problem 11: behind a proxy every request looked non-indexable and Googlebot got 403 for 19 minutes. If TicketScanner.ca has a proxy or Worker, decide "is this the live site" from the forwarded host header the proxy sets (`soViaLiveProxy()`), never from the origin's own hostname.

### 6.4 Health page
`/ajax/health.php` (counts and yes/no only, no keys, no names, **no PHP version**): deployed commit and age, migrations pending, `ticketnetwork.today` counters, `callsPerSecond`, `circuitOpen` per family, `pages`, `noDataRate`, `visitorPages`, `visitorNoDataRate`, `emptySuspectRate`, `throttled`, `overBudget`, `noDataWhy`, `demand` (bot and direct shares, crawler families), `refresh {calls, skipped}`, `likelyHumanPages`, `likelyHumanNoDataRate`, `nonBrowserPages`, `viaProxy`, `alert`. How to read it:
| You see | Meaning | Do |
|---|---|---|
| high `overBudget`, low `throttled` | our own budget is the bottleneck | ask TicketNetwork for a higher tier; do not raise the rate yourself |
| high `throttled` | our rate is above their limit | lower `TN_CALLS_PER_SECOND` |
| `noDataWhy` mostly `budget` | demand exceeds the tier | tier, bulk import, edge caching |
| `noDataWhy` mostly `api_error` | TicketNetwork is slow | wait, check Sentry |
| `noDataWhy` `breaker` | a 429 opened the circuit | find what exceeded the real rate (uncounted calls) |
| bot share of calls high | crawlers eat the budget | lower class shares, serve snapshots |
Alerts: `cron/check-tn-health.php` every 15 minutes sends a Sentry warning when visitor no-data passes 20% over 50+ pages or a key listing answers 503 or "0 results".

---

## 7. Cron jobs (all from the command line, with the shared cache directory)
| Job | Every | Purpose |
|---|---|---|
| `warm-listings` | 5 min | keep hubs and top category and city feeds warm |
| `warm-event-details` | 15 min | refresh event details behind listed events (15 per run, cursor stored) |
| `warm-snapshots` | 30 min | visit sitemap URLs at 1 per second, 400 per run, missing and oldest first; stops itself when the API throttles |
| `home-events`, `home-top-performers`, `home-venues`, `home-categories` | hourly | precomputed homepage lists |
| `build-sitemaps` | 6 h | dynamic sitemaps |
| `indexnow` | 30 min | notify search engines |
| `check-tn-health` | 15 min | alert |
| `backup-db` | nightly | dump including `tn_event_snapshot` |
| `check-php-version` | daily | alert while PHP is older than the fixed release |
Crons use the background class (upper half of the bucket only) and their own device-scope token.

---

## 8. Bulk importer (built, not enabled)
`cron/tn-bulk-import.php`, `inc/tn-bulk.php`, migration `0050_tn_bulk_events.sql`, test `tools/test-tn-bulk.php`. Purpose: a local copy of every event so listing, search and sitemap pages need no live call per page.
- Needs an extra DevPortal role and new production keys from TicketNetwork web support. Off until `TN_BULK_ENABLED=1`.
- `/catalog/v2/events/bulk`: sort `createdAt,id`, one request at a time (never parallel), `fields=` list (31 names in `TN_BULK_FIELDS`), up to 10,000 events a request in about 20 seconds (long timeouts). The Swagger says a page holds at most 200 while the guide says 10,000, so a run ends on the first empty page, not a short page.
- Modes: a full refresh about daily (07:00 to 10:59 UTC window) and an hourly changes pull (`metadataInclusiveUpdatedAt ge <time>`). Each call takes a token from the shared budget. A throttle stops the run and saves progress. Old rows are deleted only after a full run that reached the end and saw at least half the previous count.
- Before enabling: validate the mapping against a saved real response (`php cron/tn-bulk-import.php --file=page.json --dry-run`). Then decide how stale a price on a listing may be before switching pages to read the local copy.

---

## 9. Problems hit, and the fix (read this before building; each one cost time)

| # | Problem | Evidence | Fix |
|---|---|---|---|
| 1 | Pages answered 503 on cold URLs | 9 throttles in 2 h on 4 Oct 2026; a 58,000 page audit made 12,567 5XX | stale-on-error 14 days, page snapshots, warmers, crawl guard (sections 3 to 6) |
| 2 | Throttle code unknown | Sentry showed 429 body code 900804 | document the WSO2 levels; ask TicketNetwork for the tier; default 0.75 a second |
| 3 | Raising the rate made things worse | 2 a second: 37 throttled, 38 breaker openings, same 460 calls | keep the rate under the confirmed limit; only a tier change helps |
| 4 | Breaker too coarse | one throttle stopped all calls | per-family breakers (`events`, `reference`) |
| 5 | Third call of a prefetch batch failed on the spot | about 70% of pages "no data" while breakers tripped once | `tnRequestMulti` waits up to 1.5 s in total, burst of 2 tokens |
| 6 | Token revoked by another process | a new token revokes the old one with the same scope | device scopes per web and cron, file lock on refresh |
| 7 | Crawler cold pages almost never finished | 16 Oct 2026: 87% of Googlebot's calls held back while the budget sat 91% idle | class bucket with burst 4 and a 1.5 s wait for the shared token |
| 8 | Crawlers used almost all live calls | 98% of calls, 96% of pages on 16 Oct | class shares 0.35 and 0.10 of the rate; visitors keep the rest |
| 9 | No way to tell why a page had no data | visitor no-data 79% with no explanation | `no_data_why_*` reasons: it was 96% our own budget, not TicketNetwork errors |
| 10 | Background refreshes bypassed the budget | `tnScheduleRefresh` called the API with no token and no count | refreshes take a background token, skipped ones counted; an uncounted call can push the real rate past the limit, and a 429 opens the breaker and blanks a whole family (visitor `breaker` no-data was 735 pages on 20 Oct 2026; the link to uncounted refreshes is likely, not proven) |
| 11 | Googlebot got 403 on the live site for about 19 minutes | live host was a Worker proxy to the beta origin, so every request looked non-indexable | refusal is opt-in; detect the live proxy from the forwarded host |
| 12 | The whole live site returned error 1027 | Cloudflare Workers Free allows 100,000 requests a day, the Worker (which fronts every request and every asset) hit it on 20 Oct 2026 (pages were fine at about 16:00 UTC and returned error 1027 at 16:54 UTC; it resets at 00:00 UTC); the origin was healthy | Workers Paid plan or set the route to fail open, or remove the Worker (`docs/production-cutover.md`). TicketScanner.ca should not put a Free-plan Worker in front of the site |
| 13 | After an outage, long-tail pages had no stored copy to fall back on | a page never built before cannot be served from a snapshot | warmers visit sitemap URLs slowly (1 a second) so every page has a last good copy |
| 14 | `MySQL server has gone away` | Sentry SEAT-OUTLET-R, 12, 13, 18 | `soDbRetry` with three tries, every public read after an API call wrapped |
| 15 | Search Console "Missing field offers", then "Missing field price" | first events had no offer when there was no price; a price-less offer and a SoldOut offer both made it worse (PRs 236 and 237, replaced by 240) | emit an offer only with a real price; leave unpriced events out of the markup; checked 104 live pages, 638 Event nodes, 0 without a priced offer |
| 16 | Empty answer overwrote a good list | flapping feed | `tnEmptyListSuspect()` keeps the stored list |
| 17 | "0 results" page looked real | hubs and genre pages showed empty on feed failure | 503 with Retry-After or snapshot, never 200 |
| 18 | Failed lookup cached for an hour | `/artist/hoobastank` 503 from APCu | cache only real records |
| 19 | Dead performer links | QA-007 | 3-day dead list |
| 20 | Internal links were old 301 addresses | 6,456 "internal links to 3XX" in the audit | link only to the clean slug URLs; keep a slug registry (`inc/slugs.php`, 301 old id URLs) |
| 21 | Images never cached at the edge | `cf-cache-status: DYNAMIC` on the image host | Cloudflare Cache Rule for the image hostname |
| 22 | Critical PHP remote code execution advisory | 21 Oct 2026, fixed in 8.5.12, 8.4.27, 8.3.36, 8.2.35 | update the server; daily version check (`inc/php-security.php`, `cron/check-php-version.php`); keep the version off the public health page |
| 23 | Credentials exposed | a key pair once committed to a public repository, others pasted in chat | env vars only, rotate, never echo (`docs/secret-rotation.md`) |

Unfixable in code (say so early to the owner): the API tier. At 45 calls a minute, with about 2.5 times that demand, roughly 80% of visitor pages and crawler pages for cold URLs had no data on 20 Oct 2026. The remaining levers are the 600permin tier, the bulk importer, and edge caching of public pages.

---

## 10. Tests to port (they are the acceptance checks)
All offline unless noted. Run them in CI on every pull request. Mutation-check new tests (remove the code, confirm the test fails).
- `test-tn-budget.php`: bucket maths, upper-half rule, crawler class bucket, burst, give-back, counters, stats summary, source-shape checks of the call sites.
- `test-tn-listing-copy.php`, `test-tn-snapshot.php`: stored copies, stale limits, empty-suspect rule, snapshot save and fallback.
- `test-feed-down.php`, `test-feed-order.php`: feed failure answers.
- `test-event-offer.php`: offers only with a price, lists skip unpriced events, each caller leaves them out.
- `test-jsonld-safe.php`: JSON-LD escaping.
- `test-db-retry.php` (needs a database): real killed connection, refused reconnects.
- `test-dead-performers.php`, `test-slugs.php`, `test-ratelimit.php`, `test-listing-filter.php`.
- `test-tn-bulk.php`: field mapping, modes, deletion rule.
- `test-php-patch.php`: PHP version rules.
- `check-env.php --production`: sandbox or placeholder settings fail before launch.
- A live checker you run after every deploy: crawl a sample of real URLs (hubs, 40 or more events, 2 pages of each other type), parse every JSON-LD block, assert each Event has a priced offer, and report any non-200 page. Pace it (one request every few seconds) and run it in the background with unbuffered output.

---

## 11. Build order for TicketScanner.ca
1. Credentials and config as env vars; its own website config id; environment safety check. Confirm the TicketNetwork tier.
2. The request layer: token store with device scopes, cache with stale-while-revalidate and grace, negative cache, per-family breaker, single-flight, live call with one fast retry.
3. The shared call budget with caller classes and crawler shares, and the counters and health page. Do this before any warmer or crawler traffic.
4. Fallbacks: stale limits, empty-suspect rule, page snapshots, 503 with Retry-After, event copies and `tn_event_snapshot`, `soDbRetry`.
5. Crawl guard and crawler classification. Leave crawler refusal off.
6. Event node and structured data with the price-only rule, then every ItemList.
7. Crons: listings, event details, snapshots, home lists, sitemaps, health check, backups.
8. Alerts and dashboards: Sentry fingerprints, the health decision table, the PHP version check.
9. Live checker script and CI tests. Verify on live after each deploy and report the numbers.
10. Bulk importer, after TicketNetwork grants the role.

## 12. Questions to send TicketNetwork for TicketScanner.ca
1. Our throttling tier today; move us to 600permin (reason: SEO crawl of a long tail, warmers, traffic growth). Is the limit per application, subscription, IP or website config, and is the window rolling or fixed?
2. Retry-After or quota headers on a 429.
3. Written rule for how long prices and availability may be cached or shown.
4. The `bulk` role and new production keys for `/events/bulk`.
5. Website config id (live and sandbox), hosted checkout host and DNS record, post-purchase redirect parameters, and a server-side order postback or webhook.
6. How commission is calculated and reported, and the treatment of cancellations and refunds.
7. Brand and wording rules (what we may call "guarantee", logos, "official").

## 13. Reference map (Seat Outlet repository)
`functions.php` (request layer, snapshots, event node), `inc/tn-budget.php` (budget, crawler classes, stats), `inc/tn-listing-copy.php` (stale limits, copies), `inc/tn-bulk.php`, `inc/dead-performers.php`, `inc/crawl-guard.php`, `inc/slugs.php`, `inc/seo-event.php`, `inc/seo-tickets.php`, `inc/entity-pages.php`, `inc/seo.php`, `inc/php-security.php`, `ajax/health.php`, `cron/*`, `db/migrations/0048_tn_event_snapshot.sql`, `0050_tn_bulk_events.sql`, `tools/test-*.php`, and the docs `docs/reliability.md`, `docs/ticketnetwork-api.md`, `docs/ticketscanner-port-brief.md`, `docs/production-cutover.md`, `docs/cloudflare-go-live-fixes.md`, `docs/security-php-2026-10.md`, `docs/secret-rotation.md`.
