# Keyword strategy from the real search list (4 Oct 2026)

Source: 564 unique real queries supplied by the owner. Metrics: SE Ranking, US, 4 Oct 2026 (volume per month, KD 0-100). Queries not in the tool were not given numbers.

## What the list says

| Cluster | Queries | Share | Where it lands on the site |
|---|---|---|---|
| Team tickets ("dodgers tickets") | 140 | 24% | Team pages (/artist/...), league pages |
| Team schedule / game today | 121 | 21% | Same team pages (title and H2 say "Schedule") |
| Team or league, other (playoffs, vs, UFC, WWE, Monster Jam, US Open) | 83 | 14% | League and sport pages |
| Artist or name only | 68 | 12% | Artist pages (/artist/...) |
| Head terms and "where to buy" | 55 | 9% | Hub pages |
| City and local ("concerts in dallas", "near me") | 38 | 6% | City pages, /city-events |
| Competitor brands (Ticketmaster, SeatGeek, Vivid Seats) | 23 | 4% | Not targeted (see below) |
| Venue (MSG, Fenway, SoFi, Opry) | 20 | 3% | Venue pages |
| FIFA World Cup 2026 | 16 | 2% | Not built (see below) |

About 59% of the list is sports. Team and artist pages are generated from live inventory, so one template change improves hundreds of pages.

## The honest constraint

The head terms are very hard for a new domain: concert tickets KD 88, nfl tickets 90, nba tickets 93, mlb tickets 93, events near me 98, phillies schedule 95. Realistic first wins are the lower-KD terms: theater tickets (880, KD 41), best place to buy concert tickets (180, KD 7), ticket promo code (760, KD 29), game day tickets (660, KD 38), mls tickets (6,600, KD 0). Head terms are still in titles, H1 and copy, but ranking for them will come from authority built through the long-tail pages below, not from one hub page.

## Per-page changes (this release)

| Page | Focus keyword (was) | New title | Secondary keywords and on-page additions |
|---|---|---|---|
| / | event tickets (buy event tickets) | Event Tickets—Concerts, Sports & Theater | where to buy event tickets (H2 on how-to page) |
| /concert-tickets-for-sale | concert tickets (concert tickets for sale) | Concert Tickets for Sale—{Y} Tours & Shows | best place to buy concert tickets (new H2), concert tickets near me (new H2 with genre links) |
| /game-day-tickets | sports tickets (game day tickets) | Sports Tickets—NFL, NBA, MLB & NHL Games | game day tickets (meta, copy), new H2 linking every league and sport page |
| /buy-broadway-tickets | theater tickets (buy broadway tickets) | Theater Tickets—Broadway Shows & Musicals | broadway show tickets for sale (new H2), comedy and Las Vegas shows links |
| /upcoming-music-festivals | upcoming music festivals (unchanged) | Festival Tickets for Sale—{Y} Lineups & Passes | festival tickets for sale (title and new H2). Kept the old focus keyword: the list term has volume 10 vs 540 |
| /city-events | city events (unchanged) | Events Near Me—Concerts, Sports & Shows | events near me today (new H2 and meta). Kept the focus keyword: "events near me" is KD 98 |
| /how-to-buy-tickets-online | how to buy tickets online | How to Buy Tickets Online—Where to Buy Safely | where to buy event tickets (new H2) |
| /tickets-promo-code | ticket promo code (tickets promo code) | Ticket Promo Code—Current Seat Outlet Offers | seatgeek promo code style searches are not targeted |
| /ticket-customer-service | unchanged | unchanged | New honest H2: not affiliated with Ticketmaster, SeatGeek or Vivid Seats, contact them for their orders |

Templates (apply to every generated page)

| Template | Change |
|---|---|
| Team and artist pages (performer.php) | Meta description now says "see the full {name} schedule / tour dates"; hero image alt "{name} tickets and schedule on Seat Outlet"; About photo alt includes "{name} tickets". Title was already "{Name} Tickets—{Y} Schedule & Prices" |
| League and sport pages (category.php) | Sports titles now "{League} Tickets—{Y} Schedule & Prices" (schedule queries are 21% of the list) |
| City pages (entity-listing.php) | Title now "{City} Events & Concerts—Tickets & Dates", matching "concerts in dallas", "las vegas events" |
| New clean pages | /soccer-tickets, /tennis-tickets, /racing-tickets, /boxing-tickets, /las-vegas-shows-tickets (real TicketNetwork categories, verified live). Old /category/... URLs 301 to them. Added to menu and sitemaps |

## Deliberately not done

- FIFA World Cup 2026 tickets: the tournament ended in July 2026, so a page would be stale. Revisit for the next major event.
- Competitor brand queries (seatgeek, ticketmaster, vivid seats, "customer service number"): navigational searches for another company. Pages built to capture them would mislead buyers and carry trademark risk. The single support note above is the only honest touchpoint. Any "Seat Outlet vs" comparison needs verified facts first.
- UFC, WWE, Monster Jam, Disney on Ice, Grand Ole Opry, Formula 1 pages: high volume, but the TicketNetwork category ids are not confirmed. Next step: confirm ids from the live API, then add them in inc/genre-pages.php (one line plus a stub file each).
- Name-only and misspelling queries (tickests, seetgeek, xx): not worth a page.

## Next steps that move revenue

1. Connect Search Console and submit /sitemap.php. After 2 to 4 weeks, rank by impressions per page and rewrite titles for pages with high impressions and low click-through.
2. Confirm the ids for the sports and show categories above and add pages.
3. Add a "Schedule" jump link and a visible schedule table on team pages for the "game today" searches.
4. Internal links: link each league page to its top teams (already driven by inventory) and each team page to its venue and city.
5. Re-run this analysis monthly using Search Console queries, which replace third-party estimates.
