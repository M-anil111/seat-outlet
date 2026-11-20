# Seat Outlet product enhancements (20 Nov 2026)

About 200 enhancements, grouped by what they move, each with how to build it in this codebase. Written for the owner and the developers.

## How to read this

- **Size:** S is under a day, M is a few days, L is weeks.
- **[OWNER]** means it touches the TicketNetwork call budget, a production setting, pricing, a legal statement or the brand. It needs the owner's approval first (see `CLAUDE.md`).
- **[VERIFY]** means a platform rule, a legal point or a TicketNetwork behavior must be confirmed against the primary source before it ships. Any quoted figure or price is an existing baseline or an example with its source and date (the Search Console exports and SE Ranking audits of 14 to 18 Nov 2026, code comments, or the live site on 20 Nov), not an invented target; every "why" names the metric to watch, and the target is set after a baseline is measured.
- **Metric** is the number that proves the change worked. Where tracking does not exist yet, item E164 to E173 builds it first.
- Where an item says a piece "already exists", it extends that piece and does not rebuild it (E004 to E009, E143, E158, E159, E164 were rewritten after checking the JavaScript on 20 Nov). Existing pieces named here: `inc/leads-core.php` (sign-ups), `cron/send-price-alerts.php`, `cron/warm-event-details.php`, `cron/indexnow.php`, `inc/event-redirects.php`, `inc/tn-bulk-read.php` (catalog copy), `inc/sitemap-build.php`, `inc/seo-*.php`, `inc/page-spec.php`, `inc/adsense.php`, the admin CMS (`admin/`), and the order webhook in `ajax/`.
- **The order webhook is thin today.** `ajax/tn-order-webhook.php` accepts only `order_id`, `event_id`, `total` and `currency`, stored in `verified_orders`. It has no buyer email, no order status and no click id. Items that need any of those (E008, E017, E018, E092, E098, E099, E111) first need that data to reach Seat Outlet, by extending the signed payload with TicketNetwork or by another verified route; plan that contract before the item.
- Rules that apply to every item: no new TicketNetwork call on a hot path without reading `docs/reliability.md`; no full import and no change to the hourly bulk cron without approval; no production migration without a database dump first; one small pull request per item with a test listed in `ci.yml`.

---

## A. Conversion and checkout (E001 to E020)

**E001. Lowest price in the event title strip** [S]. Metric: click-through from event page to the seat map or checkout. How: when the live record carries `pricingInfo.lowPrice`, print "From $X" in `.so-evbar__facts` in `event.php`; hide it for saved copies (the page already drops prices then).

**E002. Sticky mobile "See tickets" bar on event pages** [S] [VERIFY]. Metric: seat-map interaction rate on mobile. How: first check whether the seat-map widget already shows its own mobile buy bar; if not, a fixed bar in `event.php` with an anchor to `#tn-maps`, shown only after the title strip scrolls out (IntersectionObserver in `js/event-actions.js`), `padding-bottom` on the body so it never covers the footer.

**E003. Fee and price disclosure line near the seat map** [S] [VERIFY]. Metric: checkout abandonment, support tickets about "extra fees". How: confirm what TicketNetwork's checkout shows (fees included or added) from its documentation, then put one accurate sentence in `.so-evbar__note` and the guarantee page.

**E004. Pick up where you left off: widen it** [S]. Metric: return-visit conversion. How: the home page already shows recently viewed events and performers (`index.php`, `js/main.js`, `js/event-actions.js`); extend it to venues and cities viewed, and add a "price dropped" tag only when E096 stores a price history.

**E005. Email option inside the "Still deciding?" card** [M]. Metric: alert sign-ups, then purchases from alert emails. How: `js/idle-nudge.js` already shows one polite card on event and artist pages; add a one-field "email me if the price drops" form (`soLeadForm` with `interest_type=event`, `alert_kind=price` and the current `baseline_price`, as `inc/leads-core.php` expects) that feeds `cron/send-price-alerts.php`.

**E006. Seat-map failure rate in the health view** [S]. Metric: seat-map load success rate. How: `js/event-widget.js` already has a watchdog and sends `widget_load_failed` and `no_inventory` to the data layer. `ajax/vitals.php` accepts only Web Vitals metrics today, so either extend it with an allow-listed event schema and a migration for storage, or add a separate small telemetry endpoint; then show the daily failure rate in the token-protected health view.

**E007. Quantity from the URL** [S] [VERIFY]. Metric: conversion from ads and email. How: the quantity sheet (1 to 6+) exists in `js/event-qty.js`; add `?qty=` to pre-select it so an ad or email can carry the quantity; check that the widget accepts it.

**E008. Match begin_checkout to the order** [M]. Metric: revenue per channel. How: `js/event-widget.js` already pushes `view_item`, `select_item` and `begin_checkout`. The order webhook has no field for a click id, so first confirm that a signed TicketNetwork return or webhook can echo a first-party correlation ID; then store that ID on the Buy click and match it in `ajax/` so each purchase is attributed to its source, medium and campaign.

**E009. Email capture on the saved-event heart** [M]. Metric: sign-ups per 1,000 event views. How: `js/saved-events.js` keeps saved events in the browser only; after the first save offer "email me if this event changes" with `soLeadForm`; one confirmation email through Brevo.

**E010. Promo placement test** [S]. Metric: promo-code redemption, revenue per visit. How: `soPromoCodes()` and `soSpecPromoMarkup()` exist; test the block above and below the fold with the experiment flag from E168. Keep the "no promise of a discount" copy.

**E011. Guarantee badge beside the primary button** [S]. Metric: click-through to checkout. How: reuse `inc/trust-block.php`; a one-line badge linking to `/worry-free-guarantee`, same wording as the page.

**E012. Face-value wording review** [S] [VERIFY]. Metric: complaint rate, legal exposure. How: have counsel confirm the resale disclosure text ("Prices are set by sellers and may be above or below face value") against the rules for each state you sell in.

**E013. Scarcity only from real data** [S] [VERIFY]. Metric: conversion without trust loss. How: `_metadata.ticketCount` is on the event record; show "N listings" only when it is present and current, never an invented count. Skip it for saved copies.

**E014. Rescheduled event detection** [M]. Metric: refund and support volume. How: `event_redirects.event_date` remembers the last date; when the live date differs, show a notice from `inc/event-notice.php` ("The date changed from X to Y") and email alert subscribers.

