export default class MiniCart {
  static FRAGMENT_CART_COUNT = 'cart-count';
  static MINI_CART_COUNT = '.mini-cart__count';
  static MINI_CART_CONTENT = '.mini-cart__content'

  constructor(element) {
    this.miniCartCount = element.querySelector(MiniCart.MINI_CART_COUNT);
    this.miniCartContent = element.querySelector(MiniCart.MINI_CART_CONTENT);

    if (this.miniCartCount) {
      this.updateContent();
      window.jQuery(document.body).on('wc_fragments_refreshed', this.updateContent.bind(this));
    }

    if (this.miniCartContent) {
      this.setContentHeight();
      window.addEventListener('resize', this.setContentHeight.bind(this));
      window.jQuery.blockUI.defaults.overlayCSS = { backgroundColor: '#fff', opacity: 0.7 };
    }
  }

  updateContent() {
    let fragments = JSON.parse(sessionStorage.getItem(wc_cart_fragments_params.fragment_name));
    let count = this.miniCartCount.dataset.count;

    if (fragments && fragments[MiniCart.FRAGMENT_CART_COUNT]) {
      count = fragments[MiniCart.FRAGMENT_CART_COUNT];
    }

    this.miniCartCount.innerHTML = count;
  }

  setContentHeight() {
    const maxHeight = window.innerHeight - this.miniCartContent.getBoundingClientRect().y - 20;
    this.miniCartContent.style.maxHeight = `${maxHeight}px`;
  }
}
