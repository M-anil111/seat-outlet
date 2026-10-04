# Lighthouse: what is fixed in code, and what only Cloudflare can fix

Measured with Lighthouse 13.5.0 (the version in the owner's reports), mobile, simulated Slow 4G, against `/`, `/game-day-tickets`, `/ticket-faq` and `/blog`.

## Fixed in code (this release)

| Finding | Cause | Fix |
|---|---|---|
| Accessibility 94 to 100: `color-contrast` (about 30 nodes) | The footer had `content-visibility: auto`. While it is skipped, contrast checkers cannot hit-test it and read the light page background behind white text (1.1 to 1.2 to 1 ratios that are not real) | Removed it from `.tm-footer`; solid color after the gradient |
| `color-contrast` on three buttons and one badge | White on `#2f6bff` (4.49), white on `#16a34a` (3.3), `#0d6efd` on `#f5f7fb` (4.2), grey ad label | `#2858e0`, `#15803d`, `#0a58ca`, `#6e6e73`; event card sports and festival colors darkened the same way |
| `aria-hidden-focus` | Slick marks off-screen slides `aria-hidden` but leaves them (and their links) focusable, and re-sets `tabindex` after its own events | `js/main.js`: hidden slides and everything focusable inside get `tabindex="-1"`, re-applied by a MutationObserver, and undone when the slide shows |
| Agentic Browsing 0/2 to 2/2 | "Accessibility tree is not well-formed" was the same `aria-hidden-focus` failure; the other check is layout shift | Same fix; CLS below |
| CLS 0.136 to 0.027 | "Popular this weekend" was hidden until its data arrived, then pushed the rows below it down | The row is on the page from the first paint with skeleton cards and a reserved height; the script hides it only if there is no data |
| Best Practices: AdSense third-party cookies, 503 console errors | AdSense (about 250 KB, a cookie from doubleclick.net and Google's consent script) loaded 1.5 s after load; the idle prefetch of the date picker files returned 503 | AdSense now loads on the first touch, scroll, click or key press (or 15 s after load). The date picker files are prefetched on the first interaction instead of on load |

Local results (home): Accessibility 100, Best Practices 100, SEO 100, Agentic 2/2, CLS 0.027.

## Only Cloudflare can fix these (owner steps, 5 minutes)

| Setting (Cloudflare dashboard) | Why it matters |
|---|---|
| **Speed, Optimization, Content Optimization, Rocket Loader: Off** | Rocket Loader rewrites every script tag and delays them. In the reports it is a render-blocking request of about 680 ms and 700 ms of scripting. It also broke the home sections earlier. Highest single gain for mobile Performance |
| **Security, Bots, JavaScript Detections: Off** (or Bot Fight Mode off) | Adds `/cdn-cgi/challenge-platform/.../main.js`: 700 ms of scripting and the "deprecated API" Best Practices warning (`StorageType.persistent`) that dropped one page to 77 |
| **SSL/TLS, Edge Certificates, HSTS: add "Preload"** | The "strong HSTS policy" check wants `max-age>=31536000; includeSubDomains; preload` (today: no `preload`). Only submit to the preload list when every subdomain is HTTPS for good |
| **Caching, Cache Rules: static files** (`/css/*`, `/js/*`, `/lib/*`, `/fonts/*`, `/images/*`) Browser TTL 1 year | "Use efficient cache lifetimes": `main.min.js` is cached 5 minutes. Our files carry `?v=` versions, so a long lifetime is safe |
| Remove the `seatoutlet-blog-proxy` Worker (see docs/production-cutover.md) | The Worker adds latency (first byte about 650 ms on the home page) |

## Why mobile Performance will not reach 100 on code alone

LCP (25% of the score) and Total Blocking Time (30%) are driven by first byte time, the two render-blocking stylesheets (about 65 KB compressed) and third-party scripts. After the Cloudflare steps above, the next code step is inlining critical CSS per page type and loading the rest later. That is a separate, larger change: it needs a build step that is kept in sync with the CSS, so it is not part of this release. Re-test after the owner steps and this deploy before deciding.

## Not worth doing

"Ensure CSP is effective against XSS" and "Mitigate DOM-based XSS with Trusted Types": both are unscored in the Best Practices category and a strict CSP would need a nonce on every inline script (GTM, AdSense, Seatics). Revisit only as a security project, not for the score.
