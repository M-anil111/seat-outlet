// CI: a home page feed row that comes back empty keeps its place instead of collapsing under the visitor (SE Ranking audit, 12 Nov 2026:
// CLS 0.307 on the home page; the "Last-minute tickets" row, already on screen as skeleton cards, was hidden when its feed was empty and
// everything below it jumped up). The row stays, says it has nothing to show and links to all events.
// Run: node tools/test-home-feed-cls.mjs http://127.0.0.1:8161
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const base = (process.argv[2] || 'http://127.0.0.1:8161').replace(/\/$/, '');
let fail = 0;
const check = (name, ok, detail = '') => { if (!ok) { console.log(`FAIL ${name}${detail ? ': ' + detail : ''}`); fail++; } else console.log(`ok   ${name}`); };

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1350, height: 940 } });
// Every feed answers "nothing": the case a throttled or empty ticket feed produces.
await page.route('**/ajax/get-home-feed.php*', route => route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ events: [], scope: 'near', total: 0 }) }));
await page.addInitScript(() => {
  window.__cls = 0;
  new PerformanceObserver(list => { for (const e of list.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: 'layout-shift', buffered: true });
  // The row's height as the server drew it (skeleton cards), before any feed answer arrives.
  document.addEventListener('DOMContentLoaded', () => { const b = document.getElementById('soFeedLastMinute'); window.__h0 = b ? Math.round(b.getBoundingClientRect().height) : -1; });
});
await page.goto(base + '/', { waitUntil: 'load', timeout: 90000 });
const before = await page.evaluate(() => window.__h0 ?? -1);
await page.waitForTimeout(3000);
const state = await page.evaluate(() => {
  const b = document.getElementById('soFeedLastMinute');
  if (!b) return null;
  const empty = b.querySelector('.so-feed__empty');
  const link = empty ? empty.querySelector('a[href]') : null;
  return { hidden: b.hidden, height: Math.round(b.getBoundingClientRect().height), empty: !!empty, text: empty ? empty.textContent.trim() : '', href: link ? link.getAttribute('href') : '', cls: window.__cls };
});
check('the last-minute row is on the page', state !== null && before > 0, JSON.stringify({ before }));
if (state) {
  check('an empty feed does not hide the row', state.hidden === false);
  check('the row keeps its height (neither collapses nor grows)', Math.abs(state.height - before) <= 2, `${before}px -> ${state.height}px`);
  check('the row says it has nothing to show', state.empty && /no events/i.test(state.text), state.text);
  check('the row links to all events', state.href === '/buy-tickets-online', state.href);
  check('layout shift stays under 0.1', state.cls < 0.1, state.cls.toFixed(3));
}
// A row whose events arrive keeps its height: the skeleton cards are as tall as the event cards that replace them (live home page,
// 14 Nov 2026: rows grew from 338px to 405px when their events arrived). The feed answers after the stylesheets are in.
{
  const fixture = require('node:fs').readFileSync(new URL('./fixtures/home-feed.json', import.meta.url));
  const page2 = await browser.newPage({ viewport: { width: 1350, height: 940 } });
  await page2.route('**/ajax/get-home-feed.php*', async route => { await new Promise(r => setTimeout(r, 3000)); await route.fulfill({ status: 200, contentType: 'application/json', body: fixture }).catch(() => {}); });
  await page2.goto(base + '/', { waitUntil: 'load', timeout: 90000 });
  await page2.waitForTimeout(1500);
  const rows = () => page2.evaluate(() => Object.fromEntries(['soFeedLastMinute', 'soFeedTrending'].map(id => { const b = document.getElementById(id); return [id, b && !b.hidden ? Math.round(b.getBoundingClientRect().height) : -1]; })));
  const before2 = await rows();
  await page2.waitForTimeout(4500);
  const after2 = await rows();
  const filled = await page2.evaluate(() => document.querySelectorAll('#soFeedLastMinute .so-evc:not(.so-evc--skeleton)').length);
  check('events replaced the skeleton cards', filled > 0, String(filled));
  for (const id of Object.keys(before2)) check(`${id} keeps its height when its events arrive`, before2[id] > 0 && Math.abs(after2[id] - before2[id]) <= 8, `${before2[id]}px -> ${after2[id]}px`);
  await page2.close();
}
await browser.close();
if (fail) { console.log(`${fail} check(s) failed`); process.exit(1); }
console.log('ok: home feed rows keep their place');
