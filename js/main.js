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
    const v = document.cookie.split('; ').find(row => row.startsWith(name + '='));
    return v ? decodeURIComponent(v.split('=')[1]) : '';
}

function setCookie(name, value) {
    // Lax + Secure (on https) + 30-day expiry; these were session cookies with no SameSite flag.
    const secure = location.protocol === 'https:' ? ';Secure' : '';
    document.cookie = name + '=' + value + ';path=/;max-age=2592000;SameSite=Lax' + secure;
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
            if (DOM.locationSelectorText.textContent !== input.value) {
                DOM.locationSelectorText.textContent = input.value;
            }
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
        if (DOM.locationSelectorText) DOM.locationSelectorText.innerHTML = savedLabel + ' <i class="bi bi-chevron-down"></i>';
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
    const showPrompt = () => { if (locationLabel) locationLabel.innerHTML = 'Select your location <i class="bi bi-chevron-down"></i>'; };
    const applyLocation = (lat, lng, label, labelCookie) => {
        setCookie('so_lat', encodeURIComponent(lat));
        setCookie('so_lng', encodeURIComponent(lng));
        setCookie('so_label', labelCookie || label);
        if (locationLabel) locationLabel.innerHTML = label + ' <i class="bi bi-chevron-down"></i>';
        window.locationReady = true;
        if (typeof reloadActiveTab === 'function') {
            reloadActiveTab('ll', { lat: lat, lng: lng });
        }
        if (typeof loadNearbyVenues === 'function') {
            loadNearbyVenues();
        }
    };
    // Second chance when the network lookup has no answer: use the device location, but only if the visitor already
    // allowed it, or ask once if they have not been asked yet.
    const tryAllowedDeviceLocation = () => {
        if (!navigator.geolocation || !navigator.permissions || !navigator.permissions.query) { showPrompt(); return; }
        navigator.permissions.query({ name: 'geolocation' }).then(state => {
            // 'prompt': ask once per visitor (remembered in a cookie), after the page has settled; 'denied': never nag.
            if (state.state === 'prompt' && !getCookie('so_geo_asked')) { setCookie('so_geo_asked', '1'); }
            else if (state.state !== 'granted') { showPrompt(); return; }
            navigator.geolocation.getCurrentPosition(async position => {
                const lat = position.coords.latitude, lng = position.coords.longitude;
                try {
                    await loadGoogleMapsApi();
                    getCityState(lat, lng, label => applyLocation(lat, lng, label));
                } catch (e) { showPrompt(); }
            }, showPrompt, { timeout: 8000, maximumAge: 600000 });
        }).catch(showPrompt);
    };

    if (locationLabel) locationLabel.innerHTML = 'Finding your location... <i class="bi bi-chevron-down"></i>';
    // If nothing has answered after a few seconds (for example the permission prompt is still open), go back to the plain prompt.
    setTimeout(() => { if (locationLabel && /^Finding/.test(locationLabel.textContent.trim())) showPrompt(); }, 4000);
    fetch('/ajax/get_ip_details.php')
    .then(res => res.json())
    .then(data => {
        // The lookup can legitimately come back empty (visitor's address not in the database, no location headers from
        // the CDN): never save or show a half-empty "undefined, undefined" label, try the allowed device location instead.
        if (!data || !data.city || !data.state) {
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
            DOM.resultsHeader.innerHTML = `
                <div class="current-location">
                    <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2" id="useCurrentLocationHeader"> Current location </span>  
                </div>
            `;
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
// header.php prefetches both files at idle priority; this loads them (from the cache by then) and resolves when ready.
// Warm the browser cache once the page has fully loaded and the browser is idle (not in the <head>: an early prefetch competes
// with the render-blocking CSS and the hero image for bandwidth, which measurably delayed first paint on slow connections).
window.addEventListener('load', function () {
    var warm = function () {
        var assets = window.SO_ASSETS || {};
        [[assets.flatpickrJs, 'script'], [assets.flatpickrCss, 'style']].forEach(function (a) {
            if (!a[0]) return;
            var l = document.createElement('link');
            l.rel = 'prefetch'; l.as = a[1]; l.href = a[0];
            document.head.appendChild(l);
        });
    };
    if ('requestIdleCallback' in window) requestIdleCallback(warm, { timeout: 5000 }); else setTimeout(warm, 3000);
});
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
            js.onerror = function () { pending = null; reject(new Error('flatpickr failed to load')); };
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
    ['pointerdown', 'touchstart', 'focus'].forEach(function (evt) {
        dateEl.addEventListener(evt, function () {
            soLoadFlatpickr().then(function () {
                const f = getFp();
                if (f && document.activeElement === dateEl) f.open();   // the calendar did not exist yet when the visitor tapped
            }).catch(function () {});
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
        if (DOM.searchLoader) DOM.searchLoader.style.display = 'block';
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
  
    function createSlug(name, id) {
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
        fetch('/cache/top_performers.json', { cache: 'force-cache' })
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

$(document).ready(function(){
    $('.open-submenu').click(function(e){
        e.preventDefault();
        let target = $(this).data('target');
        $('#' + target).addClass('active');
    });
    $('.back-btn').click(function(){
        $(this).closest('.submenu-panel').removeClass('active');
    });


    var currentPath = window.location.pathname.replace(/\/$/, "");

    $('footer a').each(function () {
  
      var href = $(this).attr('href');
  
      if (!href || href === '#' || href.startsWith('#')) return;
  
      var linkPath = new URL(this.href).pathname.replace(/\/$/, "");
  
      if (currentPath === linkPath) {
        $(this).addClass('active');
      }
  
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
    imgs.forEach((img, i) => {
      const r = results[i];
      if (!r || !r.image) { img.classList.add('loaded'); return; }
      const tempImg = new Image();
      img.style.transition = 'opacity 0.3s ease';
      const reveal = () => {
        if (typeof isCurrent === 'function' && !isCurrent()) return;
        img.src = r.image;
        if (r.credit) img.title = r.credit;
        requestAnimationFrame(() => { img.style.opacity = '1'; img.classList.add('loaded'); });
      };
      tempImg.onload = reveal;
      tempImg.onerror = () => { img.style.opacity = '1'; img.classList.add('loaded'); };
      tempImg.src = r.image;
    });
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
      const clean = { id: String(item.id), name: String(item.name).slice(0, 90), slug: item.slug, date: /^\d{4}-\d{2}-\d{2}$/.test(item.date || '') ? item.date : '', city: String(item.city || '').slice(0, 60), venue: String(item.venue || '').slice(0, 80) };
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

// Homepage: "Pick up where you left off" from the performers this browser viewed.
document.addEventListener('DOMContentLoaded', function () {
  const box = document.getElementById('recentlyViewed');
  if (!box || !window.soLocal) return;
  const items = window.soLocal.recentPerformers().slice(0, 6);
  const evs = window.soLocal.recentEvents().slice(0, 3);
  if (!items.length && !evs.length) return;
  const evRow = box.querySelector('.recent-events-row');
  evs.forEach(function (ev) {
    const col = document.createElement('div');
    col.className = 'col-12 col-md-6 col-lg-4';
    const a = document.createElement('a');
    a.className = 'recent-card recent-event';
    a.href = '/event/' + ev.slug;
    const title = document.createElement('span');
    title.textContent = ev.name;
    const meta = document.createElement('small');
    const when = ev.date ? new Date(ev.date + 'T12:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
    meta.textContent = [when, ev.city].filter(Boolean).join(' · ');
    const wrap = document.createElement('div');
    wrap.appendChild(title); wrap.appendChild(document.createElement('br')); wrap.appendChild(meta);
    a.appendChild(wrap);
    a.addEventListener('click', function () { (window.dataLayer = window.dataLayer || []).push({ event: 'recent_event_click', event_id: ev.id }); });
    col.appendChild(a);
    if (evRow) evRow.appendChild(col);
  });
  const row = box.querySelector('.recent-row');
  items.forEach(function (it) {
    const col = document.createElement('div');
    col.className = 'col-12 col-sm-6 col-lg-4';
    const a = document.createElement('a');
    a.className = 'recent-card';
    a.href = '/artist/' + it.slug;
    if (it.img) {
      const img = document.createElement('img');
      img.src = it.img; img.alt = ''; img.loading = 'lazy'; img.width = 56; img.height = 56;
      a.appendChild(img);
    }
    const span = document.createElement('span');
    span.textContent = it.name;
    a.appendChild(span);
    col.appendChild(a);
    row.appendChild(col);
  });
  box.classList.remove('d-none');
});

// Remember what was searched (submit of the header search form).
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form.search-bar-form');
  const input = document.getElementById('keywordHeader');
  if (form && input && window.soLocal) {
    form.addEventListener('submit', function () { window.soLocal.addSearch(input.value); });
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
    PHONES: search icon folds the header search open and closed
===================================================== */
(function () {
    var btn = document.querySelector('.so-search-toggle');
    var form = document.getElementById('soSearch');
    if (!btn || !form) return;
    btn.addEventListener('click', function () {
        var open = form.hasAttribute('data-so-collapsed');
        if (open) { form.removeAttribute('data-so-collapsed'); } else { form.setAttribute('data-so-collapsed', ''); }
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('is-open', open);
        if (open) {
            var field = document.getElementById('keywordHeader');
            if (field) { try { field.focus({ preventScroll: true }); } catch (e) { field.focus(); } }
        }
    });
})();
