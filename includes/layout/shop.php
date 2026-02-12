<?php
/**
 * Layout hooks - shop catalog
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Shop;

use function Chocante\Assets\icon;

defined( 'ABSPATH' ) || exit;

// Breadcrumbs.
add_action( 'chocante_product_archive_header', 'woocommerce_breadcrumb', 20 );

// Notices.
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices' );
add_action( 'chocante_product_archive_header', 'woocommerce_output_all_notices' );

// Layout.
add_action( 'woocommerce_archive_description', __NAMESPACE__ . '\display_shop_short_description', 5 );
add_action( 'woocommerce_shop_loop_header', __NAMESPACE__ . '\open_shop_loop_wrapper' );
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\open_shop_loop_section', 12 );
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\open_shop_loop_header', 15 );
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\close_shop_loop_header', 35 );
add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\close_shop_loop_wrapper' );
add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\close_shop_loop_section' );
add_action( 'woocommerce_after_main_content', __NAMESPACE__ . '\display_shop_description', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\set_catalog_ordering_options', 30 );
add_filter( 'woocommerce_pagination_args', __NAMESPACE__ . '\modify_pagination' );

// Filters.
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\output_mobile_filter_trigger', 25 );
add_action( 'chocante_product_filters_header', __NAMESPACE__ . '\output_mobile_filter_close' );

/**
 * Set catalog ordering.
 */
function set_catalog_ordering_options() {
	woocommerce_catalog_ordering( array( 'useLabel' => true ) );
}

/**
 * Open loop section
 */
function open_shop_loop_wrapper() {
	echo '<section class="shop-loop">';
	echo '<div class="shop-loop__container">';
}

/**
 * Close loop section
 */
function close_shop_loop_wrapper() {
	echo '</div>';
	echo '</section>';
}

/**
 * Open shop loop header
 */
function open_shop_loop_header() {
	if ( woocommerce_products_will_display() ) {
		echo '<header class="shop-loop__header">';
	}
}

/**
 * Close shop loop header
 */
function close_shop_loop_header() {
	if ( woocommerce_products_will_display() ) {
		echo '</header>';
	}
}

/**
 * Open shop loop section
 */
function open_shop_loop_section() {
	echo '<section class="shop-loop__section">';
}

/**
 * Close shop loop section
 */
function close_shop_loop_section() {
	echo '</section>';
}

/**
 * Display mobile product filters trigger
 */
function output_mobile_filter_trigger() {
	if ( ! class_exists( 'Chocante_Product_Filters' ) ) {
		return;
	}

	if ( woocommerce_products_will_display() && \Chocante_Product_Filters::instance()->has_available_filters() ) {
		echo '<button id="openMobileFilters">';
		echo esc_html__( 'Filter', 'chocante-product-filters' );
		if ( \Chocante_Product_Filters::instance()->has_filters() ) {
			printf( '<span>%d</span>', esc_html( \Chocante_Product_Filters::instance()->get_active_filters() ) );
		}
		echo '</button>';
	}
}

/**
 * Display mobile product filters close button
 */
function output_mobile_filter_close() {
	get_template_part( 'template-parts/modal-close' );
}

/**
 * Display shop page short description
 */
function display_shop_short_description() {
	if ( ! is_shop() || is_search() ) {
		return;
	}

	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );

	$short_description = get_the_excerpt( wc_get_page_id( 'shop' ) );

	if ( empty( $short_description ) ) {
		return;
	}

	echo '<div class="term-description">' . wp_kses_post( apply_filters( 'the_excerpt', $short_description ) ) . '</div>';
}

/**
 * Display shop page description
 */
function display_shop_description() {
	if ( ! is_shop() ) {
		return;
	}

	woocommerce_product_archive_description();
}

/**
 * Modify shop pagination
 *
 * @param array $pagination Pagination args.
 * @return array
 */
function modify_pagination( $pagination ) {
	ob_start();
	echo '<span class="screen-reader-text">';
	esc_html_e( 'Previous page', 'woocommerce' );
	echo '</span>';
	icon( 'prev' );
	$prev_icon = ob_get_clean();

	ob_start();
	echo '<span class="screen-reader-text">';
	esc_html_e( 'Next page', 'woocommerce' );
	echo '</span>';
	icon( 'next' );
	$next_icon = ob_get_clean();

	$pagination['prev_text'] = is_rtl() ? $next_icon : $prev_icon;
	$pagination['next_text'] = is_rtl() ? $prev_icon : $next_icon;

	return $pagination;
}
