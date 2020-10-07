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
      autoplaySpeed: 4000,
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
      ],
    });

    $('.slick-quotes').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      infinite: true,
      arrows: false,
      dots: false,
      asNavFor: '.slick-images, .slick-names',
      fade: true,
      cssEase: 'linear',
      speed: 200,

    });

    $('.slick-images').slick({
      slidesToShow: 3,
      slidesToScroll: 1,
      infinite: true,
      arrows: false,
      dots: false,
      asNavFor: '.slick-quotes, .slick-names',
      centerMode: true,
      autoplay: true,
      autoplaySpeed: 5000,
      speed: 200,
      responsive: [
        {
          breakpoint: 480,
          settings: {
            slidesToShow: 1,
            // centerMode: false,
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
      asNavFor: '.slick-images, .slick-quotes',
      fade: true,
      cssEase: 'linear',
      speed: 200,
    });
  },
};
