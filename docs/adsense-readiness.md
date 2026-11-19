# AdSense readiness (low value content rejection, 18 Nov 2026)

Google AdSense rejected seatoutlet.com for "Low value content": substantial unique value, a consistent presence and genuine user interest. The notice names no page, so this file records what the data shows, what was changed, and what is still open. Everything under "Data" comes from two Search Console exports downloaded 18 Nov 2026 (Performance on Search, Coverage drilldown for "Server error (5xx)") and from requests made to the live site the same day.

## Data

| Item | Value |
|---|---|
| Search window in the export | 3 Oct to 15 Nov 2026 (44 days) |
| Clicks, impressions, CTR | 76 clicks, 7,561 impressions, 1.0% |
| Impressions by week | 812, 1,394, 4,753 (week of 17 Oct), then 280, 99, 154, 69 |
| Last 7 days | 14 to 38 impressions a day, 0 or 1 clicks |
| Branded queries in the top 1,000 | none |
| Blog pages in the top 1,000 pages | none |
| Pages reported "Server error (5xx)" | 384 (5 Oct), 2,035 (19 Oct), 10,247 (25 Oct), 11,582 (13 Nov) |
| First 1,000 of those URLs by type | 737 `/event/` (401 dated before 18 Nov, 331 old name-and-id form), 177 `/artist/`, about 85 other entity and hub pages |
| Live check of 41 of them, 18 Nov | every `/event/` sample (14 of 14) and 2 of 6 artist samples answered 503 after about 5 seconds; city, venue and hub samples answered 200 or 301 |

Reading: the site is about six weeks old in Google, traffic peaked in the week of 17 Oct and then collapsed while the 5xx count grew. A reviewer who crawls the site meets thousands of error pages, template pages for events that ended, and almost no editorial content. The impression collapse and the 5xx growth coincide; this file does not prove one caused the other.

## Changed (pull request from branch `claude/loving-sagan-9b8i2h`)

1. Event pages: an event whose remembered date is past redirects (302) to its performer page instead of waiting for TicketNetwork and answering 503. Over for more than 7 days: no TicketNetwork call at all. Test `tools/test-event-over-redirect.php`.
2. Artist pages: a performer with no upcoming event renders as the "no upcoming events" page (200, noindex) from what `event_redirects` remembered, instead of 503 when the lookup is refused. Test `tools/test-performer-redirect-standin.php`.
3. Ads: none on a 404, a 503, a feed-down page, a noindex page or an ads-off page, and a slot printed before the status was known is removed by the output filter. Test `tools/test-ad-suppress.php`.
4. Event pages whose record says no ticket is listed are noindex,follow. Test `tools/test-event-no-inventory.php`.

5. Event pages: the catalog copy now answers both the gate in `header.php` and the head in `inc/seo-event.php`, so an event the copy holds is a 200 with a named head and not a 503 whose body came from the copy. Test `tools/test-event-bulk-gate.php` (answered 503 before).

Follow-up (branch `claude/festive-mendel-ptp6xg`, after the live check of 18 Nov 2026):

6. Event pages: an old event id that TicketNetwork cannot be asked about, and that the fresh catalog copy does not hold, answers like a removed event (a redirect to what is remembered, else 404) instead of 503. Seen live: `/event/lenny-pearce-8048654` answered 503, then 404 a minute later. An id within 25,000 of the newest id in the copy may be an event created since the last pull, so it keeps the 503. Test `tools/test-event-bulk-gate.php`.
7. Event pages: the "other events in this city" list is read from the catalog copy when it is fresh, with no TicketNetwork call: one live round trip fewer on the cold pages that needed it. Test `tools/test-spec-city-bulk.php`.
8. Venue, city, county, state and country pages printed the date strip twice (seen live on `/city/boston-ma`); now once. Test `tools/test-date-strip.php`.
9. Listing rows: the "This weekend" pill now uses the same Friday to Sunday window as the "This weekend" filter (on a Sunday it labelled the next Saturday "This weekend"). Test `tools/test-date-pill.mjs`.
10. The Reddit profile stays in the Organization schema (`sameAs`) but is no longer a visible footer link (see "External links" below). Test `tools/test-seranking-nov12.php`.
11. Workflows: every action pinned to a commit SHA (`tools/test-actions-pinned.php`, Dependabot proposes updates), and the SFTP upload workflow `deploy.yml` retired (the firewall blocks GitHub runners; the server pulls `main`).
12. `deploy/list-stray-files.sh`: a read-only report of files in the web root that the repository does not ship, for the owner to review. It deletes nothing.

