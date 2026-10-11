# Site, interaction and API review

This review covers the repository's shared page families, their API adapters and interactive scripts. Design and behavior changes are in the working tree. They have not been deployed. Live beta requests from this environment return a proxy 403, and the six required service credentials are absent. Accordingly, this is a source review plus isolated functional testing, **not a completed live-site or purchase acceptance test**.

## Changes implemented

| Area | Problem | Result |
| --- | --- | --- |
| Shared date/price/sort controls | Later CSS reduced targets to 32px; scrolling filter parents could clip menus | 44px targets, readable labels, wrapping mobile controls, visible menus and stronger selected states |
| Dropdown accessibility | Summary labels hid the selected value from assistive technology; Escape lost context | Labels include the selected value; Escape returns focus to the open control |
| Search dates | Quick date links retained the overriding custom range; custom dates looked like “All dates” | Quick selection replaces the range; the chosen range is displayed; sorting preserves it |
| Date validation | Impossible or reversed dates could reach the API; inclusive “7/30 days” windows actually spanned 8/31 days | Strict calendar dates, chronological ranges and correctly sized inclusive windows |
| Search location | Unvalidated numeric conversions could turn malformed coordinates into a real location | Coordinates use the shared validated geographic grid |
| Artist filtering | Slow older responses could overwrite newer selections | Abort plus generation checks keep the newest selection; list exposes its busy state |
| Empty results | Previous rows, counts and pagination remained after an empty response | Rows, counters, progress and paging reset together |
| Sorting and pagination | Sort discarded custom dates; pagination rebuilt unsnapped coordinates; old pages could append to new filters | Dates survive sorting, continuation uses the server's exact parameters and stale pages are discarded |
| Location reset | Cookies reintroduced the location after fields were cleared | Saved coordinates and label clear with the fields |
| API outages | Failed reads appeared as successful empty results, sometimes cached | Listing, paging and keyword AJAX failures return an error with `no-store`; degraded reads return 503; search shows an unavailable state |
| Homepage location categories | Nationwide cached events could appear beneath a selected location | Results retain their requested scope; no nationwide substitution; null dates/times are safe; HTTP freshness is five minutes |
| Autocomplete | Storage exceptions interrupted input; failed keywords could not retry; old responses affected the loading state | Input survives blocked storage, HTTP failures can retry, and current requests own their results/loading state |
| Suggestions/home cards | HTTP errors and old failures could change current UI | HTTP checks and response guards; successful suggestions restore a previously hidden section |
| Catalog caching | Same website config ID could share cached data across API hosts/apps | Cache identity also includes API host and consumer identity (hashed; no credential is exposed). Deploying this change starts a fresh catalog-cache namespace; warm important routes gradually to avoid a cold-cache traffic burst. |

The earlier Sentry repairs and deployment requirements remain documented in [sentry-issues-runbook.md](sentry-issues-runbook.md).

## Coverage and remaining acceptance work

| Page family/function | Evidence available | Remaining live check |
| --- | --- | --- |
| Header navigation, search and autocomplete | Shared scripts inspected; real autocomplete script tested with grouped API fixtures, storage failure, races, failed HTTP and short-query reset | Actual suggestions, Maps city/ZIP selection, touch navigation and slow-network behavior |
| Home: categories, nearby venues, weekend/last-minute feeds | Shared templates/scripts/API adapters inspected; category error handling and scope fixed | Real tabs and location results, retry button, image/slider layout and stale-feed indicators |
| Search, category, genre, discovery and holiday pages | Shared rendering/query builders inspected; date links and date validation executed; holiday suite passes | Real inventory correctness, price caps and order across every page of results |
| Artist, venue, city, county, state and country pages | Shared entity/listing code inspected; county tests pass; real shared filtering/paging script tested | API-specific entity edge cases and actual nearest-distance behavior |
| A–Z directories and legacy URLs | Pagination, slug and sitemap suites pass | Live redirects, alphabet/keyword navigation and cache behavior |
| Event detail and ticket selection | Event template renders with partial fixtures; quantity/widget adapters inspected; offer schema tests pass | Actual Seatics inventory, every quantity, map/list agreement, accessibility and hosted handoff |
| Checkout and confirmation | Checkout parameter construction and fee messaging inspected; payment remains hosted by TicketNetwork | Authorized sandbox ticket-group selection and order, final fees, delivery, cancel/back and confirmation |
| Saved events | Storage/read/write and stale-event handling inspected | Save/remove across page navigation, private browsing and storage quota |
| Blog, help, policies, guides and contact | Shared content/CSS families inspected; contact origin checks pass | Real article images, forms with reCAPTCHA, delivery to the intended inbox and mobile content layout |
| Newsletter/lead forms | POST validation, honeypot, reCAPTCHA and rate-limit paths inspected | Real provider delivery, duplicate signup and unsubscribe |
| Admin | Authentication, CSRF, registration and prepared-write patterns inspected; PHP lint/static checks pass | Authenticated create/edit/delete, reset email, upload and permissions in a disposable test account |
| Worker, routing and assets | Worker checks, URL/schema checks, stylesheet checks and generated-asset build pass | Production CDN headers, redirects and public/private cache boundaries |

