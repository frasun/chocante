import QuantityInput from './quantity-input';

const QUANTITY = 'form.cart .quantity';

// Init quantity input.
const productQuantity = document.querySelector( QUANTITY );

if ( productQuantity ) {
	new QuantityInput( productQuantity );
}

// Get stock quantity.
const STOCK_STATUS = '#stockStatus';
const STOCK_ELEMENT_CLASS = 'stock';
const CART_FORM = 'form.cart';
const VARIATIONS_FORM = 'variations_form';
const API_ERROR = 'Error fetching stock data.';
const DATA_VARIATIONS = 'product_variations';
const VARIATIONS_STOCK_DATA = 'availability_html';

( async function ( $ ) {
	const addToCartForm = $( CART_FORM );
	const isSimple =
		addToCartForm && ! addToCartForm.hasClass( VARIATIONS_FORM );
	const fetchStockInfo = window.chocanteApi !== undefined;

	addToCartForm.on( 'found_variation', displayVariationStock );
	addToCartForm.on( 'reset_data', resetVariationStock );

	if ( ! fetchStockInfo ) {
		return;
	}

	if ( isSimple ) {
		const stockStatusElement = document.querySelector( STOCK_STATUS );

		try {
			const response = await fetch(
				new URL(
					`${ window.chocanteApi.url }${ window.chocanteApi.productId }/stock`
				)
			);
			const data = await response.json();

			if ( ! data.stock.length ) {
				return;
			}

			const stockInfo = document.createElement( 'p' );
			stockInfo.innerHTML = data.stock;

			stockStatusElement.appendChild( stockInfo.firstChild );
		} catch ( e ) {
			throw new Error( API_ERROR );
		}
	} else {
		try {
			const response = await fetch(
				new URL(
					`${ window.chocanteApi.url }${ window.chocanteApi.productId }/variations`
				)
			);
			const data = await response.json();

			addToCartForm.data( DATA_VARIATIONS, data.variations );
			addToCartForm.trigger( 'reload_product_variations' );
		} catch ( e ) {
			throw new Error( API_ERROR );
		}
	}
} )( jQuery );

function displayVariationStock( event, variationData ) {
	if (
		! variationData.hasOwnProperty( VARIATIONS_STOCK_DATA ) ||
		! variationData.availability_html.length
	) {
		return;
	}

	const element = document.createElement( 'div' );
	element.innerHTML = variationData.availability_html;
	const stockInfo = element.firstChild;
	if ( ! stockInfo ) {
		return;
	}

	const stockStatusElement = document.querySelector( STOCK_STATUS );
	const stockEl = stockStatusElement.querySelector(
		`.${ STOCK_ELEMENT_CLASS }`
	);
	if ( stockEl ) {
		stockEl.replaceWith( stockInfo );
	} else {
		stockStatusElement.appendChild( stockInfo );
	}
}

function resetVariationStock() {
	const stockStatusElement = document.querySelector( STOCK_STATUS );
	const stockEl = stockStatusElement.querySelector(
		`.${ STOCK_ELEMENT_CLASS }`
	);

	stockEl.className = STOCK_ELEMENT_CLASS;
	stockEl.innerHTML =
		// eslint-disable-next-line no-undef, camelcase
		wc_add_to_cart_variation_params.i18n_make_a_selection_text;
}
