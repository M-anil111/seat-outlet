const DOM = {
    locationSelectorText: document.getElementById('locationSelectorText'),
    keywordResultsHeader: document.getElementById('keywordResultsHeader'),
    keywordHeader: document.getElementById('keywordHeader'),
    locationPanel: document.getElementById('locationPanel'),
    inputHeader: document.getElementById('locationInputHeader'),
    resultsHeader: document.getElementById('locationResultsHeader'),
    latHeader: document.getElementById('latHeader'),
    lngHeader: document.getElementById('lngHeader'),
    resetLocHeader: document.getElementById('locationHeaderReset'),
    startInputHeader: document.getElementById('startInputHeader'),
    endInputHeader: document.getElementById('endInputHeader'),
    searchLoader: document.getElementById('search-loader')
};

function getMonthCount() {
    return window.innerWidth <= 689 ? 1 : 2;
}

function normalizeKey(v) {
    return v.toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');   
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
    // Cut at the FIRST "=" only: a value may contain "=" itself (split('=')[1] used to truncate it).
    const prefix = name + '=';
    const row = document.cookie.split('; ').find(r => r.startsWith(prefix));
    if (!row) return '';
    const raw = row.slice(prefix.length);
    try { return decodeURIComponent(raw); } catch (e) { return raw; }
}

function setCookie(name, value) {
    // Values are always stored URL-encoded, so text with ";" or "=" or a comma cannot corrupt the cookie. Callers that already
    // encoded their value are fine: it is decoded once first, then encoded once, never twice.
    let v = String(value == null ? '' : value);
    try { v = decodeURIComponent(v); } catch (e) { /* a literal "%": keep as typed */ }
    // Lax + Secure (on https) + 30-day expiry; these were session cookies with no SameSite flag.
    const secure = location.protocol === 'https:' ? ';Secure' : '';
    // A location cookie lasts a day, not 30: a wrong lookup, a VPN or a trip must not stay selected for a month.
    const maxAge = /^so_(lat|lng|label)$/.test(name) ? 86400 : 2592000;
    document.cookie = name + '=' + encodeURIComponent(v) + ';path=/;max-age=' + maxAge + ';SameSite=Lax' + secure;
}

/* Paints the visitor's location on the home page "Top picks" heading and its location chip.
   label: a place name, '' (nothing known: "Top picks across the US" + "Set location"), or null with state 'finding'. */
function soSetLocText(label, state) {
    const chip = document.getElementById('locationSelectorText');
    const title = document.getElementById('topPicksTitle');
    const caret = ' <i class="bi bi-chevron-down" aria-hidden="true"></i>';
    if (chip) {
        if (state === 'finding') chip.innerHTML = 'Finding your location...' + caret;
        else if (label) chip.innerHTML = 'Near ' + escapeHtml(label) + caret;
        else chip.innerHTML = 'Set location' + caret;
    }
    if (title) title.textContent = (label || state === 'finding') ? 'Top picks' : 'Top picks across the US';
}

function equalHeightSlider(sectionClass, cardClass) {
    if (!window.jQuery) return;   // only the pages that load jQuery (search) have this slider
    var $ = window.jQuery;
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
        const place = autocomplete.getPlace();
        if (!place || !place.geometry) return;
        let city = '';
        let state = '';
        place.address_components.forEach(component => {
            const types = component.types;
            if (types.includes('locality')) {
                city = component.long_name;
            }
            if (types.includes('administrative_area_level_1')) {
                state = component.short_name;
            }        
        });
        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();  
        if(type == 'home') {   
            soSetLocText(input.value);
            if (DOM.locationPanel) DOM.locationPanel.classList.remove('show');  
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
            }, 200);
        }else if(type == 'event') {
            const resetBtn = document.getElementById('locationInputReset');
            const locRes = document.getElementById('locationResults');
            const locHdg = document.getElementById('locationHeading');
            const locNoRes = document.getElementById('location-no-results');
            const latEvent = document.getElementById('latEvent');
            const lngEvent = document.getElementById('lngEvent');
            const sdateEvent = document.getElementById('sdateEvent');
            const edateEvent = document.getElementById('edateEvent');
            const pidEvent = document.getElementById('pidEvent');
            setCookie('so_label', input.value);
            setCookie('so_lat', lat);
            setCookie('so_lng', lng);  
            if (input.value !== '') {                
                resetBtn.classList.remove('d-none');
                locRes.classList.add('d-none');
            }
            resetBtn.onclick = function () {
                input.value = '';
                resetBtn.classList.add('d-none');
                locRes.classList.remove('d-none');    
                locHdg.innerHTML = '';
                locNoRes.innerHTML = '';    
                updateEventsSection({
                    flag: 'reset'
                });
            };
        
            latEvent.value = lat;
            lngEvent.value = lng;
            updateHeading(input.value);
            updateEventsSection({
                lat,
                lng,
                startDate: sdateEvent.value,
                endDate: edateEvent.value,
                pid: pidEvent ? pidEvent.value : ''
            });
        }else{
            DOM.latHeader.value = lat;
            DOM.lngHeader.value = lng;  
            if(input.value !== '') {
                if (DOM.resetLocHeader.classList.contains('d-none')) {
                    DOM.resetLocHeader.classList.remove('d-none');
                }
                DOM.resetLocHeader.onclick = function () {
                    input.value = '';
                    this.classList.add('d-none');
                    DOM.latHeader.value = '';
                    DOM.lngHeader.value = '';
                };         
            } 
        }
    });  
}

document.addEventListener('DOMContentLoaded', function () {
    const savedLat = getCookie('so_lat');
    const savedLng = getCookie('so_lng');
    const savedLabel = getCookie('so_label');  
    if (savedLabel) {
        soSetLocText(savedLabel);
    }
    // Header search: show the saved place as a hint in the location field (it is applied only when the visitor taps it).
    if (savedLabel && DOM.inputHeader && !DOM.inputHeader.value) {
        DOM.inputHeader.placeholder = 'Near ' + savedLabel;
    }
  
    if (savedLat && savedLng) {
        window.locationReady = true;
        if (typeof reloadActiveTab === 'function') {
            reloadActiveTab('ll', { lat: savedLat, lng: savedLng });
        }
        if (typeof loadNearbyVenues === 'function') {
            loadNearbyVenues();
        }
        return;
    }
  
    const locationLabel = DOM.locationSelectorText;
    const showPrompt = () => {
        soSetLocText('');
        window.locationReady = true;
        document.dispatchEvent(new CustomEvent('so:location', { detail: { lat: '', lng: '', label: '' } }));
        if (typeof reloadActiveTab === 'function') {
            reloadActiveTab('', {});
        }
        if (typeof loadNearbyVenues === 'function') {
            loadNearbyVenues();
        }
    };
    const applyLocation = (lat, lng, label, labelCookie) => {
        setCookie('so_lat', encodeURIComponent(lat));
        setCookie('so_lng', encodeURIComponent(lng));
        setCookie('so_label', labelCookie || label);
        soSetLocText(label);
        if (DOM.inputHeader && !DOM.inputHeader.value) DOM.inputHeader.placeholder = 'Near ' + label;
        window.locationReady = true;
        document.dispatchEvent(new CustomEvent('so:location', { detail: { lat: lat, lng: lng, label: label } }));
        if (typeof reloadActiveTab === 'function') {
            reloadActiveTab('ll', { lat: lat, lng: lng });
        }
        if (typeof loadNearbyVenues === 'function') {
            loadNearbyVenues();
        }
    };
    // Second chance when the network lookup has no answer: use the device location only if the visitor has ALREADY allowed
    // it for this site. The page never opens the browser's permission prompt by itself (like the big marketplaces, it
    // starts from the visitor's address); the "Current location" button in the location menu is the only way to ask.
    const tryAllowedDeviceLocation = () => {
        if (!navigator.geolocation || !navigator.permissions || !navigator.permissions.query) { showPrompt(); return; }
        navigator.permissions.query({ name: 'geolocation' }).then(state => {
            if (state.state !== 'granted') { showPrompt(); return; }
            navigator.geolocation.getCurrentPosition(async position => {
                const lat = position.coords.latitude, lng = position.coords.longitude;
                try {
                    await loadGoogleMapsApi();
                    getCityState(lat, lng, label => applyLocation(lat, lng, label));
                } catch (e) { showPrompt(); }
            }, showPrompt, { timeout: 8000, maximumAge: 600000 });
        }).catch(showPrompt);
    };

    soSetLocText(null, 'finding');
    // If nothing has answered after a few seconds (for example the permission prompt is still open), go back to the plain prompt.
    setTimeout(() => { if (locationLabel && /^Finding/.test(locationLabel.textContent.trim())) showPrompt(); }, 4000);
    fetch('/ajax/get_ip_details.php')
    .then(res => res.json())
    .then(data => {
        // The lookup can legitimately come back empty (visitor's address not in the database, no location headers from
        // the CDN): never save or show a half-empty "undefined, undefined" label, try the allowed device location instead.
        if (!data || !data.city || !data.state || !data.lat || !data.lng) {
            tryAllowedDeviceLocation();
            return;
        }
        applyLocation(data.lat, data.lng, data.city + ', ' + data.state);
    }).catch(tryAllowedDeviceLocation);
});

