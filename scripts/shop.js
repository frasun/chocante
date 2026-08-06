import Modal from './modal';
import { MOBILE_BREAKPOINT } from './constants';
import { getGTM, pushGTM } from './gtm';
/* eslint-disable-next-line import/no-unresolved */
import { store, watch } from '@wordpress/interactivity';

const SITE_HEADER = '#siteHeader';

const { state } = store( 'chocante/product-filters' );
const mobileFilters = new Modal(
	'#chocante-product-filters',
	'#openMobileFilters',
	MOBILE_BREAKPOINT
);

const menuScroll = window.setTimeout( () => {
	document
		.querySelector( SITE_HEADER )
		?.removeEventListener( 'scrollend', onScrollEnd );
}, 500 );

const onScrollEnd = () => {
	clearTimeout( menuScroll );
	document.querySelector( SITE_HEADER )?.headerScroll?.showMenu();
};

watch( () => {
	if ( state.hasLoaded ) {
		mobileFilters?.hideModal();

		document.addEventListener( 'scrollend', onScrollEnd, { once: true } );

		if ( state.extra?.gtm ) {
			pushDataLayer( state.extra.gtm );
		}
	}
} );

pushDataLayer();

// GTM.
async function pushDataLayer( data ) {
	if ( ! window.chocanteGtm ) {
		return;
	}

	const gtmData = data || window.gtmItems;

	if ( ! gtmData ) {
		return;
	}

	const eventData = await getGTM(
		window.chocanteGtm.ajaxUrl,
		window.chocanteGtm.ajaxNonce,
		window.chocanteGtm.gtmAction,
		gtmData?.products,
		gtmData?.pageId,
		gtmData?.pageName
	);

	pushGTM( eventData );
}
