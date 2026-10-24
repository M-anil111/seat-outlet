// Real-browser CSP check: opens the site's own pages in Chromium with the report-only policy and fails on any violation that is not
// listed in tools/csp-known-violations.json. Usage:  node tools/test-csp-browser.mjs http://127.0.0.1:8160
// Needs the site running and Playwright (npm i playwright; npx playwright install chromium). CSP_ENFORCE must be unset: the check reads
// "securitypolicyviolation" events, which a report-only policy fires too, so nothing is blocked while it looks.
// Pages that need TicketNetwork data are left out on purpose (they may show an empty state in CI); the page frame, footer, banner,
// forms and inline scripts that every page carries are what this covers.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { createRequire } from 'node:module';

const base = (process.argv[2] || process.env.SO_CSP_BASE || '').replace(/\/$/, '');
if (!base) { console.error('Usage: node tools/test-csp-browser.mjs <base url>'); process.exit(2); }
const here = dirname(fileURLToPath(import.meta.url));
const known = JSON.parse(readFileSync(join(here, 'csp-known-violations.json'), 'utf8')).known || [];
const paths = ['/', '/ticket-faq', '/privacy-policy', '/policies', '/sitemap', '/image-credits', '/tickets-promo-code', '/cookie-policy'];

let chromium;
try { ({ chromium } = createRequire(import.meta.url)('playwright')); } catch { try { ({ chromium } = await import('playwright')); } catch { console.error('playwright is not installed'); process.exit(2); } }
const browser = await chromium.launch();
let failures = 0;
const ok = (label, good, detail = '') => { console.log((good ? 'ok   ' : 'FAIL ') + label + (good || !detail ? '' : ': ' + detail)); if (!good) failures++; };

for (const path of paths) {
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await ctx.newPage();
  await page.addInitScript(() => {
    window.__csp = [];
    document.addEventListener('securitypolicyviolation', (e) => window.__csp.push({ directive: e.effectiveDirective, blocked: e.blockedURI, sample: (e.sample || '').slice(0, 80), disposition: e.disposition }));
  });
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e.message || e).slice(0, 160)));
  const resp = await page.goto(base + path, { waitUntil: 'domcontentloaded', timeout: 30000 }).catch((e) => ({ err: e }));
  if (!resp || resp.err) { ok(`${path} loads`, false, resp && resp.err ? String(resp.err.message).slice(0, 120) : 'no response'); await ctx.close(); continue; }
  const status = resp.status();
  const hdrs = resp.headers();
  ok(`${path} answers 200`, status === 200, 'status ' + status);
  ok(`${path} sends the report-only policy and not an enforced one`, !!hdrs['content-security-policy-report-only'] && !hdrs['content-security-policy']);
  await page.waitForLoadState('load', { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(1500);   // late scripts (banner, lead form, analytics bootstrap)
  // use the keyboard and the interactive bits every page has, so handlers that inject scripts or styles run
  await page.keyboard.press('Tab').catch(() => {});
  const v = await page.evaluate(() => window.__csp || []);
  const unexpected = v.filter((x) => !known.some((k) => k.directive === x.directive && (k.blocked === x.blocked || (k.blockedHost && String(x.blocked).includes(k.blockedHost)))));
  ok(`${path}: no violation outside the known list (${v.length} seen, ${v.length - unexpected.length} known)`, unexpected.length === 0, JSON.stringify(unexpected.slice(0, 5)));
  ok(`${path}: no uncaught script error`, errors.length === 0, errors.join(' | '));
  await ctx.close();
}
// Canary: the check must be able to see a violation. A script from a host the policy does not list has to be reported; if it is not, a
// clean run above means nothing (a browser that ignores the header, a listener that never attached).
{
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await page.addInitScript(() => { window.__csp = []; document.addEventListener('securitypolicyviolation', (e) => window.__csp.push({ directive: e.effectiveDirective, blocked: e.blockedURI })); });
  await page.goto(base + '/privacy-policy', { waitUntil: 'load', timeout: 30000 }).catch(() => {});
  await page.evaluate(() => { const s = document.createElement('script'); s.src = 'https://csp-canary.invalid/x.js'; document.head.appendChild(s); });
  await page.waitForTimeout(800);
  const seen = await page.evaluate(() => window.__csp || []);
  ok('canary: a script from an unlisted host is reported as a violation', seen.some((x) => String(x.blocked).includes('csp-canary.invalid') && x.directive.startsWith('script-src')), JSON.stringify(seen));
  await ctx.close();
}
await browser.close();
process.exit(failures ? 1 : 0);
