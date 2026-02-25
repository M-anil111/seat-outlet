/* Save Location Start */

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

/* Save Location End */


/* Events Section Start */

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

/* Events Section End */

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
   CATEGORIES
===================================================== */

function loadBrowseCategories() {

  const wrapper = document.getElementById('browseCategoriesWrapper');
  if (!wrapper) return;

  fetch('./ajax/get-home-categories.php')
      .then(res => res.json())
      .then(data => {

          wrapper.innerHTML = buildCategoryHTML(data ?? {});

          const loader = document.getElementById('bbcLoader');
          if (loader) loader.remove();
      })
      .catch(() => {
          wrapper.innerHTML = '<p>Error loading categories</p>';
      });
}

function buildCategoryHTML(data = {}) {
  return `
      ${buildColumn('Concerts', data.concerts ?? [])}
      ${buildColumn('Sports', data.sports ?? [])}
      ${buildColumn('Theater', data.theater ?? [])}
      ${buildColumn('Festivals', data.festivals ?? [])}
      ${buildCitiesColumn('Cities', data.cities ?? [])}
  `;
}

function buildColumn(title, items) {

  if (!Array.isArray(items) || items.length === 0) return '';

  let list = `<ul class="categories__list">`;
  items.forEach(item => {
      list += `<li><a href="#">${Array.isArray(item) ? item[1] : item}</a></li>`;
  });
  list += `</ul>`;

  return `
      <div class="categories__col">
          <h3 class="categories__heading">${title}</h3>
          ${list}
      </div>
  `;
}

function buildCitiesColumn(title, cities) {

  if (!Array.isArray(cities) || cities.length === 0) return '';

  let list = `<ul class="categories__list">`;
  cities.forEach(city => {
      list += `<li><a href="#">${city.name}, ${city.state}</a></li>`;
  });
  list += `</ul>`;

  return `
      <div class="categories__col">
          <h3 class="categories__heading">${title}</h3>
          ${list}
      </div>
  `;
}

loadBrowseCategories();

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
                    loadNewsletter();
                    return;
                }

                let html = '';

                data.forEach(venue => {
                    html += `
                        <a href="/venue/${venue.slug}" class="team-link">
                            <div class="card venue-card venue-list-card p-3">
                                <h6 class="fw-semibold mb-1">${venue.name}</h6>
                                <p class="text-stub-muted mb-0" style="font-size: 1rem;">
                                    ${venue.city}, ${venue.state}
                                </p>
                            </div>
                        </a>
                    `;
                });

                container.innerHTML = html;

                setTimeout(() => {
                    initVenueSlider();
                    resolve();
                    loadNewsletter(); // 👈 load newsletter AFTER venues
                }, 50);

            })
            .catch(() => {
                container.innerHTML = '<p>Error loading venues</p>';
                resolve();
                loadNewsletter();
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
   iContact Newsletter
===================================================== */

function loadNewsletter() {

    const wrapper = document.getElementById('newsletterSection');
    if (!wrapper) return;

    fetch('./ajax/load-newsletter.php')
        .then(res => res.text())
        .then(html => {
            wrapper.innerHTML = html;
        })
        .catch(() => {
            wrapper.innerHTML = '';
        });
}


// Newsletter form submission
const newsletterForm = document.getElementById('newsletter-submit');
const newsletterEmail = document.getElementById('newsletter-email');

if (newsletterForm && newsletterEmail) {
  newsletterForm.addEventListener('click', function(e) {
      e.preventDefault();
      const email = newsletterEmail.value.trim();      
      if (email && isValidEmail(email)) {
        // Here you would typically send the email to a server
        alert('Thank you for subscribing!');
        newsletterEmail.value = '';
      } else {
        alert('Please enter a valid email address.');
      }
  });
  // Allow Enter key to submit
  newsletterEmail.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        newsletterForm.click();
      }
  });
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/* Location Input Header Start */
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

/* Location Input Header End */



/* Date Range Input Header Start */

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

/* Date Range Input Header End */



/* Keyword Input Header Start */

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

/* Keyword Input Header End */

