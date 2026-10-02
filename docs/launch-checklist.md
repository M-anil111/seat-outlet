# Launch checklist

Short list of things code cannot do. Each entry: what, who, why. Workstreams append their own section below.

## WS1: backend, security, leads

### Owner (Jay): credentials and accounts
- **Rotate every credential that was ever committed.** The repository is public and old commits still contain real keys (database password, TicketNetwork consumer key and secret, two Google API keys, the AWS/R2 access key pair, the reCAPTCHA secret and the Brevo SMTP key; confirmed by the commit that removed them, "Remove all hardcoded credentials"). Deleting a line from the latest code does not remove it from history. Do these in the provider's own console, then put the NEW values in `inc/env.local.php` on the server and in the GitHub repository secrets. Nothing below needs the old values:
  1. Database: set a new password for the MySQL user, update `DB_PASS`.
  2. TicketNetwork: ask your TicketNetwork contact to issue a new consumer key and secret pair (the old pair may be sandbox-only; they can tell you), update `CONSUMER_KEY`, `CONSUMER_SECRET`.
  3. Google Cloud console, APIs and services, Credentials: create new keys, delete the old ones. Restrict the Maps key by HTTP referrer (your domains) and by API; restrict server keys by API. Update `GAPI_KEY` (and `GKGSAPI_KEY` if used).
  4. AWS/R2 (Cloudflare R2 or S3): create a new access key pair, delete the old one, update `AWS_ACCESS_KEY`, `AWS_SECRET_KEY`. Check the bucket for files you do not recognise.
  5. reCAPTCHA (google.com/recaptcha/admin): create a new v3 key pair for the live domain, update `RECAPTCHA_SECRET_KEY` and `RECAPTCHA_SITE_KEY`.
  6. Brevo: SMTP and API keys page, delete the old SMTP key, create a new one, update `SMTP_USER`, `SMTP_PASS`.
  7. In GitHub (repository Settings): turn on Secret scanning and Push protection; turn on branch protection for `main` (require a pull request and the CI checks to pass). Consider making the repository private. Rewriting history is optional once everything above is rotated (rotation is the real fix) and breaks every clone, so do it only with a developer's help.
