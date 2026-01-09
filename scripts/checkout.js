jQuery( function ( $ ) {
	validatePostCode( $( '[name="billing_postcode"]' ) );
	validatePostCode( $( '[name="shipping_postcode"]' ) );

	$( 'form.checkout' ).on(
		'change',
		'[name$="_postcode"], [name$="_country"]',
		handleChange
	);

	function handleChange( e ) {
		const field = $( e.target );
		const fieldName = field.attr( 'name' );

		if (
			( fieldName && fieldName.includes( 'billing_' ) ) ||
			fieldName.includes( 'shipping_' )
		) {
			validatePostCode( field );
		}
	}

	function validatePostCode( field ) {
		const fieldType = field.attr( 'name' ).split( '_' )[ 0 ];

		const postcode = $( `[name="${ fieldType }_postcode"]` ).val();
		const country = $( `[name="${ fieldType }_country"]` ).val();

		if ( postcode && country ) {
			$( `[name="${ fieldType }_postcode"]` )
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
						$( `[name="${ fieldType }_postcode"]` )
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
