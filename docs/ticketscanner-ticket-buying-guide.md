# Building the ticket-buying side of ticketscanner.ca

A build guide based on what we built, measured and got wrong on Seat Outlet (seatoutlet.com / beta.seatoutlet.com, TicketNetwork catalog API + Seatics seat maps + TicketNetwork hosted checkout). Everything marked **Verified** was tested against the TicketNetwork sandbox API or in a real browser during that build. Everything marked **Confirm** is an assumption you must check before it drives a decision.

---

## 0. Read this first

**What transfers directly.** The TicketNetwork integration is the same whichever site sits on top: same API, same seat-map widget, same hosted checkout, same data quirks. The listing, performer, venue, city and event pages, the caching layer, the image pipeline and the analytics events can all be reused as a pattern.

**What is different for ticketscanner.ca.**

| Topic | Seat Outlet | ticketscanner.ca (what I could see) |
|---|---|---|
| Business | Ticket marketplace, tickets are the product | Flights, hotels, vacations, rental cars (page title: "Hotel Deals, Flights, Cheap Vacations & Rental Cars"); tickets would be a new vertical |
| Stack | Plain PHP, MariaDB, file cache, FTP/SFTP deploy | **Confirm.** The site is behind Cloudflare and I could not identify the CMS or framework from the public HTML. Do not assume WordPress or PHP |
| Market | US catalog (country `US`) | Canadian audience. **Confirm** the TicketNetwork website config supports Canadian inventory, CAD display and a Canadian buyer |
| Cross-sell | None | Strong: someone booking a flight to Las Vegas is a ticket buyer for Las Vegas (see section 11) |

**Three blockers to clear with TicketNetwork before writing any checkout code** (all three bit us on Seat Outlet):

