/* A single, polite "Still deciding?" card for visitors who stop interacting on an event or artist page.
   - once per browser session, never on checkout pages, never over another dialog;
   - only facts the page already knows (name, city, and the listed-ticket count when it is small);
   - dismissible with Escape, the close button, "Not now" or a tap outside. */
(function () {
  var path = location.pathname;
  var isEvent = /^\/event\//.test(path), isArtist = /^\/artist\//.test(path);
  if (!isEvent && !isArtist) return;
  var IDLE_MS = 45000;
  var KEY = 'so_nudge_shown';
  try { if (sessionStorage.getItem(KEY)) return; } catch (e) {}

  var timer = null, shown = false, lastFocus = null;

  function push(name) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: name, page_type: isEvent ? 'event' : 'artist' });
  }

  function anotherDialogOpen() {
    if (document.querySelector('.modal.show, .offcanvas.show')) return true;
    var q = document.getElementById('sea-quantity-modal');
    return !!(q && q.offsetParent !== null);
  }

  function content() {
    if (isEvent) {
      var data = {};
      try { data = JSON.parse(document.getElementById('so-event-data').textContent); } catch (e) {}
      var name = data.name || 'this event';
      var where = data.city ? ' in ' + data.city : '';
      var n = parseInt(data.tickets, 10);
      var line = 'Tickets for ' + name + where + ' are on sale.' + (n > 0 && n <= 20 ? ' ' + n + ' tickets are listed right now.' : '') + ' Prices and availability can change.';
      return { text: line, cta: 'Back to tickets', target: '#tn-maps' };
    }
    var h1 = document.querySelector('h1, h2.h1');
    var who = h1 ? h1.textContent.trim().replace(/\s+tickets?$/i, '') : 'this artist';
    return { text: 'Compare seats and prices for ' + who + ' dates. Every order is backed by our 100% guarantee.', cta: 'See dates', target: '#eventsSection, .performer-event-item' };
  }

  function close() {
    var box = document.getElementById('soNudge');
    if (box) box.remove();
    document.removeEventListener('keydown', onKey);
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }
  function onKey(e) { if (e.key === 'Escape') close(); }

  function show() {
    if (shown || anotherDialogOpen() || document.hidden) { schedule(); return; }
    shown = true;
    try { sessionStorage.setItem(KEY, '1'); } catch (e) {}
    var c = content();
    lastFocus = document.activeElement;
    var box = document.createElement('div');
    box.id = 'soNudge';
    box.className = 'so-nudge';
    box.innerHTML = '<div class="so-nudge__card" role="dialog" aria-modal="true" aria-labelledby="soNudgeTitle">' +
      '<button type="button" class="so-nudge__x" aria-label="Close">&times;</button>' +
      '<h2 id="soNudgeTitle" class="so-nudge__title">Still deciding?</h2>' +
      '<p class="so-nudge__text"></p>' +
      '<button type="button" class="so-nudge__cta"></button>' +
      '<button type="button" class="so-nudge__later">Not now</button></div>';
    box.querySelector('.so-nudge__text').textContent = c.text;
    box.querySelector('.so-nudge__cta').textContent = c.cta;
    document.body.appendChild(box);
    push('idle_nudge_shown');
    box.addEventListener('click', function (e) { if (e.target === box) close(); });
    box.querySelector('.so-nudge__x').addEventListener('click', close);
    box.querySelector('.so-nudge__later').addEventListener('click', close);
    box.querySelector('.so-nudge__cta').addEventListener('click', function () {
      push('idle_nudge_click');
      close();
      var t = document.querySelector(c.target);
      if (t && t.scrollIntoView) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    document.addEventListener('keydown', onKey);
    box.querySelector('.so-nudge__cta').focus();
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(show, IDLE_MS);
  }
  var last = 0;
  function activity() {
    var now = Date.now();
    if (now - last < 1000) return;
    last = now;
    if (!shown) schedule();
  }
  ['pointerdown', 'pointermove', 'keydown', 'scroll', 'touchstart', 'wheel'].forEach(function (ev) {
    window.addEventListener(ev, activity, { passive: true });
  });
  schedule();
})();
