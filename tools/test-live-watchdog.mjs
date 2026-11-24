// CI: the production watchdog (tools/live-watchdog.mjs) against fixture sites served on localhost: a healthy site passes and each failure it exists
// to catch (noindex everywhere, the beta address in the page, no events, "Invalid event.", a blocked robots.txt, health not ok) fails it.
import { createServer } from 'node:http';
import { checkSite } from './live-watchdog.mjs';

let fail = 0;
const check = (name, ok, detail = '') => { if (!ok) { console.log(`FAIL ${name}${detail ? ': ' + detail : ''}`); fail++; } };

const page = (o) => `<!doctype html><html><head><meta name="robots" content="${o.robots}"><link rel="canonical" href="http://127.0.0.1:${o.port}/">` +
  `<title>Seat Outlet</title></head><body><h1>Home</h1>${o.assets}${o.events}<a href="/artist/zq-band">Zq Band</a></body></html>`;

async function run(overrides) {
  const o = { robots: 'index, follow', assets: '<script src="/js/main.min.js"></script>', events: '<a href="/event/zq-one-1">e1</a><a href="/event/zq-two-2">e2</a>',
    health: { ok: true, time: 'x', commit: 'abcd1234' }, robotsTxt: 'User-agent: *\nDisallow: /admin/\n', eventBody: '<h1>Zq One</h1>', ...overrides };
  const srv = createServer((req, res) => {
    const u = req.url.split('?')[0];
    const send = (code, type, body) => { res.writeHead(code, { 'content-type': type, ...(o.noCloudflare ? {} : { 'cf-ray': 'test-ray' }) }); res.end(body); };
    if (u === '/') return send(200, 'text/html', page({ ...o, port: srv.address().port }));
    if (u === '/ajax/health.php') return send(200, 'application/json', JSON.stringify(o.health));
    if (u === '/robots.txt') return send(200, 'text/plain', o.robotsTxt);
    if (u === '/sitemap.xml') return send(200, 'application/xml', '<urlset><url><loc>http://x/</loc></url></urlset>');
    if (u.startsWith('/event/')) return send(o.eventStatus || 200, 'text/html', o.eventBody);
    if (u.startsWith('/artist/')) return send(200, 'text/html', '<html><head><meta name="robots" content="index,follow"></head><body><div class="performer-event-item"></div></body></html>');
    if (u === '/search') return send(200, 'text/html', 'ok');
    send(404, 'text/html', 'no');
  });
  await new Promise((r) => srv.listen(0, '127.0.0.1', r));
  try { return await checkSite(`http://127.0.0.1:${srv.address().port}`); } finally { srv.close(); }
}
const has = (r, text) => r.problems.some((p) => p.includes(text));

let r = await run({});
check('a healthy site passes', r.problems.length === 0, r.problems.join(' | '));
r = await run({ robots: 'noindex, nofollow' });
check('noindex on the home page fails', has(r, 'robots meta'));
r = await run({ assets: '<script src="https://beta.seatoutlet.com/js/main.min.js"></script>' });
check('the beta address in the page fails', has(r, 'beta address'));
r = await run({ events: '' });
check('no events on the home page fails', has(r, 'links to no events'));
r = await run({ eventBody: '<p>Invalid event.</p>', eventStatus: 404 });
check('"Invalid event." on every linked event fails', has(r, 'none of the events'));
r = await run({ robotsTxt: 'User-agent: *\nDisallow: /\n' });
check('Disallow: / in robots.txt fails', has(r, 'Disallow: /'));
r = await run({ health: { ok: false, time: 'x', commit: 'abcd1234' } });
check('health ok:false fails', has(r, 'ok:false'));
r = await run({ health: { ok: true, time: 'x', commit: null } });
check('a missing deploy marker fails', has(r, 'no deployed commit'));
r = await run({ noCloudflare: true });
check('a site that does not come through Cloudflare fails', has(r, 'did not come through Cloudflare'));
r = await run({ eventBody: '<h1>Ok</h1>', eventStatus: 200, events: '<a href="/event/zq-one-1">e1</a>' });
check('one working event is enough to pass', r.problems.length === 0, r.problems.join(' | '));

if (fail) { console.log(`${fail} check(s) failed`); process.exit(1); }
console.log('ok: the production watchdog passes a healthy site and fails each breakage');
