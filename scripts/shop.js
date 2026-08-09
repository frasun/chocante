import Modal from './modal';
import { MOBILE_BREAKPOINT } from './constants';
import { getGTM, pushGTM } from './gtm';
/* eslint-disable-next-line import/no-unresolved */
import { store, watch, getElement } from '@wordpress/interactivity';

const SITE_HEADER = '#siteHeader';

const { state: filtersState } = store( 'chocante/product-filters' );
const mobileFilters = new Modal(
	'#chocante-product-filters',
	null,
	MOBILE_BREAKPOINT
);

const { actions, callbacks } = store( 'chocante/shop', {
	callbacks: {
		shopInit() {
			if ( mobileFilters instanceof Modal ) {
				mobileFilters.toggle = getElement().ref;
			}
		},
		showMenu() {
			document.querySelector( SITE_HEADER )?.headerScroll?.showMenu();
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

				if ( 'onscrollend' in window ) {
					window.addEventListener( 'scrollend', callbacks.showMenu, {
						once: true,
					} );
				} else {
					let scrollTimeout;
					const onScroll = () => {
						clearTimeout( scrollTimeout );
						scrollTimeout = setTimeout( () => {
							window.removeEventListener( 'scroll', onScroll );
							callbacks.showMenu();
						}, 100 );
					};
					window.addEventListener( 'scroll', onScroll, {
						passive: true,
					} );
				}
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
