#!/usr/bin/env node
/**
 * Critical CSS per page type: the rules a page needs for what is on screen when it first paints, so header.php can put them in
 * the <head> and load the full stylesheets without blocking the first paint (the biggest mobile speed gain).
 *
 * How: for each page type (inc/css-groups.php) it opens real pages of that type in headless Chromium at four screen sizes, takes
 * every rule of css/bootstrap.min.css and the page type's css/style.<page type>*.min.css, and keeps a rule when one of its selectors
 * matches an element that is on screen (or hidden inside something that is on screen, like a menu in the header) at that size.
 * The kept rules stay in their original order and inside their original @media blocks, so the cascade is the same as the full files'.
 * Writes css/critical/<page type>.css.
 *
 *   node tools/critical-css.cjs --base=http://127.0.0.1:8140 [--groups=home,browse] [--urls=tools/critical-urls.json] [--out=css/critical]
 *
 * Needs: the site running with its pages able to render (a mock or real ticket API), the global `playwright` package
 * (NODE_PATH=$(npm root -g)), and `postcss` and `clean-css` (npm i in a scratch folder, NODE_PATH to its node_modules).
 * Run it again after a change that adds or restyles something near the top of a page type. It is not part of CI: the build cannot
 * start the site. A rule missing from the critical file is never wrong, only late: the full stylesheet still brings it.
 */
const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const CleanCSS = require('clean-css');
const { chromium } = require('playwright');

const args = Object.fromEntries(process.argv.slice(2).map(a => { const m = a.match(/^--([^=]+)=(.*)$/); return m ? [m[1], m[2]] : [a.replace(/^--/, ''), '1']; }));
const root = path.resolve(__dirname, '..');
const base = (args.base || 'http://127.0.0.1:8140').replace(/\/$/, '');
const outDir = path.resolve(root, args.out || 'css/critical');
const urlMap = JSON.parse(fs.readFileSync(path.resolve(root, args.urls || 'tools/critical-urls.json'), 'utf8'));
const only = args.groups ? args.groups.split(',') : Object.keys(urlMap);
const VIEWPORTS = [[390, 844], [768, 1024], [1280, 800], [1440, 900]];
const EXE = process.env.CHROME_PATH || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome';

/** Every style rule of a stylesheet with an id and the at-rule chain around it: [{id, selector, chain:[{name, params}]}]. */
function listRules(cssText, idOffset) {
  const root_ = postcss.parse(cssText);
  const rules = [];
  let id = idOffset;
  root_.walkRules(rule => {
    if (rule.parent && rule.parent.type === 'atrule' && /keyframes$/i.test(rule.parent.name)) return;   // keyframe steps are not selectors
    const chain = [];
    for (let p = rule.parent; p && p.type !== 'root'; p = p.parent) { if (p.type === 'atrule') chain.unshift({ name: p.name, params: p.params }); }
    rule.raws.soId = id;
    rules.push({ id: id++, selector: rule.selector, chain });
  });
  return { root: root_, rules, next: id };
}

/** Runs in the page: which of these rules match something on screen right now. */
function matchOnScreen(rules) {
  const H = window.innerHeight;
  const rendered = el => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0; };
  const onScreen = el => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < H; };
  // The last element, in page order, that is on screen. Everything that comes before it in the page (or contains it) is part of the
  // first screen's markup even when it is hidden or positioned off screen (the skip link, a menu in the header), so its rules are
  // critical; a modal printed at the end of the page is after it and is not.
  let lastOnScreen = null;
  // Fixed bars (the consent banner is printed at the very end of the page) do not count: they would pull the whole page in.
  const fixedSet = new Set();
  for (const el of document.body.querySelectorAll('*')) { if (getComputedStyle(el).position === 'fixed') fixedSet.add(el); }
  const underFixed = el => { for (let a = el; a && a !== document.body; a = a.parentElement) { if (fixedSet.has(a)) return true; } return false; };
  for (const el of document.body.querySelectorAll('*')) { if (onScreen(el) && !underFixed(el)) lastOnScreen = el; }
  // Anything wider than the screen (an off-canvas panel, a carousel track) and everything that clips or contains it: without their
  // rules the page would be wider than the screen until the full stylesheet arrives, then jump back.
  const W = window.innerWidth;
  const wide = new Set();
  for (const el of document.body.querySelectorAll('*')) {
    const r = el.getBoundingClientRect();
    if (r.width > 0 && (r.right > W + 1 || r.left < -1)) { for (let a = el; a && a !== document.body; a = a.parentElement) wide.add(a); }
  }
  const count = el => {
    if (el === document.documentElement || el === document.body) return true;
    if (wide.has(el)) return true;
    if (rendered(el) && onScreen(el)) return true;
    if (!lastOnScreen) return false;
    const pos = el.compareDocumentPosition(lastOnScreen);
    return !!(pos & (Node.DOCUMENT_POSITION_FOLLOWING | Node.DOCUMENT_POSITION_CONTAINED_BY));
  };
  const strip = sel => sel
    .replace(/::?(?:before|after|first-line|first-letter|placeholder|selection|marker|backdrop|file-selector-button|-[a-z-]+)(\([^)]*\))?/gi, '')
    .replace(/:(?:hover|focus|focus-within|focus-visible|active|visited|link|target|any-link|autofill|-webkit-autofill|user-invalid|user-valid)\b/gi, '');
  const splitTop = sel => {
    const out = []; let depth = 0, cur = '';
    for (const ch of sel) {
      if (ch === '(' || ch === '[') depth++; else if (ch === ')' || ch === ']') depth--;
      if (ch === ',' && depth === 0) { out.push(cur); cur = ''; } else cur += ch;
    }
    out.push(cur);
    return out.map(s => s.trim()).filter(Boolean);
  };
  const mediaOk = chain => chain.every(c => {
    if (c.name === 'media') return /\bprint\b/i.test(c.params) && !/\bscreen\b/i.test(c.params) ? false : window.matchMedia(c.params).matches;
    if (c.name === 'supports') return CSS.supports(c.params);
    return true;
  });
  const cache = new Map();
  const hit = [];
  for (const r of rules) {
    if (!mediaOk(r.chain)) continue;
    let used = false;
    for (let s of splitTop(r.selector)) {
      s = strip(s).trim();
      if (!s || /[>+~]$/.test(s)) s = (s || '*');
      let els;
      try { els = cache.get(s) || document.querySelectorAll(s); cache.set(s, els); } catch (e) { used = true; break; }   // a selector the browser cannot parse: keep the rule
      for (const el of els) { if (count(el)) { used = true; break; } }
      if (used) break;
    }
    if (used) hit.push(r.id);
  }
  return hit;
}

