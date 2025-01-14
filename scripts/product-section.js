import Slider from './product-slider';

export default class ProductSlider {
  static SECTION_CLASS = 'product-section';
  static SPINNER_CLASS = '.product-section__spinner';
  static ERROR_MISSING_PRODUCT_SECTION = 'Missing slider element';
  static ERROR_MISSING_FETCH_URL = 'Missing URL for fetching products';

  constructor(productSection) {
    this.productSection = productSection;

    if (!this.productSection) {
      throw new Error(ProductSlider.ERROR_MISSING_PRODUCT_SECTION);
    }

    this.fetchProducts();
  }

  getFetchUrl(element) {
    const fetchUrl = new URL(chocante.ajaxurl);

    fetchUrl.searchParams.append('nonce', chocante.nonce);
    fetchUrl.searchParams.append('lang', chocante.lang);
    fetchUrl.searchParams.append('action', 'get_product_section');

    for (const [key, value] of Object.entries(element.dataset)) {
      fetchUrl.searchParams.append(key, value);
    }

    return fetchUrl;
  }

  async fetchProducts() {
    const fetchUrl = this.getFetchUrl(this.productSection);

    try {
      const response = await fetch(fetchUrl);
      const featuredHtml = await response.text();

      if (featuredHtml) {
        const spinnerElement = this.productSection.querySelector(ProductSlider.SPINNER_CLASS);

        if (spinnerElement) {
          spinnerElement.remove();
        }

        this.productSection.insertAdjacentHTML('beforeend', featuredHtml);
        new Slider(this.productSection.className);
      }
    } catch (error) {
      throw new Error(error);
    }
  }
}

const productSection = document.querySelectorAll(`.${ProductSlider.SECTION_CLASS}`);

for (let section of Array.from(productSection)) {
  new ProductSlider(section);
}