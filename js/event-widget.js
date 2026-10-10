/* Event page: loads the TicketNetwork seat-map widget (Seatics) and wires it to the page.
   - loads the widget script after the page is parsed (async), so a slow third party cannot block the page;
   - settings for the widget (checkout address, quantity sheet, sort order);
   - GA4 ecommerce events: view_item, select_item (listing click), begin_checkout (Buy click), no_inventory, widget_load_failed;
   - a watchdog: if the widget never answers, show a visible message instead of an empty box;
   - one consistent "no tickets" / "could not load" state (hero text, button, nudge) with an email alert form.
   The facts come from the JSON block event.php prints (#so-event-data). */
(function () {
  'use strict';
  var node = document.getElementById('so-event-data');
  if (!node) return;
  var ev;
  try { ev = JSON.parse(node.textContent); } catch (e) { return; }
  if (!ev || !ev.id) return;

  var WATCHDOG_MS = 12000;
  var WIDGET_HOST = 'https://mapwidget3.seatics.com/';
  var CUR = ev.currency || 'USD';
  var state = { loaded: false, empty: false, failed: false, buyTimer: null, buyPayloads: [], lastBuy: 0 };
  var selected = {};
  var watchdog = null;
  var root = document.documentElement;
  var map = document.getElementById('tn-maps');
  var box = document.getElementById('so-no-tickets');
  var hero = { price: document.querySelector('.so-evhero__price'), cta: document.querySelector('[data-so-cta]') };
  var original = { price: hero.price ? hero.price.innerHTML : '', cta: hero.cta ? hero.cta.textContent : '', href: hero.cta ? hero.cta.getAttribute('href') : '' };

  function dl() { return (window.dataLayer = window.dataLayer || []); }
  /* Ecommerce events: clear the previous ecommerce object first so GTM never merges stale items into this one. */
  function pushEc(name, ecommerce, extra) {
    dl().push({ ecommerce: null });
    var o = { event: name, ecommerce: ecommerce };
    for (var k in extra) if (Object.prototype.hasOwnProperty.call(extra, k)) o[k] = extra[k];
    dl().push(o);
  }
  function pushPlain(name, extra) {
    var o = { event: name, event_id: String(ev.id) };
    for (var k in extra) if (Object.prototype.hasOwnProperty.call(extra, k)) o[k] = extra[k];
    dl().push(o);
  }
  function item(extra) {
    var it = { item_id: String(ev.id), item_name: ev.name, item_category: ev.catName || '', affiliation: 'Seat Outlet', location_id: ev.venue || '' };
    for (var k in extra) if (Object.prototype.hasOwnProperty.call(extra, k) && extra[k] !== null && extra[k] !== undefined && extra[k] !== '') it[k] = extra[k];
    return it;
  }
  function num(v) {
    if (v === null || v === undefined || typeof v === 'object') return null;
    var n = parseFloat(String(v).replace(/[^0-9.]/g, ''));
    return isFinite(n) && n > 0 ? n : null;
  }
  /* The shape of the widget's click payload is not documented, so look for the usual field names (one level deep) and use nothing that is missing. */
  function dig(o, keys) {
    if (!o || typeof o !== 'object') return null;
    var i, k;
    for (i = 0; i < keys.length; i++) if (o[keys[i]] !== undefined && o[keys[i]] !== null && typeof o[keys[i]] !== 'object') return o[keys[i]];
    for (k in o) {
      if (o[k] && typeof o[k] === 'object' && !Array.isArray(o[k])) {
        for (i = 0; i < keys.length; i++) if (o[k][keys[i]] !== undefined && o[k][keys[i]] !== null && typeof o[k][keys[i]] !== 'object') return o[k][keys[i]];
      }
    }
    return null;
  }
  var PRICE_KEYS = ['price', 'pricePerTicket', 'ticketPrice', 'unitPrice', 'retailPrice'];
  var QTY_KEYS = ['quantity', 'qty', 'ticketQuantity', 'numTickets', 'selectedQuantity'];
  var TG_KEYS = ['ticketGroupId', 'tgId', 'tgid', 'ticketGroupID'];
  function details(data) {
    var price = num(dig(data, PRICE_KEYS));
    var qty = parseInt(dig(data, QTY_KEYS), 10);
    var tg = dig(data, TG_KEYS);
    return { price: price, qty: qty > 0 && qty < 100 ? qty : null, tg: tg !== null ? String(tg) : '' };
  }
  function round2(n) { return Math.round(n * 100) / 100; }

  /* ---- Buy click: one begin_checkout, and a first-party marker that lets /order-confirmation tell a real return from a pasted link ---- */
  function setCheckoutCookie() {
    try {
      var v = Math.floor(Date.now() / 1000) + '.' + ev.id;
      document.cookie = 'so_checkout=' + v + '; Max-Age=86400; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    } catch (e) { /* cookies blocked: the confirmation page then simply does not fire a purchase event */ }
  }
  function sendBuy() {
    state.buyTimer = null;
    var d = { price: null, qty: null, tg: '' };
    state.buyPayloads.forEach(function (p) {
      var x = details(p);
      if (d.price === null) d.price = x.price;
      if (d.qty === null) d.qty = x.qty;
      if (!d.tg) d.tg = x.tg;
    });
    state.buyPayloads = [];
    var estimated = d.price === null;
    var price = estimated ? ev.lowPrice : d.price;      // no price in the click: fall back to the lowest listed price and say so
    var qty = d.qty || 1;
    var ec = { currency: CUR, items: [item({ price: price, quantity: qty, item_variant: d.tg })] };
    if (price !== null && price !== undefined) ec.value = round2(price * qty);
    pushEc('begin_checkout', ec, { event_id: String(ev.id), value_estimated: estimated });
    setCheckoutCookie();
  }
  function onBuy(data) {
    state.buyPayloads.push(data);
    if (state.buyTimer) return;                          // the config callback and the tracking listener both report the same click
    if (Date.now() - state.lastBuy < 1500) return;
    state.lastBuy = Date.now();
    state.buyTimer = setTimeout(sendBuy, 150);
  }
  function onListingClick(type, data) {
    var d = details(data);
    var key = d.tg || type;
    if (selected[key] && Date.now() - selected[key] < 1500) return;
    selected[key] = Date.now();
    var price = d.price !== null ? d.price : null;
    var ec = { currency: CUR, items: [item({ price: price, quantity: d.qty, item_variant: d.tg })] };
    if (price !== null) ec.value = round2(price * (d.qty || 1));
    pushEc('select_item', ec, { event_id: String(ev.id), item_list_name: 'ticket_listings' });
  }

  /* ---- Empty and failed states: the same text everywhere on the page ---- */
  function showState(kind, title, text, reason) {
    if (state.empty || (state.failed && kind === 'failed')) return;
    state[kind] = true;
    if (watchdog) { clearTimeout(watchdog); watchdog = null; }
    if (!box) return;
    var t = document.getElementById('so-no-tickets-title'), x = document.getElementById('so-no-tickets-text');
    if (title && t) t.textContent = title;
    if (text && x) x.textContent = text;
    var reload = document.getElementById('so-no-tickets-reload');
    if (reload) reload.hidden = kind !== 'failed';
    box.classList.remove('d-none');
    if (map) map.style.display = 'none';
    root.classList.add('so-ev-empty');
    if (hero.price) hero.price.textContent = kind === 'failed' ? 'Seat map unavailable' : 'No tickets listed right now';
    if (hero.cta) { hero.cta.textContent = kind === 'failed' ? 'See what to do' : 'Get notified'; hero.cta.setAttribute('href', '#so-no-tickets'); }
    var nudgeX = document.querySelector('#soNudge .so-nudge__x');
    if (nudgeX) nudgeX.click();   // closes it the normal way (page made inert while it is open is released)
    if (kind === 'failed') pushPlain('widget_load_failed', { reason: reason || 'unknown', online: navigator.onLine !== false });
    else pushPlain('no_inventory', { reason: title || '' });
  }
  function showEmpty(title, text) { showState('empty', title, text, ''); }
  function showFailed(reason) {
    showState('failed', 'The seat map did not load',
      'This can happen with an ad blocker, a slow connection or when our ticket partner is busy. Reload the page to try again. If it keeps happening, contact us and we will help you find tickets.', reason);
  }
  function recover() {
    if (!state.failed) return;
    state.failed = false;
    root.classList.remove('so-ev-empty');
    if (box) box.classList.add('d-none');
    if (map) map.style.display = '';
    if (hero.price) hero.price.innerHTML = original.price;
    if (hero.cta) { hero.cta.textContent = original.cta; hero.cta.setAttribute('href', original.href); }
  }
  var reloadBtn = document.querySelector('[data-so-reload]');
  if (reloadBtn) reloadBtn.addEventListener('click', function () { location.reload(); });

  /* ---- Widget settings and hooks (MapWidget3 Integration Guide v1.4, "Special Functions" and "User Event Tracking") ---- */
  function configure() {
    var S = window.Seatics;
    if (!S || !S.config) { showFailed('no_global'); return; }
    var c = S.config;
    if (ev.checkoutUrl) c.checkoutUrl = ev.checkoutUrl;
    if (ev.checkoutDomain) c.c3CheckoutDomain = ev.checkoutDomain;   // the hosted checkout's domain, set from the maps page (checkout.seatoutlet.com)
    c.enableLegalDisclosureMobile = true;
    c.preCheckoutButtonHtml = 'Continue to Payment';
    c.buyButtonContentHtml = '<div class="buy-btn">' + 'Buy Now' + '</div>';
    if (S.SortOptions) c.defaultSort = S.SortOptions.PriceAsc;
    c.tgMarkTooltipText = 'We recommend this seller&#039;s tickets.';
    c.enableMyList = true;
    c.showCents = false;
    c.skipPrecheckoutMobile = true;
    c.skipPrecheckoutDesktop = true;
    c.showZoomControls = true;
    c.ticketListOnRight = true;
    c.legendExpanded = true;
    // The "How many tickets?" sheet that opens first, the per-listing value score and the widget's own urgency messages (all TicketNetwork data;
    // if the widget has no figure it shows nothing).
    c.enableQuantityModal = true;
    c.forceQuantityModalSelection = false;
    c.enableValueScore = true;
    c.showOtherCustomersUrgencyMessagingMobile = true;
    c.showOtherCustomersUrgencyMessagingDesktop = true;
    c.noEventHandler = function () {
      // Our own listing says tickets exist for this event (the "From $63" on the listing page), so a seat map that reports "no event"
      // is the map service not having the event ready, not an empty event. Say that, track the mismatch, and try once more by reloading
      // (guarded so it can never loop); only an event with no listed tickets gets the "not available" message.
      if (ev.hasTickets && ev.tickets > 0) {
        var key = 'so_wr_' + ev.id, again = false;
        try { again = !!sessionStorage.getItem(key); if (!again) sessionStorage.setItem(key, String(Date.now())); } catch (e) { again = true; }
        pushPlain('widget_no_event_but_listed', { event_id: String(ev.id), retried: again, tickets: ev.tickets });
        if (!again) { setTimeout(function () { location.reload(); }, 3000); }
        showState('failed', 'The seat map is not ready for this event yet',
          'Tickets are listed for this event' + (ev.price ? ' from ' + ev.price : '') + ', but the seat map did not return them just now. ' + (again ? 'Reload the page in a minute. If it keeps happening, contact us and we will help you find tickets.' : 'Trying again in a moment.'), 'no_event_but_listed');
        return;
      }
      showEmpty('Tickets are not available to show right now', 'Please try again in a little while, or browse other dates for the same performer, city or venue below.');
    };
    c.noTicketsHandler = function () { showEmpty(); };
    c.onBuyButtonClicked = function (data) { onBuy(data); };
    if (S.TrackingEvents && S.TrackingEvents.registerEventListener) {
      S.TrackingEvents.registerEventListener(function (type, data) {
        if (type === 'FinishedLoading') {
          state.loaded = true;
          if (watchdog) { clearTimeout(watchdog); watchdog = null; }
          demoteWidgetH1();
          if (data && data.numTicketGroups === 0) { showEmpty(); return; }
          recover();
          var ec = { currency: CUR, items: [item({ price: ev.lowPrice })] };
          if (ev.lowPrice !== null && ev.lowPrice !== undefined) ec.value = ev.lowPrice;
          pushEc('view_item', ec, { event_id: String(ev.id), ticket_groups: data && data.numTicketGroups, tickets_available: data && data.numTickets });
        } else if (type === 'BuyButtonClicked') {
          onBuy(data);
        } else if (/listing|ticketgroup|ticket_group|tgselect|tgclick/i.test(String(type))) {
          onListingClick(type, data);
        }
      });
    }
  }

  /* The seat-map widget prints the event name as a second <h1>. One page, one top heading: the widget's heading becomes a level-2 heading
     element (same classes, same children, same look), here and again if the widget redraws its header. */
  function demoteWidgetH1() {
    var box = document.getElementById('tn-maps');
    if (!box) return;
    var hs = box.querySelectorAll('h1');
    for (var i = 0; i < hs.length; i++) {
      var h = hs[i], d = document.createElement('div');
      for (var a = 0; a < h.attributes.length; a++) d.setAttribute(h.attributes[a].name, h.attributes[a].value);
      d.setAttribute('role', 'heading');
      d.setAttribute('aria-level', '2');
      d.style.lineHeight = window.getComputedStyle(h).lineHeight;   // a div's default line height differs from the h1's
      while (h.firstChild) d.appendChild(h.firstChild);
      h.parentNode.replaceChild(d, h);
    }
    if (!demoteWidgetH1.watching && window.MutationObserver) {
      demoteWidgetH1.watching = true;
      var busy = false;
      new MutationObserver(function () {
        if (busy || !box.querySelector('h1')) return;
        busy = true;
        setTimeout(function () { busy = false; demoteWidgetH1(); }, 50);
      }).observe(box, { childList: true, subtree: true });
    }
  }

  function load() {
    if (window.Seatics) { configure(); return; }
    var url = String(ev.widgetUrl || '');
    if (url.indexOf(WIDGET_HOST) !== 0) { showFailed('bad_url'); return; }
    var s = document.createElement('script');
    s.src = url;
    s.async = true;
    s.onload = function () { configure(); };
    s.onerror = function () { showFailed('script_error'); };
    document.body.appendChild(s);
    watchdog = setTimeout(function () { if (!state.loaded && !state.empty) showFailed('timeout'); }, WATCHDOG_MS);
  }
  load();
})();