/* =====================================================
    HEADER LOCATION FIELD
===================================================== */
  
if(DOM.resetLocHeader && DOM.inputHeader.value) {
    DOM.resetLocHeader.classList.remove('d-none');
    DOM.resetLocHeader.addEventListener('click', function () {
        DOM.inputHeader.value = '';
        DOM.resetLocHeader.classList.add('d-none');       
        DOM.latHeader.value = '';
        DOM.lngHeader.value = '';
    });  
}
  
if (DOM.inputHeader) {
    DOM.inputHeader.addEventListener('focus', async function once() {
        await loadGoogleMapsApi();
        initLocationSearch('locationInputHeader');
        DOM.inputHeader.removeEventListener('focus', once);
    }, { once: true });

    DOM.inputHeader.addEventListener('click', function () {
        const q = this.value.trim();
        if (q.length === 0) {
            const savedPlace = getCookie('so_label'), sLat = getCookie('so_lat'), sLng = getCookie('so_lng');
            DOM.resultsHeader.innerHTML = (savedPlace && sLat && sLng ? `
                <div class="current-location">
                    <i class="bi bi-geo-alt ms-1 me-2"></i> <span class="ms-4 ps-2" id="useSavedLocationHeader" role="button" tabindex="0"></span>
                </div>` : '') + `
                <div class="current-location">
                    <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2" id="useCurrentLocationHeader"> Current location </span>  
                </div>
            `;
            const sp = document.getElementById('useSavedLocationHeader');
            if (sp) sp.textContent = 'Near ' + savedPlace;
            return;
        }
    });
}

if(DOM.resultsHeader) {
    DOM.resultsHeader.addEventListener('click', function (e) {  
        if (e.target.id === 'useCurrentLocationHeader') {
            getCurrentLocationHeader();
            return;
        }
        if (e.target.id === 'useSavedLocationHeader') {
            // One tap: use the place the site already knows (the clear button next to the field removes it again).
            DOM.inputHeader.value = getCookie('so_label');
            DOM.latHeader.value = getCookie('so_lat');
            DOM.lngHeader.value = getCookie('so_lng');
            DOM.resultsHeader.innerHTML = '';
            if (DOM.resetLocHeader) {
                DOM.resetLocHeader.classList.remove('d-none');
                DOM.resetLocHeader.onclick = function () { DOM.inputHeader.value = ''; this.classList.add('d-none'); DOM.latHeader.value = ''; DOM.lngHeader.value = ''; };
            }
            return;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            DOM.resultsHeader.innerHTML = '';
        }
    });

    document.addEventListener('click', function (e) {
        if (!DOM.inputHeader.contains(e.target) && !DOM.resultsHeader.contains(e.target)) {
            DOM.resultsHeader.innerHTML = '';
        }
    });
}
  
