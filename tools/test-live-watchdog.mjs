// CI: the production watchdog (tools/live-watchdog.mjs) against fixture sites served on localhost: a healthy site passes and each failure it exists
// to catch (noindex everywhere, the beta address in the page, an artist page with no events, "Invalid event.", a blocked robots.txt, health not ok)
// fails it. What must NOT fail it: a home page with no event links (the home feed is filled by the browser), a missing deploy marker, one 503 from search.
import { createServer } from 'node:http';
import { checkSite } from './live-watchdog.mjs';

let fail = 0;
const check = (name, ok, detail = '') => { if (!ok) { console.log(`FAIL ${name}${detail ? ': ' + detail : ''}`); fail++; } };

async function run(overrides) {
  const o = { robots: 'index, follow', assets: '<script src="/js/main.min.js"></script>', artistEvents: '<a href="/event/zq-one-1">e1</a><a href="/event/zq-two-2">e2</a>',
    health: { ok: true, time: 'x', commit: 'abcd1234' }, robotsTxt: 'User-agent: *\nDisallow: /admin/\n', eventBody: '<h1>Zq One</h1>', searchStatuses: [200], ...overrides };
  let searchCalls = 0;
  const srv = createServer((req, res) => {
    const u = req.url.split('?')[0];
    const port = srv.address().port;
    const send = (code, type, body, extra = {}) => { res.writeHead(code, { 'content-type': type, ...extra }); res.end(body); };
    if (u === '/') return send(200, 'text/html', `<!doctype html><html><head><meta name="robots" content="${o.robots}"><link rel="canonical" href="http://127.0.0.1:${port}/"><title>Seat Outlet</title></head><body>${o.assets}<h1>Home</h1></body></html>`);
    if (u === '/ajax/health.php') return send(200, 'application/json', JSON.stringify(o.health));
    if (u === '/robots.txt') return send(200, 'text/plain', o.robotsTxt);
    if (u === '/ajax/get_ip_details.php') return send(200, 'application/json', '{"city":"","source":"unknown"}');
    if (u === '/sitemap.xml') return send(301, 'text/html', '', { location: '/sitemaps/sitemap.xml' });   // the live site redirects the index like this
    if (u === '/sitemaps/sitemap.xml') return send(200, 'application/xml', o.sitemap ? o.sitemap : o.noPerformers ? `<sitemapindex><sitemap><loc>http://127.0.0.1:${port}/sitemaps/pages-1.xml</loc></sitemap></sitemapindex>` : `<sitemapindex><sitemap><loc>http://127.0.0.1:${port}/sitemaps/performers-1.xml</loc></sitemap></sitemapindex>`);
    if (u === '/sitemaps/performers-1.xml') return send(200, 'application/xml', `<urlset><url><loc>http://127.0.0.1:${port}/artist/zq-band</loc></url></urlset>`);
    if (u.startsWith('/event/')) { const b = o.eventBodies && o.eventBodies[u]; return send(b ? b[0] : (o.eventStatus || 200), 'text/html', b ? b[1] : o.eventBody); }
    if (u.startsWith('/artist/')) return send(200, 'text/html', `<html><head><meta name="robots" content="${o.artistRobots || 'index,follow'}"></head><body>${o.artistEvents}${o.artistNoEventsMessage || ''}</body></html>`);
    if (u === '/search') return send(o.searchStatuses[Math.min(searchCalls++, o.searchStatuses.length - 1)], 'text/html', 'x');
    send(404, 'text/html', 'no');
  });
  await new Promise((r) => srv.listen(0, '127.0.0.1', r));
  try { return await checkSite(`http://127.0.0.1:${srv.address().port}`); } finally { srv.close(); }
}
const has = (r, text) => r.problems.some((p) => p.includes(text));

let r = await run({});
check('a healthy site served directly (no Cloudflare headers; home with no event links, artist with events) passes', r.problems.length === 0, r.problems.join(' | '));
r = await run({ searchStatuses: [503, 200] });
check('one 503 from search (a throttled feed) does not fail it', r.problems.length === 0, r.problems.join(' | '));
r = await run({ searchStatuses: [503, 503] });
check('two 503s from search fail it', has(r, 'search answered 503'));
r = await run({ searchStatuses: [500, 200] });
check('a 500 from search is not retried: it fails', has(r, 'search answered 500'));
r = await run({ robots: 'noindex, nofollow' });
check('noindex on the home page fails', has(r, 'robots meta'));
r = await run({ assets: '<script src="https://beta.seatoutlet.com/js/main.min.js"></script>' });
check('the beta address in the page fails', has(r, 'beta address'));
r = await run({ artistEvents: '' });
check('an artist page with no events and no message fails', has(r, 'lists no events'));
r = await run({ artistEvents: '', artistNoEventsMessage: '<p>No upcoming events</p>' });
check('an artist with a real "no upcoming events" message passes', r.problems.length === 0, r.problems.join(' | '));
r = await run({ eventBody: '<p>Invalid event.</p>', eventStatus: 404 });
check('"Invalid event." on every linked event fails', has(r, 'none of the events'));
r = await run({ eventBodies: { '/event/zq-one-1': [200, '<p>Invalid event.</p>'], '/event/zq-two-2': [200, '<p>Invalid event.</p>'] } });
check('"Invalid event." with a 200 status is caught by the text, not only the status', has(r, 'none of the events'));
r = await run({ eventBodies: { '/event/zq-one-1': [404, '<p>Invalid event.</p>'], '/event/zq-two-2': [200, '<h1>Zq Two</h1>'] } });
check('one working event among broken ones is enough to pass', r.problems.length === 0, r.problems.join(' | '));
r = await run({ noPerformers: true });
check('a sitemap with no performers (no artist to check) fails instead of passing', has(r, 'no artist page found'));
r = await run({ artistRobots: 'noindex, nofollow' });
check('an artist page with events but noindex fails', has(r, 'robots meta'));
r = await run({ robotsTxt: 'User-agent: *\nDisallow: /\n' });
check('Disallow: / in robots.txt fails', has(r, 'Disallow: /'));
r = await run({ health: { ok: false, time: 'x', commit: 'abcd1234' } });
check('health ok:false fails', has(r, 'ok:false'));
r = await run({ health: { ok: true, time: 'x', commit: null } });
check('a missing deploy marker fails', has(r, 'no deployed commit'));
r = await run({ sitemap: '<sitemapindex><sitemap><loc>https://beta.seatoutlet.com/sitemaps/performers-1.xml</loc></sitemap></sitemapindex>' });
check('a sitemap that lists beta addresses fails', has(r, 'beta address(es)'));

if (fail) { console.log(`${fail} check(s) failed`); process.exit(1); }
console.log('ok: the production watchdog passes a healthy site and fails each breakage');
