// import Splide from '@splidejs/splide';
const Splide = window.Splide || {};

export default class Slider {
	constructor( element ) {
		this.sliderElement = element;
		this.initSlider();
	}

	initSlider() {
		const sliderOptions = {
			type: 'slide',
			perPage: 1,
			gap: 20,
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
					destroy: true,
				},
			},
			live: false,
			slideFocus: false,
		};

		this.slider = new Splide( this.sliderElement, sliderOptions ).mount();
	}
}
