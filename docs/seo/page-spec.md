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
