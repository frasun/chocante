import QuantityInput from "./quantity-input";

const productQuantity = document.querySelector('form.cart .quantity');

if (productQuantity) {
  new QuantityInput(productQuantity);
}
