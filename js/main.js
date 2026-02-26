/* =====================================================
   SAVE LOCATION
===================================================== */

if (navigator.geolocation) {

  navigator.geolocation.getCurrentPosition(
      function(position) {

          fetch('./ajax/save-location.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'same-origin',
              body: JSON.stringify({
                  lat: position.coords.latitude,
                  lng: position.coords.longitude
              })
          })
          .then(res => res.text())
          .then(data => console.log('Server response:', data))
          .catch(err => console.error('Fetch error:', err));

      },
      function(error) {
          console.error('Geolocation error:', error.message);
      },
      {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
      }
  );
}

/* =====================================================
   SAVE LOCATION End
===================================================== */

/* =====================================================
   EVENTS Section
===================================================== */

(function () {
    document.addEventListener('DOMContentLoaded', function () {

         
      // ---------------------------
      // Elements
      // ---------------------------
      const cityInput = document.getElementById('cityLocationInput');
      const dd = document.getElementById('cityLocationDd');
      const locationText = document.getElementById('locationSelectorText');
      const useCurrentLocationBtn = document.getElementById('useCurrentLocationCity');
  
      // Optional: if you add ids as suggested
      const locationToggleBtn = document.getElementById('locationToggleBtn');
      const locationPanel = document.getElementById('locationPanel');
      const locationClearBtn = document.getElementById('locationClearBtn');
  
      // ---------------------------
      // Helpers
      // ---------------------------
      function getCookie(name) {
        const v = document.cookie.split('; ').find(row => row.startsWith(name + '='));
        return v ? decodeURIComponent(v.split('=')[1]) : '';
      }
  
      function setCookie(name, value) {
        document.cookie = name + '=' + value + ';path=/';
      }
  
      function escapeHtml(str) {
        return String(str)
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#039;');
      }
  
      function debounce(fn, wait) {
        let t;
        return function (...args) {
          clearTimeout(t);
          t = setTimeout(() => fn.apply(this, args), wait);
        };
      }
  
      function openDd() {
        if (!dd) return;
        dd.style.display = 'block';
        dd.classList.add('show');
      }
  
      function closeDd() {
        if (!dd) return;
        dd.style.display = 'none';
        dd.classList.remove('show');
        dd.innerHTML = '';
      }
  
      function getActiveTabId() {
        const activePane = document.querySelector('.tab-pane.show.active');
        return activePane ? activePane.id : 'concerts';
      }
  
      // ---------------------------
      // Slick / Cards
      // ---------------------------
      function buildCards(data) {
        let html = '';
  
        data.forEach(event => {
          const date = new Date(event.date);
          const formattedDate = date.toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit'
          });
  
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
  
      let locationFetchController = null;

    function loadLocationCategory(tabId, type, loc1, loc2) {

        return new Promise((resolve) => {
    
            const selector = '#' + tabId + ' .custom-slider';
            const container = document.querySelector(selector);
            if (!container) return resolve();
    
            // Cancel previous request
            if (locationFetchController) {
                locationFetchController.abort();
            }
    
            locationFetchController = new AbortController();
    
            container.innerHTML = '<div class="loader"></div>';
    
            const url = `./ajax/get-location-category-events.php?tab=${encodeURIComponent(tabId)}&type=${encodeURIComponent(type)}&loc1=${encodeURIComponent(loc1)}&loc2=${encodeURIComponent(loc2)}`;
    
            fetch(url, { signal: locationFetchController.signal })
                .then(res => res.json())
                .then(data => {
    
                    // If aborted, do nothing
                    if (locationFetchController.signal.aborted) return resolve();
    
                    if (!data || !data.length) {
                        container.innerHTML = '<p>No events found</p>';
                        return resolve();
                    }
    
                    // Destroy previous slick BEFORE replacing content
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
                .catch((err) => {
    
                    if (err.name === 'AbortError') return resolve();
    
                    container.innerHTML = '<p>Error loading events</p>';
                    resolve();
                });
        });
    }
  
      function reloadActiveTab(mode = '', loc = {}) {
        
        const tabId = getActiveTabId();
    
        if (mode === 'll' && loc.lat && loc.lng) {
            return loadLocationCategory(tabId, 'll', loc.lat, loc.lng);
        }
    
        if (mode === 'cs' && loc.city && loc.state) {
            return loadLocationCategory(tabId, 'cs', loc.city, loc.state);
        }
    
        return loadLocationCategory(tabId, '', '', '');
    }
  
      
  
      // ---------------------------
      // Bootstrap tab switching
      // ---------------------------
      function detectLocationMode() {

        const lat   = getCookie('so_lat');
        const lng   = getCookie('so_lng');
        const city  = getCookie('so_city');
        const state = getCookie('so_state');
      
        if (lat && lng) {
          return { mode: 'll', data: { lat, lng } };
        }
      
        if (city && state) {
          return { mode: 'cs', data: { city, state } };
        }
      
        return { mode: '', data: {} };
      }

      (function init() {
          const savedLabel = getCookie('so_label');
          if (savedLabel && locationText) {
            locationText.textContent = savedLabel;
          }
          const { mode, data } = detectLocationMode();
          reloadActiveTab(mode, data).then(() => {
            setTimeout(() => {
              $('.slick-slider').slick('setPosition');
            }, 50);
          });
      })();
                  

      const pills = document.querySelectorAll('button[data-bs-toggle="pill"]');
      pills.forEach(pill => {

        pill.addEventListener('shown.bs.tab', function () {
      
          const { mode, data } = detectLocationMode();
      
          reloadActiveTab(mode, data).then(() => {
            setTimeout(() => {
              $('.slick-slider').slick('setPosition');
            }, 50);
          });
      
        });
      
      });
    
  
      // ---------------------------
      // City search dropdown
      // ---------------------------
      const MIN_CHARS = 3;
      let controller = null;
  
      async function searchCities(q) {
        if (!dd) return;
  
        if (controller) controller.abort();
        controller = new AbortController();
  
        dd.innerHTML = `<div class="dropdown-item text-muted">Searching...</div>`;
        openDd();
  
        try {
          const res = await fetch(`./ajax/city-suggest.php?q=${encodeURIComponent(q)}`, {
            signal: controller.signal
          });
  
          const data = await res.json();
  
          if (!Array.isArray(data) || data.length === 0) {
            dd.innerHTML = `<div class="dropdown-item text-muted">No results</div>`;
            openDd();
            return;
          }
  
          dd.innerHTML = data.map(item => {
            const label =
              item.label ||
              `${item.city || ''}${item.state ? ', ' + item.state : ''}`;
  
            return `
              <button type="button"
                class="dropdown-item city-dd-item"
                data-city="${item.city ?? ''}"
                data-state="${item.state ?? ''}"
                data-label="${escapeHtml(label)}">
                ${escapeHtml(label)}
              </button>
            `;
          }).join('');
  
          openDd();
        } catch (e) {
          if (e.name === 'AbortError') return;
          dd.innerHTML = `<div class="dropdown-item text-danger">Error loading results</div>`;
          openDd();
        }
      }
  
      if (cityInput) {
        cityInput.addEventListener('keyup', debounce(function () {
          const q = cityInput.value.trim();
          if (q.length < MIN_CHARS) {
            closeDd();
            return;
          }
          searchCities(q);
        }, 250));
  
        cityInput.addEventListener('focus', function () {
          if (dd && dd.innerHTML.trim() !== '') openDd();
        });
      }
  
      // Select city from dropdown
      document.addEventListener('click', function (e) {
        const btn = e.target.closest('.city-dd-item');
        if (!btn) return;
        
        const label = btn.getAttribute('data-label') || '';
        const city = btn.getAttribute('data-city') || '';
        const state = btn.getAttribute('data-state') || '';
        if (!city || !state) return;
  
        if (locationText) locationText.textContent = label;
        if (cityInput) cityInput.value = label;
  
        closeDd();
  
        if (locationPanel) locationPanel.classList.remove('show');
        setCookie('so_city', city);
        setCookie('so_state', state);
        setCookie('so_label', city + ', ' + state);
        reloadActiveTab('cs', {city, state});
      });
  
      // Close dropdown on outside click
      document.addEventListener('click', function (e) {
        if (!dd || !cityInput) return;
        if (e.target === cityInput || dd.contains(e.target)) return;
        closeDd();
      });
  
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDd();
      });
  
      // ---------------------------
      // Current location button
      // ---------------------------
      if (useCurrentLocationBtn) {
        useCurrentLocationBtn.addEventListener('click', function () {
          if (!navigator.geolocation) {
            alert('Geolocation not supported');
            return;
          }
  
          useCurrentLocationBtn.classList.add('loading');
  
          navigator.geolocation.getCurrentPosition(
            function (position) {
              const lat = String(position.coords.latitude);
              const lng = String(position.coords.longitude);
  
              setCookie('so_lat', lat);
              setCookie('so_lng', lng);
              setCookie('so_label', encodeURIComponent('Current Location'));
  
              if (locationText) locationText.textContent = 'Current Location';
              if (cityInput) cityInput.value = 'Current Location';
  
              if (locationPanel) locationPanel.classList.remove('show');
  
              useCurrentLocationBtn.classList.remove('loading');
              reloadActiveTab('ll', {lat, lng});
            },
            function () {
              alert('Unable to get your location');
              useCurrentLocationBtn.classList.remove('loading');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
          );
        });
      }
  
      // ---------------------------
      // Optional: panel toggle + clear
      // ---------------------------
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
          setCookie('so_lat', '');
          setCookie('so_lng', '');
          setCookie('so_city', '');
          setCookie('so_state', '');
          setCookie('so_label', '');
  
          if (locationText) locationText.textContent = 'Select your location';
          if (cityInput) cityInput.value = '';
          closeDd();
  
          reloadActiveTab('', {});
        });
      }
    });
  })();

