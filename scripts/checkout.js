import PODService from './pod';

class Checkout {
	static SHIP_TO_DIFFERENT_ADDRESS = '#ship-to-different-address-checkbox';
	static SHIPPING = 'shipping';
	static BILLING = 'billing';
	static POD_SELECT = '.chocante-delivery-point select';
	static ACTION_SAVE_POINT = 'chocante_delivery_point_save';
	static ERROR_BAD_REQUEST = '[DELIVERY POINT]: Bad request';

	constructor( scriptData ) {
		this.scriptData = scriptData;
		this.pod = {};
		this.language = false;
		this.deliveryPoint = scriptData.deliveryPoint;
		this.shipToDifferentAddress = document.querySelector(
			Checkout.SHIP_TO_DIFFERENT_ADDRESS
		);
		this.locationChanged = false;

		jQuery(
			function ( $ ) {
				$( document.body ).on(
					'init_checkout',
					this.init.bind( this )
				);
				$( document.body ).on(
					'update_checkout',
					this.handleLocationChange.bind( this, $ )
				);
				$( document.body ).on(
					'updated_checkout',
					this.initPODSelect.bind( this, $ )
				);

				this.bindLocationChange( $ );
				$( Checkout.SHIP_TO_DIFFERENT_ADDRESS ).on(
					'change',
					this.bindLocationChange.bind( this, $ )
				);
			}.bind( this )
		);
	}

	get postCode() {
		return this.postCodeField ? this.postCodeField.value : null;
	}

	get country() {
		return this.countryField ? this.countryField.value : null;
	}

	get shippingAddress() {
		return this.shipToDifferentAddress &&
			this.shipToDifferentAddress.checked
			? Checkout.SHIPPING
			: Checkout.BILLING;
	}

	get postCodeField() {
		return document.querySelector(
			`[name="${ this.shippingAddress }_postcode"]`
		);
	}

	get countryField() {
		return document.querySelector(
			`[name="${ this.shippingAddress }_country"]`
		);
	}

	init() {
		const podSelectors = Array.from(
			document.querySelectorAll( Checkout.POD_SELECT )
		);

		if ( ! podSelectors.length ) {
			return;
		}

		podSelectors.forEach( ( podSelect ) => {
			const courier = podSelect.dataset.courierCode;
			this.initPODService( courier );

			if ( this.postCode && this.country ) {
				this.pod[ courier ].getPoints( this.postCode, this.country );
			}
		} );

		this.addSelectTranslation( podSelectors[ 0 ].dataset.language );
	}

	bindLocationChange( $ ) {
		$(
			`[name="${ this.shippingAddress }_postcode"], [name="${ this.shippingAddress }_country"]`
		).on( 'change', () => {
			this.locationChanged = true;
		} );

		$( `[name="${ this.shippingAddress }_postcode"]` ).on( 'change', () => {
			$( 'form.checkout' ).trigger( 'update' );
		} );
	}

	handleLocationChange( $ ) {
		$( Checkout.POD_SELECT ).each( async ( index, el ) => {
			const select = $( el );
			select.empty();
			select.append(
				new Option( select.data( 'placeholder-fetching' ) )
			);
			select.prop( 'disabled', true );
		} );
	}

	initPODService( courier ) {
		if ( courier && ! this.pod[ courier ] ) {
			this.pod[ courier ] = new PODService(
				this.scriptData.apiUrl,
				courier
			);
		}
	}

	addSelectTranslation( language ) {
		if ( ! this.language && language ) {
			const script = document.createElement( 'script' );
			script.src = `https://cdn.jsdelivr.net/npm/select2/dist/js/i18n/${ language }.js`;

			document.body.append( script );
			this.language = language;
		}
	}

