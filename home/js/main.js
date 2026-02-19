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


  $(document).ready(function () {
 
   
 
    $('.event-slider').slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        infinite: true,
        autoplay:false,
        arrows: true,
        dots: false,
        adaptiveHeight: false,
        responsive: [
            { breakpoint: 992, settings: { slidesToShow: 3 } },
            { breakpoint: 768, settings: { slidesToShow: 2 } },
            { breakpoint: 576, settings: { slidesToShow: 1 } }
        ]
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