# Keyword master list: low difficulty, buying intent first (US)

Source: SE Ranking, Google US database (`source=us`), queried 2026-10-04 with `getKeywordsMetrics` (bulk), `getRelatedKeywords` and `getKeywordQuestions`. Every volume, KD and CPC below is a number the tool returned. Nothing is estimated.

How the list was built and filtered:
- Volume is average monthly US searches (the latest figure SE Ranking returned). KD is keyword difficulty 0-100. CPC is in USD where the tool returned one ("0" means the tool returned 0).
- Kept: volume 200 or more and KD 30 or less. A few rows with KD 31-37 are kept and labelled "stretch" because they are strong buying terms.
- Left out on purpose: head terms (KD 60+), competitor brand navigation terms (tickpick, gametime, seatgeek promo code), terms about selling tickets or paying traffic tickets, and anything the tool returned under 200.
- Intent is my classification (SE Ranking labels almost all of these "I"). Funnel: Bottom = ready to buy or compare sellers, Middle = comparing or checking trust, Top = learning.
- Priority: 1 = buying or trust intent, KD 20 or less, volume 300 or more, or a seasonal window open now. 2 = KD 21-30 or smaller volume. 3 = informational, stretch or seasonal later.
- Seat Outlet compares listings and does not sell tickets. Every mapped page must keep that wording, and competitor posts (refund policy, "is X legit") must stick to what is publicly documented, with no accusations.

Already owned, so not proposed again (existing targets in `docs/seo/top-15-pages.md`, `inc/seo-keywords.php` and migrations 0040-0041): mls tickets, soccer game tickets, how to buy broadway tickets, broadway shows list, how to buy tickets online, verified resale tickets, ticket promo code, comedy show tickets, latin concerts, ticket scams, christmas shows near me (new page planned), what is general admission, best site to buy tickets, how to get presale tickets, ticket price tracker. Blog slugs already in the repo: `ticket-buying-guide`, `what-is-general-admission`, `best-seats-at-a-concert`, `orchestra-vs-mezzanine`, `how-to-get-presale-tickets`, `ticket-price-tracker`, `best-site-to-buy-tickets`, `are-resale-tickets-legit`, `ticket-fees-explained`, `how-to-avoid-ticket-scams`, `upcoming-concert-tours`, `taylor-swift-tour-dates`, `kids-events-in-austin`, `best-concerts-in-nyc`, `things-to-do-in-nyc-in-december`, `best-concert-venues-in-the-us`. Rows that point to these are extension work (a section, FAQ or internal link), not new pages.

## Master table

Mapped page legend: a path starting with `/` that exists today is an existing page. "NEW POST" gives slug, intent and suggested H1. "NEW LANDING" gives the proposed path.

