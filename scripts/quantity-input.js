export default class QuantityInput {
	static QUANTITY_PLUS = '.quantity__plus';
	static QUANTITY_MINUS = '.quantity__minus';
	static ERROR_MISSING_INPUT = 'Missing quantity input element';

	constructor( quantityEl ) {
		this.quantityEl = quantityEl;
		this.input = this.quantityEl.querySelector( 'input.qty' );

		if ( ! this.input ) {
			throw new Error( QuantityInput.ERROR_MISSING_INPUT );
		}

		this.addQuantity = this.setQuantityPlus.bind( this );
		this.subtractQuantity = this.setQuantityMinus.bind( this );

		this.setupQuantityControls();

		this.input.addEventListener( 'blur', this.setInputValue.bind( this ) );
	}

	setQuantityPlus( event ) {
		event.preventDefault();
		const newValue = parseInt( this.value ) + this.step;

		if ( this.max && newValue > this.max ) {
			return;
		}

		this.setQuantity( newValue );
	}

	setQuantityMinus( event ) {
		event.preventDefault();
		const newValue = parseInt( this.value ) - this.step;

		if ( this.min && newValue < this.min ) {
			return;
		}

		this.setQuantity( newValue );
	}

	setInputValue( event ) {
		if ( event.target.value !== this.value ) {
			this.value = event.target.value;
			this.quantityChanged();
		}
	}

	setQuantity( quantity ) {
		if ( quantity !== this.value ) {
			this.input.value = quantity;
			this.value = quantity;
			this.quantityChanged();
		}
	}

	quantityChanged() {
		this.input.dispatchEvent(
			new CustomEvent( 'quantityInputChanged', {
				bubbles: true,
				detail: {
					quantity: this.value,
				},
			} )
		);
	}

	setupQuantityControls() {
		this.value = parseInt( this.input.value );
		this.min = parseInt( this.input.min ) || null;
		this.max = parseInt( this.input.max ) || null;
		this.step = parseInt( this.input.step ) || 1;
		this.readOnly =
			this.max &&
			this.min &&
			this.value === this.max &&
			this.value === this.min;

		this.input.readOnly = this.readOnly;

		if ( this.max && this.value > this.max ) {
			this.value = this.max;
			this.input.value = this.value;
		}

		const quantityPlus = this.quantityEl.querySelector(
			QuantityInput.QUANTITY_PLUS
		);
		const quantityMinus = this.quantityEl.querySelector(
			QuantityInput.QUANTITY_MINUS
		);

		if ( ! quantityPlus || ! quantityMinus ) {
			return;
		}

		if ( ! this.readOnly ) {
			quantityPlus.addEventListener( 'click', this.addQuantity );
			quantityMinus.addEventListener( 'click', this.subtractQuantity );
			quantityPlus.disabled = false;
			quantityMinus.disabled = false;
		} else {
			quantityPlus.removeEventListener( 'click', this.addQuantity );
			quantityMinus.removeEventListener( 'click', this.subtractQuantity );
			quantityPlus.disabled = true;
			quantityMinus.disabled = true;
		}
	}
}