/* =====================================================
   EVENTS End
===================================================== */

/* =====================================================
   TEAMS
===================================================== */

document.addEventListener('DOMContentLoaded', function () {

  const container = document.getElementById('sportsTabContent');

  function loadTeams(slug) {

      container.innerHTML = '<div class="loader"></div>';
      
      fetch(`./ajax/get-nearby-teams.php?slug=${slug}`)
          .then(res => res.json())
          .then(data => {

              if (!data.length) {
                  container.innerHTML = '<p>No teams available.</p>';
                  return;
              }

              let html = `<div class="tab-pane show active" id="${slug}" role="tabpanel"><div class="team-slider new-slider px-4">`;

              data.forEach(team => {
                  html += `
                      <a href="/artist/${team.slug}" class="team-link">
                        <div class="team-card">
                          <div class="team-icon bg-primary">${getTeamIconJS(slug)}</div>
                          <span>${team.name}</span>
                        </div>                              
                      </a>                      
                  `;
              });

              html += `</div></div>`;

              container.innerHTML = html;

              initTeamSlider();
          });
  }

  // Default load
  const activeBtn = document.querySelector('.sport-cat.active');
  if (activeBtn) {
      loadTeams(activeBtn.dataset.slug);
  }

  // On tab switch
  const teams = document.querySelectorAll('button.sport-cat');
  teams.forEach(pillTeam => {
    pillTeam.addEventListener('shown.bs.tab', function () {
      loadTeams(this.dataset.slug);
    });  
  });
 

});

