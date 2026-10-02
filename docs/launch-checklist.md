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
