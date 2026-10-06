// Offline test for the pure helpers in deploy/cloudflare-worker/seatoutlet-blog-proxy.js. Run: node tools/test-worker.mjs
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const src = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..', 'deploy/cloudflare-worker/seatoutlet-blog-proxy.js');
const tmp = path.join(fs.mkdtempSync(path.join(os.tmpdir(), 'so-worker-')), 'worker.mjs');
fs.copyFileSync(src, tmp);   // .js with ES module syntax: load it as .mjs
const { isBlockedPath, cleanPagePath, legacyPhpUrl, SECURITY_TXT } = await import(pathToFileURL(tmp).href);

let fail = 0;
const eq = (got, want, msg) => { if (got !== want) { fail++; console.log(`FAIL: ${msg}: got ${JSON.stringify(got)}, want ${JSON.stringify(want)}`); } };

// DEV-04: never public
for (const p of ['/vendor/composer/installed.json', '/vendor/', '/composer.json', '/composer.lock', '/docs/pull-deploy.md', '/db/migrate.php', '/deploy/pull-deploy.sh',
  '/tools/check-url-slugs.php', '/cron/build-sitemaps.php', '/inc/functions.php', '/cache/pages/x.html', '/.git/config', '/.env', '/.env.local', '/README.md', '/x/notes.md', '/dump.sql', '/package.json']) {
  eq(isBlockedPath(p), true, `blocked ${p}`);
}
for (const p of ['/', '/event/jekyll-hyde-chicago-il-2026-10-28', '/blog/how-to-avoid-ticket-scams', '/lib/slick-carousel/1.8.1/slick.min.js', '/js/main.min.js',
  '/images/favicon-32.png', '/.well-known/security.txt', '/.well-known/acme-challenge/AbC123', '/admin/login', '/ajax/health.php', '/sitemaps/sitemap.xml', '/city/austin-tx',
  '/tickets-promo-code', '/documents', '/cache-tips']) {
  eq(isBlockedPath(p), false, `not blocked ${p}`);
}

// DEV-07: clean page paths
eq(cleanPagePath('/about-seat-outlet/'), '/about-seat-outlet', 'trailing slash');
eq(cleanPagePath('/About-Seat-Outlet'), '/about-seat-outlet', 'capitals');
eq(cleanPagePath('/Blog/Ticket-Buying-Guide/'), '/blog/ticket-buying-guide', 'both');
eq(cleanPagePath('/about///'), '/about', 'several slashes');
eq(cleanPagePath('/'), '/', 'root stays');
eq(cleanPagePath('/images/Logo.WEBP'), '/images/Logo.WEBP', 'files untouched');
eq(cleanPagePath('/lib/jquery/3.7.1/jquery.min.js'), '/lib/jquery/3.7.1/jquery.min.js', 'libraries untouched');
eq(cleanPagePath('/ajax/Search/'), '/ajax/Search/', 'data endpoints untouched');
eq(cleanPagePath('/.well-known/acme-challenge/AbC123'), '/.well-known/acme-challenge/AbC123', 'certificate tokens untouched');
eq(cleanPagePath('/admin/Users/'), '/admin/Users/', 'admin untouched');
eq(cleanPagePath('/cdn-cgi/scripts/x/rocket-loader.min.js'), '/cdn-cgi/scripts/x/rocket-loader.min.js', 'cloudflare paths untouched');

// DEV-03: clean URLs are rewritten to the extensionless address (no .php, which the origin answers with a 301)
const to = (p) => { const u = legacyPhpUrl(new URL('https://seatoutlet.com' + p)); return u ? u.pathname + u.search : null; };
eq(to('/state/florida'), '/state?slug=florida', 'state');
eq(to('/county/travis-county-tx'), '/county?slug=travis-county-tx', 'county');
eq(to('/event-city/austin-tx'), '/event-city?slug=austin-tx', 'event-city');
eq(to('/artist-city/taylor-swift/austin-tx'), '/artist-city?slug=taylor-swift&loc=austin-tx', 'artist-city');
eq(to('/halloween-events-in-austin-tx'), '/holiday-city?holiday=halloween-events&slug=austin-tx', 'holiday');
eq(to('/city/austin-tx'), null, 'city passes through');
eq(/\.php/.test(to('/last-minute-tickets/austin-tx') || ''), false, 'no .php in rewritten paths');

eq(/^Contact: mailto:info@seatoutlet\.com\n/.test(SECURITY_TXT) && /\nExpires: \d{4}-/.test(SECURITY_TXT), true, 'security.txt has Contact and Expires');

console.log(fail ? `${fail} failure(s)` : 'worker helpers: all passed');
process.exit(fail ? 1 : 0);
