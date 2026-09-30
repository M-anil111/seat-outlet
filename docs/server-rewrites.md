# Server rewrite rules (nginx / CloudPanel)

Pretty URLs are rewritten by the web server, not by the app (no `.htaccess` ships in this repo). Checked on beta on 2026-09-30 after the web root moved to the new `seat-outlet` directory:

| Works (200) | Returns 404 "File not found." |
|---|---|
| `/city/`, `/venue/`, `/category/`, `/artist/`, `/event/` | `/event-city`, `/concerts-city`, `/sports-city`, `/theater-city`, `/festivals-city` and every other `*-city`, `*-state`, `*-country`, `*-venue` page, `/artist-city`, `/artist-state`, `/artist-country`, `/artist-venue`, `/state/`, `/country/` |

The site's own sitemap and the `/cities` page link to the `*-city` URLs, so 150 sitemap URLs currently 404 on beta. The same pages work when called as `/event-city.php?slug=...`, so only the rewrite rules are missing. Whatever rules the old site config had for `/city/` and friends need the same treatment for these prefixes.

## Rules to add (CloudPanel: site, Vhost editor, inside the `server { }` block, before the `location ~ \.php$` block)

```nginx
# One slug: /event-city/las-vegas-nv-2355 -> /event-city.php?slug=las-vegas-nv-2355
rewrite ^/(event-city|concerts-city|concerts-state|concert-country|concert-venue|events-state|festivals-city|festivals-country|festivals-state|festivals-venue|sports-city|sports-state|theater-city|theater-country|theater-state|theater-venue|theatre-city|theatre-country|theatre-state|theatre-venue|state|country)/([^/]+)/?$ /$1.php?slug=$2 last;

# Two parts: /artist-city/taylor-swift-1234/austin-tx-247 -> slug (performer) + loc (location)
rewrite ^/(artist-city|artist-state|artist-country|artist-venue)/([^/]+)/([^/]+)/?$ /$1.php?slug=$2&loc=$3 last;
```

nginx keeps the visitor's own `?when=...&sort=...` and appends it after the rewritten arguments, so filters keep working. The app also recovers the original query string from `REQUEST_URI` (`functions.php`), so a config that drops it is handled too.

Test after reloading nginx: `/event-city/las-vegas-nv-2355`, `/concerts-city/new-york-ny-3027`, `/state/nevada-34` should return 200, and the sitemap URLs should stop returning 404. Not tested against your nginx: apply on a copy first or keep the old config file to roll back.

## Keep repo files out of the web root

The whole repository was copied into the web root, so `docs/`, `deploy/`, `composer.lock`, `.env.example` and `CONTRIBUTING.md` are downloadable. None contain secrets, but they are not meant to be public. Preferred fix: in nginx, add

```nginx
location ~ ^/(docs|deploy|tools|db/migrations|db/seeds|\.github)/ { return 404; }
location ~ \.(md|lock|example)$ { return 404; }
```

Maintenance scripts (`cron/*`, `db/migrate.php`, `tools/*`) now refuse web requests in the code itself (`inc/cli-guard.php`): run them with `php`, not by URL.
