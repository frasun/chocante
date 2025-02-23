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
	}

	initMiniCart() {
		const miniCart = document.querySelector( '.mini-cart' );

		if ( ! miniCart ) {
			return;
		}

		new MiniCart( miniCart );
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
}
