# Seat Outlet build brief: everything to recreate on TicketScanner.ca (any stack)

How to use this: paste this whole file into a new chat for TicketScanner.ca, then add the answers to the section "Answers I must give first". The brief describes what each page and system must do and the rules behind it. It is written for any stack (no PHP). Where Seat Outlet used a specific tool, the brief says so and gives the neutral requirement.

Everything here exists and works in the Seat Outlet repository (github.com/M-anil111/seat-outlet, branch `main`). File names in brackets point to the working reference code, so the new chat can read them if it has access. Source of truth for rules: `docs/seo/page-spec.md`, `docs/seo/titles-and-metas.md`, `docs/seo/keyword-master-list.md`, `docs/seo/directories.md`, `docs/seo/sitemap-requirements.md`.

---

## 0. Answers I must give first (the new chat must ask for these before building)

1. Where do events, prices, venues and performers come from on TicketScanner.ca (API, feed, database)? Seat Outlet uses the TicketNetwork catalog API. Every page below depends on this data. Fields needed per event: name, date and time, venue name and address, city, state/province, country, category path, performer(s), lowest price, whether tickets are listed, event URL for checkout.
2. Who fulfils orders and what must the site say about it? (Seat Outlet says "buy at Seat Outlet" in titles and keeps a fulfillment disclosure in body copy and footer.)
3. Stack, hosting, CMS and database. Is there a sitemap tool, a CDN (Cloudflare?) and a staging site?
4. Primary market: Canada first? Currency (CAD), tax and fee wording, French pages needed or not?
5. Are the promo codes TAKE5 and TAKE10 valid on TicketScanner.ca? (On Seat Outlet they are unconfirmed with TicketNetwork. Do not publish codes that are not confirmed.)
6. Contact email, legal wording and which policies exist. (Seat Outlet shows only info@seatoutlet.com. No phone, no postal address, no company-number placeholders.)
7. Analytics: GA4/GTM container id, Search Console, ad network (AdSense), error monitoring.

If the new chat cannot get an answer, it must say what it assumed. Never invent facts, prices, dates, reviews, statistics or policies.

---

## 1. Principles that apply to every page

- **Goal is revenue, not traffic:** every page leads to a ticket purchase or a sign-up. Track both.
- **One keyword, one page.** Two pages must never target the same keyword. Decide the winner and give the other page a different angle. URLs must never collide.
- **No numeric ids in URLs.** URLs are readable slugs. Keep a slug registry (slug <-> id) and 301 old id URLs to the slug URL.
- **Titles and metas:** the rules in section 3 are enforced by a normalizer in code plus an automated check in CI. Do not rely on writers following them.
- **No fabricated content:** no invented testimonials, ratings, counts, awards, or "verified" claims. If a fact is not in the data, the section is left out, not filled.
- **Honest urgency:** "before they sell out" appears only when tickets are listed. A page with no tickets listed gets an alert sign-up line instead.
- **Thin pages stay out of search:** a generated page with fewer than 3 events is `noindex` and left out of the XML sitemap.
- **US English, no em dashes, no pipes in titles.** (TicketScanner.ca owner may want Canadian spelling: ask.)
- **Mobile first.** Test 280 to 1440 px. One H1 per page. No horizontal scroll.
- **Everything dynamic.** New events, cities and performers create or update pages and sitemap entries automatically. Nothing is hard-coded per page.

---

## 2. URL map (page types to build)

Seat Outlet URL -> what it is. Use the same patterns on TicketScanner.ca unless the stack forces a change (state the change and keep it consistent).

| Pattern | Page |
|---|---|
| `/` | Home |
| `/event/<event-slug>` | One event (slug ends in city, state and date, so repeat dates never collide) |
| `/artist/<performer-slug>` | Performer / team / show: tour or schedule page |
| `/artist-city/<performer-slug>/<city-slug>` | Performer in a city (also `-venue`, `-state`, `-country` variants) |
| `/city/<city-slug>`, `/state/<state-slug>`, `/country/<country-slug>` | Location pages (all events there) |
| `/venue/<venue-slug>` | Venue page |
| `/category/<category-slug>` | Category/genre pages |
| `/concerts-city/<city>`, `/sports-city/<city>`, `/theater-city/<city>`, `/festivals-city/<city>` | Category in a city. Also `-state`, `-country`, `-venue` |
| `/last-minute-tickets/<city>`, `/weekend-events/<city>`, `/cheap-tickets/<city>`, `/best-events/<city>` | City discovery pages |
| `/<holiday>-in-<city-slug>` | Holiday page per city, e.g. `/july-4th-events-in-austin-tx`; hub `/holiday-events` |
| `/concert-artists`, `/sports-teams`, `/nfl-teams`, `/nba-teams`, `/mlb-teams`, `/nhl-teams`, `/mls-teams`, `/broadway-shows`, `/comedians-on-tour`, `/music-festivals-list`, `/all-artists-and-teams` | A to Z directories |
| `/blog`, `/blog/<slug>` | Blog (list, article, category filter `/blog?category=<slug>`) |
| `/policies`, `/privacy-policy`, `/terms-and-conditions`, `/cookie-policy`, `/worry-free-guarantee`, `/tickets-promo-code`, `/ticket-customer-service`, `/about-seat-outlet`, `/sitemap` | Policy and trust pages |

