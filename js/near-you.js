/* Listing hubs: "Explore ... near you". Location comes from the same cookies the rest of the site uses (set by the
   address lookup in main.js, or chosen here); the browser's permission prompt only ever opens from the
   "Use my current location" button. Everything is optional: with no location the section simply stays hidden and the full
   national list below is shown. */
(function () {
  var root = document.querySelector('[data-so-explore]');
  if (!root) return;

  var cat = root.getAttribute('data-cat') || 'all';
  var noun = root.getAttribute('data-noun') || 'events';
  var catId = root.getAttribute('data-catid') || '0';
  var $ = function (sel) { return root.querySelector(sel); };
  var locBtn = $('[data-so-loc]'), locLabel = $('[data-so-loc-label]'), locPop = $('[data-so-loc-pop]');
  var locInput = $('#soNearInput'), locHere = $('[data-so-loc-here]');
  var near = $('[data-so-near]'), grid = $('[data-so-near-grid]'), more = $('[data-so-near-more]'), title = $('[data-so-near-title]');
  // One filter state for the whole page. It comes from the address (the server printed it into the data attributes), so a
  // filtered view can be copied and shared; changing a chip updates the address and refreshes both the grid and the list.
  var DEFAULTS = { when: '', sort: 'distance', radius: '0', max: '0' };
  var state = { lat: '', lng: '', label: '', when: root.getAttribute('data-when') || '', sort: root.getAttribute('data-sort') || 'distance', radius: root.getAttribute('data-radius') || '0', max: root.getAttribute('data-max') || '0', page: 1, token: 0, nw: false, scope: '' };

  var esc = function (v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
  var slug = function (v) { return String(v).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); };
  var readCookie = function (n) { return typeof getCookie === 'function' ? getCookie(n) : ''; };

  function whenBadge(iso) {
    if (!iso) return '';
    var p = iso.split('-').map(Number);
    var d = new Date(p[0], p[1] - 1, p[2]);
    var now = new Date(); now.setHours(0, 0, 0, 0);
    var days = Math.round((d - now) / 86400000);
    if (days <= 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    return '';
  }

  // The same row as the national list below ("All concerts in the USA"): date tile, day and time, venue, place, name, price, button.
  function card(ev, i) {
    // "Popular" is only claimed when the grid really is sorted by sales (or is the nationwide list), never for "nearest first".
    var badge = whenBadge(ev.iso) || (ev.top ? (state.scope === 'nationwide' ? 'Popular nationwide' : 'Popular near you') : '');
    var d = null;
    if (/^\d{4}-\d{2}-\d{2}/.test(ev.iso || '')) { var p = ev.iso.slice(0, 10).split('-').map(Number); d = new Date(p[0], p[1] - 1, p[2]); }
    var mon = d ? d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase() : '';
    var day = d ? ('0' + d.getDate()).slice(-2) : '';
    var wd = d ? d.toLocaleDateString('en-US', { weekday: 'short' }) : '';
    var yr = d && d.getFullYear() > new Date().getFullYear() ? '<div class="month">' + d.getFullYear() + '</div>' : '';
    var href = '/event/' + (ev.slug || (slug(ev.name) + '-' + ev.id));
    var away = ev.dist != null ? (ev.dist < 3 ? 'nearby' : ev.dist + ' mi away') : '';
    var time = String(ev.time || '').trim();
    var hasPrice = !!ev.price;
    return '<div class="d-flex align-items-center justify-content-between performer-event-item">' +
      '<div class="date-box text-center me-3"><div class="month">' + esc(mon) + '</div><div class="day">' + esc(day) + '</div>' + yr + '</div>' +
      '<div class="flex-grow-1 w-50">' +
        '<div class="d-flex align-items-center gap-2 flex-wrap"><span class="fw-semibold day-weeks">' + esc(wd) + '</span>' + (time ? '<span class="dot">&middot;</span><span class="time-clock">' + esc(time) + '</span>' : '') +
          (badge ? '<span class="so-row__badge' + (/^Popular/.test(badge) ? ' so-row__badge--hot' : '') + '">' + esc(badge) + '</span>' : '') + '</div>' +
        (ev.venue ? '<div class="ev-venue"><span>' + esc(ev.venue) + '</span></div>' : '') +
        (ev.loc ? '<div class="ev-place"><span>' + esc(ev.loc) + '</span>' + (away ? '<span class="so-row__away"> &middot; ' + esc(away) + '</span>' : '') + '</div>' : '') +
        '<div class="ev-name"><a href="' + href + '">' + esc(ev.name) + '<span class="visually-hidden"> tickets</span></a></div>' +
      '</div>' +
      '<div class="ms-3">' + (hasPrice ? '<div class="event-price-tag">From <strong>' + esc(ev.price) + '</strong></div>' : '<div class="event-price-tag event-price-tag--none">No tickets listed yet</div>') +
        '<a href="' + href + '" class="btn ' + (hasPrice ? 'btn-primary' : 'btn-outline-primary') + ' d-flex align-items-center gap-2" aria-label="' + (hasPrice ? 'Buy tickets for ' : 'View ') + esc(ev.name) + '"><span>' + (hasPrice ? 'Buy Tickets' : 'View Event') + '</span><i class="bi bi-chevron-right"></i></a></div>' +
    '</div>';
  }

  function skeleton(n) {
    var h = '';
    for (var i = 0; i < n; i++) h += '<div class="so-row-skel" aria-hidden="true"><span class="so-evc__sk so-row-skel__date"></span><span class="so-row-skel__txt"><span class="so-evc__sk so-evc__sk--title"></span><span class="so-evc__sk so-evc__sk--line"></span></span></div>';
    return h;
  }

  var notice = null;
  var WHEN_TEXT = { today: 'today', weekend: 'this weekend', week: 'in the next 7 days', month: 'in the next 30 days' };
  function ensureNotice() {
    if (!notice) {
      notice = document.createElement('p');
      notice.className = 'so-near__notice';
      title.insertAdjacentElement('afterend', notice);
    }
    return notice;
  }
  function setTitle(data) {
    var place = state.label || 'you';
    var when = state.when && WHEN_TEXT[state.when] ? ' ' + WHEN_TEXT[state.when] : '';
    var cap = noun.charAt(0).toUpperCase() + noun.slice(1);
    var scope = data && data.scope;
    var far = scope === 'nearest';
    ensureNotice();
    if (scope === 'nationwide') {
      // The nearest event is more than 250 miles away: say what this is instead of calling it "near you".
      title.textContent = 'Popular ' + noun + ' nationwide' + when;
      notice.hidden = false;
      if (!state.lat) { notice.textContent = 'Choose a location to see ' + noun + ' near you. These are the most popular across the country' + (state.when ? ' for those dates' : '') + '.'; return; }
      notice.textContent = 'No ' + noun + ' within 250 miles of ' + place + (state.when ? ' for those dates' : '') + '. These are the most popular across the country' + (data.closest ? ' (the nearest is about ' + data.closest + ' miles away).' : '.');
      return;
    }
    title.textContent = far ? (state.sort === 'distance' ? 'Closest ' + noun + ' to ' + place : cap + ' beyond 50 miles of ' + place) + when : 'Explore ' + noun + ' near ' + place + when;
    // Honest about distance: say so when nothing is close, instead of quietly showing events from another region.
    notice.hidden = !far;
    notice.textContent = far ? 'No ' + noun + ' within ' + (data.radius || 50) + ' miles of ' + place + '. ' + (state.sort === 'distance' ? 'These are the closest, nearest first' : 'These are farther away') + (data.closest ? ' (the nearest is about ' + data.closest + ' miles away).' : '.') : '';
  }

  // A distance limit with nothing inside it: say so and offer the nearest events anywhere.
  function showEmpty(data) {
    near.hidden = false; more.hidden = true;
    title.textContent = 'Explore ' + noun + ' near ' + (state.label || 'you');
    if (notice) notice.hidden = true;
    grid.innerHTML = '<div class="so-near__empty"><p>No ' + esc(noun) + ' within ' + esc(data.limited) + ' miles of ' + esc(state.label || 'you') + (state.when ? ' for those dates' : '') + (state.max !== '0' ? ' under $' + esc(state.max) : '') + '.</p>' +
      '<button type="button" class="so-near__any" data-so-any>Show the nearest anywhere</button></div>';
  }

  function showFailure() {
    near.hidden = false; more.hidden = true;
    if (notice) notice.hidden = true;
    grid.innerHTML = '<div class="so-near__empty" role="alert"><p>Could not load events near you right now.</p><button type="button" class="so-near__any" data-so-retry>Try again</button></div>';
  }

  function load(page) {
    // Without a location the section stays hidden, except for the explicit nationwide list (startNationwide).
    if ((!state.lat || !state.lng) && !state.anywhere) { near.hidden = true; return; }
    var my = ++state.token;
    state.page = page;
    if (page === 1) { grid.innerHTML = skeleton(4); near.hidden = false; more.hidden = true; state.nw = false; grid.classList.remove('is-expanded'); }
    more.disabled = true;
    if (page > 1) { more.textContent = 'Loading...'; more.setAttribute('aria-busy', 'true'); }
    var qs = 'kind=near&cat=' + encodeURIComponent(cat) + (catId !== '0' ? '&catid=' + encodeURIComponent(catId) : '') + '&when=' + encodeURIComponent(state.when) + '&sort=' + encodeURIComponent(state.sort) + '&radius=' + encodeURIComponent(state.radius) + '&max=' + encodeURIComponent(state.max) + (state.nw ? '&nw=1' : '') + '&page=' + page +
      '&lat=' + encodeURIComponent(state.lat) + '&lng=' + encodeURIComponent(state.lng);
    fetch('/ajax/get-home-feed.php?' + qs).then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); }).then(function (data) {
      if (my !== state.token) return;
      var events = (data && data.events) || [];
      if (page === 1) {
        if (!events.length) {
          if (data && data.scope === 'empty') { showEmpty(data); return; }
          near.hidden = true; return;
        }
        state.scope = data.scope || '';
        state.nw = state.scope === 'nationwide';
        syncUi();
        setTitle(data);
        grid.innerHTML = '';
      }
      events.forEach(function (e, i) { var lead = e.top === true || (e.top === undefined && i < 3); e.top = (page === 1 && lead) && ((state.sort === 'popular' && state.scope === 'near') || state.scope === 'nationwide'); });
      var start = grid.querySelectorAll('.performer-event-item').length;
      var html = events.map(function (e, i) { return card(e, start + i); }).join('');
      if (page === 1) grid.innerHTML = html; else grid.insertAdjacentHTML('beforeend', html);
      more.hidden = !(data && data.hasMore);
      more.disabled = false;
      more.textContent = 'See more';
      more.removeAttribute('aria-busy');
      // On phones this grid is a sideways carousel, so new cards used to land off-screen and "See more" looked dead. After the
      // first tap the grid opens up into rows and the first new card is brought into view.
      if (page > 1) {
        var fresh = grid.querySelectorAll('.performer-event-item')[start];
        if (fresh && fresh.scrollIntoView) { fresh.scrollIntoView({ behavior: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest', inline: 'nearest' }); }
      }
    }).catch(function () {
      if (my !== state.token) return;
      more.disabled = false;
      more.textContent = 'See more';
      more.removeAttribute('aria-busy');
      if (page === 1) showFailure();
    });
  }

  function setLocation(lat, lng, label, save) {
    state.lat = lat; state.lng = lng; state.label = label || '';
    state.anywhere = false;
    locLabel.textContent = label || 'Near you';
    syncUi();
    if (save) {
      if (typeof setCookie === 'function') { setCookie('so_lat', encodeURIComponent(lat)); setCookie('so_lng', encodeURIComponent(lng)); setCookie('so_label', label || ''); }
    }
    load(1);
  }

  function closePops() {
    [[locBtn, locPop]].forEach(function (pair) { pair[1].hidden = true; pair[0].setAttribute('aria-expanded', 'false'); });
  }
  function toggle(btn, pop) {
    var open = pop.hidden;
    closePops();
    pop.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  locBtn.addEventListener('click', function (e) {
    e.stopPropagation(); toggle(locBtn, locPop);
    if (!locPop.hidden) locInput.focus();
  });
  document.addEventListener('click', function (e) { if (!root.contains(e.target)) closePops(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePops(); });

  more.addEventListener('click', function () { grid.classList.add('is-expanded'); load(state.page + 1); });

  // Typing a place: Google Places, loaded only when the field is first used.
  var placesReady = false;
  locInput.addEventListener('focus', function () {
    if (placesReady || typeof loadGoogleMapsApi !== 'function') return;
    placesReady = true;
    loadGoogleMapsApi().then(function () {
      var ac = new google.maps.places.Autocomplete(locInput, { types: ['(regions)'], componentRestrictions: { country: 'us' } });
      ac.addListener('place_changed', function () {
        var place = ac.getPlace();
        if (!place || !place.geometry) return;
        var city = '', st = '';
        (place.address_components || []).forEach(function (c) {
          if (c.types.indexOf('locality') > -1) city = c.long_name;
          if (c.types.indexOf('administrative_area_level_1') > -1) st = c.short_name;
        });
        var label = city && st ? city + ', ' + st : locInput.value;
        setLocation(place.geometry.location.lat(), place.geometry.location.lng(), label, true);
        locInput.value = '';
        closePops();
      });
    }).catch(function () { placesReady = false; });
  });

  // Date, distance, price and sort chips. One state drives the near-you grid (ajax) and the national list below (the same page,
  // fetched with the same query string), and is written to the address so the view can be shared.
  var dds = root.querySelectorAll('[data-so-dd]');
  var quicks = root.querySelectorAll('[data-so-quick]');
  var listBox = document.querySelector('.list-category-bg');
  var listSeq = 0;

  function syncUi() {
    dds.forEach(function (dd) {
      var key = dd.getAttribute('data-so-dd');
      var cur = String(state[key]);
      // With no location the list is the country's best sellers: show (and highlight) "Best sellers", not "Nearest first".
      if (key === 'sort' && cur === 'distance' && state.nw) cur = 'popular';
      var label = dd.querySelector('[data-so-dd-label]');
      dd.querySelectorAll('[data-val]').forEach(function (o) {
        var on = o.getAttribute('data-val') === cur;
        o.classList.toggle('is-active', on);
        if (on) label.textContent = o.textContent;
      });
      dd.querySelector('summary').classList.toggle('so-chip--on', key !== 'sort' && cur !== '' && cur !== '0');
    });
    quicks.forEach(function (q) {
      var on = state.when === q.getAttribute('data-so-quick');
      q.classList.toggle('so-chip--on', on);
      q.setAttribute('aria-pressed', on ? 'true' : 'false');
      var l = q.querySelector('[data-so-quick-label]');
      var base = q.getAttribute('data-so-quick') === 'today' ? 'Tonight' : 'This weekend';
      if (l) l.textContent = state.lat ? base + ' near me' : base;
    });
  }

  function listQuery() {
    // The national list knows when, price and (best sellers | soonest | lowest price); "nearest first" is the grid's own order.
    var p = [];
    if (state.when) p.push('when=' + encodeURIComponent(state.when));
    if (state.sort !== 'distance') p.push('sort=' + encodeURIComponent(state.sort));
    if (state.max !== '0') p.push('max=' + encodeURIComponent(state.max));
    return p;
  }
  function pushUrl() {
    var p = listQuery();
    if (state.radius !== '0') p.push('radius=' + encodeURIComponent(state.radius));
    var url = location.pathname + (p.length ? '?' + p.join('&') : '');
    try { history.replaceState(null, '', url); } catch (e) {}
  }

  function refreshList() {
    if (!listBox) return;
    var q = listQuery();
    var url = location.pathname + (q.length ? '?' + q.join('&') : '');
    var my = ++listSeq;
    listBox.classList.add('is-loading');
    listBox.setAttribute('aria-busy', 'true');
    fetch(url, { headers: { 'X-Requested-With': 'fetch' } }).then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); }).then(function (html) {
      if (my !== listSeq) return;
      var doc = new DOMParser().parseFromString(html, 'text/html');
      var fresh = doc.querySelector('.list-category-bg');
      if (!fresh) throw new Error('no list');
      listBox.innerHTML = fresh.innerHTML;
      var c1 = document.getElementById('results_count'), c2 = doc.getElementById('results_count');
      if (c1 && c2) c1.textContent = c2.textContent.trim();
      listBox.classList.remove('is-loading');
      listBox.removeAttribute('aria-busy');
      document.dispatchEvent(new CustomEvent('so:list-refreshed'));
    }).catch(function () {
      if (my !== listSeq) return;
      window.location.href = url;   // the plain page renders the same filter on the server
    });
  }

  function change(key, val) {
    if (state[key] === val) return;
    state[key] = val;
    syncUi();
    pushUrl();
    load(1);
    if (key !== 'radius') refreshList();
  }

  dds.forEach(function (dd) {
    var key = dd.getAttribute('data-so-dd');
    dd.addEventListener('toggle', function () { if (dd.open) dds.forEach(function (o) { if (o !== dd) o.open = false; }); });
    dd.querySelectorAll('[data-val]').forEach(function (b) {
      b.addEventListener('click', function () {
        dd.open = false;
        change(key, b.getAttribute('data-val'));
      });
    });
  });
  quicks.forEach(function (q) {
    q.addEventListener('click', function () {
      var v = q.getAttribute('data-so-quick');
      change('when', state.when === v ? '' : v);
    });
  });
  document.addEventListener('click', function (e) { if (!e.target.closest('[data-so-dd]')) dds.forEach(function (d) { d.open = false; }); });
  grid.addEventListener('click', function (e) {
    if (e.target.closest('[data-so-any]')) { change('radius', '0'); return; }
    if (e.target.closest('[data-so-retry]')) load(1);
  });
  syncUi();

  // Only this button opens the browser's location prompt.
  locHere.addEventListener('click', function () {
    if (!navigator.geolocation) return;
    locHere.textContent = 'Locating...';
    navigator.geolocation.getCurrentPosition(function (pos) {
      var lat = pos.coords.latitude, lng = pos.coords.longitude;
      var done = function (label) { locHere.textContent = 'Use my current location'; setLocation(lat, lng, label, true); closePops(); };
      if (typeof loadGoogleMapsApi === 'function' && typeof getCityState === 'function') {
        loadGoogleMapsApi().then(function () { getCityState(lat, lng, done); }).catch(function () { done('Near you'); });
      } else { done('Near you'); }
    }, function () { locHere.textContent = 'Use my current location'; }, { timeout: 8000, maximumAge: 600000 });
  });

  // Location from the cookies if they exist; otherwise wait for the address lookup in main.js.
  var lat = readCookie('so_lat'), lng = readCookie('so_lng');
  if (lat && lng) setLocation(lat, lng, readCookie('so_label'), false);
  // No location known (the lookup failed, or the visitor cleared it): show the country's popular events instead of hiding the section.
  var nationwideStarted = false;
  function startNationwide() {
    if (state.lat || nationwideStarted) return;
    nationwideStarted = true;
    state.anywhere = true;
    locLabel.textContent = 'Choose location';
    load(1);
  }
  document.addEventListener('so:location', function (e) {
    var d = e.detail || {};
    if (d.lat && d.lng && (d.lat != state.lat || d.lng != state.lng)) setLocation(d.lat, d.lng, d.label, false);
    else if (!d.lat && !d.lng) startNationwide();
  });
  setTimeout(function () { if (!state.lat && /^Finding/.test(locLabel.textContent)) locLabel.textContent = 'Choose location'; startNationwide(); }, 5000);
})();
