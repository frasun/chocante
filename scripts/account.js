jQuery(function ($) {
  const BREAKPOINT = 768;

  $('.woocommerce-MyAccount-content__back-button').on('click', () => {
    if (window.innerWidth < BREAKPOINT) {
      $('.woocommerce-MyAccount-content').slideUp(() => {
        $('.page-header, .woocommerce-MyAccount-navigation').slideDown();
      });
    }
  });

  $('.woocommerce-MyAccount-navigation .is-active').on('click', (event) => {
    if (window.innerWidth < BREAKPOINT) {
      event.preventDefault();

      $('.page-header, .woocommerce-MyAccount-navigation').slideUp(() => {
        $('.woocommerce-MyAccount-content').slideDown();
      });
    }
  });

  $(window).on('resize', () => {
    if (window.innerWidth >= BREAKPOINT) {
      $('.woocommerce-MyAccount-content, .woocommerce-MyAccount-navigation, .page-header').removeAttr('style');
    }
  });
});