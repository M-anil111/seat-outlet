/* Escape a value for HTML text and attributes: names come from the ticket API and are put into innerHTML templates below. */
function soEsc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
/* =====================================================
    EVENTS Section
===================================================== */

  document.addEventListener('DOMContentLoaded', function () {

    const cityInput = document.getElementById('cityLocationInput');
    const locationText = document.getElementById('locationSelectorText');
    const useCurrentLocationBtn = document.getElementById('useCurrentLocationCity');    
    const locationToggleBtn = document.getElementById('locationToggleBtn');
    const locationPanel = document.getElementById('locationPanel');
    const locationClearBtn = document.getElementById('locationClearBtn');

    let currentLoadToken = 0;

    function getActiveTabId() {
      const activePane = document.querySelector('.tab-pane.show.active');
      return activePane ? activePane.id : 'concerts';
    }

    function buildCards(data) {
      return data.map(function (event) {
        return '<div class="so-evc-slide">' + window.soEvCard(event) + '</div>';
      }).join('');
    }

    function generateEventSkeleton(count = 4) {
      let html = '';
      for (let i = 0; i < count; i++) html += '<div class="so-evc-slide">' + window.soEvCardSkeleton(1) + '</div>';
      return html;
    }

    function initSlider(selector) {
      const $slider = $(selector);
  
      if ($slider.hasClass('slick-initialized')) {
        $slider.slick('unslick');
      }
  
      $slider.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        infinite: false,
        arrows: true,
        autoplay: false,
        dots: false,
        responsive: [
          { breakpoint: 992, settings: { slidesToShow: 3 } },
          { breakpoint: 768, settings: { slidesToShow: 2 } },
          { breakpoint: 576, settings: { slidesToShow: 1.5 } }
        ]
      });
    }
    
    async function loadImagesOneByOne(container, loadToken) {
      // Name kept for the call sites; it is one batched request now.
      await window.soBatchLoadImages(container, '.event-dynamic-image', () => loadToken === currentLoadToken);
    }

    window.loadLocationCategory = async function(tabId, type, loc1, loc2) {

      const selector = '#' + tabId + ' .custom-slider';
      const container = document.querySelector(selector);
      if (!container) return;

      const loadToken = ++currentLoadToken;

      const ajaxUrl =
        `/ajax/get-location-category-events.php?tab=${encodeURIComponent(tabId)}` +
        `&type=${encodeURIComponent(type)}` +
        `&loc1=${encodeURIComponent(loc1)}` +
        `&loc2=${encodeURIComponent(loc2)}`;
        
      container.classList.add('skeleton-loading');
      container.innerHTML = generateEventSkeleton(4);
      initSlider(selector);

      try {
        const res = await fetch(ajaxUrl);
        const data = await res.json();
  
        if (loadToken !== currentLoadToken) return;
  
        if (!data || !data.length) {
          const $slider = $(selector);
          if ($slider.hasClass('slick-initialized')) {
            $slider.slick('unslick');
          }
          container.innerHTML = '<p class="text-center">No events found</p>';
          return;
        }
  
        const $slider = $(selector);
        if (!$slider.hasClass('slick-initialized')) {
          initSlider(selector);
        }
  
        const newHtml = buildCards(data);
        $slider.slick('slickRemove', null, null, true);  
        $slider.slick('slickAdd', newHtml);
        container.classList.remove('skeleton-loading');
        $slider.slick('setPosition');
  
      } catch (err) {
        const $slider = $(selector);
        if ($slider.hasClass('slick-initialized')) {
          $slider.slick('unslick');
        }
        container.classList.remove('skeleton-loading');
        container.innerHTML =
          '<div class="text-center py-3">' +
          '<p class="mb-2">We couldn\'t load events right now.</p>' +
          '<button type="button" class="btn btn-outline-primary btn-sm retry-load-events">Try Again</button>' +
          '</div>';
        const retryBtn = container.querySelector('.retry-load-events');
        if (retryBtn) {
          retryBtn.addEventListener('click', function () {
            window.loadLocationCategory(tabId, type, loc1, loc2);
          });
        }
      }

    }
    
    window.reloadActiveTab = function (mode = '', loc = {}) {
      const tabId = getActiveTabId();
  
      if (mode === 'll') {
        return loadLocationCategory(tabId, 'll', loc.lat, loc.lng);
      }
  
      return loadLocationCategory(tabId, '', '', '');
    };

    (function init() {
      const savedLabel = getCookie('so_label');
      if (savedLabel && locationText) {
        soSetLocText(savedLabel);
        document.getElementById('cityLocationInput').value = savedLabel;
      }

      window.locationReady = true;
      const { mode, data } = detectLocationMode();  
      reloadActiveTab(mode, data);

      setTimeout(() => {
        $('.slick-slider').slick('setPosition');
      }, 100);
    })();

    function detectLocationMode() {
      const lat = getCookie('so_lat');
      const lng = getCookie('so_lng');
  
      if (lat && lng) return { mode: 'll', data: { lat, lng } };
  
      return { mode: '', data: {} };
    }

    document.querySelectorAll('button.category-pill').forEach(pill => {
      pill.addEventListener('shown.bs.tab', function () {
        const { mode, data } = detectLocationMode();
        reloadActiveTab(mode, data);
  
        setTimeout(() => {
          $('.slick-slider').slick('setPosition');
        }, 100);
      });
    });
  
    if (useCurrentLocationBtn) {
      useCurrentLocationBtn.addEventListener('click', function () {
        if (!navigator.geolocation) {
          alert('Geolocation not supported');
          return;
        }
  
        navigator.geolocation.getCurrentPosition((position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          
          setCookie('so_lat', encodeURIComponent(lat));
          setCookie('so_lng', encodeURIComponent(lng));

          getCityState(lat, lng, function(so_cs) {

            setCookie('so_label', so_cs);
        
            if (locationText) {
              soSetLocText(so_cs);
            }
    
            reloadActiveTab('ll', { lat, lng });
        
          });  
          
      
        });
      });
    }

    if (locationToggleBtn && locationPanel) {
      locationToggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        locationPanel.classList.toggle('show');
        if (cityInput) cityInput.focus();
      });

      document.addEventListener('click', function (e) {
        if (!locationPanel.classList.contains('show')) return;
        if (locationPanel.contains(e.target) || e.target === locationToggleBtn) return;
        locationPanel.classList.remove('show');
      });
    }

    if (locationClearBtn) {
      locationClearBtn.addEventListener('click', function () {
        const loadNationalEvents = () => {
          setCookie('so_lat', '');
          setCookie('so_lng', '');
          setCookie('so_label', '');
          if (cityInput) cityInput.value = '';
          soSetLocText('');
          if (typeof reloadActiveTab === 'function') {
            reloadActiveTab('', {});
          }
          if (typeof loadNearbyVenues === 'function') {
            loadNearbyVenues();
          }
        };

        fetch(`/ajax/get_ip_details.php`)
          .then(res => res.json())
          .then(data => {
            // Same failure mode as main.js's version of this lookup: an
            // empty response (rate limited, IP not resolvable, timeout)
            // must not overwrite the location field with a literal
            // "undefined, undefined".
            if (!data || !data.city || !data.state || !data.lat || !data.lng) {
              loadNationalEvents();
              return;
            }

            setCookie('so_lat', encodeURIComponent(data.lat));
            setCookie('so_lng', encodeURIComponent(data.lng));
            setCookie('so_label', data.city + ', ' + data.state);
            if (cityInput) cityInput.value = '';
            
            setTimeout(() => {
              soSetLocText(data.city + ', ' + data.state);
                          
              if (typeof reloadActiveTab === 'function') {
                reloadActiveTab('ll', { lat: data.lat, lng: data.lng });
              }
              if (typeof loadNearbyVenues === 'function') {
                loadNearbyVenues();
              }             
            }, 200);
            
          })
        .catch(loadNationalEvents);
        
      });
    }

    const cityLocationInput = document.getElementById('cityLocationInput');
    if (cityLocationInput) {
      cityLocationInput.addEventListener('focus', async function once() {
        await loadGoogleMapsApi();
        initLocationSearch('cityLocationInput', 'home');
        cityLocationInput.removeEventListener('focus', once);
      }, { once: true });
    }
  });
   
