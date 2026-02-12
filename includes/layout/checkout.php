<?php
/**
 * Layout hooks - checkout
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Checkout;

use const Chocante\Woo\PRODUCT_WEIGHT_ATT;

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

// Order pay.
add_filter( 'woocommerce_order_item_quantity_html', __NAMESPACE__ . '\modify_order_item_quantity', 10, 2 );

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
	$product = $cart_item['data'];

	if ( $product instanceof \WC_Product_Variation ) {
		$weight = $product->get_attribute( PRODUCT_WEIGHT_ATT );

		return "<span class='product-variation-quantity'>{$weight}{$quantity_html}</div>";
	}

	return $quantity_html;
}

/**
 * Modify order item quantity in order details
 *
 * @param string        $quantity_html Order line item quantity HTML.
 * @param WC_Order_Item $item Order line item.
 * @return string
 */
function modify_order_item_quantity( $quantity_html, $item ) {
	$attribute   = PRODUCT_WEIGHT_ATT;
	$weight      = '';
	$weight_slug = $item->get_meta( $attribute );

	if ( $weight_slug ) {
		$term = get_term_by( 'slug', $item->get_meta( $attribute ), $attribute );

		if ( ! is_wp_error( $term ) ) {
			$weight = $term->name;
		}

		if ( ! empty( $weight ) ) {
			return "<span class='product-variation-quantity'>{$weight}{$quantity_html}</div>";
		}
	}

	return $quantity_html;
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