| # | Keyword | Volume | KD | CPC | Intent | Funnel | Mapped page or post | Pri | Note |
|---|---|---|---|---|---|---|---|---|---|
| **A. Buying, cheap and near me** | | | | | | | | | |
| 1 | concert tickets near me | 880 | 15 | $0.10 | Local/transactional | Bottom | `/concert-tickets-for-sale` (add a "near you" block fed by the city feed) | 1 | Volume is rising fast (80 in Apr to 880 now). |
| 2 | where to buy cheap concert tickets | 590 | 6 | $0.77 | Transactional | Bottom | NEW POST `/blog/where-to-buy-cheap-concert-tickets`. Intent: compare sellers and ways to pay less. H1: Where to Buy Cheap Concert Tickets and How to Pay Less | 1 | KD 6 with commercial CPC. Link to `/ticket-deals` and `/tickets-promo-code`. |
| 3 | concert tickets cheap no fees | 480 | 27 | $1.02 | Transactional | Bottom | NEW POST `/blog/concert-tickets-no-fees`. Intent: what "no fees" or "all-in" really means. H1: Concert Tickets With No Fees: What It Means and What to Check | 2 | Do not promise no fees. Explain all-in pricing; link `/blog/ticket-fees-explained`. |
| 4 | event tickets near me | 390 | 23 | $0.21 | Local/transactional | Bottom | `/city-events` | 2 | Add a "tickets near me" H2 and geolocation prompt. |
| 5 | sports tickets near me | 260 | 19 | $0.31 | Local/transactional | Bottom | `/game-day-tickets` | 2 | Quick win: one H2 plus 2 FAQs. |
| 6 | comedy tickets near me | 320 | 26 | $0.24 | Local/transactional | Bottom | `/comedy-show-tickets` | 2 | Quick win next to "stand up comedy near me". |
| 7 | wwe tickets near me | 480 | 26 | $0.12 | Local/transactional | Bottom | NEW LANDING `/wwe-tickets` | 2 | Parent term "wwe tickets" is KD 87, so win the near-me and cheap variants first. |
| 8 | rodeo tickets near me | 390 | 8 | $0.54 | Local/transactional | Bottom | NEW LANDING `/rodeo-tickets` | 1 | KD 8. Volume climbs from Jul to Oct. |
| 9 | cheap rodeo tickets | 320 | 28 | $0.50 | Transactional | Bottom | `/rodeo-tickets` (same page) | 2 | Secondary keyword of row 8. |
| 10 | cheap disney on ice tickets | 480 | 6 | $0.88 | Transactional | Bottom | NEW LANDING `/disney-on-ice-tickets` | 1 | KD 6. Parent term is KD 84, so lead with the cheap angle. Family and holiday season. |
| 11 | tickets for today | 220 | 0 | $0.71 | Transactional | Bottom | NEW LANDING `/tickets-today` (same-day and tonight events) | 2 | KD 0. "tickets for tonight" (260 / KD 37) is the stretch variant, see row 12. |
| 12 | tickets for tonight | 260 | 37 | $0.56 | Transactional | Bottom | `/tickets-today` | 3 | Stretch (KD 37). Secondary keyword. |
| 13 | shows this weekend | 260 | 7 | $0.17 | Local/commercial | Middle | `/city-events` or NEW LANDING `/events-this-weekend` | 2 | KD 7 but volume dips seasonally. |
| 14 | cheap vegas shows | 480 | 29 | $0.54 | Transactional | Bottom | NEW LANDING `/las-vegas-show-tickets` | 2 | Cluster with rows 15-16. |
| 15 | cheap las vegas show tickets | 260 | 28 | $0.74 | Transactional | Bottom | `/las-vegas-show-tickets` | 2 | CPC 0.74 shows buyer intent. |
| 16 | discount vegas shows | 390 | 34 | $0.82 | Transactional | Bottom | `/las-vegas-show-tickets` | 3 | Stretch (KD 34). |
| 17 | cheap wicked tickets | 390 | 21 | $0.74 | Transactional | Bottom | NEW LANDING `/wicked-tickets` | 2 | Volume dipped to 170 in Aug, now 390. Parent "wicked tickets" is KD 85. |
| 18 | cheap opera tickets | 390 | 19 | $0.34 | Transactional | Bottom | NEW LANDING `/opera-tickets` | 2 | Parent "opera tickets" is KD 65. |
| 19 | cheap soccer tickets | 210 | 15 | $0.84 | Transactional | Bottom | `/soccer-tickets` | 2 | Already a secondary keyword there. Add an H2. |
| 20 | cheap baseball tickets | 290 | 29 | $1.04 | Transactional | Bottom | `/game-day-tickets` (or NEW LANDING `/mlb-tickets` later) | 2 | CPC above 1. |
| 21 | cheap nascar tickets | 210 | 21 | $0.45 | Transactional | Bottom | NEW LANDING `/nascar-tickets` | 3 | Parent "nascar tickets" is KD 85. |
| 22 | cheap nhl tickets | 480 | 34 | $0.67 | Transactional | Bottom | `/nhl-teams` or NEW LANDING `/nhl-tickets` | 3 | Stretch (KD 34). Hockey season starts now. |
| 23 | cheap theater tickets | 390 | 35 | $0.48 | Transactional | Bottom | `/buy-broadway-tickets` | 3 | Stretch (KD 35). |
| 24 | broadway tickets cheap | 210 | 27 | $0.64 | Transactional | Bottom | `/buy-broadway-tickets` | 2 | Add to existing cheap-tickets H2. |
| 25 | where to find cheap broadway tickets | 590 | 14 | $0.53 | Transactional | Bottom | `/buy-broadway-tickets` | 1 | Quick win: question H2 with a short answer. |
| 26 | discount tickets to broadway shows | 480 | 26 | $0.75 | Transactional | Bottom | `/buy-broadway-tickets` | 2 | Same section as row 25. |
| 27 | how to get cheap broadway tickets nyc | 480 | 25 | $0.34 | Local/transactional | Bottom | `/buy-broadway-tickets` | 2 | Same section as row 25. |
| 28 | cheap tickets nyc | 480 | 30 | $0.53 | Transactional | Bottom | NEW LANDING `/nyc-tickets` | 2 | Pairs with the NYC concert page (row 49). |
| 29 | broadway resale tickets | 480 | 30 | $0.68 | Transactional | Bottom | `/buy-broadway-tickets` | 2 | Resale angle fits the marketplace. Mention guarantee wording from `inc/guarantee.php`. |
| 30 | broadway discount codes | 480 | 7 | $0.90 | Transactional | Bottom | `/tickets-promo-code` (add Broadway H2) | 1 | KD 7. Do not invent codes. |
| 31 | broadway promo codes | 480 | 13 | $1.12 | Transactional | Bottom | `/tickets-promo-code` | 1 | Same section as row 30. |
| 32 | broadway tickets promo code | 260 | 9 | $1.03 | Transactional | Bottom | `/tickets-promo-code` | 2 | Same section as row 30. |
| 33 | mj the musical tickets | 720 | 26 | $0.64 | Transactional | Bottom | NEW LANDING `/mj-the-musical-tickets` | 2 | Volume swings (4,400 in Sep, 720 now). Show page template. |
| 34 | the outsiders tickets | 220 | 17 | $0.77 | Transactional | Bottom | NEW LANDING `/the-outsiders-tickets` | 3 | Low volume, only if a show feed exists. |
| 35 | broadway in dallas | 250 | 18 | $0.19 | Local/navigational | Middle | NEW LANDING `/broadway-in-dallas` | 3 | Series-style query. Check the feed has Dallas venues. |
| 36 | drag show tickets | 390 | 30 | $0 | Transactional | Bottom | NEW LANDING `/drag-show-tickets` | 3 | Genre page. CPC 0. |
| 37 | nutcracker tickets near me | 320 | 20 | $0.93 | Local/transactional | Bottom | `/christmas-shows-near-me` | 1 | Seasonal. Add before November. |
| 38 | christmas concert tickets | 390 | 8 | $0 | Transactional | Bottom | `/christmas-shows-near-me` | 1 | KD 8. Seasonal. |
| **B. Concert city and venue pages** | | | | | | | | | |
| 39 | red rocks amphitheatre tickets | 610 | 29 | $0.59 | Local/transactional | Bottom | NEW LANDING `/red-rocks-tickets` | 2 | "red rocks tickets" is row 40. Strong summer spikes. |
| 40 | red rocks tickets | 390 | 26 | $0.92 | Transactional | Bottom | `/red-rocks-tickets` | 2 | The last 12 months show a summer spike to 60,500. Plan the page before May. |
| 41 | metlife stadium tickets | 610 | 30 | $0.15 | Local/transactional | Bottom | NEW LANDING `/metlife-stadium-tickets` | 3 | KD 30 edge. |
| 42 | q2 stadium tickets | 260 | 20 | $0.27 | Local/transactional | Bottom | NEW LANDING `/q2-stadium-tickets` | 3 | Austin FC home. Ties to `/mls-tickets`. |
| 43 | chase center tickets | 480 | 31 | $0.64 | Local/transactional | Bottom | NEW LANDING `/chase-center-tickets` | 3 | Stretch (KD 31). |
| 44 | sofi stadium tickets | 470 | 32 | $0.43 | Local/transactional | Bottom | NEW LANDING `/sofi-stadium-tickets` | 3 | Stretch (KD 32). |
| 45 | nyc concert tickets | 590 | 19 | $0.23 | Transactional | Bottom | NEW LANDING `/nyc-concert-tickets` | 1 | Variants with the same volume: "concert tickets new york city" (590 / KD 21), "new york concert ticket" (590 / KD 21), "tickets concerts new york" (590 / KD 26). One page. |
| 46 | concert tickets new york city | 590 | 21 | $0.23 | Transactional | Bottom | `/nyc-concert-tickets` | 1 | Secondary keyword of row 45. |
| 47 | boston concert tickets | 590 | 21 | $0.16 | Transactional | Bottom | NEW LANDING `/boston-concert-tickets` | 1 | City template page. |
| 48 | atlanta concert tickets | 390 | 19 | $0.11 | Transactional | Bottom | NEW LANDING `/atlanta-concert-tickets` | 1 | City template page. |
| 49 | dallas concert tickets | 390 | 23 | $0.17 | Transactional | Bottom | NEW LANDING `/dallas-concert-tickets` | 2 | City template page. |
| 50 | philadelphia concert tickets | 320 | 26 | $0.12 | Transactional | Bottom | NEW LANDING `/philadelphia-concert-tickets` | 2 | City template page. |
| 51 | seattle concert tickets | 260 | 23 | $0.14 | Transactional | Bottom | NEW LANDING `/seattle-concert-tickets` | 2 | City template page. |
| 52 | phoenix concert tickets | 260 | 23 | $0.17 | Transactional | Bottom | NEW LANDING `/phoenix-concert-tickets` | 2 | City template page. |
| 53 | denver concert tickets | 260 | 16 | $0.22 | Transactional | Bottom | NEW LANDING `/denver-concert-tickets` | 2 | City template page. Links to Red Rocks page. |
| 54 | san diego concert tickets | 210 | 22 | $0.19 | Transactional | Bottom | NEW LANDING `/san-diego-concert-tickets` | 3 | City template page. |
| 55 | los angeles concert tickets | 480 | 31 | $0.21 | Transactional | Bottom | NEW LANDING `/los-angeles-concert-tickets` | 3 | Stretch (KD 31). |
| **C. Tour dates, schedules, genres** | | | | | | | | | |
| 56 | concert schedule | 810 | 24 | $0.21 | Informational/commercial | Middle | `/blog/upcoming-concert-tours` (extend) or NEW LANDING `/concert-schedule` | 2 | Evergreen and rising (590 to 810). Needs a live feed to beat news pages. |
| 57 | concert lineup | 590 | 7 | $0.03 | Informational | Top | `/concert-schedule` | 3 | KD 7 but low buying intent. |
| 58 | concert events | 590 | 23 | $0.10 | Local/commercial | Middle | `/concert-schedule` | 3 | Variant cluster ("concerts and events", "concerts events"). |
| 59 | concerts ny | 920 | 23 | $0.06 | Informational | Middle | `/nyc-concert-tickets` | 2 | Fold into the NYC page. |
| 60 | big concerts | 920 | 17 | $0.04 | Informational | Top | `/concert-schedule` (arena and stadium filter) | 3 | Variants: "big concert" (720 / KD 21). |
| 61 | concerts tomorrow | 740 | 21 | $0.07 | Informational | Middle | NEW LANDING `/concerts-today-and-tomorrow` | 2 | Daily-updating feed page. |
| 62 | concerts happening today | 590 | 20 | $0.03 | Informational | Middle | `/concerts-today-and-tomorrow` | 2 | Same page as row 61. |
| 63 | what concert is tonight near me | 590 | 20 | $0.03 | Local/commercial | Middle | `/concerts-today-and-tomorrow` | 2 | Same page as row 61. |
| 64 | concerts in nyc today | 720 | 24 | $0.09 | Informational | Middle | `/nyc-concert-tickets` | 2 | Add a "today" block to the NYC page. |
| 65 | concerts this month | 590 | 19 | $0.04 | Informational | Middle | NEW LANDING `/concerts-this-month` | 2 | Auto-updating title with month name. |
| 66 | music events this weekend | 590 | 21 | $0.16 | Informational | Middle | `/events-this-weekend` | 2 | See row 13. |
| 67 | concert in october | 880 | 21 | $0.02 | Informational | Middle | NEW POST `/blog/concerts-in-october`. Intent: what is on this month and where to buy. H1: Concerts in October: Tours and Shows Worth Buying Tickets For | 1 | Window is open now (today is 4 Oct). Variants: "concerts october" (660 / KD 17), "concert october" (610 / KD 24), "concerts in october" (410 / KD 26). |
| 68 | concerts in november | 390 | 25 | $0.02 | Informational | Middle | NEW POST `/blog/concerts-in-november`. H1: Concerts in November: Tours and Shows to Know | 1 | Publish by mid October. |
| 69 | concerts in december | 390 | 26 | $0.02 | Informational | Middle | NEW POST `/blog/concerts-in-december`. H1: Concerts in December: Holiday Shows and Year-End Tours | 2 | Pair with `/christmas-shows-near-me`. |
| 70 | concerts in february | 480 | 26 | $0.04 | Informational | Middle | NEW POST `/blog/concerts-in-february` | 3 | Publish in December. |
| 71 | concerts in april | 390 | 24 | $0 | Informational | Middle | NEW POST `/blog/concerts-in-april` | 3 | Publish in February. |
| 72 | concerts in august | 390 | 24 | $0.02 | Informational | Middle | NEW POST `/blog/concerts-in-august` | 3 | Publish in June. |
| 73 | concerts in january | 210 | 22 | $0.02 | Informational | Middle | NEW POST `/blog/concerts-in-january` | 3 | Publish in November. |
| 74 | concerts in june | 260 | 6 | $0 | Informational | Middle | NEW POST `/blog/concerts-in-june` | 3 | Publish in April. |
| 75 | tour announcements | 410 | 28 | $0 | Informational | Top | `/blog/upcoming-concert-tours` (extend) | 2 | Volume peaked at 810 in Jul. |
| 76 | just announced concerts | 480 | 16 | $0 | Informational | Top | `/blog/upcoming-concert-tours` (add a "just announced" section) | 2 | Variant: "just announced tours" (210 / KD 17). |
| 77 | reunion tours | 320 | 9 | $0.18 | Informational | Top | NEW POST `/blog/reunion-tours`. Intent: list of announced reunion tours and how to get tickets. H1: Reunion Tours and How to Get Tickets | 2 | KD 9. Needs a maintained list. |
| 78 | artist going on tour | 590 | 30 | $0.03 | Informational | Top | `/blog/upcoming-concert-tours` | 3 | KD 30 edge. |
| 79 | edm concerts near me | 740 | 9 | $0.04 | Local | Middle | NEW LANDING `/edm-concerts` | 1 | KD 9. |
| 80 | kpop concerts near me | 810 | 12 | $0.06 | Local | Middle | NEW LANDING `/kpop-concerts` | 1 | Volume jumped from 220 to 810 this month. |
| 81 | indie concerts near me | 480 | 12 | $0.04 | Local | Middle | NEW LANDING `/indie-concerts` | 2 | Steady 480. |
| 82 | jazz concerts near me | 720 | 23 | $0.26 | Local/commercial | Middle | NEW LANDING `/jazz-concerts` | 2 | Volume swings 270 to 990. |
| 83 | rap concerts near me | 720 | 6 | $0.03 | Local | Middle | NEW LANDING `/rap-concerts` | 1 | KD 6. |
| 84 | live shows near me | 720 | 6 | $0.29 | Local/commercial | Middle | `/city-events` | 1 | KD 6. Add to the page's H2 set. |
| 85 | live bands tonight | 590 | 22 | $0.24 | Local/commercial | Middle | `/concerts-today-and-tomorrow` | 3 | Music venue intent, weak ticket fit. |
| 86 | christian concert tickets | 320 | 11 | $0.46 | Transactional | Bottom | NEW LANDING `/christian-concerts` | 2 | Genre page. "christian concerts near me" is KD 77, so avoid. |
| 87 | r&b concert tickets | 390 | 14 | $0.21 | Transactional | Bottom | NEW LANDING `/rnb-concerts` | 2 | Genre page. |
| 88 | rock concert tickets | 210 | 17 | $0.07 | Transactional | Bottom | NEW LANDING `/rock-concerts` | 3 | Genre page. |
| 89 | rap concert tickets | 210 | 19 | $0.03 | Transactional | Bottom | `/rap-concerts` | 3 | Secondary keyword of row 83. |
| 90 | festival tickets | 760 | 19 | $0.59 | Transactional | Bottom | `/upcoming-music-festivals` | 1 | Quick win: not yet a secondary keyword there. |
| **D. Sports events (seasonal)** | | | | | | | | | |
| 91 | college football tickets | 920 | 18 | $0.43 | Transactional | Bottom | NEW LANDING `/college-football-tickets` | 1 | Season is on now. Volume was 47,500 in Dec, so expect a bowl and playoff surge. |
| 92 | spring training tickets | 810 | 19 | $0.77 | Transactional | Bottom | NEW LANDING `/spring-training-tickets` | 2 | Peak Jan-Mar. Publish by December. |
| 93 | grapefruit league tickets | 590 | 23 | $0 | Transactional | Bottom | `/spring-training-tickets` | 2 | Secondary keyword. Volume rising (140 to 590). |
| 94 | nfr tickets | 880 | 15 | $0.62 | Transactional | Bottom | NEW LANDING `/nfr-tickets` (can share `/rodeo-tickets`) | 2 | Event in early December. Variant: "national finals rodeo tickets" (20 / KD 16). |
| 95 | nba all star tickets | 810 | 26 | $0.20 | Transactional | Bottom | NEW LANDING `/nba-all-star-tickets` | 2 | Peak Feb. |
| 96 | pga championship tickets | 810 | 27 | $0.48 | Transactional | Bottom | NEW LANDING `/pga-championship-tickets` | 3 | Peak around May. |
| 97 | nfl draft tickets | 720 | 29 | $0 | Transactional | Bottom | NEW LANDING `/nfl-draft-tickets` | 3 | Peak Apr. Often free entry, so check the angle. |
| 98 | nfl playoff tickets | 470 | 30 | $0.50 | Transactional | Bottom | NEW LANDING `/nfl-playoff-tickets` | 2 | Publish by December. KD 30 edge. |
| 99 | ufl tickets | 470 | 19 | $0.24 | Transactional | Bottom | NEW LANDING `/ufl-tickets` | 3 | Spring league. |
| 100 | preakness tickets | 390 | 17 | $0.25 | Transactional | Bottom | NEW LANDING `/preakness-tickets` | 3 | Peak May. |
| 101 | waste management open tickets | 330 | 12 | $0.42 | Transactional | Bottom | NEW LANDING `/waste-management-phoenix-open-tickets` | 3 | Peak Feb. |
| 102 | lacrosse tickets | 320 | 13 | $0.77 | Transactional | Bottom | NEW LANDING `/lacrosse-tickets` | 3 | CPC 0.77 is good for the volume. |
| 103 | wnba finals tickets | 270 | 19 | $0.41 | Transactional | Bottom | `/game-day-tickets` (section) | 3 | Seasonal. |
| 104 | mls playoff tickets | 260 | 26 | $0.28 | Transactional | Bottom | `/mls-tickets` | 2 | Already a secondary keyword. Do the H2. |
| **E. Fees, refunds, trust, resale** | | | | | | | | | |
| 105 | ticket refund | 810 | 13 | $0.90 | Informational/trust | Middle | NEW POST `/blog/how-to-get-a-ticket-refund`. Intent: when refunds are allowed and what to do. H1: How to Get a Ticket Refund: Cancelled, Postponed and Resale Orders | 1 | Volume rose from 210 to 810 in 2 months. Currently only a secondary on `/ticket-buyer-protection`. Link to `/worry-free-guarantee`. |
| 106 | legit ticket sites | 320 | 0 | $1.18 | Commercial/trust | Middle | `/ticket-buyer-protection` | 1 | KD 0. Add a checklist H2. |
| 107 | fake tickets | 720 | 18 | $1.06 | Informational/trust | Middle | `/blog/how-to-avoid-ticket-scams` (rewrite) | 1 | Already a secondary there. Make "fake tickets" an H2. |
| 108 | is ticketmaster legit | 810 | 12 | $3.40 | Informational/trust | Middle | NEW POST `/blog/is-ticketmaster-legit`. Intent: facts on primary vs resale sellers. H1: Is Ticketmaster Legit? Primary Sales, Resale and What to Check | 1 | High CPC (3.40). Stay neutral and factual. |
| 109 | is ticketnetwork legit | 260 | 14 | $3.46 | Informational/trust | Middle | `/worry-free-guarantee` (add FAQ) | 1 | Seat Outlet orders are fulfilled through TicketNetwork, so own this answer. Only state what is documented in `inc/guarantee.php`. |
| 110 | ticketmaster vs stubhub | 590 | 7 | $5.41 | Commercial | Middle | NEW POST `/blog/ticketmaster-vs-stubhub`. Intent: comparison. H1: Ticketmaster vs StubHub: Fees, Refunds and Seat Choice Compared | 1 | KD 7 and CPC 5.41. Position comparison sites as the third option. |
| 111 | stubhub alternatives | 260 | 22 | $1.52 | Commercial | Middle | NEW POST `/blog/stubhub-alternatives`. H1: StubHub Alternatives: Where Else to Compare and Buy Tickets | 1 | Singular "stubhub alternative" is 260 / KD 17. One post. |
| 112 | ticketmaster alternatives | 320 | 15 | $1.55 | Commercial | Middle | `/blog/ticketmaster-alternatives` (or fold into row 111 post) | 2 | Singular "ticketmaster alternative" is 320 / KD 27. |
| 113 | ticketmaster refund policy | 590 | 7 | $0 | Informational | Middle | `/blog/how-to-get-a-ticket-refund` (section) | 2 | Add a section, not a separate post. |
| 114 | seatgeek refund | 590 | 12 | $0.01 | Informational | Middle | `/blog/how-to-get-a-ticket-refund` | 2 | Volume jumped from 20 to 590 in Aug. |
| 115 | stubhub refund | 660 | 28 | $0.42 | Informational | Middle | `/blog/how-to-get-a-ticket-refund` | 2 | Same post, own H2. |
| 116 | axs refund | 390 | 14 | $0 | Informational | Middle | `/blog/how-to-get-a-ticket-refund` | 3 | Same post, own H2. |
| 117 | vivid seats refund | 210 | 29 | $2.21 | Informational | Middle | `/blog/how-to-get-a-ticket-refund` | 3 | Same post, own H2. |
| 118 | ticket fees | 480 | 7 | $2.16 | Informational | Middle | `/blog/ticket-fees-explained` | 1 | KD 7, CPC 2.16. Existing post: check title and H1 use the exact phrase. |
| 119 | seatgeek fees | 320 | 20 | $2.55 | Informational | Middle | `/blog/ticket-fees-explained` (add H2) | 2 | Add factual fee comparison only if sourced. |
| 120 | ticketmaster fees | 590 | 33 | $1.76 | Informational | Middle | `/blog/ticket-fees-explained` | 3 | Stretch (KD 33). |
| 121 | dynamic pricing tickets | 320 | 22 | $0 | Informational | Top | NEW POST `/blog/dynamic-pricing-tickets`. H1: Dynamic Pricing for Tickets: Why Prices Change and How to Buy Smarter | 2 | Volume rose from 50 to 320 in 4 months. |
| 122 | face value tickets | 480 | 20 | $1.14 | Informational | Middle | NEW POST `/blog/face-value-tickets`. H1: Face Value Tickets: What They Are and When Resale Costs More | 1 | CPC 1.14. Steady 480. |
| 123 | ticketmaster platinum tickets | 480 | 6 | $3.58 | Informational | Middle | NEW POST `/blog/ticketmaster-platinum-tickets`. H1: What Are Ticketmaster Platinum Tickets and How Do They Compare With Resale? | 1 | KD 6, CPC 3.58. Variant: "ticketmaster official platinum" (480 / KD 6). |
| 124 | ticketmaster queue | 290 | 25 | $0.57 | Informational | Top | `/blog/how-to-get-presale-tickets` (add H2) | 3 | Fits the on-sale day audience. |
| 125 | ticket insurance | 480 | 21 | $0.62 | Commercial | Middle | NEW POST `/blog/is-ticket-insurance-worth-it`. H1: Is Ticket Insurance Worth It? What It Covers and What It Does Not | 2 | Link to `/worry-free-guarantee`. Do not call the guarantee insurance. |
| 126 | ticket resale laws | 210 | 12 | $0 | Informational | Top | `/blog/are-resale-tickets-legit` (add H2) | 3 | Keep it general, not legal advice. |
| 127 | is scalping illegal | 390 | 32 | $0 | Informational | Top | `/blog/are-resale-tickets-legit` | 3 | Stretch (KD 32). |
| **F. Informational that feeds buying** | | | | | | | | | |
| 128 | what is general admission | 760 | 17 | $0 | Informational | Top | `/blog/what-is-general-admission` | 1 | Existing. Variants: "general admission ticket" (720 / KD 26), "what does general admission mean at a concert" (320 / KD 12). |
| 129 | general admission ticket | 720 | 26 | $0.95 | Informational | Middle | `/blog/what-is-general-admission` | 2 | Add as H2. CPC 0.95. |
| 130 | standing room tickets | 590 | 17 | $0.37 | Informational | Middle | `/blog/what-is-general-admission` | 2 | Already a secondary there. |
| 131 | floor seats concert | 590 | 10 | $1.43 | Informational | Middle | `/blog/best-seats-at-a-concert` | 1 | CPC 1.43. Volume is spiky (10 to 590). |
| 132 | best seats at a concert | 320 | 10 | $1.37 | Informational | Middle | `/blog/best-seats-at-a-concert` | 2 | Existing. Check H1 and title. |
| 133 | orchestra vs mezzanine | 480 | 7 | $1.37 | Informational | Middle | `/blog/orchestra-vs-mezzanine` | 1 | Existing. Quick win: confirm it ranks. |
| 134 | best time to buy tickets | 320 | 17 | $0.26 | Informational | Middle | `/blog/ticket-price-tracker` | 2 | Already a secondary there. |
| 135 | track ticket prices | 590 | 15 | $2.13 | Informational | Middle | `/blog/ticket-price-tracker` (price alerts feature, migration 0039) | 1 | CPC 2.13. Pair with the price-alert tool. |
| 136 | best place to buy tickets | 720 | 24 | $1.65 | Commercial | Middle | `/blog/best-site-to-buy-tickets` | 1 | Already a secondary there. |
| 137 | what is a presale | 480 | 14 | $0 | Informational | Top | `/blog/how-to-get-presale-tickets` | 2 | Existing. Add a definition H2. |
| 138 | how can i get presale tickets | 590 | 13 | $0.41 | Informational | Middle | `/blog/how-to-get-presale-tickets` | 2 | Question variant. "how to get tickets on presale" is 590 / KD 16. |
| 139 | mobile tickets | 330 | 8 | $1.03 | Informational | Middle | `/how-to-buy-tickets-online` | 2 | Already a secondary there. |
| 140 | how much do concert tickets cost | 390 | 13 | $0 | Informational | Top | NEW POST `/blog/how-much-do-concert-tickets-cost`. H1: How Much Do Concert Tickets Cost? Typical Ranges by Seat and Venue | 1 | Do not invent figures. Use live price ranges from the feed. |
| 141 | average ticket price | 590 | 11 | $0 | Informational | Top | `/blog/how-much-do-concert-tickets-cost` (section) | 2 | Add per-sport average only with sourced data. |
| 142 | how much are baseball tickets | 480 | 16 | $0.04 | Informational | Top | NEW POST `/blog/how-much-are-baseball-tickets`. H1: How Much Are Baseball Tickets? Prices by Section and Matchup | 2 | Variants: "average mlb ticket price" (320 / KD 6). |
| 143 | how much are hockey tickets | 320 | 16 | $0.05 | Informational | Top | NEW POST `/blog/how-much-are-hockey-tickets` | 3 | Season starts now. |
| 144 | average nba ticket price | 590 | 22 | $0 | Informational | Top | NEW POST `/blog/how-much-are-nba-tickets`. H1: How Much Are NBA Tickets? | 2 | Volume was 10 until Oct, now 590 (season start). |
| 145 | how much are season tickets | 720 | 15 | $0 | Informational | Top | NEW POST `/blog/how-much-are-season-tickets`. H1: How Much Are Season Tickets? Per-Game Cost vs Buying Single Games | 2 | Comparison fits a single-game marketplace. |
| 146 | how much do broadway tickets cost | 390 | 9 | $0.06 | Informational | Middle | `/buy-broadway-tickets` | 1 | Already a secondary there. |
| 147 | how much are national championship tickets | 590 | 13 | $0 | Informational | Top | NEW POST `/blog/college-football-national-championship-tickets`. H1: How Much Are College Football National Championship Tickets? | 3 | Publish in November for the January game. |
| 148 | how much are nfl draft tickets | 590 | 23 | $0 | Informational | Top | `/nfl-draft-tickets` (FAQ) | 3 | Seasonal. |
| 149 | how to get rush tickets for broadway shows | 590 | 30 | $0.22 | Informational | Middle | `/buy-broadway-tickets` | 2 | Already covers rush. Use the question as an H2. |
| 150 | box office tickets | 810 | 14 | $1.29 | Informational | Middle | NEW POST `/blog/box-office-vs-online-tickets`. H1: Box Office vs Online Tickets: Where to Buy and What You Pay | 2 | CPC 1.29. Good compare-the-sellers angle. |

