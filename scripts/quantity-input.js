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

		this.value = parseInt( this.input.value );
		this.min = parseInt( this.input.min ) || null;
		this.max = parseInt( this.input.max ) || null;
		this.step = parseInt( this.input.step ) || 1;
		this.readOnly = this.input.readOnly;

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
			new Event( 'quantityInputChanged', { bubbles: true } )
		);
	}

	setupQuantityControls() {
		const quantityPlus = this.quantityEl.querySelector(
			QuantityInput.QUANTITY_PLUS
		);
		const quantityMinus = this.quantityEl.querySelector(
			QuantityInput.QUANTITY_MINUS
		);

		this.readOnly =
			this.max &&
			this.min &&
			this.value === this.max &&
			this.value === this.min;

		this.input.readOnly = this.readOnly;

		if ( ! quantityPlus || ! quantityMinus ) {
			return;
		}

		if ( ! this.readOnly ) {
			quantityPlus.addEventListener(
				'click',
				this.setQuantityPlus.bind( this )
			);

			quantityMinus.addEventListener(
				'click',
				this.setQuantityMinus.bind( this )
			);
		} else {
			quantityPlus.disabled = true;
			quantityMinus.disabled = true;
		}
	}
}
