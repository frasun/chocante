<?php
/**
 * Layout hooks - cart
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Cart;

use function Chocante\Layout\ProductSection\display_product_section;
use function Chocante\Assets\icon;
use function Chocante\Woo\get_variation_name;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

add_action( 'woocommerce_before_cart', __NAMESPACE__ . '\display_cart_title', 1 );
add_action( 'woocommerce_after_cart_table', __NAMESPACE__ . '\display_cart_info' );
add_action( 'woocommerce_cart_totals_before_shipping', __NAMESPACE__ . '\display_coupon_form_in_cart' );
add_filter( 'woocommerce_cart_item_permalink', __NAMESPACE__ . '\return_empty_permalink' );
add_action( 'chocante_after_main', __NAMESPACE__ . '\display_featured_products_in_cart' );
add_filter( 'body_class', __NAMESPACE__ . '\modify_empty_cart_body_class' );
remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
add_filter( 'woocommerce_cart_totals_order_total_html', __NAMESPACE__ . '\display_including_tax_sup' );
add_action(
	'woocommerce_review_order_after_cart_contents',
	function () {
		add_filter( 'woocommerce_cart_display_prices_including_tax', __NAMESPACE__ . '\display_tax_in_totals' );
	}
);
add_action(
	'woocommerce_review_order_before_order_total',
	function () {
		remove_filter( 'woocommerce_cart_display_prices_including_tax', __NAMESPACE__ . '\display_tax_in_totals', 10 );
	}
);

// Cart & mini-cart.
add_filter( 'woocommerce_cart_item_remove_link', __NAMESPACE__ . '\use_remove_icon' );
add_filter( 'woocommerce_cart_item_name', __NAMESPACE__ . '\display_product_name_with_link', 10, 2 );
add_filter( 'woocommerce_cart_item_price', __NAMESPACE__ . '\modify_price_in_cart', 10, 2 );

// Mini-cart.
add_action( 'woocommerce_before_mini_cart', __NAMESPACE__ . '\display_mini_cart_title' );
remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
add_filter( 'woocommerce_widget_cart_item_quantity', __NAMESPACE__ . '\display_mini_cart_item_total', 10, 3 );
add_action(
	'woocommerce_before_mini_cart_contents',
	function () {
		add_filter( 'woocommerce_cart_item_thumbnail', __NAMESPACE__ . '\get_mini_cart_thumbnail', 10, 2 );
	}
);
add_action(
	'woocommerce_after_mini_cart_contents',
	function () {
		remove_filter( 'woocommerce_cart_item_thumbnail', __NAMESPACE__ . '\get_mini_cart_thumbnail', 10 );
	}
);
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\update_mini_cart_count' );

// Modify shipping calculator.
add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_false' );
add_filter( 'woocommerce_shipping_calculator_enable_postcode', '__return_false' );
add_filter( 'woocommerce_shipping_calculator_enable_state', '__return_false' );


/**
 * Display additional information about delivery
 */
function display_cart_info() {
	get_template_part( 'template-parts/info', 'cart' );
}

/**
 * Display coupon form in cart
 */
function display_coupon_form_in_cart() {
	get_template_part( 'template-parts/cart', 'coupon' );
}

/**
 * Return # as a link to product in cart that chnaged status to out of stock in order to remain HTML structure
 *
 * @param string $link Product permalink.
 * @return string
 */
function return_empty_permalink( $link ) {
	return empty( $link ) ? '#' : $link;
}

/**
 * Display featured products in cart
 */
function display_featured_products_in_cart() {
	if ( is_cart() ) {
		display_product_section(
			array(
				'heading'  => _x( 'Featured products', 'product slider', 'chocante' ),
				'featured' => true,
			)
		);
	}
}

/**
 * Display cart title
 */
function display_cart_title() {
	if ( ! WC()->cart->is_empty() ) {
		echo wp_kses_post( '<h1 class="page-title">' . get_the_title() . '</h1>' );
	}
}

/**
 * Add class to body when cart is empty
 *
 * @param string[] $body_class Body CSS classes.
 * @return string[]
 */
function modify_empty_cart_body_class( $body_class ) {
	if ( is_cart() && WC()->cart->is_empty() ) {
		$body_class[] = 'cart-empty';
	}

	return $body_class;
}

/**
 * Display title in mini cart
 */
function display_mini_cart_title() {
	get_template_part( 'template-parts/mini-cart', 'header' );
}

/**
 * Display item total in mini cart
 *
 * @param string $quantity Quantity HTML.
 * @param array  $cart_item Cart line item.
 * @return string
 */
function display_mini_cart_item_total( $quantity, $cart_item ) {
	return '<footer>' . $quantity . '<strong>' . wc_price( wc_get_price_to_display( $cart_item['data'], array( 'qty' => $cart_item['quantity'] ) ) ) . '</strong></footer>';
}

/**
 * Use remove icon in cart & mini-cart
 *
 * @param string $remove_link Remove link HTML.
 * @return string
 */
function use_remove_icon( $remove_link ) {
	ob_start();
	icon( 'remove' );

	return str_replace( '&times;</a>', ob_get_clean() . '</a>', $remove_link );
}

/**
 * Display product name without variation data
 *
 * @param string $product_name Product title.
 * @param array  $cart_item Product in the cart.
 * @return string
 */
function display_product_name_with_link( $product_name, $cart_item ) {
	$product              = $cart_item['data'];
	$product_display_name = $product->get_title();

	if ( $product->is_visible() ) {
		return sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ), $product_display_name );
	}

	return $product_display_name;
}

/**
 * Display variation suffix next to price in cart & mini-cart.
 *
 * @param string $price Search form HTML.
 * @param arrat  $cart_item Product item in cart.
 * @return string
 */
function modify_price_in_cart( $price, $cart_item ) {
	$product        = $cart_item['data'];
	$variation_name = get_variation_name( $product );

	if ( $variation_name ) {
		$price .= " <span class='woocommerce-price-suffix'>/ {$variation_name}</span>";
	}

	return $price;
}

/**
 * Use smaller image in mini-cart
 *
 * @param string $product_image Image url.
 * @param array  $cart_item Product in the cart.
 */
function get_mini_cart_thumbnail( $product_image, $cart_item ) {
	$product = $cart_item['data'];

	return $product->get_image( 'woocommerce_gallery_thumbnail' );
}

/**
 * Conditionally display including tax supersciption in cart total value
 *
 * // @param string $value Cart total HTML>
 *
 * @return string
 */
function display_including_tax_sup() {
	return '<strong>' . WC()->cart->get_total() . '</strong>';
}

/**
 * Conditionally display tax line item in cart totals
 */
function display_tax_in_totals() {
	$customer        = WC()->customer;
	$billing_country = $customer->get_billing_country();
	$is_vat_country  = in_array( $billing_country, WC()->countries->get_european_union_countries( 'eu_vat' ), true );
	$company         = $customer->get_billing_company();

	return ! ( ! empty( $company ) && $is_vat_country );
}

/**
 * Add cart count to wc-fragments
 *
 * @param array $fragments WC fragments.
 * @return array
 */
function update_mini_cart_count( $fragments ) {
	if ( ! is_object( WC()->cart ) ) {
		return;
	}

	$fragments['cart-count'] = WC()->cart->get_cart_contents_count();

	return $fragments;
}
