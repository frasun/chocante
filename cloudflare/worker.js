// eslint-disable-next-line import/no-unresolved
import { env as vars } from 'cloudflare:workers';

const CURRENCY_PARAM = 'currency';
const CURRENCY_PARAM_ALT = 'wmc-currency';
const CACHE_TAG_HEADER = 'X-Cache-Tag';
const VARY_VAT_EXEMPT = 'vat-exempt';
const EU_COUNTRIES = [
	'AT',
	'BE',
	'BG',
	'CY',
	'CZ',
	'DE',
	'DK',
	'EE',
	'ES',
	'FI',
	'FR',
	'GR',
	'HR',
	'HU',
	'IE',
	'IT',
	'LT',
	'LU',
	'LV',
	'MT',
	'NL',
	'PL',
	'PT',
	'RO',
	'SE',
	'SI',
	'SK',
	'MC',
];

export default {
	async fetch( request, env, ctx ) {
		if ( ! isEligible( request ) ) {
			return fetch( request );
		}

		const defaultCookies = getDefaultCookies( request );
		const newRequest = prepareRequest( request, defaultCookies );
		const cacheKey = getCacheKey( newRequest );
		const cacheApi = caches.default;

		let response = await cacheApi.match( cacheKey );

		if ( ! response ) {
			response = await fetch( newRequest );
			ctx.waitUntil(
				cacheApi.put( cacheKey, prepareResponse( response ) )
			);
		}

		return setResponseHeaders( response, defaultCookies );
	},
};

const isEligible = ( request ) => {
	const isGet = request.method === 'GET';
	if ( ! isGet ) {
		return false;
	}

	const accept = request.headers.get( 'accept' ) || '';
	const isPage = accept.includes( 'text/html' );
	if ( ! isPage ) {
		return false;
	}

	const cookies = request.headers.get( 'cookie' ) || '';
	const isAdmin =
		vars.COOKIE_ADMIN && cookies.includes( `${ vars.COOKIE_ADMIN }=` );
	if ( isAdmin ) {
		return false;
	}

	return true;
};

const getDefaultCookies = ( request ) => {
	const cookies = request.headers.get( 'cookie' ) || '';
	const country = request.cf?.country || vars.DEFAULT_COUNTRY;
	const defaults = {};
	let currency;

	if (
		vars.COOKIE_COUNTRY &&
		! cookies.includes( `${ vars.COOKIE_COUNTRY }=` )
	) {
		defaults[ vars.COOKIE_COUNTRY ] = country;
	}

	if (
		vars.COOKIE_CURRENCY &&
		vars.DEFAULT_CURRENCY &&
		! cookies.includes( `${ vars.COOKIE_CURRENCY }=` )
	) {
		const paramCurrency = getParamCurrency( request );

		if ( paramCurrency ) {
			currency = paramCurrency;
		} else {
			const countryCurrency = vars.COUNTRY_CURRENCY
				? JSON.parse( vars.COUNTRY_CURRENCY )
				: null;
			currency =
				countryCurrency && countryCurrency[ country ]
					? countryCurrency[ country ]
					: vars.DEFAULT_CURRENCY;
		}

		defaults[ vars.COOKIE_CURRENCY ] = currency;
	}

	return Object.entries( defaults );
};

const prepareRequest = ( request, defaultCookies ) => {
	const url = new URL( request.url );

	url.searchParams.sort();

	if ( vars.DROP_QS ) {
		dropQueryParams( url.searchParams, JSON.parse( vars.DROP_QS ) );
	}

	const headers = new Headers( request.headers );

	if ( defaultCookies.length ) {
		let cookies = headers.get( 'cookie' ) || '';

		for ( const [ name, value ] of defaultCookies ) {
			cookies += ( cookies ? '; ' : '' ) + `${ name }=${ value }`;
		}

		headers.set( 'cookie', cookies );
	}

	return new Request( url, {
		headers,
		cache: request.cache,
		method: request.method,
		redirect: request.redirect,
		signal: request.signal,
	} );
};