/* =====================================================
    EVENTS End
===================================================== */
  
/* =====================================================
    VENUES
===================================================== */
  
  function buildVenueSkeleton(count = 8) {
  
    let html = '';
  
    for (let i = 0; i < count; i++) {
  
        html += `
            <div class="venue-card-skeleton">
                <div class="skeleton-img shimmer"></div>
                <div class="venue-content text-center p-3">
                    <div class="skeleton-line skeleton-title shimmer"></div>
                    <div class="skeleton-line skeleton-location shimmer"></div>
                </div>
            </div>
        `;
    }
  
    return html;
  }

  let venueLoadToken = 0;

  async function loadVenueImagesOneByOne(container, loadToken) {
    await window.soBatchLoadImages(container, '.venue-dynamic-image', () => loadToken === venueLoadToken);
  }
  
  window.loadNearbyVenues = function() { 
      
      const container = document.querySelector('.venue-slider');
      if (!container) return;
      const venueBlock = document.querySelector('.venue-section');

      const loadToken = ++venueLoadToken;

      const solt = getCookie('so_lat') || '';
      const solg = getCookie('so_lng') || '';

      const ajaxUrlVenue =
    `/ajax/get-nearby-venues.php?solt=${encodeURIComponent(solt)}&solg=${encodeURIComponent(solg)}`;

    container.innerHTML = buildVenueSkeleton(4);
    initVenueSlider('skeleton');
  
      let scope = 'near';
      fetch(ajaxUrlVenue)
      .then(res => { scope = res.headers.get('X-So-Venue-Scope') || 'near'; return res.json(); })
        .then(data => {

          if (loadToken !== venueLoadToken) return;

          // Nothing to show (no venues near the visitor and no saved top-venue list): hide the whole block instead of
          // leaving a heading over an empty box.
          if (!data || !data.length) {
            const $empty = $('.venue-slider');
            if ($empty.hasClass('slick-initialized')) { $empty.slick('unslick'); }
            container.innerHTML = '';
            if (venueBlock) venueBlock.hidden = true;
            return;
          }
          if (venueBlock) venueBlock.hidden = false;

          // "Near <place>" only when these really are venues near the visitor; the fallback list is the top venues overall.
          const venueTitle = document.querySelector('.venue-section h2');  
          if (venueTitle) {
              const solabel = getCookie('so_label') || '';
              venueTitle.textContent = (solabel && scope === 'near')
                ? `Top Venues Near ${solabel}`
                : 'Top Venues';
          }

          const $vslider = $('.venue-slider');
          if ($vslider.hasClass('slick-initialized')) {
            $vslider.slick('unslick');
          }

          let html = '';
  
          const eyebrow = document.getElementById('venueEyebrow');
          if (eyebrow) eyebrow.textContent = (scope === 'near' && (getCookie('so_label') || '')) ? 'Trending near you' : 'Popular venues';

          data.forEach((venue, index) => {
            html += '<div class="so-vc-slide"><a class="so-vc so-vc--' + (index % 4) + '" href="/venue/' + encodeURI(String(venue.slug || '')) + '">' +
              '<h3 class="so-vc__name">' + soEsc(venue.name) + '</h3>' +
              '<p class="so-vc__loc">' + soEsc(venue.city) + (venue.state ? ', ' + soEsc(venue.state) : '') + '</p>' +
              '<span class="so-vc__cta">View Events <span class="so-vc__go" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></span></a></div>';
          });

          container.innerHTML = html;          
          setTimeout(() => {
            initVenueSlider();
          }, 50);
          

        })
        .catch(err => {
          console.error('VENUE ERROR:', err);
          const $vslider = $('.venue-slider');
          if ($vslider.hasClass('slick-initialized')) {
            $vslider.slick('unslick');
          }
          container.innerHTML =
            '<div class="text-center py-3">' +
            '<p class="mb-2">We couldn\'t load venues right now.</p>' +
            '<button type="button" class="btn btn-outline-primary btn-sm retry-load-venues">Try Again</button>' +
            '</div>';
          const retryBtn = container.querySelector('.retry-load-venues');
          if (retryBtn) {
            retryBtn.addEventListener('click', function () {
              window.loadNearbyVenues();
            });
          }
        });

  }

  function initVenueSlider(loader = '') {
  
      const $slider = $('.venue-slider');
  
      if ($slider.hasClass('slick-initialized')) {
          $slider.slick('unslick');
      }
  
      $slider.slick({
          slidesToShow: 4,
          slidesToScroll: 1,
          arrows: loader === 'skeleton' ? false : true,
          autoplay: false,
          dots: false,
          infinite: false,
          responsive: [
              { breakpoint: 992, settings: { slidesToShow: 3 } },
              { breakpoint: 768, settings: { slidesToShow: 2 } },
              { breakpoint: 576, settings: { slidesToShow: 1.5 } }
          ]
      });
  }
 
  document.addEventListener('DOMContentLoaded', function () {

    const venueSection = document.querySelector('.venue-section');
    
    // 🔥 VENUES
    if (typeof loadNearbyVenues === 'function') {
  
      if (!venueSection) {
        loadNearbyVenues(); // fallback
      } else if (venueSection.getBoundingClientRect().top < window.innerHeight) {
        loadNearbyVenues(); // already visible
      } else {
        const venueObserver = new IntersectionObserver((entries, observer) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              loadNearbyVenues();
              observer.disconnect();
            }
          });
        }, { rootMargin: '600px' });
  
        venueObserver.observe(venueSection);
      }
    }
  
  });
 
