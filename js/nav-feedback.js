/* Instant feedback when an internal link is tapped.
 *
 * A page that needs the ticket feed can take a second or two to answer, and until the browser receives it nothing on screen
 * changes, so it feels broken and people tap again. This shows a thin progress bar at the top and marks the tapped link as busy
 * the moment it is tapped. It changes nothing about where the link goes and never blocks it. */
(function () {
  'use strict';
  var root = document.documentElement;
  var bar = null;

  function ensureBar() {
    if (bar) return bar;
    bar = document.createElement('div');
    bar.className = 'so-navbar';
    bar.setAttribute('role', 'progressbar');
    bar.setAttribute('aria-label', 'Loading page');
    document.body.appendChild(bar);
    return bar;
  }

  function reset() {
    root.classList.remove('so-navigating');
    document.querySelectorAll('.so-link-busy').forEach(function (a) { a.classList.remove('so-link-busy'); a.removeAttribute('aria-busy'); });
    if (bar) bar.classList.remove('is-on');
  }

  function start(link) {
    ensureBar();
    root.classList.add('so-navigating');
    // Next frame, so the bar starts from zero and animates instead of appearing already full.
    requestAnimationFrame(function () { bar.classList.add('is-on'); });
    if (link) { link.classList.add('so-link-busy'); link.setAttribute('aria-busy', 'true'); }
    // If the page never replaced this one (download, blocked, offline), do not leave the screen looking busy.
    setTimeout(reset, 15000);
  }

  function isPlainInternalClick(e, a) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return false;
    if (a.target && a.target !== '_self') return false;
    if (a.hasAttribute('download') || a.hasAttribute('data-bs-toggle') || a.hasAttribute('data-no-feedback')) return false;
    var url;
    try { url = new URL(a.href, location.href); } catch (err) { return false; }
    if (url.origin !== location.origin) return false;
    if (url.pathname === location.pathname && url.search === location.search) return false;   // same page, in-page jump
    return true;
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href]');
    if (a && isPlainInternalClick(e, a)) start(a);
  });

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (e.defaultPrevented || !f || f.target === '_blank' || (f.method || 'get').toLowerCase() !== 'get') return;
    start(null);
  });

  // Back/forward restores a finished page from memory: clear any leftover busy state.
  window.addEventListener('pageshow', function (e) { if (e.persisted) reset(); });
})();
