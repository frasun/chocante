import MiniCart from './mini-cart';
import Modal from './modal';

export default class ChocanteWooCommerce {
	constructor() {
		this.initMiniCart();
		this.initProductSearch();

		// Force shipping cost recalculation.
		window
			.jQuery( 'form.checkout' )
			.on(
				'change',
				'input[name="billing_postcode"], input[name="shipping_postcode"]',
				() => {
					window.jQuery( 'form.checkout' ).trigger( 'update' );
				}
			);

		// Curcy currency switcher - delete cookies added in .htaccess on currency switch using JS.
		this.initCurrencySwitcher();

		document.addEventListener(
			'DOMContentLoaded',
			this.modifyCurrencySwitcher
		);
	}

	initMiniCart() {
		const miniCart = document.querySelector( '.mini-cart' );

		if ( ! miniCart ) {
			return;
		}

		window.mc = new MiniCart( miniCart );

		// Handle language change using switcher links.
		const switcherLinks = document.querySelectorAll(
			'.switcher--language a'
		);

		Array.from( switcherLinks ).forEach( ( link ) =>
			link.addEventListener( 'click', window.mc.clearMiniCartFragments )
		);
	}

	initProductSearch() {
		new Modal( '.search-products__form', '.search-products__display' );
		document.addEventListener( 'showModal', this.onProductSearchShow );
	}

	onProductSearchShow( event ) {
		const modalId = event.detail.modalId;

		if ( modalId === '.search-products__form' ) {
			const searchInput = document.querySelector(
				'.search-products__form input[type="search"]'
			);

			if ( searchInput ) {
				searchInput.value = '';
				searchInput.focus();
			}
		}
	}

	initCurrencySwitcher() {
		const url = new URL( window.location.href );

		if ( url.searchParams.has( 'currency' ) ) {
			if ( window.mc ) {
				window.mc.clearMiniCartFragments();
			}

			url.searchParams.delete( 'currency' );
			window.history.replaceState( {}, null, url.href );
		}
	}

	modifyCurrencySwitcher() {
		if ( ! window.woocommerce_multi_currency_switcher ) {
			return;
		}

		const setCookie = window.woocommerce_multi_currency_switcher.setCookie;

		window.woocommerce_multi_currency_switcher.setCookie = async (
			cname,
			cvalue,
			expire
		) => {
			document.cookie = `wmc_current_currency=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=${ window.location.hostname }`;
			document.cookie = `wmc_current_currency_old=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=${ window.location.hostname }`;
			setCookie( cname, cvalue, expire );
		};
	}
}
