function copyPromoCode(code, el) {
    navigator.clipboard.writeText(code).then(function () {
        const originalText = el.innerHTML;
        el.innerHTML = '<div class="promo text-success border-success"><strong>COPIED!</strong></div>';
        el.classList.add('copied');

        setTimeout(function () {
            el.innerHTML = originalText;
            el.classList.remove('copied');
        }, 1500);
    });
}

const input = document.getElementById('locationInput');
const results = document.getElementById('locationResults');

input.addEventListener('click', function () {
    const q = this.value.trim();

    if (q.length === 0) {
        results.innerHTML = `
            <div class="current-location" id="useCurrentLocation">
                Current location
            </div>
        `;
        return;
    }
});

input.addEventListener('keyup', function () {
    const q = this.value.trim();

    if (q.length === 0) {
        results.innerHTML = `
            <div class="current-location" id="useCurrentLocation">
                Current location
            </div>
        `;
        return;
    }
    
    if (q.length < 2) {
        results.innerHTML = '';
        return;
    }

    fetch(`./ajax/location-search.php?q=${q}`)
        .then(res => res.json())
        .then(data => {
            let html = '<ul>';
            data.forEach(item => {
                html += `<li class="result-item"
                    data-city="${item.city}"
                    data-state="${item.state}"
                    data-zip="${item.zip ?? ''}">
                    <strong>${item.city}</strong>, ${item.state}
                    ${item.zip ? ' ' + item.zip : ''}
                </li>`;
            });
            html += '</ul>'
            results.innerHTML = html;
        });
});

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
        ? `${city}, ${state} ${zip}`
        : `${city}, ${state}`;

    results.innerHTML = '';

    updateHeading(city, state, zip, dcat);

    updateEventsSection({ city, state, zip, cpid, dcat });
});

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

    fetch(`./ajax/load-events.php?${params}`)
        .then(res => res.text())
        .then(html => {
            if(html == 'no') {
                document.getElementById('location-no-results').innerHTML = '<strong>No ' + location.dcat + ' available in your selected area</strong><p>Try changing locations or browse through the available ' + location.dcat + ' below</p>';
            }else{
                document.getElementById('location-no-results').innerHTML = '';
                document.getElementById('eventsSection').innerHTML = html;
            }            
        })
        .catch(err => {
            console.error('Failed to load events', err);
        });
}

function updateHeading(city, state, zip, dcat) {
    const heading = document.getElementById('locationHeading');
    if (!heading) return;
    const capitalized = dcat.charAt(0).toUpperCase() + dcat.slice(1);
    
    if (zip) {        
        heading.textContent = `${capitalized} near ${zip}`;
    } else if (city && state) {
        heading.textContent = `${capitalized} near ${city}, ${state}`;
    } else {
        heading.textContent = '';
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

function toApiDate(dateObj) {
    if (!dateObj) return null;

    const year  = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day   = String(dateObj.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}



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



/* Load more events */

const loadMoreBtn = document.getElementById('loadMoreBtn');
const eventsSection = document.getElementById('eventsSection');

if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
        const page        = this.dataset.page;
        const performerId = this.dataset.performer;
        const perPage     = this.dataset.perpage;

        this.disabled = true;
        this.innerHTML = 'Loading...';

        fetch(`./ajax/load-more-events.php?page=${page}&perPage=${perPage}&performerId=${performerId}`)
            .then(res => res.json())
            .then(data => {

                data.events.forEach(event => {
                    eventsSection.insertAdjacentHTML('beforeend', renderEvent(event));
                });

                if (data.hasMore) {
                    loadMoreBtn.dataset.page = data.nextPage;
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.innerHTML = 'Load More <i class="bi bi-box-arrow-in-down fs-4"></i>';
                } else {
                    loadMoreBtn.remove();
                }
            });
    });
}

function formatEventDate(dateStr) {
    const date = new Date(dateStr);

    const month = date.toLocaleString('en-US', { month: 'long' });
    const day   = date.getDate();
    const year  = date.getFullYear();

    // ordinal suffix
    const suffix = (day % 10 === 1 && day !== 11) ? 'st'
                 : (day % 10 === 2 && day !== 12) ? 'nd'
                 : (day % 10 === 3 && day !== 13) ? 'rd'
                 : 'th';

    return `${month}. ${day}${suffix}, ${year}`;
}

function getWeekdayName(weekdayNumber) {
    const days = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ];

    return days[weekdayNumber - 1] || '';
}

function renderEvent(event) {
    return `
        <div class="performer-event-item">
            <div class="row">
                <div class="performer-event-item-info col-sm-9">
                    <h3>${event.text.name}</h3>
                    <ul class="row ps-0">
                        <li class="col-sm-5">
                            <span>Venue</span>
                            ${event.venue.text.name}
                            <span class="location">${event.city.text.name}, ${event.stateProvince.text.abbr}</span>
                        </li>
                        <li class="col-sm-4">
                            <span>${getWeekdayName(event.date.weekday)}</span>
                            ${formatEventDate(event.date.date)}
                        </li>
                        <li class="col-sm-3">
                            <span>Time</span>
                            ${event.date.text.time}
                        </li>
                    </ul>
                </div>
                <div class="performer-event-item-price col-sm-3">
                    ${event.pricingInfo ? `
                        <span>Price From</span>
                        <strong>${event.pricingInfo.lowPrice.text.formatted}</strong>
                        <a href="/event.php?id=${event.id}">Get Tickets</a>
                    ` : `
                        <a href="/event.php?id=${event.id}" class="so-cta-tickets">Get Tickets</a>
                    `}                   
                </div>
            </div>
        </div>
    `;
}


flatpickr("#dateRange", {
    mode: "range",
    dateFormat: "M d, Y",
	minDate: "today",
    allowInput: false,
    locale: {
        rangeSeparator: " to "
    },
    onChange: function(selectedDates) {
        const cpid  = input.dataset.cpid;
        const dcat  = input.dataset.dcat;
        const startDate = toApiDate(selectedDates[0]);
        const endDate   = toApiDate(selectedDates[1]);
        if (selectedDates.length === 2) {
            updateHeading(null, null, null, null);
            updateEventsSection({
                startDate, endDate, cpid, dcat
            });   
        }
    }
});