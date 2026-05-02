// eslint-disable-next-line import/no-unresolved
import { env } from 'cloudflare:workers';

const CURRENCY_PARAM = 'currency';
const CURRENCY_PARAM_ALT = 'wmc-currency';

export default {
	async fetch( request ) {
		if ( ! isEligible( request ) ) {
			return fetch( request );
		}

		const defaultCookies = getDefaultCookies( request );
		const newRequest = prepareRequest( request, defaultCookies );
		const cacheKey = getCacheKey( newRequest );

		let response = await fetch( newRequest, {
			cf: {
				cacheEverything: true,
				cacheKey,
				cacheTtlByStatus: {
					401: 0,
					403: 0,
				},
			},
		} );

		response = setResponseHeaders( response, defaultCookies );

		return response;
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
		env.COOKIE_ADMIN && cookies.includes( `${ env.COOKIE_ADMIN }=` );
	if ( isAdmin ) {
		return false;
	}

	return true;
};

const getDefaultCookies = ( request ) => {
	const cookies = request.headers.get( 'cookie' ) || '';
	const country = request.cf?.country || env.DEFAULT_COUNTRY;
	const defaults = {};
	let currency;

	if (
		env.COOKIE_COUNTRY &&
		! cookies.includes( `${ env.COOKIE_COUNTRY }=` )
	) {
		defaults[ env.COOKIE_COUNTRY ] = country;
	}

	if (
		env.COOKIE_CURRENCY &&
		env.DEFAULT_CURRENCY &&
		! cookies.includes( `${ env.COOKIE_CURRENCY }=` )
	) {
		const paramCurrency = getParamCurrency( request );

		if ( paramCurrency ) {
			currency = paramCurrency;
		} else {
			const countryCurrency = env.COUNTRY_CURRENCY
				? JSON.parse( env.COUNTRY_CURRENCY )
				: null;
			currency =
				countryCurrency && countryCurrency[ country ]
					? countryCurrency[ country ]
					: env.DEFAULT_CURRENCY;
		}

		defaults[ env.COOKIE_CURRENCY ] = currency;
	}

	return Object.entries( defaults );
};

const prepareRequest = ( request, defaultCookies ) => {
	const url = new URL( request.url );

	url.searchParams.sort();

	if ( env.DROP_QS ) {
		dropQueryParams( url.searchParams, JSON.parse( env.DROP_QS ) );
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

const getCacheKey = ( request ) => `${ request.url }${ getVary( request ) }`;

const getVary = ( request ) => {
	const cookies = request.headers.get( 'cookie' ) || '';
	const requestVary = getVaryByPath( new URL( request.url ).pathname );

	const varyCookies = requestVary.filter(
		( key ) => typeof key === 'string' && key.length > 0
	);

	const hasVaryCookie = ( c ) =>
		varyCookies.some( ( key ) => c.startsWith( `${ key }=` ) );

	const cookieVary = cookies
		.split( ';' )
		.map( ( c ) => c.trim() )
		.filter( hasVaryCookie )
		.sort()
		.join( ';' );

	return cookieVary.length ? `::${ cookieVary }` : '';
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

	if ( currency && env.COUNTRY_CURRENCY ) {
		const currencies = [
			...new Set( Object.values( JSON.parse( env.COUNTRY_CURRENCY ) ) ),
		];
		if ( ! currencies.includes( currency ) ) {
			currency = undefined;
		}
	}

	return currency;
};

const getVaryByPath = ( path ) => {
	if (
		env.PATH_PRODUCT &&
		new RegExp( `/${ env.PATH_PRODUCT }/` ).test( path )
	) {
		return [
			env.COOKIE_CURRENCY,
			env.COOKIE_COUNTRY,
			env.COOKIE_VAT_EXEMPT,
			env.COOKIE_SHIPPING_COUNTRY,
		];
	}

	if ( env.PATH_SHOP && new RegExp( `/${ env.PATH_SHOP }/` ).test( path ) ) {
		return [ env.COOKIE_CURRENCY, env.COOKIE_VAT_EXEMPT ];
	}

	return [ env.COOKIE_CURRENCY ];
};
