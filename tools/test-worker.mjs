// Offline test for the pure helpers in deploy/cloudflare-worker/seatoutlet-blog-proxy.js. Run: node tools/test-worker.mjs
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const src = path.resolve(path.dirname(new URL(import.meta.url).pathname), '..', 'deploy/cloudflare-worker/seatoutlet-blog-proxy.js');
const tmp = path.join(fs.mkdtempSync(path.join(os.tmpdir(), 'so-worker-')), 'worker.mjs');
fs.copyFileSync(src, tmp);   // .js with ES module syntax: load it as .mjs
const { isBlockedPath, cleanPagePath, legacyPhpUrl, SECURITY_TXT, isBareNotFound, default: worker } = await import(pathToFileURL(tmp).href);

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

eq(/^Contact: mailto:support@seatoutlet\.com\n/.test(SECURITY_TXT) && /\nExpires: \d{4}-/.test(SECURITY_TXT), true, 'security.txt has Contact and Expires');

// QA-008: the web server's bare "File not found." is replaced by the branded 404 (the origin sends it with no content-length header)
const eqj = (got, want, msg) => eq(JSON.stringify(got), JSON.stringify(want), msg);
const res404 = (body, headers = {}) => new Response(body, { status: 404, headers: { 'content-type': 'text/html; charset=UTF-8', ...headers } });
eq(await isBareNotFound(res404('File not found.\n')), true, 'a chunked "File not found." (no content-length) is bare');
eq(await isBareNotFound(res404('File not found.\n', { 'content-length': '16' })), true, 'a declared 16-byte body is bare');
eq(await isBareNotFound(res404('x'.repeat(900))), false, 'a large body without content-length is not bare');
eq(await isBareNotFound(res404('x', { 'content-length': '134303' })), false, 'a declared large body is not bare');
eq(await isBareNotFound(res404('')), false, 'an empty body (HEAD) is not judged');
const BRANDED = '<!doctype html><html><head><title>Page not found | Seat Outlet</title></head><body>' + 'branded '.repeat(200) + 'https://beta.seatoutlet.com/x</body></html>';
const realFetch = globalThis.fetch;
const originWith404 = (path) => async (input) => {
  const u = new URL(typeof input === 'string' ? input : input.url);
  if (u.pathname === '/404') return new Response(BRANDED, { status: 404, headers: { 'content-type': 'text/html; charset=UTF-8' } });
  if (u.pathname === path) return res404('File not found.\n');
  return new Response('ok', { status: 200 });
};
globalThis.fetch = originWith404('/this-page-does-not-exist-qa');
let r = await worker.fetch(new Request('https://seatoutlet.com/this-page-does-not-exist-qa'));
let body = await r.text();
eqj([r.status, body.includes('Page not found | Seat Outlet'), body.includes('beta.seatoutlet.com')], [404, true, false], 'an unknown page address answers 404 with the branded page, addresses rewritten to the live host');
globalThis.fetch = originWith404('/images/nope.png');
r = await worker.fetch(new Request('https://seatoutlet.com/images/nope.png'));
body = await r.text();
eqj([r.status, body], [404, 'File not found.\n'], 'a missing image keeps the small 404 (no 130 KB page for an image)');
globalThis.fetch = originWith404('/artist/real-page');
r = await worker.fetch(new Request('https://seatoutlet.com/artist/real-page'));
body = await r.text();
eqj([r.status, body.includes('Page not found')], [404, true], 'a bare 404 under a deeper page path is branded too');
globalThis.fetch = realFetch;

console.log(fail ? `${fail} failure(s)` : 'worker helpers: all passed');
process.exit(fail ? 1 : 0);