function initTeamSlider() {

    const $slider = $('.team-slider');

    if ($slider.hasClass('slick-initialized')) {
        $slider.slick('unslick');
    }

    $slider.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true,
        dots: false,
        infinite: false,
        responsive: [
            { breakpoint: 992, settings: { slidesToShow: 3 } },
            { breakpoint: 768, settings: { slidesToShow: 2 } },
            { breakpoint: 576, settings: { slidesToShow: 1 } }
        ]
    });
}

function getTeamIconJS(league) {

  const icons = {
      NFL: '🏈',
      NBA: '🏀',
      MLB: '⚾',
      NHL: '🏒',
      MLS: '⚽'
  };

  return icons[league?.toUpperCase()] || '🏟️';
}

/* =====================================================
   TEAMS End
===================================================== */

/* =====================================================
   CITIES
===================================================== */

function loadBrowseCities() {

  const wrapper = document.getElementById('browseCitiesWrapper');
  if (!wrapper) return;

  wrapper.innerHTML = '<div class="loader"></div>';

  fetch('./ajax/get-home-cities.php')
      .then(res => res.json())
      .then(data => {
          wrapper.innerHTML = buildCityHTML(data ?? {});
      })
      .catch(() => {
          wrapper.innerHTML = '<p>Error loading categories</p>';
      });
}

function buildCityHTML(data = {}) {
    return `
        ${buildCities(data.cities ?? [])}
    `;
}

function buildCities(cities) {

  if (!Array.isArray(cities) || cities.length === 0) return '';

  let list = ``;
  cities.forEach(city => {
      list += `<div class="col-auto"><a href="#" class="city-pill">${city.name}, ${city.state}</a></div>`;
  });
 

  return `${list}`;
}

loadBrowseCities();

/* =====================================================
   CITIES End
===================================================== */

/* =====================================================
   VENUES
===================================================== */

