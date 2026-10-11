/* Real shared HTML, CSS and JS with controlled fetch responses. Run after activating the PHP runtime.
   Requires Playwright and Chromium. No services, real customer data, or outbound requests. */
const { chromium } = require('playwright');
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const html = execFileSync('php', [path.join(__dirname, 'test-listing-controls.php'), '--html'], { encoding: 'utf8' });
(async () => {
  const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH || (fs.existsSync('/usr/bin/chromium') ? '/usr/bin/chromium' : chromium.executablePath()), args: ['--no-sandbox'] });
  try {
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.route('**/*', route => {
      const url = new URL(route.request().url());
      if (url.hostname !== 'fixture.test') return route.abort();
      const file = url.pathname;
      if (file === '/') return route.fulfill({ contentType: 'text/html', body: html });
      if (!['/css/style.css','/css/bootstrap.min.css','/js/events-listing.js'].includes(file)) return route.fulfill({ status: 404, body: '' });
      return route.fulfill({ contentType: file.endsWith('.css') ? 'text/css' : 'application/javascript', body: fs.readFileSync(root + file) });
    });
    for (const width of [360, 390, 768, 1440]) {
      await page.setViewportSize({ width, height: 900 });
      await page.goto('http://fixture.test/');
      for (const index of [0,1,2]) {
      await page.locator('summary').nth(index).click();
      await page.waitForTimeout(180);
      const geometry = await page.locator('.so-dd__menu').nth(index).evaluate(menu => {
        const rect = menu.getBoundingClientRect();
        const summary = menu.parentElement.querySelector('summary').getBoundingClientRect();
        const last = menu.lastElementChild.getBoundingClientRect();
        const hit = document.elementFromPoint(last.left + 10, last.top + last.height / 2);
        return { height: summary.height, left: rect.left, right: rect.right, visible: !!hit && menu.contains(hit), overflow: document.documentElement.scrollWidth > innerWidth };
      });
      assert(geometry.height >= 44, `Small target at ${width}`);
      assert(geometry.left >= 0 && geometry.right <= width, `Menu outside viewport at ${width}`);
      assert(geometry.visible && !geometry.overflow, `Clipped menu or page overflow at ${width}`);
      await page.keyboard.press('Escape');
      assert(await page.locator('summary').nth(index).evaluate(el => el === document.activeElement));
      }
      console.log(`ok   ${width}px: usable date/price/sort menus, 44px targets, Escape restores focus`);
    }
    // Keep requests deliberately pending and ignore abort, proving the generation guard works too.
    await page.evaluate(() => {
      window.pending = [];
      window.fetch = (url, options) => new Promise((resolve, reject) => pending.push({ url: String(url), resolve, reject, signal: options.signal }));
      window.reply = (index, name, total = 1, hasMore = false) => pending[index].resolve({ ok: true, json: () => Promise.resolve({ events: name ? [{id: 1}] : [], html: name ? '<div class="performer-event-item">' + name + '</div>' : '', totalCount: total, nextPage: 2, hasMore, params: { geoFilter: 'nearby(30.1,-97.7,3000mi)', sort: 'pricingInfo/lowPrice/value', filter: '_metadata/hasTickets eq true' } }) });
      updateEventsSection({ when: 'week' });
      updateEventsSection({ when: 'today' });
      reply(1, 'New selection', 4, true);
    });
    await page.waitForFunction(() => document.getElementById('eventsSection').textContent === 'New selection');
    await page.evaluate(() => reply(0, 'Old selection'));
    await page.waitForTimeout(30);
    assert.equal(await page.locator('#eventsSection').textContent(), 'New selection');
    const params = JSON.parse(await page.locator('#loadMoreBtn').getAttribute('data-params'));
    assert.equal(params.geoFilter, 'nearby(30.1,-97.7,3000mi)');
    await page.evaluate(() => { soLoadMore(document.getElementById('loadMoreBtn')); updateEventsSection({ when: 'month' }); reply(3, 'Filtered selection'); reply(2, 'Old next page'); });
    await page.waitForFunction(() => document.getElementById('eventsSection').textContent === 'Filtered selection');
    assert.equal(await page.locator('#eventsSection').textContent(), 'Filtered selection');
    await page.evaluate(() => { updateEventsSection({ when: 'today' }); reply(4, '', 0); });
    await page.waitForFunction(() => document.getElementById('eventsSection').textContent === '');
    assert.equal(await page.locator('#totalCount').textContent(), '0');
    assert.equal(await page.locator('#loadedCount').textContent(), '0');
    assert(await page.locator('#loadMoreBtn').evaluate(el => el.classList.contains('d-none')));
    await page.evaluate(() => { updateEventsSection({}); reply(5, 'Retained event'); });
    await page.waitForFunction(() => document.getElementById('eventsSection').textContent === 'Retained event');
    await page.evaluate(() => { updateEventsSection({}); pending[6].resolve({ok:false,status:503}); });
    await page.waitForFunction(() => document.getElementById('location-no-results').textContent.includes('Could not load'));
    assert.equal(await page.locator('#eventsSection').textContent(), 'Retained event');
    await page.evaluate(() => { document.getElementById('sdateEvent').value='2027-01-01'; document.getElementById('edateEvent').value='2027-01-10'; });
    await page.locator('[data-so-sort="price"]').click();
    const url = await page.evaluate(() => pending[7].url);
    assert(url.includes('startDate=2027-01-01') && url.includes('endDate=2027-01-10') && url.includes('sort=price'));
    await page.evaluate(() => reply(7, 'Sorted event'));
    await page.locator('[data-so-when="today"]').click();
    assert.equal(await page.locator('#sdateEvent').inputValue(), '');
    await page.evaluate(() => reply(8, 'Today event'));
    assert.deepEqual(errors, []);
    if (process.env.SCREENSHOT_DIR) {
      fs.mkdirSync(process.env.SCREENSHOT_DIR, {recursive:true});
      for (const width of [390,1440]) {
        await page.setViewportSize({width,height:900});
        await page.locator('summary').first().click();
        await page.waitForTimeout(180);
        await page.screenshot({path:path.join(process.env.SCREENSHOT_DIR, `listing-${width}.png`),fullPage:true});
        await page.keyboard.press('Escape');
      }
    }
    // Header autocomplete: the real script must accept grouped API objects, survive blocked storage,
    // discard old answers and allow the same keyword to retry after a failed HTTP response.
    await page.goto('http://fixture.test/');
    await page.evaluate(() => {
      document.body.innerHTML = '<form><input id="keywordHeader" value="fixture"><div id="keywordResultsHeader"></div><div id="search-loader"></div></form>';
      window.searchPending = [];
      window.fetch = (url, options) => new Promise(resolve => searchPending.push({url:String(url), resolve, signal:options.signal}));
      Storage.prototype.setItem = () => { throw new Error('Storage blocked'); };
      window.searchReply = (i, name, status = 200) => searchPending[i].resolve({ok:status===200,status,json:()=>Promise.resolve({performers:{totalResultCount:1,results:[{id:42,name,slug:'fixture-artist'}]}})});
    });
    await page.addScriptTag({content:fs.readFileSync(path.join(root,'js/main.js'),'utf8')});
    await page.locator('#keywordHeader').fill('fixture');
    await page.waitForFunction(() => searchPending.length === 1);
    await page.locator('#keywordHeader').fill('new artist');
    await page.waitForFunction(() => searchPending.length === 2);
    await page.evaluate(() => {searchReply(1,'New artist');searchReply(0,'Old artist');});
    await page.waitForFunction(() => document.getElementById('keywordResultsHeader').textContent.includes('New artist'));
    assert(!(await page.locator('#keywordResultsHeader').textContent()).includes('Old artist'));
    await page.locator('#keywordHeader').fill('outage');
    await page.waitForFunction(() => searchPending.length === 3);
    await page.evaluate(() => searchReply(2,'ignored',503));
    await page.waitForTimeout(180);
    await page.locator('#keywordHeader').dispatchEvent('input');
    await page.waitForFunction(() => searchPending.length >= 4);
    await page.evaluate(() => searchReply(3,'Recovered artist'));
    await page.waitForFunction(() => document.getElementById('keywordResultsHeader').textContent.includes('Recovered artist'));
    await page.locator('#keywordHeader').fill('x');
    assert.equal(await page.locator('#keywordHeader').getAttribute('aria-expanded'),'false');
    assert.deepEqual(errors, []);
    console.log('ok   autocomplete grouped responses, blocked storage, stale responses, HTTP retry and short-query reset');
    console.log('ok   filter races, pagination races, exact continuation, empty counts, API failures, custom date sort and quick-date reset');
  } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exitCode=1; });
