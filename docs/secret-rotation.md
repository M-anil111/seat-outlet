# Secrets found in the public git history: what to rotate and how

Found 8 October 2026 by scanning every revision of this repository. The repository is public, so anything ever committed is readable, even after it was removed from `main`. **No values are written here.**

| What | Where it was committed | Still in current `main`? | Action |
|---|---|---|---|
| reCAPTCHA secret key (`$recaptchaSecret`) | `newsletter-email.php`, `ajax/check-email.php`, about 15 revisions each, from April 2026 | No | Rotate (below) |
| Mail relay password (`$mail->Password`) | the same two files and revisions | No | Rotate (below) |

The scan looked for AWS (`AKIA...`), Brevo (`xkeysib-...`), database, TicketNetwork and generic `password / secret / token / api_key = '...'` assignments. It found nothing beyond the two above, but it did not re-confirm the exposures the launch checklist (`docs/launch-checklist.md`) already records (database, TicketNetwork, Google API, AWS). Do not read it as an all-clear: that checklist's rotation list is still required, and a scan cannot prove absence.

## Steps (owner or tech support)
1. **reCAPTCHA:** Google reCAPTCHA admin, create a new v3 key pair for `seatoutlet.com` (and `www`). Put the secret in `RECAPTCHA_SECRET_KEY` and the site key in `RECAPTCHA_SITE_KEY` in `inc/env.local.php` on the server. Delete the old pair. Run `php tools/check-env.php`.
2. **Mail relay:** in the mail provider (Brevo SMTP), create a new SMTP key and set `SMTP_USER` / `SMTP_PASS` in `inc/env.local.php`. Revoke the old one.
3. **Make the repository private** (GitHub, Settings, General, Danger Zone). If it must stay public, rewrite history first with an expressions file that lists the exposed values (`git filter-repo --replace-text <expressions-file>`, keep that file out of the repository), force-push all branches, ask GitHub support to purge cached views, and expect every clone and the pull-deploy checkout on the server to be re-cloned.
4. **Turn on** GitHub secret scanning and push protection, and branch protection for `main` (require a pull request and the CI checks).
5. After rotating, send a test message from `/ticket-customer-service` and a newsletter signup to confirm both still work.
