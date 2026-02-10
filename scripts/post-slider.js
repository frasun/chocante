export default class Slider {
	static TYPE_LOOP = 'loop';
	static TYPE_SLIDE = 'slide';
	static SLIDER_CLASS = '.splide';
	static SLIDE_CLASS = '.splide__slide:not(.splide__slide--clone)';

	constructor( sliderEl ) {
		this.sliderEl = sliderEl;

		this.initSlider();
		window.addEventListener( 'resize', this.initSlider.bind( this ) );
	}

	getSliderType() {
		const screenWidth = window.innerWidth;
		const slidesCount = this.sliderEl.querySelectorAll(
			Slider.SLIDE_CLASS
		).length;

		if ( screenWidth >= 1180 ) {
			return slidesCount >= 8 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
		} else if ( screenWidth >= 900 ) {
			return slidesCount >= 6 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
		} else if ( screenWidth >= 600 ) {
			return slidesCount >= 4 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
		}
		return slidesCount > 1 ? Slider.TYPE_LOOP : Slider.TYPE_SLIDE;
	}

	async initSlider() {
		if ( ! window.Splide ) {
			const splideJS = await import( '@splidejs/splide' );
			window.Splide = splideJS.Splide;
		}

		const sliderType = this.getSliderType();

		if ( this.slider ) {
			if ( this.slider.is( sliderType ) ) {
				return;
			}
			this.slider.destroy( true );
		}

		window.requestAnimationFrame( () => {
			this.slider = new window.Splide( this.sliderEl, {
				type: sliderType,
				perPage: 1,
				gap: 20,
				drag: 'free',
				snap: true,
				speed: 350,
				mediaQuery: 'min',
				breakpoints: {
					600: {
						perPage: 2,
					},
					900: {
						perPage: 3,
					},
					1180: {
						gap: 30,
						perPage: 4,
					},
				},
				// i18n: labels ? JSON.parse(labels) : {},
				live: false,
				slideFocus: false,
			} );

			this.slider.mount();
		} );
	}
}
