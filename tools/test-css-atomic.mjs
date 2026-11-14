// CI: the page's stylesheet parts take effect together, never one at a time.
// A page with critical CSS loads its full stylesheets after the first paint. They used to be added one by one, and each applied the
// moment it arrived: with css/style.home.1, .2 and .3 the page went through partial states. Parts 1 and 2 re-declared the top bar's
// base rule (wrap) over the inline desktop rule (nowrap), so the bar grew from 44px to 61px, then part 3 shrank it back. Live home
// page, 14 Nov 2026: CLS 1.29 on desktop. Here part 3 arrives 1.2 s after the others, as a slow connection would deliver it.
// Run: node tools/test-css-atomic.mjs http://127.0.0.1:8161
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const base = (process.argv[2] || 'http://127.0.0.1:8161').replace(/\/$/, '');
let fail = 0;
const check = (name, ok, detail = '') => { if (!ok) { console.log(`FAIL ${name}${detail ? ': ' + detail : ''}`); fail++; } else console.log(`ok   ${name}`); };

const browser = await chromium.launch();
for (const path of ['/', '/policies']) {
  const page = await browser.newPage({ viewport: { width: 1350, height: 940 } });
  await page.route(/\/css\/style\.[a-z]+\.3\.min\.css/, async route => { await new Promise(r => setTimeout(r, 1200)); await route.continue(); });
  await page.route('**/ajax/get-home-feed.php*', route => route.fulfill({ status: 200, contentType: 'application/json', body: '{"events":[],"scope":"near","total":0}' }));
  await page.addInitScript(() => {
    window.__cls = 0; window.__bar = [];
    new PerformanceObserver(list => { for (const e of list.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: 'layout-shift', buffered: true });
    const tick = () => { const el = document.querySelector('.tm-topbar'); if (el) { const h = Math.round(el.getBoundingClientRect().height); if (h > 0 && window.__bar[window.__bar.length - 1] !== h) window.__bar.push(h); } if (performance.now() < 6000) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
  });
  await page.goto(base + path, { waitUntil: 'load', timeout: 90000 });
  await page.waitForTimeout(4000);
  const r = await page.evaluate(() => ({ cls: window.__cls, bar: window.__bar, sheets: [...document.querySelectorAll('link[rel=stylesheet]')].filter(l => /\/css\/style\./.test(l.href)).map(l => l.media || 'all') }));
  check(`${path}: every stylesheet part is applied in the end`, r.sheets.length >= 1 && r.sheets.every(m => m === 'all'), r.sheets.join(','));
  check(`${path}: the top bar keeps one height while the stylesheets arrive`, r.bar.length <= 1, r.bar.join('px -> ') + 'px');
  check(`${path}: layout shift stays under 0.1`, r.cls < 0.1, r.cls.toFixed(3));
  await page.close();
}
await browser.close();
if (fail) { console.log(`${fail} check(s) failed`); process.exit(1); }
console.log('ok: stylesheet parts apply together');
