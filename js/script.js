const input = document.getElementById('locationInput');
const results = document.getElementById('locationResults');

if(input) {
    input.addEventListener('click', function () {
        const q = this.value.trim();

        if (q.length === 0) {
            results.innerHTML = `
                <div class="current-location" id="useCurrentLocation">
                    <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2"> Current location </span>  
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
                    <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2"> Current location </span>
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
                        ${item.zip ? item.zip + ', ' : ''}${item.city}, ${item.state}
                    </li>`;
                });
                html += '</ul>'
                results.innerHTML = html;
            });
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

    fetch(`./ajax/load-events.php?${params}`)
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
            if(location.flag !== 'reset') {
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

        this.disabled = true;
        loadMoreBtn.querySelector('#btnSpinner').classList.remove('d-none');
       
        fetch(`./ajax/load-more-events.php?page=${page}&perPage=${perPage}&performerId=${performerId}`)
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
    const names = eperformers.map(performer => performer.name ?? null).filter(Boolean);
    const dataPerformers = names.join('|');
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
                    ${event.text.name}
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


if (navigator.geolocation) {

    console.log('geolocation supported');

    navigator.geolocation.getCurrentPosition(
        function(position) {

            console.log('position', position);

            fetch('./ajax/save-location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                })
            })
            .then(res => res.text())
            .then(data => console.log('Server response:', data))
            .catch(err => console.error('Fetch error:', err));

        },
        function(error) {
            console.error('Geolocation error:', error.message);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}
