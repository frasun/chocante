import QuantityInput from "./quantity-input";
import ProductSlider from "./product-slider";

new ProductSlider('.product-section--featured', `${chocante.ajaxurl}?nonce=${chocante.nonce}&lang=${chocante.lang}&action=get_products&type=featured`);

jQuery(function ($) {
  // Quantity.
  initQuantityInputs();
  $(document.body).on('updated_wc_div', initQuantityInputs);
  $(document.body).on('click', `.quantity__plus`, (event) => setQuantity(event, 1));
  $(document.body).on('click', `.quantity__minus`, (event) => setQuantity(event, -1));
  $(document.body).on('quantityInputChanged', `input.qty`, submitCartForm);

  // Coupon.
  $(document.body).on('change', 'input[name="coupon_code"]', setCouponValue);
  $(document.body).on('submit', '.checkout_coupon', submitCouponForm);
  $(document.body).on('updated_wc_div', displayCouponError);
  $(document.body).on('removed_coupon', removeCouponError);

  // Remove default option from shipping calculator.
  $('#calc_shipping_country option[value="default"]').remove();

  // Empty cart.
  $(document.body).on('wc_cart_emptied', handleEmptyCart);

  function initQuantityInputs() {
    $('input.qty').each((index, input) => {
      new QuantityInput(input);
    });
  }

  function setQuantity(event, quantity) {
    event.preventDefault();

    const input = $(event.currentTarget).closest('.quantity').find('input.qty');

    if (!input) return;

    const newValue = parseInt($(input).val()) + quantity;

    $(input).val(newValue);
    submitCartForm();
  }

  function submitCartForm() {
    $(':input[name="update_cart"]').prop('disabled', false).trigger('click');
  }

  function setCouponValue(event) {
    $('input[name="coupon_code"]').val(event.target.value);
  };

  function submitCouponForm(event) {
    event.preventDefault();
    $(':input[name="apply_coupon"]').trigger('click');
  }

  function displayCouponError() {
    removeCouponError();

    const couponError = $('.coupon-error-notice');

    if (couponError) {
      $('.woocommerce-notices-wrapper').append(couponError);
    }
  }

  function removeCouponError() {
    $('.woocommerce-notices-wrapper .coupon-error-notice').remove();
  }

  function handleEmptyCart() {
    $(document.body).addClass('cart-empty');
  }
});
