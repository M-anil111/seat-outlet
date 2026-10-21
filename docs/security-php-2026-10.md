# PHP security release of 21 October 2026 (critical, update now)

Source: the PHP project's announcement, https://www.php.net/archive/2026.php#2026-10-21-5 (advisories GHSA-w8hw-w9fx-j79w and
GHSA-6wmf-4rv5-p3rp).

Two critical flaws allow unauthenticated remote code execution on any PHP that serves web requests (FPM included):

| CVE | What | Fixed in |
|---|---|---|
| CVE-2026-109618 | out-of-memory handling leaves request data half initialized | 8.5.12, 8.4.27, 8.3.36, 8.2.35 |
| CVE-2026-109619 | request state survives after its memory is freed and a later request on the same worker uses it | same releases |

PHP 8.1 and older get no fix from the PHP project.

## Where we stand

- The origin server runs PHP 8.4.19 under PHP-FPM (reported by Sentry on 19 Oct 2026). The fixed release for 8.4 is **8.4.27**. Until it is
  installed the server is exposed. This is a server task: nothing in this repository can patch PHP.
- GitHub Actions installs the newest 8.3 it offers (`setup-php`), so CI is not the concern.

## 1. Update PHP on the server (the real fix)

Whoever administers the host (SSH or the hosting panel):

1. Update the PHP packages to the fixed release of the branch in use (8.4.27 or newer). With a package manager that is the normal upgrade
   of the `php8.4-*` packages, then restart FPM. Confirm the vendor has published the fixed package first: distributions received the
   patches in advance, but check yours. Build from source only if no package exists.
2. Restart every PHP worker (`php8.4-fpm`), not only the web server.
3. Verify both binaries: `php -v` (command line, used by cron) and `php-fpm8.4 -v` (the web). Both must show 8.4.27 or newer.
   A vendor may fix the package without raising the version number (a backport); then the vendor's changelog is the proof, and
   `cron/check-php-version.php` will keep reporting until the version number passes (switch it off only after you checked the changelog).
4. Run `php cron/check-php-version.php` and `php tools/check-env.php --production` in the site folder: the PHP line must say OK.

## 2. If the update cannot happen today: reduce exposure (host configuration, not code)

From the advisory. These lower the risk, they do not make the server safe.

| Measure | Our assessment |
|---|---|
| `session.upload_progress.enabled=0` in php.ini or the FPM pool (not `ini_set`, not `.user.ini`), restart FPM | Safe for this site (nothing uses upload progress). Do this. |
| `post_max_size` low (8M with no uploads, 32M with uploads), `upload_max_filesize` below it | The only upload is the admin picture form (15 MB limit in `admin/images-form.php`), so use **32M**. Set it in the pool, and at the web server too (nginx `client_max_body_size 32m;`). |
| `memory_limit=-1` as `php_admin_value` | The advisory's measure for CVE-2026-109618, but it removes the cap that protects the server's memory from one runaway request. Decide it with the host owner; do not set it blindly on a shared server. |
| Container or cgroup memory limit that kills only the affected worker | Good availability boundary if the host supports it. Do not use `RLIMIT_AS` or `RLIMIT_DATA` (they can cause the vulnerable failure). |
| Fix reachable fatal errors and timeouts | Watch Sentry for fatal errors and "Maximum execution time" on public pages; fix the ones a visitor can trigger. |

Also: Cloudflare in front of the site can block abnormal request sizes at the edge (a WAF rule on request body size, if the plan allows it).

## 3. What this repository now does

- `inc/php-security.php`: the table of fixed releases and `soPhpPatchStatus()`.
- `cron/check-php-version.php` (daily): a Sentry error (fingerprint `php-security-update`) and exit 1 while the PHP running it is older than the fixed release.
- `tools/check-env.php --production`: a failing PHP line until patched.
- `tools/test-php-patch.php` (CI): the version rules.
- Add the next advisory's minimums to `SO_PHP_MIN_PATCHED` when one is published.

The public `/ajax/health.php` deliberately does not show the PHP version or patch state (it would tell scanners which sites are open).