1. **Mercury is a separate API subscription.** On Seat Outlet every `/mercury/v5/*` call returns `403 API Subscription validation failed`. Mercury is what holds ticket groups and places orders. Without it you cannot build your own checkout; you use TicketNetwork's hosted checkout instead (section 7).
2. **The hosted checkout needs a DNS host on your domain** (Seat Outlet's config points to `checkout.seatoutlet.com`, which had no DNS record, so no purchase could complete). Ask what host TicketNetwork expects for ticketscanner.ca and set up the CNAME.
3. **Catalog environment and website config must match.** Sandbox event IDs only work with the sandbox website config; live IDs with the live one. Mixing them makes the seat map say every event "has expired" with zero ticket groups. This is exactly what beta showed.

---

## 1. Architecture at a glance

```
Browser
  |
  |  listing / performer / venue / city / search / event pages   (server-rendered)
  v
Your site  --->  request layer (token cache + response cache + profiling)  --->  TicketNetwork Catalog API
  |                                                                              (events, performers, venues, cities, categories, suggest)
  |-- event page embeds Seatics MapWidget3 (seat map + ticket list) ---------->  mapwidget3.seatics.com
  |
  |-- "Buy"  --> /checkout (your order-review page) --> hosted checkout on checkout.<yourdomain>  (TicketNetwork)
  |                                                          |
  |<-- /order-confirmation?oid=... <---------------------------  post-purchase redirect (configured by TicketNetwork)
  |
  |-- images: stored on your CDN, resolved by a cron queue from licensed sources
  |-- analytics: dataLayer events -> GTM/GA4
```

The important design fact: **the visitor pays on TicketNetwork's hosted checkout**, not on your site. Your job is discovery, trust, the seat-map page, the hand-off, and the confirmation and measurement around it.

---

## 2. What to request from TicketNetwork (send this list)

1. API app credentials for **sandbox and production** (consumer key + secret). Production catalog base URL is `https://www.tn-apis.com`; sandbox is `https://sandbox.tn-apis.com`.
2. A **website config ID** for ticketscanner.ca in each environment (Seat Outlet: 12498 sandbox, 27773 live). This is what the `X-Listing-Context: website-config-id=` header and the seat-map script use.
3. **Mercury API** subscription, if you want an owned checkout later (ask for the order, hold and delivery documentation and a sandbox).
4. The **hosted-checkout hostname** they expect and the DNS record to create.
5. **Post-purchase redirect** set to `https://ticketscanner.ca/order-confirmation` and which parameters they append (order number is essential; ask for total and email too).
6. **Canadian coverage**: is Canadian inventory available, in which currency is it priced and charged, and are Canadian buyers supported at checkout. Also ask about service-fee display rules and delivery methods.
7. **Maps Configuration Management** access (TN Portal) for widget branding without code changes.
8. Confirmation of **who is the merchant of record** for the sale, for your terms, refund policy and support flow.

---

## 3. The API request layer (build this first)

Every call to the catalog API should go through one function. Seat Outlet's `tnRequest()` in `functions.php` is the reference.

### 3.1 Authentication

- `POST https://key-manager.tn-apis.com/oauth2/token`, HTTP Basic with consumer key and secret, body `grant_type=client_credentials`. Tokens last 3,600 seconds.
- Send `Authorization: Bearer <token>`, `Accept: application/json`, and `X-Listing-Context: website-config-id=<id>` on every catalog call.
- **Cache the token for its real lifetime** (minus a safety buffer). On Seat Outlet the host had no APCu, the APCu-only cache never worked, and every page view paid a ~0.4 s OAuth round trip before its first catalog call. Store it in a file outside the web root (or Redis/your platform's cache).

### 3.2 Response caching (the biggest performance win)

Cache GET responses by a hash of endpoint + params, with a TTL by data type:

| Data | TTL | Why |
|---|---|---|
| Performers, venues, cities, categories, countries | 6 hours | Reference data barely changes |
| Event lists | 10 minutes | Prices and inventory move, but not per second |
| `events/search` and anything with `geoFilter` | 5 minutes | Per-visitor variety, cheap to recompute |
| `suggest` (autocomplete) | 1 hour | Small, repetitive |

Never cache error bodies (`code`/`Message` keys). Sweep stale cache files occasionally. Add a profiling switch (an env var that logs calls, cache hits and milliseconds per request) so you can prove the numbers.

**Measured on Seat Outlet** (server time per page, live calls → warm cache): performer 1.2 s → 6 ms, artist-by-city 1.3 s → 7 ms, city/venue/state 0.4–0.5 s → 4–8 ms, autocomplete 0.9 s → 2 ms, sitemap 7 s → 65 ms.

Add a **warm cron every 5 minutes** for the pages that get the most traffic, so the first visitor never waits on the API.

### 3.2b Resilience (each of these was verified by simulating it)

- **Tokens rotate.** TicketNetwork invalidates an app's previous token a few seconds after a new one is issued (token A returned 200 right after token B was issued and 401 five seconds later). Web servers, cron jobs and every other process must share one token store, fetching must be serialized with a lock, and a 401 must trigger one refresh-and-retry. Two processes with separate stores keep knocking each other out. PHP-FPM and CLI often have different temp directories; use one shared directory.
- **Serve stale while refreshing.** Past the TTL but inside a grace window, return the cached copy immediately and refresh after the response under a non-blocking lock. This removes the "first visitor after expiry waits" cost and prevents stampedes.
- **Circuit breaker.** After a live failure, skip live calls for ~20 seconds and serve stale or empty data instead of letting every request wait out a 20-second timeout.
- **Parallelize independent calls** (curl_multi / your platform's equivalent) and warm the cache from the top of the page, then let the normal calls hit it. Batch lookups with `filter=id in (1,2,3)` instead of one request per id.

### 3.3 Rules that prevent bugs

- One code path for all calls: timeouts (connect 5 s, total 20 s), gzip, error logging to Sentry, return an empty array on failure. **A thrown exception on an API outage took down every category page on Seat Outlet** before we caught it.
- Never trust `results` to exist: `$data['results'] ?? []`, `$data['totalCount'] ?? 0`.
- Never put secrets in the repo. Seat Outlet's sandbox key and secret ended up in git history; **rotate them** and load credentials from environment variables.

---

## 4. Catalog API: what works and what bites (all Verified)

| Need | Call | Notes |
|---|---|---|
| Upcoming events | `GET /catalog/v2/events` | `filter=date/date ge 2026-10-01`. Add `includeTotalCount=true` and read `totalCount` from the **same response**; do not make a second count call. |
| Best sellers first | `sort=-salesRank` | **Descending** is the real top sellers. Ascending (`salesRank`) returns the salesRank=100 baseline alphabetically ("3 Doors Down, 50 Cent…"). Add `salesRankOptions={"interval":"day","metric":"orderVolume"}`. Valid intervals we confirmed: `day`, `week` (`month` is rejected). |
| Only buyable events | `_metadata/hasTickets eq true` in the filter | Without it, sales-rank sorting surfaces placeholder events with zero tickets. |
| Price on listing rows | already in each event: `pricingInfo.lowPrice / averagePrice / highPrice` (`.value`, `.text.formatted`) | Free "From $X" with no extra call. |
| Page size | `perPage` max **200** | Asking for 500 silently returns 200. Counting with `perPage=500` under-reports; use `includeTotalCount`. |
| Category tree | `/catalog/v2/categories`, children via `filter=parentCategory/path eq '.1859.1986.'` | Categories have no salesRank but carry `_metadata.ticketCount/eventCount`; rank by ticketCount. Match a category by exact path segment (`contains(path,'.1868.')`), not bare digits. |
| Performers by category | `GET /catalog/v2/performers?categoryFilter=contains(path,'…')&eventFilter=_metadata/hasTickets eq true&sort=-salesRank` | `eventFilter` removes performers with no events. |
| Events for one performer | `performerFilter=id eq 1451` on `/events` | |
| Location filters | `city/id eq N`, `stateProvince/id eq N`, `venue/id eq N`, `country/alphaCode eq 'US'` | **Countries are keyed by 2-letter code**, not numeric ID (`/countries/US` works, `/countries/1` is a 404). |
| Near a point | `geoFilter=nearby(lat, lng, 50mi)` | The radius must be **unquoted**; `'50mi'` returns 400. |
| Top cities | `GET /catalog/v2/cities` with `-salesRank` | Skip the placeholder city named "Your Home" (virtual events). |
| Autocomplete | `GET /catalog/v2/suggest?q=…&performersRequested=5&venuesRequested=5&citiesRequested=5` | |
| Free-text search | `GET /catalog/v2/events/search?q=…` | `q=*` for "everything". |
| Date-TBA placeholders | events dated 2070+ with time "TBA" | Cap featured feeds at `today + 2 years`; a card saying "Feb 2072" looks like a bug. Full listings do not need the cap. |

No image fields exist on performers, venues, events or categories. Images need their own source (section 8).

---

## 5. Pages to build

Use a URL scheme with the numeric ID last: `/artist/carrot-top-1451`, `/venue/madison-square-garden-1889`, `/city/las-vegas-nv-2355`, `/event/carrot-top-5215917`, `/category/broadway-1868`. The slug is cosmetic and the trailing ID is what you look up, so renamed performers never break.

| Page | Content | Source |
|---|---|---|
| Ticket hub (`/tickets`) | Top sellers, "From $X" on every row, Deal badge, filters, More Events | events, `-salesRank`, hasTickets |
| Category listings (concerts, sports, theater, festivals) | Same, plus top subcategories and top performers | events + categories |
| Performer | Hero with photo and "13 upcoming events · Tickets from $X · Next: Oct 2 in Las Vegas", Next Event card, price guide, event list, bio, FAQ, "tickets by city / venue / state", related performers | performers, events |
| Venue / city / state / country | Upcoming events, "More tickets in {place}" links to category variants | events with location filter |
| Search | GET form (shareable URL), `noindex, follow`, escaped output | events/search + suggest |
| Event | Header (date, venue, links to performer/city/venue), **seat-map widget**, fallback block when there is no inventory | events/{id} |
| `/checkout`, `/order-confirmation` | Sections 7 and 9 | |

**Listing row spec** (applies everywhere): date box, time, performer/event name, city · venue links, **From price**, Deal badge when `lowPrice <= 0.6 × averagePrice`, Find Tickets button. The Deal badge is derived from the data, not a promo code. Do not claim a discount the checkout cannot apply.

---

## 6. The event page and the seat map

The seat map and the ticket list are the Seatics MapWidget3, an external script. Documentation: the *MapWidget3 Integration Guide v1.4* (TicketNetwork). Key facts:

```html
<div id="tn-maps" class="seatics" style="height: calc(100vh - 50px); width: 100%;"></div>
<script src="https://mapwidget3.seatics.com/js?eventId={EVENT_ID}&websiteConfigId={WEBSITE_CONFIG_ID}&mobileOptimized=true&includeJQuery=false&containerId=tn-maps&useDarkTheme=false"></script>
```

- The **event ID is the catalog event ID** and the widget resolves it against the website config. **Use the same environment for both** (section 0, blocker 3). Read the website config ID from the same setting that chooses the catalog base URL, never hard-code it.
- `includeJQuery=false` means your page must load jQuery first (Seat Outlet loads 3.7.1 from code.jquery.com).
- The widget loads its own CSS/JS from its CDN and starts Riskified fraud tooling and a feature-flag SDK. Allow those domains if you ever add a Content-Security-Policy.
- Configuration lives in `Seatics.config.*` (or in the Maps Configuration Management tool). What we found live in the config: `checkoutUrl` (the hosted checkout host), `skipPrecheckoutDesktop/Mobile = true` (single click to checkout), deep-link keys `tgid`, `qty`, `prc`, `cstnm`.
- **Hooks worth using:** `Seatics.config.noEventHandler` (event already happened), `Seatics.config.noTicketsHandler` (no inventory), `Seatics.config.onBuyButtonClicked`, and `Seatics.TrackingEvents.registerEventListener(fn)` which receives `FinishedLoading` (with `numTicketGroups`), `MapInteraction`, `FiltersChanged`, `BuyButtonClicked`.
- **Replace the default dead-end.** Out of the box the widget prints "Sorry, this event has expired" and stops. Replace it with your own block that keeps the visitor on the site: links to the performer's other dates, other events in the city, other events at the venue. On Seat Outlet this is `noEventHandler`/`noTicketsHandler` toggling a hidden `#so-no-tickets` block and hiding the map container.
- Do not restyle the widget with fragile CSS unless you must; it changes when TicketNetwork ships an update. Prefer the Maps Configuration tool.

---

## 7. Checkout

### 7.1 What happens today (hosted checkout)

The widget's single-click flow sends the buyer to `{checkoutUrl}/?tgid={ticketGroupId}&qty={n}&prc={expectedPerTicketPrice}`. TicketNetwork's checkout locks the ticket group, takes payment, screens for fraud and handles delivery. You do not handle card data, which keeps you out of PCI scope for the payment itself.

### 7.2 An order-review step on your side (`/checkout`)

Route the hand-off through your own page, so the buyer sees your brand, the event details and the price before leaving:

`/checkout?eid={eventId}&tgid={ticketGroupId}&qty={n}&prc={price}`

- Shows event name, date, venue, quantity × price, subtotal, "fees calculated at checkout", the guarantee, what happens next.
- Fires `view_cart`, and `begin_checkout` when the button is clicked.
- The button links to the hosted checkout with the same parameters.
- `noindex, nofollow`.
- Set the widget's `checkoutUrl` to your `/checkout` route if you want every Buy click to pass through it. **Confirm** with TicketNetwork that a `checkoutUrl` pointing at your own page is supported and that the parameter names are preserved.

Do not show a final total you cannot guarantee. Say plainly that fees are added at checkout.

### 7.3 An owned checkout later (Mercury)

With Mercury enabled you can hold a ticket group, collect payment through your own gateway, create the order and manage delivery. That is a real project: PCI scope (use a hosted payment field such as Stripe Elements to keep it small), fraud screening, delivery workflows, refunds and support. Scope it separately once the Mercury sandbox and documentation exist; the `/checkout` page is where the steps slot in.

---

## 8. Confirmation page (`/order-confirmation`)

TicketNetwork sends the buyer back after a successful order. Build the page to be safe by design:

- Read `oid` (order number), optionally `total`, `email`, `eid` from the URL. **Nothing money-related is trusted from the URL**: the order number is displayed, the total only feeds analytics, and no ticket access is granted.
- Show: "Purchase complete", the order number, a **masked** email (`ja••@example.com`), the event summary, what happens next (confirmation email in minutes, delivery method and timing from the email, arrive early), support contact quoting the order number, buyer-protection link, and 3–5 related events from your cached feeds (no API call).
- Fire the GA4 `purchase` event **once per order number** (guard with `localStorage`), with `transaction_id`, `value`, `currency`.
- `noindex, nofollow`.
- Ask TicketNetwork whether they can also post a server-side confirmation (webhook) so you can reconcile orders without relying on the browser redirect. **Confirm.**

---

## 9. Images

The catalog has no images. What we built and why (details in Seat Outlet's `inc/images.php` and `CONTRIBUTING.md`):

- **Resolve in the background, not in the request.** Cards call a serve-only endpoint that returns the stored image or a category fallback and queues the entity; a cron works the queue. Single-entity pages may resolve inline with short timeouts.
- **Never cache a failure forever.** Store status (`ok`, `manual`, `fallback`, `pending`), source, license, attribution, attempts and `expires_at`. Retry misses after 7 days, rate limits and storage errors after ~90 minutes.
- **One source chain per entity type**, because no single source covers everything:

| Type | Sources | License handling |
|---|---|---|
| Artist / show | Wikidata (verify entity type via P31/P106) → Wikimedia Commons | Accept only CC0, CC BY, CC BY-SA, public domain. Reject NC, ND, non-free and trademark-flagged files. Store the photographer credit and show it under the image. |
| Sports team | TheSportsDB (photos before badge) → Wikidata | Logos are trademarks whichever API supplies them; get legal advice before using them commercially. |
| Venue | Wikidata → TheSportsDB venues | |
| Festival | Wikidata → Openverse (`license=cc0,by,by-sa`) | Store Openverse's attribution string. |
| City / destination | Wikidata → Pexels (free API key) | Pexels images are free for commercial use. |

- Send a **User-Agent with contact info** to Wikimedia APIs (200 requests/minute with one, 10 without). Pace the cron.
- Sources we checked and **do not recommend** for a resale site: Ticketmaster Discovery (terms prohibit competing use and caching), Spotify (images must be unmodified with logo and link), Google Places photos (cannot be stored), Unsplash (hotlink-only).
- Give staff an **admin override** (upload or paste an image, never auto-replaced) for the top 200 performers and venues. Licensed editorial photos are the only zero-risk option for high-traffic pages.

**For a travel site there is an advantage:** you likely already license destination photography for the flight and hotel side. Reuse it for city pages.

---

## 10. SEO and structured data

- **Per-page title, description, canonical.** Performer descriptions should carry the from-price and event count.
- **JSON-LD:** site-wide Organization and WebSite; `BreadcrumbList` on every deep page; on performer, venue and city pages an `Event` node per listed date with `location` (Place + PostalAddress), `performer`, `eventStatus`, `eventAttendanceMode` and an `Offer` (price, currency, `InStock`/`SoldOut`, url). Never fabricate `AggregateRating` or reviews; Seat Outlet had a fake 4.8-star rating hard-coded in every page's schema and it had to be removed.
- **Indexing by environment.** Seat Outlet hard-coded `noindex nofollow` on every page for the beta host and it would have shipped that way. Drive the robots meta from configuration: `noindex` on beta/staging hosts, `index, follow` on the production host, `noindex` always for search results, checkout, confirmation and admin.
- **Sitemap:** static pages, top cities and their category pages, upcoming events (capped page count). Link-only for the long tail of combination pages.
- **Internal linking is the crawl path** for combination pages (performer × city, category × city). Every city page links to its concerts/sports/theater variants; every performer page links to its cities, venues and states.
- Keep the travel site's existing SEO intact: put ticket pages under a clear path (for example `/tickets/…` or `/events/…`) rather than mixing them into flight and hotel URL patterns.

---

## 11. Making tickets work inside a flight and travel site

This is where ticketscanner.ca has an edge Seat Outlet does not.

1. **"Events during your trip."** After a flight or hotel search, query `/catalog/v2/events` with the destination city and the trip dates (`city/id eq N and date/date ge {arrive} and date/date le {depart}`, `sort=-salesRank`, `_metadata/hasTickets eq true`). Show 3–6 events with From prices. Needs a mapping from your destination (IATA code or city) to TicketNetwork city IDs: build it once by searching `/catalog/v2/cities` and cache it; **Confirm** how you identify destinations today.
2. **Destination pages.** Reuse `/city/{name}-{id}` pages as "Things to do in {city}: concerts, sports, theater".
3. **Confirmation-page cross-sell** in the other direction: a completed flight booking offers events at the destination.
4. **Keep the two checkouts separate.** Flights and hotels check out on your existing system; tickets check out on TicketNetwork's. Say so plainly ("Tickets are sold and fulfilled by our ticket partner") so buyers are not surprised by a different payment page.
5. **One analytics model.** Use the same dataLayer event names (`view_item`, `begin_checkout`, `purchase`) with an `item_category` such as `tickets` vs `flights`, so revenue reports can be split.

---

## 12. Compliance and trust (Canada) — verify with counsel

I have not verified any of the following for Canada; treat them as a checklist to confirm, not as advice:

- **Provincial ticket-resale rules** (some provinces regulate resale, price caps or disclosure). Check every province you sell into.
- **Price transparency.** "Fees calculated at checkout" may not satisfy all-in / drip-pricing rules under the Competition Act; ask whether the displayed price must include mandatory fees.
- **Privacy** (PIPEDA and provincial laws), **CASL** for marketing email, and cookie consent for the analytics tags.
- **Refund and guarantee wording.** Only promise what the merchant of record actually honors. Seat Outlet had "verified promo codes, applied instantly at checkout" copy for codes nothing applied; it was rewritten. Keep the same discipline.
- **Attribution and licenses** for images and geo data (MaxMind GeoLite2 requires an attribution line and forbids redistributing the database).

---

## 13. Security checklist

- Credentials only from environment or a secret store; rotate anything that ever touched git.
- Escape every value echoed into HTML (`htmlspecialchars`), including request parameters echoed back into form fields and "no results for X" messages. Seat Outlet had two reflected XSS holes of this kind.
- Parameterized queries only; allow-list client-supplied parameters that get forwarded to the API (load-more endpoints).
- Send `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, HSTS. Add a Content-Security-Policy in report-only mode first (the widget, GTM and fonts load from several hosts).
- Cookies: `SameSite=Lax`, `Secure`, sensible expiry.
- Rate-limit and bot-protect the search and AJAX endpoints; reCAPTCHA on forms.
- Admin: CSRF tokens, one-time bootstrap registration (Seat Outlet had an open admin registration page on production), `noindex`.
- Checkout and confirmation pages: `noindex`, no ticket access granted from URL parameters.

---

## 14. Analytics and measurement

Push GA4 ecommerce events to `window.dataLayer` on every page and load GTM when an ID is configured.

| Event | Fired when |
|---|---|
| `view_item` | Seat map finished loading (with ticket group count) |
| `select_item` | Buy clicked in the widget |
| `view_cart` | `/checkout` viewed |
| `begin_checkout` | Continue to secure payment clicked |
| `purchase` | `/order-confirmation` loaded, once per order number |
| `no_inventory` | Fallback block shown |

Funnel to watch: listing click → event page → `view_item` → `select_item` → `begin_checkout` → `purchase`. The drop between `begin_checkout` and `purchase` measures the hosted checkout; you cannot see inside it, so ask TicketNetwork for order reports to reconcile.

---

## 15. Performance targets

Seat Outlet after the work: server time 3–10 ms warm; Lighthouse desktop performance 95–98, SEO 100. Targets to hold ticketscanner.ca to:

- Every listing/detail page under 100 ms server time on a warm cache; no page makes more than 3 catalog calls.
- Card images batched in one request per slider (Seat Outlet went from up to 24 sequential requests to 1).
- `Cache-Control` on cheap GET AJAX endpoints (autocomplete 1 h, images 10 min).
- Lazy-load below-the-fold images; `fetchpriority=high` and explicit dimensions on the hero image.
- Watch Cloudflare caching: dynamic HTML is not cached by default, so the file/response cache does the work.

---

## 16. Suggested build order

| Phase | Deliverable | Done when |
|---|---|---|
| 0. Setup | Sandbox credentials, website config, Mercury/checkout answers from TicketNetwork, Canadian coverage confirmed | Section 2 list answered in writing |
| 1. API layer | Token cache, `tnRequest` with TTL cache, profiling switch, warm cron | Warm calls under 10 ms; errors return empty arrays; profiling log shows hit rates |
| 2. Discovery pages | Hub, category listings, performer, venue, city; From prices; Deal badge; internal links | Pages render from cache in under 100 ms, no PHP/JS errors, counts match the API |
| 3. Event page | Seat-map widget, environment-safe config, fallback block, dataLayer events | A real event shows ticket groups; expired and no-inventory events show your fallback |
| 4. Checkout + confirmation | `/checkout`, hosted-checkout hand-off, `/order-confirmation`, purchase event | A test order completes end to end in sandbox and lands on your confirmation page |
| 5. Travel integration | Events-during-your-trip module, destination mapping, cross-sell | Module appears after a flight/hotel search with events matching the dates |
| 6. Images, SEO, compliance | Image queue and admin, schema, sitemap, legal review, consent | Section 12 items signed off; Lighthouse SEO 100 |
| 7. Launch | Production credentials and website config, indexing switched on, crons scheduled | First live order reconciled against TicketNetwork's report |

---

## 17. Test plan

1. **Catalog:** for one performer, one venue and one city, confirm the listing count equals `totalCount`, page 2 continues the same sort, and no event dated 2070+ appears on featured feeds.
2. **Cache:** load a page twice with profiling on; the second load shows zero live calls.
3. **Outage:** point the base URL at a dead host; every page still renders (empty state), nothing 500s.
4. **Seat map:** one event with inventory, one expired, one with no inventory; each shows the right state.
5. **Checkout:** sandbox order from Buy click to confirmation page; the analytics events fire in order and `purchase` fires once even after a refresh.
6. **Security:** send `<script>` payloads in every search/filter parameter and confirm they render escaped; confirm `/checkout` and `/order-confirmation` are `noindex`.
7. **Mobile:** 375 px width and a foldable width (280 px) with no horizontal overflow on listing, event, checkout and confirmation.
8. **Lighthouse** on hub, performer, event, city: performance, accessibility, SEO.

---

## Appendix A: environment settings

| Setting | Purpose |
|---|---|
| `BASE_URL` | Catalog host: sandbox `https://sandbox.tn-apis.com`, production `https://www.tn-apis.com` |
| `WEBSITE_CONFIG_ID` | Follows `BASE_URL` (sandbox and live configs differ) |
| `CONSUMER_KEY`, `CONSUMER_SECRET` | OAuth client credentials, secrets only |
| `TN_CHECKOUT_URL` | Hosted checkout host for the hand-off |
| `GTM_ID` | Google Tag Manager container |
| `SITE_INDEXABLE` | Force indexing on or off (default: off on beta/staging/dev hosts) |
| `TN_PROFILE` | File path; logs calls, hits and milliseconds per request |
| `THESPORTSDB_KEY`, `PEXELS_API_KEY`, `UNSPLASH_ACCESS_KEY`, `PIXABAY_API_KEY` | Optional image sources |
| `MAXMIND_ACCOUNT_ID`, `MAXMIND_LICENSE_KEY`, `GEOIP_DB_PATH` | Local geo-IP database |

## Appendix B: Seat Outlet files to use as a reference

(Repository `M-anil111/seat-outlet`, merged from pull request 1.)

| Area | File |
|---|---|
| Request layer, cache, price helpers, location links | `functions.php` (`tnRequest`, `getTnAccessToken`, `categoryListingParams`, `eventDealInfo`, `renderLocationCategoryLinks`) |
| Event page + seat map hooks | `event.php` |
| Order review, confirmation | `checkout.php`, `order-confirmation.php` |
| Images | `inc/images.php`, `cron/resolve-images.php`, `ajax/get-images.php`, `admin/images*.php` |
| Geo-IP | `inc/geoip.php`, `cron/geoip-update.php` |
| Cache warming | `cron/warm-listings.php` |
| Pipeline notes | `CONTRIBUTING.md` (TicketNetwork calls, purchase flow, images, geo-IP sections) |

## Appendix C: mistakes to not repeat (each one happened)

1. Ascending `salesRank` sort returns an alphabetical baseline, not top sellers.
2. `perPage=500` silently caps at 200 and under-reports counts.
3. A second API call just to count results; `includeTotalCount` on the list call does it.
4. APCu-only caching on a host without APCu: no caching at all in production.
5. Uncaught exceptions on API failure taking down entire sections of the site.
6. Sandbox event IDs used with a live (or different) website config: every event "expired".
7. Hosted-checkout hostname configured but with no DNS record: buyers cannot pay.
8. `noindex nofollow` hard-coded on every page and forgotten.
9. Wikipedia's lead image used regardless of license, generic fallback cached forever after one 429.
10. Promo-code and "verified savings" copy for codes the checkout never applied.
11. Placeholder "Sponsored advertisement" images left on production pages.
12. Unescaped request values echoed into HTML.
13. Fake aggregate rating hard-coded into structured data.
14. Quoted radius in `geoFilter` (`'50mi'`) returns HTTP 400.
15. Separate token stores per process: each fresh token invalidates the others, causing intermittent 401s.
16. One API request per autocomplete suggestion; `filter=id in (...)` does it in one call.
