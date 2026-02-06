import QuantityInput from './quantity-input';

const STOCK_ELEMENT = '.stock';
const AVAILABILITY = 'availability_html';
const CART_FORM = '.variations_form';

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

function resetVariationStockData() {
	const stock = document.querySelector( STOCK_ELEMENT );

	if ( ! stock ) {
		return;
	}

	stock.innerHTML =
		// eslint-disable-next-line no-undef,camelcase
		wc_add_to_cart_variation_params.i18n_make_a_selection_text;
}

// Quantity form.
const productQuantity = document.querySelector( 'form.cart .quantity' );
if ( productQuantity ) {
	new QuantityInput( productQuantity );
}

// Variation stock info.
( function ( $ ) {
	const form = $( CART_FORM );

	form.on( 'found_variation', selectVariationStockData );
	form.on( 'reset_data', resetVariationStockData );
} )( jQuery );
