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
        dateFormat: "Y-m-d",
        altFormat: "M j, Y",
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
      const offset   = isMobile ? 0 : 92; 
    
      const elementPosition = target.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition  = elementPosition - offset;
    
      window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
      });
    
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
          const type     = this.dataset.type;
          let params = this.dataset.params;
          
          this.disabled = true;
          loadMoreBtn.querySelector('#btnSpinner').classList.remove('d-none');
            if(performerId) {
                fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&performerId=${performerId}`;
            }else{
                if(type == 'search') { 
                    fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&params=${encodeURIComponent(params)}&type=${type}`;
                }else{
                    fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&params=${encodeURIComponent(params)}`;
                }
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
      const d = evtdate.getDate();
      const edate = d < 10 ? '0' + d : d;
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