export default {
  init() {
    // JavaScript to be fired on all pages
  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
    // toggles hamburger and nav open and closed states
    var removeClass = true;
    $('.hamburger').click(function () {
      $('.hamburger').toggleClass('is-active');
      $('#sideNav').toggleClass('sideNav-open');
      $('.sideNavBody').toggleClass('sideNavBody-push');
      removeClass = false;
    });

    $('.sideNav').click(function() {
      removeClass = false;
    });

    document.addEventListener('touchstart', function(e) {
      if (removeClass && !$(e.target).hasClass('sideNav') && $('.sideNav').has($(e.target)).length === 0) {
         $('.hamburger').removeClass('is-active');
         $('#sideNav').removeClass('sideNav-open');
         $('.sideNavBody').removeClass('sideNavBody-push');
      }
      removeClass = true;
    }, false);

    $(window).scroll(function () {
      if ($(this).scrollTop() > 100) {
        $('.scrollup').fadeIn();
      } else {
        $('.scrollup').fadeOut();
      }
    });

    $('.scrollup').click(function () {
      $('html, body').animate({
        scrollTop: 0,
      }, 600);
      return false;
    });

    $('a[href*="#contactus"]').on('click', function (e) {
      e.preventDefault()

      $('html, body').animate(
        {
          scrollTop: $($(this).attr('href')).offset().top,
        },
        100,
        'linear'
      )
    })
  },
};
