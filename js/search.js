/* Escape a value for HTML text and attributes: names come from the ticket API and are put into innerHTML templates below. */
function soEsc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
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

async function loadImagesOneByOne(container, loadToken) {
    await window.soBatchLoadImages(container, '.venue-dynamic-image', () => loadToken === suggestionLoadToken);
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
                    <a href="${soEsc(item.slug)}" class="team-link">
                        <div class="card venue-card">
                            <div class="venue-img">
                                <img 
                                    src="${soEsc(item.image)}"
                                    alt="${soEsc(item.name)}"
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
                                <h5 class="venue-title">${soEsc(item.name)}</h5>
                                <p class="venue-location mb-0">${soEsc(item.meta)}</p>
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