// CI: when "More Events" cannot load the next page, the visitor sees why and can try again. 20 Nov 2026, /artist/lizzy-mcalpine: the
// artist page (and the artist-by-location and category-by-location listings) had no #loadMoreError, so a failed load was silent and the
// button looked broken. Builds a listing with the page's real button markup (soLoadMoreErrorHtml() from inc/listing.php) and the real
// js/events-listing.min.js, answers the load-more request first with a failure (no events while more are promised), then with rows.
// Also checks every PHP page that prints the button prints the message. Run: node tools/test-load-more-error.mjs
import { createRequire } from 'node:module';
import { readFileSync, readdirSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

let chromium;
try { ({ chromium } = createRequire(import.meta.url)('playwright')); } catch { try { ({ chromium } = await import('playwright')); } catch { console.error('playwright is not installed'); process.exit(2); } }

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
let fail = 0;
const check = (name, got, want) => { if (got !== want) { console.log(`FAIL ${name}: got ${JSON.stringify(got)}, wanted ${JSON.stringify(want)}`); fail++; } };

// Wiring: every "More Events" button in the PHP pages has the message printed in the same wrapper.
const phpFiles = ['performer.php', 'functions.php', 'inc/listing.php', ...readdirSync(root).filter(f => f.endsWith('.php')).filter(f => !['performer.php', 'functions.php'].includes(f))];
let buttons = 0, messages = 0;
for (const f of new Set(phpFiles)) {
  const src = readFileSync(path.join(root, f), 'utf8');
  const b = (src.match(/id="loadMoreBtn"/g) || []).length;
  if (!b) continue;
  const m = (src.match(/<\?php echo soLoadMoreErrorHtml\(\); \?>\s*<button[\s\S]{0,400}?id="loadMoreBtn"/g) || []).length;
  check(`${f}: every More Events button has the error message right before it`, m, b);
  buttons += b; messages += m;
}
check('the button is printed by the artist page, both location listings and the shared renderer (4)', buttons, 4);

let errorHtml = '';
try { errorHtml = execFileSync('php', ['-r', 'require $argv[1]; echo function_exists("soLoadMoreErrorHtml") ? soLoadMoreErrorHtml() : "";', path.join(root, 'inc/listing.php')]).toString(); } catch { errorHtml = ''; }
check('inc/listing.php has soLoadMoreErrorHtml()', errorHtml !== '', true);
check('the message carries the ids js/events-listing.js uses', /id="loadMoreError"/.test(errorHtml) && /id="loadMoreRetry"/.test(errorHtml), true);
const js = readFileSync(path.join(root, 'js/events-listing.min.js'), 'utf8');

const row = (d) => `<div class="d-flex align-items-center justify-content-between performer-event-item" data-so-day="${d}"><div class="date-box"><div class="month">JUN</div><div class="day">${d.slice(8)}</div></div><div class="flex-grow-1">Row ${d}</div></div>`;
const first = Array.from({ length: 20 }, (_, i) => row(`2027-05-${String(i + 1).padStart(2, '0')}`)).join('');
const page = `<!doctype html><html><head><meta charset="utf-8"><style>[hidden]{display:none}.d-none{display:none}</style></head><body>
<div id="eventsSection" class="section-artist-content event-row-all">${first}</div>
<div class="load-more-wrapper">${errorHtml}
<button class="btn more-events-btn" id="loadMoreBtn" data-total="32" data-page="2" data-params='{"performerFilter":"id eq 1","sort":"date/date"}' data-perpage="20"><span class="btn-text">More Events</span><span class="btnSpinner d-none"></span></button>
<button class="btn more-events-btn d-none" id="backToTopJs">Back to Top</button></div>
<script>${js}</script></body></html>`;

const browser = await chromium.launch();
const p = await browser.newPage();
const errors = [];
p.on('pageerror', (e) => errors.push(e.message));
let calls = 0, mode = 'fail';
await p.route('**/*', async (route) => {
  const u = new URL(route.request().url());
  if (u.pathname === '/artist/zq') return route.fulfill({ status: 200, contentType: 'text/html', body: page });
  if (u.pathname === '/ajax/load-more-events.php') {
    calls++;
    const body = mode === 'fail'
      ? { events: [], html: '', totalCount: 32, currentPage: 2, nextPage: null, hasMore: false }
      : { events: Array.from({ length: 12 }, (_, i) => ({ id: i + 1 })), html: Array.from({ length: 12 }, (_, i) => row(`2027-06-${String(i + 1).padStart(2, '0')}`)).join(''), totalCount: 32, currentPage: 2, nextPage: null, hasMore: false };
    return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(body) });
  }
  return route.fulfill({ status: 404, body: '' });
});
await p.goto('http://so.test/artist/zq');
const state = () => p.evaluate(() => ({
  rows: document.querySelectorAll('#eventsSection .performer-event-item').length,
  errorVisible: !!document.getElementById('loadMoreError') && !document.getElementById('loadMoreError').hidden,
  moreHidden: document.getElementById('loadMoreBtn').classList.contains('d-none'),
  backShown: !document.getElementById('backToTopJs').classList.contains('d-none'),
}));

await p.click('#loadMoreBtn');
await p.waitForFunction(() => !document.getElementById('loadMoreBtn').disabled);
let s = await state();
check('failed load: one request', calls, 1);
check('failed load: the message is shown (not silent)', s.errorVisible, true);
check('failed load: no row is added', s.rows, 20);
check('failed load: More Events stays', s.moreHidden, false);

mode = 'ok';
const retry = await p.$('#loadMoreRetry');
check('the message has a Try again button', !!retry, true);
if (retry) {
  await retry.click();
  await p.waitForFunction(() => document.querySelectorAll('#eventsSection .performer-event-item').length > 20, null, { timeout: 5000 }).catch(() => {});
}
s = await state();
check('Try again: a second request', calls, 2);
check('Try again: the 12 rows are added', s.rows, 32);
check('Try again: the message goes away', s.errorVisible, false);
check('Try again: the list is complete, More Events gives way to Back to Top', s.moreHidden && s.backShown, true);
check('no script error', errors.join(' | '), '');
await browser.close();

if (fail) { console.log(`${fail} failure(s)`); process.exit(1); }
console.log('ok: a failed "More Events" load says so and can be retried, on every page with the button');
