import Modal from './modal';
import { MOBILE_BREAKPOINT } from './constants';
import { getGTM, pushGTM } from './gtm';

// Product filters.
let mobileFilters;

function initMobileFilters() {
	mobileFilters = new Modal(
		'#chocante-product-filters',
		'#openMobileFilters',
		MOBILE_BREAKPOINT
	);
}

// GTM.
async function pushDataLayer( data ) {
	if ( ! window.chocanteGtm ) {
		return;
	}

	const gtmData = data.gtm || window.gtmItems;

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

if ( window.ChocanteProductFiltersRegisterCallback ) {
	window.ChocanteProductFiltersRegisterCallback( initMobileFilters, 'init' );
	window.ChocanteProductFiltersRegisterCallback( () => {
		mobileFilters.hideModal();
	}, 'navigation' );
	window.ChocanteProductFiltersRegisterCallback( pushDataLayer, 'update' );
} else {
	initMobileFilters();
	pushDataLayer();
}
