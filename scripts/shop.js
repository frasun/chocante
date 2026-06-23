import Modal from './modal';
import { MOBILE_BREAKPOINT } from './constants';
import { getGTM, pushGTM } from './gtm';

new Modal(
	'#chocante-product-filters',
	'#openMobileFilters',
	MOBILE_BREAKPOINT
);

// GTM.
document.addEventListener( 'DOMContentLoaded', pushDataLayer );

async function pushDataLayer() {
	if ( ! window.chocanteGtm ) {
		return;
	}

	const eventData = await getGTM(
		window.chocanteGtm.ajaxUrl,
		window.chocanteGtm.ajaxNonce,
		window.chocanteGtm.gtmAction,
		window.gtmItems?.products,
		window.gtmItems?.pageId,
		window.gtmItems?.pageName
	);

	pushGTM( eventData );
}
