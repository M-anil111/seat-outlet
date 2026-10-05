# CSS per page type, critical CSS and mobile speed

## Why
SE Ranking flags a CSS file over 150 KB ("CSS too big"). `css/style.min.css` is 222 KB (43 KB compressed) and every page loaded all of it, plus Bootstrap, as two render-blocking files. Most of it is unused on any one page.

## What the code does now
1. **One stylesheet per page type.** `inc/css-groups.php` lists the page types (home, event, entity, browse, directory, content, blog, checkout) and which scripts belong to each. `tools/css-split.php` collects, for each type, its templates, the PHP functions they call (transitively), the scripts and endpoints they use, and (for blog and content) the HTML stored in `db/migrations` and `db/seeds`. `tools/build-assets.sh` runs PurgeCSS on that text, so each type gets `css/style.<type>.min.css`: the same rules in the same order as the full file, minus rules that type cannot use. Files over 110 KB are split in source order (`.1`, `.2`), so the cascade is unchanged and no file passes 150 KB. A script that belongs to no type loads `css/style.full.*.min.css`.
2. **Critical CSS.** `css/critical/<type>.css` holds the rules needed for what is on screen at first paint (about 40 to 80 KB uncompressed, 8 to 15 KB compressed). `header.php` prints it in the head and starts the full stylesheets loading right after first paint, so only the critical rules compete with the first paint. `noscript` loads the full files directly.
3. **jQuery only where needed.** The footer loads jQuery on the home, search and about pages and on pages that set `$soNeedsJquery`; `js/main.js` no longer needs it. That removes a 37 KB script and its long task from every other page.
4. **Always-loaded scripts bundled** into `js/site-extras.min.js`.

## How to change CSS safely
- Edit `css/style.css` as before, then run `bash tools/build-assets.sh` and commit the generated `css/*.min.css`. CI fails if they are stale.
- A page script that is new needs an entry in `inc/css-groups.php`, or it keeps the whole stylesheet. `php tools/check-css-groups.php` (CI) tells you.
- A class built from pieces at runtime ("is-" + state) cannot be seen by PurgeCSS: write the whole name in the PHP or JS, or safelist it in `tools/purgecss-style.config.cjs`.
- **Critical CSS is generated, not built in CI** (it needs the site running). After you change something near the top of a page type (header, hero, first rows), regenerate and commit it:

  ```bash
  # site running (a mock or real ticket API), pages able to render
  export NODE_PATH=$(npm root -g):/path/to/scratch/node_modules   # playwright (global), postcss, clean-css
  node tools/critical-css.cjs --base=http://127.0.0.1:8140 [--groups=home,browse]
  ```

  `tools/critical-urls.json` lists the pages used for each type. A rule missing from the critical file is never wrong, only late: the full stylesheet still brings it. A stale critical file can show a short flash of unstyled content below the first screen, never a broken page.

## How it was checked
- 75 full-page screenshots (33 URLs, mobile 390 and desktop 1440, plus mobile menu, mega menus and search open) against a mock ticket API, before and after: pixel-identical with the per-type files; with critical CSS and post-paint loading 73 of 75 identical and the other two differ by about 50 pixels (image timing). No console errors on any page.
- First screen with only the critical CSS (full stylesheets blocked) against first screen fully loaded: 55 of 62 identical, the rest dither noise in a dark gradient or content that loads later.
- Layout shift: an early version let off-canvas panels and carousels overflow sideways before the full CSS arrived, which made phones zoom out and then jump (Lighthouse CLS 0.16). Fixed with `html,body{overflow-x:clip}` in the critical CSS (clip, not hidden, so sticky headers still work) and by counting elements wider than the screen as critical.

## Lighthouse mobile, local, behind a compressing proxy (like Cloudflare)
The local PHP server does not compress, which makes the lab numbers look 30 to 40 points worse than real life. These runs go through a brotli proxy (`/tmp/vt/zproxy.js` in the working session) and are the fair comparison.

| Page | Before | After |
|---|---|---|
| / | 91 | 97 |
| /concert-tickets-for-sale | 95 | 99 |
| /about-seat-outlet | 94 | 97 |
| /blog | 93 | 95 |
| /city/austin-tx | 95 | 100 |
| /event/<slug> | 93 | 95 |

Still short of 100: the largest image on the blog list and event pages, and the home page's JavaScript (slick carousels still need jQuery there). Live numbers also depend on Cloudflare: Rocket Loader off, Brotli on, long cache for `/css`, `/js`, `/fonts`, `/images` (docs/owner-launch-steps.md).
