import Splide from '@splidejs/splide';

export default class Slider {
  static TYPE_LOOP = 'loop';
  static TYPE_SLIDE = 'slide';
  static SLIDER_CLASS = '.splide';
  static SLIDE_CLASS = '.splide__slide:not(.splide__slide--clone)';

  constructor(containerClass) {
    this.wrapperClass = `.${containerClass.replace(' ', '.')}`;
    this.sliderClass = `${this.wrapperClass} ${Slider.SLIDER_CLASS}`;

    if (!document.querySelector(this.sliderClass)) {
      return;
    }

    this.initSlider();
    window.addEventListener('resize', this.initSlider.bind(this));
  }

  getSliderType() {
    const screenWidth = window.innerWidth;
    const slidesCount = document.querySelectorAll(`${this.sliderClass} ${Slider.SLIDE_CLASS}`).length;

    if (screenWidth >= 1180) {
      return slidesCount >= 8 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
    } else if (screenWidth >= 900) {
      return slidesCount >= 6 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
    } else if (screenWidth >= 600) {
      return slidesCount >= 4 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
    } else {
      return slidesCount > 1 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
    }
  }

  initSlider() {
    const sliderType = this.getSliderType();
    const wrapper = document.querySelector(this.sliderClass);
    const labels = 'aria' in wrapper.dataset ? wrapper.dataset.aria : undefined;

    if (this.slider) {
      if (this.slider.is(sliderType)) {
        return;
      } else {
        this.slider.destroy(true);
      }
    }

    this.slider = new Splide(this.sliderClass, {
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