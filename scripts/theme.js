import ModalService from './modal-service';
import Modal from './modal';
import ChocanteWooCommerce from './woocommerce';
import MenuScroll from './menu-scroll';
import { MOBILE_BREAKPOINT, MOBILE_BREAKPOINT_HEIGHT } from './constants';
// import Splide from '@splidejs/splide';
import Accordion from './details';
import PostSlider from './post-slider';
import ContentSlider from './content-slider';

const Splide = window.Splide || {};

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
		new MenuScroll( '#siteHeader' );

		// Dropdowns - mini-cart, currency/language switcher etc.
		this.setDropdownSize();
		window.addEventListener( 'resize', this.setDropdownSize );

		// Sliders.
		// window.Splide = Splide;
		document.addEventListener( 'DOMContentLoaded', () => {
			this.initSliders();
		} );

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

	initSliders() {
		// @todo: Chocante - Bricks Change class to '.splide'.
		const postSliders = document.querySelectorAll( '.post-slider' );

		Array.from( postSliders ).forEach( ( slider ) => {
			new Splide( slider, {
				type: 'fade',
				arrows: false,
				speed: 700,
				rewind: true,
				autoplay: true,
			} ).mount();
		} );

		const blogSliders = document.querySelectorAll( '.blog__slider' );

		Array.from( blogSliders ).forEach( ( slider ) => {
			new PostSlider( slider );
		} );

		// Sliders in the editor content
		const contentSliders = document.querySelectorAll(
			'.wp-block-group.splide'
		);

		Array.from( contentSliders ).forEach( ( slider ) => {
			new ContentSlider( slider );
		} );
	}

	// Sliders added by adding classes in the editor.
	setEditorSliders() {
		const contentSliders = document.querySelectorAll(
			'.wp-block-group.splide'
		);
		const screenWidth = window.innerWidth;

		if ( Array.from( contentSliders ).length ) {
			for ( const sliderElement of Array.from( contentSliders ) ) {
				const slider = new Splide( sliderElement, {
					type: 'slide',
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
					live: false,
					slideFocus: false,
				} );

				slider.mount();

				if ( screenWidth >= 1024 ) {
					slider.remove( '.splide--mobile' );
				}
			}
		}
	}
}

new Chocante();
new ChocanteWooCommerce();

jQuery( function ( $ ) {
	// Include header when scrolling to notices.
	// @see: /plugins/woocommerce/assets/js/frontend/woocommerce.js:87
	// const SCROLL_OFFSET = 15;
	const SCROLL_DURATION = 350;

	$.scroll_to_notices = function ( scrollElement ) {
		if ( scrollElement.length ) {
			// const adminBarHeight = $( '#wpadminbar' ).length
			// 	? $( '#wpadminbar' ).height()
			// 	: 0;

			$( 'html, body' ).animate(
				{
					// scrollTop: scrollElement.offset().top - $('.site-header').height() - SCROLL_OFFSET - adminBarHeight,
					scrollTop: 0,
				},
				SCROLL_DURATION
			);
		}
	};

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
