# functions.php refactor plan (proposal, nothing here is done yet)

Audit of 16 Nov 2026: `functions.php` is 6,849 lines with 270 functions. By name: 72 `get*` (TN lookups and page data), 55 `so*` (site helpers), 37 `tn*` (TN requests, cache, budget), 14 `render*` and 9 `build*` (markup), plus cache, SEO and image helpers. It mixes database access, HTTP calls to TN and page markup, and every page loads all of it. That makes a change risky to review and slow to test.

This is a plan for small, safe steps. It is not a rewrite. Each step moves code without changing it, and ships alone.

## Rules for every step

1. **Move, do not edit.** Cut a group of functions into a new `inc/<name>.php`, `require_once` it from `functions.php` at the same place, and change nothing else. Function names and signatures stay, so no caller changes.
2. **One group per pull request**, small enough to read in ten minutes.
3. **Before the move:** run the full test loop (`README.md`), `php tools/check-undefined-functions.php .` and the "No function is defined twice" step from `.github/workflows/ci.yml` (an inline check, copy it to run locally).
4. **After the move:** the same checks, plus a page render of one page that uses the group (`/`, an artist page, an event page) compared with the live HTML.
5. **Stop at any step** where a test fails for a reason that is not obvious. A half-done move is worse than none.

## Steps, safest first

| # | Move to | What | Why this order |
|---|---|---|---|
| 1 | `inc/tn-http.php` | `tnRequest`, `tnRequestRaw`, `tnFetchStore`, `tnRequestMulti`, `tnRequestCached`, `tnDefaultTtl`, `tnGrace` and the other `tn*` request and cache helpers (roughly `functions.php` lines 285 to 900) | One concern, already covered by `test-tn-budget.php`, `test-tn-listing-copy.php`, `test-tn-snapshot.php` and `test-feed-down.php`. Biggest single win: it is the most edited and most dangerous code. |
| 2 | `inc/tn-queries.php` | The `get*` functions that only call TN and shape the answer (`getTnVenueById`, `getTnCityById`, `getPerformerPageEvents`, ...) | Pure data fetch; no markup. |
| 3 | `inc/cache-store.php` | `cache_get`, `cache_set`, `cache_dir`, `cache_file_path` | Tiny, no dependencies, used everywhere; moving it first makes step 1 simpler to read. (Do it before step 1 if you prefer.) |
| 4 | `inc/markup-cards.php` | `render*` and `build*` functions that print event cards, lists and chips | Presentation only. Covered by the page-level tests (`test-date-strip.php`, `test-ad-content-slots.php`, ...). |
| 5 | `inc/http-errors.php` | `renderNotFoundPage`, `soFeedDownGate`, the 503 page | Small, easy to check by hand (a refused lookup, a missing page). |
| 6 | `inc/seo-helpers.php` | `seo*` and title/description helpers still in `functions.php` | `inc/seo-*.php` and `page-spec.php` already hold most SEO code; this finishes the job. |

After step 6, `functions.php` should be a short bootstrap (constants, `require_once` list, security headers, shutdown hooks) under about 1,500 lines. Stop there.

## Not part of this plan

- Rewriting any function, renaming anything, changing the database layer or adding a framework.
- Splitting `css/style.css` (8,131 lines): it is already built into per page-type bundles by `tools/build-assets.sh`.
- Moving `vendor/` out of git: a separate decision (`docs/pull-deploy.md`).

## How to approve

Say which step numbers to do. Each is one branch and one pull request, with the tests above run before and after.