function loadNearbyVenues() {

    return new Promise((resolve) => {

        const container = document.querySelector('.venue-slider');
        if (!container) {
            resolve();
            return;
        }

        fetch('./ajax/get-nearby-venues.php')
            .then(res => res.json())
            .then(data => {
                
                if (!data || !data.length) {
                    container.innerHTML = '<p>No nearby venues found</p>';
                    resolve();
                    return;
                }

                let html = ``;

                data.forEach(venue => {
                    html += `
                        <a href="/venue/${venue.slug}" class="team-link">
                            <div class="card venue-card">
                                <div class="venue-img">
                                    <img src="${venue.image}" alt="${venue.name}" class="img-fluid">
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
                    resolve();
                }, 50);

            })
            .catch(() => {
                container.innerHTML = '<p>Error loading venues</p>';
                resolve();
            });

    });
}

function initVenueSlider() {

    const $slider = $('.venue-slider');

    if ($slider.hasClass('slick-initialized')) {
        $slider.slick('unslick');
    }

    $slider.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: true,
        autoplay: true,
        dots: false,
        infinite: false,
        responsive: [
            { breakpoint: 992, settings: { slidesToShow: 3 } },
            { breakpoint: 768, settings: { slidesToShow: 2 } },
            { breakpoint: 576, settings: { slidesToShow: 1 } }
        ]
    });
}

loadNearbyVenues();

/* =====================================================
   VENUES End
===================================================== */

/* =====================================================
   HEADER LOCATION FIELD
===================================================== */

const inputHeader = document.getElementById('locationInputHeader');
const resultsHeader = document.getElementById('locationResultsHeader');
const latHeader = document.getElementById('latHeader');
const lngHeader = document.getElementById('lngHeader');

if(inputHeader) {
  inputHeader.addEventListener('click', function () {
      const q = this.value.trim();

      if (q.length === 0) {
          resultsHeader.innerHTML = `
              <div class="current-location">
                  <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2" id="useCurrentLocationHeader"> Current location </span>  
              </div>
          `;
          return;
      }
  });

  inputHeader.addEventListener('keyup', function () {
      const q = this.value.trim();

      if (q.length === 0) {
          resultsHeader.innerHTML = `
              <div class="current-location">
                  <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2" id="useCurrentLocationHeader"> Current location </span>
              </div>
          `;
          return;
      }
      
      if (q.length < 2) {
          resultsHeader.innerHTML = '';
          return;
      }

      fetch(`./ajax/location-search.php?q=${q}`)
          .then(res => res.json())
          .then(data => {
              let html = '<ul>';
              data.forEach(item => {
                  html += `<li class="result-item">
                      ${item.zip ? item.zip + ', ' : ''}${item.city}, ${item.state}
                  </li>`;
              });
              html += '</ul>'
              resultsHeader.innerHTML = html;
          });
  });
}

if(resultsHeader) {
    resultsHeader.addEventListener('click', function (e) {

        if (e.target.id === 'useCurrentLocationHeader') {
            getCurrentLocationHeader();
            return;
        }

        const item = e.target.closest('.result-item');
        if (!item) return;

        inputHeader.value = item.textContent.trim();
		resultsHeader.innerHTML = '';
    });
}

function getCurrentLocationHeader() {

    if (!navigator.geolocation) {
        alert('Geocoding is not supported by your browser');
        return;
    }

    resultsHeader.innerHTML = '';
    inputHeader.value = 'Detecting location...';

    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            latHeader.value = lat;
            lngHeader.value = lng;

            inputHeader.value = 'Current location';
           
        },
        error => {
            inputHeader.value = '';
            alert('Unable to access your location');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000
        }
    );
}

/* =====================================================
   HEADER LOCATION FIELD End
===================================================== */

/* =====================================================
   HEADER DATERANGE FIELD
===================================================== */

function toApiDateHeader(dateObj) {
    if (!dateObj) return null;

    const year  = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day   = String(dateObj.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const dateRangeHeader = document.getElementById('dateRangeHeader');
const datePickerSectionHeader = document.getElementById('datePickerSectionHeader');

let startInputHeader = document.getElementById('startInputHeader');
let endInputHeader = document.getElementById('endInputHeader');

let selectedStart = null;
let selectedEnd = null;

let pickerHeader = flatpickr('#calendarHeader', {
    inline: true,
    minDate: "today",
    mode: 'range',
    showMonths: 2,
    dateFormat: "M d, Y",
    onChange: function(selectedDates) {
        selectedStart = selectedDates[0] || null;
        selectedEnd   = selectedDates[1] || null;

        if (selectedDates.length === 2) {
            const startDate = toApiDateHeader(selectedStart);
            const endDate   = toApiDateHeader(selectedEnd);

            startInputHeader.value = startDate;
            endInputHeader.value = endDate;
        }
    }
});

// Open picker on input click
const dateArrowHeader = document.getElementById('dateArrowHeader');
if(dateRangeHeader) {
    dateRangeHeader.addEventListener('click', (e) => {
        e.stopPropagation();

        const isHidden = datePickerSectionHeader.classList.toggle('opacity-zero');

        if (isHidden) {
            dateArrowHeader.classList.remove('bi-chevron-up');
            dateArrowHeader.classList.add('bi-chevron-down');
        } else {
            dateArrowHeader.classList.remove('bi-chevron-down');
            dateArrowHeader.classList.add('bi-chevron-up');

            setTimeout(() => {
                pickerHeader.redraw();
            }, 10);
        }
    });
}
// Close on outside click
document.addEventListener('click', (e) => {
    if (
        !datePickerSectionHeader.contains(e.target) &&
        e.target !== dateRangeHeader
    ) {
        datePickerSectionHeader.classList.add('opacity-zero');
    }
});

const resetDatesHeader = document.getElementById('resetDatesHeader');
if(resetDatesHeader) {
    resetDatesHeader.addEventListener('click', () => {
        pickerHeader.clear();
        selectedStart = selectedEnd = null;
        startInputHeader.value = '';
        endInputHeader.value = '';
        dateRangeHeader.value = '';
    });
}


const cancelDatesHeader = document.getElementById('cancelDatesHeader');
if(cancelDatesHeader) {
    cancelDatesHeader.addEventListener('click', () => {
        datePickerSectionHeader.classList.add('opacity-zero');
        startInputHeader.value = '';
        endInputHeader.value = '';
    });
}

const applyDatesHeader = document.getElementById('applyDatesHeader');
if(applyDatesHeader) {
    applyDatesHeader.addEventListener('click', () => {
        if (!selectedStart || !selectedEnd) return;

        const startText = flatpickr.formatDate(selectedStart, 'm/d/Y');
        const endText = flatpickr.formatDate(selectedEnd, 'm/d/Y');

        dateRangeHeader.value = startText + ' - ' + endText;

        datePickerSectionHeader.classList.add('opacity-zero');
    });
}

/* =====================================================
   HEADER DATERANGE FIELD End
===================================================== */

/* =====================================================
   HEADER KEYWORD FIELD
===================================================== */

const keywordHeader = document.getElementById('keywordHeader');
const keywordResultsHeader = document.getElementById('keywordResultsHeader');
const keywordType = document.getElementById('keywordType');
const keywordId = document.getElementById('keywordId');

if(keywordHeader) {	

	keywordHeader.addEventListener('keyup', function () {
      	const q = this.value.trim();

      	if (q.length < 2) {
			keywordResultsHeader.innerHTML = '';
          	return;
      	}

      	fetch(`./ajax/keyword-search.php?q=${encodeURIComponent(q)}`)
		.then(res => res.json())
		.then(data => {

			let html = '<ul class="search-suggestions">';

			// =====================
			// ARTISTS
			// =====================
			if (data.artists && data.artists.length > 0) {
				html += '<li class="suggestion-label">Artists</li>';
				data.artists.forEach(item => {
					html += `
					<li class="result-item"
						data-type="artist"
						data-id="${item.id}"
						data-slug="${item.slug}">
						${item.name}
					</li>`;
				});
			}

			// =====================
			// EVENTS
			// =====================
			if (data.events && data.events.length > 0) {
				html += '<li class="suggestion-label">Events</li>';
				data.events.forEach(item => {
					html += `
					<li class="result-item"
						data-type="event"
						data-id="${item.id}"
						data-slug="${item.slug}">
						${item.name}
						${item.date ? `<span class="small text-muted">(${item.date})</span>` : ''}
					</li>`;
				});
			}

			// =====================
			// VENUES
			// =====================
			if (data.venues && data.venues.length > 0) {
				html += '<li class="suggestion-label">Venues</li>';

				data.venues.forEach(item => {
					html += `
					<li class="result-item"
						data-type="venue"
						data-id="${item.id}"
						data-slug="${item.slug}">
						${item.name}
						${item.city ? `<span class="small text-muted">(${item.city}${item.state ? ', ' + item.state : ''})</span>` : ''}
					</li>`;
				});
			}

			html += '</ul>';

			keywordResultsHeader.innerHTML = html;
		});
  	});
}

if(keywordResultsHeader) {
    keywordResultsHeader.addEventListener('click', function (e) {

        const item = e.target.closest('.result-item');
        if (!item) return;

        keywordHeader.value = item.textContent.trim();
        keywordType.value = item.dataset.type;
        keywordId.value = item.dataset.id;
		keywordResultsHeader.innerHTML = '';
    });
}

/* =====================================================
   HEADER KEYWORD FIELD End
===================================================== */

/* =====================================================
   ARTIST FILTER
===================================================== */

const input = document.getElementById('locationInput');
const results = document.getElementById('locationResults');

if(input) {
    input.addEventListener('click', function () {
        const q = this.value.trim();

        if (q.length === 0) {
            results.innerHTML = `
                <div class="current-location" id="useCurrentLocation">
                    <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2"> Current location </span>  
                </div>
            `;
            return;
        }
    });

    input.addEventListener('keyup', function () {
        const q = this.value.trim();

        if (q.length === 0) {
            results.innerHTML = `
                <div class="current-location" id="useCurrentLocation">
                    <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2"> Current location </span>
                </div>
            `;
            return;
        }
        
        if (q.length < 2) {
            results.innerHTML = '';
            return;
        }

        fetch(`./ajax/location-search.php?q=${q}`)
            .then(res => res.json())
            .then(data => {
                let html = '<ul>';
                data.forEach(item => {
                    html += `<li class="result-item"
                        data-city="${item.city}"
                        data-state="${item.state}"
                        data-zip="${item.zip ?? ''}">
                        ${item.zip ? item.zip + ', ' : ''}${item.city}, ${item.state}
                    </li>`;
                });
                html += '</ul>'
                results.innerHTML = html;
            });
    });
}

if(results) {
    results.addEventListener('click', function (e) {

        if (e.target.id === 'useCurrentLocation') {
            getCurrentLocation();
            return;
        }

        const item = e.target.closest('.result-item');
        if (!item) return;

        const city  = item.dataset.city;
        const state = item.dataset.state;
        const zip   = item.dataset.zip;
        const cpid  = input.dataset.cpid;
        const dcat  = input.dataset.dcat;

        input.value = zip
            ? `${zip}, ${city}, ${state}`
            : `${city}, ${state}`;

        results.innerHTML = '';

        updateHeading(city, state, zip, dcat);

        updateEventsSection({ city, state, zip, cpid, dcat });   
    });
}

function updateEventsSection(location) {
    
    const params = new URLSearchParams();

    if (location.city)  params.append('city', location.city);
    if (location.state) params.append('state', location.state);
    if (location.zip)   params.append('zip', location.zip);
    if (location.cpid)  params.append('cpid', location.cpid);
    if (location.lat)   params.append('lat', location.lat);
    if (location.lng)   params.append('lng', location.lng);
    if (location.startDate)   params.append('startDate', location.startDate);
    if (location.endDate)   params.append('endDate', location.endDate);

    fetch(`./ajax/load-events.php?${params}`)
        .then(res => res.text())
        .then(html => {
            const list = html.split("|");
            if(list[0] == 'no') {   
                input.value = list[1] + ', ' + list[2];             
                document.getElementById('location-no-results').innerHTML = '<strong>No ' + location.dcat + ' available in your selected area</strong><p>Try changing locations or browse through the available ' + location.dcat + ' below</p>';
            }else{
                document.getElementById('location-no-results').innerHTML = '';
                const temp = document.createElement('div');
                temp.innerHTML = html;
                const count = temp.querySelectorAll('.performer-event-item').length;
                const countmsg = count > 1 ? ' RESULTS' : ' RESULT';
                document.getElementById('results_count').innerHTML = count + countmsg;
                document.getElementById('eventsSection').innerHTML = html;
            }
            if(location.flag !== 'reset') {
                input.disabled = true;   
                const resetBtn = document.getElementById('locationInputReset');
                resetBtn.classList.remove('d-none');
                resetBtn.addEventListener('click', function () {
                    input.disabled = false;
                    input.value = '';
                    this.classList.add('d-none');       
                    document.getElementById('location-no-results').innerHTML = '';
                    document.getElementById('locationHeading').innerHTML = '';
                    updateEventsSection({ cpid: input.dataset.cpid, flag: 'reset' });
                }); 
            }     
        })
    .catch(err => {
        console.error('Failed to load events', err);
    }); 
    
}

function updateHeading(city, state, zip, dcat) {
    const heading = document.getElementById('locationHeading');
    if (!heading) return;
    if(dcat && (city || state || zip)) {
        const capitalized = dcat.charAt(0).toUpperCase() + dcat.slice(1);
        
        if (zip) {        
            heading.textContent = `${capitalized} near ${zip}, ${city}, ${state}`;
        } else if (city && state) {
            heading.textContent = `${capitalized} near ${city}, ${state}`;
        } else {
            heading.textContent = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateHeading();
});

function getCurrentLocation() {

    if (!navigator.geolocation) {
        alert('Geocoding is not supported by your browser');
        return;
    }

    results.innerHTML = '';
    input.value = 'Detecting location...';

    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            input.value = '';

            updateHeading(null, null, null, null);
            updateEventsSection({
                lat,
                lng,
                cpid: input.dataset.cpid,
                dcat: input.dataset.dcat
            });
        },
        error => {
            input.value = '';
            alert('Unable to access your location');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000
        }
    );
}

/* Date Range */

function toApiDate(dateObj) {
  if (!dateObj) return null;

  const year  = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day   = String(dateObj.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

const dateRange = document.getElementById('dateRange');
const datePickerSection = document.getElementById('datePickerSection');

const startInput = document.getElementById('startInput');
const endInput = document.getElementById('endInput');

let selectStart = null;
let selectEnd = null;

let picker = flatpickr('#calendar', {
  inline: true,
  minDate: "today",
  mode: 'range',
  showMonths: 2,
  dateFormat: "M d, Y",
  onChange: function(selectedDates) {
      selectStart = selectedDates[0] || null;
      selectEnd   = selectedDates[1] || null;

      const cpid = input.dataset.cpid;
      const dcat = input.dataset.dcat;

      if (selectedDates.length === 2) {
          const startDate = toApiDate(selectStart);
          const endDate   = toApiDate(selectEnd);

          updateHeading(null, null, null, null);
          updateEventsSection({
              startDate,
              endDate,
              cpid,
              dcat
          });
      }
  }
});

// Open picker on input click
const dateArrow = document.getElementById('dateArrow');
if(dateRange) {
  dateRange.addEventListener('click', (e) => {
      e.stopPropagation();

      const isHidden = datePickerSection.classList.toggle('opacity-zero');

      if (isHidden) {
          dateArrow.classList.remove('bi-chevron-up');
          dateArrow.classList.add('bi-chevron-down');
      } else {
          dateArrow.classList.remove('bi-chevron-down');
          dateArrow.classList.add('bi-chevron-up');

          setTimeout(() => {
              picker.redraw();
          }, 10);
      }
  });
}
// Close on outside click
document.addEventListener('click', (e) => {
  if (
      !datePickerSection.contains(e.target) &&
      e.target !== dateRange
  ) {
      datePickerSection.classList.add('opacity-zero');
  }
});

const resetDates = document.getElementById('resetDates');
if(resetDates) {
  resetDates.addEventListener('click', () => {
      picker.clear();
      selectStart = selectEnd = null;
      startInput.value = '';
      endInput.value = '';
      dateRange.value = '';
  });
}


const cancelDates = document.getElementById('cancelDates');
if(cancelDates) {
  cancelDates.addEventListener('click', () => {
      datePickerSection.classList.add('opacity-zero');
  });
}

const applyDates = document.getElementById('applyDates');
if(applyDates) {
  applyDates.addEventListener('click', () => {
      if (!selectStart || !selectEnd) return;

      const startText = flatpickr.formatDate(selectStart, 'm/d/Y');
      const endText = flatpickr.formatDate(selectEnd, 'm/d/Y');

      dateRange.value = startText + ' - ' + endText;

      const apiStart = flatpickr.formatDate(selectStart, 'Y-m-d');
      const apiEnd = flatpickr.formatDate(selectEnd, 'Y-m-d');

      datePickerSection.classList.add('opacity-zero');
  });
}

/* =====================================================
   ARTIST FILTER End
===================================================== */

/* =====================================================
   ARTIST SUBMENU SCROLL
===================================================== */

function scrollToElement(id) {
  const target = document.getElementById(id);
  if (!target) return;

  const isMobile = window.innerWidth < 992;
  const offset   = isMobile ? 0 : 92; // sticky tabs height

  const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
  const offsetPosition  = elementPosition - offset;

  window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
  });

  // update active tab
  document.querySelectorAll('#artistTabs .nav-link')
      .forEach(btn => btn.classList.remove('active'));

  const activeBtn = document.querySelector(`#artistTabs button[onclick*="${id}"]`);
  if (activeBtn) activeBtn.classList.add('active');
}

/* =====================================================
 ARTIST SUBMENU SCROLL End
===================================================== */

/* =====================================================
 ARTIST PROMOCODES COPY
===================================================== */

document.querySelectorAll(".offer-copy-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const code = btn.dataset.code || btn.previousElementSibling.textContent.trim();
    navigator.clipboard
      .writeText(code)
      .then(() => {
        btn.textContent = "Copied";
        setTimeout(() => (btn.textContent = "Copy"), 1500);
      })
      .catch(() => {
        alert("Unable to copy code. Please copy manually.");
      });
  });
});

/* =====================================================
 ARTIST PROMOCODES COPY End
===================================================== */

/* =====================================================
BACK TO TOP
===================================================== */

const backToTop = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.scrollY > 400) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none';
    }
});

backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

/* =====================================================
BACK TO TOP End
===================================================== */

/* =====================================================
   ARTIST EVENTS LOADMORE
===================================================== */

const loadMoreBtn = document.getElementById('loadMoreBtn');
const backToTopBtn = document.getElementById('backToTopJs');
const eventsSection = document.getElementById('eventsSection');
const progressBar = document.getElementById('progressBar');
const loadedCount = document.getElementById('loadedCount');

if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
        const page        = this.dataset.page;
        const performerId = this.dataset.performer;
        const perPage     = this.dataset.perpage;
        const total     = this.dataset.total;

        this.disabled = true;
        loadMoreBtn.querySelector('#btnSpinner').classList.remove('d-none');
       
        fetch(`./ajax/load-more-events.php?page=${page}&perPage=${perPage}&performerId=${performerId}`)
            .then(res => res.json())
            .then(data => {

                data.events.forEach(event => {
                    eventsSection.insertAdjacentHTML('beforeend', renderEvent(event));
                });

                let loaded = eventsSection.querySelectorAll('.performer-event-item').length;
                const countmsg = loaded === 1 ? ' RESULT' : ' RESULTS';
                document.getElementById('results_count').innerHTML = loaded + countmsg;

                if (data.hasMore) { 
                    loadMoreBtn.dataset.page = data.nextPage;   
                    loadMoreBtn.disabled = false;                 
                    loadMoreBtn.querySelector('#btnSpinner').classList.add('d-none');
                } else {
                    loadMoreBtn.classList.add('d-none');
                    backToTopBtn.classList.remove('d-none');
                }
                loaded = Math.min(loaded, total);
                const percent = (loaded / total) * 100;
                progressBar.style.width = percent + '%';
                loadedCount.textContent = loaded;
            });
    });
}

