// CI (needs Playwright and Chromium): the "More Events" button against a mock ajax endpoint, with the real js/events-listing.js and the
// real error block (soLoadMoreErrorHtml in inc/listing.php). No site or database is needed: the page and the endpoint are served by routes.
//   ok            first answer has the rows        -> rows added, no retry, button hidden at the end of the list
//   flaky         first answer is a 503            -> one quiet retry, rows added, no message
//   down          every answer is a 503            -> two requests, the message and "Try again" shown, button usable again, label restored
//   down-then-up  down, then "Try again" works     -> rows added, message hidden
//   empty         an empty page while more are due -> treated as a failure (retry, then the message)
// A failed load used to change nothing on screen (the artist page had no message element), so the click looked dead. Run: node tools/test-load-more-click.mjs
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const root = fileURLToPath(new URL('..', import.meta.url));
const js = readFileSync(root + 'js/events-listing.min.js', 'utf8');   // the file the site serves (soAsset)
const listing = readFileSync(root + 'inc/listing.php', 'utf8');
const errorBlock = (listing.match(/function soLoadMoreErrorHtml\(\): string \{\s*return '(.*?)';\s*\}/s) || [])[1] || '';
if (!errorBlock) { console.log('FAIL the error block helper was not found in inc/listing.php'); process.exit(1); }

const row = (n) => `<div class="performer-event-item" data-so-count="1"><a href="/event/zq-${n}">Zq Event ${n}</a></div>`;
const page = `<!doctype html><html><head><style>.d-none{display:none!important}[hidden]{display:none!important}</style></head><body>   <!-- the two rules Bootstrap supplies on the real pages -->
<div id="eventsSection">${row(1)}${row(2)}</div>
<div class="load-more-wrapper">${errorBlock}
<button class="btn more-events-btn" id="loadMoreBtn" data-total="4" data-page="2" data-params='{"performerFilter":"id eq 1"}' data-perpage="2"><span class="btn-text">More Events</span><span class="btnSpinner spinner-border d-none"></span></button>
<button class="btn d-none" id="backToTopJs">Back to Top</button></div>
<script src="/js/events-listing.min.js"></script></body></html>`;

let fail = 0;
const check = (name, got, want) => { if (JSON.stringify(got) !== JSON.stringify(want)) { console.log(`FAIL ${name}: got ${JSON.stringify(got)}, wanted ${JSON.stringify(want)}`); fail++; } };

const browser = await chromium.launch();
async function run(mode) {
  const page_ = await (await browser.newContext()).newPage();
  let calls = 0;
  const times = [];   // when each endpoint request arrived
  await page_.route('http://zq.test/**', (r) => {
    const u = new URL(r.request().url());
    if (u.pathname === '/artist') return r.fulfill({ status: 200, contentType: 'text/html', body: page });
    if (u.pathname === '/js/events-listing.min.js') return r.fulfill({ status: 200, contentType: 'application/javascript', body: js });
    if (u.pathname === '/ajax/load-more-events.php') {
      calls++; times.push(Date.now());
      const good = { events: [{ id: 3 }, { id: 4 }], html: row(3) + row(4), totalCount: 4, currentPage: 2, nextPage: null, hasMore: false };
      const fail503 = () => r.fulfill({ status: 503, contentType: 'application/json', body: '{}' });
      if (mode === 'ok') return r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(good) });
      if (mode === 'flaky') return calls === 1 ? fail503() : r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(good) });
      if (mode === 'empty') return r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ events: [], html: '', totalCount: 4, currentPage: 2, nextPage: 3, hasMore: true }) });
      if (mode === 'down-then-up') return calls <= 2 ? fail503() : r.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(good) });
      return fail503();   // down
    }
    return r.fulfill({ status: 204, body: '' });
  });
  await page_.goto('http://zq.test/artist');
  const btn = page_.locator('#loadMoreBtn');
  await btn.click();
  // Wait for the end state the mode should reach, not a fixed time (the quiet retry waits 1.5 s).
  const done = { ok: () => page_.waitForFunction(() => document.querySelectorAll('.performer-event-item').length >= 4),
                 flaky: () => page_.waitForFunction(() => document.querySelectorAll('.performer-event-item').length >= 4),
                 down: () => page_.locator('#loadMoreError').waitFor({ state: 'visible', timeout: 10000 }),
                 empty: () => page_.locator('#loadMoreError').waitFor({ state: 'visible', timeout: 10000 }),
                 'down-then-up': () => page_.locator('#loadMoreError').waitFor({ state: 'visible', timeout: 10000 }) }[mode];
  await done();
  await page_.waitForFunction(() => !document.getElementById('loadMoreBtn').hasAttribute('aria-busy'));   // the load has finished either way
  const state = async () => ({
    calls,
    gap: times.length > 1 ? times[1] - times[0] : null,
    rows: await page_.locator('.performer-event-item').count(),
    message: await page_.locator('#loadMoreError').isVisible(),
    buttonVisible: await btn.isVisible(),
    buttonEnabled: await btn.isEnabled().catch(() => false),
    label: (await btn.locator('.btn-text').innerText()).trim(),
    busy: await btn.getAttribute('aria-busy'),
  });
  const s = await state();
  if (mode === 'down-then-up') {
    check('down-then-up: after two failures the message shows', [s.calls, s.message, s.buttonEnabled], [2, true, true]);
    await page_.locator('#loadMoreRetry').click();
    await page_.waitForFunction(() => document.querySelectorAll('.performer-event-item').length >= 4);
    await page_.waitForFunction(() => document.getElementById('loadMoreError').hidden);
    return { ...(await state()), afterRetry: true };
  }
  return s;
}

let s = await run('ok');
check('ok: one request, rows added, no message', [s.calls, s.rows, s.message], [1, 4, false]);
check('ok: the list is complete so the button is hidden', s.buttonVisible, false);
s = await run('flaky');
check('flaky: one quiet retry, rows added, no message', [s.calls, s.rows, s.message], [2, 4, false]);
check('flaky: the retry waits about 1.5 s, not immediately', s.gap !== null && s.gap >= 1400, true);
s = await run('down');
check('down: two requests, no rows added', [s.calls, s.rows], [2, 2]);
check('down: the message is shown', s.message, true);
check('down: the button is usable again, label restored, not busy', [s.buttonVisible, s.buttonEnabled, s.label, s.busy], [true, true, 'More Events', null]);
s = await run('empty');
check('empty: treated as a failure, two requests, message shown', [s.calls, s.rows, s.message], [2, 2, true]);
s = await run('down-then-up');
check('down-then-up: "Try again" loads the rows and hides the message', [s.rows, s.message], [4, false]);
await browser.close();
if (fail) { console.log(`${fail} check(s) failed`); process.exit(1); }
console.log('ok: More Events retries once, then says so, and recovers');
