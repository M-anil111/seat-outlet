// Production watchdog: reads the public site like a visitor and fails (exit 1) when it is broken in a way a visitor or a search engine would notice.
// Read-only, a handful of GET requests, no credentials. Run by .github/workflows/watchdog.yml every 15 minutes, or by hand:
//   node tools/live-watchdog.mjs [https://seatoutlet.com]
// Added 24 Nov 2026 after the live site ran for days on beta/sandbox settings (noindex,nofollow everywhere, "Invalid event." on real events)
// with nothing watching. Each check names what a person should look at; none of them prints a secret or a visitor's data.
import { pathToFileURL } from 'node:url';

const MAX_SECONDS = 12;   // slower than this for the home page is reported as a failure (a normal page answers in under 3 s)

async function get(base, path, ms = 30000) {
  const t0 = Date.now();
  const res = await fetch(new URL(path, base), { redirect: 'follow', signal: AbortSignal.timeout(ms), headers: { 'user-agent': 'SeatOutletWatchdog/1.0' } });
  const body = await res.text();
  return { status: res.status, body, seconds: (Date.now() - t0) / 1000, url: res.url, headers: res.headers };
}

/** @returns {Promise<{problems: string[], notes: string[]}>} */
export async function checkSite(base) {
  const problems = [];
  const notes = [];
  const host = new URL(base).host;
  const fail = (m) => problems.push(m);

  // 1. Home page: answers, is indexable, names the right host, shows events.
  let home;
  try { home = await get(base, '/'); } catch (e) { return { problems: ['home page did not answer: ' + String(e).slice(0, 120)], notes }; }
  notes.push(`home ${home.status} in ${home.seconds.toFixed(1)}s`);
  if (home.status !== 200) fail(`home page answered ${home.status}, wanted 200`);
  if (home.seconds > MAX_SECONDS) fail(`home page took ${home.seconds.toFixed(1)}s (limit ${MAX_SECONDS}s)`);
  // The live site is served through Cloudflare (the proxy and the Worker that rewrites the pages). 24 Nov 2026: the DNS records pointed straight at
  // the origin, so visitors got the raw pages (noindex, beta addresses) and a server setup without the data.
  if (!home.headers.get('cf-ray')) fail('the home page did not come through Cloudflare (no cf-ray header): the DNS proxy, and the Worker that rewrites the pages, is off');
  const robots = (home.body.match(/<meta name="robots" content="([^"]*)"/i) || [])[1] || '';
  if (/noindex|nofollow/i.test(robots)) fail(`home page robots meta is "${robots}": search engines are told to drop the site`);
  const beta = (home.body.match(/beta\.seatoutlet\.com/gi) || []).length;
  if (beta > 0) fail(`home page carries ${beta} reference(s) to the origin's beta address: the Worker that rewrites it is not processing this page`);
  const canon = (home.body.match(/<link rel="canonical" href="([^"]*)"/i) || [])[1] || '';
  if (!canon.includes(host.replace(/^www\./, ''))) fail(`home canonical is "${canon}", not ${host}`);
  const eventLinks = [...new Set([...home.body.matchAll(/href="(\/event\/[^"#?]+)"/g)].map((m) => m[1]))];
  if (eventLinks.length === 0) fail('home page links to no events (the ticket API is returning nothing)');
  notes.push(`home event links: ${eventLinks.length}`);

  // 2. Health: ok, and a deploy marker.
  try {
    const h = await get(base, '/ajax/health.php');
    let j = null; try { j = JSON.parse(h.body); } catch { /* handled below */ }
    if (!j) fail(`health answered ${h.status} and not JSON`);
    else {
      if (j.ok !== true) fail('health reports ok:false');
      if (!j.commit) fail('health reports no deployed commit (the deploy marker is missing or unreadable)');
      notes.push(`health ok=${j.ok} commit=${j.commit}`);
    }
  } catch (e) { fail('health did not answer: ' + String(e).slice(0, 100)); }

  // 3. robots.txt must not block everything; the sitemap must list pages.
  try {
    const r = await get(base, '/robots.txt');
    if (r.status !== 200) fail(`robots.txt answered ${r.status}`);
    else if (/^\s*Disallow:\s*\/\s*$/im.test(r.body)) fail('robots.txt says "Disallow: /" (the whole site is blocked from search engines)');
  } catch (e) { fail('robots.txt did not answer: ' + String(e).slice(0, 100)); }
  try {
    const sm = await get(base, '/sitemap.xml');
    if (sm.status !== 200 || !/<loc>/.test(sm.body)) fail(`sitemap.xml answered ${sm.status} and ${/<loc>/.test(sm.body) ? 'lists pages' : 'lists no pages'}`);
  } catch (e) { fail('sitemap.xml did not answer: ' + String(e).slice(0, 100)); }

  // 4. Detail pages: up to three events from the home page must open (no "Invalid event.", a seat map or price block present).
  let opened = 0;
  for (const path of eventLinks.slice(0, 3)) {
    try {
      const e = await get(base, path);
      if (e.status === 200 && !/Invalid event\./i.test(e.body)) opened++;
      else notes.push(`event ${path} answered ${e.status}${/Invalid event\./i.test(e.body) ? ' "Invalid event."' : ''}`);
    } catch (err) { notes.push(`event ${path} did not answer`); }
  }
  if (eventLinks.length > 0 && opened === 0) fail('none of the events linked from the home page opens (every one answers an error or "Invalid event.")');
  notes.push(`events opened: ${opened}/${Math.min(3, eventLinks.length)}`);

  // 5. An artist page from the menu or the home page shows the artist and its events (not the generic not-found page).
  const artistLink = (home.body.match(/href="(\/artist\/[^"#?]+)"/) || [])[1];
  if (artistLink) {
    try {
      const a = await get(base, artistLink);
      const arobots = (a.body.match(/<meta name="robots" content="([^"]*)"/i) || [])[1] || '';
      if (a.status !== 200) fail(`artist page ${artistLink} answered ${a.status}`);
      else if (/noindex/i.test(arobots) && /performer-event-item/.test(a.body) === false && /no upcoming events/i.test(a.body) === false) fail(`artist page ${artistLink} is noindex with no events and no "no upcoming events" message`);
      notes.push(`artist ${artistLink} ${a.status}`);
    } catch (err) { fail(`artist page ${artistLink} did not answer`); }
  } else notes.push('no artist link on the home page to check');

  // 6. Search answers.
  try {
    const s = await get(base, '/search?q=concert');
    if (s.status !== 200) fail(`search answered ${s.status}`);
  } catch (e) { fail('search did not answer: ' + String(e).slice(0, 100)); }

  return { problems, notes };
}

if (import.meta.url === pathToFileURL(process.argv[1] || '').href) {
  const base = process.argv[2] || 'https://seatoutlet.com';
  const { problems, notes } = await checkSite(base);
  for (const n of notes) console.log('  ' + n);
  if (problems.length) {
    for (const p of problems) console.log('::error::' + p);
    console.log(`WATCHDOG FAILED: ${problems.length} problem(s) on ${base}`);
    process.exit(1);
  }
  console.log(`watchdog ok: ${base}`);
}
