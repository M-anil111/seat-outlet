// Event page seat-map wiring (js/event-widget.min.js, the file the page ships) in a real browser, with a stand-in for the Seatics widget:
// no network and no database. Checks, on a phone-sized and a desktop-sized screen and in both precheckout modes:
//   - the review-screen flags (skipPrecheckoutMobile / skipPrecheckoutDesktop) follow ev.precheckout, default "skip";
//   - the hosted-checkout hand-off settings are the same either way;
//   - begin_checkout is sent once however many times the widget reports the same click, with the clicked price and quantity;
//   - the seat map is not broken: FinishedLoading shows no error state and sends view_item once.
// Run: node tools/test-event-widget.mjs   (needs Playwright; see tools/test-csp-browser.mjs)
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { createRequire } from 'node:module';

const here = dirname(fileURLToPath(import.meta.url));
const script = readFileSync(join(here, '..', 'js', process.env.SO_WIDGET_FILE || 'event-widget.min.js'), 'utf8');
let chromium;
try { ({ chromium } = createRequire(import.meta.url)('playwright')); } catch { try { ({ chromium } = await import('playwright')); } catch { console.error('playwright is not installed'); process.exit(2); } }

const WIDGET = 'https://mapwidget3.seatics.com/widget.js';
const stub = `window.Seatics = { config: {}, SortOptions: { PriceAsc: 'priceAsc' }, TrackingEvents: { registerEventListener: function (fn) { window.__track = fn; } } };`;
const html = (ev) => `<!doctype html><html><head><meta charset="utf-8"></head><body>
<div class="so-evhero__price">From $50</div><a data-so-cta href="#tn-maps">Find tickets</a>
<div id="so-no-tickets" class="d-none"><p id="so-no-tickets-title"></p><p id="so-no-tickets-text"></p><button id="so-no-tickets-reload" hidden></button></div>
<div id="tn-maps"></div>
<script type="application/json" id="so-event-data">${JSON.stringify(ev)}</script>
<script src="https://fixture.test/event-widget.js"></script></body></html>`;
const baseEv = { id: 4242, name: 'Fixture Show', currency: 'USD', lowPrice: 50, price: '$50', tickets: 10, hasTickets: true, widgetUrl: WIDGET,
  checkoutUrl: 'https://checkout.seatoutlet.com/checkout', checkoutDomain: 'checkout.seatoutlet.com', venue: 'Fixture Hall', catName: 'concerts' };

const browser = await chromium.launch();
let failures = 0;
const ok = (label, good, detail = '') => { console.log((good ? 'ok   ' : 'FAIL ') + label + (good || !detail ? '' : ': ' + detail)); if (!good) failures++; };

async function open(viewport, ev) {
  const ctx = await browser.newContext({ viewport });
  const page = await ctx.newPage();
  await page.route('https://fixture.test/**', (r) => r.fulfill(r.request().url().endsWith('.js') ? { contentType: 'text/javascript', body: script } : { contentType: 'text/html', body: html(ev) }));
  await page.route(WIDGET, (r) => r.fulfill({ contentType: 'text/javascript', body: stub }));
  const errs = [];
  page.on('pageerror', (e) => errs.push(String(e.message || e)));
  await page.goto('https://fixture.test/event', { waitUntil: 'load' });
  await page.waitForFunction(() => window.Seatics && window.Seatics.config && typeof window.Seatics.config.onBuyButtonClicked === 'function', null, { timeout: 5000 });
  return { ctx, page, errs };
}
const events = (page, name) => page.evaluate((n) => (window.dataLayer || []).filter((e) => e.event === n), name);

