export default class Slider {
	constructor( element ) {
		this.sliderElement = element;
		this.initSlider();
	}

	async initSlider() {
		if ( ! window.Splide ) {
			const splideJS = await import( '@splidejs/splide' );
			window.Splide = splideJS.Splide;
		}

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

		window.requestAnimationFrame( () => {
			this.slider = new window.Splide(
				this.sliderElement,
				sliderOptions
			).mount();
		} );
	}
}