- **Mailing address** for marketing emails (US CAN-SPAM asks for a valid physical postal address): give the tech team the one-line address for `SO_MAIL_ADDRESS`. Until it is set the welcome and alert emails go out without it. I did not invent one.
- **Brevo (optional):** if you want sign-ups copied to a Brevo list, create the list and send the tech team its numeric id (`BREVO_LIST_ID`) and a v3 API key (`BREVO_API_KEY`). In Brevo, contact attributes `FIRSTNAME` and `LASTNAME` must exist (they do on a default account); otherwise the contact is still added without names.
- **Counsel:** review the sign-up wording ("By signing up you agree to get emails from Seat Outlet. Unsubscribe any time."), the welcome email and the privacy policy for the new lead storage (email, optional names, page and interest, a salted hash of the IP address, no raw IP). Sign-up is single opt-in (no confirmation email); if counsel wants double opt-in, `leads.confirmed_at` is already in the table.
- **Unsubscribed people** who sign up again with the same address stay unsubscribed (we do not resubscribe on a form post, because anyone could type someone else's address). Decide how support should handle "please add me back" (delete the row or clear `unsubscribed_at` in the database).

### Tech support: server and hosting
- Apply the blocks in `docs/server-rewrites.md`: deny `composer.json`, `composer.lock`, `cache/`, `vendor/`, `phpmailer/`, `docs/`, `db/`, `.git`, `.github`, `inc/`; manifest content type `application/manifest+json`; gzip or brotli; long cache for `*.min.*` and `/images/`; rate limit `/ajax/`; `error_page 404 /404.php;`; serve `/robots.txt` from `robots.php`; `/unsubscribe` must route to `unsubscribe.php` like the other clean URLs.
- Copies of `docs/`, `.github/`, `composer.*`, `CONTRIBUTING.md` that are already in the web root from earlier deploys are not removed automatically: delete them once by hand.
- Move `inc/env.local.php` one level above the web root if the host allows it.
- Add the crons as commands (not URLs): `php cron/send-alerts.php` once a day (run `--dry-run` first and read the output), the other schedules are in `docs/pull-deploy.md` and `CONTRIBUTING.md`.
- Before launch run `php tools/check-env.php --production` on the server and fix every FAIL and BETA line: `HOME_URL`, `BASE_URL` (live API), `DB_NAME`, `DB_USER`, `HOME_PATH`, `AWS_CDN_URL`, `GTM_ID`, `SO_MAIL_ADDRESS`, `SENTRY_DSN`. With the live API and a beta `HOME_URL` the site now answers 503 on purpose (set `SO_ALLOW_MIXED_ENV=1` only to test).
- Confirm Cloudflare's address ranges in `soTrustedProxyRanges()` (`inc/request-guard.php`) still match https://www.cloudflare.com/ips/ . Only requests from those ranges may set the visitor address through `CF-Connecting-IP`; extra proxies go in `SO_TRUSTED_PROXIES`.
- CI runs MySQL 8 while production is MariaDB: consider running CI on a MariaDB image too.
- Sentry alert for "TicketNetwork API unavailable, circuit opened" and an uptime check on one event URL (the circuit breaker now opens only for real outages: 5xx, timeouts, throttling).
- The purchase event on `/order-confirmation` still comes from the URL (the page now ignores impossible totals and order numbers). The real fix is a server-confirmed signal from TicketNetwork (postback or signed redirect): ask TicketNetwork what they offer.
- Branch protection on `main` and pinning GitHub Actions by commit SHA are settings/changes in GitHub, not in the code.

## WS2: event page and purchase funnel

- **Checkout host (F1)** - Tech support. Every Buy click goes straight to the hosted checkout (TN_CHECKOUT_URL); `checkout.php` (review page) is not linked from anywhere. Run `php tools/check-checkout-host.php` after any DNS change and daily from a monitor (exit code 1 = problem). Then place one real test order and confirm TicketNetwork keeps `?tgid=&qty=&prc=`. Decide: wire `/checkout` in, or delete `checkout.php` and docs section 3.
- **Revenue attribution (F3, S6)** - Owner and TicketNetwork. `/order-confirmation` now fires `purchase` only for a visitor who clicked Buy within 24 hours (cookie `so_checkout`) with a plausible order number, but the total in the URL is still unverified. True revenue attribution needs a TicketNetwork server-side order webhook (order id, total, event) stored in our database. Also confirm which parameter names TicketNetwork appends to the return link (`oid`? `total`? `eid`?), and that the return link lands on `/order-confirmation`.
- **Fees wording (U2)** - Owner and counsel. The page now says "Price per ticket, From $X" and does not claim whether fees are included. Ask TicketNetwork whether `lowPrice` includes fees, and ask counsel whether the FTC all-in pricing rule for live-event tickets applies to us and what must be shown before the Buy click. `skipPrecheckoutMobile/Desktop` are still on (buyers leave the site without a fee breakdown): test turning them off in `js/event-widget.js`.
- **Widget events (F2)** - Tech support. In GA4 DebugView on a live event, check which Seatics tracking type fires on a listing click (code assumes names containing "listing" or "ticketgroup") and what fields the Buy click carries (price, quantity, ticket group id). Until a price arrives, `begin_checkout` uses the lowest listed price and sets `value_estimated: true`.
- **FAQ and claims (U7)** - Owner. Check the "seats together" and "legit" answers against TicketNetwork's terms.
- **Past or removed event links (F9)** - Past events now 302 to the performer page with a notice; `event_redirects` is filled only by visits, so an unvisited removed event shows the 404 page. Filling it from a cron over the catalog is not done.
- **Consent (F16)** - Owned by WS6.

## WS3: listings, categories, genres and search
- Server timezone: the site now sets America/Los_Angeles in inc/constants.php (override with env SITE_TIMEZONE). Tech support: confirm the production PHP/host timezone does not conflict and that "Tonight" shows events through the evening. Why: "today" used to roll over at 5pm Pacific on a UTC server.
- Letter pages for the artist directory use ?letter=A (query string). Tech support: if clean paths like /all-artists-and-teams/a are wanted, add a rewrite (see docs/server-rewrites.md). Why: cannot verify host rewrites from code.
- Price filter ("Under $X") uses TicketNetwork's pricingInfo/lowPrice/value (verified in the sandbox). Owner: check on production that results look right, since sandbox prices are $1 test values.
- Promo codes TAKE5 / TAKE10 were removed from listing pages and replaced by an email alert form (soLeadForm). Owner: if the codes are real, confirm them with TicketNetwork before putting them back. Why: unverifiable discount claims.
- Email alerts for "Tell me when events are added" depend on WS1's /ajax/subscribe.php storing interest_type category or city (and performer for search).
- Old buy-tickets-online location and custom date-range box was removed in favor of the shared chips (location, dates, distance, price, sort). Owner: say so if a custom date range picker is still wanted there.
- Names starting with "The" sort under T in the artist directory (the API has no sort key without the article). Owner: decide if that needs a data fix.
Items the code cannot do for itself. Each workstream appends a section: what, who, why.

## WS4: entity pages and images

- **TheSportsDB licence (owner / counsel).** Team art from TheSportsDB is user-contributed and the free test key is not licensed for commercial use. The code now uses TheSportsDB only when `THESPORTSDB_KEY` is set to a real paid key (never the test keys 1, 2, 3, 123), never uses the team badge/logo, and otherwise falls back to Wikidata, then the initials tile. Before setting a paid key, get their written terms for commercial display and for art that users uploaded. Without a key, sports pages show Wikimedia photos or tiles only.
- **Cron for images (tech support).** Schedule `php cron/resolve-images.php` every 10 minutes. Admin > Images shows the oldest queued item; more than a day old means the cron is not running (the background worker is only a slow safety net).
- **Wikimedia user agent (tech support).** Wikimedia answers 429 to anonymous-looking bots. The bot sends `SeatOutletBot/1.0 (+https://<site>/contact)`; register a contact email in that string (inc/images.php `imageUserAgent()`) per the Wikimedia User-Agent policy, and confirm the production server IP is not rate limited.
- **Old stored pictures (owner / tech support).** Pictures stored before licences were recorded sit under slug-keyed objects (for example `artists/taylor-swift.webp`) with no licence. New files are written under `<folder>/<md5>.webp`. Use Admin > Images > "Re-verify older pictures" to queue them again (they show an initials tile until re-resolved), then delete the old slug-keyed objects from the bucket once no row points at them. Needs bucket access.
- **Photo credits page (owner).** `/image-credits` lists every licensed photo with author, licence link and source. Cards link to it. Please add a "Photo credits" link to the site footer (footer.php is owned by another workstream).
- **Wikidata matching is untestable here (tech support).** Wikidata and Commons answered 429 from the build network, so the city state check (P131) and the artist occupation check (P106) were tested with stubbed responses only. After the cron runs in production, spot-check 10 cities (Springfield, Columbus, Portland, Kansas City) and 10 artists in Admin > Images and fix any wrong picture with "Set image".
- **Sitemap and zero-event pages (owner).** Zero-event pages are `noindex,follow` and are removed from the sitemap once they have been rendered once (pages record themselves in `cache/so_zero_pages`). A page nobody has visited yet can still be listed until its first visit. In Search Console compare "submitted" with "indexed" after a few weeks.
- **Server rewrites (tech support).** `/theatre-*` now 301s to `/theater-*`, and non-canonical slugs 301 to the canonical slug. Both need the nginx rewrites in docs/server-rewrites.md to be in place, as before.
- **Footer/menu headings (WS6).** On entity pages the H1 is followed by the menu's H3 items before the first H2; the menu markup lives in header/footer, outside this workstream.

## WS6: home page, header/footer, privacy, 404

- **Counsel review (owner to arrange):** the privacy bar copy ("We use cookies for analytics and ads ... Accept / Decline / Manage") and the "Your privacy choices" link are working defaults, not legal text. Counsel must review the wording, the US opt-out model (tags load unless Global Privacy Control or Decline), the cookie policy page, and whether California or other states need an extra "Do not sell or share" wording. The bar and footer link appear only when GTM_ID is set.
- **Global Privacy Control:** honored in the browser (navigator.globalPrivacyControl, the same signal as the Sec-GPC header): GTM is not loaded. The server cannot vary the HTML (pages are CDN-cached). Optional, after counsel agrees: publish /.well-known/gpc.json {"gpc": true}.
- **Third-party tags kept:** Google Maps loads only when a visitor uses a location field; reCAPTCHA only when a form is submitted. Both are treated as functional. Counsel to confirm.
- **Pricing claim (B8):** the "No hidden fees" claim was removed from the home page. Someone should place a test order to confirm what checkout adds, then decide on all-in price wording (FTC rule for live-event tickets: counsel).
- **404 for unknown URLs (tech support):** the web server prints a bare "File not found." Add the nginx rule in docs/server-rewrites.md (error_page 404 /404.php).
- **Other findings not done here:** B1/B3/B4 (newsletter-email.php) are replaced on the home page by the shared lead form (WS1 owns the endpoint; newsletter-email.php can be retired). B17 (/blog 500 on beta) and B18 (composer.json and /cache files readable on beta): tech support, server config. S3/S4 (event price alerts, service worker) and S5 (idle nudge) not done.
- **Slick:** already loaded only on home, search and about pages (deferred); jQuery and Bootstrap JS untouched.
## WS5: content, trust, blog, legal, compliance

For the owner
- Contact details: the old page showed a placeholder phone (1-800-123-4567) and street address (123 Ticket Plaza). Both were removed. Add real ones only if they exist (phone, hours, mailing address). Who: owner. Why: nothing may be invented.
- Company facts for the About page: legal entity name, founding year, address, real team names. Not on the site now because none could be verified.
- Real reviews/testimonials: none are shown. Collect them after real orders (post-purchase email) before showing any rating or AggregateRating.
- BBB: say so only if a BBB profile or accreditation exists; the page currently says it shows no rating.
- Promo codes TAKE5 and TAKE10 (shown on /tickets-promo-code and several listing pages): confirm with TicketNetwork that they exist and their minimums ($199 / $349), otherwise remove them everywhere.
- Guarantee: confirm with TicketNetwork that the four points in inc/guarantee.php match its current policy, and whether a rescheduled or postponed event is refundable.
- Fees: confirm what TicketNetwork's checkout adds (service, delivery, taxes) and when it shows them. Counsel: check "review the full cost at checkout" against the FTC rule on unfair or deceptive fees (all-in pricing).
- Email sending: set SMTP_USER and SMTP_PASS (Brevo) on the server, and optionally CONTACT_TO / CONTACT_TO_PARTNERSHIP. Without them messages are saved (see Admin > Contact Messages) but no email is sent. Make sure support@seatoutlet.com is a verified Brevo sender. Do NOT set SO_RECAPTCHA_SKIP outside local development.
- Someone must read Admin > Contact Messages (or the support inbox) daily.
- Footer: the "Sitemap" link still points to the XML file (/sitemap.php). Point it to /sitemap-page. Footer and header text still say "Trusted resale marketplace", "100% Worry-Free Guarantee", "verified tickets", and "By continuing past this page, you agree" (WS6 area): align with the guarantee wording.
- Production robots: serve robots.php at /robots.txt (docs/server-rewrites.md). The static robots.txt has no Sitemap line on purpose.
- Blog: the seven launch posts repeat their focus phrase 34 to 51 times (SEO tool now warns). Have the author vary the wording. Review dates and facts before re-publishing.
- Blog post image alt texts were written from the pictures; replace generic stock images with relevant ones when available.
- The old iContact page /newsletter and newsletter-email.php: retire or noindex (WS1 area).

For counsel (not drafted, deliberately)
- Privacy policy: US state privacy rights section (CCPA/CPRA and other states): categories, sale/share, "Do Not Sell or Share", Global Privacy Control, request methods, response times. Section on targeted advertising/retargeting: confirm whether it applies; GTM is only loaded when GTM_ID is set.
- Cookie policy: no consent banner exists; decide if one is required and which tags must wait for consent (GTM, reCAPTCHA). Name actual vendors (Google reCAPTCHA, GTM, Brevo, TicketNetwork/Seatics).
- Terms: add a guarantee clause and name TicketNetwork as fulfilment partner (terms say sales are final, the guarantee page is broader); governing law and disputes (section 19 keeps the existing Austin, Texas wording); arbitration/class waiver decision; registered postal address (placeholder line removed); replace "By continuing... you agree" browsewrap.
- Affiliation disclaimer added to legal pages and key pages; counsel to confirm wording and decide on a footer line.
- Photo/image licensing for stock pictures used on the site.
