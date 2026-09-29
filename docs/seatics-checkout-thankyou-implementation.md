# Seat map, checkout and thank-you page: implementation write-up

Written for the ticketscanner.ca build, using what is already working on Seat Outlet. Every claim is labeled **Verified** (seen in code or in a live test), **Inference** (reasoned, not proven) or **Confirm** (needs an answer from TicketNetwork).

## 1. Why ticketscanner.ca sends buyers to grabticketsnow

**Short answer: the checkout host is not decided by your PHP or WordPress code. It is decided by the TicketNetwork website config that your site uses.** Change the config, or override it in the widget, and the redirect changes.

### How the redirect is built (Verified)

1. The event page loads the Seatics MapWidget3 script with a `websiteConfigId`.
2. The widget fetches that config from TicketNetwork at runtime. The config holds `checkoutUrl`, the post-purchase redirect, branding and single-click settings. (The loader script does not contain the host; it is fetched after load, so it cannot be read from the script URL alone.)
3. When a buyer clicks Buy, the widget sends them to `{checkoutUrl}/?tgid=...&qty=...&prc=...`.

So if the buyer lands on grabticketsnow, the `checkoutUrl` the widget resolved is grabticketsnow's.

### Most likely causes, in order (Inference, check in this order)

| # | Cause | How to check | Fix |
|---|---|---|---|
| 1 | The site is using a website config ID that belongs to, or was cloned from, the Grab Tickets Now account | Compare the `websiteConfigId` in your widget script URL and your `X-Listing-Context` header with the ID TicketNetwork issued for ticketscanner.ca | Use the ID issued for ticketscanner.ca. Same ID in the widget and in the API header |
| 2 | The correct config exists but its `checkoutUrl` and redirects still point at grabticketsnow | Ask TicketNetwork, or open the Maps Configuration Management tool if you have access | TicketNetwork sets `checkoutUrl` to your checkout host, and the post-purchase redirect to `https://ticketscanner.ca/order-confirmation` |
| 3 | Seat Outlet code was copied, including the footer partner tile (`index.php` links to grabticketsnow.com) | Search the ticketscanner code and theme for `grabticketsnow` and `gtn.webp` | Remove the tile. This is a plain link, not the checkout, but it will confuse buyers and testers |
| 4 | No local override is set, so the widget falls back to the config default | In `event.php` the override is only written when the `TN_CHECKOUT_URL` env var is set | Set the override (section 3) as a stopgap |

Causes 1 and 2 are the ones that matter. Cause 4 is why this can look like a code bug when it is a settings gap.

### What to change, in order

1. **Confirm the config ID.** One ID per environment, used in both places: the widget script `websiteConfigId=` and the API header `X-Listing-Context: website-config-id=N`. Seat Outlet: sandbox 12498, live 27773. Ticketscanner needs its own.
2. **Ask TicketNetwork to update the config** (send the list in section 6). This is the real fix and it also covers the thank-you redirect.
3. **Add the local override** as a stopgap, so nothing depends on the config being right:
   ```js
   Seatics.config.checkoutUrl = 'https://ticketscanner.ca/checkout'; // or the TN-hosted host issued to you
   ```
   Put it before the widget initializes, as `event.php` does. **Confirm** with TicketNetwork that pointing `checkoutUrl` at your own page is supported and that `tgid`, `qty` and `prc` arrive unchanged. If it is not supported, keep the override on the TicketNetwork-hosted host they issue you.
4. **Remove the grabticketsnow footer tile** and any copied partner logos.
5. **Retest** with section 7.

Do not rewrite links after the fact with JavaScript (for example, replacing `grabticketsnow` in the DOM). It breaks when the widget updates and it hides a config problem that will also affect the confirmation redirect.

## 2. Seat map (Seatics MapWidget3)

**Verified on Seat Outlet** (`event.php`):

```html
<div id="tn-maps" class="seatics" style="height: calc(100vh - 50px); width: 100%;"></div>
<script src="https://mapwidget3.seatics.com/js?eventId={ID}&websiteConfigId={CONFIG}&mobileOptimized=true&includeJQuery=false&containerId=tn-maps&useDarkTheme=false"></script>
```

- `eventId` is the catalog event ID. It must exist in the same environment as the config (sandbox events with the live config show "expired").
- `includeJQuery=false` means jQuery must already be on the page.
- Hooks in use: `noEventHandler` (event over), `noTicketsHandler` (no inventory), `onBuyButtonClicked`, and `Seatics.TrackingEvents.registerEventListener` for `FinishedLoading` and `BuyButtonClicked`.
- Replace the default "Sorry, this event has expired" dead end with your own block linking to other dates, the city and the venue.
- Fires dataLayer events: `view_item`, `select_item`, `begin_checkout`, `no_inventory`.

