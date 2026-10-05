# Lighthouse re-measure, 2026-10-04 (local dev server)

Tool: Lighthouse 13.5.0 (same version as `docs/lighthouse-and-cloudflare.md`; the CI workflow pins `lighthouse@12`, so CI numbers can differ slightly), run via the same CLI invocation as `tools/lighthouse-run.php` (headless Chromium 1194, `--no-sandbox`, categories performance/accessibility/best-practices/seo). Mobile = Lighthouse default (simulated Slow 4G, 4x CPU slowdown). Desktop = `--preset=desktop`. One run per URL and device, so expect roughly plus or minus 5 points of Performance noise on mobile.

Target: `http://127.0.0.1:8140` (PHP built-in server through `/tmp/router.php`, MariaDB on 3307, real cached TicketNetwork data). All 7 URLs returned HTTP 200 with real content; none was skipped.

## Local numbers are not live numbers

- No CDN, no Cloudflare, no HTTP/2 or HTTP/3, no Brotli/gzip on the PHP server, no browser cache headers on static files. Lighthouse reports `cache-insight` (about 620 to 1,200 KiB "savings") only because of that. Live behaviour depends on the Cloudflare cache rules in `docs/lighthouse-and-cloudflare.md`.
- Time to first byte here is 25 to 45 ms (live is about 650 ms with the blog-proxy Worker). Mobile Performance is therefore dominated by simulated throttling (render-blocking CSS, JS execution), not by network.
- Cloudflare Rocket Loader, bot JS challenge and HSTS are absent here, so those live findings do not appear.
- `cdn-beta.seatoutlet.com` and `www.googletagmanager.com` fail with `ERR_CERT_AUTHORITY_INVALID` in this sandbox (the proxy CA is not trusted by Chromium). That alone causes the Best Practices `errors-in-console` failure (96 instead of 100) on every page. It is an environment artefact, not a code bug.
- SEO is 69 on every page for one reason only: `header.php` / `inc/constants.php` sets `<meta name="robots" content="noindex, nofollow">` because `HOME_URL` is `127.0.0.1` (`SITE_INDEXABLE` false). On the live host it is `index, follow`, so SEO is expected to be 100 there (the only other SEO audits all passed locally). Verify against live before treating it as a code task.

## Scores

Perf = Performance, A11y = Accessibility, BP = Best Practices, SEO. Metrics are lab values (LCP, CLS, TBT, FCP).

### Mobile

| URL | Perf | A11y | BP | SEO | LCP | CLS | TBT | FCP |
|---|---|---|---|---|---|---|---|---|
| / | 52 | 100 | 96 | 69 | 5.9 s | 0 | 760 ms | 3.6 s |
| /concert-tickets-for-sale | 71 | 100 | 96 | 69 | 5.7 s | 0.03 | 70 ms | 3.6 s |
| /buy-broadway-tickets | 71 | 100 | 96 | 69 | 5.4 s | 0.063 | 0 ms | 3.6 s |
| /about-seat-outlet | 79 | 96 | 96 | 69 | 4.2 s | 0 | 40 ms | 3.3 s |
| /city/austin-tx | 72 | 100 | 96 | 69 | 5.5 s | 0 | 0 ms | 3.6 s |
| /blog | 72 | 100 | 96 | 69 | 6.0 s | 0 | 30 ms | 3.2 s |
| /all-artists-and-teams | 73 | 100 | 96 | 69 | 5.5 s | 0.032 | 40 ms | 3.3 s |

### Desktop

| URL | Perf | A11y | BP | SEO | LCP | CLS | TBT | FCP |
|---|---|---|---|---|---|---|---|---|
| / | 98 | 100 | 96 | 69 | 1.1 s | 0.001 | 0 ms | 0.7 s |
| /concert-tickets-for-sale | 98 | 100 | 96 | 69 | 1.1 s | 0.027 | 0 ms | 0.6 s |
| /buy-broadway-tickets | 98 | 100 | 96 | 69 | 1.1 s | 0.043 | 0 ms | 0.7 s |
| /about-seat-outlet | 98 | 96 | 92 | 69 | 1.1 s | 0 | 0 ms | 0.7 s |
| /city/austin-tx | 99 | 100 | 96 | 69 | 0.8 s | 0 | 0 ms | 0.7 s |
| /blog | 97 | 100 | 96 | 69 | 1.2 s | 0 | 0 ms | 0.6 s |
| /all-artists-and-teams | 98 | 100 | 96 | 69 | 1.0 s | 0 | 0 ms | 0.6 s |