	initPODSelect( $ ) {
		this.maybeResetDeliveryPoint();
		$( Checkout.POD_SELECT ).each( async ( index, el ) => {
			const select = $( el );
			if ( select.hasClass( 'select2-hidden-accessible' ) ) {
				select.off( 'select2:select' );
				select.select2( 'destroy' );
			}

			select.selectWoo( {
				placeholder: this.scriptData.i18n.placeholder_fetching,
			} );
			select.prop( 'disabled', true );

			if ( ! this.deliveryPoint ) {
				this.updateSelectedPointInfo( select );
			}

			const courier = select.data( 'courier-code' );

			if ( ! this.postCode || ! this.country || ! courier ) {
				return;
			}

			if ( ! this.pod[ courier ] ) {
				this.initPODService( courier );
			}

			const pointsData = await this.pod[ courier ].getPoints(
				this.postCode,
				this.country
			);

			this.handlePointsData( pointsData, select );

			select.on( 'select2:select', ( event ) => {
				const { id, text: info } = event.params.data;
				this.handlePointSelect( id, info, courier, select );
			} );
		} );

		this.locationChanged = false;
	}

	handlePointsData( pointsData, select ) {
		select.empty();

		if ( ! pointsData.length ) {
			select.selectWoo( {
				placeholder: this.scriptData.i18n.placeholder_empty,
			} );
			return;
		}

		select.append( new Option() );
		pointsData.forEach( ( { id, text } ) => {
			select.append( new Option( text, id ) );
		} );

		select.selectWoo( {
			placeholder: this.scriptData.i18n.placeholder,
		} );

		select.prop( 'disabled', false );

		if (
			this.deliveryPoint &&
			this.deliveryPoint.id &&
			this.deliveryPoint.info
		) {
			if (
				pointsData.find( ( { id } ) => id === this.deliveryPoint.id )
			) {
				select.val( this.deliveryPoint.id ).trigger( 'change' );
			}

			this.updateSelectedPointInfo( select, this.deliveryPoint.info );
		}
	}

	async handlePointSelect( id, info, courier, select ) {
		try {
			this.updateSelectedPointInfo( select, info );

			const body = new FormData();

			body.append( 'action', Checkout.ACTION_SAVE_POINT );
			body.append( '_ajax_nonce', this.scriptData.ajaxNonce );
			body.append( 'id', id );
			body.append( 'info', info );
			body.append( 'courier', courier );

			await fetch( this.scriptData.ajaxUrl, {
				method: 'POST',
				body,
			} );

			this.deliveryPoint = {
				id,
				info,
				courier,
				country: this.country,
			};
		} catch ( error ) {
			this.updateSelectedPointInfo( select );
			throw new Error( Checkout.BAD_REQUEST );
		}
	}

	updateSelectedPointInfo( select, info ) {
		if ( ! select ) {
			return;
		}

		const pointInfo = select
			.closest( '.chocante-delivery-point' )
			.find( '.chocante-delivery-point__info' );

		if ( info ) {
			pointInfo.html(
				`<small>${ this.scriptData.i18n.selected }</small><p>${ info }</p>`
			);
		} else {
			pointInfo.html(
				`<p class="chocante-delivery-point__empty">${ this.scriptData.i18n.selected_empty }</p>`
			);
		}
	}

	maybeResetDeliveryPoint() {
		if (
			this.deliveryPoint &&
			this.deliveryPoint.country &&
			this.deliveryPoint.country !== this.country
		) {
			const tempDeliveryPoint = this.deliveryPoint;
			this.deliveryPoint = null;

			try {
				const body = new FormData();

				body.append( 'action', Checkout.ACTION_SAVE_POINT );
				body.append( '_ajax_nonce', this.scriptData.ajaxNonce );

				fetch( this.scriptData.ajaxUrl, {
					method: 'POST',
					body,
				} );
			} catch {
				this.deliveryPoint = tempDeliveryPoint;
				throw new Error( Checkout.BAD_REQUEST );
			}
		}
	}
}

if ( window.chocante ) {
	new Checkout( window.chocante );
}

jQuery( function ( $ ) {
	// Fix default login form scrolling offset.
	$( document.body ).off( 'click', 'a.showlogin' );
	$( document.body ).on( 'click', 'a.showlogin', showLoginForm );

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
						window.location.hash = 'login';
					}
				},
			} );
		}
		return false;
	}
} );
