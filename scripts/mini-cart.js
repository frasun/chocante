export default class MiniCart {
	static FRAGMENT_CART_COUNT = 'cart-count';
	static MINI_CART_COUNT = '.mini-cart__count';
	static MINI_CART_CONTENT = '.mini-cart__content';

	constructor( element ) {
		this.miniCartCount = element.querySelector( MiniCart.MINI_CART_COUNT );
		this.miniCartContent = element.querySelector(
			MiniCart.MINI_CART_CONTENT
		);

		if ( this.miniCartCount ) {
			this.updateContent();
			window
				.jQuery( document.body )
				.on(
					'wc_fragments_refreshed removed_from_cart added_to_cart',
					this.updateContent.bind( this )
				);
		}

		if ( this.miniCartContent ) {
			this.setContentHeight();
			window.addEventListener(
				'resize',
				this.setContentHeight.bind( this )
			);
			window.jQuery.blockUI.defaults.overlayCSS = {
				backgroundColor: '#fff',
				opacity: 0.7,
			};
		}
	}

	updateContent( event, fragments ) {
		if ( fragments && MiniCart.FRAGMENT_CART_COUNT in fragments ) {
			this.miniCartCount.innerHTML =
				fragments[ MiniCart.FRAGMENT_CART_COUNT ];
			return;
		}

		let count = this.miniCartCount.dataset.count;

		const storeFragments = JSON.parse(
			window.sessionStorage.getItem(
				window.wc_cart_fragments_params.fragment_name
			)
		);
		if (
			storeFragments &&
			MiniCart.FRAGMENT_CART_COUNT in storeFragments
		) {
			count = storeFragments[ MiniCart.FRAGMENT_CART_COUNT ];
		}

		this.miniCartCount.innerHTML = count;
	}

	setContentHeight() {
		const maxHeight =
			window.innerHeight -
			this.miniCartContent.getBoundingClientRect().y -
			20;
		this.miniCartContent.style.maxHeight = `${ maxHeight }px`;
	}
}
