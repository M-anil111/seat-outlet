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
    
        html += `
          <a href="/event.php?id=${encodeURIComponent(event.id)}" class="team-link">
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
    
    async function fetchImageSequentially(img, loadToken) {
      if (!img || loadToken !== currentLoadToken) return;
  
      const eventName = img.dataset.event ? decodeURIComponent(img.dataset.event) : '';
      const artist = img.dataset.artist ? decodeURIComponent(img.dataset.artist) : '';
      const tab = img.dataset.tab ? decodeURIComponent(img.dataset.tab) : '';
      const category = img.dataset.category || '{}';
  
      const url =
        `/ajax/get-image.php?event=${encodeURIComponent(eventName)}` +
        `&artist=${encodeURIComponent(artist)}` +
        `&tab=${encodeURIComponent(tab)}` +
        `&category=${encodeURIComponent(category)}`;
  
        try {
          const res = await fetch(url);
          const data = await res.json();
        
          if (loadToken !== currentLoadToken) return;
        
          if (data && data.image) {
            const tempImg = new Image();
        
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
        
            tempImg.onload = function () {
        
              img.src = data.image;
        
              requestAnimationFrame(() => {
                img.style.opacity = '1';
                img.classList.add('loaded');
              });
        
            };
        
            tempImg.src = data.image;
          }
        
        } catch (err) {
          console.error('Image load failed:', eventName, err);
        }
    }

    async function loadImagesOneByOne(container, loadToken) {
      const images = container.querySelectorAll('.event-dynamic-image');
  
      for (const img of images) {
        if (loadToken !== currentLoadToken) break;
        await fetchImageSequentially(img, loadToken);
      }
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
        container.innerHTML = '<p>Error loading events</p>';
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

  async function fetchVenueImageSequentially(img, loadToken) {
    if (!img || loadToken !== venueLoadToken) return;
  
    const venueName = img.dataset.venue
      ? decodeURIComponent(img.dataset.venue)
      : '';
  
    if (!venueName) return;
  
    const url = `/ajax/get-image.php?venue=${encodeURIComponent(venueName)}`;
  
    try {
      const res = await fetch(url);
      const data = await res.json();
  
      if (loadToken !== venueLoadToken) return;
  
      if (data && data.image) {
        const tempImg = new Image();
  
        tempImg.onload = function () {
          img.src = data.image;
  
          setTimeout(() => {
            img.classList.add('loaded');
          }, 50);
        };
  
        tempImg.src = data.image;
      }
  
    } catch (err) {
      console.error('Venue image load failed:', venueName, err);
    }
  }

  async function loadVenueImagesOneByOne(container, loadToken) {
    const images = container.querySelectorAll('.venue-dynamic-image');
  
    for (const img of images) {
      if (loadToken !== venueLoadToken) break;
      await fetchVenueImageSequentially(img, loadToken);
    }
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
              <a href="/venue/${venue.slug}" class="team-link">
                <div class="card venue-card">
                  <div class="venue-img">
                    <img
                      src="${venue.image}"
                      alt="${venue.name}"
                      class="img-fluid venue-dynamic-image blur-image"
                      data-venue="${encodeURIComponent(venue.name)}"
                      loading="${loadingType}"
                      fetchpriority="${fetchPriority}"
                    >
                  </div>
                  <div class="venue-content text-center">
                    <h5 class="venue-title">${venue.name}</h5>
                    <p class="venue-location mb-0">
                      ${venue.city}, ${venue.state}
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
          container.innerHTML = '<p>Error loading venues</p>';
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
    fetch('/cache/top_performers.json', { cache: "force-cache" })
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
        <a href="/artist/${item.slug}">
          ${item.name}
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