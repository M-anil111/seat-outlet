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

$('.suggestion-slider').on('setPosition', function(){
  equalHeightSlider('suggestion-slider', 'venue-card');
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
        { breakpoint: 576, settings: { slidesToShow: 1.5 } }
    ]
});

function getCityState(lat, lng, callback) {

  const geocoder = new google.maps.Geocoder();

  geocoder.geocode({ location: { lat, lng } }, function(results, status) {

    if (status !== "OK" || !results[0]) return;

    let city = '';
    let state = '';

    results[0].address_components.forEach(c => {
      if (c.types.includes('locality')) city = c.long_name;
      if (c.types.includes('administrative_area_level_1')) state = c.short_name;
    });

    if (!city) {
      results[0].address_components.forEach(c => {
        if (c.types.includes('administrative_area_level_2')) {
          city = c.long_name;
        }
      });
    }

    const label = city + ', ' + state;

    setCookie('so_cs', label);

    if (callback) callback(label);
  });
}

function initLocationSearch(inputId, type = '') {

  const input = document.getElementById(inputId);
  if (!input) return; 

  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ['(regions)'],
    componentRestrictions: { country: 'us' }
  });

  autocomplete.addListener('place_changed', function () {

    input.closest('.locationInputFieldWrapper')?.classList.remove('pac-active');

    const place = autocomplete.getPlace();
    if (!place.address_components) return;
    let city = '';
    let state = '';
    place.address_components.forEach(component => {
      const types = component.types;

      if (types.includes('locality')) {
        city = component.long_name;
      }

      if (types.includes('administrative_area_level_1')) {
        state = component.long_name;
      }        
    });


    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();

    if(type == 'home') { 

      const locationText = document.getElementById('locationSelectorText');
      const locationPanel = document.getElementById('locationPanel');
      const nearLocationText = document.getElementById('nearLocationText');

      if (locationText) locationText.innerHTML = input.value + ' <i class="bi bi-chevron-down"></i>';
      if (locationPanel) locationPanel.classList.remove('show');
      if (nearLocationText) nearLocationText.innerHTML = input.value;

      setCookie('so_label', input.value);
      setCookie('so_lat', lat);
      setCookie('so_lng', lng);      
     
      setTimeout(() => {
        if (typeof reloadActiveTab === 'function') {
          reloadActiveTab('ll', {lat, lng});
        }
        if (typeof loadNearbyVenues === 'function') {
          loadNearbyVenues();
        }
        if (typeof loadTeams === 'function') {
          loadTeams('NFL');
        }
      }, 200);
      

    }else{

        document.getElementById('latHeader').value = lat;
        document.getElementById('lngHeader').value = lng;

        if(input.value !== '') {
          const nearLocationText = document.getElementById('nearLocationText');
          if (nearLocationText) nearLocationText.innerHTML = input.value;
          //input.readOnly = true;   
          const resetLoc = document.getElementById('locationHeaderReset');
          resetLoc.classList.remove('d-none');
          resetLoc.addEventListener('click', function () {
            //input.readOnly = false;
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
    const savedLat = getCookie('so_lat');
    const savedLng = getCookie('so_lng');
    const savedLabel = getCookie('so_label');
  
    if (savedLabel) {
      const locText = document.getElementById('locationSelectorText');
      if (locText) locText.innerHTML = savedLabel + ' <i class="bi bi-chevron-down"></i>';
  
      const nearLocationText = document.getElementById('nearLocationText');
      if (nearLocationText) nearLocationText.innerHTML = savedLabel;
    }
  
    if (savedLat && savedLng) {
      window.locationReady = true;
  
      if (typeof reloadActiveTab === 'function') {
        reloadActiveTab('ll', { lat: savedLat, lng: savedLng });
      }
      if (typeof loadNearbyVenues === 'function') {
        loadNearbyVenues();
      }
      if (typeof loadTeams === 'function') {
        loadTeams('NFL');
      }
      return;
    }
  
    fetch('/ajax/get_ip_details.php')
      .then(res => res.json())
      .then(data => {
        setCookie('so_lat', encodeURIComponent(data.lat));
        setCookie('so_lng', encodeURIComponent(data.lng));
        setCookie('so_label', data.city + ', ' + data.state);
  
        const locText = document.getElementById('locationSelectorText');
        if (locText) locText.innerHTML = data.city + ', ' + data.state + ' <i class="bi bi-chevron-down"></i>';
  
        const nearLocationText = document.getElementById('nearLocationText');
        if (nearLocationText) nearLocationText.innerHTML = data.city + ', ' + data.state;
  
        window.locationReady = true;
  
        if (typeof reloadActiveTab === 'function') {
          reloadActiveTab('ll', { lat: data.lat, lng: data.lng });
        }
        if (typeof loadNearbyVenues === 'function') {
          loadNearbyVenues();
        }
        if (typeof loadTeams === 'function') {
          loadTeams('NFL');
        }
      })
      .catch(() => {});
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
    const nearLocationText = document.getElementById('nearLocationText');
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

          tempImg.onload = function () {
            img.src = data.image;

            setTimeout(() => {
              img.classList.add('loaded');
            }, 50);
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
        
      container.innerHTML = generateEventSkeleton(4);
      initSlider(selector, 'skeleton');

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
        if ($slider.hasClass('slick-initialized')) {
          $slider.slick('unslick');
        }
  
        container.innerHTML = buildCards(data);
  
        setTimeout(() => {
          initSlider(selector);
        }, 50);
  
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
        if (nearLocationText) nearLocationText.innerHTML = savedLabel;
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

            const nearLocationText = document.getElementById('nearLocationText');
            if (nearLocationText) nearLocationText.innerHTML = so_cs;
    
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
              const nearLocationText = document.getElementById('nearLocationText');
              if (nearLocationText) nearLocationText.innerHTML = data.city + ', ' + data.state;
              
              if (typeof reloadActiveTab === 'function') {
                reloadActiveTab('ll', { lat: data.lat, lng: data.lng });
              }
              if (typeof loadNearbyVenues === 'function') {
                loadNearbyVenues();
              }
              if (typeof loadTeams === 'function') {
                loadTeams('NFL');
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
          { breakpoint: 576, settings: { slidesToShow: 1.5 } }
        ]
      });
    }
  
    window.loadTeams = function(slug) {
      const league = String(slug || '').toUpperCase().trim();
      if (!league) return;
    
      container.innerHTML = '';
      container.innerHTML = generateTeamSkeleton(4);

      const jsonUrl = `/cache/teams_${league}.json`;

      fetch(jsonUrl)
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
                  teamsTitle.textContent = 'Top Teams';
              }

              let html = `<div class="tab-pane show active" id="${escapeHtml(league)}" role="tabpanel">
                      <div class="team-slider new-slider">`;

              data.sort((a, b) => a.name.localeCompare(b.name))
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
    const teamsSection = document.querySelector('.teams-section');
  
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
        }, { rootMargin: '200px' });
  
        venueObserver.observe(venueSection);
      }
    }
  
    // 🔥 TEAMS
    if (typeof loadTeams === 'function') {
  
      if (!teamsSection) return;
  
      const teamsObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const activeBtn = document.querySelector('.sport-cat.active');
            if (activeBtn && activeBtn.dataset.slug) {
              loadTeams(activeBtn.dataset.slug);
            }
            observer.disconnect();
          }
        });
      }, { rootMargin: '200px' });
  
      teamsObserver.observe(teamsSection);
    }
  
  });
 
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
  const resetLocHeader = document.getElementById('locationHeaderReset');
  
  if(resetLocHeader && inputHeader.value) {
    resetLocHeader.classList.remove('d-none');
    resetLocHeader.addEventListener('click', function () {
      //input.readOnly = false;
      inputHeader.value = '';
      resetLocHeader.classList.add('d-none');       
      latHeader.value = '';
      lngHeader.value = '';
    });  
  }
  
  if (inputHeader) {
    inputHeader.addEventListener('focus', async function once() {
      await loadGoogleMapsApi();
      initLocationSearch('locationInputHeader');
      inputHeader.removeEventListener('focus', once);
    }, { once: true });
  }

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

      document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
              resultsHeader.innerHTML = '';
          }
      });

      document.addEventListener('click', function (e) {
          if (!inputHeader.contains(e.target) && !resultsHeader.contains(e.target)) {
              resultsHeader.innerHTML = '';
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

              getCityState(lat, lng, function(so_cs) {

                inputHeader.value = so_cs;
            
              }); 
  
              if(inputHeader.value !== '') {
                //inputHeader.readOnly = true;   
                resetLocHeader.classList.remove('d-none');
                resetLocHeader.addEventListener('click', function () {
                    //inputHeader.readOnly = false;
                    inputHeader.value = '';
                    this.classList.add('d-none');       
                    latHeader.value = '';
                    lngHeader.value = '';
                });            
              } 
             
          },
          () => {
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
      dateFormat: "Y-m-d",
      altFormat: "M j, Y",
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
  const searchLoader = document.getElementById('search-loader');
  
  if (keywordHeader && keywordResultsHeader) {
  
    let typingTimer = null;
    let currentController = null;
    let activeIndex = -1;
  
    const typingDelay = 150;
    const minChars = 2;
  
    let lastQuery = '';
    let lastResponse = null;
  
    const searchCache = {};
  
    // 🔥 better normalize (important)
    function normalize(q) {
      return q
        .toLowerCase()
        .replace(/,/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    }
  
    function showLoader() {
      if (searchLoader) searchLoader.style.display = 'block';
    }
  
    function hideLoader() {
      if (searchLoader) searchLoader.style.display = 'none';
    }
  
    function closeSuggestions() {
      activeIndex = -1;
      keywordResultsHeader.innerHTML = '';
      keywordResultsHeader.style.display = 'none';
      keywordHeader.setAttribute('aria-expanded', 'false');
      keywordHeader.removeAttribute('aria-activedescendant');
      hideLoader();
    }
  
    function showSuggestions() {
      keywordHeader.setAttribute('aria-expanded', 'true');
      keywordResultsHeader.style.display = 'block';
    }
  
    function createSlug(name, id) {
      return `${name}-${id}`
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-');
    }
  
    // 🔥 PREFIX CACHE (instant results)
    function getCachedPrefix(q) {
      let bestMatch = '';
  
      Object.keys(searchCache).forEach(key => {
        if (q.startsWith(key) && key.length > bestMatch.length) {
          bestMatch = key;
        }
      });
  
      return bestMatch ? searchCache[bestMatch] : null;
    }
  
    function renderResults(data, q) {

      const performers = data?.performers || {};
      const cities = data?.cities || {};
      const venues = data?.venues || {};

      let html = '<ul class="search-suggestions" role="listbox">';
  
      if (
        (performers.totalResultCount || 0) ||
        (cities.totalResultCount || 0) ||
        (venues.totalResultCount || 0)
      ) {
        let s = 0;
  
        if (performers.totalResultCount > 0 && Array.isArray(performers.results)) {
          html += '<li class="suggestion-label">Performers</li>';
          performers.results.forEach(item => {
            html += `
              <li class="result-item" id="suggestion-${s}" role="option">
                <a href="/artist/${createSlug(item.name, item.id)}">
                  ${escapeHtml(item.name)}
                </a>
              </li>`;
            s++;
          });
        }
  
        if (cities.totalResultCount > 0 && Array.isArray(cities.results)) {
          html += '<li class="suggestion-label">Cities</li>';
          cities.results.forEach(item => {
            html += `
              <li class="result-item" id="suggestion-${s}" role="option">
                <a href="/city/${createSlug(item.name, item.id)}">
                  ${escapeHtml(item.name)}, ${escapeHtml(item.state || '')}
                </a>
              </li>`;
            s++;
          });
        }
  
        if (venues.totalResultCount > 0 && Array.isArray(venues.results)) {
          html += '<li class="suggestion-label">Venues</li>';
          venues.results.forEach(item => {
            html += `
              <li class="result-item" id="suggestion-${s}" role="option">
                <a href="/venue/${createSlug(item.name, item.id)}">
                  ${escapeHtml(item.name)}
                </a>
              </li>`;
            s++;
          });
        }
  
      } else {
        html += `<li class="result-item">No results for "${escapeHtml(q)}"</li>`;
      }
  
      html += '</ul>';
  
      keywordResultsHeader.innerHTML = html;
      showSuggestions();
      activeIndex = -1;
    }
  
    function fetchSuggestions(q) {
  
      if (q.length < minChars) {
        closeSuggestions();
        return;
      }
  
      // ✅ exact cache
      if (searchCache[q]) {
        hideLoader();
        renderResults(searchCache[q], q);
        return;
      }
  
      // 🔥 prefix cache (instant feel)
      const prefixData = getCachedPrefix(q);
      if (prefixData) {
        renderResults(prefixData, q);
      }
  
      if (q === lastQuery) return;
      lastQuery = q;
  
      if (currentController) currentController.abort();
  
      currentController = new AbortController();
  
      if (!prefixData) showLoader();
  
      fetch(`/ajax/keyword-search.php?q=${encodeURIComponent(q)}`, {
        signal: currentController.signal
      })
      .then(res => res.text())
      .then(text => {
        const data = JSON.parse(text);
        hideLoader();  
        searchCache[q] = data;
        lastResponse = data;
  
        if (normalize(keywordHeader.value) !== q) return;
  
        renderResults(data, q);
      })
      .catch(err => {
        hideLoader();
        if (err.name !== 'AbortError') console.error(err);
      });
    }
  
    // 🔥 INPUT (instant + debounce hybrid)
    keywordHeader.addEventListener('input', function () {
  
      const q = normalize(this.value);
  
      sessionStorage.setItem('last_search', q);
  
      clearTimeout(typingTimer);
  
      if (q.length < minChars) {
        closeSuggestions();
        return;
      }
  
      // instant feel
      fetchSuggestions(q);
  
      // debounce API
      typingTimer = setTimeout(() => {
        fetchSuggestions(q);
      }, typingDelay);
    });
  
    // keyboard navigation (same as yours, kept intact)
    keywordHeader.addEventListener('keydown', function (e) {
  
      const items = keywordResultsHeader.querySelectorAll('.result-item a');
  
      if (e.key === 'Escape') {
        closeSuggestions();
        return;
      }
  
      if (!items.length) return;
  
      if (e.key === 'ArrowDown' || (e.key === 'Tab' && !e.shiftKey)) {
        e.preventDefault();
        activeIndex = (activeIndex + 1) % items.length;
        updateActive(items);
      }
  
      if (e.key === 'ArrowUp' || (e.key === 'Tab' && e.shiftKey)) {
        e.preventDefault();
        activeIndex = (activeIndex - 1 + items.length) % items.length;
        updateActive(items);
      }
  
      if (e.key === 'Enter') {
        if (activeIndex >= 0 && items[activeIndex]) {
          e.preventDefault();
          window.location.href = items[activeIndex].href;
        }
      }
    });
  
    function updateActive(items) {
      items.forEach(el => el.parentElement.classList.remove('active'));
  
      if (items[activeIndex]) {
        const activeItem = items[activeIndex];
        activeItem.parentElement.classList.add('active');
  
        keywordHeader.setAttribute(
          'aria-activedescendant',
          activeItem.parentElement.id
        );
      }
    }
  
    document.addEventListener('click', function (e) {
      if (!keywordHeader.contains(e.target) && !keywordResultsHeader.contains(e.target)) {
        closeSuggestions();
      }
    });
  
    keywordHeader.addEventListener('focus', function () {
      const q = normalize(this.value);
  
      if (q.length < minChars) return;
  
      if (searchCache[q]) {
        renderResults(searchCache[q], q);
        return;
      }
  
      fetchSuggestions(q);
    });
  
  }
  $(document).ready(function(){

    // open submenu
    $('.open-submenu').click(function(e){
        e.preventDefault();

        let target = $(this).data('target');
        $('#' + target).addClass('active');
    });

    // back
    $('.back-btn').click(function(){
        $(this).closest('.submenu-panel').removeClass('active');
    });

});

    /* =====================================================
     HEADER KEYWORD FIELD End
  ===================================================== */