function getCurrentLocationHeader() {
    if (!navigator.geolocation) {
        alert('Geocoding is not supported by your browser');
        return;
    }

    DOM.resultsHeader.innerHTML = '';
    DOM.inputHeader.value = 'Detecting location...';
    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            DOM.latHeader.value = lat;
            DOM.lngHeader.value = lng;
            getCityState(lat, lng, function(so_cs) {
                DOM.inputHeader.value = so_cs;
            }); 
  
            if(DOM.inputHeader.value !== '') {
                DOM.resetLocHeader.classList.remove('d-none');
                DOM.resetLocHeader.addEventListener('click', function () {
                    DOM.inputHeader.value = '';
                    this.classList.add('d-none');       
                    DOM.latHeader.value = '';
                    DOM.lngHeader.value = '';
                });            
            }
        },
        () => {
            DOM.inputHeader.value = '';
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
 
/* =====================================================
    DATE PICKER LIBRARY (loaded on demand)
===================================================== */
// flatpickr (50 KB script + 16 KB CSS) is only needed once someone touches a date field, so it is not on every page load.
// Both files are prefetched on the first interaction (below); this loads them (from the cache by then) and resolves when ready.
(function () {
    var warmed = false;
    function warm() {
        if (warmed) return;
        warmed = true;
        ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach(function (t) { window.removeEventListener(t, warm); });
        var assets = window.SO_ASSETS || {};
        [[assets.flatpickrJs, 'script'], [assets.flatpickrCss, 'style']].forEach(function (a) {
            if (!a[0]) return;
            var l = document.createElement('link');
            l.rel = 'prefetch'; l.as = a[1]; l.href = a[0];
            document.head.appendChild(l);
        });
    }
    // On the visitor's first touch, scroll or key press (not on page load): a page that is only being read, measured or crawled never
    // requests the date picker files, and an early prefetch would also compete with the page itself.
    ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach(function (t) { window.addEventListener(t, warm, { passive: true }); });
})();
window.soLoadFlatpickr = (function () {
    var pending = null;
    return function () {
        if (window.flatpickr) return Promise.resolve(window.flatpickr);
        if (pending) return pending;
        var assets = window.SO_ASSETS || {};
        pending = new Promise(function (resolve, reject) {
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = assets.flatpickrCss || '/lib/flatpickr/4.6.13/flatpickr.min.css';
            document.head.appendChild(css);
            var js = document.createElement('script');
            js.src = assets.flatpickrJs || '/lib/flatpickr/4.6.13/flatpickr.min.js';
            js.async = true;
            js.onload = function () { resolve(window.flatpickr); };
            js.onerror = function () {
                // One retry (a different address, so a failed answer is not reused) before giving up.
                var again = document.createElement('script');
                again.src = js.src + (js.src.indexOf('?') === -1 ? '?r=1' : '&r=1');
                again.async = true;
                again.onload = function () { resolve(window.flatpickr); };
                again.onerror = function () { pending = null; reject(new Error('flatpickr failed to load')); };
                setTimeout(function () { document.head.appendChild(again); }, 800);
            };
            document.head.appendChild(js);
        });
        return pending;
    };
})();

document.addEventListener("DOMContentLoaded", function () {
    let selectedDatesTemp = [];  
    let selectedStart = null;
    let selectedEnd = null;
    const dateEl = document.getElementById('customDatePicker');
    if (!dateEl) return;
    let fp = null;
    // Built on first touch/focus of the field rather than on every page view (it is in the header of every page).
    function getFp() {
      if (fp) return fp;
      fp = flatpickr(dateEl, {
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
                DOM.startInputHeader.value = startDate;
                DOM.endInputHeader.value = endDate;
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
                DOM.startInputHeader.value = '';
                DOM.endInputHeader.value = '';
                instance.clear();
            });
        }
      });
      return fp;
    }
    // Fallback when the calendar library cannot be loaded: the browser's own date fields (From, To) in a small panel under the field.
    function nativeDateFallback() {
        if (dateEl.getAttribute('data-fallback')) { var open = document.getElementById('soDateFallback'); if (open) open.hidden = false; return; }
        dateEl.setAttribute('data-fallback', '1');
        var today = new Date(), iso = function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
        var panel = document.createElement('div');
        panel.id = 'soDateFallback'; panel.className = 'so-date-fallback'; panel.setAttribute('role', 'group'); panel.setAttribute('aria-label', 'Choose dates');
        panel.innerHTML = '<label>From <input type="date" id="soDateFrom" min="' + iso(today) + '"></label><label>To <input type="date" id="soDateTo" min="' + iso(today) + '"></label><button type="button" class="so-date-fallback__done">Done</button>';
        (dateEl.closest('.search-item') || dateEl.parentNode).appendChild(panel);
        var from = panel.querySelector('#soDateFrom'), to = panel.querySelector('#soDateTo');
        function apply() {
            if (from.value && to.value && to.value < from.value) to.value = from.value;
            if (DOM.startInputHeader) DOM.startInputHeader.value = from.value;
            if (DOM.endInputHeader) DOM.endInputHeader.value = to.value || from.value;
            dateEl.value = from.value ? (from.value + (to.value && to.value !== from.value ? ' to ' + to.value : '')) : '';
        }
        from.addEventListener('change', apply); to.addEventListener('change', apply);
        panel.querySelector('.so-date-fallback__done').addEventListener('click', function () { apply(); panel.hidden = true; });
        panel.hidden = false;
    }
    ['pointerdown', 'touchstart', 'focus'].forEach(function (evt) {
        dateEl.addEventListener(evt, function () {
            soLoadFlatpickr().then(function () {
                const f = getFp();
                if (f && document.activeElement === dateEl) f.open();   // the calendar did not exist yet when the visitor tapped
            }).catch(function () { nativeDateFallback(); });   // the calendar files did not load: two plain date fields instead of a dead field
        }, { once: true, passive: true });
    });
  
    window.addEventListener("resize", function () {
        if (!fp) return;
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

if (DOM.keywordHeader && DOM.keywordResultsHeader) {
    let typingTimer = null;
    let currentController = null;
    let activeIndex = -1;
    const typingDelay = 150;
    const minChars = 2;
    let lastQuery = '';
    let lastResponse = null;
    const searchCache = {};
    function normalize(q) {
        return q
        .toLowerCase()
        .replace(/,/g, '')
        .replace(/\s+/g, ' ')
        .trim();
    }
  
    function showLoader() {
        // Only while the search box is actually on screen: when the header search is collapsed the spinner has no
        // parent to sit in and used to float under the logo.
        if (DOM.searchLoader && DOM.keywordHeader && DOM.keywordHeader.offsetParent !== null) DOM.searchLoader.style.display = 'block';
    }
  
    function hideLoader() {
        if (DOM.searchLoader) DOM.searchLoader.style.display = 'none';
    }
  
    function closeSuggestions() {
        activeIndex = -1;
        DOM.keywordResultsHeader.innerHTML = '';
        DOM.keywordResultsHeader.style.display = 'none';
        DOM.keywordHeader.setAttribute('aria-expanded', 'false');
        DOM.keywordHeader.removeAttribute('aria-activedescendant');
        hideLoader();
    }
  
    function showSuggestions() {
        DOM.keywordHeader.setAttribute('aria-expanded', 'true');
        DOM.keywordResultsHeader.style.display = 'block';
    }
  
    // Slugs come from the server (no ids in URLs). The name-and-id form is only the fallback for an answer without a slug; the server
    // understands it and redirects to the clean URL.
    function createSlug(name, id, slug) {
        if (slug) return slug;
        return `${name}-${id}`
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-');
    }
  
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
        const dym = Array.isArray(data?.didYouMean) ? data.didYouMean : [];
        const hasMatches = (performers.totalResultCount || 0) || (cities.totalResultCount || 0) || (venues.totalResultCount || 0);
        if (dym.length && hasMatches) {
            // Close names first when no performer matched but other things did
            // (e.g. "carot top" only matches unrelated venues).
            html += '<li class="suggestion-label">Did you mean</li>';
            dym.forEach((item, i) => {
                html += `<li class="result-item" id="suggestion-dym-${i}" role="option"><a href="${escapeHtml(item.url)}">${escapeHtml(item.name)}</a></li>`;
            });
        }
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
                        <a href="/artist/${createSlug(item.name, item.id, item.slug)}">
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
                        <a href="/city/${createSlug(item.name, item.id, item.slug)}">
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
                        <a href="/venue/${createSlug(item.name, item.id, item.slug)}">
                        ${escapeHtml(item.name)}
                        </a>
                    </li>`;
                    s++;
                });
            }    
        } else if (Array.isArray(data?.didYouMean) && data.didYouMean.length) {
            html += `<li class="suggestion-label">Did you mean</li>`;
            data.didYouMean.forEach((item, i) => {
                html += `
                <li class="result-item" id="suggestion-${i}" role="option">
                    <a href="${escapeHtml(item.url)}">${escapeHtml(item.name)}</a>
                </li>`;
            });
        } else {
            html += `<li class="result-item">No results for "${escapeHtml(q)}"</li>`;
        }
  
        html += '</ul>';  
        if (DOM.keywordResultsHeader.innerHTML !== html) {
            DOM.keywordResultsHeader.innerHTML = html;
        }
        showSuggestions();
        activeIndex = -1;
    }
  
    function fetchSuggestions(q) {  
        if (q.length < minChars) {
            closeSuggestions();
            return;
        }
  
        if (searchCache[q]) {
            hideLoader();
            renderResults(searchCache[q], q);
            return;
        }
  
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
            if (normalize(DOM.keywordHeader.value) !== q) return;    
            renderResults(data, q);
        })
        .catch(err => {
            hideLoader();
            if (err.name !== 'AbortError') console.error(err);
        });
    }
  
    DOM.keywordHeader.addEventListener('input', function () {  
        const q = normalize(this.value);
        sessionStorage.setItem('last_search', q);
        clearTimeout(typingTimer);
        if (q.length < minChars) {
            closeSuggestions();
            return;
        }  
        fetchSuggestions(q);
        typingTimer = setTimeout(() => {
            fetchSuggestions(q);
        }, typingDelay);
    });
  
    DOM.keywordHeader.addEventListener('keydown', function (e) {
        const items = DOM.keywordResultsHeader.querySelectorAll('.result-item a');
        if (e.key === 'Escape') {
            closeSuggestions();
            return;
        }  
        if (!items.length) return;  
        // Arrow keys move through the suggestions. Tab is NOT used for that: it moves on to the next control and closes the list
        // (a keyboard user could not leave the field while any suggestion was showing).
        if (e.key === 'Tab') {
            closeSuggestions();
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            updateActive(items);
        }  
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            updateActive(items);
        }  
        if (e.key === 'Enter') {
            if (activeIndex >= 0 && items[activeIndex]) {
            e.preventDefault();
            if (window.soTrack) {
                const row = items[activeIndex].parentElement;
                let group = '', n = 0;
                Array.prototype.forEach.call(row.parentElement.children, function (c, i) { if (c.classList.contains('suggestion-label') && i < Array.prototype.indexOf.call(row.parentElement.children, row)) group = c.textContent; if (c === row) n = i; });
                window.soTrack('suggestion_click', { suggestion_group: group.toLowerCase().replace(/\s+/g, '_'), suggestion_position: n + 1, via: 'keyboard' });
            }
            window.location.href = items[activeIndex].href;
            }
        }
    });
  
    function updateActive(items) {
        items.forEach(el => el.parentElement.classList.remove('active'));
  
        if (items[activeIndex]) {
            const activeItem = items[activeIndex];
            activeItem.parentElement.classList.add('active');    
            DOM.keywordHeader.setAttribute(
                'aria-activedescendant',
                activeItem.parentElement.id
            );
        }
    }
  
    document.addEventListener('click', function (e) {
        if (!DOM.keywordHeader.contains(e.target) && !DOM.keywordResultsHeader.contains(e.target)) {
            closeSuggestions();
        }
    });
  
    let trendingCache = null;
    function renderIdleSuggestions() {
        const recents = window.soLocal ? window.soLocal.recentSearches() : [];
        const build = function (trending) {
            if (normalize(DOM.keywordHeader.value).length >= minChars) return;   // user has typed since
            let html = '<ul class="search-suggestions" role="listbox">';
            let n = 0;
            if (recents.length) {
                html += '<li class="suggestion-label">Recent searches</li>';
                recents.forEach(t => {
                    html += `<li class="result-item" id="suggestion-${n}" role="option"><a href="/search?keywordHeader=${encodeURIComponent(t)}">${escapeHtml(t)}</a></li>`;
                    n++;
                });
            }
            if (trending.length) {
                html += '<li class="suggestion-label">Trending now</li>';
                trending.forEach(t => {
                    html += `<li class="result-item" id="suggestion-${n}" role="option"><a href="/artist/${escapeHtml(t.slug)}">${escapeHtml(t.name)}</a></li>`;
                    n++;
                });
            }
            html += '</ul>';
            if (!recents.length && !trending.length) return;
            DOM.keywordResultsHeader.innerHTML = html;
            showSuggestions();
            activeIndex = -1;
        };
        if (trendingCache) { build(trendingCache); return; }
        fetch('/ajax/get-top-performers.php', { cache: 'force-cache' })
            .then(r => r.ok ? r.json() : {})
            .then(d => {
                const pick = [];
                ['concerts', 'sports', 'theater'].forEach(k => (d[k] || []).slice(0, 2).forEach(p => { if (p && p.slug && p.name) pick.push({ name: p.name, slug: p.slug }); }));
                trendingCache = pick;
                build(pick);
            })
            .catch(() => { trendingCache = []; build([]); });
    }

    DOM.keywordHeader.addEventListener('focus', function () {
        const q = normalize(this.value);    
        if (q.length < minChars) { renderIdleSuggestions(); return; }
        if (searchCache[q]) {
            renderResults(searchCache[q], q);
            return;
        }    
        fetchSuggestions(q);
    });  
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.open-submenu').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.getElementById(btn.getAttribute('data-target'));
            if (target) target.classList.add('active');
        });
    });
    document.querySelectorAll('.back-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = btn.closest('.submenu-panel');
            if (panel) panel.classList.remove('active');
        });
    });

    var currentPath = window.location.pathname.replace(/\/$/, "");
    document.querySelectorAll('footer a').forEach(function (a) {
        var href = a.getAttribute('href');
        if (!href || href === '#' || href.indexOf('#') === 0) return;
        var linkPath = new URL(a.href).pathname.replace(/\/$/, "");
        if (currentPath === linkPath) a.classList.add('active');
    });
});

/* =====================================================
    HEADER KEYWORD FIELD End
===================================================== */

/* =====================================================
    BATCH IMAGE LOADER (homepage cards, venue slider, search suggestions)
    One POST to /ajax/get-images.php for every dynamic image in a container,
    instead of one GET per card fired sequentially. isCurrent() lets the
    caller cancel when the slider was re-rendered meanwhile.
===================================================== */
/* An initials tile as a data URI: used where an artist, team or show has no picture yet, so no two cards share the same
   stock photo. The colour comes from the name, so a name always gets the same tile. */
window.soTile = function (name) {
  var words = String(name || '').replace(/[^A-Za-z0-9 ]+/g, ' ').trim().split(/\s+/).filter(Boolean);
  var ini = ((words[0] || '?').charAt(0) + (words.length > 1 ? words[words.length - 1].charAt(0) : '')).toUpperCase();
  var nm = String(name || ''), h = 0; for (var i = 0; i < nm.length; i++) { h = (h * 31 + nm.charCodeAt(i)) >>> 0; }
  var hue = h % 360, hue2 = (hue + 40) % 360;
  var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl(' + hue + ',62%,38%)"/><stop offset="1" stop-color="hsl(' + hue2 + ',70%,24%)"/></linearGradient></defs><rect width="400" height="400" fill="url(#g)"/><text x="200" y="228" text-anchor="middle" font-family="-apple-system,Segoe UI,Inter,Arial,sans-serif" font-size="150" font-weight="700" fill="rgba(255,255,255,.92)">' + ini.replace(/&/g, '&amp;') + '</text></svg>';
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
};

/* Real pictures arrive later for names that were only queued: ask a few at a time (the server limits how many it
   will look up per request), a couple of times, then keep the tile. */
window.soResolveLater = function (pending, round) {
  round = round || 0;
  if (!pending.length || round > 3) return;
  setTimeout(function () {
    fetch('/ajax/resolve-images.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: pending.slice(0, 8).map(function (p) { return { name: p.name, type: p.type }; }) }) })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var left = [];
        pending.slice(0, 8).forEach(function (p, i) {
          var url = (data.images || [])[i];
          if (!url) { left.push(p); return; }
          var t = new Image();
          var cr = (data.credits || [])[i];
          t.onload = function () {
            p.img.src = url; p.img.classList.remove('so-img-tile');
            if (cr && cr.text) { p.img.title = cr.full || cr.text; p.img.setAttribute('data-credit', cr.text); }   // the licence notice travels with the picture
          };
          t.src = url;
        });
        window.soResolveLater(left.concat(pending.slice(8)), round + 1);
      }).catch(function () {});
  }, round === 0 ? 800 : 3600);
};

window.soBatchLoadImages = async function (container, selector, isCurrent) {
  if (!container) return;
  const imgs = Array.from(container.querySelectorAll(selector));
  if (!imgs.length) return;
  const items = imgs.map(img => {
    let category = {};
    try { category = JSON.parse(img.dataset.category || '{}'); } catch (e) { category = {}; }
    return {
      artist: img.dataset.artist ? decodeURIComponent(img.dataset.artist) : '',
      venue: img.dataset.venue ? decodeURIComponent(img.dataset.venue) : '',
      tab: img.dataset.tab ? decodeURIComponent(img.dataset.tab) : '',
      category: category
    };
  });
  try {
    const res = await fetch('/ajax/get-images.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items })
    });
    const data = await res.json();
    if (typeof isCurrent === 'function' && !isCurrent()) return;
    const results = (data && data.images) || [];
    const pending = [];
    imgs.forEach((img, i) => {
      const r = results[i];
      const label = items[i].artist || items[i].venue;
      if (r && r.real === false && label) {
        // No picture of its own yet: initials tile now, the real picture when it has been looked up.
        img.src = window.soTile(label);
        img.classList.add('loaded', 'so-img-tile');
        img.style.opacity = '1';
        pending.push({ img: img, name: label, type: r.type || 'artist' });
        return;
      }
      if (!r || !r.image) { img.classList.add('loaded'); return; }
      const tempImg = new Image();
      img.style.transition = 'opacity 0.3s ease';
      const reveal = () => {
        if (typeof isCurrent === 'function' && !isCurrent()) return;
        if (!img.dataset.soFallback) img.dataset.soFallback = img.getAttribute('src') || '';
        img.src = r.image;
        if (r.credit) img.title = r.credit;
        requestAnimationFrame(() => { img.style.opacity = '1'; img.classList.add('loaded'); });
      };
      tempImg.onload = reveal;
      tempImg.onerror = () => { img.style.opacity = '1'; img.classList.add('loaded'); };
      tempImg.src = r.image;
    });
    if (pending.length) window.soResolveLater(pending, 0);
  } catch (err) {
    console.error('Batch image load failed:', err);
    imgs.forEach(img => img.classList.add('loaded'));
  }
};

/* =====================================================
    LOCAL PERSONALIZATION (recently viewed, recent searches)
    Stored only in this browser's localStorage; nothing is sent to the
    server and no account is needed. Cleared with the browser's site data.
===================================================== */
window.soLocal = (function () {
  function read(key) {
    try { const v = JSON.parse(localStorage.getItem(key) || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; }
  }
  function write(key, list) {
    try { localStorage.setItem(key, JSON.stringify(list)); } catch (e) { /* private mode / quota */ }
  }
  const SLUG = /^[a-z0-9-]+$/;
  return {
    recentPerformers: function () {
      return read('so_recent_viewed').filter(i => i && SLUG.test(String(i.slug || '')) && typeof i.name === 'string');
    },
    addPerformer: function (item) {
      if (!item || !SLUG.test(String(item.slug || '')) || !item.name) return;
      const clean = { id: String(item.id || ''), name: String(item.name).slice(0, 80), slug: item.slug, img: /^https?:\/\//.test(item.img || '') || /^\//.test(item.img || '') ? item.img : '' };
      const list = read('so_recent_viewed').filter(i => i && i.slug !== clean.slug);
      list.unshift(clean);
      write('so_recent_viewed', list.slice(0, 8));
    },
    recentEvents: function () {
      const today = new Date().toISOString().slice(0, 10);
      return read('so_recent_events').filter(i => i && /^[0-9]+$/.test(String(i.id || '')) && SLUG.test(String(i.slug || '')) && typeof i.name === 'string' && (!i.date || String(i.date) >= today));
    },
    addEvent: function (item) {
      if (!item || !/^[0-9]+$/.test(String(item.id || '')) || !SLUG.test(String(item.slug || '')) || !item.name) return;
      const clean = { id: String(item.id), name: String(item.name).slice(0, 90), slug: item.slug, date: /^\d{4}-\d{2}-\d{2}$/.test(item.date || '') ? item.date : '', city: String(item.city || '').slice(0, 60), venue: String(item.venue || '').slice(0, 80), performer: String(item.performer || '').slice(0, 80), cat: /^[.0-9]+$/.test(item.cat || '') ? item.cat : '' };
      const list = read('so_recent_events').filter(i => i && String(i.id) !== clean.id);
      list.unshift(clean);
      write('so_recent_events', list.slice(0, 6));
    },
    recentSearches: function () {
      return read('so_recent_searches').filter(t => typeof t === 'string' && t.length >= 2);
    },
    addSearch: function (term) {
      term = String(term || '').trim().slice(0, 60);
      if (term.length < 2) return;
      const list = read('so_recent_searches').filter(t => String(t).toLowerCase() !== term.toLowerCase());
      list.unshift(term);
      write('so_recent_searches', list.slice(0, 5));
    }
  };
})();

// Home card rows' arrow (desktop): one page of whole cards per click (the cards fill the row exactly from 992px); back to
// the start after the last page.
window.soRowArrow = function (box, track) {
  const nx = box.querySelector('[data-so-feed-next]');
  if (!nx) return;
  // No arrow when every card already fits (a row with few events).
  const fit = function () { nx.hidden = track.scrollWidth <= track.clientWidth + 2; };
  requestAnimationFrame(fit);
  if (nx.dataset.bound) return;
  nx.dataset.bound = '1';
  window.addEventListener('resize', fit, { passive: true });
  nx.addEventListener('click', function () {
    const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
    const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
    if (atEnd) track.scrollTo({ left: 0, behavior: 'smooth' });
    else track.scrollBy({ left: track.clientWidth + gap, behavior: 'smooth' });
  });
};

// Homepage: "Pick up where you left off": the events and performers this browser viewed, as picture cards.
// Every card shows a picture: the stored one, the artist's picture when it can be found, or an initials tile.
document.addEventListener('DOMContentLoaded', function () {
  const box = document.getElementById('recentlyViewed');
  if (!box || !window.soLocal) return;
  const track = box.querySelector('[data-so-recent-track]');
  const items = window.soLocal.recentPerformers().slice(0, 6);
  const evs = window.soLocal.recentEvents().slice(0, 4);
  if (!track || (!items.length && !evs.length)) return;
  const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const catJson = cat => esc(JSON.stringify(cat ? { path: cat } : {}));
  let html = '';
  const kindOf = cat => /\.1988\./.test(cat || '') ? 'sports' : /\.1989\./.test(cat || '') ? 'theatre' : 'concerts';
  evs.forEach(function (ev) {
    html += '<div class="so-recent__slide" data-recent-event="' + esc(ev.id) + '">' + window.soEvCard({ id: ev.id, name: ev.name, iso: ev.date, loc: ev.city, venue: ev.venue, tab: kindOf(ev.cat) }, { status: 'Viewed', href: '/event/' + ev.slug, noTime: true }) + '</div>';
  });
  items.forEach(function (it) {
    const ini = String(it.name).split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    let h = 0; for (let i = 0; i < it.name.length; i++) h = (h * 31 + it.name.charCodeAt(i)) % 360;
    html += '<div class="so-recent__slide"><a class="so-rp" href="/artist/' + esc(it.slug) + '">' +
      (it.img ? '<img class="so-topc__avatar" src="' + esc(it.img) + '" alt="" width="44" height="44" loading="lazy">' : '<span class="so-topc__avatar so-topc__avatar--init" style="--so-hue:' + h + '">' + esc(ini) + '</span>') +
      '<span class="so-rp__txt"><strong>' + esc(it.name) + '</strong><small>View tickets</small></span>' +
      '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></a></div>';
  });
  track.innerHTML = html;
  box.classList.remove('d-none');
  window.soRowArrow(box, track);
  track.addEventListener('click', function (e) { var a = e.target.closest('[data-recent-event]'); if (a) (window.dataLayer = window.dataLayer || []).push({ event: 'recent_event_click', event_id: a.getAttribute('data-recent-event') }); });
  const clear = box.querySelector('[data-so-recent-clear]');
  if (clear) clear.addEventListener('click', function () {
    try { localStorage.removeItem('so_recent_viewed'); localStorage.removeItem('so_recent_events'); } catch (e) {}
    box.classList.add('d-none');
  });
});

// Remember what was searched (submit of the header search form).
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form.search-bar-form');
  const input = document.getElementById('keywordHeader');
  if (form && input && window.soLocal) {
    form.addEventListener('submit', function () { window.soLocal.addSearch(input.value); });
  }
  // Keep the results URL short and shareable: fields left empty are not sent (/search?keywordHeader=taylor instead of six parameters).
  // The browser reads the form after this handler runs, so disabling is enough; the fields come back right after, for the back button.
  if (form) {
    form.addEventListener('submit', function () {
      const off = [];
      form.querySelectorAll('input[name]').forEach(function (el) { if (!el.disabled && String(el.value).trim() === '') { el.disabled = true; off.push(el); } });
      setTimeout(function () { off.forEach(function (el) { el.disabled = false; }); }, 0);
    });
    window.addEventListener('pageshow', function () { form.querySelectorAll('input[name]').forEach(function (el) { el.disabled = false; }); });
  }
});


/* Carousel accessibility: slick marks off-screen slides aria-hidden but leaves their links focusable.
   Keep keyboard focus off hidden slides (re-applied whenever a slider initializes or moves). */
(function () {
    function fixSlickFocus(root) {
        (root || document).querySelectorAll('.slick-slide').forEach(function (slide) {
            var hidden = slide.getAttribute('aria-hidden') === 'true';
            var controls = [slide].concat([].slice.call(slide.querySelectorAll('a, button, input, select, textarea')))
                .filter(function (el) { return /^(A|BUTTON|INPUT|SELECT|TEXTAREA)$/.test(el.tagName); });
            controls.forEach(function (el) {
                if (hidden) {
                    if (!el.hasAttribute('data-so-tab')) { el.setAttribute('data-so-tab', el.getAttribute('tabindex') === null ? '' : el.getAttribute('tabindex')); }
                    el.setAttribute('tabindex', '-1');
                } else if (el.hasAttribute('data-so-tab')) {
                    var v = el.getAttribute('data-so-tab');
                    if (v === '') el.removeAttribute('tabindex'); else el.setAttribute('tabindex', v);
                    el.removeAttribute('data-so-tab');
                }
            });
        });
    }
    window.soFixSlickFocus = fixSlickFocus;
    window.addEventListener('load', function () { fixSlickFocus(); setTimeout(fixSlickFocus, 600); });
    function bind() { if (window.jQuery) { jQuery(document).on('init reInit afterChange setPosition', '.slick-slider', function () { fixSlickFocus(this); }); } }
    if (window.jQuery) bind(); else document.addEventListener('DOMContentLoaded', bind);
})();

/* =====================================================
    Search: the header's Search button (an icon on phones) and the home hero's "Search Events" button fold the header search open and closed
===================================================== */
(function () {
    var btn = document.querySelector('.so-search-toggle');
    var form = document.getElementById('soSearch');
    if (!btn || !form) return;
    function setOpen(open) {
        if (open) { form.removeAttribute('data-so-collapsed'); } else { form.setAttribute('data-so-collapsed', ''); }
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('is-open', open);
        if (open) {
            var field = document.getElementById('keywordHeader');
            if (field) { try { field.focus({ preventScroll: true }); } catch (e) { field.focus(); } }
        }
    }
    btn.addEventListener('click', function () { setOpen(form.hasAttribute('data-so-collapsed')); });
    document.querySelectorAll('[data-so-open-search]').forEach(function (b) {
        b.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            setOpen(true);
        });
    });
})();

/* =====================================================
    MENUS: phone rail menu (tabs) and desktop mega menu (hover with a short delay, focus and Escape)
===================================================== */
(function () {
    var menu = document.querySelector('[data-so-menu]');
    if (menu) {
        var tabs = menu.querySelectorAll('.so-menu__tab');
        var panels = menu.querySelectorAll('.so-menu__panel');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var key = tab.getAttribute('data-tab');
                tabs.forEach(function (t) { var on = t === tab; t.classList.toggle('is-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1; });
                panels.forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== key; });
                var pane = menu.querySelector('.so-menu__pane'); if (pane) pane.scrollTop = 0;
            });
            tab.addEventListener('keydown', function (e) {
                var list = Array.prototype.slice.call(tabs), i = list.indexOf(tab), to = -1;
                if (e.key === 'ArrowDown' || e.key === 'ArrowRight') to = (i + 1) % list.length;
                else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') to = (i - 1 + list.length) % list.length;
                else if (e.key === 'Home') to = 0; else if (e.key === 'End') to = list.length - 1;
                if (to > -1) { e.preventDefault(); list[to].focus(); list[to].click(); }
            });
        });
    }
    var tops = document.querySelectorAll('.so-mega-top');
    if (!tops.length) return;
    var openKey = null, timer = null, skipFocusOpen = false;
    function panelOf(a) { return document.getElementById('so-mega-' + a.getAttribute('data-so-mega')); }
    function closeAll() {
        tops.forEach(function (a) { var p = panelOf(a); if (p) p.hidden = true; a.setAttribute('aria-expanded', 'false'); a.classList.remove('is-open'); });
        openKey = null;
        document.documentElement.classList.remove('so-mega-open');
    }
    function open(a) {
        clearTimeout(timer);
        var key = a.getAttribute('data-so-mega');
        if (openKey === key) return;
        closeAll();
        var p = panelOf(a); if (!p) return;
        p.hidden = false; a.setAttribute('aria-expanded', 'true'); a.classList.add('is-open'); openKey = key;
        document.documentElement.classList.add('so-mega-open');
    }
    function later(fn, ms) { clearTimeout(timer); timer = setTimeout(fn, ms); }
    tops.forEach(function (a) {
        var item = a.closest('.so-mega-item');
        item.addEventListener('mouseenter', function () { later(function () { open(a); }, openKey ? 40 : 110); });
        item.addEventListener('mouseleave', function () { later(closeAll, 140); });
        a.addEventListener('focus', function () { if (!skipFocusOpen) open(a); });
        // Touch screens (no hover): the first tap opens the panel, the second tap follows the link.
        a.addEventListener('click', function (e) {
            if (window.matchMedia && matchMedia('(hover: none)').matches && openKey !== a.getAttribute('data-so-mega')) { e.preventDefault(); open(a); }
        });
        a.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { var first = panelOf(a).querySelector('a'); if (first) { e.preventDefault(); open(a); first.focus(); } }
        });
        item.addEventListener('focusout', function (e) { if (!item.contains(e.relatedTarget)) later(closeAll, 60); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || openKey === null) return;
        // Focus inside the panel would be lost when it hides: put it back on the menu's top link.
        var active = document.activeElement, owner = active && active.closest ? active.closest('.so-mega-item') : null;
        var top = owner ? owner.querySelector('.so-mega-top') : null;
        closeAll();
        if (top && active !== top) { skipFocusOpen = true; try { top.focus({ preventScroll: true }); } catch (err) { top.focus(); } skipFocusOpen = false; }
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.so-mega-item')) closeAll(); });
})();

/* Header search: leave empty fields out of the address (/search?keywordHeader=adele instead of five empty parameters). */
(function () {
    var f = document.getElementById('soSearch');
    if (!f) return;
    f.addEventListener('submit', function () {
        f.querySelectorAll('input[name]').forEach(function (i) { if (i.value.trim() === '') i.disabled = true; });
        setTimeout(function () { f.querySelectorAll('input[name]').forEach(function (i) { i.disabled = false; }); }, 1500);
    });
})();


/* =====================================================
    Expired or deleted picture: never show a broken image.
    A stored artist or venue picture can disappear from storage (file removed, link expired). The image error does not bubble, so it is
    caught here in the capture phase and the tile goes back to the category placeholder it started with (data-so-fallback), once.
===================================================== */
document.addEventListener('error', function (e) {
  const img = e.target;
  if (!img || img.tagName !== 'IMG' || img.dataset.soFailed) return;
  img.dataset.soFailed = '1';
  const fb = img.dataset.soFallback || img.getAttribute('data-fallback') || '';
  if (fb && img.getAttribute('src') !== fb) { img.src = fb; img.classList.add('loaded'); img.style.opacity = '1'; return; }
  img.style.visibility = 'hidden';   // nothing better to show: keep the card's layout, lose the broken-image icon
}, true);

/* =====================================================
    Event cards (text only, no pictures): one card for every event list on the site.
    soEvCard(ev, opts) returns the HTML; ev is what the feed endpoints return
    { id, name, iso 'YYYY-MM-DD', date (text, fallback), time, venue, loc, price, tab, dist }.
    opts.status = a small chip such as "Tomorrow" or "Popular". The heart saves the event on this device only
    (same localStorage list the event page uses, key so_saved_events).
===================================================== */
(function () {
  var KEY = 'so_saved_events';
  var ICONS = {
    concert: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 17.5V6l10-2v11.5"/><circle cx="6.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="15.5" r="2.5"/></svg>',
    sports: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4zM8 6H4.5a2 2 0 0 0 2 3.5M16 6h3.5a2 2 0 0 1-2 3.5M12 13v4M8.5 20h7"/></svg>',
    theater: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 5.5h10v6a5 5 0 0 1-10 0v-6zM6.5 9h.01M10.5 9h.01M6.5 13c1 1 3 1 4 0"/><path d="M10.5 18.5a5 5 0 0 0 10-1.5v-6h-5.5M15.5 14h.01M19 14h.01"/></svg>',
    festival: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8a2 2 0 0 0 0 4v0a2 2 0 0 0 0 4v1.5h18V16a2 2 0 0 0 0-4v0a2 2 0 0 0 0-4V6.5H3V8zM14 6.5v11"/></svg>'
  };
  var LABEL = { concert: 'Concert', sports: 'Sports', theater: 'Theater', festival: 'Festival' };
  function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function kind(tab) {
    tab = String(tab || '').toLowerCase();
    return tab === 'sports' ? 'sports' : (tab === 'theatre' || tab === 'theater') ? 'theater' : tab === 'festival' ? 'festival' : 'concert';
  }
  function dateText(ev, noTime) {
    var out = '';
    if (/^\d{4}-\d{2}-\d{2}/.test(ev.iso || '')) {
      var p = ev.iso.slice(0, 10).split('-').map(Number);
      out = new Date(p[0], p[1] - 1, p[2]).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    } else {
      out = String(ev.date || '');
    }
    var t = String(ev.time || '').trim();
    if (out && !noTime && /^\d{4}-\d{2}-\d{2}/.test(ev.iso || '')) out += ' • ' + (t && !/^tba$/i.test(t) ? esc(t) : 'Time TBA').replace(/&amp;/g, '&');
    return out;
  }
  function slugOf(v) { return (typeof normalizeKey === 'function') ? normalizeKey(v) : String(v).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }
  function storeOk() { try { localStorage.getItem(KEY); return true; } catch (e) { return false; } }
  function savedIds() {
    try { var a = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(a) ? a.map(function (x) { return String(x && x.id); }) : []; } catch (e) { return []; }
  }

  window.soEvCard = function (ev, opts) {
    opts = opts || {};
    var k = kind(ev.tab);
    var slug = ev.slug || (slugOf(ev.name) + '-' + ev.id);   // the server sends the clean slug
    var href = opts.href || ('/event/' + slug);
    var saved = storeOk() && savedIds().indexOf(String(ev.id)) !== -1;
    var place = ev.loc ? '<small>' + esc(ev.loc) + (ev.dist != null ? ' · ' + (ev.dist < 3 ? 'nearby' : ev.dist + ' mi') : '') + '</small>' : '';
    return '<article class="so-evc so-evc--' + k + (opts.tint ? ' so-evc--tint' : '') + '">' +
      '<div class="so-evc__top"><span class="so-evc__badge">' + ICONS[k] + LABEL[k] + '</span>' +
        (opts.status ? '<span class="so-evc__status' + (/^Popular/.test(opts.status) ? ' so-evc__status--hot' : '') + '">' + esc(opts.status) + '</span>' : '') +
        (storeOk() ? '<button type="button" class="so-evc__save' + (saved ? ' is-saved' : '') + '" aria-pressed="' + (saved ? 'true' : 'false') + '" aria-label="Save ' + esc(ev.name) + '" data-so-evc-save data-id="' + esc(ev.id) + '" data-slug="' + esc(slug) + '" data-name="' + esc(ev.name) + '" data-iso="' + esc(ev.iso || '') + '" data-city="' + esc(ev.loc || '') + '" data-venue="' + esc(ev.venue || '') + '" data-price="' + esc(ev.price || '') + '"><svg width="20" height="20" viewBox="0 0 24 24" fill="' + (saved ? 'currentColor' : 'none') + '" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.2-9.3C1.7 8 3.6 5 6.7 5c1.9 0 3.5 1 5.3 3 1.8-2 3.4-3 5.3-3 3.1 0 5 3 3.9 6.2-1.7 4.7-9.2 9.3-9.2 9.3z"/></svg></button>' : '') +
      '</div>' +
      '<h3 class="so-evc__name"><a class="so-evc__link" href="' + href + '">' + esc(ev.name) + '</a></h3>' +
      '<ul class="so-evc__meta">' +
        '<li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4M16 3v4M3.5 10h17"/></svg><span>' + esc(dateText(ev, opts.noTime)) + '</span></li>' +
        (ev.venue ? '<li><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12zm0-9.2a2.8 2.8 0 1 1 0-5.6 2.8 2.8 0 0 1 0 5.6z"/></svg><span>' + esc(ev.venue) + place + '</span></li>' : '') +
      '</ul>' +
      '<div class="so-evc__foot">' + (ev.price ? '<span class="so-evc__price">From <strong>' + esc(ev.price) + '</strong></span>' : '<span class="so-evc__price so-evc__price--none">View tickets</span>') +
        '<span class="so-evc__go" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></div>' +
    '</article>';
  };
  window.soEvCardSkeleton = function (n) {
    var h = '';
    for (var i = 0; i < (n || 4); i++) h += '<article class="so-evc so-evc--skeleton" aria-hidden="true"><div class="so-evc__top"><span class="so-evc__sk so-evc__sk--badge"></span></div><span class="so-evc__sk so-evc__sk--title"></span><span class="so-evc__sk so-evc__sk--line"></span><span class="so-evc__sk so-evc__sk--line so-evc__sk--short"></span></article>';
    return h;
  };

  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-so-evc-save]') : null;
    if (!b) return;
    e.preventDefault(); e.stopPropagation();
    var list;
    try { list = JSON.parse(localStorage.getItem(KEY) || '[]'); if (!Array.isArray(list)) list = []; } catch (x) { return; }
    var id = String(b.getAttribute('data-id'));
    var had = list.some(function (x) { return String(x && x.id) === id; });
    if (had) list = list.filter(function (x) { return String(x && x.id) !== id; });
    else list.unshift({ id: id, name: b.getAttribute('data-name'), slug: b.getAttribute('data-slug'), date: b.getAttribute('data-iso'), city: b.getAttribute('data-city'), venue: b.getAttribute('data-venue'), price: b.getAttribute('data-price'), savedAt: Date.now() });
    try { localStorage.setItem(KEY, JSON.stringify(list.slice(0, 40))); } catch (x) { return; }
    b.classList.toggle('is-saved', !had);
    b.setAttribute('aria-pressed', had ? 'false' : 'true');
    var svg = b.querySelector('svg'); if (svg) svg.setAttribute('fill', had ? 'none' : 'currentColor');
  });
})();

/* Sideways scrollers (sub-category row, "Browse by category" tiles): previous / next buttons appear only when there is more to scroll. */
(function () {
  function setup(wrap) {
    var track = wrap.querySelector('[data-so-subs-track]');
    var prev = wrap.querySelector('[data-so-subs-prev]');
    var next = wrap.querySelector('[data-so-subs-next]');
    if (!track || !prev || !next) return;
    function sync() {
      var max = track.scrollWidth - track.clientWidth;
      prev.hidden = !(max > 4 && track.scrollLeft > 4);
      next.hidden = !(max > 4 && track.scrollLeft < max - 4);
    }
    // The category grid pages by whole columns (its columns fill the width exactly); other rows move 80% of the width.
    var paged = wrap.classList.contains('so-cattiles__wrap');
    function by(dir) {
      var step = paged ? track.clientWidth + (parseFloat(getComputedStyle(track).columnGap) || 0) : Math.max(200, track.clientWidth * 0.8);
      track.scrollBy({ left: dir * step, behavior: 'smooth' });
    }
    prev.addEventListener('click', function () { by(-1); });
    next.addEventListener('click', function () { by(1); });
    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
    sync();
    setTimeout(sync, 400);
  }
  function init() { document.querySelectorAll('[data-so-subs]').forEach(setup); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();


/* "Read more" under the newsletter on listing pages: opens everything below it, closes it again. */
(function () {
  var wrap = document.querySelector('[data-so-more]');
  if (!wrap) return;
  var btn = wrap.querySelector('[data-so-more-toggle]');
  var panel = document.getElementById('soMorePanel');
  var label = wrap.querySelector('[data-so-more-label]');
  if (!btn || !panel) return;
  function set(open, scroll) {
    panel.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (label) label.textContent = open ? 'Show less' : 'Read more';
    wrap.classList.toggle('is-open', open);
    if (open && typeof window.soAdsPush === 'function') window.soAdsPush();   // ads inside the panel are only requested once they are visible
    if (scroll) {
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var target = open ? panel : btn;
      var top = target.getBoundingClientRect().top + window.pageYOffset - (open ? 90 : 220);
      window.scrollTo({ top: Math.max(0, top), behavior: reduce ? 'auto' : 'smooth' });
    }
  }
  btn.addEventListener('click', function () { set(panel.hidden, true); });
  // A link to something inside the panel (a #hash) opens it first.
  function openForHash() {
    if (!location.hash || location.hash.length < 2) return;
    var t = null;
    try { t = document.getElementById(decodeURIComponent(location.hash.slice(1))); } catch (e) {}
    if (t && panel.contains(t)) set(true, false);
  }
  openForHash();
  window.addEventListener('hashchange', openForHash);
})();

/* =====================================================
    SLIDERS: hidden slides must not be focusable
===================================================== */
// Slick marks off-screen slides aria-hidden="true" but leaves the slide (and the links inside it) in the tab order, which breaks the
// ARIA rule that hidden content cannot take focus (axe: aria-hidden-focus). Hidden slides and everything focusable inside them get
// tabindex="-1" (marked with data-so-ti so it is undone when the slide becomes visible again).
(function () {
    var FOCUSABLE = 'a[href],button,input,select,textarea,[tabindex]';
    function fix(slider) {
        slider.querySelectorAll('.slick-slide').forEach(function (slide) {
            var hidden = slide.getAttribute('aria-hidden') === 'true';
            var nodes = [slide].concat(Array.prototype.slice.call(slide.querySelectorAll(FOCUSABLE)));
            nodes.forEach(function (el) {
                if (hidden) {
                    if (el.getAttribute('tabindex') !== '-1') { el.setAttribute('data-so-ti', el.getAttribute('tabindex') === null ? '' : el.getAttribute('tabindex')); el.setAttribute('tabindex', '-1'); }
                } else if (el.hasAttribute('data-so-ti')) {
                    var prev = el.getAttribute('data-so-ti');
                    if (prev === '') el.removeAttribute('tabindex'); else el.setAttribute('tabindex', prev);
                    el.removeAttribute('data-so-ti');
                }
            });
        });
    }
    var watched = typeof WeakSet === 'function' ? new WeakSet() : null;
    function watch(slider) {
        // Slick rewrites tabindex/aria-hidden after its own events, so the rule is re-applied whenever those attributes change.
        if (!window.MutationObserver || (watched && watched.has(slider))) return;
        if (watched) watched.add(slider);
        var queued = false;
        new MutationObserver(function () {
            if (queued) return;
            queued = true;
            requestAnimationFrame(function () { queued = false; fix(slider); });
        }).observe(slider, { attributes: true, attributeFilter: ['aria-hidden', 'tabindex'], subtree: true });
    }
    function all() { document.querySelectorAll('.slick-slider').forEach(function (sl) { fix(sl); watch(sl); }); }
    if (window.jQuery) {
        window.jQuery(document).on('init reInit afterChange setPosition breakpoint', '.slick-slider', function () { fix(this); watch(this); });
    }
    window.addEventListener('load', function () { all(); setTimeout(all, 600); });
})();

// Filter and "See all" controls that change the listing (date, sort) carry their target in data-go instead of an href: they are
// choices on the page, not pages of their own, so crawlers do not list every filtered copy of a page.
(function () {
    function go(el) {
        var to = el.getAttribute('data-go');
        if (to && to.charAt(0) === '/') window.location.href = to;
    }
    document.addEventListener('click', function (e) {
        var el = e.target.closest && e.target.closest('[data-go]');
        if (el) { e.preventDefault(); go(el); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var el = e.target.closest && e.target.closest('[data-go]');
        if (el && el.tagName !== 'BUTTON') { e.preventDefault(); go(el); }
    });
})();


/* Promo "Copy code" buttons: one delegated handler for every page (event, artist, venue and promo pages),
   with a fallback for browsers that block the async clipboard API. */
document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.offer-copy-btn');
    if (!btn) return;
    var code = btn.dataset.code || (btn.previousElementSibling && btn.previousElementSibling.textContent.trim()) || '';
    if (!code) return;
    var label = btn.getAttribute('data-label') || btn.textContent;
    btn.setAttribute('data-label', label);
    var done = function (ok) {
        btn.textContent = ok ? 'Copied' : 'Press Ctrl+C to copy: ' + code;
        clearTimeout(btn._soT);
        btn._soT = setTimeout(function () { btn.textContent = label; }, ok ? 1500 : 4000);
    };
    var legacy = function () {
        var t = document.createElement('textarea');
        t.value = code; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
        document.body.appendChild(t); t.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
        document.body.removeChild(t);
        done(ok);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(function () { done(true); }, legacy);
    } else {
        legacy();
    }
});