**E015. Checkout trust bar** [M]. Metric: checkout completion. How: payment methods, guarantee, support hours, and delivery promise on the pages Seat Outlet controls before the hand-off (event page and cart notice); do not edit TicketNetwork's hosted checkout.

**E016. Order confirmation with calendar, directions and parking** [M]. Metric: repeat purchase, partner clicks. How: `checkout.php` and `order-confirmation.php` already exist; add the calendar file (the event page builds one), a map link from the venue's `geoLocation`, and links to parking partners (E019).

**E017. Post-purchase email series** [M] [VERIFY]. Metric: support contacts per order, repeat purchase. How: confirm TicketNetwork can provide verified delivery and fulfillment dates and statuses and the buyer's email in a webhook or supported feed (today's webhook has only order id, event id, total and currency); only then use those fields for Brevo templates with an unsubscribe link in each.

**E018. Order status lookup page** [M] [VERIFY]. Metric: support tickets avoided. How: `verified_orders` stores no email and no status, so first confirm a TicketNetwork-supported status endpoint or webhook payload and a privacy-safe email or order verification field; then build a rate-limited email-plus-order-number form using `inc/order-verify.php` and `inc/request-guard.php`.

**E019. Parking, hotel and ride partner links** [L] [OWNER] [VERIFY]. Metric: partner revenue per order. How: apply to the partner programs you choose, read their terms on linking and disclosure, add disclosed links on the confirmation page and venue pages.

**E020. "Alert me when this performer announces dates"** [M]. Metric: sign-ups from artist pages with no upcoming events (which now render as the no-events page). How: `cron/send-alerts.php` already mails performer interests for upcoming events that have tickets; add the form (`soLeadForm`, `interest_type=performer`) to the no-events page so those visitors can subscribe, and check that the cron's 7-day rule suits it.

---

## B. Speed and reliability (E021 to E036)

**E021. City fallback inside the head batch** [S] [OWNER]. Metric: cold event page time to first byte. How: add the `categoryEventsByLocationSpec` for the city to the `tnRequestMulti` batch in `inc/seo-event.php`; costs one TicketNetwork call on pages where it is not needed, so approve against the budget first.

**E022. Short edge cache for event HTML** [M] [OWNER] [VERIFY]. Metric: time to first byte for repeat visits and crawlers. How: send `s-maxage` with `stale-while-revalidate` for event pages with a catalog answer; confirm no price or personal data is in the HTML first; keep `no-store` for saved copies and 503s.

**E023. Warm the next 14 days of events** [M]. Metric: share of event requests served from cache. How: extend `cron/warm-event-details.php` to walk events sorted by date and by Search Console impressions, inside the cron's half of the budget (it never waits).

**E024. Event gate from the bulk copy first** [M] [OWNER]. Metric: cold event time to first byte, 503 rate. How: when the catalog copy is fresh, answer the gate and head from `tn_bulk_events` and refresh the live record in the background through the existing `tnScheduleRefresh`.

**E025. TicketNetwork latency percentiles on the health detail** [S]. Metric: real per-call latency (the number the profile could not give). How: record call durations in `tnProfile` into the daily stats file and show p50 and p95 per endpoint family in the token-protected health view.

**E026. Server-Timing header for cold pages** [S]. Metric: time spent per stage. How: when `TN_PROFILE` is on or a debug header is sent with the token, emit `Server-Timing` entries from `tnProfile` so a browser's network panel shows each TicketNetwork call.

**E027. Defer the seat-map script** [M] [OWNER]. Metric: Total Blocking Time and interaction delay on event pages. How: load the Seatics script on first scroll or tap or when the map container enters view; trial on the beta host first; keep a visible "Loading seat map" state so no layout shift.

**E028. Preload the hero image on entity pages** [S]. Metric: Largest Contentful Paint on artist, venue and city pages. How: set `$pagePreloadImage` to the selected hero URL before including `header.php`, as `performer.php` already does, so the existing header emits `<link rel="preload" as="image" fetchpriority="high">`; do the same in `venue.php` and `city.php`; confirm the critical-CSS test still passes.

**E029. Visual regression for critical CSS** [M]. Metric: layout shift regressions caught before release. How: extend `tools/test-critical-coverage.php` with screenshots at two widths of the fixture pages and a pixel diff threshold in CI.

**E030. 103 Early Hints at the edge** [S] [VERIFY]. Metric: First Contentful Paint. How: enable in Cloudflare if the plan supports it; list the main stylesheet and font.

**E031. Per-weight font fallback** [M]. Metric: Cumulative Layout Shift on venue and city pages (the font swap was measured as the cause). How: self-host a latin subset of the web font and add fallback faces with `size-adjust` tuned per weight (400, 600, 700), or preload only the regular weight; measure each change with `tools` Lighthouse runs.

**E032. Reserve hero stats height on small screens** [S]. Metric: CLS on venue and city pages. How: `min-height` for two lines in the hero stats block below 576px; accept about 24px of blank space.

**E033. AVIF with responsive sources** [M]. Metric: image bytes per page, LCP. How: extend the image pipeline in `inc/images.php` and `cron/resolve-images.php` to write AVIF beside WebP, `srcset` at three widths, explicit `width` and `height`.

**E034. Shared cache for multiple servers** [L]. Metric: cache hit rate after scaling. How: if the site moves to more than one web server, move `cache/` to shared storage or Redis; `TN_TOKEN_DIR` must point at the same place (TicketNetwork invalidates a token when another is issued).

**E035. PHP-FPM and OPcache tuning from measurement** [M] [OWNER]. Metric: origin response variance (health answered in 0.25 to 2.3 seconds in one minute). How: record worker saturation and slow log for a week, then set `pm.max_children`, OPcache memory and preloading from the numbers; this is a server change.

**E036. External uptime checks** [S]. Metric: time to detect an outage. How: one-minute checks on `/ajax/health.php`, one event URL, one hub and one 404; alert to email or chat; Sentry uptime monitors can do this.

---

## C. Technical SEO (E037 to E056)

**E037. Search Console as a weekly report** [S]. Metric: clicks, impressions, indexed pages, errors by type. How: export Performance and Coverage on a fixed day each week into one sheet; the 18 Nov exports are the baseline (76 clicks and 7,561 impressions in 44 days).

**E038. Index coverage dashboard from the sitemap** [M]. Metric: share of sitemap URLs indexed. How: compare `sitemaps/*.xml` counts with Search Console "Indexed" counts per sitemap file; the sitemap is already split by type, so each type gets its own ratio.

