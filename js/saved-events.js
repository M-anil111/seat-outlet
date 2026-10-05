/* Event page: "Save this event" heart and a small saved-events drawer. No account: the list lives in this browser (localStorage)
   and is only ever shown back to the same visitor. If storage is blocked the heart simply does not appear. */
(function () {
  'use strict';
  var KEY = 'so_saved_events';
  var MAX = 40;
  var node = document.getElementById('so-event-data');
  var heart = document.querySelector('[data-so-save]');
  var openBtn = document.querySelector('[data-so-saved-open]');
  if (!node || !heart) return;
  var ev;
  try { ev = JSON.parse(node.textContent); } catch (e) { return; }
  if (!ev || !ev.id) return;

  function read() {
    try {
      var a = JSON.parse(localStorage.getItem(KEY) || '[]');
      return Array.isArray(a) ? a.filter(function (x) { return x && x.id && x.slug; }) : [];
    } catch (e) { return null; }
  }
  function write(list) {
    try { localStorage.setItem(KEY, JSON.stringify(list.slice(0, MAX))); return true; } catch (e) { return false; }
  }
  var probe = read();
  if (probe === null) { heart.hidden = true; return; }

  function today() { var d = new Date(); d.setHours(0, 0, 0, 0); return d; }
  function upcoming(list) {
    var t = today().getTime() - 86400000;
    return list.filter(function (x) {
      if (!/^\d{4}-\d{2}-\d{2}/.test(x.date || '')) return true;
      var p = x.date.slice(0, 10).split('-').map(Number);
      return new Date(p[0], p[1] - 1, p[2]).getTime() >= t;
    });
  }
  function say(msg) {
    var s = document.getElementById('so-action-status');
    if (s) { s.textContent = ''; setTimeout(function () { s.textContent = msg; }, 30); }
  }
  function track(name) {
    var w = window.dataLayer = window.dataLayer || [];
    w.push({ ecommerce: null });
    w.push({ event: name, event_id: String(ev.id), ecommerce: { currency: ev.currency || 'USD', items: [{ item_id: String(ev.id), item_name: ev.name, affiliation: 'Seat Outlet' }] } });
  }
  function isSaved(list) { return list.some(function (x) { return String(x.id) === String(ev.id); }); }

  function paint() {
    var list = upcoming(read() || []);
    var saved = isSaved(list);
    heart.setAttribute('aria-pressed', saved ? 'true' : 'false');
    var icon = heart.querySelector('i'), label = heart.querySelector('span');
    if (icon) icon.className = saved ? 'bi bi-heart-fill' : 'bi bi-heart';
    if (label) label.textContent = saved ? 'Saved' : 'Save';
    heart.classList.toggle('is-saved', saved);
    if (openBtn) {
      var c = openBtn.querySelector('[data-so-saved-count]');
      if (c) c.textContent = String(list.length);
      openBtn.hidden = list.length === 0;
    }
    return list;
  }

  heart.addEventListener('click', function () {
    var list = upcoming(read() || []);
    if (isSaved(list)) {
      list = list.filter(function (x) { return String(x.id) !== String(ev.id); });
      write(list);
      say('Removed from saved events.');
    } else {
      list.unshift({ id: ev.id, name: ev.name, slug: ev.slug, date: ev.date || '', city: ev.city || '', venue: ev.venue || '', price: ev.price || '', savedAt: Date.now() });
      if (write(list)) { track('add_to_wishlist'); say('Event saved on this device.'); }
      else say('Saving is turned off in this browser.');
    }
    paint();
    if (drawer && !drawer.hidden) render();
  });

  /* ---- Drawer ---- */
  var drawer = null, lastFocus = null;
  function esc(t) { var d = document.createElement('div'); d.textContent = String(t == null ? '' : t); return d.innerHTML; }
  function fmtDate(iso) {
    if (!/^\d{4}-\d{2}-\d{2}/.test(iso || '')) return '';
    var p = iso.slice(0, 10).split('-').map(Number);
    return new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
  }
  function render() {
    var list = upcoming(read() || []).sort(function (a, b) { return String(a.date).localeCompare(String(b.date)); });
    var ul = drawer.querySelector('.so-saved__list');
    if (!list.length) { ul.innerHTML = '<li class="so-saved__empty">No saved events yet. Tap the heart on an event to keep it here.</li>'; return; }
    ul.innerHTML = list.map(function (x) {
      var meta = [fmtDate(x.date), x.city].filter(Boolean).join(' \u00b7 ');
      return '<li class="so-saved__item"><a class="so-saved__link" href="/event/' + encodeURIComponent(x.slug) + '"><strong>' + esc(x.name) + '</strong>' +
        '<small>' + esc(meta) + '</small>' + (x.price ? '<small>Listed from ' + esc(x.price) + ' per ticket when saved</small>' : '') + '</a>' +
        '<button type="button" class="so-saved__rm" data-id="' + esc(x.id) + '" aria-label="Remove ' + esc(x.name) + ' from saved events"><i class="bi bi-x-lg" aria-hidden="true"></i></button></li>';
    }).join('');
  }
  function focusables() { return Array.prototype.slice.call(drawer.querySelectorAll('a[href], button:not([disabled])')); }
  function onKey(e) {
    if (e.key === 'Escape') { closeDrawer(); return; }
    if (e.key !== 'Tab') return;
    var f = focusables();
    if (!f.length) return;
    var first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }
  function closeDrawer() {
    if (!drawer) return;
    drawer.hidden = true;
    document.removeEventListener('keydown', onKey);
    if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) { /* element gone */ } }
  }
  function openDrawer() {
    if (!drawer) {
      drawer = document.createElement('div');
      drawer.className = 'so-saved';
      drawer.hidden = true;
      drawer.innerHTML = '<div class="so-saved__scrim" data-close></div>' +
        '<aside class="so-saved__panel" role="dialog" aria-modal="true" aria-labelledby="soSavedTitle">' +
        '<div class="so-saved__head"><h2 id="soSavedTitle">Saved events</h2><button type="button" class="so-saved__x" data-close aria-label="Close saved events"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>' +
        '<p class="so-saved__note">Kept on this device only. Prices are not updated here: open an event to see what is listed now.</p>' +
        '<ul class="so-saved__list"></ul></aside>';
      document.body.appendChild(drawer);
      drawer.addEventListener('click', function (e) {
        if (e.target.closest('[data-close]')) { closeDrawer(); return; }
        var rm = e.target.closest('.so-saved__rm');
        if (rm) {
          var id = rm.getAttribute('data-id');
          write((read() || []).filter(function (x) { return String(x.id) !== String(id); }));
          paint(); render();
          var f = focusables(); if (f.length) f[0].focus();
        }
      });
    }
    lastFocus = document.activeElement;
    render();
    drawer.hidden = false;
    if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    document.addEventListener('keydown', onKey);
    var x = drawer.querySelector('.so-saved__x'); if (x) x.focus();
  }
  if (openBtn) openBtn.addEventListener('click', openDrawer);

  paint();
})();