const setResponseHeaders = ( response, cookies ) => {
	const responseWithHeaders = new Response( response.body, response );

	responseWithHeaders.headers.delete( CACHE_TAG_HEADER );
	responseWithHeaders.headers.set( 'Vary', 'Cookie' );

	if ( cookies.length ) {
		for ( const [ name, value ] of cookies ) {
			responseWithHeaders.headers.append(
				'Set-Cookie',
				`${ name }=${ value }; Path=/; Secure;`
			);
		}
	}

	return responseWithHeaders;
};

const getCacheKey = ( request ) => {
	const url = new URL( request.url );
	url.searchParams.append( 'vary', getVary( request ) );
	return url.toString();
};

const getVary = ( request ) => {
	const cookies = request.headers.get( 'cookie' ) || '';
	const [ varyCookies, vat ] = getVaryByPath(
		new URL( request.url ).pathname
	);

	const parseCookie = ( name ) =>
		cookies
			.split( ';' )
			.map( ( c ) => c.trim() )
			.find( ( c ) => c.startsWith( `${ name }=` ) )
			?.split( '=' )[ 1 ] || false;

	const parts = varyCookies.map( parseCookie ).filter( Boolean );

	if ( vat ) {
		const country = parseCookie( vars.COOKIE_COUNTRY );
		const isEU = EU_COUNTRIES.includes( country );

		if ( isEU ) {
			const vatExempt = parseCookie( vars.COOKIE_VAT_EXEMPT );
			if ( vatExempt ) {
				parts.push( VARY_VAT_EXEMPT );
			}
		} else {
			parts.push( VARY_VAT_EXEMPT );
		}
	}

	return parts.sort().join( '-' );
};

const dropQueryParams = ( params, blocklist ) => {
	for ( const [ key, value ] of [ ...params ] ) {
		const blocked = blocklist.some( ( rule ) => {
			if ( rule.includes( '=' ) ) {
				const [ ruleKey, ruleVal ] = rule.split( '=' );
				return key === ruleKey && value === ruleVal;
			}
			return rule.endsWith( '*' )
				? key.startsWith( rule.slice( 0, -1 ) )
				: key === rule;
		} );

		if ( blocked ) {
			params.delete( key );
		}
	}
};

const getParamCurrency = ( request ) => {
	const url = new URL( request.url );
	let currency;

	if ( url.searchParams.has( CURRENCY_PARAM ) ) {
		currency = url.searchParams.get( CURRENCY_PARAM );
	} else if ( url.searchParams.has( CURRENCY_PARAM_ALT ) ) {
		currency = url.searchParams.get( CURRENCY_PARAM_ALT );
	}

	if ( currency && vars.COUNTRY_CURRENCY ) {
		const currencies = [
			...new Set( Object.values( JSON.parse( vars.COUNTRY_CURRENCY ) ) ),
		];
		if ( ! currencies.includes( currency ) ) {
			currency = undefined;
		}
	}

	return currency;
};

const getVaryByPath = ( path ) => {
	const cookies = [ vars.COOKIE_CURRENCY ];
	let vat = false;

	if (
		vars.PATH_PRODUCT &&
		new RegExp( `/${ vars.PATH_PRODUCT }/` ).test( path )
	) {
		cookies.push( vars.COOKIE_COUNTRY );
		cookies.push( vars.COOKIE_SHIPPING_COUNTRY );
		vat = true;
	}

	if (
		vars.PATH_SHOP &&
		new RegExp( `/${ vars.PATH_SHOP }/` ).test( path )
	) {
		vat = true;
	}

	return [ cookies, vat ];
};

const prepareResponse = ( response ) => {
	const cacheTags = response.headers.get( CACHE_TAG_HEADER );
	const responseToCache = new Response( response.clone().body, response );
	responseToCache.headers.delete( 'Set-Cookie' );
	responseToCache.headers.set( 'Cache-Tag', cacheTags );

	return responseToCache;
};
