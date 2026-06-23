import QuantityInput from './quantity-input';
import ProductGallery from './product-gallery';
import { getGTM, pushGTM } from './gtm';

const STOCK_ELEMENT = '.stock';
const ATC_FORM = 'form.cart';
const ATC_FORM_VARIABLE = 'form.cart.variations_form';
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

// Reset add to cart button data.
function resetVariationToAdd() {
	const addToCart = document.querySelector( ADD_TO_CART );

	if ( addToCart ) {
		addToCart.dataset.product_id = 0;
	}
}

// Select variation based on url param.
function selectVariationFromUrl() {
	const currentUrl = new URL( window.location.href );

	currentUrl.searchParams.forEach( ( value, key ) => {
		const variationSelect = document.querySelector(
			`select[name="${ key }"]`
		);

		if (
			variationSelect &&
			variationSelect.querySelector( `[value="${ value }"]` )
		) {
			variationSelect.value = value;
		}
	} );
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
		const form = document.querySelector( ATC_FORM );
		const noticeEl = document.createElement( 'div' );

		noticeEl.className = ADD_TO_CART_NOTICE;
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

// GTM.
async function pushDataLayer( variationData ) {
	if ( ! window.chocanteGtm ) {
		return;
	}

	if ( ! window.gtmData ) {
		window.gtmData = await getGTM(
			window.chocanteGtm.ajaxUrl,
			window.chocanteGtm.ajaxNonce,
			window.chocanteGtm.gtmAction,
			window.chocanteGtm.gtmId
		);
	}

	let eventData = window.gtmData;

	if ( variationData ) {
		const variant = {
			item_sku: variationData.sku ?? undefined,
			item_variant: variationData[ VARIATION_ID ].toString(),
			price: parseFloat( variationData.display_price ).toString(),
		};

		eventData = {
			...eventData,
			ecommerce: {
				...eventData?.ecommerce,
				items: [ { ...eventData?.ecommerce?.items[ 0 ], ...variant } ],
				value: variant ? variant.price : eventData.ecommerce.value,
			},
		};
	}

	pushGTM( eventData );
}

// Update tags for add_to_cart event
function updateGtmATC( variationData ) {
	const itemSku = document.querySelector(
		'input[type="hidden"][name="gtm_item_sku"]'
	);

	if ( itemSku ) {
		itemSku.value = variationData?.sku;
	}

	const itemPrice = document.querySelector(
		'input[type="hidden"][name="gtm_price"]'
	);

	if ( itemPrice ) {
		itemPrice.value = parseFloat( variationData.display_price ).toString();
	}

	const itemVariant = document.querySelector(
		'input[type="hidden"][name="gtm_item_variant"]'
	);

	if ( itemVariant ) {
		itemVariant.value = variationData[ VARIATION_ID ].toString();
	}
}

( async function ( $ ) {
	const form = $( ATC_FORM_VARIABLE );

	$( document.body ).on(
		'quantityInputChanged',
		'form.cart input.qty',
		setAddToCartButtonQuantity
	);

	$( document.body ).on( 'adding_to_cart', clearAddToCartNotice );
	$( document.body ).on( 'added_to_cart', displayAddToCartNotice );

	// Variable product.
	if ( form.length ) {
		selectVariationFromUrl();
		form.on( 'found_variation', selectVariationStockData );
		form.on( 'found_variation', selectVariationToAdd );
		form.on( 'reset_data', resetVariationToAdd );
	}

	// GTM.
	if ( form.length && form.data( 'product_variations' ).length ) {
		form.on( 'found_variation', ( event, variationData ) => {
			pushDataLayer( variationData );
			updateGtmATC( variationData );
		} );
	} else {
		pushDataLayer();
	}
} )( jQuery );

// Product gallery.
new ProductGallery();