/* =====================================================
    VENUES End
===================================================== */

/* =====================================================
    Top Performers Start
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

  const section = document.getElementById("topPerformersSection");
  if (!section) return;

  let loaded = false;

  const observer = new IntersectionObserver((entries, obs) => {
    if (entries[0].isIntersecting && !loaded) {
      loaded = true;
      obs.disconnect();
      loadPerformers();
    }
  }, { threshold: 0.2 });

  observer.observe(section);

  // -----------------------
  // Fetch JSON
  // -----------------------
  function loadPerformers() {
    fetch('/ajax/get-top-performers.php', { cache: "force-cache" })
      .then(res => {
        if (!res.ok) throw new Error('Failed to load JSON');
        return res.json();
      })
      .then(data => {
        renderList(data.concerts, 'concerts-list');
        renderList(data.sports, 'sports-list');
        renderList(data.theater, 'theater-list');
        // "From $53": the lowest listed price among that card's performers (computed on the server from TicketNetwork's own figures)
        Object.keys(data.from || {}).forEach(k => {
          const el = document.querySelector('[data-so-from="' + k + '"]');
          if (el && data.from[k] > 0) { el.textContent = 'From $' + data.from[k]; el.hidden = false; }
        });
      })
      .catch(err => {
        console.error(err);
        showError('concerts-list');
        showError('sports-list');
        showError('theater-list');
      });
  }

  // -----------------------
  // Render List: avatar (stored picture, otherwise an initials tile), name, chevron
  // -----------------------
  function initials(name) {
    const w = String(name || '').replace(/[^A-Za-z0-9 ]+/g, ' ').trim().split(/\s+/).filter(Boolean);
    return ((w[0] || '?')[0] + (w.length > 1 ? w[w.length - 1][0] : '')).toUpperCase();
  }
  function hue(name) { let h = 0; for (const c of String(name || '')) h = (h * 31 + c.charCodeAt(0)) % 360; return h; }

  function renderList(list, elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;

    if (!list || list.length === 0) {
      el.innerHTML = '<li class="so-topc__row so-topc__row--empty">Check back soon</li>';
      return;
    }

    const html = list.map(item => {
      const avatar = item.img
        ? '<img class="so-topc__avatar" src="' + soEsc(item.img) + '" alt="" width="44" height="44" loading="lazy" decoding="async">'
        : '<span class="so-topc__avatar so-topc__avatar--init" style="--so-hue:' + hue(item.name) + '" aria-hidden="true">' + soEsc(initials(item.name)) + '</span>';
      return '<li><a class="so-topc__row" href="/artist/' + encodeURI(String(item.slug || '')) + '">' + avatar +
        '<span class="so-topc__name">' + soEsc(item.name) + '</span>' +
        '<svg class="so-topc__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></a></li>';
    }).join('');

    el.innerHTML = html;
  }

  // -----------------------
  // Error fallback
  // -----------------------
  function showError(id) {
    const el = document.getElementById(id);
    if (!el) return;

    el.innerHTML = '<li>Unable to load data</li>';
  }

});

/* =====================================================
    Top Performers End
===================================================== */

