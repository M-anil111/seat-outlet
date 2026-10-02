
## WS3: listings, categories, genres and search
- Server timezone: the site now sets America/Los_Angeles in inc/constants.php (override with env SITE_TIMEZONE). Tech support: confirm the production PHP/host timezone does not conflict and that "Tonight" shows events through the evening. Why: "today" used to roll over at 5pm Pacific on a UTC server.
- Letter pages for the artist directory use ?letter=A (query string). Tech support: if clean paths like /all-artists-and-teams/a are wanted, add a rewrite (see docs/server-rewrites.md). Why: cannot verify host rewrites from code.
- Price filter ("Under $X") uses TicketNetwork's pricingInfo/lowPrice/value (verified in the sandbox). Owner: check on production that results look right, since sandbox prices are $1 test values.
- Promo codes TAKE5 / TAKE10 were removed from listing pages and replaced by an email alert form (soLeadForm). Owner: if the codes are real, confirm them with TicketNetwork before putting them back. Why: unverifiable discount claims.
- Email alerts for "Tell me when events are added" depend on WS1's /ajax/subscribe.php storing interest_type category or city (and performer for search).
- Old buy-tickets-online location and custom date-range box was removed in favor of the shared chips (location, dates, distance, price, sort). Owner: say so if a custom date range picker is still wanted there.
- Names starting with "The" sort under T in the artist directory (the API has no sort key without the article). Owner: decide if that needs a data fix.