Reference code: `inc/slugs.php` (registry), `event.php`, `performer.php`, `functions.php` (`renderArtistLocationPage`, `renderCategoryLocationPage`), `inc/discovery-pages.php`, `inc/holidays.php`, `all-artists-and-teams.php`, `inc/directories.php`.

---

## 3. Titles, descriptions, headings (the page spec)

### 3.1 Hard rules
- **Titles:** never contain `|`, `-` (hyphen/dash) or `:`. Brand is "at Seat Outlet" (two words; use "at TicketScanner" or the owner's chosen form).
- **Length:** 59 characters max for normal pages. Event pages and performer-in-location pages may run to 70. If a long title does not fit, fall back through shorter variants in a fixed order; the date-bearing variant comes before the date-less ones so repeat dates stay unique (this fixed 24 duplicate-title errors in the SE Ranking audit).
- **Descriptions:** 120 to 155 characters, include a call to action, add "before they sell out" only when tickets are listed.
- **Wording:** titles say "Buy ... Tickets" (customers check out on the site). Fulfillment disclosure stays in the body and footer.
- **One H1 per page**, containing the focus keyword.
- **Keyword in the H1, first paragraph, a H2, alt text and the meta description.** Do not stuff.
- A CI check prevents bad titles from shipping (`tools/check-seo-titles.php`).

### 3.2 Event page templates (by kind)
Kind is detected from the category path: concert, sports, theater, festival (comedy uses theater wording; festivals also count as concerts for location pages).

| Kind | Title (fallback order, first that fits) | H1 |
|---|---|---|
| Concert | `Buy <Performer> Tickets for <Venue> Show in <City>, <ST> on <Mon D>` > `Buy <Performer> Tickets for <City> Show on <Mon D>` > `Buy <Performer> Tickets on <Mon D>` > without date variants > `<Performer> Tickets` | `Buy <Performer> Tickets for <Venue> Show in <City>, <ST>` |
| Sports | `Buy <A> vs <B> Game Tickets at <Venue> on <Mon D>` > `Buy <A> vs <B> Tickets in <City> on <Mon D>` > `<A> vs <B> Tickets on <Mon D>` > shorter | `Buy <A> vs <B> Game Tickets at <Venue>` |
| Theater | `Buy <Show> Tickets at <Venue>, <City> on <Mon D>` > `Buy <Show> Tickets in <City> on <Mon D>` > `<Show> Tickets on <Mon D>` > shorter | `Buy <Show> Tickets at <Venue>, <City>` |
| Festival | `Buy <Festival> Passes for <Venue> on <Mon D>` > `<Festival> Passes on <Mon D>` > `Buy <Festival> Passes for <Venue>, <City>` > shorter | `Buy <Festival> Passes for <Venue>, <City>` |

Descriptions: "Buy <Performer> tickets for <Venue> show in <City>. Find great seats and secure your tickets online today at <Brand> before they sell out." (shorter variants for the length limit).

### 3.3 Other page templates
- Performer in a city: "<Performer> Concert Tickets in <City>" ("Tickets in" for sports, shows, festivals).
- Performer (tour page): "<Performer> Tickets".
- Performer in a venue: "at <Venue>" instead of "in <City>".
- City, state, country, venue, category-in-place: `Buy Tickets to <Concerts|Sports Events|Theater|Music Festivals> in <Place>` (country name shortened to fit, e.g. "the US").
- City discovery: `Buy Last Minute Tickets in <City>`, `Buy Tickets for Weekend Events in <City>`, `Buy Cheap Tickets in <City>`, `Buy Tickets to the Best Events in <City>`.
- Holiday: `Buy Tickets to <Holiday> Events in <City>`.
- Directories: "<League> Teams with Tickets and Schedules" etc. (see `inc/seo-keywords.php` for each page's keyword, title, description, volume and difficulty).

### 3.4 Section headings (H2) on event pages
Order: Tickets, How to buy, Promo codes, About, Buyer guarantee, FAQs, City info, then by kind (tour dates / games in the city / shows in the city / lineup), Guide, Similar events. Each is built only from data the page holds and omitted when there is no data (no bio means no About; no events in the city means no City info). Sheet wording example (concert): "Get Tickets for <Performer> at <Venue>", "<Performer> Promo Codes for <Venue> Show", "FAQs about <Performer> Tickets at <Venue>", "What to Know Before Attending at <Venue> in <City>". Full set per kind: `soSpecEventText()` in `inc/page-spec.php`.

FAQ questions are H3 inside a collapsible element, section title is H2, answers are plain paragraphs.

---

## 4. Promo-code block (every ticket page)

- Section named for the page subject: "Promo codes for <Performer> tickets".
- Two codes, same on every page, if the owner confirms them for this site: **TAKE5** (5% off orders of $199 or more) and **TAKE10** (10% off orders of $349 or more). A copy button for each. Say terms apply, codes can change or stop, and whether a code applies is decided at checkout. Link to the promo-codes page with the full terms.
- Track `promo_copy` and `buy_click` events to GTM/GA4.
- Reference: `soSpecPromoHtml()`.

---

## 5. Structured data (JSON-LD, one `@graph` per page)

Build one graph per page with linked nodes (WebSite, Organization, WebPage, BreadcrumbList, then the page entity).
- Event pages: most specific type: `MusicEvent`, `SportsEvent` (two teams as `competitor`), `TheaterEvent`, `ComedyEvent`, `Festival`. Include `startDate`, `endDate` (typical duration if unknown: say so in code comments, Google treats it as optional), `location` (Place + PostalAddress), `performer`, `organizer`, `offers` (price, currency, availability, url, validFrom), `eventStatus`, `eventAttendanceMode`, `inLanguage`, `isAccessibleForFree: false`, `image`.
- Event pages also get an `ItemList` of similar events; performer-in-city pages get an `ItemList` of listed events; `about` on the page node.
- FAQPage from the visible FAQ (Google shows no rich result for most sites now; still valid).
- Venue: `Place`/`EventVenue` with address and geo. Performer: `Person`/`MusicGroup`/`SportsTeam` with `sameAs` links (Wikidata, official site, from a Wikidata lookup).
- Blog: `Article` with author, dates, image. Breadcrumbs everywhere.
- Do not use `HowTo` (Google no longer shows it). Do not invent `aggregateRating`/`review`.
- Reference: `inc/seo-event.php` and the other `inc/seo-*.php` files.

---

## 6. Images and alt text

- Alt text pattern: "<Performer> live concert in <City>", "<A vs B> game in <City>", "<Show> live show in <City>", "<Festival> festival in <City>".
- Unique image per event/performer/venue where licensed. Store source, license, attribution; show a visible credit link for CC BY / BY-SA images. Photo credits page lists them (noindex).
- Serve WebP, explicit width/height, lazy-load below the fold, preload the LCP image.
- Reference: `inc/images.php`, `cron/resolve-images.php`, `image-credits.php`.

---

## 7. Page-by-page content spec

### 7.1 Event page
Top: title, date/time, venue, city, urgency line, price-from, "How many tickets?" selector (1 to 6 and 6+), listing cards with transparent price rating, seat map widget from the ticket provider. Then sections in 3.4. Includes promo block, buyer guarantee block (link to guarantee page), price-drop alert sign-up (email, daily cron), city info (venue address, directions, transit, official site and weather links; nothing about parking or bag rules unless the data holds it), similar events, internal links to performer, city, venue and category. No H1 duplicated by the ticket widget (demote the widget's heading).

### 7.2 Performer page (`/artist/...`)
Next-event card, listing rows (sort chips: Soonest, Lowest price), by-city / by-venue / by-state boxes, FAQs, "fans also love", tour dates table (keyboard-focusable scroll container with a label), promo block, schedule or tour H2 by kind.

### 7.3 Performer in a city / venue / state / country
Same template as performer but filtered; title and H1 use the place; unique intro paragraph built from the data (count of events, date range, venues).

### 7.4 City, state, country, venue, category-in-place pages
Hero card, filter chips, date-tile event rows, "near you" first then all, See more, promo block named for the place, FAQs named for the place ("FAQs about <Place> Tickets"). Venue page adds "About <Venue> in <City>, <ST>" and "Top Upcoming Performances at <Venue>", plus a seating-chart FAQ that points visitors to an event seat map (only claim what the data holds).

### 7.5 City discovery pages
- Last minute: events in the next 7 days. Weekend: Fri to Sun. Cheap: lowest price first. Best: best sellers.
- noindex and out of the sitemap with fewer than 3 events. No VIP or floor pages unless the data says which listings are VIP or floor.

### 7.6 Holiday pages (every city, US and Canada)
17 holidays, each page lists the city's events inside the holiday's date window for the next time it comes round (dates computed from rules so they stay right every year). Pages with fewer than 3 events are noindex and out of the sitemap. Hub `/holiday-events` lists every holiday with its dates and top cities. Holidays and rules (window = days before and after the day itself): Christmas (category-based, no dates), New Year's Eve (Dec 31, 1 before / 1 after), Valentine's Day (Feb 14, 1/1), St. Patrick's Day (Mar 17, 2/0), Easter weekend (Easter Sunday, 2/1), Mother's Day (2nd Sunday of May, 2/0), Memorial Day (US, last Monday of May, 3/0), Victoria Day (Canada, Monday before May 25, 3/0), Father's Day (3rd Sunday of June, 2/0), Canada Day (Canada, Jul 1, 1/1), July 4th (US, 1/1), Labor Day (US, 1st Monday of Sept, 3/0), Labour Day (Canada, same date), Halloween (Oct 31, 6/0), Thanksgiving (US, 4th Thursday of Nov, 1/3), Canadian Thanksgiving (2nd Monday of Oct, 3/0), Boxing Day (Canada, Dec 26, 0/1). **For TicketScanner.ca, Canada is the main market: list Canadian holidays first and keep US holidays only if US events are on the site.** Exact rule table: `SO_HOLIDAYS` in `inc/holidays.php`; test: `tools/test-holidays.php`.

### 7.7 A to Z directories
Each type has its own crawlable list with its own title, description, focus keyword and SEO copy: artists, comedians, festivals, Broadway/shows, sports teams, and one page per league (NFL, NBA, MLB, NHL, MLS; for Canada add CFL, CPHL/PWHL if the data has them). Letter filter and pagination, each its own address with its own title. Only names with tickets on sale are listed. Footer "Browse by name" row, mega-menu column, links from category and performer pages.

### 7.8 Home page
Hero with search, category tabs (concerts, sports, theater, festivals), "Last-minute tickets" and "Trending events" rows with location from IP/geolocation (no permission prompt first; city fallback), popular cities, trust block (guarantee, honest disclosures, no invented ratings), alerts sign-up band (full-width background, content in the container), partners. Idle nudge popup (once per visit, honest copy, key pages only).

### 7.9 Blog
List, article, categories, author box, table of contents, FAQ block (FAQ schema), live listings block (a short code that pulls current events for a performer/category), CTA box, newsletter box, featured image with alt text. HTML sitemap lists only the blog hub and blog categories, never individual posts. Posts already written (reuse the structure): the ticket buying guide cluster (pillar plus eight guides) and eight buying-intent posts: where to buy cheap concert tickets, concert tickets with no fees, how to get a ticket refund, face value tickets, is ticket insurance worth it, dynamic pricing, concerts in October, concerts in November. Rules for posts: 1,800+ words, 8 FAQs, 4+ internal links, live event block, sources only from official pages (ftc.gov, the ticket partner's policy page), no invented dates or prices, no claims about other sellers' policies unless verified from their official help pages. Keyword plan: `docs/seo/keyword-master-list.md` (volume and difficulty per keyword, priority, which page owns it). For Canada, re-research keywords (Canadian volumes) and cite Canadian rules (for example the Competition Bureau and provincial rules) instead of FTC; verify before publishing.

### 7.10 Policy and trust pages
- `/policies`: a page that loads the ticket partner's policy script (Seat Outlet: TicketNetwork `bid=9250`, `sitenumber=30`, `tid=600`; TicketScanner.ca needs its own values from the partner) plus links to terms, privacy and guarantee; no-JavaScript fallback. The partner uses this URL on checkout.
- Privacy, Terms, Cookie pages: no placeholders left in brackets. Contact shows only the support email (info@seatoutlet.com on Seat Outlet). No phone number, no postal address, no company-number lines unless the owner supplies real ones.
- Guarantee page, promo codes page, customer service page with contact form, about page, photo credits page, HTML sitemap (`/sitemap`): menu sections, blog hub and blog categories, "More pages".

---

## 8. Sitemaps and robots (must be dynamic; no static lists)

- **One XML sitemap index** at `/sitemaps/sitemap.xml` and **one HTML sitemap** at `/sitemap`. No `.php`-style sitemap URL anywhere. Old addresses 301 to these two.
- Child sitemaps are built by a scheduled job (every 6 hours) that crawls the data source: `pages-N`, `events-N`, `performers-N`, `venues-N`, `cities-N`, **`holiday-events-N`** (a separate sitemap just for holiday pages), 5,000 URLs per file, `lastmod` on every entry, only canonical indexable 200 URLs (no redirects, no noindex, no thin pages). A browser stylesheet makes the XML readable.
- Pages file is built by scanning the site's own routes, so a new page appears with no manual step. Admin and utility pages are excluded.
- `robots.txt`: allow all, disallow admin/ajax/cache/vendor/db/tools/cron/deploy/docs/inc/search/checkout/newsletter/unsubscribe/thank-you/order-confirmation, plus the sitemap line. Staging site: `Disallow: /` and `noindex`.
- A CLI tool prints the real URL count per holiday after each build (`tools/list-holiday-urls.php`).
- IndexNow ping job for new URLs (optional). Submit the index once in Search Console.
- **Do not put sitemap logic in a CDN/Worker.** On Seat Outlet a Cloudflare Worker generated static sitemaps and overrode the dynamic ones; that is why the sitemap went stale. Keep the CDN as a pure pass-through.
- Reference: `inc/sitemap-build.php`, `sitemap.php`, `robots.php`, `cron/build-sitemaps.php`, `docs/seo/sitemap-requirements.md`.

---

## 9. Internal linking

Closed loops: event <-> performer <-> performer-in-city <-> city <-> venue <-> category <-> directory <-> holiday and discovery pages <-> blog. Footer "Browse by name" row, mega-menu column "Artists & Teams", HTML sitemap built from the menu, category/performer pages show a "Browse by name" line chosen from the page's branch of the category tree. Every page links up (breadcrumb) and sideways (similar events, nearby cities, other holidays, other discovery pages).

---

## 10. Accessibility (WCAG 2.2 AA)

Audited with axe-core on ~40 page types at mobile and desktop. Rules that came out of it:
- Links inside paragraphs, lists and small text are underlined (not colour only).
- Main landmark is named after the page title (`aria-labelledby` the H1); the top strip with the H1 is a labelled region.
- Every landmark with the same role has a unique accessible name (ads: "Advertisement 1", "Advertisement 2"; scrollable tables: "Table 1, scrollable").
- Scrollable tables are keyboard focusable (`tabindex=0`, `role=region`, label).
- No skipped heading levels. Loading placeholders are not links. Tap targets at least 24 px (add padding on inline links in rows). Contrast 4.5:1 minimum (secondary text `#5f6b7c` on white).
- Skip link to main content. Form fields have labels and error text. Visible focus ring.

---

## 11. Security headers and CSP

Send on every public page: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(self)`. HSTS from the CDN (preload only when every subdomain is HTTPS). Add the same first three to static files at the CDN or web server. Content-Security-Policy starts in **report-only** mode with a reporting endpoint that logs a short line per violation; read a week of reports (a summary script groups by directive and host), add legitimate hosts (analytics, ads, ticket partner), then switch on enforcement with one setting. Never ship enforcement first. Also: input validation, CSRF tokens on forms, rate limits on sign-up and search, no secrets in the repo, admin behind login, price-alert and newsletter emails with unsubscribe, no customer data in logs.

---

## 12. Performance and caching

- HTML: `Cache-Control: public, max-age=0, s-maxage=120, stale-while-revalidate=600, stale-if-error=3600` for 200 pages; `no-store` for checkout, account and degraded pages (built without API data). Serve a stale snapshot when the data source is down instead of a 5xx.
- Static: hashed or versioned CSS/JS with `max-age=31536000, immutable`; images 30 days; fonts and libraries immutable. Gzip/Brotli.
- CSS split per page type (home, event, browse, entity, directory, blog, content, checkout), critical CSS inlined for the home page, below-the-fold CSS deferred; a build step regenerates bundles and CI fails if they are stale.
- Lazy-load date pickers and carousels; minify every vendor CSS file (the unminified carousel CSS was flagged on 125 pages).
- Targets: Lighthouse mobile 90+ on all key pages, LCP under 2.5 s, CLS under 0.1, INP under 200 ms. Real-user vitals collected and reported.
- API calls: cache with short TTL, circuit breaker when the provider is failing, warm caches by cron, never let a failing API produce a cached error page.

---

## 13. Tracking and measurement

GA4 through GTM only (one container; `off` on staging). Events: `buy_click`, `promo_copy`, `select_item` (listing click), `begin_checkout`, `purchase`, `newsletter_signup`, `price_alert_signup`, search and filter usage. Conversion tracking audited before any ad spend. Microsoft Clarity optional. Search Console: submit the sitemap index, watch coverage for noindex and 404s. Weekly report: clicks, impressions, conversions by page type, top landing pages, wins, risks, next actions. Note: a Search Console message such as "No clicks for this time period" on a new site means indexing has not happened yet; check that the live site is indexable and the sitemap is submitted.

---

## 14. Email, alerts and retention

Newsletter and "alerts for tours and on-sales" sign-up (home band and blog). Price-drop alerts on event pages (stored alert, cron checks price, email with unsubscribe). Welcome email, abandoned-search reminders optional. Customer email is off on staging. Loyalty and SMS only after the owner approves consent wording.

---

## 15. Ads (optional)

AdSense banner placements (`banner`, `mid`, `foot`, `pre`, `listing`, `side2`) with fixed sizes, loaded after first interaction or 15 s, only when consent allows; placeholders only on staging. Keep layout stable (reserve ad space) so CLS stays low.

---

## 16. Environments, deploy and quality gates

- **Two sites:** staging (beta) and live, each with its own database, secrets and analytics id. Staging is noindex, blocked in robots, sends no customer email. Live deploys only on promote of a commit whose CI is green; rollback by promoting an older commit. Database changes are add-only so rollback works.
- **CI checks that fail the build:** syntax/lint, undefined functions and wrong argument counts, title and description rules, no numeric ids in URLs, slug tests, holiday date tests, sitemap pages scan, stylesheets up to date, CSS groups include every template.
- Secrets live outside the web root; error monitoring (Sentry) with environment names; nightly database backup; image resolution cron; geo-IP database refresh weekly.
- Run the SE Ranking audit after launch and fix every error (the Seat Outlet audit scored 83 on its first run; causes were old numeric-id URLs, origin errors, duplicate titles, unminified CSS, and a Worker-built stub for blog posts).

---

## 17. What not to copy

- TicketNetwork-specific API code, the TicketNetwork policy script ids, the `TAKE5`/`TAKE10` codes (unless the owner confirms them for this site), brand text "Seat Outlet", the seatoutlet.com domain in schema and canonical tags, the info@seatoutlet.com address.
- US-only assumptions (US holidays, FTC citations, US league list, dollar amounts). Replace with Canadian equivalents and verify sources.
- The Cloudflare Worker (it was a workaround; do not rebuild it).

---

## 18. Suggested build order for TicketScanner.ca

1. Data layer and slug registry (events, performers, venues, cities) with caching and fallback.
2. Title/meta normalizer plus CI check, JSON-LD builder, breadcrumb and canonical logic.
3. Event page and performer page, then performer-in-city, city, venue, category pages.
4. Promo block, FAQs, buyer guarantee, similar events, internal links.
5. Dynamic sitemaps (including holiday-events) and robots; HTML sitemap.
6. Directories, city discovery pages, holiday pages and hub.
7. Blog, live listings block, the policy pages and `/policies` with the partner script.
8. Accessibility pass (axe), security headers and CSP report-only, performance pass (Lighthouse), tracking.
9. Staging review, audit (SE Ranking, Search Console), promote to live.

Acceptance for each page type: one H1, title and description pass the rules, schema validates in Google's Rich Results Test, axe shows no serious issues, Lighthouse mobile 90+, page is in the sitemap only if indexable, internal links resolve with 200.

---

## 19. Seat Outlet reference numbers (so the new chat can sanity check scale)

On the Seat Outlet mock/dev data: 134 static pages, about 3,200 events, 2,000 performers, 1,200 venues and 1,100 cities in the sitemaps; 17 holiday pages in the first build (the real count appears once the live build runs). The live SE Ranking audit crawled 1,000 pages and scored 83 before the fixes in this brief.
