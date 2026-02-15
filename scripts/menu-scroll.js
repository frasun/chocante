export default class HeaderScroll {
	static SCROLLED = 'is-scrolled';
	static ERROR_MISSING_ELEMENT = 'Element missing in DOM';
	static HEADER_SCROLL_VAR = '--site-header--height';

	constructor( elem ) {
		this.elem = document.querySelector( elem );

		if ( ! this.elem ) {
			return;
		}

		this.scrollTop = window.scrollY;
		this.canTransition = true;

		window.addEventListener( 'scroll', this.manageScroll.bind( this ) );

		this.elem.addEventListener( 'mouseenter', () => {
			this.canTransition = false;
		} );

		this.elem.addEventListener( 'mouseleave', () => {
			this.canTransition = true;
		} );

		// Set global CSS variable so that scrolling to # can account for site header;
		this.setCSSProp();
		document.addEventListener(
			'click',
			this.handleInternalLinks.bind( this )
		);
	}

	get offset() {
		return this.elem.offsetHeight * 2;
	}

	manageScroll() {
		if ( ! this.canTransition ) {
			return;
		}

		if ( window.scrollY < this.scrollTop && window.scrollY > this.offset ) {
			this.hideMenu();
		} else {
			this.showMenu();
		}

		this.scrollTop = window.scrollY;
	}

	hideMenu() {
		window.requestAnimationFrame( () => {
			this.elem.classList.add( HeaderScroll.SCROLLED );
		} );
	}

	showMenu() {
		window.requestAnimationFrame( () => {
			this.elem.classList.remove( HeaderScroll.SCROLLED );
		} );
	}

	getHeaderHeight( keepScrollElement = true ) {
		if ( ! this.elem.children.length ) {
			return;
		}

		let height = 0;
		const children = Array.from( this.elem.children ).filter(
			( child ) =>
				keepScrollElement ||
				! child.hasAttribute( 'data-header-scroll' )
		);

		for ( const childEl of children ) {
			height += childEl.offsetHeight;
		}

		return height;
	}

	scrollToElem( event, link ) {
		event.preventDefault();

		const linkHref = link.getAttribute( 'href' );
		const linkTarget = document.getElementById(
			linkHref.replace( '#', '' )
		);

		if ( ! linkTarget ) {
			return 0;
		}

		const rect = linkTarget.getBoundingClientRect();
		const elementOffset = rect.top + window.scrollY;
		const includeScrolled = rect.top > 0 || elementOffset < this.offset;

		this.setCSSProp( includeScrolled, linkHref );
	}

	handleInternalLinks( e ) {
		const link = e.target.closest( 'a[href^="#"]' );

		if ( link ) {
			this.scrollToElem.call( this, e, link );
		}
	}

	setCSSProp( includeScrolled, url ) {
		const headerHeight = this.getHeaderHeight( includeScrolled );

		window.requestAnimationFrame( () => {
			document.documentElement.style.setProperty(
				HeaderScroll.HEADER_SCROLL_VAR,
				`${ headerHeight }px`
			);

			if ( url ) {
				window.location.hash = url;
			}

			document.addEventListener(
				'scrollend',
				() => {
					const currentUrl = new URL( window.location.href );
					currentUrl.hash = '';

					window.history.replaceState( null, '', currentUrl );
				},
				{ once: true }
			);
		} );
	}
}
