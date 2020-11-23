export default {
  init() {
    // JavaScript to be fired on the home page
  },
  finalize() {
    // JavaScript to be fired on the home page, after the init JS
    $('.strapline-container').slick({
      slidesToShow: 8,
      slidesToScroll: 1,
      prevArrow: '<i class="fas fa-chevron-left"></i>',
      nextArrow: '<i class="fas fa-chevron-right"></i>',
      infinite: true,
      autoplay: true,
      autoplaySpeed: 1500,
      responsive: [
        {
          breakpoint: 991,
          settings: {
            slidesToShow: 6,
          },
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 5,
          },
        },
        {
          breakpoint: 600,
          settings: {
            slidesToShow: 4,
          },
        },
        {
          breakpoint: 480,
          settings: {
            slidesToShow: 3,
          },
        },
      ],
    });

    $('.slick-quotes').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      infinite: true,
      dots: false,
      asNavFor: '.slick-names',
      fade: false,
      cssEase: 'linear',
      autoplaySpeed: 1500,
      speed: 200,
      arrows: true,
      prevArrow: '<i class="fas fa-chevron-left"></i>',
      nextArrow: '<i class="fas fa-chevron-right"></i>',
      responsive: [
        {
          breakpoint: 768,
          settings: {
            arrows: false,
          },
        },
      ],
    });


    $('.slick-names').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      infinite: true,
      arrows: false,
      dots: false,
      asNavFor: '.slick-quotes',
      fade: true,
      cssEase: 'linear',
      speed: 200,
    });
  },
};