Mobile FCP is 3.2 to 3.6 s on every page and is the main reason mobile Performance is stuck in the low 70s. Two render-blocking stylesheets (`css/bootstrap.min.css`, `css/style.min.css`) cost about 1,900 ms of simulated render delay on mobile (`render-blocking-insight`).

## Top failing audits per page

Ids are Lighthouse 13 audit ids. "Common" items below apply to every page and are listed once; per-page rows list only what is page-specific. Scored (category) failures come first, then the Performance insights with the largest estimated savings. The Performance score itself is driven by LCP, TBT and FCP (shown above), not by the insight audits.

### Common to all 14 runs

1. `is-crawlable` (SEO, weight 4): `<meta name="robots" content="noindex, nofollow">` in `<head>` (`head > meta`). Environment artefact, see above.
2. `errors-in-console` (Best Practices): `net::ERR_CERT_AUTHORITY_INVALID` for `https://www.googletagmanager.com/gtm.js?id=GTM-W2XCB423`. Environment artefact (sandbox TLS). On `/all-artists-and-teams` also `https://cdn-beta.seatoutlet.com/categories/westend.webp`, `childrenfamily.webp` and others (images are served from the beta CDN host, which fails TLS here).
3. `render-blocking-insight`: `/css/bootstrap.min.css?v=...` and `/css/style.min.css?v=...` (mobile est. savings 1,870 to 1,950 ms, desktop 300 to 320 ms).
4. `cache-insight`: no cache lifetime on `style.min.css`, `jquery.min.js`, `bootstrap.bundle.min.js`, `bootstrap.min.css`, images (local server only; live is governed by Cloudflare).
5. `unused-css-rules`: 231 to 255 KiB unused in `style.min.css` + `bootstrap.min.css` (home mobile 231 KiB). `network-dependency-tree-insight` also reports a chain, and `unused-javascript` on home is 121 KiB (bootstrap.bundle 62 KB, jquery 37 KB, main.min.js 24 KB).

### / (home)

- Mobile Perf 52. LCP element: `body.so-home > main#main > section.so-hero2 > img.so-hero2__bg` (`/images/home-slider-1024.webp`, preloaded). LCP breakdown: TTFB 35 ms, resource load 60 ms, **element render delay 330 ms**, so the rest of the 5.9 s is the simulated FCP wait for CSS.
- TBT 760 ms. Long tasks: `/lib/jquery/3.7.1/jquery.min.js` 330 ms, `/js/main.min.js` 300 ms and 161 ms, the HTML document itself 297 ms (inline scripts / parse). Bootup: `home.min.js` 731 ms total (371 ms scripting), `main.min.js` 802 ms, jquery 584 ms. Main-thread: Style & Layout 1.6 s, Other 1.6 s, Script Evaluation 1.1 s.
- `forced-reflow-insight` fails (JS reads layout after writing it, from `main.min.js` / `home.min.js`).
- Desktop: only the common items; Perf 98.

### /concert-tickets-for-sale

- Mobile LCP element is the consent banner text `body > section.so-consent > p#soConsentText`, at 5.7 s. The banner appears late, so it becomes the largest paint.
- CLS 0.03 mobile / 0.027 desktop. Shifts: `aside.so-ad.so-ad--mid[data-so-ad]` (ad slot inside `div.so-explore`), `details.so-dd > summary.so-chip[aria-label="Distance"]`, and on desktop `div.tm-topbar`.
- `image-delivery-insight` (57 KiB mobile, 39 KiB desktop): `div.so-evhero__media > img` (`/images/event-concert.jpg`, a JPEG served at 480x480), plus `/images/jm.webp` and `/images/seatoutlet-logo.webp` oversized.

### /buy-broadway-tickets