## Data gaps (queried, SE Ranking returned no data)

These were tested and returned no volume. Treat them as unproven, do not plan around them:

- Buying and urgency: last minute concert tickets near me, last minute tickets near me, last minute concert tickets nyc, last minute tickets to a concert, last minute tickets to a show, last minute tickets to a game, nfl tickets near me, tickets under 20, tickets under 50, tickets under 100, cheap concert tickets under 20, cheap concert tickets under 50, concert tickets under 100, on sale this friday tickets, onsale this week.
- Tour and event dates: tour dates 2026, tour dates 2027, concerts 2026, concerts 2027, upcoming concerts 2027, new tours 2027, tours 2027, upcoming tours 2027, world tours 2027, stadium tours 2027, 2026 concert tours, 2027 concert tours, las vegas residencies 2026, las vegas residencies 2027, las vegas shows 2026, comeback tours, broadway shows 2026, broadway shows 2027, broadway shows opening 2027, new musicals 2027, national tour broadway, final four 2027 tickets, super bowl 2027 tickets, super bowl 61 tickets, wrestlemania 42 tickets.
- Venues and shows: t-mobile arena tickets, kia forum tickets, stubb's tickets, frost bank center tickets, germania insurance amphitheater tickets, stranger things broadway tickets, oh mary tickets, mean girls tickets, maybe happy ending tickets, death becomes her tickets, cirque du soleil tickets near me, festival tickets near me, halloween concert tickets, new years eve concert tickets, caitlin clark tickets, angel city tickets, messi tickets.
- Trust and fees: is ticket resale legal, are ticket resellers legit, are concert tickets refundable, can you get a refund on resale tickets, can i get a refund on tickets, can you return tickets, can you return concert tickets, what happens if a concert is cancelled, event cancelled refund, how to avoid ticket fees, ticket fee calculator, ticketmaster all in pricing, are concert tickets cheaper on resale, is ticket insurance worth it, buyer protection tickets, why are tickets so expensive, vivid seats alternative, seatgeek alternatives, seatgeek buyer guarantee, stubhub vs seatgeek (also stubhub vs vivid seats), today tix, today tix legit.
- Reader questions: when to buy nfl tickets, when to buy mlb tickets, when to buy broadway tickets, when do concert tickets go down in price, what is face value ticket, what is a ticket aggregator, what is a sold out show, what is a nosebleed seat, what does sec mean on tickets, what does restricted view mean, is the pit worth it, how to transfer tickets, best seats for football game, are tickets cheaper the day of the event, are lawn seats worth it, are floor seats worth it, how to buy tickets to a sold out concert, where to sit at a concert, how much are nfl tickets, how much are football tickets, how much are comedy show tickets, how much are festival tickets, is seat outlet legit, seat outlet tickets, seatoutlet, concert promo code, promo code for tickets, concerts under 50.
- Brand: "seat outlet" returned volume 10 (KD 6). The brand is effectively unsearched, so brand building has to come from the non-brand terms above.

