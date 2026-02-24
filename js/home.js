$(document).ready(function () {
 
    $('.custom-slider').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        infinite: true,
        arrows: true,
        autoplay: true,
        dots: false,
        responsive: [
            { breakpoint: 992, settings: { slidesToShow: 3 } },
            { breakpoint: 768, settings: { slidesToShow: 2 } },
            { breakpoint: 576, settings: { slidesToShow: 1 } }
        ]
    });

    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).data('bs-target');
        $(target).find('.custom-slider').slick('setPosition');
    });

  $('.team-slider').slick({
      slidesToShow: 4,
      slidesToScroll: 1,
      arrows: true,
      autoplay:true,
      dots: false,
      infinite: false,
      responsive: [
          {
              breakpoint: 992,
              settings: { slidesToShow: 3 }
          },
          {
              breakpoint: 768,
              settings: { slidesToShow: 2 }
          },
          {
              breakpoint: 576,
              settings: { slidesToShow: 1 }
          }
      ]
  });

  $('.venue-slider').slick({
    slidesToShow: 4,
    slidesToScroll: 1,
    arrows: true,
    autoplay:true,
    dots: false,
    infinite: false,
    responsive: [
        {
            breakpoint: 992,
            settings: { slidesToShow: 3 }
        },
        {
            breakpoint: 768,
            settings: { slidesToShow: 2 }
        },
        {
            breakpoint: 576,
            settings: { slidesToShow: 1 }
        }
    ]
});

});

// Category pill active state management
document.addEventListener('DOMContentLoaded', function() {
  // Handle category pill clicks
  const categoryPills = document.querySelectorAll('.category-pill');
  categoryPills.forEach(pill => {
    pill.addEventListener('click', function() {
      // Remove active class from all pills
      categoryPills.forEach(p => p.classList.remove('active'));
      // Add active class to clicked pill
      this.classList.add('active');
  });
});

// Newsletter form submission
const newsletterForm = document.getElementById('newsletter-submit');
const newsletterEmail = document.getElementById('newsletter-email');

if (newsletterForm && newsletterEmail) {
  newsletterForm.addEventListener('click', function(e) {
      e.preventDefault();
      const email = newsletterEmail.value.trim();      
      if (email && isValidEmail(email)) {
        // Here you would typically send the email to a server
        alert('Thank you for subscribing!');
        newsletterEmail.value = '';
      } else {
        alert('Please enter a valid email address.');
      }
  });
  // Allow Enter key to submit
  newsletterEmail.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        newsletterForm.click();
      }
  });
}

// Search form submission
const searchButton = document.querySelector('.search-bar-container button');
if (searchButton) {
  searchButton.addEventListener('click', function(e) {
      e.preventDefault();
      // Here you would typically handle search functionality
      const inputs = document.querySelectorAll('.search-bar-container input');
      const searchData = {
        location: inputs[0]?.value || '',
        date: inputs[1]?.value || '',
        query: inputs[2]?.value || ''
      };
    
      // Placeholder for search functionality
      console.log('Search:', searchData);
       // Update "Our top picks in ..." location from the location input
       const topPicksLocationEl = document.getElementById('top-picks-location');
       if (topPicksLocationEl && searchData.location.trim() !== '') {
         topPicksLocationEl.textContent = searchData.location.trim();
       }
  });
}

// Clicking "Select your location" focuses the location input in the search bar
const locationSelectorButton = document.querySelector('.location-selector-button');
if (locationSelectorButton) {
  locationSelectorButton.addEventListener('click', function(e) {
      e.preventDefault();
      const locationInput = document.querySelector('.search-bar-container input');
      if (locationInput) {
        locationInput.focus();
        locationInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
  });
}

// Card hover effects are handled by CSS, but we can add click handlers if needed
const eventCards = document.querySelectorAll('.card-event, .venue-list-card');
  eventCards.forEach(card => {
    card.addEventListener('click', function() {
    // Here you would typically navigate to event details page
    console.log('Event card clicked:', this);
  });
});
});