**E039. Add `lastmod` truth to every sitemap URL** [S]. Metric: crawl freshness. How: `inc/sitemap-build.php` already writes `lastmod` for events and entities; make it the date the page content changed (event data change, new events on an artist), not the crawl date.

**E040. Submit and monitor the sitemap index in both Google and Bing** [S]. Metric: discovered versus indexed. How: add `/sitemaps/sitemap.xml` in Search Console and Bing Webmaster Tools; `cron/indexnow.php` already pings IndexNow.

**E041. 410 for permanently dead performer and venue pages** [S]. Metric: crawl waste, 4xx reports. How: `soDeadPerformerIds()` knows dead performers; answer 410 (not 404) after the 3-day dead window so Google drops them faster; keep the redirect for events.

**E042. Canonical and noindex audit job** [M]. Metric: duplicate and non-canonical URLs in the sitemap (SE Ranking checks). How: a cron that fetches a sample of sitemap URLs per type and verifies status 200, a self canonical and no noindex; write failures to the cron log and Sentry.

**E043. Structured data validation in CI** [M]. Metric: rich-result eligibility. How: run the Event, FAQPage, BreadcrumbList and Organization JSON-LD from fixture pages through a schema validator in `tools/`; fail on missing required fields.

**E044. Event rich-result completeness** [S] [VERIFY]. Metric: Event enhancement errors in Search Console. How: `soEventNode()` already emits `eventStatus` and `eventAttendanceMode` and drops markup without an offer (correct); add `previousStartDate` only when E014 detects a reschedule, then check Google's Event structured data documentation for required fields.

**E045. Organization and WebSite schema on the home page** [S]. Metric: knowledge panel signals, sitelinks search box eligibility. How: confirm `WebSite` with `SearchAction` is on the home page; add `Organization` with logo, `sameAs` (the real social profiles) and contact point.

**E046. `llms.txt` and crawler policy file** [S] [VERIFY]. Metric: citations in AI search answers. How: publish a plain `llms.txt` pointing at the guides, FAQ and hub pages; decide per crawler in `robots.txt`; check the current spec before publishing.

**E047. Open Graph and X card defaults per template** [S]. Metric: click-through from shared links. How: `inc/seo-event.php` sets a share image; give hubs, venues and cities a template card with the performer or venue photo when one is licensed.

**E048. Pagination and parameter handling** [M]. Metric: duplicate titles (SE Ranking counted about 2,200 before the page-number fix). How: keep `?page=` noindex,follow after page 1 or canonical to itself; confirm `?when=` date-chip URLs stay nofollow (193 nofollow internal links are intended) and out of the sitemap.

**E049. Internal link depth report** [M]. Metric: pages with one inbound link (11 in the last audit). How: build the link graph from the sitemap and the crawl, list pages under N inbound links, and add links from hubs and "related" blocks.

**E050. Related-entity link blocks** [M]. Metric: crawl depth, pages per session. How: on artist pages add "Similar artists" from the same category path; on venue pages add "Nearby venues" from the same city; on city pages add "Popular this month". All from the catalog copy, no new calls.

**E051. Breadcrumb consistency** [S]. Metric: breadcrumb rich results. How: confirm every template emits visible breadcrumbs that match `BreadcrumbList` (events, artists, venues, cities, categories, blog).

**E052. Core Web Vitals field data tracking** [M]. Metric: LCP, INP and CLS at the 75th percentile from real users. How: `ajax/vitals.php` and `cron/prune-vitals.php` exist; build a small admin view with percentiles per template and device.

**E053. Redirect map for legacy URLs** [S]. Metric: 3xx hops, lost link equity. How: `inc/legacy-redirect.php` exists; audit external backlinks (a backlink tool) for URLs that 404 and add 301s.

**E054. Hreflang decision** [S] [VERIFY]. Metric: none unless you sell outside the US and Canada. How: the crawl is limited to US and CA events; skip hreflang unless a localized version exists. Record the decision so the audit warning is understood.

**E055. Robots meta policy table** [S]. Metric: no accidental noindex. How: write down which templates are indexable, noindex,follow (empty listings, artists with no events, events with no tickets) and noindex,nofollow (checkout, admin); add a test that renders each fixture page and checks the value.

**E056. Log-based crawl analysis** [M]. Metric: share of Googlebot hits on 5xx, redirects and parameter URLs. How: sample the access log weekly, group Googlebot requests by status and path pattern; this finds the next crawl-waste problem before Search Console does.

---

## D. Programmatic and local SEO (E057 to E071)

**E057. Quality gate for every programmatic page type** [M]. Metric: indexed versus crawled-not-indexed ratio per type. How: define a minimum for each template (for a city page: at least N upcoming events and one unique paragraph); below it the page is noindex and out of the sitemap, as zero-result pages already are.

**E058. Unique data blocks on venue pages** [M]. Metric: impressions and clicks on venue queries. How: from the catalog copy compute real facts per venue (events listed this month, busiest weekday, categories hosted, next date); print them as text and as a small table. Only computed data, nothing invented.

**E059. Unique data blocks on city pages** [M]. Metric: city page rankings. How: events by category this month, venues with the most events, a date range; plus an editor-written paragraph for the top 50 cities.

**E060. Artist tour summary** [M]. Metric: artist page clicks. How: for artists with upcoming events, a generated summary of the tour: first and last date, number of cities, states covered, from the catalog copy.

**E061. Venue guides for the top 100 venues by impressions** [L]. Metric: venue page rankings, time on page. How: an editor writes seating, parking, transit, bag policy and accessibility notes from the venue's own website; each fact cites its source and has a "last checked" date.

**E062. Local business profile** [S] [VERIFY]. Metric: branded search and map visibility. How: only if Seat Outlet has a physical business address it is willing to publish; otherwise skip. Create and verify the Google Business Profile, keep name, address and phone identical to the About page.

**E063. "Tickets near me" landing pages by metro** [M]. Metric: queries containing "near me" and the metro name. How: `city-events.php` and the `*-city` pages exist; group cities into metros and create metro pages with events across the metro, canonical to the metro page.

**E064. Date-based landing pages** [M]. Metric: queries like "concerts this weekend in <city>". How: `weekend-events` and `last-minute-tickets` page types exist; add "tonight" and "this month" pages with a minimum event count, refreshed from the copy.

**E065. Category by city matrix with a floor** [S]. Metric: indexed matrix pages that receive impressions. How: `SO_SITEMAP_CITYPAGE_MIN` already sets a floor; raise it if Search Console shows many crawled-not-indexed pages of the type.