The sitemap already leaves out past events, dead performers, zero-result pages and (at crawl time) events without tickets, so no sitemap change was needed.

## Open: needs editorial work, not code

| Gap | Evidence | What closes it |
|---|---|---|
| No new editorial content in six weeks | the blog index links 10 posts, dated 4 to 6 Oct 2026, last modified 7 Oct 2026 | a steady schedule with real dates; the owner decides the cadence |
| Unique value on the pages that already get impressions | top pages below | a short, fact-checked guide on each, written by a person who can stand behind it |
| Author and editorial standards | no author name in the post schema | a named author or editor and a short editorial policy, written by the owner |

Pages and queries that already get impressions (Search Console, impressions in brackets), the best first targets for guides:

- Queries: horse racing tickets (33), music festivals tickets (31), coldplay tickets (26), katseye tickets (20), wembley tickets (20), ireland 6 nations tickets (17), lion king louisville ky (13), sevilla tickets (13), wnba finals tickets (13), how to buy event tickets online (10), how do i buy tickets online (9).
- Pages: `/upcoming-music-festivals` (107), `/artist/horse-racing` (106), `/venue/wembley-stadium` (92), `/artist/six-nations-tournament` (39) and its `/artist-city/` pages, `/artist/sevilla-fc` (55), `/artist/katseye` (126).

Rules for these guides: every venue, date, rule or price statement needs a primary source named in the post (the venue, the league or the promoter), no invented statistics or quotes, and nothing copied from another site. Resale pricing and refund statements must match `/worry-free-guarantee` and the terms page.

## Open: slow event pages (measured, not changed)

Live, 18 Nov 2026: a cold event page answers in 6.5 to 9.1 seconds (time to first byte, same on the second request for the three upcoming events tried), `Cache-Control: no-store`; one in three answered 503 after about 5.7 seconds. SE Ranking lists 164 pages as slow, mostly hubs (2.9 s on `/country-music-tickets`) and event pages dated today (4.8 s on one).

Where the time goes, from running a cold event page against a mock TicketNetwork API with `TN_PROFILE` (the mock cannot give real latency, only the call chain):

1. The event lookup in `header.php` (one call).
2. `inc/seo-event.php`: one parallel batch (the performer's other dates, the venue record, the venue's other events).
3. `soSpecCityEvents()`: one more call, only when the venue has nothing else on sale.
4. The menu's category tree (`inc/category-tree.php`, three calls in a row): cached six hours, so cold only after a cache expiry.

So a cold page waited for two to three TicketNetwork round trips in a row. Step 3 now reads the catalog copy when it is fresh (item 7 above), so it costs no call and no round trip; steps 1 and 2 stay live (the event itself and its prices must be live). Real latency per call, not the number of calls, sets the total. Options, each needing the owner's decision because they touch the call budget or caching of pages with live data: put the city fallback in the step 2 batch (one more call on some pages), cache event HTML for a short time at the edge (the seat map and prices load in the browser), or warm event pages the sitemap lists. The first stays within `docs/reliability.md` only if the call budget has room; none was started.

## Open: external links

All 343 "external links to 4XX" are the Reddit link in the footer (`https://www.reddit.com/user/SeatOutlet/`): Reddit answers 403 to crawlers, so SE Ranking cannot verify it. It is not a broken link for a visitor. Done: the profile stays in the Organization schema (`sameAs`, which no crawler follows as a link) and is no longer a visible link, so the warning goes. To show it again, remove `'link' => false` from its row in `inc/social.php`.

## Before requesting the review

1. The pull request is merged and deployed (`/ajax/health.php` shows the new commit). `bash deploy/verify-production.sh <commit>` checks steps 1, 3 and 4 and the #289 fixes in one run (read-only; add `HEALTH_TOKEN=...` for auto-migrate, pending migrations and the catalog copy).
2. In Search Console, validate the "Server error (5xx)" issue and wait for Google to recrawl. Do not request the review while the count is still in the thousands.
3. `curl -sI` three event URLs dated before today: expect 302, not 503. Spot-check `/artist/` pages for a performer with no upcoming events: expect 200 with `noindex`.
4. View source on a 404 and on an empty listing: no `adsbygoogle` slot.
5. Publish the first new guides and name the author. Then request one review. A request without a change gets the same answer.
6. If the rejection comes back with the same reason, the next lever is the volume of template pages versus editorial pages, not more fixes to the error pages. Decide then whether AdSense is worth the effort for a ticket marketplace whose revenue is ticket sales; ads on pages next to a purchase can lower conversion.
