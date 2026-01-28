<?php
/**
 * Caching rules
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Cache class.
 */
class Chocante_Cache {
	/**
	 * Register hooks.
	 */
	public static function init() {
		// Product page - stock quantity.
		add_action( 'wp_ajax_get_product_stock', array( __CLASS__, 'cache_ajax' ), 5 );
		add_action( 'wp_ajax_nopriv_get_product_stock', array( __CLASS__, 'cache_ajax' ), 5 );
		add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'clear_cached_product_stock' ) );
		add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'clear_cached_product_stock' ) );

		// Product sliders.
		add_action( 'wp_ajax_get_product_section', array( __CLASS__, 'cache_ajax' ), 5 );
		add_action( 'wp_ajax_nopriv_get_product_section', array( __CLASS__, 'cache_ajax' ), 5 );
		add_action( 'chocante_product_section_clear_cached_products', array( __CLASS__, 'clear_cached_product_section' ) );
	}

	/**
	 * Set public cache for AJAX calls.
	 */
	public static function cache_ajax() {
		do_action( 'litespeed_control_force_public', 'chocante ajax' );
	}

	/**
	 * Clear cached product stock data
	 *
	 * @param WC_Product $product Product object.
	 */
	public static function clear_cached_product_stock( $product ) {
		do_action( 'litespeed_purge', 'AJAX.get_product_stock' );
	}

	/**
	 * Clear cached product sliders
	 */
	public static function clear_cached_product_section() {
		do_action( 'litespeed_purge', 'AJAX.get_product_section' );
	}
}
