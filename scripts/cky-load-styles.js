let checktimeout = 0;

window.addEventListener( 'load', function () {
	waitForElement( '.cky-consent-container', function () {
		const styleNode = document.querySelector( '#cky-style' );
		const clonedStyleNode = styleNode.cloneNode( true );
		let lastUrl = location.href;

		new MutationObserver( () => {
			const url = location.href;
			if ( url !== lastUrl ) {
				lastUrl = url;
				onUrlChange();
			}
		} ).observe( document, { subtree: true, childList: true } );

		function onUrlChange() {
			document.head.appendChild( clonedStyleNode );
		}
	} );
} );

function waitForElement( selector, callback ) {
	const element = document.querySelector( selector );
	if ( element ) {
		return callback();
	}

	checktimeout++;
	if ( checktimeout < 120 ) {
		setTimeout( function () {
			waitForElement( selector, callback );
		}, 500 );
	}
}
