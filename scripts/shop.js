import Modal from './modal';
import { MOBILE_BREAKPOINT } from './constants';
import { getGTM, pushGTM } from './gtm';
/* eslint-disable-next-line import/no-unresolved */
import { store, watch, getElement } from '@wordpress/interactivity';
import delayedShowMenu from './delayed-show-menu';

const { state: filtersState } = store( 'chocante/product-filters' );
const mobileFilters = new Modal(
	'#chocante-product-filters',
	null,
	MOBILE_BREAKPOINT
);

const { actions } = store( 'chocante/shop', {
	callbacks: {
		shopInit() {
			if ( mobileFilters instanceof Modal ) {
				mobileFilters.toggle = getElement().ref;
			}
		},
	},
	actions: {
		openMobileFiltes() {
			if ( mobileFilters instanceof Modal ) {
				mobileFilters.showModal();
			}
		},
		closeMobileFilters() {
			if ( mobileFilters instanceof Modal ) {
				mobileFilters.hideModal();
				delayedShowMenu();
			}
		},
		*pushDataLayer( data ) {
			if ( ! window.chocanteGtm ) {
				return;
			}

			const gtmData = data || window.gtmItems;

			if ( ! gtmData ) {
				return;
			}

			const eventData = yield getGTM(
				window.chocanteGtm.ajaxUrl,
				window.chocanteGtm.ajaxNonce,
				window.chocanteGtm.gtmAction,
				gtmData?.products,
				gtmData?.pageId,
				gtmData?.pageName
			);

			pushGTM( eventData );
		},
	},
} );

actions.pushDataLayer();

watch( () => {
	if ( filtersState.hasLoaded ) {
		actions.closeMobileFilters();

		if ( filtersState.extra?.gtm ) {
			actions.pushDataLayer( filtersState.extra.gtm );
		}
	}
} );