**E066. Team and league pages with fixtures** [M]. Metric: sports query coverage. How: league hubs exist (`SO_HUB_LEAGUES`); add schedule tables per team from the copy and `SportsEvent` markup.

**E067. Festival hub pages with lineups** [M] [VERIFY]. Metric: festival queries (for example "music festivals tickets" had 31 impressions). How: from events and performers per festival; lineups only from the catalog; link each performer.

**E068. Holiday and seasonal pages** [M]. Metric: seasonal query clicks. How: `holiday-events.php` and `holiday-city.php` exist; add Christmas, New Year, Valentine, summer tours with fixed URLs that stay live all year and are refreshed before the season.

**E069. Venue-to-venue and city-to-city comparisons** [M]. Metric: long-tail clicks. How: only for pairs with real shared data, such as "Madison Square Garden vs Barclays Center seating"; requires researched facts per venue (E061).

**E070. Internal anchor text rules** [S]. Metric: ranking for exact queries. How: link to artist pages with the plain "<Artist> tickets" anchor and to cities with "events in <City>"; one script scans templates for generic anchors like "click here" and "more".

**E071. Search query to page mapping** [M]. Metric: keyword cannibalization count. How: from Search Console export, map each query to the page that ranks; where two of your pages compete (event versus artist), set the canonical owner as the spec already does for focus keywords.

---

## E. Content and editorial (E072 to E091)

**E072. Editorial calendar with a fixed cadence** [M] [OWNER]. Metric: dated posts per month, impressions on blog pages (none of the top 1,000 pages were posts on 18 Nov). How: the owner picks the cadence; plan 12 weeks in `docs/`; each post has an owner, a source list and a review date.

**E073. Named author and editor profile pages** [S]. Metric: trust signals, AdSense review. How: real names and short bios from the team; `Person` schema on posts; an About page section on how guides are researched.

**E074. Editorial policy page** [S] [OWNER]. Metric: trust and review outcome. How: one page on how content is written and fact checked, how errors are corrected, and the resale disclosure; the owner writes or approves every statement.

**E075. Guides from queries that already show impressions** [M]. Metric: clicks on the guide, then clicks into hubs. How: horse racing tickets, music festivals tickets, Wembley tickets, Ireland Six Nations tickets, how to buy event tickets online: each guide answers the query, names primary sources, and links to the matching hub and events.

**E076. How-to-buy guides by sport and genre** [M]. Metric: organic entrances to hubs. How: for NFL, NBA, MLB, NHL, MLS, tennis, country, hip hop and Broadway: how resale works, how to pick seats, when prices tend to change (only claims you can source), refund rules.

**E077. Venue seating guides with a seat-view explainer** [L]. Metric: venue page engagement. How: pair E061 with the seat map: explain sections, rows and typical views using the venue's published information; never promise a view.

**E078. Tour announcement posts** [M]. Metric: impressions on new-tour queries. How: when a headline artist's dates appear in the catalog, publish a short factual post with the dates, cities and link to the artist page; a draft can be generated from the data and reviewed by an editor.

**E079. Evergreen refund and protection explainers** [S]. Metric: support tickets avoided, conversion. How: tie directly to `/worry-free-guarantee` and `/ticket-faq`; add `FAQPage` markup that matches visible text.

**E080. Annual calendar posts** [M]. Metric: seasonal clicks. How: "Concerts in <month>" posts exist for October and November; keep one per month with a stable URL and update it each month instead of creating a new URL.

**E081. Update stale posts on a schedule** [S]. Metric: freshness, rankings. How: a monthly check of every post's facts and dates; the admin CMS shows `updated_at`; edit and republish, do not only change the date.

**E082. Customer stories with real orders** [M] [OWNER]. Metric: conversion on hubs. How: only with written permission from real customers; no composite or invented quotes.

**E083. Transparent comparison page: resale versus box office** [M] [VERIFY]. Metric: informational query clicks. How: explain the difference accurately, cite the primary sources, avoid naming competitors in a misleading way.

**E084. Glossary** [M]. Metric: long-tail impressions. How: face value, general admission, will call, mobile delivery, resale; each term with a short definition and a link to a guide.

**E085. Accessibility ticket guide** [M] [VERIFY]. Metric: trust, long-tail queries. How: explain accessible seating requests with sources from venues; add a contact route in customer service.

**E086. Video summaries of the top guides** [L]. Metric: video views, clicks to the page. How: short narrated screen recordings with captions; embed with lazy loading so speed is unaffected.

**E087. Podcast or newsletter of "what is on this week"** [M]. Metric: newsletter sign-ups, returning visits. How: generate the draft weekly from catalog data by city; an editor adds two lines; send through Brevo (see F).

**E088. Content scorecard in the admin** [M]. Metric: share of posts with clicks. How: `admin/seo-scores.php` exists; add Search Console clicks and impressions per post to the list so editors see what works.

**E089. Source citation component** [S]. Metric: trust, review outcomes. How: a "Sources" block in `inc/blog-render.php` with title, publisher and access date; required by the admin form before publish.

**E090. Content refresh of thin hubs** [M]. Metric: hub rankings. How: `inc/seo-copy/` holds hub copy; check each hub for unique intro text; rewrite the weakest first using Search Console queries as a guide.

**E091. Image credits and alt text audit** [S]. Metric: image search traffic, accessibility. How: `/image-credits` exists; make sure every photo has a credit, a licence note and descriptive alt text; the SE Ranking alt-text check already passes, keep it passing.

---

## F. Email, SMS, CRM and lifecycle (E092 to E109)

**E092. One customer record across sign-ups, alerts and orders** [L] [VERIFY]. Metric: repeat purchase rate. How: the webhook and `verified_orders` hold no email or hash, so first obtain an owner- and TicketNetwork-approved customer identifier in a supported order feed; then join it to `inc/leads-core.php` in a table or a Brevo contact with attributes (interests, last order, city).

