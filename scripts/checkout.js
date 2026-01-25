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
} );
