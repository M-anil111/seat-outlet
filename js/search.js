$('.suggestion-slider').on('setPosition', function(){
    equalHeightSlider('suggestion-slider', 'venue-card');
});
  
const $sugg_slider = $('.suggestion-slider');
$sugg_slider.slick({
    slidesToShow: 4,
    slidesToScroll: 1,
    arrows: true,
    autoplay: true,
    dots: false,
    infinite: false,
    responsive: [
        { breakpoint: 992, settings: { slidesToShow: 3 } },
        { breakpoint: 768, settings: { slidesToShow: 2 } },
        { breakpoint: 576, settings: { slidesToShow: 1.5 } }
    ]
});