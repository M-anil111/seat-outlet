/* Menus: a "near you" group in the Concerts, Sports, Theater and Festivals panels (desktop mega menu and phone menu).
   Uses the location the site already has (cookies set by the address lookup or the visitor's own choice). It never opens a
   permission prompt, and without a location it adds nothing. Events come from the same nearest-first feed as the listing
   pages and are fetched the first time a panel is opened, then kept for 10 minutes. */
(function () {
  var CATS = { concerts: 'concerts', sports: 'sports', theater: 'theatre', festivals: 'festival' };
  var NOUN = { concerts: 'concerts', sports: 'games', theater: 'shows', festivals: 'festivals' };
  var TTL = 600000, loading = {};

  function cookie(n) { return typeof getCookie === 'function' ? getCookie(n) : ''; }
  function where() {
    var lat = cookie('so_lat'), lng = cookie('so_lng');
    if (!lat || !lng || isNaN(parseFloat(lat)) || isNaN(parseFloat(lng))) return null;
    return { lat: lat, lng: lng, label: cookie('so_label') || '' };
  }
  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
  var slug = function (v) { return String(v).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); };

  function read(k) { try { var o = JSON.parse(sessionStorage.getItem(k) || 'null'); return o && Date.now() - o.t < TTL ? o.d : null; } catch (e) { return null; } }
  function write(k, d) { try { sessionStorage.setItem(k, JSON.stringify({ t: Date.now(), d: d })); } catch (e) {} }

  function html(key, data, w, phone) {
    var evs = (data.events || []).slice(0, 4);
    if (!evs.length) return '';
    var far = data.scope === 'nearest';
    var place = w.label || 'you';
    var head = (far ? 'Closest ' : '') + NOUN[key].charAt(0).toUpperCase() + NOUN[key].slice(1) + (far ? ' to ' : ' near ') + place;
    var items = evs.map(function (e) {
      var sub = [e.date && e.date.split(' - ')[0], e.loc, e.dist != null ? (e.dist < 3 ? 'nearby' : e.dist + ' mi') : ''].filter(Boolean).join(' · ');
      return '<li><a class="so-near-item" href="/event/' + slug(e.name) + '-' + e.id + '"><strong>' + esc(e.name) + '</strong><span>' + esc(sub) + '</span></a></li>';
    }).join('');
    return '<p class="' + (phone ? 'so-menu__title' : 'so-mega__title') + '">' + esc(head) + '</p><ul>' + items + '</ul>';
  }

  function paint(key, data, w) {
    var mega = document.querySelector('#so-mega-' + key + ' .so-mega__inner');
    var panel = document.querySelector('.so-menu__panel[data-panel="' + key + '"]');
    [mega, panel].forEach(function (host) {
      if (!host) return;
      var old = host.querySelector('[data-so-menu-near]');
      if (old) old.remove();
      var inner = html(key, data, w, host === panel);
      if (!inner) return;
      var box = document.createElement('div');
      box.setAttribute('data-so-menu-near', '');
      box.className = host === mega ? 'so-mega__col so-mega__near' : 'so-menu__near';
      box.innerHTML = inner;
      if (host === mega) host.appendChild(box);
      else { var all = host.querySelector('.so-menu__all'); all ? all.insertAdjacentElement('afterend', box) : host.prepend(box); }
    });
  }

  function ensure(key) {
    var w = where();
    if (!w || !CATS[key]) return;
    var sig = key + '|' + w.lat + '|' + w.lng;
    if (loading[sig] === 'done' || loading[sig] === 'busy') return;
    var cached = read('so_mn_' + sig);
    if (cached) { loading[sig] = 'done'; return paint(key, cached, w); }
    loading[sig] = 'busy';
    fetch('/ajax/get-home-feed.php?kind=near&cat=' + encodeURIComponent(CATS[key]) + '&page=1&lat=' + encodeURIComponent(w.lat) + '&lng=' + encodeURIComponent(w.lng))
      .then(function (r) { return r.json(); })
      .then(function (d) { loading[sig] = 'done'; if (d && d.events && d.events.length) { write('so_mn_' + sig, d); paint(key, d, w); } })
      .catch(function () { delete loading[sig]; });
  }

  // Desktop: when the pointer or keyboard reaches a top-level item. Phone: when a tab is chosen or the menu opens.
  document.querySelectorAll('.so-mega-top').forEach(function (a) {
    var key = a.getAttribute('data-so-mega'), item = a.closest('.so-mega-item');
    ['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) { item.addEventListener(ev, function () { ensure(key); }, { passive: true }); });
  });
  document.querySelectorAll('.so-menu__tab[data-tab]').forEach(function (t) { t.addEventListener('click', function () { ensure(t.getAttribute('data-tab')); }); });
  var drawer = document.getElementById('mobileMenu');
  if (drawer) drawer.addEventListener('show.bs.offcanvas', function () { var t = drawer.querySelector('.so-menu__tab.is-active'); if (t) ensure(t.getAttribute('data-tab')); });

  // A new location (chosen on a page) makes the old groups stale.
  document.addEventListener('so:location', function () {
    loading = {};
    document.querySelectorAll('[data-so-menu-near]').forEach(function (n) { n.remove(); });
  });
})();
