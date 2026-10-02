/* Listing hubs: "Explore ... near you". Location comes from the same cookies the rest of the site uses (set by the
   address lookup in main.js, or chosen here); the browser's permission prompt only ever opens from the
   "Use my current location" button. Everything is optional: with no location the section simply stays hidden and the full
   national list below is shown. */
(function () {
  var root = document.querySelector('[data-so-explore]');
  if (!root) return;

  var cat = root.getAttribute('data-cat') || 'all';
  var noun = root.getAttribute('data-noun') || 'events';
  var $ = function (sel) { return root.querySelector(sel); };
  var locBtn = $('[data-so-loc]'), locLabel = $('[data-so-loc-label]'), locPop = $('[data-so-loc-pop]');
  var locInput = $('#soNearInput'), locHere = $('[data-so-loc-here]');
  var dateBtn = $('[data-so-date]'), dateLabel = $('[data-so-date-label]'), datePop = $('[data-so-date-pop]');
  var near = $('[data-so-near]'), grid = $('[data-so-near-grid]'), more = $('[data-so-near-more]'), title = $('[data-so-near-title]');
  var state = { lat: '', lng: '', label: '', when: '', page: 1, token: 0 };

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

  function card(ev, i) {
    var badge = whenBadge(ev.iso) || (ev.top ? 'Popular near you' : '');
    return '<a class="so-feed-card" href="/event/' + slug(ev.name) + '-' + ev.id + '">' +
      '<div class="so-feed-card__img">' +
      '<img src="' + esc(ev.placeholder) + '" alt="' + esc(ev.name) + '" class="event-dynamic-image blur-image" width="260" height="260" loading="lazy"' +
      ' data-event="' + encodeURIComponent(ev.name) + '" data-artist="' + encodeURIComponent(ev.performer || '') + '"' +
      ' data-venue="' + encodeURIComponent(ev.venue || '') + '" data-tab="' + encodeURIComponent(ev.tab) + '"' +
      " data-category='" + esc(JSON.stringify(ev.defaultCategory || {})) + "'>" +
      (badge ? '<span class="so-feed-card__badge' + (badge === 'Popular near you' ? ' so-feed-card__badge--hot' : '') + '">' + esc(badge) + '</span>' : '') +
      '</div>' +
      '<h3 class="so-feed-card__name">' + esc(ev.name) + '</h3>' +
      '<p class="so-feed-card__meta">' + esc(ev.date) + '</p>' +
      '<p class="so-feed-card__meta">' + esc(ev.venue) + (ev.loc ? ' - ' + esc(ev.loc) : '') + '</p>' +
      (ev.price ? '<p class="so-feed-card__price">From <strong>' + esc(ev.price) + '</strong></p>' : '') +
      '</a>';
  }

  function skeleton(n) {
    var h = '';
    for (var i = 0; i < n; i++) h += '<div class="so-feed-card so-feed-card--skeleton" aria-hidden="true"><div class="so-feed-card__img"></div><div class="so-feed-card__line"></div><div class="so-feed-card__line so-feed-card__line--short"></div></div>';
    return h;
  }

  function setTitle() {
    title.textContent = 'Explore ' + noun + ' near ' + (state.label || 'you');
  }

  function load(page) {
    if (!state.lat || !state.lng) { near.hidden = true; return; }
    var my = ++state.token;
    state.page = page;
    if (page === 1) { grid.innerHTML = skeleton(4); near.hidden = false; more.hidden = true; }
    more.disabled = true;
    var qs = 'kind=near&cat=' + encodeURIComponent(cat) + '&when=' + encodeURIComponent(state.when) + '&page=' + page +
      '&lat=' + encodeURIComponent(state.lat) + '&lng=' + encodeURIComponent(state.lng);
    fetch('/ajax/get-home-feed.php?' + qs).then(function (r) { return r.json(); }).then(function (data) {
      if (my !== state.token) return;
      var events = (data && data.events) || [];
      if (page === 1) {
        if (!events.length) { near.hidden = true; return; }
        setTitle();
        grid.innerHTML = '';
        events.forEach(function (e, i) { e.top = i < 3 && !state.when; });
      }
      var start = grid.querySelectorAll('.so-feed-card').length;
      var html = events.map(function (e, i) { return card(e, start + i); }).join('');
      if (page === 1) grid.innerHTML = html; else grid.insertAdjacentHTML('beforeend', html);
      more.hidden = !(data && data.hasMore);
      more.disabled = false;
      if (window.soBatchLoadImages) window.soBatchLoadImages(grid, '.event-dynamic-image:not(.loaded)', function () { return my === state.token; });
    }).catch(function () { if (my === state.token && page === 1) near.hidden = true; more.disabled = false; });
  }

  function setLocation(lat, lng, label, save) {
    state.lat = lat; state.lng = lng; state.label = label || '';
    locLabel.textContent = label || 'Near you';
    if (save) {
      if (typeof setCookie === 'function') { setCookie('so_lat', encodeURIComponent(lat)); setCookie('so_lng', encodeURIComponent(lng)); setCookie('so_label', label || ''); }
    }
    load(1);
  }

  function closePops() {
    [[locBtn, locPop], [dateBtn, datePop]].forEach(function (pair) { pair[1].hidden = true; pair[0].setAttribute('aria-expanded', 'false'); });
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
  dateBtn.addEventListener('click', function (e) { e.stopPropagation(); toggle(dateBtn, datePop); });
  document.addEventListener('click', function (e) { if (!root.contains(e.target)) closePops(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePops(); });

  datePop.addEventListener('click', function (e) {
    var b = e.target.closest('[data-when]');
    if (!b) return;
    state.when = b.getAttribute('data-when') || '';
    dateLabel.textContent = b.textContent.trim();
    datePop.querySelectorAll('.so-pop__row').forEach(function (r) { r.classList.toggle('is-active', r === b); });
    dateBtn.classList.toggle('so-chip--on', state.when !== '');
    closePops();
    load(1);
  });

  more.addEventListener('click', function () { load(state.page + 1); });

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
  document.addEventListener('so:location', function (e) {
    var d = e.detail || {};
    if (d.lat && d.lng && (d.lat != state.lat || d.lng != state.lng)) setLocation(d.lat, d.lng, d.label, false);
  });
  setTimeout(function () { if (!state.lat && /^Finding/.test(locLabel.textContent)) locLabel.textContent = 'Choose location'; }, 5000);
})();