/* =====================================================
    Phone tabs for the category and performer lists
    ([data-so-tabs]: a tab bar is built from data-so-tab-labels, one column shows at a time; wider screens show all columns
    and CSS hides the bar. Without JavaScript every column simply stays visible.)
===================================================== */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-so-tabs]').forEach(function (box, boxIndex) {
    var cols = box.querySelectorAll('.categories__grid > .categories__col');
    var labels = (box.getAttribute('data-so-tab-labels') || '').split('|');
    if (!cols.length || cols.length !== labels.length) return;

    var bar = document.createElement('div');
    bar.className = 'so-tabs__bar';
    bar.setAttribute('role', 'tablist');
    var tabs = [];

    function select(index) {
      tabs.forEach(function (tab, i) {
        var on = i === index;
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
        tab.tabIndex = on ? 0 : -1;
        cols[i].classList.toggle('is-active', on);
      });
    }

    labels.forEach(function (label, i) {
      var tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'so-tabs__tab';
      tab.setAttribute('role', 'tab');
      tab.id = 'so-tab-' + boxIndex + '-' + i;
      tab.textContent = label;
      cols[i].setAttribute('role', 'tabpanel');
      cols[i].setAttribute('aria-labelledby', tab.id);
      tab.addEventListener('click', function () { select(i); });
      tab.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        var next = (i + (e.key === 'ArrowRight' ? 1 : labels.length - 1)) % labels.length;
        select(next);
        tabs[next].focus();
      });
      tabs.push(tab);
      bar.appendChild(tab);
    });

    box.insertBefore(bar, box.querySelector('.categories__grid'));
    select(0);
    box.setAttribute('data-so-tabs-ready', '');
  });
});


