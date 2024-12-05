export default class QuantityInput {
  constructor(quantityEl) {
    this.input = quantityEl.querySelector('input.qty');

    if (!this.input) {
      throw new Error('Missing quantity input element');
    }

    this.value = this.input.value;

    const quantityPlus = quantityEl.querySelector('.quantity__plus');
    const quantityMinus = quantityEl.querySelector('.quantity__minus');

    if (quantityPlus) {
      quantityPlus.addEventListener('click', (event) => {
        event.preventDefault();
        this.setQuantity(1);
      });
    }

    if (quantityMinus) {
      quantityMinus.addEventListener('click', (event) => {
        event.preventDefault();
        this.setQuantity(-1);
      })
    }

    this.input.addEventListener('blur', this.setInputValue.bind(this));
  }

  setInputValue(event) {
    if (event.target.value !== this.value) {
      this.value = event.target.value;
      this.quantityChanged();
    }
  }

  setQuantity(quantity) {
    const newValue = parseInt(this.input.value) + quantity;

    if (newValue) {
      this.input.value = newValue;
      this.value = newValue;
      this.quantityChanged();
    }
  }

  quantityChanged() {
    this.input.dispatchEvent(new Event('quantityInputChanged', { bubbles: true }));
  }
}