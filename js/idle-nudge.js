/* A single, polite "Still deciding?" card for visitors who stop interacting on an event or artist page.
   - once per browser session, never on checkout pages, never over another dialog;
   - only facts the page already knows (name, city, and the listed-ticket count when it is small);
   - dismissible with Escape, the close button, "Not now" or a tap outside. */
(function () {
  var path = location.pathname;
  var isEvent = /^\/event\//.test(path), isArtist = /^\/artist\//.test(path);
  if (!isEvent && !isArtist) return;
  var IDLE_MS = 35000;
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

  function daysTo(iso) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(iso || '')) return null;
    var p = iso.split('-').map(Number), d = new Date(p[0], p[1] - 1, p[2]), n = new Date(); n.setHours(0, 0, 0, 0);
    return Math.round((d - n) / 86400000);
  }

  /* Copy comes only from facts the page has: how many tickets are listed, whether the event is a top seller, how soon it is. */
  function content() {
    if (isEvent) {
      var data = {};
      try { data = JSON.parse(document.getElementById('so-event-data').textContent); } catch (e) {}
      var name = data.name || 'this event';
      var n = parseInt(data.tickets, 10), rank = parseInt(data.rank, 10), days = daysTo(data.date);
      var kicker = 'Still thinking?', head = 'Good seats do not wait', body = 'Prices move with demand. Take another look at ' + name + (data.city ? ' in ' + data.city : '') + '.', tag = '';
      if (n > 0 && n <= 30) { kicker = 'Heads up'; head = 'Only ' + n + ' ticket' + (n === 1 ? '' : 's') + ' listed'; body = 'That is all we have for ' + name + ' right now. Lock your seats before they are gone.'; tag = 'Low stock'; }
      else if (rank >= 1 && rank <= 3) { kicker = 'Everyone is looking'; head = 'This one is moving'; body = name + ' is one of our top sellers right now. Check the seat map while there is still a choice.'; tag = 'Top seller'; }
      else if (days !== null && days >= 0 && days <= 7) { kicker = 'It is almost here'; head = days === 0 ? 'Showtime is today' : (days === 1 ? 'Showtime is tomorrow' : 'Only ' + days + ' days to go'); body = 'Tickets are delivered before the event. Grab yours for ' + name + '.'; tag = 'Happening soon'; }
      if (data.price) { body += ' Tickets from ' + data.price + '.'; }
      return { kicker: kicker, head: head, text: body, tag: tag, cta: 'Show me seats', target: '#tn-maps' };
    }
    var h1 = document.querySelector('h1, h2.h1');
    var who = h1 ? h1.textContent.trim().replace(/\s+tickets?$/i, '') : 'this artist';
    return { kicker: 'Still scrolling?', head: 'Catch ' + who + ' live', text: 'Compare seats and prices for every ' + who + ' date. Every order is backed by our 100% guarantee.', tag: '', cta: 'See dates', target: '#eventsSection, .performer-event-item' };
  }

  /* Everything else on the page is made inert while the dialog is open, so screen readers and the keyboard stay inside it. */
  function setInert(on) {
    Array.prototype.forEach.call(document.body.children, function (el) {
      if (el.id === 'soNudge' || el.tagName === 'SCRIPT') return;
      if (on) { if (!el.hasAttribute('inert')) { el.setAttribute('inert', ''); el.setAttribute('data-nudge-inert', '1'); } }
      else if (el.getAttribute('data-nudge-inert')) { el.removeAttribute('inert'); el.removeAttribute('data-nudge-inert'); }
    });
  }
  function close() {
    var box = document.getElementById('soNudge');
    setInert(false);
    if (box) box.remove();
    document.removeEventListener('keydown', onKey);
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }
  function onKey(e) {
    if (e.key === 'Escape') { close(); return; }
    if (e.key !== 'Tab') return;
    // Keep Tab inside the card while it is open (it is a modal dialog).
    var box = document.getElementById('soNudge');
    if (!box) return;
    var f = Array.prototype.slice.call(box.querySelectorAll('button:not([disabled])'));
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (!box.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
    else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }

  /* Event pages: no nudge when the page has no tickets to point at, or while the visitor is looking at the seat map (that is the decision moment). */
  function blocked() {
    if (!isEvent) return false;
    if (document.documentElement.classList.contains('so-empty')) return true;
    var m = document.getElementById('tn-maps');
    if (!m || !m.offsetHeight) return false;
    var r = m.getBoundingClientRect(), vh = window.innerHeight || document.documentElement.clientHeight;
    var visible = Math.min(r.bottom, vh) - Math.max(r.top, 0);
    return visible > vh * 0.4;
  }
  function show() {
    if (isEvent && document.documentElement.classList.contains('so-empty')) return;
    if (shown || anotherDialogOpen() || document.hidden || blocked()) { schedule(); return; }
    shown = true;
    try { sessionStorage.setItem(KEY, '1'); } catch (e) {}
    var c = content();
    lastFocus = document.activeElement;
    var box = document.createElement('div');
    box.id = 'soNudge';
    box.className = 'so-nudge';
    box.innerHTML = '<div class="so-nudge__card" role="dialog" aria-modal="true" aria-labelledby="soNudgeTitle">' +
      '<button type="button" class="so-nudge__x" aria-label="Close">&times;</button>' +
      '<p class="so-nudge__kicker"></p>' +
      '<h2 id="soNudgeTitle" class="so-nudge__title"></h2>' +
      '<span class="so-nudge__tag" hidden></span>' +
      '<p class="so-nudge__text"></p>' +
      '<button type="button" class="so-nudge__cta"></button>' +
      '<button type="button" class="so-nudge__later">Maybe later</button></div>';
    box.querySelector('.so-nudge__kicker').textContent = c.kicker;
    box.querySelector('#soNudgeTitle').textContent = c.head;
    var tagEl = box.querySelector('.so-nudge__tag'); if (c.tag) { tagEl.textContent = c.tag; tagEl.hidden = false; }
    box.querySelector('.so-nudge__text').textContent = c.text;
    box.querySelector('.so-nudge__cta').textContent = c.cta;
    document.body.appendChild(box);
    setInert(true);
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
  // Desktop: the pointer leaves through the top of the window (heading for the tabs or the address bar).
  document.addEventListener('mouseout', function (e) {
    if (!e.relatedTarget && e.clientY <= 0 && !shown && Date.now() - startedAt > 8000) show();
  });
  var startedAt = Date.now();
  schedule();
})();
