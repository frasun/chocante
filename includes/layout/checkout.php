<?php
/**
 * Layout hooks - checkout
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Checkout;

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_before_checkout_form', __NAMESPACE__ . '\display_page_title', 1 );

// Fix for free shipping notice order.
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

add_filter( 'woocommerce_checkout_cart_item_quantity', __NAMESPACE__ . '\modify_item_quantity', 10, 2 );
add_action( 'woocommerce_review_order_before_payment', __NAMESPACE__ . '\display_payment_title' );
add_action( 'woocommerce_checkout_after_customer_details', __NAMESPACE__ . '\show_back_to_cart' );

// Thank you.
remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

/**
 * Display page title
 */
function display_page_title() {
	get_template_part( 'template-parts/page', 'header' );
}

/**
 * Modify order item quantity in order details
 *
 * @param string $quantity_html Order line item quantity HTML.
 * @param array  $cart_item Order line item.
 * @return string
 */
function modify_item_quantity( $quantity_html, $cart_item ) {
	$product        = $cart_item['data'];
	$quantity       = $cart_item['quantity'];
	$quantity_label = sprintf( '&times; %s', $quantity );
	$variation      = $cart_item['variation'];

	if ( ! empty( $variation ) ) {
		$variation_term = get_term_by( 'slug', reset( $variation ), str_replace( 'attribute_', '', array_key_first( $variation ) ) );

		if ( $variation_term ) {
			$quantity_label = sprintf( '%s &times; %s', $quantity, $variation_term->name );
		}
	}

	return '<span class="product-quantity">' . $quantity_label . '</span>';
}

/**
 * Display payment section title
 */
function display_payment_title() {
	echo '<h6 class="payment__heading">' . esc_html__( 'Payment methods', 'woocommerce' ) . '</h6>';
}

/**
 * Display back to cart button
 */
function show_back_to_cart() {
	echo '<a href="' . esc_url( wc_get_cart_url() ) . '" class="back-to-cart">' . esc_html_x( 'Edit previous step', 'checkout', 'chocante' ) . '</a>';
}
