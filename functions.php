<?php
/**
 * Chocante Theme
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

require_once get_theme_file_path( 'includes/class-chocante.php' );
Chocante::init();

// WooCommerce.
if ( class_exists( 'Chocante_WooCommerce' ) ) {
	/**
	 * Display product badges.
	 */
	function woocommerce_show_product_sale_flash() {
		Chocante_WooCommerce::show_product_badge();
	}

	/**
	 * Display product badges in loop.
	 */
	function woocommerce_show_product_loop_sale_flash() {
		Chocante_WooCommerce::show_product_badge();
	}
}

// ACF.
if ( class_exists( 'ACF' ) ) {
	require_once get_theme_file_path( 'includes/class-chocante-acf.php' );
	Chocante_ACF::init();
}

require_once get_theme_file_path( 'includes/performance.php' );