// Email validation helper
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/* Location Input Header Start */
const inputHeader = document.getElementById('locationInputHeader');
const resultsHeader = document.getElementById('locationResultsHeader');
const latHeader = document.getElementById('latHeader');
const lngHeader = document.getElementById('lngHeader');

if(inputHeader) {
  inputHeader.addEventListener('click', function () {
      const q = this.value.trim();

      if (q.length === 0) {
          resultsHeader.innerHTML = `
              <div class="current-location" id="useCurrentLocationHeader">
                  <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2"> Current location </span>  
              </div>
          `;
          return;
      }
  });

  inputHeader.addEventListener('keyup', function () {
      const q = this.value.trim();

      if (q.length === 0) {
          resultsHeader.innerHTML = `
              <div class="current-location" id="useCurrentLocationHeader">
                  <i class="bi bi-send ms-1 me-2"></i> <span class="ms-4 ps-2"> Current location </span>
              </div>
          `;
          return;
      }
      
      if (q.length < 2) {
          resultsHeader.innerHTML = '';
          return;
      }

      fetch(`./ajax/location-search.php?q=${q}`)
          .then(res => res.json())
          .then(data => {
              let html = '<ul>';
              data.forEach(item => {
                  html += `<li class="result-item">
                      ${item.zip ? item.zip + ', ' : ''}${item.city}, ${item.state}
                  </li>`;
              });
              html += '</ul>'
              resultsHeader.innerHTML = html;
          });
  });
}

if(resultsHeader) {
    resultsHeader.addEventListener('click', function (e) {

        if (e.target.id === 'useCurrentLocationHeader') {
            getCurrentLocationHeader();
            return;
        }

        const item = e.target.closest('.result-item');
        if (!item) return;

        inputHeader.value = item.textContent.trim();
		resultsHeader.innerHTML = '';
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

            inputHeader.value = 'Current location';
           
        },
        error => {
            inputHeader.value = '';
            alert('Unable to access your location');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000
        }
    );
}

/* Location Input Header End */



/* Date Range Input Header Start */

