$('.suggestion-slider').on('setPosition', function(){
    equalHeightSlider('suggestion-slider', 'venue-card');
});
  
function initSuggestionSlider(loader = '') {
    const $slider = $('.suggestion-slider'); 
    if ($slider.hasClass('slick-initialized')) {
      $slider.slick('unslick');
    }
  
    $slider.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: loader === 'skeleton' ? false : true,
        autoplay: loader === 'skeleton' ? false : true,
        infinite: false,
        dots: false,
        responsive: [
            { breakpoint: 992, settings: { slidesToShow: 3 } },
            { breakpoint: 768, settings: { slidesToShow: 2 } },
            { breakpoint: 576, settings: { slidesToShow: 1.5 } }
        ]
    });
}

function buildSuggestionSkeleton(count = 6) {
    let html = '';
  
    for (let i = 0; i < count; i++) {
        html += `
            <div class="venue-card-skeleton">
                <div class="skeleton-img shimmer"></div>
                <div class="venue-content text-center p-3">
                    <div class="skeleton-line skeleton-title shimmer"></div>
                    <div class="skeleton-line skeleton-location shimmer"></div>
                </div>
            </div>
        `;
    }
  
    return html;
}

let suggestionLoadToken = 0;

async function fetchImageSequentially(img, loadToken) {
    if (!img || loadToken !== suggestionLoadToken) return;
  
    const artist = img.dataset.artist ? decodeURIComponent(img.dataset.artist) : '';
    const venue = img.dataset.venue ? decodeURIComponent(img.dataset.venue) : '';
    const category = img.dataset.category || '{}';
  
    const endpoint =
        `/ajax/get-image.php?artist=${encodeURIComponent(artist)}` +
        `&venue=${encodeURIComponent(venue)}` +
        `&category=${encodeURIComponent(category)}`;
  
    try {
      const res = await fetch(endpoint);  
      const data = await res.json();
  
      if (loadToken !== suggestionLoadToken) return;
  
      if (data && data.image) {
        const tempImg = new Image();

        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
  
        tempImg.onload = function () {
            img.src = data.image;
            
          requestAnimationFrame(() => {
            img.style.opacity = '1';
            img.classList.add('loaded');
          });
        };

        tempImg.onerror = function () {
            img.style.opacity = '1';
            img.classList.add('loaded');
        };

        tempImg.src = data.image;
        
      }
  
    } catch (err) {
      console.error('Image load failed:', name, err);
    }
  }
  
  async function loadImagesOneByOne(container, loadToken) {
    const images = container.querySelectorAll('.venue-dynamic-image');
  
    for (const img of images) {
      if (loadToken !== suggestionLoadToken) break;
  
      await fetchImageSequentially(img, loadToken);
    }
  }
  
window.loadTopSuggestions = function(keyword) {

    const container = document.querySelector('.suggestion-slider');
    if (!container || !keyword) return;

    const loadToken = ++suggestionLoadToken;

    container.innerHTML = buildSuggestionSkeleton(6);
    initSuggestionSlider('skeleton');

    fetch(`/ajax/get-suggestions.php?q=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {

            if (loadToken !== suggestionLoadToken) return;

            if (!data || !data.length) {
                document.querySelector('.section-suggestions').classList.add('d-none');
                return;
            }

            const $slider = $('.suggestion-slider');
            if ($slider.hasClass('slick-initialized')) {
                $slider.slick('unslick');
            }

            let html = '';

            data.forEach((item, index) => {

                const loadingType = index < 2 ? 'eager' : 'lazy';
                const fetchPriority = index < 2 ? 'high' : 'low';

                const isArtist = item.type === 'artist';
                const isVenue = item.type === 'venue';

                html += `
                    <a href="${item.slug}" class="team-link">
                        <div class="card venue-card">
                            <div class="venue-img">
                                <img 
                                    src="${item.image}"
                                    alt="${item.name}"
                                    class="img-fluid venue-dynamic-image blur-image"
                                    ${isArtist ? `data-artist="${encodeURIComponent(item.name)}" data-category='${encodeURIComponent(JSON.stringify(item.category || {}))}'` : ''}
                                    ${isVenue ? `data-venue="${encodeURIComponent(item.name)}"` : ''}
                                    loading="${loadingType}"
                                    fetchpriority="${fetchPriority}"
                                    width="278"
                                    height="200"
                                >
                            </div>
                            <div class="venue-content text-center">
                                <h5 class="venue-title">${item.name}</h5>
                                <p class="venue-location mb-0">${item.meta}</p>
                            </div>
                        </div>
                    </a>
                `;
            });

            container.innerHTML = html;

            setTimeout(() => {
                initSuggestionSlider();
            }, 50);

            loadImagesOneByOne(container, loadToken);
        })
        .catch(err => {
            console.error('Suggestion error:', err);
            container.innerHTML = '<p>Error loading suggestions</p>';
        });
};

document.addEventListener('DOMContentLoaded', function () {
    const section = document.querySelector('.section-suggestions');
    const keyword = document.getElementById('keywordHeader')?.value || '';
  
    if (!section || !keyword) return;
  
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadTopSuggestions(keyword);
                obs.disconnect();
            }
        });
    }, { rootMargin: '600px' });
    observer.observe(section);
});