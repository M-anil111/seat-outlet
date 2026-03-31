function getMonthCount() {
    return window.innerWidth <= 689 ? 1 : 2;
}
function normalizeKey(v) {
  if (typeof v === 'number') v = v.toFixed(8);
  return String(v || '').toLowerCase().replace(/\./g, '-').replace(/ /g, '_');
}
function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
function getCookie(name) {
  const v = document.cookie.split('; ').find(row => row.startsWith(name + '='));
  return v ? decodeURIComponent(v.split('=')[1]) : '';
}
function setCookie(name, value) {
  document.cookie = name + '=' + value + ';path=/';
}

function equalHeightSlider(sectionClass, cardClass) { 
  var maxHeight = 0;     
  const sectionSelector = '.' + sectionClass + ' .' + cardClass;
  $(sectionSelector).css('height','auto');
  $(sectionSelector).each(function(){
      if($(this).height() > maxHeight){
          maxHeight = $(this).height();
      }
  });     
  $(sectionSelector).height(maxHeight);
}

$('.custom-slider').on('setPosition', function(){
  equalHeightSlider('custom-slider', 'event-card');
});

$('.venue-slider').on('setPosition', function(){
equalHeightSlider('venue-slider', 'venue-card');
});

const $sugg_slider = $('.suggestion-slider');
$sugg_slider.slick({
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

function initLocationSearch(inputId, type = '') {

  const input = document.getElementById(inputId);
  if (!input) return; 

  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['(regions)'],
    componentRestrictions: { country: 'us' }
  });

  autocomplete.addListener('place_changed', function () {

    const place = autocomplete.getPlace();

    if (!place.address_components) return;

      let city = '';
      //let state = '';

      place.address_components.forEach(component => {
        const types = component.types;

        if (types.includes('locality')) {
          city = component.long_name;
        }

        // if (types.includes('administrative_area_level_1')) {
        //   state = component.long_name;
        // }

        setCookie('teamLocation', city);
      });

    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();

    if(type == 'home') { 

      const locationText = document.getElementById('locationSelectorText');
      const locationPanel = document.getElementById('locationPanel');

      if (locationText) locationText.innerHTML = input.value + ' <i class="bi bi-chevron-down"></i>';
      if (locationPanel) locationPanel.classList.remove('show');

      setCookie('so_label', input.value);
      setCookie('so_lat', lat);
      setCookie('so_lng', lng);      
     
      setTimeout(() => {
        reloadActiveTab('ll', {lat, lng});
        loadNearbyVenues();
        loadTeams('NFL');
      }, 200);
      

    }else{

        document.getElementById('latHeader').value = lat;
        document.getElementById('lngHeader').value = lng;

        if(input.value !== '') {
          input.disabled = true;   
          const resetLoc = document.getElementById('locationHeaderReset');
          resetLoc.classList.remove('d-none');
          resetLoc.addEventListener('click', function () {
            input.disabled = false;
            input.value = '';
            this.classList.add('d-none');       
            document.getElementById('latHeader').value = '';
            document.getElementById('lngHeader').value = '';
          });            
        } 
    }

  });

}

 
  /* =====================================================
     SAVE LOCATION
  ===================================================== */
 
  document.addEventListener('DOMContentLoaded', function () {

    if (navigator.geolocation) {
    
      navigator.geolocation.getCurrentPosition(
          function(position) {
    
              const lat = position.coords.latitude;
              const lng = position.coords.longitude;
              
              setCookie('so_lat', encodeURIComponent(lat));
              setCookie('so_lng', encodeURIComponent(lng));
              setCookie('so_label', 'Current Location');
              
              setTimeout(() => {
                const locText = document.getElementById('locationSelectorText');
                if (locText) locText.innerHTML = 'Current Location <i class="bi bi-chevron-down"></i>';
                reloadActiveTab('ll', { lat, lng });
                loadNearbyVenues();
                loadTeams('NFL');
              }, 200);
    
          },
          function(error) {

            const _solabel = getCookie('so_label');

            if(!_solabel || _solabel == 'Current Location') {
              
              fetch(`/ajax/get_ip_details.php`)
                .then(res => res.json())
                .then(data => {
                 
                  setCookie('so_lat', encodeURIComponent(data.lat));
                  setCookie('so_lng', encodeURIComponent(data.lng));
                  setCookie('so_label', encodeURIComponent(data.city + ', ' + data.state));
                  setCookie('teamLocation', data.city);
                  
                  setTimeout(() => {
                    const locText = document.getElementById('locationSelectorText');
                    if (locText) locText.innerHTML = data.city + ', ' + data.state + ' <i class="bi bi-chevron-down"></i>';
                    reloadActiveTab('ll', { lat: data.lat, lng: data.lng });
                    loadNearbyVenues();
                    loadTeams('NFL');
                  }, 200);
                  
                })
              .catch(() => {
                
              });
            }

          },
          {
              enableHighAccuracy: true,
              timeout: 10000,
              maximumAge: 0
          }
      );

    }

  });
  
  /* =====================================================
     SAVE LOCATION End
  ===================================================== */

 
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
        html += `
          <a href="/event.php?id=${event.id}" class="team-link">
            <article class="event-card">
              <div class="event-card__img">
                <img src="${event.image}" alt="${event.name}" class="img-fluid">
              </div>
              <div class="event-card__body">
                <h3 class="event-card__title venu-name-hide">${event.name}</h3>
                <div class="mb-1">
                  <span class="venu-date">${event.date}</span>
                  <span class="venu-name">${event.venue} - ${event.loc}</span>
                </div>
                ${event.price ? `<p class="event-card__price mb-0">from <strong>${event.price}</strong></p>` : ''}
              </div>
            </article>
          </a>
        `;
      });

      return html;
    }

    function generateEventSkeleton(count = 6) {
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

    function initSlider(selector, loader = '') {
      const $slider = $(selector);

      if ($slider.hasClass('slick-initialized')) {
        $slider.slick('unslick');
      }

      $slider.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        infinite: false,
        arrows: loader === 'skeleton' ? false : true,
        autoplay: loader === 'skeleton' ? false : true,
        dots: false,
        responsive: [
          { breakpoint: 992, settings: { slidesToShow: 3 } },
          { breakpoint: 768, settings: { slidesToShow: 2 } },
          { breakpoint: 576, settings: { slidesToShow: 1 } }
        ]
      });
    }

    window.loadLocationCategory = async function(tabId, type, loc1, loc2) {

      const selector = '#' + tabId + ' .custom-slider';
      const container = document.querySelector(selector);
      if (!container) return;

      const ajaxUrl =
        `/ajax/get-location-category-events.php?tab=${encodeURIComponent(tabId)}` +
        `&type=${encodeURIComponent(type)}&loc1=${encodeURIComponent(loc1)}&loc2=${encodeURIComponent(loc2)}`;
        
      container.innerHTML = '';
      container.innerHTML = generateEventSkeleton(4);
      initSlider(selector, 'skeleton');

      fetch(ajaxUrl)
      .then(res => res.json())
        .then(data => {
          
          if (!data || !data.length) {
            container.innerHTML = '<p class="text-center">No events found</p>';
            return;
          }
          
          const $slider = $(selector);
          if ($slider.hasClass('slick-initialized')) {
            $slider.slick('unslick');
          }

          container.innerHTML = buildCards(data);

          setTimeout(() => {
            initSlider(selector);
          }, 50);
          
        })
        .catch(err => {
          container.innerHTML = '<p>Error loading events</p>';
        });

    }
    
    window.reloadActiveTab = function(mode = '', loc = {}) {
      const tabId = getActiveTabId();

      if (mode === 'll') return loadLocationCategory(tabId, 'll', loc.lat, loc.lng);
      
      return loadLocationCategory(tabId, '', '', '');
    };

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

          setCookie('so_lat', lat);
          setCookie('so_lng', lng);
          setCookie('so_label', 'Current Location');

          if (locationText) {
            locationText.innerHTML = 'Current Location <i class="bi bi-chevron-down"></i>';
          }

          reloadActiveTab('ll', { lat, lng });

        });

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
        setCookie('so_label', '');
        setCookie('teamLocation', '');        

        if (locationText) locationText.innerHTML = 'Select your location <i class="bi bi-chevron-down"></i>';
        if (cityInput) cityInput.value = '';
        
        reloadActiveTab('', {});
        loadNearbyVenues();
        loadTeams('NFL');
      });
    }

    initLocationSearch('cityLocationInput', 'home');
  });
   
  
  /* =====================================================
     EVENTS End
  ===================================================== */
  
  /* =====================================================
     TEAMS
  ===================================================== */
  
  document.addEventListener('DOMContentLoaded', function () {
  
    const container = document.getElementById('sportsTabContent');
    if (!container) return;
  
     
    function generateTeamSkeleton(count = 6) {
  
      let html = '<div class="team-slider">';
    
      for (let i = 0; i < count; i++) {
    
        html += `
        <a href="javascript:void(0)" class="team-link skeleton-link">
          <div class="team-card team-card-skeleton">
            <span class="skeleton-line skeleton-team-name"></span>
          </div>
        </a>
        `;
      }
    
      return html + '</div>';
    }
  
    function initTeamSlider(loader = '') {
      const $slider = $('.team-slider');
      if (!$slider.length) return;
  
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
          { breakpoint: 992, settings: { slidesToShow: 2 } },
          { breakpoint: 768, settings: { slidesToShow: 2 } },
          { breakpoint: 576, settings: { slidesToShow: 1 } }
        ]
      });
    }
  
    window.loadTeams = function(slug) {
      const league = String(slug || '').toUpperCase().trim();
      if (!league) return;
    
      container.innerHTML = '';
      container.innerHTML = generateTeamSkeleton(4);
      initTeamSlider('skeleton');

      const jsonUrl = `/cache/teams_${league}.json`;

      fetch(jsonUrl, { cache: 'no-store' })
          .then(res => {
            if (!res.ok) throw new Error('json_not_found');
            return res.json();
          })
          .then(data => {

            if (!Array.isArray(data) || !data.length) {
              document.querySelector('.teams-section .container')?.classList.remove('text-white');
              container.innerHTML = '<p>No teams available.</p>';
              return;
            }

              const teamsTitle = document.querySelector('.teams-section h2');  
              if (teamsTitle) {
                  const solbl = getCookie('so_label') || '';
                  teamsTitle.textContent = solbl
                    ? `Top Teams Near ${solbl}`
                    : 'Top Teams';
              }

              const cityName = getCookie('teamLocation');
              let filteredData = data.filter(team =>
                team.name.includes(cityName)
              );

              if (filteredData.length === 0) {
                filteredData = data;
              }

              let html = `<div class="tab-pane show active" id="${escapeHtml(league)}" role="tabpanel">
                      <div class="team-slider new-slider px-4">`;

              filteredData.sort((a, b) => a.name.localeCompare(b.name))
              .forEach(team => {
                const teamSlug = escapeHtml(team.slug);
                const teamName = escapeHtml(team.name);
                const teamLogo = escapeHtml(team.logo);
      
                html += `
                  <a href="/artist/${teamSlug}" class="team-link">
                    <div class="team-card">
                      <div class="team-icon bg-primary"><img src="${teamLogo}" alt="${teamName}" /></div>
                      <span>${teamName}</span>
                    </div>
                  </a>
                `;
              });
      
              html += `</div></div>`;
      
              document.querySelector('.teams-section .container')?.classList.add('text-white');
              container.innerHTML = html;
              initTeamSlider();

          })
          .catch(() => {
            container.innerHTML = '<p>Error loading teams.</p>';            
          });

      
    
    };
  
    // default load
    const activeBtn = document.querySelector('.sport-cat.active');
    if (activeBtn && activeBtn.dataset.slug) {
      loadTeams(activeBtn.dataset.slug);
    }
  
    // on tab switch
    document.querySelectorAll('button.sport-cat').forEach(btn => {
      btn.addEventListener('shown.bs.tab', function () {
        loadTeams(this.dataset.slug);
      });
    });
  
  });
  
  /* =====================================================
     TEAMS End
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
  
  window.loadNearbyVenues = function() { 
 
      const container = document.querySelector('.venue-slider');
      if (!container) return;

      const solt = getCookie('so_lat') || '';
      const solg = getCookie('so_lng') || '';

      container.innerHTML = '';
      container.innerHTML = buildVenueSkeleton(4);
      initVenueSlider('skeleton');
      
      const ajaxUrlVenue =
        `/ajax/get-nearby-venues.php?solt=${encodeURIComponent(solt)}&solg=${encodeURIComponent(solg)}`;
  
      fetch(ajaxUrlVenue)
      .then(res => res.json())
        .then(data => {
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
          initVenueSlider();
          
        })
        .catch(err => {
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
  
  google.maps.event.addDomListener(window, 'load', initLocationSearch('locationInputHeader'));

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
  }
  
  if(resultsHeader) {
      resultsHeader.addEventListener('click', function (e) {  
          if (e.target.id === 'useCurrentLocationHeader') {
              getCurrentLocationHeader();
              return;
          }
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

              if(inputHeader.value !== '') {
                inputHeader.disabled = true;   
                const resetLoc = document.getElementById('locationHeaderReset');
                resetLoc.classList.remove('d-none');
                resetLoc.addEventListener('click', function () {
                    inputHeader.disabled = false;
                    inputHeader.value = '';
                    this.classList.add('d-none');       
                    latHeader.value = '';
                    lngHeader.value = '';
                });            
              } 
             
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
  
  function toApiDate(dateObj) {
    if (!dateObj) return null;
  
    const year  = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day   = String(dateObj.getDate()).padStart(2, '0');
  
    return `${year}-${month}-${day}`;
  }
  
  document.addEventListener("DOMContentLoaded", function () {
  
    let selectedDatesTemp = [];  
    let startInputHeader = document.getElementById('startInputHeader');
    let endInputHeader = document.getElementById('endInputHeader');
    let selectedStart = null;
    let selectedEnd = null;
  
   
  
    let fp = flatpickr("#customDatePicker", {
      mode: "range",
      minDate: "today",
      dateFormat: "M j, Y",
      showMonths: getMonthCount(),
      disableMobile: true,
      clickOpens: true,
    
      onChange: function(selectedDates) {
        selectedStart = selectedDates[0] || null;
        selectedEnd   = selectedDates[1] || null;
        if (selectedDates.length === 2) {
            selectedDatesTemp = selectedDates;
            const startDate = toApiDate(selectedStart);
            const endDate   = toApiDate(selectedEnd);
  
            startInputHeader.value = startDate;
            endInputHeader.value = endDate;
        }
      },
    
      onReady: function(selectedDates, dateStr, instance) {
    
        instance.calendarContainer.classList.add("so-date-picker-custom");
    
        const footer = document.createElement("div");
        footer.className = "fp-footer";
    
        footer.innerHTML = `
          <div class="fp-footer-left">
            <button class="fp-reset" id="fp-header-reset">Reset</button>
          </div>
        `;
    
        instance.calendarContainer.appendChild(footer);
    
        footer.querySelector("#fp-header-reset").addEventListener("click", () => {
          startInputHeader.value = '';
          endInputHeader.value = '';
          instance.clear();
        });
  
        
  
      }
    });
  
    window.addEventListener("resize", function () {
      const newMonthCount = getMonthCount();
    
      if (fp.config.showMonths !== newMonthCount) {
        fp.set("showMonths", newMonthCount);
        fp.redraw();
      }
    });
  
  });
  
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

      let typingTimerKeyword;
      let currentRequest = null;
      const typingDelayKeyword = 500;

      keywordHeader.addEventListener('keyup', function () {

        const q = this.value.trim();
    
        clearTimeout(typingTimerKeyword);
    
        if (q.length < 2) {
            keywordResultsHeader.innerHTML = '';
            return;
        }
    
        typingTimerKeyword = setTimeout(() => {
    
            // Abort previous request (important)
            if (currentRequest) {
                currentRequest.abort();
            }
    
            currentRequest = new AbortController();
    
            fetch(`/ajax/keyword-search.php?q=${encodeURIComponent(q)}`, {
                signal: currentRequest.signal
            })
            .then(res => res.json())
            .then(data => {

              let html = '<ul class="search-suggestions">';

              if (data.artists?.length || data.cities?.length || data.venues?.length) {
    
                // ARTISTS
                if (data.artists?.length) {
                    html += '<li class="suggestion-label">Artists</li>';
                    data.artists.sort((a, b) => a.name.localeCompare(b.name))
                    .forEach(item => {
                        let itemSlug = item.slug.toLowerCase();
                        html += `
                            <li class="result-item">
                              <a href="/artist/${itemSlug}">
                                ${item.name}
                              </a>
                            </li>`;
                    });
                }
    
                // CITIES
                if (data.cities?.length) {
                    html += '<li class="suggestion-label">Cities</li>';
                    data.cities.sort((a, b) => a.name.localeCompare(b.name))
                    .forEach(item => {
                        let itemSlug = item.slug.toLowerCase();
                        html += `
                            <li class="result-item">
                              <a href="/city/${itemSlug}">
                                ${item.name}${item.state ? ', ' + item.state : ''}
                              </a>
                            </li>`;
                    });
                }
    
                // VENUES
                if (data.venues?.length) {
                    html += '<li class="suggestion-label">Venues</li>';
                    data.venues.sort((a, b) => a.name.localeCompare(b.name))
                    .forEach(item => {
                        let itemSlug = item.slug.toLowerCase();
                        html += `
                            <li class="result-item">
                              <a href="/venue/${itemSlug}">
                                ${item.name}
                                ${item.city ? `<span class="small text-muted">(${item.city}${item.state ? ', ' + item.state : ''})</span>` : ''}
                              </a>
                            </li>`;
                    });
                }
              
              }else{
                html += '<li class="result-item">Nothing found</li>';
              }
              
              html += '</ul>';

              keywordResultsHeader.innerHTML = html;
                
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    console.error(err);
                }
            });
    
        }, typingDelayKeyword);
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

let typingTimer;
const typingDelay = 400;

if (input) {

    input.addEventListener('click', function () {
        const q = this.value.trim();

        if (q.length === 0) {
            results.innerHTML = `
                <div class="current-location" id="useCurrentLocation">
                    <i class="bi bi-send ms-1 me-2"></i>
                    <span class="ms-4 ps-2">Current location</span>
                </div>
            `;
        }
    });

    input.addEventListener('keyup', function () { 

        clearTimeout(typingTimer);

        const q = this.value.trim();

        if (q.length === 0) {
            results.innerHTML = `
                <div class="current-location" id="useCurrentLocation">
                    <i class="bi bi-send ms-1 me-2"></i>
                    <span class="ms-4 ps-2">Current location</span>
                </div>
            `;
            return;
        }

        if (q.length < 2) {
            results.innerHTML = '';
            return;
        }

        typingTimer = setTimeout(() => {

            fetch(`/ajax/location-search.php?q=${encodeURIComponent(q)}`)
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

                    html += '</ul>';

                    results.innerHTML = html;

                });

        }, typingDelay);
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
  
      fetch(`/ajax/load-events.php?${params}`)
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
              if(location.flag !== 'reset' && input.value !== '') {
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
  
  const startInput = document.getElementById('startInput');
  const endInput = document.getElementById('endInput');
  let performerSelectedDatesTemp = [];
  let selectStart = null;
  let selectEnd = null;
  
  let picker = flatpickr("#performerDatePicker", {
      mode: "range",
    minDate: "today",
      dateFormat: "M j, Y",
      showMonths: getMonthCount(),
      disableMobile: true,
      clickOpens: true,
  
      onChange: function(selectedDates) {
          
        selectStart = selectedDates[0] || null;
        selectEnd   = selectedDates[1] || null;
  
        const cpid = input.dataset.cpid;
        const dcat = input.dataset.dcat;
  
        if (selectedDates.length === 2) {
            performerSelectedDatesTemp = selectedDates;
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
      },
  
      onReady: function(selectedDates, dateStr, instance) {
  
          instance.calendarContainer.classList.add("so-date-picker");
  
          const footer = document.createElement("div");
          footer.className = "fp-footer";
  
          footer.innerHTML = `
              <div class="fp-footer-left">
                  <button class="fp-reset" id="fp-reset">Reset</button>
              </div>
          `;
  
          instance.calendarContainer.appendChild(footer);
  
          footer.querySelector("#fp-reset").addEventListener("click", () => {
              instance.clear();
          });
      }
  });
  
  window.addEventListener("resize", function () {
      const newMonthCount = getMonthCount();
      if (!picker || !picker.config) return;
      if (picker.config.showMonths !== newMonthCount) {
          picker.set("showMonths", newMonthCount);
          picker.redraw();
      }
  });
  
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
          let params = this.dataset.params;
          
          this.disabled = true;
          loadMoreBtn.querySelector('#btnSpinner').classList.remove('d-none');
            if(performerId) {
                fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&performerId=${performerId}`;
            }else{
                fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&params=${encodeURIComponent(params)}`;
            }
            
          fetch(fetchUrl)
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
      let dataPerformers = [];
      if(eperformers) {
        let names = eperformers.map(performer => performer.name ?? null).filter(Boolean);
        dataPerformers = names.join('|');
      }
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
                      <a href="/event.php?id=${event.id}">${event.text.name}</a>
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
  
  
  