- Mobile LCP element: consent text `p#soConsentText`, 5.4 s. Desktop LCP: `div.so-evhero__media > img` (`/images/loews-theatre.webp`).
- CLS 0.063 mobile / 0.043 desktop: same `aside.so-ad--mid`, `summary.so-chip` and `div.tm-topbar` shifts.
- `image-delivery-insight` 59 KiB: `/images/loews-theatre.webp` (480x480 hero), `/images/jm.webp`, `/images/seatoutlet-logo.webp`. `forced-reflow-insight` fails.

### /about-seat-outlet

1. `link-in-text-block` (Accessibility, weight 7, mobile and desktop): `div.row > div.col-md-7 > p > a` `<a href="/ticket-customer-service">contact our support team</a>`. Link colour `#0a58ca` against body text `#151623` is 2.78:1 (needs 3:1) and the link has no underline. Source: `about-seat-outlet.php` line 509.
2. `image-aspect-ratio` (Best Practices, desktop 92): `section#industry img.img-fluid[style="max-width:200px"]`: `/images/event-concert.jpg` is attributed 800x512 (1.56) but displays 200x225 (0.89), so it is squashed; same pattern on `/images/stage.webp` (750x843) and `/images/crowd-at-concert-or-event.webp` (442x442). Source: `about-seat-outlet.php` lines 528 to 530 (`img-fluid` sets `height:auto` only when not constrained by the flex row; the flex container stretches them).
3. Mobile LCP element is `div.container > div.row > div.col-lg-6 > p.text-white` (hero paragraph), 4.2 s, FCP 3.3 s.
4. `forced-reflow-insight` fails; common items.

### /city/austin-tx

- Mobile LCP element: `div#default > div.row > div.col-sm-12 > p.so-ent-intro` (intro text), 5.5 s; FCP 3.6 s. Desktop LCP 0.8 s.
- `document-latency-insight` (desktop, score 0.5): 121 KiB text compression savings. Local server sends no gzip (live Cloudflare compresses).
- `forced-reflow-insight` fails on mobile. Otherwise only common items.

### /blog

- Mobile LCP 6.0 s, element `div.container > a.so-np__feature > span.so-np__media > img` (`/images/crowd-at-concert-or-event.webp`). `lcp-discovery-insight` fails (LCP image is not preloaded or `fetchpriority="high"`; it is discovered late).
- `image-delivery-insight`: 272 KiB mobile, 527 KiB desktop. Offenders: `/images/venue.webp`, `/images/crowd-at-concert-or-event.webp`, `/images/indie-rock-night.webp`, `/images/event-ticket-buying.webp` in `.so-np__card` / `.so-np__feature` (images larger than their displayed 3:2 boxes).
- Desktop LCP element is the `h2.h1` heading in `section.so-np`.

### /all-artists-and-teams

- Mobile LCP element: `section.performers-hero-section > div.hero-bg-photo` (a CSS background image, `css` rule at `all-artists-and-teams.php` line 101, markup line 274). `lcp-discovery-insight` fails because a CSS background cannot be discovered by the preload scanner. `image-delivery-insight` 65 KiB for the same element.
- CLS 0.032 mobile: `body > main#main > section.py-4`.
- `errors-in-console` has 4 or more entries (GTM plus `cdn-beta.seatoutlet.com/categories/*.webp`). Locally the beta CDN images do not load at all, so the page is measured with missing images; its LCP may be different live.

## Fixes needed to reach 100, ordered by impact

Size: S = under an hour, one file; M = a few hours or several files; L = build or architecture change.

### Performance (mobile; desktop already 97 to 99)

