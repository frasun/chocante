export default class Modal {
	static MOBILE_BREAKPOINT = 1024;

	constructor( nav, toggle, breakpoint = 0, breakpointHeight = 0 ) {
		this.nav = nav;
		this.toggle = document.querySelector( toggle );
		this.breakpoint = breakpoint;
		this.breakpointHeight = breakpointHeight;
		this.open = false;

		if ( this.toggle ) {
			this.toggle.addEventListener( 'click', this.showMenu.bind( this ) );
		}

		if ( this.breakpoint > 0 ) {
			this.onResizeHandler = this.onResize.bind( this );
		}

		this.close = document.querySelector(
			`${ this.nav } button[data-close-modal]`
		);

		if ( this.close ) {
			this.close.addEventListener( 'click', this.hideMenu.bind( this ) );
		}
	}

	showMenu() {
		if ( ! this.checkBreakpoint() ) {
			return;
		}

		this.open = true;
		this.toggle.setAttribute( 'aria-expanded', this.open );
		document.dispatchEvent(
			new CustomEvent( 'showModal', { detail: { modalId: this.nav } } )
		);
		window.addEventListener( 'resize', this.onResizeHandler );
	}

	hideMenu( event, resize = false ) {
		if ( event ) {
			event.preventDefault();
		}

		this.open = false;
		this.toggle.setAttribute( 'aria-expanded', this.open );
		document.dispatchEvent(
			new CustomEvent( 'hideModal', { detail: { resize } } )
		);
		window.removeEventListener( 'resize', this.onResizeHandler );
	}

	checkBreakpoint() {
		return (
			( 0 === this.breakpoint
				? true
				: window.innerWidth < this.breakpoint ) ||
			( 0 === this.breakpointHeight
				? true
				: window.innerHeight < this.breakpointHeight )
		);
	}

	onResize() {
		if ( this.open && ! this.checkBreakpoint() ) {
			this.hideMenu( null, true );
		}
	}
}
