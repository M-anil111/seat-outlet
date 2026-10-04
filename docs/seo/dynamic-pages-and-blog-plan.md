# Dynamic page keywords and blog plan (4 Oct 2026)

Metrics: SE Ranking, US, 4 Oct 2026. Volume is searches per month, KD is keyword difficulty 0 to 100. A few volumes are seasonal spikes (for example "texas rangers tickets" 96,200 in Aug to Oct, 1,900 the rest of the year), so check the trend before betting on a number.

## 1. Keyword pattern for every dynamic page type

One pattern per page type. The focus keyword drives the H1 strip, the title and the meta description.

| Page type (URL) | Focus keyword pattern | Title pattern | Why this phrase | Evidence |
|---|---|---|---|---|
| Artist or team (/artist/...) | {Name} tickets | {Name} Tickets—{Y} Schedule & Prices (sports) or Tour Dates (music) | Name plus "tickets" is the buying query. "Schedule" and "tour dates" are the other big query | yankees schedule 450,000 (KD 91), yankees tickets 47,500 (KD 93), morgan wallen tour dates 10,800 (KD 84) |
| Artist or team in a place (/artist-city/...) | {Name} tickets in {City} | {Name} Tickets in {City}—{Y} Dates | Unchanged, already matches local intent | n/a |
| Concerts in a city, state or country | concerts in {Place} | Concerts in {Place}—{Y} Tickets & Dates | "concerts in X" is searched far more than "concert tickets in X" | concerts in dallas 27,100, nashville 27,100, seattle 22,400, austin 18,100, los angeles 8,000 (KD 59) |
| Concerts at a venue | concerts at {Venue} | Concerts at {Venue}—Seat Outlet | Venue + concerts query | madison square garden concerts 610 (KD 43), sofi stadium concerts 440 (KD 8) |
| Sports in a city or state | sports events in {Place} | Sports Events in {Place}—{Y} Schedule & Tickets | Lowest KD of any local pattern | sporting events in dallas 480 (KD 9), near me 220 (KD 12) |
| Theater in a city or state | theater in {Place} | Theater in {Place}—{Y} Shows, Dates & Tickets | Matches how people search | theater in chicago 6,600 (KD 65), theatre in chicago 2,900 |
| Festivals in a place | music festivals in {Place} | Music Festivals in {Place}—{Y} Dates & Passes | Matches how people search | music festivals near me 4,400 (KD 63); state level terms are tiny (texas 50) so these pages rely on the hub |
| All events in a city or state (/city/..., /state/...) | events in {Place} | Events in {Place}—Concerts, Sports & Shows | Lowest KD of the broad city terms | events in dallas 9,900 (KD 67) |
| Venue (/venue/...) | {Venue} tickets | {Venue} Tickets—{City} Events & Seats | Unchanged. Added a seating chart FAQ (see below) | madison square garden seating chart 8,000, fenway park seating chart 6,600 (KD 53) |
| Event (/event/...) | {Event} tickets | {Event} Tickets—{Place}, {Date} | Unchanged | n/a |
| League or genre pages | {League} tickets | {League} Tickets—{Y} Schedule & Prices | Done in the previous release | mlb tickets 368,000 (KD 93) |

Implemented in this release: the category in a place pages (concerts, sports, theater, festivals, events in a city, state or country or at a venue), the all events city and state pages, the venue focus keyword and seating chart FAQ, and a schedule FAQ on team pages.

Not changed on purpose: event pages, artist pages and artist in a place pages already use the right pattern.

### Venue seating chart searches

Seating chart searches are large (Madison Square Garden 8,000, Fenway Park 6,600, Yankee Stadium 4,400, SoFi Stadium 2,900) and the difficulty is moderate (KD 53 to 65). We do not own venue seating charts, only the live seat map on each event page. So the venue page now has an FAQ "Where can I see the {Venue} seating chart?" that sends visitors to an event seat map. It is not in the title, so we do not promise something the page does not show. If you want to win these searches, the right asset is a sourced seating guide per top venue (see blog list).

### Team schedule searches

"{Team} schedule" and "{team} game today" are the biggest team queries (phillies schedule 673,000, yankees game today 246,000) but KD is 91 to 95, which a new domain will not win soon. Team pages now carry a schedule FAQ listing the next three games with absolute dates. It deliberately says nothing like "today", because pages are cached and a wrong "game today" answer would hurt trust.

## 2. Keywords to add (not built yet)

