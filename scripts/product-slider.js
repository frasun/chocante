import Splide from '@splidejs/splide';

export default class ProductSlider {
  static LOOP = 'loop';
  static SLIDE = 'slide';
  static SLIDER_CLASS = '.splide';
  static SLIDE_CLASS = '.splide__slide:not(.splide__slide--clone)';
  static SPINNER_CLASS = '.product-section__spinner';
  static ERROR_MISSING_PRODUCT_SECTION = 'Missing slider element';
  static ERROR_MISSING_FETCH_URL = 'Missing URL for fetching products';

  constructor(productSection, fetchUrl) {
    this.fetchUrl = fetchUrl;
    this.productSection = document.querySelector(productSection);

    if (!this.productSection) {
      throw new Error(ProductSlider.ERROR_MISSING_PRODUCT_SECTION);
    }

    this.fetchProducts();
  }

  async fetchProducts() {
    if (!this.fetchUrl) {
      throw new Error(ProductSlider.ERROR_MISSING_FETCH_URL)
    }

    try {
      const response = await fetch(this.fetchUrl);
      const featuredHtml = await response.text();

      if (featuredHtml) {
        const spinnerElement = this.productSection.querySelector(ProductSlider.SPINNER_CLASS);

        if (spinnerElement) {
          spinnerElement.remove();
        }

        this.productSection.insertAdjacentHTML('beforeend', featuredHtml);
        this.initSlider();
        window.addEventListener('resize', this.initSlider.bind(this));
      }
    } catch (error) {
      throw new Error(error);
    }
  }

  getSliderType() {
    const screenWidth = window.innerWidth;
    const slidesCount = this.productSection.querySelectorAll(ProductSlider.SLIDE_CLASS).length;

    if (screenWidth >= 1180) {
      return slidesCount >= 8 ? ProductSlider.LOOP : ProductSlider.SLIDE;
    } else if (screenWidth >= 900) {
      return slidesCount >= 6 ? ProductSlider.LOOP : ProductSlider.SLIDE;
    } else if (screenWidth >= 600) {
      return slidesCount >= 4 ? ProductSlider.LOOP : ProductSlider.SLIDE;
    } else {
      return slidesCount > 1 ? ProductSlider.LOOP : ProductSlider.SLIDE;
    }
  }

  initSlider() {
    const sliderType = this.getSliderType();
    const labels = this.productSection.querySelector(ProductSlider.SLIDER_CLASS).dataset.aria;

    if (this.slider) {
      if (this.slider.is(sliderType)) {
        return;
      } else {
        this.slider.destroy(true);
      }
    }

    this.slider = new Splide(ProductSlider.SLIDER_CLASS, {
      type: sliderType,
      perPage: 1,
      gap: 20,
      drag: 'free',
      snap: true,
      speed: 350,
      mediaQuery: 'min',
      breakpoints: {
        600: {
          perPage: 2
        },
        900: {
          perPage: 3
        },
        1180: {
          gap: 30,
          perPage: 4,
        }
      },
      i18n: labels ? JSON.parse(labels) : {},
      live: false,
      slideFocus: false
    });

    this.slider.mount();
  }
}