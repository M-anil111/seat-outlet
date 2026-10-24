# Content-Security-Policy

The policy lives in `inc/csp.php` (`SO_CSP_POLICY`) and is sent by `soSendSecurityHeaders()` in `functions.php`.

- **Report-only by default.** Nothing is blocked. Violations are posted to `/ajax/csp-report.php`, which writes one `csp-report-only: <directive> blocked=<uri> page=<url>` line to the PHP error log.
- **Enforced with `CSP_ENFORCE=1`** in `inc/env.local.php`. No code change is needed; the same policy is sent as `Content-Security-Policy` and `frame-ancestors 'self'` is added.
- **No `'unsafe-eval'`.** The Seatics seat map evaluates strings. Event pages would report it, and enforcing the policy there needs a decision first: allow `'unsafe-eval'` for the seat map's script only (not possible with CSP alone, it is page-wide), move the seat map into a frame with its own policy, or accept the report. Until then the policy stays report-only.

## Checking it

| What | How |
|---|---|
| Policy rules and every external host the code loads | `php tools/check-csp.php` (CI, no database; `-v` lists each host) |
| What browsers reported in production | `php tools/csp-report-summary.php /path/to/php-error.log` (add `--json` for a machine-readable list) |
| Real browser, no violation on the site's own pages | `node tools/test-csp-browser.mjs` (needs a running site, see the file) |

### Checklist before setting `CSP_ENFORCE=1`

1. `php tools/check-csp.php` passes.
2. The browser check passes against beta.
3. The summary of a week of real reports lists nothing you are not willing to lose. Add a host you recognise to `SO_CSP_POLICY`, and to `SO_CSP_EXCEPTIONS` if it is a wildcard.
4. Event pages: the Seatics decision above has been made.
5. Set `CSP_ENFORCE=1` on beta first, open an event page, the checkout hand-off, a listing with ads and the contact form, then on live.
6. Keep the report endpoint: enforcement still reports what it blocks.

## Documented exceptions

Every wildcard host, scheme source and `'unsafe-*'` keyword in the policy is listed here and in `SO_CSP_EXCEPTIONS` (`inc/csp.php`). `tools/check-csp.php` fails if the policy and this list disagree, or this file misses one.

| Source | Why |
|---|---|
| `script-src 'unsafe-inline'` | Pages carry inline scripts (the page frame, the Google tag bootstrap, per-page widgets, the consent banner). Moving them to nonces is a separate change. |
| `style-src 'unsafe-inline'` | Inline style attributes and per-page style blocks are used throughout the templates. |
| `script-src https://*.seatics.com` | The seat map widget loads from several Seatics hosts. |
| `script-src https://*.googlesyndication.com` | AdSense serves scripts from changing subdomains. |
| `script-src https://*.doubleclick.net` | Google ad delivery uses changing subdomains. |
| `script-src https://*.adtrafficquality.google` | Google ad traffic quality uses changing subdomains. |
| `style-src https://*.seatics.com` | The seat map loads its own style sheets from its hosts. |
| `img-src https:` | Photos, team logos, venue pictures and ad creatives come from many hosts. Images cannot run code; this is the one scheme-wide source and it is limited to https. |
| `connect-src https://*.tickettransaction.com` | The hosted checkout and its API use several subdomains. |
| `connect-src https://*.seatics.com` | The seat map talks to several Seatics hosts. |
| `connect-src https://*.adtrafficquality.google` | Google ad traffic quality uses changing subdomains. |
| `connect-src https://*.google-analytics.com` | GA4 sends to regional collection hosts. |
| `connect-src https://*.analytics.google.com` | GA4 sends to regional collection hosts. |
| `connect-src https://*.googlesyndication.com` | AdSense reporting uses changing subdomains. |
| `connect-src https://*.doubleclick.net` | Google ad delivery uses changing subdomains. |
| `frame-src https://*.adtrafficquality.google` | Google ad traffic quality frames. |
| `frame-src https://*.googlesyndication.com` | AdSense ad frames. |
| `frame-src https://*.doubleclick.net` | Google ad frames. |
