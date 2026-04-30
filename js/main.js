const DOM = {
    locationSelectorText: document.getElementById('locationSelectorText'),
    keywordResultsHeader: document.getElementById('keywordResultsHeader'),
    keywordHeader: document.getElementById('keywordHeader'),
    backToTop: document.getElementById('backToTop'),
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

let ticking = false;
window.addEventListener('scroll', () => {
    if (!ticking) {
        requestAnimationFrame(() => {
            DOM.backToTop.classList.toggle('show', window.scrollY > 400);
            ticking = false;
        });
        ticking = true;
    }
});
  
DOM.backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
  
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
  
    fetch('/ajax/get_ip_details.php')
    .then(res => res.json())
    .then(data => {
        setCookie('so_lat', encodeURIComponent(data.lat));
        setCookie('so_lng', encodeURIComponent(data.lng));
        setCookie('so_label', data.city + ', ' + data.state);
        if (DOM.locationSelectorText) DOM.locationSelectorText.innerHTML = data.city + ', ' + data.state + ' <i class="bi bi-chevron-down"></i>';
        window.locationReady = true;  
        if (typeof reloadActiveTab === 'function') {
            reloadActiveTab('ll', { lat: data.lat, lng: data.lng });
        }
        if (typeof loadNearbyVenues === 'function') {
            loadNearbyVenues();
        }
    }).catch(() => {
        
    });
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
}

if(DOM.inputHeader) {
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
 
document.addEventListener("DOMContentLoaded", function () {
    let selectedDatesTemp = [];  
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
  
    DOM.keywordHeader.addEventListener('focus', function () {
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
  
      // ❌ skip invalid links
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