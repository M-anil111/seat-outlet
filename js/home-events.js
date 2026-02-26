(function () {

    document.addEventListener('DOMContentLoaded', function () {
  
      /* =========================
         SESSION CACHE
      ========================== */
  
      function fetchWithSessionCache(url, key, ttl = 600000) {
  
        const cached = sessionStorage.getItem(key);
  
        if (cached) {
          const parsed = JSON.parse(cached);
          if (Date.now() - parsed.time < ttl) {
            return Promise.resolve(parsed.data);
          }
        }
  
        return fetch(url)
          .then(res => res.json())
          .then(data => {
            sessionStorage.setItem(key, JSON.stringify({
              time: Date.now(),
              data: data
            }));
            return data;
          });
      }
  
      /* =========================
         BUILD CARDS
      ========================== */
  
      function buildCards(data) {
  
        let html = '';
  
        data.forEach(event => {
  
          const date = event.date ? new Date(event.date) : null;
          const formattedDate = date
            ? date.toLocaleDateString('en-US', { month: 'short', day: '2-digit' })
            : '';
  
          html += `
            <a href="/event.php?id=${event.id}" class="team-link px-3">
              <article class="event-card">
                <div class="event-card__img" style="background-image:url('${event.image}')"></div>
                <div class="event-card__body">
                  <h3 class="event-card__title venu-name-hide">${event.name}</h3>
                  <div class="mb-1">
                    <span class="venu-date">${formattedDate}</span>
                    <span class="venu-name">${event.venue}</span>
                  </div>
                  ${event.price ? `<p class="event-card__price mb-0">from <strong>${event.price}</strong></p>` : ''}
                </div>
              </article>
            </a>
          `;
        });
  
        return html;
      }
  
      /* =========================
         SLICK INIT
      ========================== */
  
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
          autoplay: true,
          dots: false,
          responsive: [
            { breakpoint: 992, settings: { slidesToShow: 3 } },
            { breakpoint: 768, settings: { slidesToShow: 2 } },
            { breakpoint: 576, settings: { slidesToShow: 1 } }
          ]
        });
      }
  
      /* =========================
         LOAD CATEGORY
      ========================== */
  
      function loadLocationCategory(tabId, type, loc1, loc2) {
  
        return new Promise((resolve) => {
  
          const selector  = '#' + tabId + ' .custom-slider';
          const container = document.querySelector(selector);
  
          if (!container) return resolve();
  
          const cacheKey = `home_${tabId}_${type}_${loc1}_${loc2}`;
          const url = `./ajax/get-location-category-events.php?tab=${encodeURIComponent(tabId)}&type=${encodeURIComponent(type)}&loc1=${encodeURIComponent(loc1)}&loc2=${encodeURIComponent(loc2)}`;
  
          container.innerHTML = '<div class="loader"></div>';
  
          fetchWithSessionCache(url, cacheKey)
            .then(data => {
  
              if (!data || !data.length) {
                container.innerHTML = '<p class="text-center">No events found</p>';
                return resolve();
              }
  
              const $slider = $(selector);
              if ($slider.hasClass('slick-initialized')) {
                $slider.slick('unslick');
              }
  
              container.innerHTML = buildCards(data);
  
              setTimeout(() => {
                initSlider(selector);
                resolve();
              }, 50);
            })
            .catch(() => {
              container.innerHTML = '<p class="text-center">Error loading events</p>';
              resolve();
            });
        });
      }
  
      /* =========================
         TAB SWITCH HANDLER
      ========================== */
  
      function getActiveTabId() {
        const activePane = document.querySelector('.tab-pane.show.active');
        return activePane ? activePane.id : 'concerts';
      }
  
      function detectLocationMode() {
  
        function getCookie(name) {
          const v = document.cookie.split('; ').find(row => row.startsWith(name + '='));
          return v ? decodeURIComponent(v.split('=')[1]) : '';
        }
  
        const lat   = getCookie('so_lat');
        const lng   = getCookie('so_lng');
        const city  = getCookie('so_city');
        const state = getCookie('so_state');
  
        if (lat && lng) return { mode: 'll', data: { lat, lng } };
        if (city && state) return { mode: 'cs', data: { city, state } };
  
        return { mode: '', data: {} };
      }
  
      function reloadActiveTab() {
  
        const tabId = getActiveTabId();
        const { mode, data } = detectLocationMode();
  
        if (mode === 'll') {
          return loadLocationCategory(tabId, 'll', data.lat, data.lng);
        }
  
        if (mode === 'cs') {
          return loadLocationCategory(tabId, 'cs', data.city, data.state);
        }
  
        return loadLocationCategory(tabId, '', '', '');
      }
  
      /* =========================
         INITIAL LOAD
      ========================== */
  
      reloadActiveTab();
  
      const pills = document.querySelectorAll('button[data-bs-toggle="pill"]');
  
      pills.forEach(pill => {
        pill.addEventListener('shown.bs.tab', function () {
          reloadActiveTab().then(() => {
            setTimeout(() => {
              $('.slick-slider').slick('setPosition');
            }, 50);
          });
        });
      });
  
    });
  
  })();