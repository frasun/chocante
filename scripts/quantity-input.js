export default class QuantityInput {
  constructor(input) {
    if (!input) {
      throw new Error('Missing quantity input element');
    }

    this.input = input;
    this.value = input.value;

    input.addEventListener('blur', this.setInputValue.bind(this));
  }

  setInputValue(event) {
    if (event.target.value !== this.value) {
      this.value = event.target.value;
      this.input.dispatchEvent(new Event('quantityInputChanged', { bubbles: true }));
    }
  }
}