import QuantityInput from './quantity-input';

const STOCK_STATUS = '#stockStatus';
const STOCK_ELEMENT_CLASS = 'stock';
const CART_FORM = 'form.cart';
const VARIATIONS_FORM = 'variations_form';
const API_ERROR = 'Error fetching stock data.';
const DATA_VARIATIONS = 'product_variations';
const VARIATIONS_STOCK_DATA = 'availability_html';
const QUANTITY = 'form.cart .quantity';
const QUANTITY_INPUT = 'input[name="quantity"]';
const GET_STOCK_ACTION = 'get_product_stock';

const productQuantity = document.querySelector( QUANTITY );

if ( productQuantity ) {
	new QuantityInput( productQuantity );
}

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

	try {
		const fetchUrl = new URL( window.chocanteApi.url );
		fetchUrl.searchParams.append( 'action', GET_STOCK_ACTION );
		fetchUrl.searchParams.append( 'id', window.chocanteApi.productId );

		const response = await fetch( new URL( fetchUrl ) );
		const data = await response.json();

		if ( ! data.stock.length ) {
			return;
		}

		if ( isSimple ) {
			const product = data.stock[ 0 ];
			const stockStatusElement = document.querySelector( STOCK_STATUS );

			if ( stockStatusElement ) {
				const stockInfo = document.createElement( 'p' );

				stockInfo.innerHTML = product.availability_html;
				stockStatusElement.appendChild( stockInfo.firstChild );
			}

			setMaxQuantity( product.max_qty );
		} else {
			addToCartForm.data( DATA_VARIATIONS, data.stock );
			addToCartForm.trigger( 'reload_product_variations' );
		}
	} catch {
		throw new Error( API_ERROR );
	}
} )( jQuery );

function displayVariationStock( event, variationData ) {
	if (
		! variationData.hasOwnProperty( VARIATIONS_STOCK_DATA ) ||
		! variationData.availability_html.length
	) {
		return;
	}

	const stockStatusElement = document.querySelector( STOCK_STATUS );

	if ( stockStatusElement ) {
		const element = document.createElement( 'div' );
		element.innerHTML = variationData.availability_html;
		const stockInfo = element.firstChild;

		if ( stockInfo ) {
			const stockEl = stockStatusElement.querySelector(
				`.${ STOCK_ELEMENT_CLASS }`
			);

			if ( stockEl ) {
				stockEl.replaceWith( stockInfo );
			} else {
				stockStatusElement.appendChild( stockInfo );
			}
		}
	}

	setMaxQuantity( variationData.max_qty );
}

function resetVariationStock() {
	const stockStatusElement = document.querySelector( STOCK_STATUS );

	if ( stockStatusElement ) {
		const stockEl = stockStatusElement.querySelector(
			`.${ STOCK_ELEMENT_CLASS }`
		);

		if ( ! stockEl ) {
			return;
		}

		stockEl.className = STOCK_ELEMENT_CLASS;
		stockEl.innerHTML =
			// eslint-disable-next-line no-undef, camelcase
			wc_add_to_cart_variation_params.i18n_make_a_selection_text;
	}
}

function setMaxQuantity( quantity ) {
	const qtyInput = document.querySelector( QUANTITY_INPUT );

	if ( ! qtyInput ) {
		return;
	}

	if ( 'undefined' !== typeof quantity && '' !== quantity ) {
		qtyInput.max = quantity;
	} else {
		qtyInput.removeAttribute( 'max' );
	}

	qtyInput.dispatchEvent( new Event( 'quantityAttributesChanged' ) );
}
