# Page spec for event pages and performer-in-location pages

Source: the owner's page template sheet (Concert, Sports, Theater, Festival). Decisions made with the owner on 5 Oct 2026. Code: `inc/page-spec.php`, `inc/seo-event.php`, `event.php`, `renderArtistLocationPage()` in `functions.php`, `performer.php`.

Decisions
- Wording: titles and descriptions say "Buy ... tickets" (customers check out on Seat Outlet). The body copy and the footer keep the TicketNetwork fulfillment disclosure.
- Titles: no pipe, dash or colon ever (`soNormalizeTitle()`); the brand is "at Seat Outlet" (two words). Event pages and performer-in-location pages may run up to 70 characters (`soTitleUpTo()`, `$pageTitleMax` in header.php); every other page stays at 59.
- Urgency: "before they sell out" is in the description when tickets are listed. A page with no tickets listed gets an alert line instead.
- URLs do not change. The sheet's patterns (`/{performer}-concert-tickets-{city}/`) would collide for repeat dates and series, and the numeric-id removal work depends on the current slugs. The spec's keyword lives in the title, H1 and H2s instead.
- One keyword, one page:
  - performer in a city (`/artist-city/...`): "<Performer> Concert Tickets in <City>" ("Tickets in" for sports, shows, festivals)
  - one event (`/event/...`): "<Event> Tickets at <Venue> on <Mon D>"
  - performer (`/artist/...`): "<Performer> Tickets"
- Promo codes: TAKE5 (5% off orders of $199 or more) and TAKE10 (10% off orders of $349 or more), the same on every page, named for the page's subject ("Promo codes for Metallica tickets"). Terms are the ones on `/tickets-promo-code`; TicketNetwork has not confirmed them (docs/launch-checklist.md).
- Schema: the most specific Event type (MusicEvent, SportsEvent with the two teams as competitors, TheaterEvent, ComedyEvent, Festival), organizer and end time (earlier release), inLanguage, isAccessibleForFree false, an ItemList of similar events on event pages and of listed events on performer-in-city pages, and `about` on the page node. HowTo is not used: Google no longer shows it.
- Alt text: "<Performer> live concert in <City>", "<A vs B> game in <City>", "<Show> live show in <City>", "<Festival> festival in <City>".

Sections (event page order): Tickets, How to buy, Promo codes, About, Buyer guarantee, FAQs, City info, Tour dates / games in the city / shows in the city / lineup (by kind), Guide, Similar events. Each is built from data the page holds and is left out when there is none (no bio and no Wikidata description means no About; no events in the city means no City info).

Second round (the venue sheet and the master sheet, 5 Oct 2026)
- Event pages now follow the venue sheet: "Buy <Performer> Tickets for <Venue> Show in <City>, <ST>" for concerts, "Buy <A> vs <B> Game Tickets at <Venue>" for sports, "Buy <Show> Tickets at <Venue>, <City>" for theater and "Buy <Festival> Passes for <Venue>, <City>" for festivals, with the sheet's H2 for every section (`soSpecEventText()` in `inc/page-spec.php`). A performer in a venue (`/artist-venue/`) says "at <Venue>"; a performer in a city says "in <City>".
- City, state, country, venue and category-city pages: "Buy Tickets to <Concerts> in <Place>" titles (up to 70 characters), the urgency line when events are listed, "Buy tickets for upcoming events in <Place>" as the list heading, a promo block named for the place, "FAQs about <Place> Tickets".
- Venue pages add "About <Venue> in <City>, <ST>" (address, directions, transit, official site and weather links, nothing about parking or bag rules, which we do not hold) and "Top Upcoming Performances at <Venue>".
- New city pages (`inc/discovery-pages.php`): `/last-minute-tickets/<city>` (next 7 days), `/weekend-events/<city>`, `/cheap-tickets/<city>` (lowest price first), `/best-events/<city>` (best sellers). They need the nginx rewrite in docs/server-rewrites.md. A page with fewer than 3 events is noindex and out of the sitemap. No VIP or floor page: the ticket API does not say which listings are VIP or floor.
- FAQ questions are H3 (inside the details summary), the section title H2, answers plain paragraphs.
- Tracking: `promo_copy` and `buy_click` events (js/analytics-events.js) for GTM.

Where the master sheet was changed
- "Seat Outlet.com" in the URL columns is a typo for seatoutlet.com.
- URL patterns (/artist/<artist>-tickets-<city>-<state>/, /team/, /game/, /show/, /festival/, /concerts/, /deals/) were not adopted: they would collide with the existing slugs, send a performer-in-city page to the performer's address, and need thousands of redirects. The keyword wording lives in titles and headings instead.
- The Artist Performer and Artist Tour rows are identical, which would put two pages on one keyword. /artist/<slug> is the tour page; /artist-city/<slug>/<city> is the performer in a city.
- State, country and category rows reused "{city} {state}" and "About {venue} in {city}"; those pages use their own place name and have no venue section.
- Text the sheet asks for that needs facts we do not hold (parking, doors open, bag policy, 300 to 500 words of generated copy per page) is not written; the pages link to Google Maps, public transit directions, the official venue site and the National Weather Service instead, and each paragraph is built from the event's own data.
- "Verified" in the festival title was dropped: it claims a check we do not do. "Perfoming" and the dashes in two H2s were fixed.
- FAQPage schema stays, but Google limits FAQ rich results to well-known government and health sites (since August 2023), so expect no rich result from it.
