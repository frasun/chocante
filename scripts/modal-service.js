export default class ModalService {
	static CLASS_BACKDROP = 'backdrop';
	static CLASS_TRANSITION = 'transition';
	static CLASS_ACTIVE = 'active';

	constructor() {
		this.modal = null;
		this.backdrop = null;

		document.addEventListener( 'showModal', this.onModalShow.bind( this ) );
		document.addEventListener( 'hideModal', this.onModalHide.bind( this ) );
	}

	onModalShow( event ) {
		const modalId = event.detail.modalId;

		if ( modalId ) {
			this.showModal( modalId );
		}
	}

	onModalHide( event ) {
		const resize =
			event.detail && event.detail.resize ? event.detail.resize : false;

		this.hideModal( ! resize );
	}

	showModal( modalId ) {
		this.modal = document.querySelector( modalId );

		if ( ! this.modal ) {
			return;
		}

		this.setModalTransition();
		this.showBackdrop();
	}

	hideModal( setTransition = true ) {
		this.setModalTransition( false, setTransition );
		this.hideBackdrop();
	}

	showBackdrop() {
		if ( this.backdrop ) {
			return;
		}

		const elem = document.createElement( 'div' );
		elem.className = ModalService.CLASS_BACKDROP;

		document.body.append( elem );

		this.backdrop = document.querySelector(
			`.${ ModalService.CLASS_BACKDROP }`
		);

		window.requestAnimationFrame( () => {
			this.backdrop.classList.add( ModalService.CLASS_ACTIVE );
		} );
	}

	hideBackdrop() {
		if ( ! this.backdrop ) {
			return;
		}

		this.backdrop.classList.remove( ModalService.CLASS_ACTIVE );

		this.backdrop.addEventListener( 'transitionend', () => {
			this.backdrop.remove();
			this.backdrop = null;
		} );
	}

	setModalTransition( active = true, setTransition = true ) {
		if ( ! this.modal ) {
			return;
		}

		if ( setTransition ) {
			this.modal.classList.add( ModalService.CLASS_TRANSITION );
		}

		if ( active ) {
			this.modal.classList.add( ModalService.CLASS_ACTIVE );
		} else {
			this.modal.classList.remove( ModalService.CLASS_ACTIVE );
		}

		if ( setTransition ) {
			this.modal.addEventListener( 'transitionend', () => {
				this.modal.classList.remove( ModalService.CLASS_TRANSITION );
			} );
		}
	}
}
