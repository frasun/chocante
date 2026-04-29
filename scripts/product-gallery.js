import PhotoSwipeLightbox from 'photoswipe/lightbox';

const PRODUCT_GALLERY = '.woocommerce-product-gallery';
const PRODUCT_GALLERY_SLIDER = '.woocommerce-product-gallery__wrapper.splide';
const PRODUCT_GALLERY_THUMBNAIL = '.woocommerce-product-gallery__thumbnails';

export default class ProductGallery {
	constructor() {
		this.slides = [];

		window.addEventListener(
			'DOMContentLoaded',
			this.initProductGallery.bind( this )
		);
		window.addEventListener(
			'DOMContentLoaded',
			this.initPhotoswipe.bind( this )
		);
	}

	// Product gallery.
	async initProductGallery() {
		const productGalleryEl = document.querySelector(
			PRODUCT_GALLERY_SLIDER
		);

		if ( ! productGalleryEl ) {
			return;
		}

		if ( ! window.Splide ) {
			const splideJS = await import( '@splidejs/splide' );
			window.Splide = splideJS.Splide;
		}

		const productGalleryOptions = {
			pagination: false,
			arrows: false,
			speed: 500,
		};

		this.productGallery = new window.Splide(
			PRODUCT_GALLERY_SLIDER,
			productGalleryOptions
		);

		window.requestAnimationFrame( () => {
			this.productGallery.mount();
			this.initGalleryThumbnails();
		} );

		this.productGallery.on(
			'ready resize',
			this.resetSliderHeight.bind( this )
		);

		this.productGallery.on( 'move', this.setSliderHeight.bind( this ) );
	}

	// Product gallery thumbnails.
	initGalleryThumbnails() {
		const wrapper =
			this.productGallery.Components.Elements.root.parentElement;
		const thumbnailsWrapper = wrapper.querySelector(
			PRODUCT_GALLERY_THUMBNAIL
		);

		if ( ! thumbnailsWrapper ) {
			return;
		}

		this.thumbnails = Array.from(
			thumbnailsWrapper.querySelectorAll( 'li' )
		);

		this.thumbnails.forEach( ( thumbnail, index ) => {
			if ( 0 === index ) {
				thumbnail.setAttribute( 'aria-current', true );
			}

			thumbnail.addEventListener( 'click', () => {
				this.productGallery.go( index );
			} );
		} );

		thumbnailsWrapper.classList.add( 'initialised' );

		this.productGallery.on(
			'moved',
			this.setCurrentThumbnail.bind( this )
		);
	}

	setCurrentThumbnail( newIndex, prevIndex ) {
		this.thumbnails.forEach( ( thumbnail, index ) => {
			if ( prevIndex === index ) {
				thumbnail.removeAttribute( 'aria-current' );
				return;
			}

			if ( newIndex === index ) {
				thumbnail.setAttribute( 'aria-current', true );
			}
		} );
	}

	resetSliderHeight() {
		this.slides = [];

		this.productGallery.Components.Elements.slides.forEach( ( slide ) => {
			this.slides.push( slide.offsetHeight );
		} );

		this.setSliderHeight(
			this.productGallery.Components.Controller.getIndex()
		);
	}

	setSliderHeight( activeIndex ) {
		this.productGallery.Components.Elements.track.style.height = `${ this.slides[ activeIndex ] }px`;
	}

	// Product gallery - Photoswipe.
	initPhotoswipe() {
		const lightbox = new PhotoSwipeLightbox( {
			gallery: PRODUCT_GALLERY,
			children: 'a',
			showHideAnimationType: 'fade',
			pswpModule: () => import( 'photoswipe' ),
			bgOpacity: 0.9,
		} );
		lightbox.init();
	}
}
