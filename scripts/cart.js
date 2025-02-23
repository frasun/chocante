import QuantityInput from './quantity-input';

window.jQuery( function ( $ ) {
	// Quantity.
	initQuantityInputs();
	$( document.body ).on( 'updated_wc_div', initQuantityInputs );
	$( document.body ).on(
		'quantityInputChanged',
		`input.qty`,
		submitCartForm
	);

	// Coupon.
	$( document.body ).on(
		'change',
		'input[name="coupon_code"]',
		setCouponValue
	);
	$( document.body ).on( 'submit', '.checkout_coupon', submitCouponForm );
	$( document.body ).on( 'updated_wc_div', displayCouponError );
	$( document.body ).on( 'removed_coupon', removeCouponError );

	// Remove default option from shipping calculator.
	$( '#calc_shipping_country option[value="default"]' ).remove();

	// Empty cart.
	$( document.body ).on( 'wc_cart_emptied', handleEmptyCart );

	function initQuantityInputs() {
		$( '.woocommerce-cart-form .quantity' ).each( ( index, quantityEl ) => {
			new QuantityInput( quantityEl );
		} );
	}

	function submitCartForm() {
		$( ':input[name="update_cart"]' )
			.prop( 'disabled', false )
			.trigger( 'click' );
	}

	function setCouponValue( event ) {
		$( 'input[name="coupon_code"]' ).val( event.target.value );
	}

	function submitCouponForm( event ) {
		event.preventDefault();
		$( ':input[name="apply_coupon"]' ).trigger( 'click' );
	}

	function displayCouponError() {
		removeCouponError();

		const couponError = $( '.coupon-error-notice' );

		if ( couponError ) {
			$( '.woocommerce-notices-wrapper' ).append( couponError );
		}
	}

	function removeCouponError() {
		$( '.woocommerce-notices-wrapper .coupon-error-notice' ).remove();
	}

	function handleEmptyCart() {
		$( document.body ).addClass( 'cart-empty' );
	}
} );