for (const [screen, viewport] of [['phone', { width: 390, height: 844 }], ['desktop', { width: 1280, height: 900 }]]) {
  for (const mode of ['default', 'review']) {
    const ev = mode === 'review' ? { ...baseEv, precheckout: 'review' } : { ...baseEv };
    const { ctx, page, errs } = await open(viewport, ev);
    const tag = `${screen}, ${mode}`;
    const cfg = await page.evaluate(() => JSON.parse(JSON.stringify(window.Seatics.config, (k, v) => typeof v === 'function' ? '[fn]' : v)));
    const want = mode === 'review' ? false : true;
    ok(`${tag}: mobile and desktop review-screen flags are ${want ? 'skip' : 'show'}`, cfg.skipPrecheckoutMobile === want && cfg.skipPrecheckoutDesktop === want, JSON.stringify([cfg.skipPrecheckoutMobile, cfg.skipPrecheckoutDesktop]));
    ok(`${tag}: hosted-checkout hand-off settings are unchanged`, cfg.checkoutUrl === baseEv.checkoutUrl && cfg.c3CheckoutDomain === baseEv.checkoutDomain && cfg.preCheckoutButtonHtml === 'Continue to Payment');
    ok(`${tag}: quantity sheet and sort are unchanged`, cfg.enableQuantityModal === true && cfg.defaultSort === 'priceAsc');

    // the seat map loads
    await page.evaluate(() => window.__track('FinishedLoading', { numTicketGroups: 5, numTickets: 20 }));
    ok(`${tag}: a loaded seat map shows no error or empty state`, await page.evaluate(() => document.getElementById('so-no-tickets').classList.contains('d-none') && !document.documentElement.classList.contains('so-ev-empty')));
    ok(`${tag}: view_item is sent once`, (await events(page, 'view_item')).length === 1);

    // the same buy click reported by the config callback and by the tracking listener
    if (mode === 'review') await page.evaluate(() => window.__track('PreCheckoutShown', {}));
    await page.evaluate(() => { const p = { price: 120, quantity: 2, ticketGroupId: 'tg9' }; window.Seatics.config.onBuyButtonClicked(p); window.__track('BuyButtonClicked', p); });
    await page.waitForTimeout(400);
    let buys = await events(page, 'begin_checkout');
    ok(`${tag}: begin_checkout is sent once for one click`, buys.length === 1, String(buys.length));
    ok(`${tag}: it carries the clicked price, quantity and listing`, buys[0] && buys[0].ecommerce.value === 240 && buys[0].ecommerce.items[0].quantity === 2 && buys[0].ecommerce.items[0].item_variant === 'tg9' && buys[0].value_estimated === false, JSON.stringify(buys[0]));
    ok(`${tag}: the Buy-click marker for /order-confirmation is set for this event`, await page.evaluate(() => /(?:^|; )so_checkout=\d+\.4242(?:;|$)/.test(document.cookie)));
    if (mode === 'review') {
      ok(`${tag}: the review screen is reported once as precheckout_viewed`, (await events(page, 'precheckout_viewed')).length === 1);
      // a second buy click after the review screen ("Continue to Payment") is the same checkout, not another begin_checkout
      await page.waitForTimeout(1700);
      await page.evaluate(() => window.__track('BuyButtonClicked', { price: 120, quantity: 2, ticketGroupId: 'tg9' }));
      await page.waitForTimeout(400);
      ok(`${tag}: "Continue" after the review screen does not send a second begin_checkout`, (await events(page, 'begin_checkout')).length === 1);
    } else {
      ok(`${tag}: no precheckout_viewed without the review screen`, (await events(page, 'precheckout_viewed')).length === 0);
      await page.waitForTimeout(1700);
      await page.evaluate(() => window.__track('BuyButtonClicked', { price: 80, quantity: 1, ticketGroupId: 'tg1' }));
      await page.waitForTimeout(400);
      ok(`${tag}: a later, separate buy click (other listing) still counts`, (await events(page, 'begin_checkout')).length === 2);
    }
    ok(`${tag}: no uncaught script error`, errs.length === 0, errs.join(' | '));
    await ctx.close();
  }
}

// the failure paths still work (the seat map not loading must not be hidden by the new code)
{
  const { ctx, page } = await open({ width: 1280, height: 900 }, { ...baseEv, precheckout: 'review' });
  await page.evaluate(() => window.Seatics.config.noTicketsHandler());
  ok('review mode: "no tickets" still shows the empty state', await page.evaluate(() => !document.getElementById('so-no-tickets').classList.contains('d-none')));
  ok('review mode: no_inventory is sent', (await events(page, 'no_inventory')).length === 1);
  await ctx.close();
}
await browser.close();
process.exit(failures ? 1 : 0);