if (backToTopBtn) { 
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function renderEvent(event) {
    const evtdate = new Date(event.date.date);
    const emonth = evtdate.toLocaleString('en-US', { month: 'short' });
    const edate = evtdate.getDate();
    const eday = evtdate.toLocaleString('en-US', { weekday: 'short' });
    const year = evtdate.getFullYear();
    let y = '';
    if(year > new Date().getFullYear()) { 
        y = '<div class="month">'+year+'</div>';
    }
    const formattedDate = evtdate.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: '2-digit'
    });
    const eperformers = event.performers;
    const names = eperformers.map(performer => performer.name ?? null).filter(Boolean);
    const dataPerformers = names.join('|');
    return `
        <div class="d-flex align-items-center justify-content-between performer-event-item">
            <div class="date-box text-center me-3">
                <div class="month">${emonth.toUpperCase()}</div>
                <div class="day">${edate}</div>
                ${y}
            </div>
            <div class="flex-grow-1 w-50">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold day-weeks">${eday}</span>
                    <span class="dot">·</span>
                    <span class="time-clock">${event.date.text.time}</span>
                    <i class="bi bi-info-circle text-muted icon-i" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" 
                    data-id="${event.id}" data-date="${formattedDate}" data-venue="${event.venue.text.name}" 
                    data-location="${event.city.text.name}, ${event.stateProvince.text.abbr}" data-title="${event.text.name}" data-performers="${dataPerformers}"></i>
                </div>
                <div class="fw-semibold location-venue-name">
                    <a href="#">${event.city.text.name}, ${event.stateProvince.text.abbr}</a> · <a href="#">${event.venue.text.name}</a>
                </div>
                <div class="text-muted small">
                    ${event.text.name}
                </div>
            </div>
            <div class="ms-3">
                <a href="/event.php?id=${event.id}" class="btn btn-primary d-flex align-items-center gap-2">
                    <span class="d-none d-md-inline">
                        Find Tickets
                    </span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    `;
}

