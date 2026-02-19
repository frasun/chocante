<?php
/**
 * Layout hooks - common
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Common;

use function Chocante\Assets\icon;

defined( 'ABSPATH' ) || exit;

// Breadcrumbs.
add_action( 'chocante_before_content_header', __NAMESPACE__ . '\display_page_breadcrumbs' );
add_filter( 'woocommerce_breadcrumb_defaults', __NAMESPACE__ . '\modify_breadcrumbs' );
add_filter( 'rank_math/frontend/breadcrumb/args', __NAMESPACE__ . '\modify_breadcrumbs' );

// Layout.
add_action( 'chocante_header_aside', __NAMESPACE__ . '\display_header_actions' );
add_action( 'chocante_before_footer', __NAMESPACE__ . '\display_join_group' );

// Cart & product page.
add_action( 'woocommerce_before_quantity_input_field', __NAMESPACE__ . '\display_remove_quantity_button' );
add_action( 'woocommerce_after_quantity_input_field', __NAMESPACE__ . '\display_add_quantity_button', 20 );
add_filter( 'woocommerce_quantity_input_type', __NAMESPACE__ . '\set_quantity_input_type' );

// Free shipping.
if ( class_exists( 'Chocante_Free_Shipping' ) ) {
	add_action( 'chocante_delivery_info', __NAMESPACE__ . '\display_free_delivery_info' );
}

// Product search.
add_action( 'pre_get_product_search_form', __NAMESPACE__ . '\display_product_search_title' );
add_filter( 'get_product_search_form', __NAMESPACE__ . '\display_product_search_icon' );

// Product loop.
add_filter( 'woocommerce_loop_add_to_cart_link', __NAMESPACE__ . '\add_to_cart_button', 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', __NAMESPACE__ . '\add_to_cart_text', 10, 2 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 50 );
add_action( 'woocommerce_before_shop_loop_item_title', __NAMESPACE__ . '\add_loop_item_info_open', 30 );
add_action( 'woocommerce_after_shop_loop_item_title', __NAMESPACE__ . '\add_loop_item_info_close', 20 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
add_action( 'woocommerce_after_shop_loop_item', __NAMESPACE__ . '\add_loop_item_info_close', 30 );

// Product & archive page.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
add_action( 'woocommerce_before_main_content', __NAMESPACE__ . '\open_main_element' );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );
add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\close_main_element', 60 );

// Page footer.
add_action( 'wp_footer', __NAMESPACE__ . '\output_product_search', 30 );

/**
 * Display account link, mini-cart & product search actions in header
 *
 * @param string $container_id Container CSS class.
 */
function display_header_actions( $container_id ) {
	if ( $container_id ) {
		echo '<aside class="' . esc_attr( $container_id ) . '">';
	}

	get_template_part( 'template-parts/mini-cart' );
	get_template_part( 'template-parts/customer-account-link' );
	get_template_part( 'template-parts/product-search' );

	if ( $container_id ) {
		echo '</aside>';
	}
}

/**
 * Display join Facebook group section
 */
function display_join_group() {
	get_template_part( 'template-parts/join', 'group' );
}

/**
 * Display page breadcrumbs
 */
function display_page_breadcrumbs() {
	if ( is_page_template( 'page-templates/temp.php' ) && function_exists( 'rank_math_the_breadcrumbs' ) ) {
		rank_math_the_breadcrumbs();
	} elseif ( is_singular( 'post' ) ) {
		get_template_part( 'template-parts/breadcrumbs', 'post' );
	}
}

/**
 * Modify breadcrumbs settings
 *
 * @param array $args Breadcrumbs args.
 * @return array
 */
function modify_breadcrumbs( $args ) {
	$args['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="' . esc_html__( 'Breadcrumb', 'chocante' ) . '">';
	$args['wrap_after']  = '</nav>';
	$args['before']      = '<span>';
	$args['after']       = '</span>';
	$args['delimiter']   = ''; // Woo breadcrumbs.
	$args['separator']   = ''; // RankMath breadcrumbs.

	return $args;
}

/**
 * Return spinner image
 */
function spinner() {
	return '<img src="' . esc_url( get_theme_file_uri( 'images/spinner-2x.gif' ) ) . '" alt="' . esc_attr_x( 'Loading', 'product slider', 'chocante' ) . '" class="spinner">';
}

/**
 * Output product search form
 */
function output_product_search() {
	if ( is_admin() ) {
		return;
	}

	get_template_part( 'template-parts/product-search', 'form' );
}

/**
 * Add title to product search
 */
function display_product_search_title() {
	echo '<h4 class="search-products__title">' . esc_html__( 'Search products', 'woocommerce' ) . '</h4>';
}

/**
 * Add title to product search
 *
 * @param string $form Search form HTML.
 * @return string
 */
function display_product_search_icon( $form ) {
	ob_start();
	echo '<div class="search-products__icon">';
	icon( 'search' );
	echo '</div>';

	return str_replace( '</form>', ob_get_clean() . '</form>', $form );
}

/**
 * Display quantity buttons in cart
 */
function display_add_quantity_button() {
	get_template_part( 'template-parts/quantity', 'plus' );
}

/**
 * Display quantity buttons in cart
 */
function display_remove_quantity_button() {
	get_template_part( 'template-parts/quantity', 'minus' );
}

/**
 * Always set quantity input to type="number"
 */
function set_quantity_input_type() {
	return 'number';
}

/**
 * Display free delivery infor
 */
function display_free_delivery_info() {
	get_template_part(
		'template-parts/info',
		'section',
		array(
			'icon'    => 'shipping',
			'content' => \Chocante_Free_Shipping::instance()->display_free_shipping_info( true ),
		)
	);
}

/**
 * Output product loop item content wrapper open
 */
function add_loop_item_info_open() {
	echo '<div class="woocommerce-loop-product__info-wrapper">';
	echo '<div class="woocommerce-loop-product__info">';
}

/**
 * Output product loop item content wrapper close
 */
function add_loop_item_info_close() {
	echo '</div>';
}

/**
 * Replace add to cart link with button
 *
 * @param string     $link Add to cart url.
 * @param WC_Product $product Product object.
 * @return string
 */
function add_to_cart_button( $link, $product ) {
	return '<button class="button">' . esc_html( $product->add_to_cart_text() ) . '</button>';
}

/**
 * Replace add to cart text when product is out of stock
 *
 * @param string     $text Add to cart text.
 * @param WC_Product $product Product object.
 * @return string
 */
function add_to_cart_text( $text, $product ) {
	return $product->is_in_stock() ? _x( 'Buy now', 'product loop', 'chocante' ) : __( 'Read more', 'woocommerce' );
}

/**
 * Open <main> element
 */
function open_main_element() {
	echo '<main role="main">';
}

/**
 * Close <main> element
 */
function close_main_element() {
	echo '</main>';
}

/**
 * Display product badges.
 */
function show_product_badge() {
	get_template_part( 'template-parts/product', 'badge' );
}