| Keyword | Volume | KD | Where it goes |
|---|---|---|---|
| tickets near me | 2,400 | 70 | /city-events copy (already has events near me) |
| concerts this weekend | 12,100 | 83 | A "this weekend" filter chip on city pages plus a heading |
| concerts tonight | 6,600 | 67 | Same, "tonight" chip |
| cheap concerts near me | 590 | 32 | Ticket deals page H2 |
| music festivals 2026 | 20,100 | 78 | Festival hub H2 (seasonal, rising) |
| sports events near me | 220 | 12 | Sports hub H2 |
| theater shows near me | 910 | 41 | Theater hub H2 |
| ticket resale sites | 5,400 | 74 | Pillar blog post (below) |

## 3. Blog plan

Existing posts (7): ticket scams, upcoming concert tours, Taylor Swift tour dates, kids events in Austin, best concerts in NYC, things to do in NYC in December, best concert venues in the US.

Priority order is conversion intent first, then low difficulty. Every post links to the matching hub, city or team page and ends on a buying step.

### Tier 1: low difficulty, buying intent (write these first)

| Post | Focus keyword | Volume | KD | Links to |
|---|---|---|---|---|
| Best site to buy tickets: what to compare before you pay | best site to buy tickets | 590 | 7 | /worry-free-guarantee, /buy-tickets-online |
| How to get presale tickets | how to get presale tickets | 590 | 14 | /concert-tickets-for-sale |
| Best seats at a concert, by section | best seats at a concert | 320 | 10 | /concert-tickets-for-sale |
| Best seats for Broadway shows | best seats for broadway shows | 320 | 21 | /buy-broadway-tickets |
| How to buy concert tickets: when, where and how to avoid fees | how to buy concert tickets | 260 | 10 | /how-to-buy-tickets-online |
| Are tickets refundable? What happens if an event is canceled | are tickets refundable | 170 | 7 | /ticket-buyer-protection |
| What are obstructed view seats | what are obstructed view seats | 140 | 10 | /game-day-tickets |
| Ticket presale codes explained | ticket presale code | 110 | 10 | Presale post above |

Combined volume is small (about 2,000 a month) but these are buyers, the competition is weak, and they support the trust pages.

### Tier 2: bigger volume, event specific (needs sourced dates)

| Post | Focus keyword | Volume | KD | Note |
|---|---|---|---|---|
| World Series tickets | world series tickets | 1,300 | 64 | Timely now. Needs verified schedule from the league |
| Coachella tickets | coachella tickets | 810 | 31 | Lowest KD in the group |
| Stanley Cup tickets | stanley cup tickets | 270 | 20 | Needs verified dates |
| Hamilton tickets NYC | hamilton tickets nyc | 8,000 | 65 | Link to the Hamilton page if inventory exists |
| Masters tickets | masters tickets | 6,600 | 63 | Check how resale of this event works before writing |
| Kentucky Derby tickets | kentucky derby tickets | 6,600 | 60 | Seasonal |
| Super Bowl tickets | super bowl tickets | 3,200 | 73 | Seasonal |
| Ticket resale sites explained | ticket resale sites | 5,400 | 74 | Pillar. Needs verified facts, no competitor claims we cannot support |

### Tier 3: city and venue guides

| Post | Focus keyword | Volume | KD |
|---|---|---|---|
| Concerts in Los Angeles | concerts in los angeles | 8,000 | 59 |
| Concerts in San Diego | concerts in san diego | 8,000 | 64 |
| Concerts in Kansas City | concerts in kansas city | 9,900 | 64 |
| Concerts in Detroit | concerts in detroit | 5,400 | 60 |
| Concerts in Columbus | concerts in columbus | 3,600 | 59 |
| Things to do in Dallas this weekend | things to do in dallas this weekend | 9,900 | 69 |
| Fenway Park seating chart guide | fenway park seating chart | 6,600 | 53 |
| Yankee Stadium seating chart guide | yankee stadium seating chart | 4,400 | 55 |
| SoFi Stadium seating chart guide | sofi stadium seating chart | 2,900 | 55 |

Venue guides must be written from the venue's own published information and cited. Do not publish parking, section or price claims from memory.

## 4. Rules for every new post

1. Only facts we can cite. Dates, rules, prices and venue details come from the official source.
2. One focus keyword, one H1, meta title under 59 characters with the brand, description 120 to 155 characters.
3. Link up to the right hub or team page, and down to one buying step.
4. Mark anything that needs outside review before publishing (legal, refunds, resale rules).