/* =====================================================
   ARTIST EVENTS LOADMORE End
===================================================== */

/* =====================================================
   ARTIST EVENTS POPUP
===================================================== */

document.addEventListener('click', function (e) {
  const icon = e.target.closest('[data-bs-toggle="offcanvas"]');
  if (!icon) return;

  const eid = icon.getAttribute('data-id');
  const edate = icon.getAttribute('data-date');
  const evenue = icon.getAttribute('data-venue');
  const elocation = icon.getAttribute('data-location');
  const etitle = icon.getAttribute('data-title');
  const eperformers = icon.getAttribute('data-performers');

  document.getElementById('offcanvasDate').textContent = edate;
  document.getElementById('offcanvasVenue').innerHTML = evenue;
  document.getElementById('offcanvasLocation').innerHTML = elocation;
  document.getElementById('offcanvasTitle').innerHTML = etitle;
  document.getElementById('offcanvasId').href = '/event.php?id=' + eid;
  const eplist = document.getElementById('offcanvasPerformers');
  eplist.innerHTML = '';
  eperformers.split('|').forEach(name => {
      const li = document.createElement('li');
      li.innerHTML = `<a href="#">${name.trim()}</a>`;
      eplist.appendChild(li);
  });
  document.getElementById('venue-link').innerHTML = evenue;
});

/* =====================================================
 ARTIST EVENTS POPUP End
===================================================== */

/* =====================================================
   ARTIST SUBMENU CHANGE ON SCROLL
===================================================== */

const tabs = document.querySelectorAll('#artistTabs .nav-link');
const sections = document.querySelectorAll('.tab-section');
const offset = 120;

window.addEventListener('scroll', () => {
    let currentId = null;

    sections.forEach(section => {
        const rect = section.getBoundingClientRect();
        if (rect.top <= offset && rect.bottom > offset) {
            currentId = section.id;
        }
    });

    if (currentId) {
        tabs.forEach(tab => {
            tab.classList.toggle(
                'active',
                tab.dataset.target === currentId
            );
        });
    }
});

/* =====================================================
   ARTIST SUBMENU CHANGE ON SCROLL End
===================================================== */