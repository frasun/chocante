import ModalService from './modal-service';
import Modal from './modal';
import ChocanteWooCommerce from './woocommerce';
import MenuScroll from './menu-scroll';

class Chocante {
  static MOBILE_BREAKPOINT = 1024;

  constructor() {
    new ModalService();
    new Modal('.mobile-menu', '.site-header__toggle', Chocante.MOBILE_BREAKPOINT);
    new MenuScroll('.site-header');

    this.setDropdownSize();
    window.addEventListener('resize', this.setDropdownSize);
  }

  setDropdownSize() {
    const dropdowns = document.querySelectorAll(':where(.site-header, .mobile-menu) .wcml-cs-submenu, :where(.site-header, .mobile-menu) .wpml-ls-sub-menu');

    Array.from(dropdowns).forEach(dropdown => {
      const maxHeight = window.innerHeight - dropdown.getBoundingClientRect().y - 20;

      dropdown.style.maxHeight = `${maxHeight}px`;
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
          scrollTop: scrollElement.offset().top - $('.site-header').height() - SCROLL_OFFSET - adminBarHeight,
        },
        SCROLL_DURATION
      );
    }
  };
});
