import QuantityInput from './quantity-input';

const STOCK_ELEMENT = '.stock';
const CART_FORM = 'form.cart';
const ADD_TO_CART = '.ajax_add_to_cart';
const AVAILABILITY = 'availability_html';
const VARIATION_ID = 'variation_id';
const ADD_TO_CART_FRAGMENT = 'add-to-cart';
const ADD_TO_CART_NOTICE = 'added-to-cart-notice';

// Update stock information for selected variation.
function selectVariationStockData( event, variationData ) {
	if ( ! variationData.hasOwnProperty( AVAILABILITY ) ) {
		return;
	}

	const stock = document.querySelector( STOCK_ELEMENT );

	if ( ! stock ) {
		return;
	}

	const element = document.createElement( 'div' );
	element.innerHTML = variationData[ AVAILABILITY ];

	stock.replaceWith( element.firstChild );
}

// Update add to cart button data for selected variation.
function selectVariationToAdd( event, variationData ) {
	const addToCart = document.querySelector( ADD_TO_CART );

	if ( addToCart ) {
		addToCart.dataset.product_id = variationData[ VARIATION_ID ];
	}
}

// Reset stock information.
function resetVariationStockData() {
	const stock = document.querySelector( STOCK_ELEMENT );

	if ( ! stock ) {
		return;
	}

	stock.innerHTML =
		// eslint-disable-next-line no-undef,camelcase
		wc_add_to_cart_variation_params.i18n_make_a_selection_text;
}

// Reset add to cart button data.
function resetVariationToAdd() {
	const addToCart = document.querySelector( ADD_TO_CART );

	if ( addToCart ) {
		addToCart.dataset.product_id = 0;
	}
}

// Init quantity form.
const productQuantity = document.querySelector( 'form.cart .quantity' );
if ( productQuantity ) {
	new QuantityInput( productQuantity );
}

// Propagate quantity changes to add to cart button.
function setAddToCartButtonQuantity( event ) {
	const button = document.querySelector( ADD_TO_CART );

	if ( button ) {
		button.dataset.quantity = event.detail.quantity;
	}
}

// Display add to cart notice.
function displayAddToCartNotice( event, fragments ) {
	const notice = fragments[ ADD_TO_CART_FRAGMENT ];

	if ( notice ) {
		const form = document.querySelector( CART_FORM );
		const noticeEl = document.createElement( 'div' );
		// const adminBar = document.getElementById( 'wpadminbar' );
		// const siteHeader = document.getElementById( 'siteHeader' );

		noticeEl.className = ADD_TO_CART_NOTICE;
		// noticeEl.style.scrollMarginTop = `${
		// 	( adminBar ? adminBar.offsetHeight : 0 ) +
		// 	( siteHeader ? siteHeader.offsetHeight : 0 )
		// }px`;
		noticeEl.innerHTML = notice;

		if ( form ) {
			form.insertAdjacentElement( 'afterend', noticeEl );
			noticeEl.scrollIntoView( { block: 'center' } );
		}
	}
}

// Clear add to cart notice.
function clearAddToCartNotice() {
	const notices = document.querySelectorAll( `.${ ADD_TO_CART_NOTICE }` );

	Array.from( notices ).forEach( ( el ) => el.remove() );
}

( function ( $ ) {
	const form = $( CART_FORM );

	form.on( 'found_variation', selectVariationStockData );
	form.on( 'found_variation', selectVariationToAdd );
	form.on( 'reset_data', resetVariationStockData );
	form.on( 'reset_data', resetVariationToAdd );

	$( document.body ).on(
		'quantityInputChanged',
		'form.cart input.qty',
		setAddToCartButtonQuantity
	);

	$( document.body ).on( 'adding_to_cart', clearAddToCartNotice );
	$( document.body ).on( 'added_to_cart', displayAddToCartNotice );
} )( jQuery );
