<?php
/**
 * Single variation cart button
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$quantity = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity();

	woocommerce_quantity_input(
		array(
			'min_value'   => $product->get_min_purchase_quantity(),
			'max_value'   => $product->get_max_purchase_quantity(),
			'input_value' => $quantity,
		)
	);

	do_action( 'woocommerce_after_add_to_cart_quantity' );

	printf(
		'<button type="submit" name="add-to-cart" data-product_id="%s" data-quantity="%s" class="%s">%s</button>',
		0,
		esc_attr( $quantity ),
		'single_add_to_cart_button add_to_cart_button ajax_add_to_cart button alt added_to_cart' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ),
		esc_html( $product->single_add_to_cart_text() )
	);
	?>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
