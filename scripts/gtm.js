export async function pushGTM( data ) {
	if ( ! data || ! window.dataLayer ) {
		return;
	}

	window.dataLayer.push( { ecommerce: null } );
	window.dataLayer.push( data );
}

export async function getGTM( url, nonce, action, products, listId, listName ) {
	try {
		if ( ! url || ! nonce || ! action ) {
			return;
		}

		const fetchUrl = new URL( url );

		fetchUrl.searchParams.append( 'nonce', nonce );
		fetchUrl.searchParams.append( 'action', action );

		if ( products ) {
			fetchUrl.searchParams.append( 'products', products );
		}

		if ( listId ) {
			fetchUrl.searchParams.append( 'listId', listId );
		}

		if ( listName ) {
			fetchUrl.searchParams.append( 'listName', listName );
		}

		const response = await fetch( fetchUrl );
		const responseData = await response.json();

		if ( ! responseData.success ) {
			throw new Error();
		}

		const eventData = responseData.data;

		// Pass to GTM Server Side script.
		if ( window.varGtmServerSide ) {
			window.varGtmServerSide.currency = eventData.ecommerce.currency;
			window.varGtmServerSide.user_data = eventData.user_data;
		}

		return eventData;
	} catch {}
}