**E093. Welcome series for new subscribers** [M]. Metric: second-visit rate, first purchase. How: three Brevo emails over ten days (what Seat Outlet is and the guarantee, how to pick seats, top events in the subscriber's city); consent text must match `privacy-policy.php`.

**E094. Interest-based segments** [M]. Metric: email click rate, revenue per send. How: `interest_type` and `interest_id` are already stored (event, performer, city, category); sync them as Brevo attributes and send only matching content.

**E095. Weekly "on sale in your city" email** [M]. Metric: revenue per send. How: generate from the catalog copy by subscriber city (GeoIP at sign-up, `cron/geoip-update.php` exists); top 8 events with real dates and links; sent on a fixed weekday.

**E096. Price-alert upgrade with a visible price history** [M] [VERIFY]. Metric: alert-to-purchase rate. How: `cron/send-price-alerts.php` exists; store a daily low price per event in a small table and show "was $X, now $Y" only from stored data; migration needs a database dump first.

**E097. Back-in-stock alert for events with no tickets** [S]. Metric: conversions from "Notify me". How: the empty-event form stores an event interest with no alert kind, and no cron reads it; add a stock-alert kind to `lead_interests` (migration after a dump), then add or extend a cron that sends when the event's `hasTickets` turns true.

**E098. Cart and browse recovery** [M]. Metric: recovered revenue. How: hand-off click events (E008) plus an email only for people who gave consent; one email after a few hours, one after a day, then stop. Needs the buyer's email to reach Seat Outlet first (see the note on the order webhook above).

**E099. Post-event email: "see them again"** [M]. Metric: repeat purchases. How: the day after an event, email buyers the performer's next dates from the catalog; skip if none. Needs the buyer's email to reach Seat Outlet first (see the note on the order webhook above).

**E100. SMS alerts opt-in** [L] [OWNER] [VERIFY]. Metric: alert-to-purchase rate. How: choose a provider, read the carrier and TCPA rules for consent and quiet hours, add a separate SMS consent checkbox and a stop keyword, then add SMS-specific disclosures to the privacy and cookie pages after counsel review (neither page has SMS wording today).

**E101. Preference center** [M]. Metric: unsubscribe rate. How: one page (token link) to change interests, frequency and city; Brevo attributes updated by the API.

**E102. Deliverability setup check** [S]. Metric: inbox placement, bounce rate. How: confirm SPF, DKIM and DMARC for the sending domain in DNS and Brevo; `docs/` records the values (names only, no secrets); watch bounce and complaint rates weekly.

**E103. Double opt-in for newsletter** [S] [VERIFY]. Metric: list quality. How: `ajax/subscribe.php` exists; add a confirmation email before the contact becomes active if legal advice or deliverability data supports it.

**E104. Referral program** [L] [OWNER]. Metric: new customers per referrer. How: unique code per buyer, reward after the referred order completes; the reward amount is the owner's decision (none is proposed here); track through the order webhook.

**E105. Loyalty points** [L] [OWNER]. Metric: repeat purchase rate, lifetime value. How: only after E092; points per completed order stored in the customer record; redemption only if TicketNetwork checkout can apply a code; otherwise a post-purchase credit through a separate process.

**E106. Birthday and anniversary offers** [S] [OWNER]. Metric: repeat purchase. How: only if the date was given with consent; one email through Brevo; offer terms set by the owner.

**E107. Win-back series for lapsed customers** [M]. Metric: reactivation rate. How: segment by last order older than N months; two emails with new events by their favorite category.

**E108. List hygiene** [S]. Metric: sender reputation. How: remove hard bounces and long-inactive contacts on a schedule; honor every unsubscribe within the legal limit.

**E109. Email and SMS reporting** [S]. Metric: revenue per send, unsubscribe rate. How: one sheet per month from Brevo and order data; part of the client-style report in K.

---

## G. Paid media and attribution (E110 to E123)

Do not turn on spend until E008, E164 and E166 are done and the 5xx and ad-slot fixes have been recrawled.

**E110. Conversion tracking audit** [S]. Metric: matched conversions between ad platforms, analytics and orders. How: check the Google tag setup (the scripts are loaded under consent in `inc/consent.php`; confirm whether a Tag Manager container is in use), list each tag and trigger, and compare counts with the order webhook for a week.

**E111. Server-side purchase events** [M]. Metric: conversion match rate. How: from the order webhook send purchase events to Google Ads and Meta through their server-side interfaces with consent and hashed email only; read each platform's current documentation first. Needs the buyer's email to reach Seat Outlet first (see the note on the order webhook above).

**E112. UTM and click-id capture** [S]. Metric: revenue by campaign. How: store `utm_*`, `gclid` and `fbclid` in a first-party cookie (consent permitting) and attach them to the hand-off click and the webhook match.

**E113. Branded search campaign** [M] [OWNER]. Metric: cost per order, impression share on the brand term. How: small budget, exact match on "seat outlet"; landing on the home page; only after tracking works.

**E114. Performer and event search campaigns** [L] [OWNER]. Metric: return on ad spend, cost per order. How: feed-style campaigns built from the catalog (top events by date and price band); landing pages are the artist and event pages; negative keywords for free, "box office", competitor brand names.

**E115. Dynamic ads from the catalog** [L] [OWNER] [VERIFY]. Metric: return on ad spend. How: a product-style feed of events (id, name, date, venue, image, price, URL) generated from the copy; read Meta and Google policies on ticketing and resale before launch.

**E116. Retargeting audiences by interest** [M]. Metric: return on ad spend on warm audiences. How: audiences for event page viewers who did not click through (7 and 30 days), artist viewers, and blog readers, with consent respected through the consent mode already in `inc/consent.php`.

**E117. Exclusion audiences** [S]. Metric: wasted spend. How: exclude purchasers from prospecting for 14 days, and past events from every feed.

**E118. Creative testing library** [M]. Metric: click-through and cost per order by creative. How: a shared folder with a naming scheme (hook, offer, format); two new variants a week; keep a log of what lost.

**E119. Landing pages for ads** [M]. Metric: conversion rate by landing page. How: use artist, city and category pages with the lowest friction; add a dedicated template without the menu only if tests show it helps.

**E120. Platform policy checklist** [S] [VERIFY]. Metric: account health. How: read the current Google Ads and Meta policies on resale, ticket pricing disclosure and certification; store the links and the review date in `docs/`.

**E121. Budget guardrails** [S] [OWNER]. Metric: spend versus plan. How: daily caps, a weekly review, and an automatic pause rule when cost per order passes the owner's limit.

**E122. Media mix view** [M]. Metric: revenue and margin by channel. How: one table joining spend and attributed orders; start with last-click and note its limits.

**E123. Incrementality test for retargeting** [M]. Metric: lift in orders. How: hold out a share of the audience for four weeks and compare order rates.

---

## H. Trust, reviews and brand (E124 to E135)

**E124. Verified review collection** [M]. Metric: review count and rating. How: after delivery, email buyers a link to a third-party review platform; `/seat-outlet-reviews` and `customer-testimonials.php` exist, so display only real reviews with their source.

**E125. Review markup rules** [S] [VERIFY]. Metric: rich-result eligibility without penalties. How: no `AggregateRating` is emitted today (`buildOrganizationSchema()` omits it on purpose, after a former invented rating was removed); collect and verify real reviews first (E124), then check Google's review snippet policy before adding markup to any page type.

**E126. Guarantee page with the terms in plain words** [S] [OWNER]. Metric: conversion and complaints. How: `/worry-free-guarantee` exists; have the owner confirm every promise matches what TicketNetwork's guarantee covers.

**E127. Customer service hours and response time on the page** [S] [OWNER]. Metric: contact rate, trust. How: print real hours and the real target reply time on `/ticket-customer-service`; only what the team can keep.

**E128. Company details in the footer** [S] [OWNER]. Metric: trust, AdSense review. How: legal entity, address or contact method, support email; the About page already tells the story.

**E129. Security and payment badges only where true** [S]. Metric: checkout completion. How: show the payment methods the checkout accepts; show a security statement only for what the hand-off page actually provides.

**E130. Press and partner mentions** [M]. Metric: referring domains. How: a short press page with real mentions and a contact; outreach list in the PR plan (N).

**E131. Brand style guide** [M]. Metric: consistency. How: colors, type, tone and photography rules in `docs/brand.md`; reuse across email, social and ads.

**E132. Social proof near the CTA that is data-driven** [S]. Metric: click-through. How: "N people viewed this event today" only if a real counter exists and the number is honest; otherwise skip.

**E133. Complaint handling process** [S]. Metric: time to resolve, chargebacks. How: a shared inbox, a template for each common case, and a monthly review of causes.

**E134. Public status page for the ticket feed** [M]. Metric: support contacts during outages. How: a simple page that reads `/ajax/health.php` and shows "tickets loading slowly" in plain words; the site already shows a card when the feed is down.

**E135. Brand search monitoring** [S]. Metric: branded impressions (none in the 18 Nov export). How: track "seat outlet" queries in Search Console weekly; they are the first sign of the brand being known.

---

## I. Search, discovery and personalization (E136 to E149)

**E136. Search result quality review** [M]. Metric: search to click rate. How: log queries and zero-result searches (without personal data) and review weekly; `cron/build-search-vocab.php` builds the vocabulary, tune it with the real misses.

**E137. Typo tolerance and synonyms** [M]. Metric: zero-result rate. How: add an edit-distance fallback and a synonym list (for example "NYC", "Philly") to the suggestion endpoint.

**E138. Search suggestions that include dates and cities** [S]. Metric: click-through from suggestions. How: show "<Artist> in <City>" entries from the copy.

**E139. OpenSearch description file** [S]. Metric: browser search integration, brand searches. How: publish `/opensearch.xml` and the link tag in the head.

**E140. Filters that persist** [S]. Metric: pages per session. How: remember date, price and category filters in the URL and in session storage; keep `?when=` links nofollow.

**E141. Price filter and sort** [M]. Metric: conversion on listings. How: low and high price exist on bulk rows (`low_price`, `high_price`); add a price range filter to listings answered by the copy, and to live listings only if the API supports the filter without extra calls.

**E142. Map view for city and venue pages** [L]. Metric: engagement, clicks to events. How: events have a venue `geoLocation`; use a lightweight map library loaded on demand; cluster markers; keep a text list for accessibility and SEO.

**E143. Use the location the site already has** [S]. Metric: use rate, conversion. How: `js/near-you.js` and `js/menu-near.js` already offer "near you" from cookies and a button-only permission prompt; add the same block to the home page and artist pages, never storing coordinates.

**E144. Personalized home picks** [M]. Metric: home to event click-through. How: `home-personalized-picks.php` exists; base it on recently viewed categories and the GeoIP city, computed in the browser or from cached lists so no extra calls are made.

**E145. Follow a performer or team** [M]. Metric: sign-ups, return visits. How: E020 stores the follow; show "Following" on artist pages for signed-in or cookie-identified visitors; weekly email of new dates.

**E146. Calendar integration** [S]. Metric: events saved. How: the event page builds a calendar file; add Google and Outlook links and a feed URL per followed performer.

**E147. Similar events on event pages** [S]. Metric: pages per session, conversion when the first event is sold out. How: `soSpecCityEvents` and venue events already feed sections; add "Same artist, other cities" using the artist's catalog events.

**E148. Browse by budget** [M] [VERIFY]. Metric: conversion for price-sensitive visitors. How: pages like "events under a price" only from live low prices; avoid claiming a price that can change without a timestamp.

**E149. Weekend planner** [M]. Metric: engagement, shares. How: pick a city and a date range, get a list across categories; shareable URL; built from the copy.

---

## J. Mobile, UX and accessibility (E150 to E163)

**E150. Accessibility audit in CI** [M]. Metric: automated violations (the last local axe run found none after the credit-contrast fix). How: run axe on the fixture pages in the browser test job and fail on any WCAG 2.1 A or AA violation.

**E151. Date-chip link label** [S]. Metric: Lighthouse accessibility audit `label-content-name-mismatch`. How: the cause is still unknown (an `aria-label` prefix did not fix it); inspect the chip markup in `inc/date-strip.php`, make the accessible name start with the visible text, and test with the audit.

**E152. Keyboard and focus review of the menu and seat map wrapper** [M]. Metric: manual test pass. How: tab through the menu, filters, date strip and modals; visible focus ring; no keyboard trap in modals (`inc/modals.php`).

**E153. Reduce motion and dark mode** [S]. Metric: complaints, accessibility score. How: `prefers-reduced-motion` for animations; dark mode only if the brand colors pass contrast checks.

**E154. Tap target and spacing pass** [S]. Metric: mis-taps on mobile (heatmap). How: 44px minimum targets for chips and filters; use Microsoft Clarity (or the chosen tool) session replays to find the worst.

**E155. Form friction review** [S]. Metric: sign-up completion. How: sign-up and notify forms ask for email only; remove the name field where it is not used (`names => false` already for the event form); inline error text.

**E156. Empty and error states library** [S]. Metric: bounce on empty pages. How: reuse `.so-state` blocks; each empty page offers a next step (another date, the city, a related artist) and the notify form.

**E157. Skeleton states for lazy sections** [S]. Metric: CLS. How: reserve height for sections loaded after paint (home feeds, related events).

**E158. Install prompt follow-up** [S]. Metric: repeat visits from mobile. How: `js/install-prompt.js` exists (phones, after two pages, 30-day snooze); confirm the service worker caches only static assets and add an offline page; measure installs with a dataLayer event.

**E159. Web Share API on event pages** [S]. Metric: shares, referral visits. How: `js/event-actions.js` has share and calendar buttons; use the native share sheet where the browser supports it and keep the copy-link fallback.

**E160. Cookie and privacy bar tidy** [S]. Metric: consent rate, layout shift. How: `inc/consent.php` exists; make the bar fixed-height, the same wording on every page, keep "Decline" as easy as "Accept".

**E161. Content reading experience for guides** [S]. Metric: scroll depth, time on page. How: table of contents, readable line length, 16px minimum text, sticky "See events" link in posts.

**E162. Performance budget per template** [M]. Metric: bytes and requests per page type. How: record JS, CSS and image bytes per template in CI (`tools/build-assets.sh` already builds per-type bundles) and fail when a budget is exceeded.

**E163. Real-device test list** [S]. Metric: bugs found before release. How: a short checklist (one older iPhone, one mid-range Android, desktop Safari) run before each release.

---

## K. Analytics, experiments and reporting (E164 to E173)

**E164. Document and complete the event names** [S]. Metric: complete funnel counts. How: events already exist (`search_submit`, `suggestion_click`, `menu_click`, `feed_card_click` in `js/analytics-events.js`; `view_item`, `select_item`, `begin_checkout`, `no_inventory`, `widget_load_failed` in `js/event-widget.js`); write one document listing every event and parameter, then add the missing ones (`sign_up`, `purchase` from the webhook).

**E165. Key events configured in Google Analytics 4** [S]. Metric: conversions visible per channel. How: mark `purchase`, `begin_checkout` and `sign_up` as key events; check the debug view; confirm consent mode is respected.

**E166. Consent-aware measurement** [M]. Metric: share of sessions measured. How: Google consent mode states from `inc/consent.php`; server-side purchase counts from the webhook fill the gap for people who decline; document the difference between the two numbers.

**E167. Weekly business dashboard** [M]. Metric: one page the owner reads on Monday. How: Search Console clicks, indexed pages, 5xx count, sessions, sign-ups, orders, revenue, ad spend; a sheet or a dashboard artifact.

**E168. Experiment flag framework** [M]. Metric: tests run per quarter. How: a small helper that assigns a visitor to a variant by a first-party cookie, logs the variant with every event, and lets a test be switched off by an environment setting.

**E169. Test backlog ranked by expected value** [S]. Metric: tests that reach significance. How: score each idea (reach, expected lift, effort); start with the sticky mobile bar (E002), price anchor (E001) and promo placement (E010).

**E170. Funnel report from event view to order** [M]. Metric: conversion by step and device. How: view_event, click_seatmap, begin_checkout, purchase from E164; segment by template and source.

**E171. Retention cohorts** [M]. Metric: repeat purchase within 90 days. How: group buyers by first order month from the customer record (E092).

**E172. Monthly client-style report** [S]. Metric: decisions made from it. How: executive summary, what the numbers mean, wins, weaknesses, risks, recommendations, next actions; no metric without its meaning.

**E173. Data quality checks** [S]. Metric: tracking gaps found early. How: a daily check that orders in the webhook match purchases in analytics within a tolerance and that every template fires its page-view event.

---

## L. Security, privacy and compliance (E174 to E183)

**E174. Confirm the production settings from the audit** [S] [OWNER]. Metric: checklist complete. How: `SO_AUTO_MIGRATE` unset, `HEALTH_TOKEN` set, backups running with an off-server copy, `display_errors` off; `deploy/verify-production.sh` checks most of them with the health token.

**E175. Pin GitHub Actions to commit SHAs** [S]. Metric: supply-chain exposure. How: replace tags with full commit hashes and let Dependabot propose updates.

**E176. Retire the old FTP deploy job** [S] [OWNER]. Metric: fewer deploy paths. How: once the pull deploy has run cleanly for a month, remove the job and its secrets from GitHub.

**E177. Content Security Policy enforcement** [M] [OWNER]. Metric: CSP violations per day. How: `docs/csp.md` has the checklist; review the report-only logs for a week, fix the sources that violate, then switch `CSP_ENFORCE` on.

**E178. Rate limiting on sign-up, alert and order-lookup endpoints** [S]. Metric: abuse attempts blocked. How: `inc/request-guard.php` exists; confirm limits per IP and per email on every `ajax/` write endpoint, with a test.

**E179. Dependency monitoring** [S]. Metric: time to patch. How: `composer audit` runs in CI; add a weekly scheduled run and an alert for new advisories.

**E180. Data retention schedule** [S] [OWNER]. Metric: records kept longer than needed. How: write down how long leads, order webhook data, vitals and logs are kept, and add a cron to delete past the limit (`cron/prune-vitals.php` is the model).

**E181. Privacy requests process** [S] [OWNER] [VERIFY]. Metric: time to fulfil a request. How: a documented route for access and deletion requests under the state laws that apply; confirm which apply with counsel.

**E182. Do Not Sell or Share and Global Privacy Control** [S] [VERIFY]. Metric: compliance. How: the site already honors GPC for ads (`inc/adsense.php`, `inc/consent.php`); confirm the same signal controls every third-party tag and the policy text says so.

**E183. Security review of the admin** [S]. Metric: findings closed. How: re-test login, CSRF on every state change, file upload limits and session settings after each admin change; two-factor sign-in for admin accounts.

---

## M. Platform, DevOps and data (E184 to E197)

**E184. Staging that mirrors production settings** [M]. Metric: bugs found before release. How: the beta host runs the same code with a catalog copy and the call budget at the production rate; the mock TicketNetwork API from the 18 Nov profile can live in `tools/` for local runs.

**E185. A mock TicketNetwork API in the repo** [M]. Metric: test speed, fewer live calls in CI. How: a small concurrent Node server (as in `tools/test-category-batch`) with canned events, performers, venues and categories; used by the page tests.

**E186. Release checklist and rollback drill** [S]. Metric: time to roll back. How: the deploy logs a one-line rollback; practice it once on the beta host and time it.

**E187. Split `functions.php`** [L]. Metric: lines and functions per file, test time. How: follow `docs/refactor-plan.md` (six move-only steps), one pull request each.

**E188. Static analysis in CI** [M]. Metric: findings per release. How: add PHPStan at a low level with a baseline; raise the level over time.

**E189. Coding standard enforcement** [S]. Metric: style diffs in reviews. How: PHP-CS-Fixer or PHP_CodeSniffer with a ruleset matching the current code; run on changed files only.

**E190. Database index review** [M] [OWNER]. Metric: slow query count. How: enable the slow query log on a copy, list queries over a threshold, add indexes in a migration after a dump.

**E191. Backup restore test** [S] [OWNER]. Metric: proven restore time. How: restore the nightly dump into a scratch database monthly and run the test loop against it.

**E192. Log aggregation** [M]. Metric: time to find the cause of an incident. How: ship the PHP error log and cron logs to one place with search; Sentry already receives errors and warnings.

**E193. Cron health board** [S]. Metric: missed cron runs. How: every cron calls `soCronWatch()`; add a "last successful run" table and show it in the token-protected health view.

**E194. Cloudflare rules review** [M] [OWNER]. Metric: bot load on origin, cache hit ratio. How: bot rules for abusive crawlers, cache rules for static assets and hub pages (`s-maxage=120` already), a page rule that never caches checkout.

**E195. Image CDN and resizing at the edge** [M] [OWNER]. Metric: image bytes and origin load. How: Cloudflare image resizing or a similar service, only if the plan and cost fit.

**E196. Environment variable documentation** [S]. Metric: onboarding time. How: `.env.example` lists names; add one line each for purpose and default, and a "required in production" flag checked by `tools/check-env.php`.

**E197. Developer onboarding guide** [S]. Metric: time to first merged pull request. How: README and CLAUDE.md exist; add a half-hour tour of one request from `event.php` to the response.

---

## N. Partnerships, monetization and new revenue (E198 to E209)

**E198. Decide the role of display ads** [S] [OWNER]. Metric: ad revenue per session against conversion change. How: after the AdSense review, test ad density against purchase rate on hubs before keeping three or more slots per page; ads next to a purchase can lower conversion.

**E199. Affiliate programs for hotels, parking and rides** [L] [OWNER] [VERIFY]. Metric: partner revenue per order. How: see E019; add disclosure text and `rel="sponsored"` on partner links.

**E200. Group sales landing page** [M]. Metric: group inquiries. How: a page and form for 10 or more tickets with a response promise; route to a person; track as a lead type.

**E201. Corporate and client gift program** [M] [OWNER]. Metric: B2B leads. How: a short page for businesses, a contact form, and an offer set by the owner.

**E202. Gift cards or credit** [L] [OWNER]. Metric: gift purchases. How: depends on whether TicketNetwork checkout can accept a credit; otherwise skip.

**E203. Venue and promoter outreach** [M]. Metric: partnerships, backlinks. How: contact local venues with a short pitch; link to their official ticket page where they sell direct; never imply an endorsement that does not exist.

**E204. Influencer and creator program** [M] [OWNER]. Metric: orders by creator code. How: unique link and tracking per creator (E112), commission terms set by the owner, disclosure rules from the platform.

**E205. Local radio, club and community partnerships** [M]. Metric: branded search. How: sponsor listings in city newsletters for the cities with the most events.

**E206. Sponsored placement for venues and promoters** [L] [OWNER] [VERIFY]. Metric: placement revenue. How: only if clearly labeled and kept apart from organic ordering; check the platform and advertising rules.

**E207. Data partnerships with local tourism boards** [M]. Metric: backlinks and referrals. How: offer an events feed or widget for a city; link back to the city page.

**E208. Embeddable "events in your city" widget** [L]. Metric: referring domains, orders. How: an iframe or script from `ajax/` with a link and attribution to the city page; rate limited and cached.

**E209. White-label for small sites** [L] [OWNER]. Metric: partner orders. How: only with a clear contract with TicketNetwork on what Seat Outlet may resell.

---

## O. Operations and support (E210 to E217)

**E210. Support macros for the top ten questions** [S]. Metric: first reply time. How: from the contact messages in the admin, list the most common questions and write one reply each; link to the FAQ.

**E211. Support inbox reporting** [S]. Metric: volume by reason, time to resolve. How: tag each message with a reason in the admin (`admin/` contact messages) and chart monthly.

**E212. Delivery-issue playbook** [S] [OWNER]. Metric: resolution time. How: written steps for late delivery, wrong seats, canceled events, with the guarantee terms and who contacts TicketNetwork.

**E213. FAQ driven by real questions** [S]. Metric: deflection from contact. How: update `/ticket-faq` quarterly from E211; keep `FAQPage` markup matching the visible text.

**E214. Live chat or callback decision** [M] [OWNER]. Metric: conversion for visitors who contact before buying. How: trial a chat tool on event pages for two weeks and compare; weigh staffing cost.

**E215. Incident runbook** [S]. Metric: time to restore. How: one page: who is called, how to check health, how to roll back, how to read the cron log; build on `docs/reliability.md` and `docs/pull-deploy.md`.

**E216. Monthly review meeting** [S]. Metric: decisions per meeting. How: 30 minutes, fixed agenda from the dashboard (E167), a short list of owners and dates.

**E217. Quarterly roadmap from this list** [S]. Metric: items shipped. How: score each item by expected return, effort and risk, pick five, put dates on them.

---

## Do these first (in order)

1. **E174 and E164 to E166:** confirm production settings and make measurement trustworthy. Nothing else can be judged without it.
2. **E037 and E167:** the weekly Search Console and business dashboard, so the effect of the 5xx fixes is visible.
3. **E072 to E075:** a named author, an editorial policy and the first three guides from real queries. This is what the AdSense review and organic growth need.
4. **E001, E002, E008:** price anchor, sticky mobile bar and purchase attribution: the cheapest conversion work.
5. **E021, E023, E025:** speed of cold event pages; measure real TicketNetwork latency first, then decide on E022 to E024 with the owner.
6. **E057 and E058:** a quality gate and unique data for programmatic pages, so more pages do not mean more thin pages.
7. **E005, E009, E020, E093:** capture email on every event and artist page and send a first welcome series.
8. **E124 and E127 to E128:** real reviews, real support hours and company details.

Items not worth doing yet: E100 (SMS), E104 and E105 (referral and loyalty), E115 (dynamic ads), E202 and E209. Each needs a customer record, tracking or a contract that does not exist today.
