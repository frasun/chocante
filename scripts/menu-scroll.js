export default class MenuScroll {
	static SCROLLED = 'is-scrolled';
	static ERROR_MISSING_ELEMENT = 'Element missing in DOM';

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

		const footnotes = document.querySelectorAll(
			'a[href^="#"][href$="link"], a[href^="#"][id$="link"]'
		);

		for ( const link of footnotes ) {
			link.addEventListener( 'click', this.handleFootnotes.bind( this ) );
		}
	}

	manageScroll() {
		window.requestAnimationFrame( () => {
			if ( ! this.canTransition ) {
				return;
			}

			if (
				window.scrollY < this.scrollTop &&
				window.scrollY > this.elem.offsetHeight * 2
			) {
				this.hideMenu();
			} else {
				this.showMenu();
			}

			this.scrollTop = window.scrollY;
		} );
	}

	hideMenu() {
		this.elem.classList.add( MenuScroll.SCROLLED );
	}

	showMenu() {
		this.elem.classList.remove( MenuScroll.SCROLLED );
	}

	handleFootnotes() {
		this.hideMenu();
		this.canTransition = false;

		// @todo: Find better solution.
		window.setTimeout( () => {
			this.canTransition = true;
		}, 2000 );
	}
}
