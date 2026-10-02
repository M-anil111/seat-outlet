/* Analytics events pushed to dataLayer (read by Google Tag Manager when it is allowed to load; inert otherwise). No personal data:
   no emails, no names of visitors, no cookie values. Event names: search_submit, suggestion_click, menu_click, feed_card_click. */
(function () {
  window.dataLayer = window.dataLayer || [];
  function track(name, data) { var o = { event: name }; for (var k in data) { if (data[k] !== undefined && data[k] !== '') o[k] = data[k]; } window.dataLayer.push(o); }
  window.soTrack = track;
  function clean(t) { t = String(t || '').replace(/\s+/g, ' ').trim().slice(0, 60); return /@|\d{6,}/.test(t) ? '' : t; }   // never send something that looks like an email or a long number

  // Header search: what kind of search was run (term, whether a location or dates were chosen).
  var form = document.querySelector('form.search-bar-form');
  if (form) form.addEventListener('submit', function () {
    var kw = document.getElementById('keywordHeader'), loc = document.getElementById('locationInputHeader'), st = document.getElementById('startInputHeader');
    track('search_submit', { search_term: clean(kw && kw.value), has_location: !!(loc && loc.value), has_dates: !!(st && st.value), page_type: location.pathname === '/' ? 'home' : 'other' });
  });

  // Search suggestions: which group (Performers, Cities, Venues, Recent searches, Trending now, Did you mean) and which row.
  document.addEventListener('click', function (e) {
    var a = e.target.closest('#keywordResultsHeader .result-item a'); if (!a) return;
    var li = a.parentElement, group = '', pos = 0, p = li.parentElement ? li.parentElement.children : [];
    for (var i = 0; i < p.length; i++) { if (p[i].classList.contains('suggestion-label')) group = p[i].textContent; if (p[i] === li) { pos = i; break; } }
    track('suggestion_click', { suggestion_group: group.toLowerCase().replace(/\s+/g, '_'), suggestion_position: pos + 1, suggestion_text: clean(a.textContent) });
  });

  // Menus: desktop mega menu and the phone drawer; menu_section is the top-level menu (concerts, sports, theater, ...).
  document.addEventListener('click', function (e) {
    var a = e.target.closest('.so-mega a, .so-mega-top, .so-menu__panel a, .so-menu__foot a'); if (!a) return;
    var section = '', device = 'desktop';
    var mega = a.closest('.so-mega');
    if (mega) section = (mega.id || '').replace('so-mega-', '');
    else if (a.classList.contains('so-mega-top')) section = a.getAttribute('data-so-mega') || '';
    else { device = 'phone'; var pn = a.closest('.so-menu__panel'); section = pn ? pn.getAttribute('data-panel') : 'search'; }
    track('menu_click', { menu_section: section, menu_device: device, link_text: clean(a.textContent).slice(0, 40) });
  });

  // Home feeds and Top picks: which row and which position the visitor chose.
  document.addEventListener('click', function (e) {
    var card = e.target.closest('.so-feed-card, #concerts .event-card, #sports .event-card, #theatre .event-card, #festival .event-card'); if (!card) return;
    var row = card.closest('[data-so-feed]'), kind = row ? row.getAttribute('data-so-feed') : '';
    var siblings, pos = 0;
    if (!kind) {
      if (card.closest('.top-picks')) { kind = 'top_picks'; siblings = card.closest('.top-picks').querySelectorAll('.tab-pane.active .event-card'); }
      else if (card.closest('#recentlyViewed')) { kind = 'recent'; }
    } else { siblings = row.querySelectorAll('.so-feed-card'); }
    if (!kind && card.closest('#recentlyViewed')) kind = 'recent';
    if (siblings) { for (var i = 0; i < siblings.length; i++) { if (siblings[i] === card) { pos = i + 1; break; } } }
    track('feed_card_click', { feed_row: kind || 'other', feed_position: pos || undefined });
  });
})();
