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
    ARTIST FILTER
===================================================== */
  
const input = document.getElementById('locationInput');
const results = document.getElementById('locationResults');
const resetBtn = document.getElementById('locationInputReset');
const latEvent = document.getElementById('latEvent');
const lngEvent = document.getElementById('lngEvent');
const sdateEvent = document.getElementById('sdateEvent');
const edateEvent = document.getElementById('edateEvent');
const pidEvent = document.getElementById('pidEvent');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const spinner = loadMoreBtn?.querySelector('.btnSpinner');

function getActiveLocation() {
    const lat = latEvent.value || getCookie('so_lat');
    const lng = lngEvent.value || getCookie('so_lng');

    return (lat && lng) ? { lat, lng } : {};
}

document.addEventListener('DOMContentLoaded', async () => {
    if (input) {
        // Google Maps (about 380 KB) is only needed for place autocomplete, so load it when the
        // visitor first touches the location field instead of on every page view.
        let autocompleteReady = null;
        const ensureAutocomplete = () => autocompleteReady || (autocompleteReady = loadGoogleMapsApi().then(() => {
            initLocationSearch('locationInput', 'event');
            if (input.value.trim() && document.activeElement === input) {
                input.dispatchEvent(new Event('input', { bubbles: true }));   // replay what was typed while Maps loaded
            }
        }));
        ['focus', 'pointerdown', 'touchstart'].forEach((evt) => input.addEventListener(evt, ensureAutocomplete, { once: true, passive: true }));

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
                endDate: edateEvent.value,
                pid: pidEvent ? pidEvent.value : ''
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
        if (!input || !results) return;
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
    if (location.pid)   params.append('pid', location.pid);

    fetch(`/ajax/load-events.php?${params}`)
    .then(res => res.json())
    .then(data => {
        
        if (spinner) {
            spinner.classList.add('d-none');
        }

        if (input && input.value !== '') {
            resetBtn.classList.remove('d-none');
            resetBtn.addEventListener('click', function () {
                input.value = '';
                this.classList.add('d-none');       
                document.getElementById('location-no-results').innerHTML = '';
                document.getElementById('locationHeading').innerHTML = '';
                latEvent.value = '';
                lngEvent.value = '';
                updateEventsSection({
                    startDate: sdateEvent.value,
                    endDate: edateEvent.value,
                    pid: pidEvent ? pidEvent.value : ''
                });
            }); 
        }  

        if (!data.events || data.events.length === 0) {
            document.getElementById('location-no-results').innerHTML = '<strong>No events available in your selected area</strong><p>Try changing locations or browse through the available events below</p>';
            return;
        }
        
        document.getElementById('location-no-results').innerHTML = '';
        eventsSection.innerHTML = '';
        
        data.events.forEach(event => {
            eventsSection.insertAdjacentHTML(
                'beforeend',
                renderEvent(event)
            );
        });
        
        const loaded = data.totalCount;
        const countmsg = loaded > 1 ? ' RESULTS' : ' RESULT';
        document.getElementById('results_count').innerHTML = loaded + countmsg;
        document.getElementById('totalCount').textContent = loaded;

        // =========================
        // LOAD MORE BUTTON UPDATE
        // =========================
        
        loadMoreBtn.dataset.page = 2;

        loadMoreBtn.dataset.total = data.totalCount;

        const savedParams = {};
        if (location.lat && location.lng) {
            savedParams.geoFilter = `nearby(${location.lat},${location.lng},50mi)`;
        }
        if (location.startDate && location.endDate) {
            savedParams.filter =
                `date/date ge ${location.startDate} and date/date le ${location.endDate}`;
        } else {
            savedParams.filter =
                `country/alphaCode eq 'US' and date/date ge ${new Date().toISOString().split('T')[0]}`;
        }
        if(location.pid) {
            savedParams.performerFilter = `id eq ${location.pid}`;
        }
        loadMoreBtn.dataset.params = JSON.stringify(savedParams);
        
        // show/hide load more
        if (data.hasMore) {
            loadMoreBtn.classList.remove('d-none');
            backToTopBtn.classList.add('d-none');
        } else {
            loadMoreBtn.classList.add('d-none');
            backToTopBtn.classList.remove('d-none');
        }
        
        // progress
        loadedCount.textContent = data.events.length;

        const percent = data.totalCount > 0
            ? ((data.events.length / data.totalCount) * 100)
            : 0;

        progressBar.style.width = percent + '%';
   
    })
    .catch(err => {
        if (spinner) {
            spinner.classList.add('d-none');
        }
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
                endDate: edateEvent.value,
                pid: pidEvent ? pidEvent.value : ''
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
   
let picker = null;
// The calendar is built on first use (touch/focus of the field), not on every page view:
// creating it up front cost a few hundred ms of main-thread time on mobile.
function getPicker() {
    if (picker) return picker;
    const el = document.getElementById('performerDatePicker');
    if (!el || typeof flatpickr === 'undefined') return null;   // callers load the library first (soLoadFlatpickr)
    picker = flatpickr(el, {
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
                endDate,
                pid: pidEvent ? pidEvent.value : ''
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
            sdateEvent.value = '';
            edateEvent.value = '';
            updateEventsSection({
                lat: latEvent.value,
                lng: lngEvent.value,
                pid: pidEvent ? pidEvent.value : ''
            });
        });
    }
});
    return picker;
}
(function () {
    const el = document.getElementById('performerDatePicker');
    if (!el) return;
    ['pointerdown', 'touchstart', 'focus'].forEach((evt) => el.addEventListener(evt, function () {
        soLoadFlatpickr().then(function () {
            const p = getPicker();
            if (p && document.activeElement === el) p.open();   // the calendar did not exist yet when the visitor tapped
        }).catch(function () {});
    }, { once: true, passive: true }));
})();

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
    ARTIST EVENTS LOADMORE
===================================================== */
  
const backToTopBtn = document.getElementById('backToTopJs');
if (backToTopBtn) { 
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

const eventsSection = document.getElementById('eventsSection');
const progressBar = document.getElementById('progressBar');
const loadedCount = document.getElementById('loadedCount');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
        const page = parseInt(this.dataset.page, 10) || 1;
        const perPage     = this.dataset.perpage;
        const total     = this.dataset.total;
        const type     = this.dataset.type;
        let params = {};
        try {
            params = JSON.parse(this.dataset.params || '{}');
        } catch (e) {
            params = {};
        }
          
        this.disabled = true;
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&params=${encodeURIComponent(JSON.stringify(params))}&type=${type || 'all'}`;
        fetch(fetchUrl)
        .then(res => res.json())
        .then(data => {   
            data.events.forEach(event => {
                eventsSection.insertAdjacentHTML('beforeend', renderEvent(event));
            });

            let loaded = eventsSection.querySelectorAll('.performer-event-item').length;
     
            if (data.hasMore) { 
                loadMoreBtn.dataset.page = data.nextPage;   
                loadMoreBtn.disabled = false;                 
                loadMoreBtn.classList.add('d-none');
            } else {
                loadMoreBtn.classList.add('d-none');
                backToTopBtn.classList.remove('d-none');
            }
            loaded = Math.min(loaded, total);
            const percent = (loaded / total) * 100;
            progressBar.style.width = percent + '%';
            loadedCount.textContent = loaded;
            document.getElementById('totalCount').textContent = loadMoreBtn.dataset.total;
        });
    });
}
  
// Mirrors renderEventPriceTag()/eventDealInfo() in functions.php so rows
// appended by "More Events" look identical to the server-rendered ones.
function buildPriceTag(event) {
    const info = event && event.pricingInfo ? event.pricingInfo : null;
    const none = '<div class="event-price-tag event-price-tag--none">No tickets listed yet</div>';
    if (!info || !info.lowPrice) return none;
    const low = Number(info.lowPrice.value || 0);
    const formatted = info.lowPrice.text && info.lowPrice.text.formatted ? info.lowPrice.text.formatted : '';
    if (!formatted || low <= 0) return none;
    const avg = info.averagePrice ? Number(info.averagePrice.value || 0) : 0;
    const isDeal = avg > 0 && low <= avg * 0.6;
    const safe = String(formatted).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const tix = event._metadata && event._metadata.ticketCount ? Number(event._metadata.ticketCount) : 0;
    const inv = tix > 0 && tix <= 20 ? '<span class="event-low-inv">Only ' + tix + ' listed</span>' : '';
    return '<div class="event-price-tag">' + (isDeal ? '<span class="event-deal-badge">Deal</span> ' : '') + 'From <strong>' + safe + '</strong>' + inv + '</div>';
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
    const eSlug = normalizeKey(event.text.name) + '-' + event.id;
    const cityName = event.city.text.name + ', ' + event.stateProvince.text.abbr;
    const citySlug = normalizeKey(cityName) + '-' + event.city.id;
    const venueSlug = normalizeKey(event.venue.text.name) + '-' + event.venue.id;
    const priceTag = buildPriceTag(event);
    // Everything from the API goes through escHtml before it is put into markup.
    const escHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    const evName = escHtml(event.text.name), evVenue = escHtml(event.venue.text.name), evCityName = escHtml(cityName);
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
                <span class="time-clock">${escHtml(event.date.text.time)}</span>
                <i class="bi bi-info-circle text-muted icon-i" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight" 
                    data-id="${event.id}" data-date="${formattedDate}" data-venue="${evVenue}" 
                    data-location="${evCityName}" data-title="${evName}" data-performers="${escHtml(dataPerformers)}"></i>
            </div>
            <div class="ev-venue"><a href="/venue/${venueSlug}">${evVenue}</a></div>
            <div class="ev-place"><a href="/city/${citySlug}">${evCityName}</a></div>
            <div class="ev-name"><a href="/event/${eSlug}">${evName}</a></div>
        </div>
        <div class="ms-3">
            ${priceTag}
            <a href="/event/${eSlug}" class="btn ${priceTag.indexOf('event-price-tag--none') === -1 ? 'btn-primary' : 'btn-outline-primary'} d-flex align-items-center gap-2" aria-label="${priceTag.indexOf('event-price-tag--none') === -1 ? 'Buy tickets for' : 'View'} ${evName}">
                <span>${priceTag.indexOf('event-price-tag--none') === -1 ? 'Buy Tickets' : 'View Event'}</span>
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
    const performerSlugs = icon.getAttribute('data-performer-slugs');
    const names = eperformers ? eperformers.split('|') : [];
    const slugs = performerSlugs ? performerSlugs.split('|') : [];
    const venueSlug = icon.getAttribute('data-venueSlug');
    
    document.getElementById('offcanvasDate').textContent = edate;
    document.getElementById('offcanvasVenue').innerHTML = evenue;
    document.getElementById('offcanvasLocation').innerHTML = elocation;
    document.getElementById('offcanvasTitle').innerHTML = etitle;
    document.getElementById('offcanvasId').href = '/event/' + normalizeKey(etitle) + '-' + eid;
    const eplist = document.getElementById('offcanvasPerformers');
    eplist.innerHTML = '';
    names.forEach((name, index) => {
        const slug = slugs[index] || '';
    
        const li = document.createElement('li');
    
        li.innerHTML = `
            <a href="/artist/${slug}">
                ${name.trim()}
            </a>
        `;
    
        eplist.appendChild(li);
    });
    document.getElementById('venue-link').innerHTML = evenue;
    document.getElementById('venue-link').href = '/venue/' + venueSlug;
});
  
/* =====================================================
    ARTIST EVENTS POPUP End
===================================================== */