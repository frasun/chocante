import ModalService from './modal-service';
import Modal from './modal';
import ChocanteWooCommerce from './woocommerce';
import MenuScroll from './menu-scroll';
import { MOBILE_BREAKPOINT } from './constants';
// import Splide from '@splidejs/splide';

class Chocante {
  constructor() {
    // Modals.
    new ModalService();

    // Mobile menu.
    new Modal('.mobile-menu', '.site-header__toggle', MOBILE_BREAKPOINT);

    // Menu on scroll.
    new MenuScroll('.site-header');

    // Dropdowns - mini-cart, currency/language switcher etc.
    this.setDropdownSize();
    window.addEventListener('resize', this.setDropdownSize);

    // Sliders.
    // window.Splide = Splide;
    this.setPostSliders();
  }

  setDropdownSize() {
    const dropdowns = document.querySelectorAll(':where(.site-header, .mobile-menu) .wcml-cs-submenu, :where(.site-header, .mobile-menu) .wpml-ls-sub-menu');

    Array.from(dropdowns).forEach(dropdown => {
      const maxHeight = window.innerHeight - dropdown.getBoundingClientRect().y - 20;

      dropdown.style.maxHeight = `${maxHeight}px`;
    });
  }

  setPostSliders() {
    // @todo: Chocante - Bricks Change class to '.splide'.
    const postSliders = document.querySelectorAll('.post-slider');

    Array.from(postSliders).forEach(slider => {
      new Splide(slider, {
        type: 'fade',
        arrows: false,
        speed: 700,
        rewind: true,
        autoplay: true,
      }).mount();
    });
  }
}

new Chocante();
new ChocanteWooCommerce();

jQuery(function ($) {
  // Include header when scrolling to notices.
  // @see: /plugins/woocommerce/assets/js/frontend/woocommerce.js:87
  const SCROLL_OFFSET = 15;
  const SCROLL_DURATION = 350;

  $.scroll_to_notices = function (scrollElement) {
    if (scrollElement.length) {
      const adminBarHeight = $('#wpadminbar').length ? $('#wpadminbar').height() : 0;

      $('html, body').animate(
        {
          // scrollTop: scrollElement.offset().top - $('.site-header').height() - SCROLL_OFFSET - adminBarHeight,
          scrollTop: 0
        },
        SCROLL_DURATION
      );
    }
  };

  // Footer menu mobile.
  $('.site-footer__nav .site-footer__nav-header').on('click', (event) => {
    if (window.innerWidth >= MOBILE_BREAKPOINT) {
      return;
    }

    $(event.target).parent().find('.menu').slideToggle();
    $(event.target).parent().toggleClass('site-footer__nav--open');
  });

  $(window).on('resize', () => {
    if (window.innerWidth >= MOBILE_BREAKPOINT) {
      $('.site-footer__nav--open').removeClass('site-footer__nav--open');
      $('.site-footer__nav .menu').removeAttr('style');
    }
  });
});
