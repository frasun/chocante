const SITE_HEADER = '#siteHeader';

export default function () {
	const showMenu = () =>
		document.querySelector( SITE_HEADER )?.headerScroll?.showMenu();

	if ( 'onscrollend' in window ) {
		window.addEventListener( 'scrollend', showMenu, { once: true } );
		return;
	}

	let scrollTimeout;
	const onScroll = () => {
		clearTimeout( scrollTimeout );
		scrollTimeout = setTimeout( () => {
			window.removeEventListener( 'scroll', onScroll );
			showMenu();
		}, 100 );
	};

	window.addEventListener( 'scroll', onScroll, { passive: true } );
}