Tested and dropped for volume under 200 even though KD is low (examples): ticket service fees (40), mobile ticket transfer (70), cheap comedy tickets (110), cheap festival tickets (10), are vip tickets worth it (30), when to buy concert tickets (70), how to get concert tickets (90), how to get tickets to a sold out show (50), best ticket resale sites (30), are resale tickets legit (20), what are resale tickets (10), ticket delivery (70), stubhub fees (30), is gametime legit (3,200 but KD 71).

## Data caveats

- Volume is a rolling figure and several rows are seasonal or spiky: red rocks tickets (390 now, 49,500-60,500 in Jul-Sep), nfr tickets, college football tickets (920 now, 47,500 in Dec), spring training, nfl and nba terms, concerts in {month}. Check the 12-month trend before committing a build date.
- Several rows are volume jumps in the last 1-3 months (kpop concerts near me, concert tickets near me, ticket refund, seatgeek refund, dynamic pricing tickets, average nba ticket price). They are real in the data but may fade.
- The same keyword can differ slightly from earlier docs: "ticket deals" returned 590 / KD 51 today, `inc/seo-keywords.php` has 480 / KD 51 from 30 Sep. Today's tool values are the ones used here.
- KD is SE Ranking's score for a generic site. A new domain with no rankings should assume KD 20 or below is winnable, KD 21-30 needs a strong page with a live event feed, and anything above is a stretch.

