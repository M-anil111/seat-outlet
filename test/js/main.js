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

  // Video modal: set iframe src on open, clear on close (stops playback)
//   function buildAutoplayUrl(src) {
//     if (!src) return '';

//     const hasQuery = src.includes('?');
//     const needsAutoplay = !/[?&]autoplay=/.test(src);
//     const needsMute = !/[?&]mute=/.test(src);
//     const params = [];

//     if (needsAutoplay) params.push('autoplay=1');
//     // Autoplay on most browsers requires muted audio.
//     if (needsMute) params.push('mute=1');

//     if (params.length === 0) return src;
//     return `${src}${hasQuery ? '&' : '?'}${params.join('&')}`;
//   }

//   const videoModalEl = document.getElementById('videoModal');
//   const videoIframe = document.getElementById('videoModalIframe');

//   if (videoModalEl && videoIframe) {
//     videoModalEl.addEventListener('show.bs.modal', (event) => {
//       const triggerEl = event.relatedTarget;
//       const rawSrc = triggerEl?.getAttribute?.('data-video-src') || '';
//       videoIframe.src = buildAutoplayUrl(rawSrc);
//     });

//     videoModalEl.addEventListener('hidden.bs.modal', () => {
//       videoIframe.src = '';
//     });
//   }
// });

// document.addEventListener("DOMContentLoaded", function () {

//   const modal = document.getElementById('videoModal');
//   const iframe = document.getElementById('videoIframe');

//   modal.addEventListener('show.bs.modal', function (event) {
//     const button = event.relatedTarget;
//     const videoId = button.getAttribute('data-video-id');

//     iframe.src = "https://www.youtube.com/embed/" 
//                  + videoId 
//                  + "?autoplay=1&mute=1&rel=0";
//   });

//   modal.addEventListener('hidden.bs.modal', function () {
//     iframe.src = "https://youtu.be/TfU0qjuZkJ4";
//   });

// });


						// Load the YouTube IFrame Player API code asynchronously
						var tag = document.createElement('script');
						tag.src = "https://www.youtube.com/player_api";
						var firstScriptTag = document.getElementsByTagName('script')[0];
						firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

						// This function will be called once the YouTube API is loaded
						var player;
						function onYouTubePlayerAPIReady() {
							// Set up the player but do not load video yet
							player = new YT.Player('ytplayer', {
								height: '450',
								width: '600',
								videoId: 'TfU0qjuZkJ4', // Video ID here
								playerVars: {
									'autoplay': 0,  // No autoplay initially
									'modestbranding': 1,
									'showinfo': 0,
									'controls': 1
								}
							});
						}
						$("#ytplayer").hide();
						// When the thumbnail is clicked, load and autoplay the video
						$('#ytifvideoplaceholder').on('click', function() {
							$(this).hide();
							$("#ytplayer").show();
							player.playVideo();
						});
					


// Email validation helper
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}


document.querySelectorAll(".category-pill").forEach(btn => {
  btn.addEventListener("click", function () {

    // Active button
    document.querySelectorAll(".category-pill").forEach(b => b.classList.remove("active"));
    this.classList.add("active");

    // Show correct tab
    document.querySelectorAll(".tab-pane").forEach(tab => tab.classList.remove("active"));
    document.querySelector(this.dataset.target).classList.add("active");

    // Refresh slick after showing tab
    $('.tab-pane.active .event-slider').slick('setPosition');

  });
});