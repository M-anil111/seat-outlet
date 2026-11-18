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

## Before requesting the review

1. The pull request is merged and deployed (`/ajax/health.php` shows the new commit).
2. In Search Console, validate the "Server error (5xx)" issue and wait for Google to recrawl. Do not request the review while the count is still in the thousands.
3. `curl -sI` three event URLs dated before today: expect 302, not 503. Spot-check `/artist/` pages for a performer with no upcoming events: expect 200 with `noindex`.
4. View source on a 404 and on an empty listing: no `adsbygoogle` slot.
5. Publish the first new guides and name the author. Then request one review. A request without a change gets the same answer.
6. If the rejection comes back with the same reason, the next lever is the volume of template pages versus editorial pages, not more fixes to the error pages. Decide then whether AdSense is worth the effort for a ticket marketplace whose revenue is ticket sales; ads on pages next to a purchase can lower conversion.
