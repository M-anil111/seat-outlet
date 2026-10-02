/* Escape a value for HTML text and attributes: names come from the ticket API and are put into innerHTML templates below. */
function soEsc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
$('.custom-slider').on('setPosition', function(){
  	equalHeightSlider('custom-slider', 'event-card');
});

$('.venue-slider').on('setPosition', function(){
	equalHeightSlider('venue-slider', 'venue-card');
});

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
      let html = '';

      data.forEach((event, index) => {
        const loadingType = index < 2 ? 'eager' : 'lazy';
        const fetchPriority = index < 2 ? 'high' : 'low';
        const eSlug = normalizeKey(event.name) + '-' + event.id;
    
        html += `
          <a href="/event/${eSlug}" class="team-link">
            <article class="event-card" data-event-index="${index}">
              <div class="event-card__img">
                <img
                  src="${escapeHtml(event.placeholder)}"
                  alt="${escapeHtml(event.name)}"
                  class="img-fluid event-dynamic-image blur-image"
                  data-event="${encodeURIComponent(event.name)}"
                  data-artist="${encodeURIComponent(event.performer || '')}"
                  data-tab="${encodeURIComponent(event.tab)}"
                  data-category='${escapeHtml(JSON.stringify(event.defaultCategory || {}))}'
                  loading="${loadingType}"
                  fetchpriority="${fetchPriority}"
                  width="278"
                  height="200"
                >
              </div>
              <div class="event-card__body">
                <h3 class="event-card__title venu-name-hide">${escapeHtml(event.name)}</h3>
                <div class="mb-1">
                  <span class="venu-date">${escapeHtml(event.date)}</span>
                  <span class="venu-name">${escapeHtml(event.venue)} - ${escapeHtml(event.loc)}</span>
                </div>
                ${event.price ? `<p class="event-card__price mb-0">from <strong>${escapeHtml(event.price)}</strong></p>` : ''}
              </div>
            </article>
          </a>
        `;
      });
    
      return html;
    }

    function generateEventSkeleton(count = 4) {
      let html = '';
  
      for (let i = 0; i < count; i++) {
        html += `
          <a href="javascript:void(0)" class="team-link skeleton-link">
            <article class="event-card skeleton-card">
              <div class="event-card__img skeleton-img"></div>
              <div class="event-card__body">
                <div class="skeleton-line skeleton-title"></div>
                <div class="skeleton-meta">
                  <span class="skeleton-line skeleton-date"></span>
                  <span class="dot"></span>
                  <span class="skeleton-line skeleton-venue"></span>
                </div>
                <div class="skeleton-line skeleton-price"></div>
              </div>
            </article>
          </a>
        `;
      }
  
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
  
        loadImagesOneByOne(container, loadToken);
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
        locationText.innerHTML = savedLabel + ' <i class="bi bi-chevron-down"></i>';
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
              locationText.innerHTML = so_cs + ' <i class="bi bi-chevron-down"></i>';
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

        fetch(`/ajax/get_ip_details.php`)
          .then(res => res.json())
          .then(data => {
            // Same failure mode as main.js's version of this lookup: an
            // empty response (rate limited, IP not resolvable, timeout)
            // must not overwrite the location field with a literal
            // "undefined, undefined".
            if (!data || !data.city || !data.state) {
              return;
            }

            setCookie('so_lat', encodeURIComponent(data.lat));
            setCookie('so_lng', encodeURIComponent(data.lng));
            setCookie('so_label', encodeURIComponent(data.city + ', ' + data.state));
            if (cityInput) cityInput.value = '';
            
            setTimeout(() => {
              const locText = document.getElementById('locationSelectorText');
              if (locText) locText.innerHTML = data.city + ', ' + data.state + ' <i class="bi bi-chevron-down"></i>';
                          
              if (typeof reloadActiveTab === 'function') {
                reloadActiveTab('ll', { lat: data.lat, lng: data.lng });
              }
              if (typeof loadNearbyVenues === 'function') {
                loadNearbyVenues();
              }             
            }, 200);
            
          })
        .catch(() => {
          
        });
        
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

      const loadToken = ++venueLoadToken;

      const solt = getCookie('so_lat') || '';
      const solg = getCookie('so_lng') || '';

      const ajaxUrlVenue =
    `/ajax/get-nearby-venues.php?solt=${encodeURIComponent(solt)}&solg=${encodeURIComponent(solg)}`;

    container.innerHTML = buildVenueSkeleton(4);
    initVenueSlider('skeleton');
  
      fetch(ajaxUrlVenue)
      .then(res => res.json())
        .then(data => {

          if (loadToken !== venueLoadToken) return;

          const venueTitle = document.querySelector('.venue-section h2');  
          if (venueTitle) {
              const solabel = getCookie('so_label') || '';
              venueTitle.textContent = solabel
                ? `Top Venues Near ${solabel}`
                : 'Top Venues';
          }
          
          if (!data || !data.length) {
            container.innerHTML = '<p>No nearby venues found</p>';
            return;
          }

          const $vslider = $('.venue-slider');
          if ($vslider.hasClass('slick-initialized')) {
            $vslider.slick('unslick');
          }

          let html = '';
  
          data.forEach((venue, index) => {
            const loadingType = index === 0 ? 'eager' : 'lazy';
            const fetchPriority = index === 0 ? 'high' : 'low';
        
            html += `
              <a href="/venue/${soEsc(venue.slug)}" class="team-link">
                <div class="card venue-card">
                  <div class="venue-img">
                    <img
                      src="${soEsc(venue.image)}"
                      alt="${soEsc(venue.name)}"
                      class="img-fluid venue-dynamic-image blur-image"
                      data-venue="${encodeURIComponent(venue.name)}"
                      loading="${loadingType}"
                      fetchpriority="${fetchPriority}"
                    >
                  </div>
                  <div class="venue-content text-center">
                    <h5 class="venue-title">${soEsc(venue.name)}</h5>
                    <p class="venue-location mb-0">
                      ${soEsc(venue.city)}, ${soEsc(venue.state)}
                    </p>
                  </div>
                </div>
              </a>
            `;
        });
  
          container.innerHTML = html;          
          setTimeout(() => {
            initVenueSlider();
          }, 50);
          
          loadVenueImagesOneByOne(container, loadToken);

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
          autoplay: loader === 'skeleton' ? false : true,
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
      })
      .catch(err => {
        console.error(err);
        showError('concerts-list');
        showError('sports-list');
        showError('theater-list');
      });
  }

  // -----------------------
  // Render List
  // -----------------------
  function renderList(list, elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
  
    if (!list || list.length === 0) {
      el.innerHTML = '<li>No data available</li>';
      return;
    }
  
    const html = list.map(item => `
      <li>
        <a href="/artist/${soEsc(item.slug)}">
          ${soEsc(item.name)}
        </a>
      </li>
    `).join('');
  
    // ✅ Single DOM write (no multiple reflows)
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
    Newsletter Form Start
===================================================== */

let recaptchaLoaded = false;

function loadRecaptcha(callback) {
  if (recaptchaLoaded) {
    callback();
    return;
  }

  const script = document.createElement("script");
  script.src = "https://www.google.com/recaptcha/api.js?render=" + RECAPTCHA_SITE_KEY;
  script.async = true;
  script.defer = true;

  script.onload = function () {
    recaptchaLoaded = true;
    callback();
  };

  document.body.appendChild(script);
}

document.getElementById("newsletterForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const form = this;
  const msg = document.getElementById("form_error");
  const btn = form.querySelector("button");

  msg.classList.add("d-none");
  btn.disabled = true;

  loadRecaptcha(function () {

    grecaptcha.ready(function() {
      grecaptcha.execute(RECAPTCHA_SITE_KEY, { action: "newsletter" })
      .then(function(token) {

        document.getElementById("recaptchaToken").value = token;

        const formData = new FormData(form);

        fetch("ajax/check-email.php", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {

          btn.disabled = false;

          if (data.status === "duplicate") {
            msg.innerHTML = "This email is already subscribed.";
            msg.classList.remove("d-none");
          } 
          else if (data.status === "recaptcha") {
            msg.innerHTML = "reCAPTCHA failed. Try again.";
            msg.classList.remove("d-none");
          } 
          else if (data.status === "success") {
            msg.classList.add("d-none");
            form.submit();
          }

        })
        .catch(() => {
          btn.disabled = false;
          msg.innerHTML = "Server error.";
          msg.classList.remove("d-none");
        });

      });
    });

  });

});
/* =====================================================
    Newsletter Form End
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
    const badge = kind === 'lastminute' ? whenBadge(ev.iso) : '';
    const eager = i < 2 ? 'eager' : 'lazy';
    return '<a class="so-feed-card" href="/event/' + slug(ev.name) + '-' + ev.id + '">' +
      '<div class="so-feed-card__img">' +
        '<img src="' + esc(ev.placeholder) + '" alt="' + esc(ev.name) + '" class="event-dynamic-image blur-image" width="260" height="260" loading="' + eager + '"' +
        ' data-event="' + encodeURIComponent(ev.name) + '" data-artist="' + encodeURIComponent(ev.performer || '') + '"' +
        ' data-venue="' + encodeURIComponent(ev.venue || '') + '" data-tab="' + encodeURIComponent(ev.tab) + '"' +
        " data-category='" + esc(JSON.stringify(ev.defaultCategory || {})) + "'>" +
        (badge ? '<span class="so-feed-card__badge">' + esc(badge) + '</span>' : '') +
      '</div>' +
      '<h3 class="so-feed-card__name">' + esc(ev.name) + '</h3>' +
      '<p class="so-feed-card__meta">' + esc(ev.date) + '</p>' +
      '<p class="so-feed-card__meta">' + esc(ev.venue) + (ev.loc ? ' - ' + esc(ev.loc) : '') + '</p>' +
      (ev.price ? '<p class="so-feed-card__price">From <strong>' + esc(ev.price) + '</strong></p>' : '') +
    '</a>';
  }

  function render(box, kind, data, label) {
    const track = box.querySelector('[data-so-feed-track]');
    const title = box.querySelector('.so-feed__title');
    const sub = box.querySelector('[data-so-feed-sub]');
    const events = (data && data.events) || [];
    if (!events.length) { box.hidden = true; return; }
    box.hidden = false;
    const near = data.scope === 'near';
    const where = near && label ? ' near ' + label : (near ? ' near you' : '');
    if (title) {
      title.textContent = kind === 'lastminute'
        ? 'Last-minute tickets' + where
        : 'Trending events' + where;
    }
    if (sub) {
      sub.textContent = kind === 'lastminute'
        ? (near ? 'Happening in the next 7 days within 50 miles' : 'Happening in the next 7 days')
        : (near ? 'Popular within 50 miles of you' : 'What fans are buying right now');
    }
    track.innerHTML = events.map((ev, i) => card(ev, i, kind)).join('');
    track.scrollLeft = 0;
    if (window.soBatchLoadImages) window.soBatchLoadImages(track, '.event-dynamic-image', () => true);
  }

  function load(lat, lng) {
    const key = (lat && lng) ? (Number(lat).toFixed(2) + ',' + Number(lng).toFixed(2)) : 'us';
    const label = (typeof getCookie === 'function' ? getCookie('so_label') : '') || '';
    const my = ++token;
    boxes.forEach(box => {
      const kind = box.getAttribute('data-so-feed');
      if (loaded[kind] === key) return;
      loaded[kind] = key;
      const qs = 'kind=' + kind + (key !== 'us' ? '&lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) : '');
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
