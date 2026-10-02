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
// The list, its footer and its buttons can be replaced when a filter chip refreshes the page body, so they are looked up on use.
const soById = (id) => document.getElementById(id);
const soSpinner = () => soById('btnSpinner') || soById('loadMoreBtn')?.querySelector('.btnSpinner');
// Today as the visitor's own calendar date (toISOString() is UTC and rolls over in the afternoon in the US).
const soTodayIso = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };

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

    const noResults = soById('location-no-results');
    fetch(`/ajax/load-events.php?${params}`)
    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
    .then(data => {
        const spinner = soSpinner();
        if (spinner) {
            spinner.classList.add('d-none');
        }

        if (input && input.value !== '' && resetBtn) {
            resetBtn.classList.remove('d-none');
            resetBtn.onclick = function () {
                input.value = '';
                this.classList.add('d-none');
                if (noResults) noResults.innerHTML = '';
                const heading = soById('locationHeading');
                if (heading) heading.innerHTML = '';
                latEvent.value = '';
                lngEvent.value = '';
                updateEventsSection({
                    startDate: sdateEvent.value,
                    endDate: edateEvent.value,
                    pid: pidEvent ? pidEvent.value : ''
                });
            };
        }

        if (!data.events || data.events.length === 0) {
            if (noResults) noResults.innerHTML = '<strong>No events available in your selected area</strong><p>Try changing locations or browse through the available events below</p>';
            return;
        }

        if (noResults) noResults.innerHTML = '';
        const list = soById('eventsSection');
        if (!list) return;
        list.innerHTML = '';
        // Filtered results are a different set of rows: the weekend chips no longer apply.
        document.querySelectorAll('[data-so-weekends]').forEach(function (b) { b.hidden = true; });

        soAppendRows(list, data);

        const loaded = data.totalCount;
        const countmsg = loaded > 1 ? ' RESULTS' : ' RESULT';
        const rc = soById('results_count');
        if (rc) rc.textContent = loaded + countmsg;
        const tc = soById('totalCount');
        if (tc) tc.textContent = loaded;

        // =========================
        // LOAD MORE BUTTON UPDATE
        // =========================
        const loadMoreBtn = soById('loadMoreBtn');
        const backToTopBtn = soById('backToTopJs');
        if (!loadMoreBtn) return;
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
                `country/alphaCode eq 'US' and date/date ge ${soTodayIso()}`;
        }
        if(location.pid) {
            savedParams.performerFilter = `id eq ${location.pid}`;
        }
        loadMoreBtn.dataset.params = JSON.stringify(savedParams);

        // show/hide load more
        if (data.hasMore) {
            loadMoreBtn.classList.remove('d-none');
            loadMoreBtn.disabled = false;
            if (backToTopBtn) backToTopBtn.classList.add('d-none');
        } else {
            loadMoreBtn.classList.add('d-none');
            if (backToTopBtn) backToTopBtn.classList.remove('d-none');
        }
        soSetProgress(list, data.totalCount);
    })
    .catch(err => {
        const spinner = soSpinner();
        if (spinner) {
            spinner.classList.add('d-none');
        }
        if (noResults) noResults.innerHTML = '<strong>Could not load events right now</strong><p>Please check your connection and try again.</p>';
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
  
// Number of events the rows in a list stand for (a festival card with three days counts three).
function soLoadedEvents(list) {
    let n = 0;
    list.querySelectorAll('.performer-event-item').forEach((row) => { n += parseInt(row.getAttribute('data-so-count') || '1', 10) || 1; });
    return n;
}

function soSetProgress(list, total) {
    const loaded = Math.min(soLoadedEvents(list), total || soLoadedEvents(list));
    const bar = soById('progressBar'), lc = soById('loadedCount'), tc = soById('totalCount');
    if (bar) bar.style.width = (total > 0 ? Math.min(100, (loaded / total) * 100) : 0) + '%';
    if (lc) lc.textContent = loaded;
    if (tc) tc.textContent = total;
}

// Rows come back as ready-made HTML from the server (the same markup as the first page: grouping, escaping, data attributes);
// the browser-side renderEvent() below is only the fallback when a response has no html.
function soAppendRows(list, data) {
    if (data && typeof data.html === 'string' && data.html.trim() !== '') {
        list.insertAdjacentHTML('beforeend', data.html);
    } else {
        (data.events || []).forEach(event => list.insertAdjacentHTML('beforeend', renderEvent(event)));
    }
}

function soLoadMoreError(show) {
    const err = soById('loadMoreError');
    if (err) err.hidden = !show;
}

function soLoadMore(btn) {
    if (btn.disabled) return;
    const list = soById('eventsSection');
    if (!list) return;
    const page = parseInt(btn.dataset.page, 10) || 1;
    const perPage = btn.dataset.perpage;
    const total = parseInt(btn.dataset.total, 10) || 0;
    const type = btn.dataset.type;
    let params = {};
    try {
        params = JSON.parse(btn.dataset.params || '{}');
    } catch (e) {
        params = {};
    }

    btn.disabled = true;
    soLoadMoreError(false);
    const spinner = soSpinner();
    if (spinner) spinner.classList.remove('d-none');
    const fetchUrl = `/ajax/load-more-events.php?page=${page}&perPage=${perPage}&params=${encodeURIComponent(JSON.stringify(params))}&type=${type || 'all'}`;
    fetch(fetchUrl)
    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
    .then(data => {
        if (spinner) spinner.classList.add('d-none');
        const got = (data.events || []).length;
        // An empty page while more events are still promised is a failed load (throttled or degraded API), not the end of the list.
        if (got === 0 && soLoadedEvents(list) < total) throw new Error('empty page');
        soAppendRows(list, data);
        const back = soById('backToTopJs');
        if (data.hasMore) {
            btn.dataset.page = data.nextPage;
            btn.disabled = false;
        } else {
            btn.classList.add('d-none');
            if (back) back.classList.remove('d-none');
        }
        soSetProgress(list, total);
    })
    .catch(err => {
        if (spinner) spinner.classList.add('d-none');
        btn.disabled = false;
        soLoadMoreError(true);
        console.warn('More events failed', err);
    });
}

document.addEventListener('click', function (e) {
    if (e.target.closest('#backToTopJs')) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    const more = e.target.closest('#loadMoreBtn');
    if (more) { soLoadMore(more); return; }
    if (e.target.closest('#loadMoreRetry')) {
        const b = soById('loadMoreBtn');
        if (b) soLoadMore(b);
    }
});

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

// Browser-side fallback for a row (the server normally sends the finished HTML, see soAppendRows). Same markup and data
// attributes as inc/listing.php; every API string goes through escHtml.
function renderEvent(event) {
    const escHtml = (v) => String(v ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    const evtdate = new Date(String(event.date.date).slice(0, 10) + 'T12:00:00');
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
    const performers = (event.performers || []).filter(p => p && p.name);
    const dataPerformers = performers.map(p => p.name).join('|');
    const dataPerformerSlugs = performers.map(p => p.id ? normalizeKey(p.name) + '-' + p.id : '').join('|');
    const eSlug = normalizeKey(event.text.name) + '-' + event.id;
    const cityName = event.city.text.name + ', ' + event.stateProvince.text.abbr;
    const citySlug = normalizeKey(cityName) + '-' + event.city.id;
    const venueSlug = normalizeKey(event.venue.text.name) + '-' + event.venue.id;
    const priceTag = buildPriceTag(event);
    const inStock = priceTag.indexOf('event-price-tag--none') === -1;
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
                <span class="dot">&middot;</span>
                <span class="time-clock">${escHtml(event.date.text.time)}</span>
                <button type="button" class="bi bi-info-circle text-muted icon-i" aria-label="Details for ${evName}" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"
                    data-id="${escHtml(event.id)}" data-date="${escHtml(formattedDate)}" data-venue="${evVenue}" data-venue-slug="${escHtml(venueSlug)}"
                    data-location="${evCityName}" data-title="${evName}" data-performers="${escHtml(dataPerformers)}" data-performer-slugs="${escHtml(dataPerformerSlugs)}"></button>
            </div>
            <div class="ev-venue"><a href="/venue/${escHtml(venueSlug)}">${evVenue}</a></div>
            <div class="ev-place"><a href="/city/${escHtml(citySlug)}">${evCityName}</a></div>
            <div class="ev-name"><a href="/event/${escHtml(eSlug)}">${evName}<span class="visually-hidden"> tickets, ${escHtml(emonth)} ${d} at ${evVenue}, ${evCityName}</span></a></div>
        </div>
        <div class="ms-3">
            ${priceTag}
            <a href="/event/${escHtml(eSlug)}" class="btn ${inStock ? 'btn-primary' : 'btn-outline-primary'} d-flex align-items-center gap-2" aria-label="${inStock ? 'Buy tickets for' : 'View'} ${evName}">
                <span>${inStock ? 'Buy Tickets' : 'View Event'}</span>
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
    const evenue = icon.getAttribute('data-venue') || '';
    const elocation = icon.getAttribute('data-location') || '';
    const etitle = icon.getAttribute('data-title') || '';
    const eperformers = icon.getAttribute('data-performers');
    const performerSlugs = icon.getAttribute('data-performer-slugs');
    const names = eperformers ? eperformers.split('|') : [];
    const slugs = performerSlugs ? performerSlugs.split('|') : [];
    // Older rows spell the attribute data-venueSlug (HTML lower-cases it); the shared renderer uses data-venue-slug.
    const venueSlug = icon.getAttribute('data-venue-slug') || icon.getAttribute('data-venueslug') || '';
    
    // Everything from the catalog is placed with textContent, never as markup.
    document.getElementById('offcanvasDate').textContent = edate || '';
    document.getElementById('offcanvasVenue').textContent = evenue;
    document.getElementById('offcanvasLocation').textContent = elocation;
    document.getElementById('offcanvasTitle').textContent = etitle;
    document.getElementById('offcanvasId').href = '/event/' + normalizeKey(etitle) + '-' + eid;
    const eplist = document.getElementById('offcanvasPerformers');
    eplist.textContent = '';
    names.forEach((name, index) => {
        const slug = slugs[index] || '';
        if (!name.trim()) return;
        const li = document.createElement('li');
        if (slug) {
            const a = document.createElement('a');
            a.href = '/artist/' + slug;
            a.textContent = name.trim();
            li.appendChild(a);
        } else {
            li.textContent = name.trim();
        }
        eplist.appendChild(li);
    });
    const venueLink = document.getElementById('venue-link');
    venueLink.textContent = evenue;
    if (venueSlug) {
        venueLink.href = '/venue/' + venueSlug;
        venueLink.removeAttribute('aria-disabled');
    } else {
        venueLink.removeAttribute('href');
        venueLink.setAttribute('aria-disabled', 'true');
    }
});
  
/* =====================================================
    ARTIST EVENTS POPUP End
===================================================== */
/* Date tile + "Today / Tomorrow / This weekend" pill on every listing row (server rows and rows added by "load more").
   Pure presentation: the date comes from the tile that is already printed (month, day, and the year when it is not this
   year), so there is nothing to keep in sync with the server and nothing is claimed that is not on the row. */
(function () {
  var MONTHS = { JAN: 0, FEB: 1, MAR: 2, APR: 3, MAY: 4, JUN: 5, JUL: 6, AUG: 7, SEP: 8, OCT: 9, NOV: 10, DEC: 11 };

  function enhance(row) {
    if (row.getAttribute('data-so-date')) return;
    var box = row.querySelector('.date-box');
    if (!box) return;
    var months = box.querySelectorAll('.month');
    var dayEl = box.querySelector('.day');
    if (!months.length || !dayEl) return;
    var mon = MONTHS[months[0].textContent.trim().toUpperCase().slice(0, 3)];
    var day = parseInt(dayEl.textContent, 10);
    if (mon === undefined || isNaN(day)) return;
    var shownYear = months.length > 1 ? parseInt(months[1].textContent, 10) : 0;
    var now = new Date(); now.setHours(0, 0, 0, 0);
    var year = shownYear || now.getFullYear();
    var dt = new Date(year, mon, day);
    if (!shownYear && dt < now) dt = new Date(year + 1, mon, day);
    row.setAttribute('data-so-date', '1');
    box.insertAdjacentHTML('beforeend', '<div class="wd">' + dt.toLocaleDateString('en-US', { weekday: 'short' }) + '</div>');
    var days = Math.round((dt - now) / 86400000), label = '';
    if (days === 0) label = 'Today';
    else if (days === 1) label = 'Tomorrow';
    else if (days > 1 && days <= 6 && (dt.getDay() === 6 || dt.getDay() === 0)) label = 'This weekend';
    if (label) {
      var host = row.querySelector('.flex-grow-1');
      if (host) host.insertAdjacentHTML('beforeend', '<div class="so-when"><span class="so-when__pill">' + label + '</span></div>');
    }
  }

  function enhanceAll(scope) { (scope || document).querySelectorAll('.performer-event-item').forEach(enhance); }
  enhanceAll();
  // The list body is also swapped as a whole when a filter chip changes, so watch the container and its subtree.
  var list = document.querySelector('.list-category-bg') || document.getElementById('eventsSection');
  if (list && window.MutationObserver) new MutationObserver(function () { enhanceAll(list); }).observe(list, { childList: true, subtree: true });
})();


/* "Weekend 1 / Weekend 2" chips (multi-day festivals only; the server prints them): show one weekend's dates at a time.
   Without JavaScript every date stays visible. */
(function () {
  var bar = document.querySelector('[data-so-weekends]');
  var list = document.getElementById('eventsSection');
  if (!bar || !list) return;
  var buttons = bar.querySelectorAll('[data-wk]');
  function show(wk) {
    buttons.forEach(function (b) {
      var on = b.getAttribute('data-wk') === wk;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    list.querySelectorAll('.performer-event-item').forEach(function (row) {
      var rw = row.getAttribute('data-wk');
      row.hidden = !!rw && rw !== wk;
    });
  }
  buttons.forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-wk')); }); });
  show('1');
})();


/* Filter dropdown chips (When, Sort): only one open at a time, closed by a click elsewhere or Escape. */
(function () {
  var all = document.querySelectorAll('.so-filterbar .so-dd');
  if (!all.length) return;
  all.forEach(function (d) {
    d.addEventListener('toggle', function () { if (d.open) all.forEach(function (o) { if (o !== d) o.open = false; }); });
  });
  document.addEventListener('click', function (e) { all.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') all.forEach(function (d) { d.open = false; }); });
})();