function toApiDateHeader(dateObj) {
    if (!dateObj) return null;

    const year  = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day   = String(dateObj.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const dateRangeHeader = document.getElementById('dateRangeHeader');
const datePickerSectionHeader = document.getElementById('datePickerSectionHeader');

let startInputHeader = document.getElementById('startInputHeader');
let endInputHeader = document.getElementById('endInputHeader');

let selectedStart = null;
let selectedEnd = null;

let pickerHeader = flatpickr('#calendarHeader', {
    inline: true,
    minDate: "today",
    mode: 'range',
    showMonths: 2,
    dateFormat: "M d, Y",
    onChange: function(selectedDates) {
        selectedStart = selectedDates[0] || null;
        selectedEnd   = selectedDates[1] || null;

        if (selectedDates.length === 2) {
            const startDate = toApiDateHeader(selectedStart);
            const endDate   = toApiDateHeader(selectedEnd);

            startInputHeader.value = startDate;
            endInputHeader.value = endDate;
        }
    }
});

// Open picker on input click
const dateArrowHeader = document.getElementById('dateArrowHeader');
if(dateRangeHeader) {
    dateRangeHeader.addEventListener('click', (e) => {
        e.stopPropagation();

        const isHidden = datePickerSectionHeader.classList.toggle('opacity-zero');

        if (isHidden) {
            dateArrowHeader.classList.remove('bi-chevron-up');
            dateArrowHeader.classList.add('bi-chevron-down');
        } else {
            dateArrowHeader.classList.remove('bi-chevron-down');
            dateArrowHeader.classList.add('bi-chevron-up');

            setTimeout(() => {
                pickerHeader.redraw();
            }, 10);
        }
    });
}
// Close on outside click
document.addEventListener('click', (e) => {
    if (
        !datePickerSectionHeader.contains(e.target) &&
        e.target !== dateRangeHeader
    ) {
        datePickerSectionHeader.classList.add('opacity-zero');
    }
});

const resetDatesHeader = document.getElementById('resetDatesHeader');
if(resetDatesHeader) {
    resetDatesHeader.addEventListener('click', () => {
        pickerHeader.clear();
        selectedStart = selectedEnd = null;
        startInputHeader.value = '';
        endInputHeader.value = '';
        dateRangeHeader.value = '';
    });
}


const cancelDatesHeader = document.getElementById('cancelDatesHeader');
if(cancelDatesHeader) {
    cancelDatesHeader.addEventListener('click', () => {
        datePickerSectionHeader.classList.add('opacity-zero');
        startInputHeader.value = '';
        endInputHeader.value = '';
    });
}

const applyDatesHeader = document.getElementById('applyDatesHeader');
if(applyDatesHeader) {
    applyDatesHeader.addEventListener('click', () => {
        if (!selectedStart || !selectedEnd) return;

        const startText = flatpickr.formatDate(selectedStart, 'm/d/Y');
        const endText = flatpickr.formatDate(selectedEnd, 'm/d/Y');

        dateRangeHeader.value = startText + ' - ' + endText;

        datePickerSectionHeader.classList.add('opacity-zero');
    });
}

/* Date Range Input Header End */



/* Keyword Input Header Start */

const keywordHeader = document.getElementById('keywordHeader');
const keywordResultsHeader = document.getElementById('keywordResultsHeader');
const keywordType = document.getElementById('keywordType');
const keywordId = document.getElementById('keywordId');

if(keywordHeader) {	

	keywordHeader.addEventListener('keyup', function () {
      	const q = this.value.trim();

      	if (q.length < 2) {
			keywordResultsHeader.innerHTML = '';
          	return;
      	}

      	fetch(`./ajax/keyword-search.php?q=${encodeURIComponent(q)}`)
		.then(res => res.json())
		.then(data => {

			let html = '<ul class="search-suggestions">';

			// =====================
			// ARTISTS
			// =====================
			if (data.artists && data.artists.length > 0) {
				html += '<li class="suggestion-label">Artists</li>';
				data.artists.forEach(item => {
					html += `
					<li class="result-item"
						data-type="artist"
						data-id="${item.id}"
						data-slug="${item.slug}">
						${item.name}
					</li>`;
				});
			}

			// =====================
			// EVENTS
			// =====================
			if (data.events && data.events.length > 0) {
				html += '<li class="suggestion-label">Events</li>';
				data.events.forEach(item => {
					html += `
					<li class="result-item"
						data-type="event"
						data-id="${item.id}"
						data-slug="${item.slug}">
						${item.name}
						${item.date ? `<span class="small text-muted">(${item.date})</span>` : ''}
					</li>`;
				});
			}

			// =====================
			// VENUES
			// =====================
			if (data.venues && data.venues.length > 0) {
				html += '<li class="suggestion-label">Venues</li>';

				data.venues.forEach(item => {
					html += `
					<li class="result-item"
						data-type="venue"
						data-id="${item.id}"
						data-slug="${item.slug}">
						${item.name}
						${item.city ? `<span class="small text-muted">(${item.city}${item.state ? ', ' + item.state : ''})</span>` : ''}
					</li>`;
				});
			}

			html += '</ul>';

			keywordResultsHeader.innerHTML = html;
		});
  	});
}

if(keywordResultsHeader) {
    keywordResultsHeader.addEventListener('click', function (e) {

        const item = e.target.closest('.result-item');
        if (!item) return;

        keywordHeader.value = item.textContent.trim();
        keywordType.value = item.dataset.type;
        keywordId.value = item.dataset.id;
		keywordResultsHeader.innerHTML = '';
    });
}

/* Keyword Input Header End */