/* =====================================================
    HOME FEEDS: "Last-minute tickets" and "Trending events"
    Located by the same address lookup as Top Picks (no browser permission prompt); falls back to the whole country.
===================================================== */
document.addEventListener('DOMContentLoaded', function () {
  const boxes = Array.from(document.querySelectorAll('[data-so-feed]'));
  if (!boxes.length) return;
  const loaded = {};
  let token = 0;

  const esc = (typeof escapeHtml === 'function') ? escapeHtml : (v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])));
  const slug = (typeof normalizeKey === 'function') ? normalizeKey : (v => String(v).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''));

  function whenBadge(iso) {
    if (!iso) return '';
    const p = iso.split('-').map(Number);
    const d = new Date(p[0], p[1] - 1, p[2]);
    const now = new Date(); now.setHours(0, 0, 0, 0);
    const days = Math.round((d - now) / 86400000);
    if (days <= 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    return d.toLocaleDateString('en-US', { weekday: 'long' });
  }

  function card(ev, i, kind) {
    const badge = (kind === 'lastminute' || kind === 'weekend') ? whenBadge(ev.iso) : '';
    return window.soEvCard(ev, { status: badge });
  }

  // "Popular this weekend": the same text-only card as everywhere else, with a soft category-colored header.
  function popCard(ev) { return window.soEvCard(ev, { tint: true }); }

  function render(box, kind, data, label) {
    const track = box.querySelector('[data-so-feed-track]');
    const title = box.querySelector('.so-feed__title');
    const sub = box.querySelector('[data-so-feed-sub]');
    const events = (data && data.events) || [];
    if (!events.length) { box.hidden = true; return; }
    if (kind === 'popweekend') {
      box.hidden = false;
      track.innerHTML = events.map(popCard).join('');
      track.scrollLeft = 0;
      const nx = box.querySelector('[data-so-feed-next]');
      if (nx && !nx.dataset.bound) {
        nx.dataset.bound = '1';
        // One page of whole cards per click (the cards fill the row exactly on desktop); back to the start after the last page.
        nx.addEventListener('click', function () {
          const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
          const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
          if (atEnd) track.scrollTo({ left: 0, behavior: 'smooth' });
          else track.scrollBy({ left: track.clientWidth + gap, behavior: 'smooth' });
        });
      }
      return;
    }
    // "This weekend near you" only makes sense for events really close by (not the "nearest anywhere" fallback).
    if (kind === 'weekend' && data.scope !== 'near') { box.hidden = true; return; }
    box.hidden = false;
    const near = data.scope === 'near';
    const where = near && label ? ' near ' + label : (near ? ' near you' : '');
    if (title) {
      title.textContent = kind === 'weekend'
        ? 'This weekend' + (label ? ' near ' + label : ' near you')
        : (kind === 'lastminute'
          ? 'Last-minute tickets' + where
          : 'Trending events' + where);
    }
    if (sub) {
      sub.textContent = kind === 'weekend'
        ? 'Events within 50 miles, Friday to Sunday'
        : (kind === 'lastminute'
          ? (near ? 'Happening in the next 7 days within 50 miles' : 'Happening in the next 7 days')
          : (near ? 'Popular within 50 miles of you' : 'What fans are buying right now'));
    }
    track.innerHTML = events.map((ev, i) => card(ev, i, kind)).join('');
    track.scrollLeft = 0;
    if (window.soBatchLoadImages) window.soBatchLoadImages(track, '.event-dynamic-image', () => true);
  }

  function load(lat, lng) {
    const fullKey = (lat && lng) ? (Number(lat).toFixed(2) + ',' + Number(lng).toFixed(2)) : 'us';
    const label = (typeof getCookie === 'function' ? getCookie('so_label') : '') || '';
    const my = ++token;
    boxes.forEach(box => {
      const kind = box.getAttribute('data-so-feed');
      const key = kind === 'popweekend' ? 'us' : fullKey;   // the same nationwide list wherever the visitor is
      if (loaded[kind] === key) return;
      loaded[kind] = key;
      // The weekend row needs a location (it is the "near" search for this weekend); without one it stays hidden.
      if (kind === 'weekend' && key === 'us') { box.hidden = true; return; }
      const qs = (kind === 'popweekend' ? 'kind=popweekend' : kind === 'weekend' ? 'kind=near&when=weekend&cat=all' : 'kind=' + kind) + (key !== 'us' ? '&lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) : '');
      fetch('/ajax/get-home-feed.php?' + qs)
        .then(r => r.json())
        .then(data => { if (my === token || loaded[kind] === key) render(box, kind, data, label); })
        .catch(() => { if (loaded[kind] === key) { loaded[kind] = ''; box.hidden = true; } });
    });
  }

  // The address lookup and the visitor's own choices all go through reloadActiveTab: follow it.
  const original = window.reloadActiveTab;
  window.reloadActiveTab = function (mode, loc) {
    if (mode === 'll' && loc && loc.lat && loc.lng) load(loc.lat, loc.lng);
    else load('', '');
    return typeof original === 'function' ? original.apply(this, arguments) : undefined;
  };
  const lat = typeof getCookie === 'function' ? getCookie('so_lat') : '';
  const lng = typeof getCookie === 'function' ? getCookie('so_lng') : '';
  if (lat && lng) load(lat, lng); else load('', '');
});


/* =====================================================
    "Read more": the long SEO text at the bottom of the page is clamped to a few lines until the visitor asks for the rest.
    The full text stays in the HTML (crawlers and screen readers get all of it); without JavaScript nothing is clamped.
===================================================== */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-so-readmore]').forEach(function (box, i) {
    const inner = box.querySelector('.so-seo-copy__inner');
    if (!inner) return;
    if (!inner.id) inner.id = 'soReadmoreBody' + i;
    box.classList.add('is-clamped');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'so-readmore__btn';
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', inner.id);
    btn.textContent = 'Read more';
    btn.addEventListener('click', function () {
      const open = box.classList.toggle('is-clamped') === false;
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.textContent = open ? 'Show less' : 'Read more';
      if (!open) box.scrollIntoView({ block: 'start' });
    });
    box.querySelector('.container').appendChild(btn);
  });
});
