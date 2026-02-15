import ModalService from './modal-service';
import Modal from './modal';
import ChocanteWooCommerce from './woocommerce';
import HeaderScroll from './menu-scroll';
import { MOBILE_BREAKPOINT, MOBILE_BREAKPOINT_HEIGHT } from './constants';
import Accordion from './details';
import PostSlider from './post-slider';
import ContentSlider from './content-slider';

class Chocante {
	constructor() {
		// Modals.
		new ModalService();

		// Mobile menu.
		new Modal(
			'#mobileMenu',
			'.site-header__toggle',
			MOBILE_BREAKPOINT,
			MOBILE_BREAKPOINT_HEIGHT
		);

		// Menu on scroll.
		new HeaderScroll( '#siteHeader' );

		// Dropdowns max-height - currency/language switcher etc.
		this.setDropdownSize();
		window.addEventListener( 'resize', this.setDropdownSize );

		// Sliders.
		this.initSliders();

		// <details> Accordion.
		document
			.querySelectorAll( 'details.wp-block-details' )
			.forEach( ( el ) => {
				new Accordion( el );
			} );
	}

	setDropdownSize() {
		const dropdowns = document.querySelectorAll(
			':where(.site-header, .mobile-menu) .wcml-cs-submenu, :where(.site-header, .mobile-menu) .wpml-ls-sub-menu, :where(.site-header, .mobile-menu) .wmc-sub-currency, .site-header__nav .sub-menu'
		);

		Array.from( dropdowns ).forEach( ( dropdown ) => {
			const maxHeight =
				window.innerHeight - dropdown.getBoundingClientRect().y - 20;

			dropdown.style.maxHeight = `${ maxHeight }px`;
		} );
	}

	async initSliders() {
		// Sliders in page header.
		this.initPostSliders();
		// Sliders with recent posts.
		this.initBlogSliders();
		// Sliders added in the editor.
		this.initContentSliders();
	}

	async importSlide() {
		if ( ! window.Splide ) {
			const splideJS = await import( '@splidejs/splide' );
			window.Splide = splideJS.Splide;
		}
	}

	// Individual slider initializers
	async initPostSliders() {
		const sliders = document.querySelectorAll( '.post-slider' );
		if ( sliders.length === 0 ) {
			return;
		}

		await this.importSlide();

		sliders.forEach( ( slider ) => {
			window.requestAnimationFrame( () => {
				new window.Splide( slider, {
					type: 'fade',
					arrows: false,
					speed: 700,
					rewind: true,
					autoplay: true,
				} ).mount();
			} );
		} );
	}

	async initBlogSliders() {
		const sliders = document.querySelectorAll( '.blog__slider' );

		sliders.forEach( ( slider ) => {
			new PostSlider( slider );
		} );
	}

	async initContentSliders() {
		const sliders = document.querySelectorAll( '.wp-block-group.splide' );
		if ( sliders.length === 0 ) {
			return;
		}

		sliders.forEach( ( slider ) => {
			new ContentSlider( slider );
		} );
	}
}

new Chocante();
new ChocanteWooCommerce();

jQuery( function ( $ ) {
	// Override custom scrolling to notice. It scrolls to the top of the page.
	$.scroll_to_notices = () => {};

	// Footer menu mobile.
	$( '.site-footer__nav .site-footer__nav-header' ).on(
		'click',
		( event ) => {
			if ( window.innerWidth >= MOBILE_BREAKPOINT ) {
				return;
			}

			$( event.target ).parent().find( '.menu' ).slideToggle();
			$( event.target ).parent().toggleClass( 'site-footer__nav--open' );
		}
	);

	$( window ).on( 'resize', () => {
		if ( window.innerWidth >= MOBILE_BREAKPOINT ) {
			$( '.site-footer__nav--open' ).removeClass(
				'site-footer__nav--open'
			);
			$( '.site-footer__nav .menu' ).removeAttr( 'style' );
		}
	} );
} );