For a travel site: place the map below a clear event summary (name, date, city, venue) and above the fold on mobile. Do not restyle the widget with fragile CSS; use the Maps Configuration tool.

## 3. Checkout

**Verified:** the buyer pays on TicketNetwork's hosted checkout. You never touch card data, which keeps PCI scope minimal. Mercury (self-hosted checkout) returned 403 "API Subscription validation failed" on our subscription, so it is not available yet.

### Flow

```
Event page (map) -> Buy click -> /checkout (your review page) -> hosted checkout -> /order-confirmation
```

### `/checkout` review page (built in `checkout.php`)

Inputs: `eid`, `tgid`, `qty`, `prc`. Sanitization: `tgid` digits only, `qty` integer, `prc` numeric. The page shows event, date, venue, quantity x price, subtotal and "fees calculated at checkout", then a button to `{TN_CHECKOUT_URL}/?tgid=&qty=&prc=`. It is `noindex, nofollow` and fires `view_cart` and `begin_checkout`.

Rules:
- Never state a final total you cannot guarantee. Say fees are added at checkout.
- Treat `prc` as an expectation only. The hosted checkout re-prices.
- If `tgid` is missing, send the buyer back to the event page, not to a blank form.

**Known gap (Verified):** `checkout.seatoutlet.com` has no DNS record. Whatever host TicketNetwork assigns must resolve before launch. Ask for it in the request list below.

## 4. Thank-you page (`/order-confirmation`)

Built in `order-confirmation.php`. Accepted parameters: `oid` / `orderId` / `order`, `total`, `email`, `eid`.

- Shows "Purchase complete", the order number, a masked email, the event summary, what happens next, support contact, buyer protection link and related events from cached feeds (no API call).
- Fires the GA4 `purchase` event once per order number (guarded in `localStorage`).
- Nothing money-related is trusted from the URL, and it grants no ticket access.
- `noindex, nofollow`.

**The redirect only happens if TicketNetwork sends the buyer there.** That is set on their side per website config. If your buyers see a TicketNetwork or grabticketsnow "thank you" screen instead, the post-purchase redirect is still the old one. Same fix as section 1, step 2.

**Confirm** with TicketNetwork: which parameter names they append to the redirect (we assumed `oid`), and whether they can send a server-side order webhook so revenue reporting does not depend on the browser landing on your page.

## 5. Tracking (revenue proof)

| Event | Where | Purpose |
|---|---|---|
| `view_item` | Event page, widget loaded | Inventory seen |
| `select_item` | Widget Buy click | Ticket group chosen |
| `begin_checkout` | `/checkout` button | Intent |
| `purchase` | `/order-confirmation` | Revenue, once per order |

Set `GTM_ID`. Until the redirect works, `purchase` will not fire, so you will see checkout starts with no revenue. That gap is itself a diagnostic for a broken redirect.

## 6. Send this to TicketNetwork

1. Confirm the website config ID for ticketscanner.ca (sandbox and live) and that it is not shared with or cloned from Grab Tickets Now.
2. Set `checkoutUrl` for that config to our checkout host, and confirm the host resolves (DNS).
3. Set the post-purchase redirect to `https://ticketscanner.ca/order-confirmation` and tell us the parameter names appended.
4. Confirm a local `Seatics.config.checkoutUrl` override is supported, and that `tgid`, `qty`, `prc` are preserved.
5. Enable the order webhook if available.
6. Enable Mercury on the API subscription if we want an owned checkout later.
7. Remove any Grab Tickets Now branding from our config (logo, support links, emails).

## 7. Test plan before launch

1. Open an event with inventory. In DevTools, Network, find the config request and confirm the `websiteConfigId`.
2. Click Buy. The address bar must show your host, not grabticketsnow. Note the full URL.
3. Confirm `tgid`, `qty`, `prc` are present and correct.
4. Complete a sandbox order. Confirm the redirect lands on `/order-confirmation` with an order number.
5. Confirm exactly one `purchase` event in the dataLayer, and none on refresh.
6. Search the rendered pages, theme and footer for `grabticketsnow`. There should be no matches.
7. Check confirmation emails for Grab Tickets Now branding (this is set by TicketNetwork, not by you).

## 8. What I could not verify

- The actual `checkoutUrl` returned for the ticketscanner config. I do not have that site's code or its config ID, and the widget fetches the value at runtime. The table in section 1 is a ranked diagnosis, not a confirmed root cause.
- Whether TicketNetwork accepts a `checkoutUrl` on your own domain.
- The exact parameter names on the post-purchase redirect.

Send me the ticketscanner `websiteConfigId` (not any secret) and the URL the Buy click lands on, and I can narrow this to one cause.