## First 10 blog posts to write

Order is by buying or trust intent, KD, and timing (today is 2026-10-04).

1. `/blog/where-to-buy-cheap-concert-tickets` (590 / KD 6 / $0.77). Intent: transactional, compare sellers and ways to pay less. H1: Where to Buy Cheap Concert Tickets and How to Pay Less.
2. `/blog/how-to-get-a-ticket-refund` (810 / KD 13 / $0.90). Intent: trust, post-purchase worry. H1: How to Get a Ticket Refund: Cancelled, Postponed and Resale Orders. Include sections for Ticketmaster (590 / KD 7), SeatGeek (590 / KD 12), StubHub (660 / KD 28), AXS (390 / KD 14).
3. `/blog/ticketmaster-vs-stubhub` (590 / KD 7 / $5.41). Intent: commercial comparison. H1: Ticketmaster vs StubHub: Fees, Refunds and Seat Choice Compared. Cross-link `/blog/stubhub-alternatives` (260 / KD 22).
4. `/blog/is-ticketmaster-legit` (810 / KD 12 / $3.40). Intent: trust. H1: Is Ticketmaster Legit? Primary Sales, Resale and What to Check. Link to `/blog/are-resale-tickets-legit` and `/ticket-buyer-protection`.
5. `/blog/concerts-in-october` (880 / KD 21). Intent: seasonal discovery, window is open now. H1: Concerts in October: Tours and Shows Worth Buying Tickets For. Follow with `/blog/concerts-in-november` (390 / KD 25) and `/blog/concerts-in-december` (390 / KD 26).
6. `/blog/ticketmaster-platinum-tickets` (480 / KD 6 / $3.58). Intent: informational with a buying decision. H1: What Are Ticketmaster Platinum Tickets and How Do They Compare With Resale?
7. `/blog/face-value-tickets` (480 / KD 20 / $1.14). Intent: informational, price expectations. H1: Face Value Tickets: What They Are and When Resale Costs More.
8. `/blog/how-much-do-concert-tickets-cost` (390 / KD 13) with the "average ticket price" section (590 / KD 11). Intent: informational, budgeting. H1: How Much Do Concert Tickets Cost? Typical Ranges by Seat and Venue.
9. `/blog/is-ticket-insurance-worth-it` (480 / KD 21 / $0.62). Intent: commercial, risk check. H1: Is Ticket Insurance Worth It? What It Covers and What It Does Not.
10. `/blog/dynamic-pricing-tickets` (320 / KD 22). Intent: informational, explains price swings. H1: Dynamic Pricing for Tickets: Why Prices Change and How to Buy Smarter. Alternative if this slot should go to a buying page: `/blog/box-office-vs-online-tickets` (810 / KD 14 / $1.29).

