jQuery( function ( $ ) {
	const billingPostcode = $( '[name="billing_postcode"]' );
	const shippingPostcode = $( '[name="shipping_postcode"]' );

	if ( billingPostcode.length ) {
		validatePostCode( billingPostcode );
	}

	if ( shippingPostcode.length ) {
		validatePostCode( shippingPostcode );
	}

	$( 'form.checkout' ).on(
		'change',
		'[name$="_postcode"], [name$="_country"]',
		handleChange
	);

	// Fix default login form scrolling offset.
	$( document.body ).off( 'click', 'a.showlogin' );
	$( document.body ).on( 'click', 'a.showlogin', showLoginForm );

	function handleChange( e ) {
		validatePostCode( $( e.target ) );
	}

	function validatePostCode( field ) {
		const fieldType = field.attr( 'name' ).split( '_' )[ 0 ];
		const postcodeField = $( `[name="${ fieldType }_postcode"]` );
		const postcode = postcodeField ? postcodeField.val() : null;
		const countryField = $( `[name="${ fieldType }_country"]` );
		const country = countryField ? countryField.val() : null;

		if ( postcode && country ) {
			postcodeField
				.parents( '.form-row' )
				.find( '.checkout-inline-error-message' )
				.remove();

			$.post(
				window.chocante.ajaxurl,
				{
					_ajax_nonce: window.chocante.nonce,
					action: 'validate_postcode',
					postcode,
					country,
				},
				( data ) => {
					const { data: response } = data;

					if ( response ) {
						postcodeField
							.parents( '.form-row' )
							.append(
								`<p class="checkout-inline-error-message">${ response }</p>`
							);
					}
				}
			);
		}
	}

	function showLoginForm() {
		const $form = $( 'form.login, form.woocommerce-form--login' );
		if ( $form.is( ':visible' ) ) {
			// If already visible, hide it.
			$form.slideToggle( {
				duration: 400,
			} );
		} else {
			// If not visible, show it and then scroll
			$form.slideToggle( {
				duration: 400,
				complete() {
					if ( $form.is( ':visible' ) ) {
						const SCROLL_OFFSET = 20;
						const adminBarHeight = $( '#wpadminbar' ).length
							? $( '#wpadminbar' ).height()
							: 0;
						$( 'html, body' ).animate(
							{
								scrollTop:
									$form.offset().top -
									$( '.site-header' ).height() -
									SCROLL_OFFSET -
									adminBarHeight,
							},
							300
						);
					}
				},
			} );
		}
		return false;
	}
} );
