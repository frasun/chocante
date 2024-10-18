export default class MiniCart {
  static FRAGMENT_CART_COUNT = 'span.cart-count';
  static FRAGMENT_CART_CONTENT = 'div.widget_shopping_cart_content';
  static MINI_CART_COUNT = '.mini-cart__count';
  static MINI_CART_CONTENT = '.mini-cart__content'

  constructor(element) {
    const miniCartCount = element.querySelector(MiniCart.MINI_CART_COUNT);
    this.miniCartContent = element.querySelector(MiniCart.MINI_CART_CONTENT);

    this.updateContent(miniCartCount, this.miniCartContent);

    if (this.miniCartContent) {
      this.setContentHeight();
      window.addEventListener('resize', this.setContentHeight.bind(this));
      window.jQuery.blockUI.defaults.overlayCSS = { backgroundColor: '#fff', opacity: 0.7 };
    }
  }

  updateContent(count, content) {
    let fragments = JSON.parse(sessionStorage.getItem(wc_cart_fragments_params.fragment_name));

    if (!fragments) {
      fragments = {}
      fragments[MiniCart.FRAGMENT_CART_COUNT] = count.dataset.count;
    }

    if (count && fragments[MiniCart.FRAGMENT_CART_COUNT]) {
      count.innerHTML = fragments[MiniCart.FRAGMENT_CART_COUNT];
    }

    if (content && fragments[MiniCart.FRAGMENT_CART_CONTENT]) {
      content.innerHTML = fragments[MiniCart.FRAGMENT_CART_CONTENT];
    }

    window.clearInterval(this.cartUpdate);
  }

  setContentHeight() {
    const maxHeight = window.innerHeight - this.miniCartContent.getBoundingClientRect().y - 20;
    this.miniCartContent.style.maxHeight = `${maxHeight}px`;
  }
}