1. L. Inline critical CSS and load the rest non-blocking. Two stylesheets block render for about 1.9 s of simulated mobile time and push FCP to 3.2 to 3.6 s, which drives FCP, LCP and Speed Index at once. Files: `header.php` lines 143 to 150 (stylesheet links), `tools/build-assets.sh` (needs a critical-CSS step per page type). Biggest single gain on every page.
2. M. Cut unused CSS (231 to 255 KiB flagged). `css/style.css` (about 7,500 lines) and `css/bootstrap.min.css`. Split page-specific rules (events listing, entity pages, blog, performers hero) into separate files loaded only on those templates. Files: `css/style.css`, `tools/build-assets.sh`, `header.php`.
3. M. Home TBT 760 ms. Break up `js/main.js` and `js/home.js` initialisation (defer slick/section init to `requestIdleCallback` or IntersectionObserver), remove the forced reflows (`forced-reflow-insight`), and stop jQuery (330 ms long task, 37 KB unused) loading on the critical path where only plain DOM is needed. Files: `js/main.js`, `js/home.js`, `footer.php` lines 172 to 173, `index.php` / home sections.
4. S. Make the consent banner not the LCP element on mobile (`/concert-tickets-for-sale`, `/buy-broadway-tickets`). Give `.so-consent` a smaller footprint or render it after first paint (it is the largest text block on those pages), or `contain`/hide its text until JS positions it. Files: `inc/consent.php`, `css/style.css` line 6838.
5. S. `/all-artists-and-teams` hero: replace the CSS `background-image` on `.hero-bg-photo` with an `<img fetchpriority="high">` or add `<link rel="preload" as="image">` via `$pagePreloadImage`. Files: `all-artists-and-teams.php` lines 101 and 274, `header.php` line 140.
6. S. `/blog`: set `fetchpriority="high"` / preload on the featured `.so-np__feature` image and serve responsive `srcset` sizes (272 KiB mobile and 527 KiB desktop wasted). Files: `blog.php`, `inc/blog-render.php`, `images/*.webp`.
7. S. Compress and size hero images: `images/event-concert.jpg` (JPEG, 480x480 box), `images/loews-theatre.webp`, `images/jm.webp`, `images/seatoutlet-logo.webp`. Provide 1x/2x or `srcset`. Files: `concert-tickets-for-sale.php`, `buy-broadway-tickets.php`, `footer.php`, `header.php`.
8. S. Remaining CLS (0.03 to 0.063): reserve `min-height` for `aside.so-ad--mid` before the ad loads, fix `summary.so-chip` (Distance) width/height changing when JS fills it, and give `.tm-topbar` a fixed height. Files: `css/style.css` lines 5589 and 7490 to 7503, `header.php` (topbar). `/all-artists-and-teams`: reserve height for `section.py-4`.
9. Cloudflare (not code, per `docs/lighthouse-and-cloudflare.md`): Rocket Loader off, bot JS detection off, 1-year browser TTL for `/css/*`, `/js/*`, `/lib/*`, `/fonts/*`, `/images/*`, enable compression. These fix `cache-insight` and `document-latency-insight`.

### Accessibility (only /about-seat-outlet is below 100)

1. S. `link-in-text-block`: underline inline links in prose or darken them to reach 3:1 against `#151623`. Example fix: `.so-prose a, section p a { text-decoration: underline; }` in `css/style.css`, or change the link markup on `about-seat-outlet.php` line 509. Check other body-text links on the page (only this one was flagged).

### Best Practices

1. S. `image-aspect-ratio` on `/about-seat-outlet` (desktop only): in `about-seat-outlet.php` lines 528 to 530 add `height:auto;object-fit:cover` or set `width`/`height` attributes matching the displayed 200 x 225 size so the aspect ratio of the displayed box equals the attributes (`img-fluid` plus the flex stretch is distorting them). Gets desktop from 92 to 96.
2. Environment only: `errors-in-console` (GTM and `cdn-beta.seatoutlet.com` TLS failures). Re-measure on the live host; no code change expected. If the beta CDN host's certificate is genuinely invalid in the real world, fix that in Cloudflare, not in code.
3. Docs list, not scored locally: HSTS `preload` and Rocket Loader are Cloudflare settings (see `docs/lighthouse-and-cloudflare.md`).

### SEO

1. Environment only: `is-crawlable` fails because `SITE_INDEXABLE` is false for `127.0.0.1`. To measure SEO properly locally, start the server with `SITE_INDEXABLE=1`; on the live host it is already indexable. S, no code change needed.

## What it would take to hit 100 everywhere

- Accessibility and Best Practices: items above are small and mostly confined to `/about-seat-outlet`.
- SEO: 100 once indexable (expected, not yet confirmed on this build).
- Performance: desktop is already 97 to 99. Mobile 100 is not realistic with the current two-stylesheet, jQuery, Bootstrap bundle setup. Critical-CSS inlining (item 1) plus the TBT work (item 3) is what moves it, as already noted in `docs/lighthouse-and-cloudflare.md`.