New landing pages worth building in parallel because they are KD 20 or below with buying intent: `/nyc-concert-tickets` (590 / KD 19), `/boston-concert-tickets` (590 / KD 21), `/atlanta-concert-tickets` (390 / KD 19), `/college-football-tickets` (920 / KD 18), `/rodeo-tickets` (390 / KD 8), `/disney-on-ice-tickets` (480 / KD 6), `/edm-concerts` (740 / KD 9), `/kpop-concerts` (810 / KD 12), `/rap-concerts` (720 / KD 6), `/concerts-today-and-tomorrow` (740 / KD 21).

## Quick wins on existing pages

Add the keyword as an H2 or FAQ with a short answer-first paragraph, then add one internal link. No new template needed.

| Existing page | Add these keywords (volume / KD) |
|---|---|
| `/ticket-buyer-protection` | legit ticket sites (320 / 0), ticket refund (810 / 13) as a short section linking to the new refund post, ticket insurance (480 / 21), ticket resale laws (210 / 12) |
| `/worry-free-guarantee` | is ticketnetwork legit (260 / 14) as an FAQ, quoting only `inc/guarantee.php` |
| `/concert-tickets-for-sale` | concert tickets near me (880 / 15), concerts tickets (990 / 26, spelling variant), links to the city pages |
| `/buy-broadway-tickets` | where to find cheap broadway tickets (590 / 14), discount tickets to broadway shows (480 / 26), how to get cheap broadway tickets nyc (480 / 25), broadway resale tickets (480 / 30), how to get rush tickets for broadway shows (590 / 30) |
| `/tickets-promo-code` | broadway discount codes (480 / 7), broadway promo codes (480 / 13), broadway tickets promo code (260 / 9) |
| `/upcoming-music-festivals` | festival tickets (760 / 19) |
| `/game-day-tickets` | sports tickets near me (260 / 19), cheap baseball tickets (290 / 29), how much are baseball tickets (480 / 16) |
| `/comedy-show-tickets` | comedy tickets near me (320 / 26), drag show tickets (390 / 30) |
| `/soccer-tickets` and `/mls-tickets` | cheap soccer tickets (210 / 15), mls playoff tickets (260 / 26) |
| `/christmas-shows-near-me` | christmas concert tickets (390 / 8), nutcracker tickets near me (320 / 20) |
| `/city-events` | event tickets near me (390 / 23), live shows near me (720 / 6), shows this weekend (260 / 7) |
| `/how-to-buy-tickets-online` | mobile tickets (330 / 8) |
| `/blog/how-to-avoid-ticket-scams` | fake tickets (720 / 18) as an H2 (already a secondary keyword) |
| `/blog/what-is-general-admission` | general admission ticket (720 / 26), standing room tickets (590 / 17), what does general admission mean at a concert (320 / 12) |
| `/blog/best-seats-at-a-concert` | floor seats concert (590 / 10) |
| `/blog/ticket-price-tracker` | track ticket prices (590 / 15), best time to buy tickets (320 / 17) |
| `/blog/ticket-fees-explained` | ticket fees (480 / 7) in title and H1, seatgeek fees (320 / 20), ticketmaster fees (590 / 33) |
| `/blog/how-to-get-presale-tickets` | what is a presale (480 / 14), how can i get presale tickets (590 / 13), ticketmaster queue (290 / 25) |
| `/blog/upcoming-concert-tours` | concert schedule (810 / 24), just announced concerts (480 / 16), tour announcements (410 / 28), reunion tours (320 / 9) |
| `/blog/are-resale-tickets-legit` | is scalping illegal (390 / 32), ticket resale laws (210 / 12) |

Also: the old audit note says the live /blog serves different, thinner content (about 150 words per post) than the migrations. Check that migrations 0040-0041 are applied in production before measuring these posts.
