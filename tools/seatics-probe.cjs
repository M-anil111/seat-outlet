// Temporary: outline of the Seatics widget DOM on a beta event page (never merged).
const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch();
  for (const w of [1440, 390]) {
    const p = await b.newPage({ viewport: { width: w, height: 1000 } });
    await p.goto(process.env.URL, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await p.waitForSelector('#tn-maps .venue-ticket-list, #tn-maps tr.Sea-TicketRow', { timeout: 45000 }).catch(() => {});
    await p.waitForTimeout(6000);
    const out = await p.evaluate(() => {
      const lines = [];
      const walk = (el, d) => {
        if (d > 7 || lines.length > 400) return;
        const r = el.getBoundingClientRect(); const cs = getComputedStyle(el);
        if (cs.display === 'none' && d > 1) return;
        const cls = typeof el.className === 'string' ? el.className.trim().split(/\s+/).slice(0, 4).join('.') : '';
        lines.push(' '.repeat(d * 2) + el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (cls ? '.' + cls : '') + ` [${Math.round(r.x)},${Math.round(r.y)} ${Math.round(r.width)}x${Math.round(r.height)}]` + (el.children.length === 0 && el.textContent.trim() ? ' "' + el.textContent.trim().slice(0, 40) + '"' : ''));
        for (const c of el.children) if (!['svg', 'script', 'style', 'path', 'g'].includes(c.tagName.toLowerCase())) walk(c, d + 1);
      };
      const m = document.querySelector('#tn-maps'); if (m) walk(m, 0);
      return lines.join('\n');
    });
    console.log('=== WIDTH', w, '===\n' + out);
    const shot = await p.screenshot({ type: 'jpeg', quality: 45, fullPage: false });
    console.log('=== SHOT', w, shot.toString('base64'), 'ENDSHOT');
    await p.close();
  }
  await b.close();
})();
