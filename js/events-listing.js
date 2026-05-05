/* =====================================================
    ARTIST FILTER
===================================================== */
  
const input = document.getElementById('locationInput');
const results = document.getElementById('locationResults');
const resetBtn = document.getElementById('locationInputReset');
const latEvent = document.getElementById('latEvent');
const lngEvent = document.getElementById('lngEvent');
const sdateEvent = document.getElementById('sdateEvent');
const edateEvent = document.getElementById('edateEvent');

function getActiveLocation() {
    const lat = latEvent.value || getCookie('so_lat');
    const lng = lngEvent.value || getCookie('so_lng');

    return (lat && lng) ? { lat, lng } : {};
}

document.addEventListener('DOMContentLoaded', async () => {
    if (input) {
        await loadGoogleMapsApi();
        initLocationSearch('locationInput', 'event');

        const savedLat = getCookie('so_lat');
        const savedLng = getCookie('so_lng');
        const savedLabel = getCookie('so_label');

        if (savedLat && savedLng) {
            latEvent.value = savedLat;
            lngEvent.value = savedLng;

            if (savedLabel) {
                input.value = savedLabel;
                updateHeading(savedLabel);
            }

            updateEventsSection({
                lat: savedLat,
                lng: savedLng,
                startDate: sdateEvent.value,
                endDate: edateEvent.value
            });
        }
        
        input.addEventListener('focus', function () {
            if (!this.value.trim()) {
                results.innerHTML = `
                    <div class="current-location">
                        <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2" id="useCurrentLocation"> Current location </span>  
                    </div>
                `;
                return;
            }
        });
    }
});
    
if(results) {
    results.addEventListener('click', function (e) {
        if (e.target.id === 'useCurrentLocation') {
            getCurrentLocation();
            return;
        }  
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            results.innerHTML = '';
        }
    });
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.innerHTML = '';
        }
    });
}
    
function updateHeading(loc = '') {
    const heading = document.getElementById('locationHeading');
    if (!heading) return;
    if (loc) {        
        heading.textContent = `Events Near ${loc}`;
    } else {
        heading.textContent = '';
    }
}

function updateEventsSection(location) {
    const params = new URLSearchParams();
    if (location.lat)   params.append('lat', location.lat);
    if (location.lng)   params.append('lng', location.lng);
    if (location.startDate)   params.append('startDate', location.startDate);
    if (location.endDate)   params.append('endDate', location.endDate);

    fetch(`/ajax/load-events.php?${params}`)
    .then(res => res.text())
    .then(html => {
        if(html == 'no') {   
            document.getElementById('location-no-results').innerHTML = '<strong>No events available in your selected area</strong><p>Try changing locations or browse through the available events below</p>';
        }else{
            document.getElementById('location-no-results').innerHTML = '';
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const count = temp.querySelectorAll('.performer-event-item').length;
            const countmsg = count > 1 ? ' RESULTS' : ' RESULT';
            document.getElementById('results_count').innerHTML = count + countmsg;
            document.getElementById('eventsSection').innerHTML = html;
        }
        if(input.value !== '') {
            resetBtn.classList.remove('d-none');
            resetBtn.addEventListener('click', function () {
                input.value = '';
                this.classList.add('d-none');       
                document.getElementById('location-no-results').innerHTML = '';
                document.getElementById('locationHeading').innerHTML = '';
                updateEventsSection({
                    startDate: sdateEvent.value,
                    endDate: edateEvent.value
                });
            }); 
        }     
    })
    .catch(err => {
        console.error('Failed to load events', err);
    }); 
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
            getCityState(lat, lng, function(so_cs) {
                input.value = so_cs;
                updateHeading(so_cs);
            }); 
            updateEventsSection({
                lat,
                lng,
                startDate: sdateEvent.value,
                endDate: edateEvent.value
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
        if (selectedDates.length === 2) {
            performerSelectedDatesTemp = selectedDates;
            const startDate = toApiDate(selectStart);
            const endDate   = toApiDate(selectEnd);
            sdateEvent.value = startDate;
            edateEvent.value = endDate;
            updateEventsSection({
                lat: latEvent.value,
                lng: lngEvent.value,
                startDate,
                endDate
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
            instance.close();
            updateEventsSection({
                lat: latEvent.value,
                lng: lngEvent.value
            });
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
    PROMOCODES COPY
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
  
const backToTopBtn = document.getElementById('backToTopJs');
if (backToTopBtn) { 
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

const loadMoreBtn = document.getElementById('loadMoreBtn');
const eventsSection = document.getElementById('eventsSection');
const progressBar = document.getElementById('progressBar');
const loadedCount = document.getElementById('loadedCount');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
        const page = parseInt(this.dataset.page, 10) || 1;
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
            fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&params=${encodeURIComponent(params)}&type=${type || 'all'}`;
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
    </div>`;
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