No contact messages, newsletter subscriptions, admin accounts or purchases were created during this review.

## Design suggestions, in priority order

1. **Make the purchase path clearer.** Keep event title, local date/time, venue/city and the available price visually dominant. The follow-up design pass restored explicit Buy Tickets/View Event buttons on desktop and mobile, with prices and actions separated from the event details. Validate this treatment with live inventory.
2. **Simplify the homepage.** Put search and location first, followed by one nearby recommendation row and the main categories. Move repetitive promotional and SEO sections below those choices. Keep fee and resale explanations close to the buying decision.
3. **Unify the visual system.** The stylesheet contains several generations of overrides. Move common colors, spacing, typography, focus styles and controls into a single maintained component layer, then gradually remove earlier conflicting rules. Preserve the existing stylesheet splitting and size checks.
4. **Expose filtering clearly.** Show a removable summary of location/date/price selections and one clear-all action. Keep a selected custom range visible. Add a distinct custom-range choice to date menus instead of making visitors find the header calendar.
5. **Use consistent empty/error/loading states.** Explain “no matching dates”, “no tickets listed” and “feed temporarily unavailable” separately. Add an inline retry and avoid moving controls while results load. Reduce autoplay and honor reduced-motion preferences in sliders.
6. **Check real page layout before a broader redesign.** Test actual long titles, multi-day festivals, unavailable images, sold-out events and several currencies at 360/390/768/1440px and 200% zoom. The saved screenshots are component fixtures, not full-site previews.

## API/programming assessment

The useful foundations are shared row rendering, escaped catalog strings, server-controlled pagination bounds, prepared database writes, bounded GET retries, stale caching and a circuit breaker. Hosted checkout is the current payment implementation; a native Mercury checkout is not implemented, and code comments report that subscription as unavailable. That entitlement needs confirmation with the provider.

Prioritized follow-up work:

| Priority | Recommendation | Reason / validation |
| --- | --- | --- |
| High | Centralize a catalog-result contract with explicit success/degraded/stale/empty states across every adapter | This change handles the confirmed endpoints; other adapters still use inconsistent arrays and HTTP caching policies. Add contract tests for each public AJAX route. |
| High | Add quota-aware concurrency and a cold-cache request lock | A breaker reacts after throttling; simultaneous cold requests can still exhaust the provider quota. Measure requests per page and cache hit rates before choosing limits. |
| High | Replace browser-supplied free-form continuation filters with validated structured filters or a signed continuation token | The existing allow-list rejects keys, sort values and disallowed characters, but does not validate filter semantics. Verify supported filter combinations and keep paging in the same scope. |
| High | Run authenticated sandbox purchase and live read-only integration checks | Fixture success cannot verify current API schemas, subscription permissions, token coordination across server processes or third-party inventory. |
| Medium | Separate HTTP transport, token management, catalog queries, database reads and rendering from the large `functions.php` | Smaller modules make endpoint-specific tests and error contracts easier without changing behavior. Refactor incrementally. |
| Medium | Recheck relevance across pagination | Local page ranking can reorder only the current page; it cannot guarantee globally best matches. Prefer a provider-level ranking or a bounded complete result set. |
| Medium | Add explicit freshness to stale results and avoid presenting stale prices as a guarantee | Existing fallback windows can be long. Verify totals and final prices with the hosted checkout. |
| Medium | Make optional services optional during local startup | Requiring Maps, image-storage and reCAPTCHA credentials for every page prevents useful partial startup. Introduce explicit development adapters, rather than fake production configuration. |
| Medium | Finish dependency and deployment checks | Composer advisory audit passed during release preparation; CI should verify the published branch. Apply migrations and monitor Sentry after deployment. |

## Reproducing the isolated tests

Activate the prepared cloud PHP runtime first, if using this workspace:

```sh
source /workspace/seatoutlet-runtime/activate.sh
php tools/test-listing-controls.php
php tools/test-listing-api.php
node tools/test-listing-browser.cjs
```

The browser test needs Playwright and Chromium. `CHROMIUM_PATH` overrides the executable; otherwise it uses `/usr/bin/chromium` when present or Playwright's installed Chromium. Optional `SCREENSHOT_DIR=/tmp/seatoutlet-review` saves mobile/desktop component screenshots. The tests use local adapters and intercepted browser requests; they do not call external providers. CI includes these tests.

## Follow-up design pass

The homepage now has a clearer photo-led headline, Search Events and Explore this weekend actions, and four direct category cards. Shared event cards use consistent spacing, borders, typography and blue accents. Listing rows have readable date tiles, prominent prices and visible Buy Tickets/View Event actions; mobile rows place price and action beneath the details. Header search styling and page-title cards use the same visual direction.

Local Chromium previews use the actual header, homepage hero/category markup and shared row renderer with fixture events. Checks at 360, 390, 768 and 1440 pixels found no horizontal overflow; ticket actions are visible, at least 44px high and receive clicks without the row overlay intercepting them. Long event titles and unavailable ticket prices were included. Existing dropdown/autocomplete browser checks, stylesheet size checks, SEO checks and icon coverage also pass. These previews do not establish live inventory or third-party widget layout.