async function fetchText(url) { const res = await fetch(url); if (!res.ok) throw new Error(url + ' ' + res.status); return res.text(); }

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch({ executablePath: EXE, args: ['--no-sandbox'] });
  for (const group of only) {
    const paths = urlMap[group];
    if (!paths || !paths.length) { console.log(group + ': no urls'); continue; }
    const keep = new Set();
    let sheets = null;   // [{href, root, rules}] in load order, found on the first page
    for (const p of paths) {
      for (const [w, h] of VIEWPORTS) {
        const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 1 });
        const page = await ctx.newPage();
        await page.route(/(googletagmanager|google-analytics|doubleclick|googleapis|gstatic|cdn-beta\.seatoutlet\.com)/, r => r.fulfill({ status: 200, body: '' }));
        let resp;
        try { resp = await page.goto(base + p, { waitUntil: 'networkidle', timeout: 60000 }); } catch (e) { console.log('  ' + p + ' ' + w + ': ' + e.message.split('\n')[0]); await ctx.close(); continue; }
        if (!resp || resp.status() !== 200) { console.log('  skip ' + p + ' (HTTP ' + (resp && resp.status()) + ')'); await ctx.close(); break; }
        await page.waitForTimeout(1500);
        if (!sheets) {
          const hrefs = await page.evaluate(() => [...document.querySelectorAll('link[rel="stylesheet"]')].map(l => l.href).filter(h => /\/css\/(bootstrap|style)[^/]*\.css/.test(h)));
          sheets = []; let off = 0;
          for (const href of hrefs) { const css = await fetchText(href); const s = listRules(css, off); off = s.next; sheets.push({ href, ...s }); }
        }
        const all = sheets.flatMap(s => s.rules);
        const hit = await page.evaluate(matchOnScreen, all);
        hit.forEach(i => keep.add(i));
        await ctx.close();
      }
    }
    if (!sheets) { console.log(group + ': no page rendered'); continue; }
    // rebuild: the kept rules, in order, inside their @media / @supports blocks
    let out = '';
    for (const s of sheets) {
      s.root.walkRules(rule => {
        if (rule.parent && rule.parent.type === 'atrule' && /keyframes$/i.test(rule.parent.name)) return;
        if (!keep.has(rule.raws.soId)) rule.remove();
      });
      s.root.walkAtRules(at => { if (/^(media|supports)$/i.test(at.name) && (!at.nodes || !at.nodes.length)) at.remove(); });
      // keyframes: keep the ones a kept rule uses; font faces stay (they are tiny and a font that loads late shifts text)
      const used = s.root.toString();
      s.root.walkAtRules(/keyframes$/i, at => { if (!new RegExp('animation[^;{}]*\\b' + at.params.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b').test(used.replace(at.toString(), ''))) at.remove(); });
      out += s.root.toString() + '\n';
    }
    const min = new CleanCSS({ level: 1 }).minify(out).styles;
    fs.writeFileSync(path.join(outDir, group + '.css'), min);
    console.log(group + ': ' + paths.length + ' pages, ' + keep.size + ' rules kept, ' + min.length + ' bytes');
  }
  await browser.close();
})();
