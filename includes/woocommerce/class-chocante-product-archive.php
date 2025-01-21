<?php
/**
 * Chocante WooCommerce product archive page
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Product_Archive class.
 */
class Chocante_Product_Archive {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Breadcrumbs.
		add_action( 'chocante_product_archive_header', 'woocommerce_breadcrumb', 20 );

		// Notices.
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices' );
		add_action( 'chocante_product_archive_header', 'woocommerce_output_all_notices' );

		// Layout.
		add_action( 'woocommerce_archive_description', array( __CLASS__, 'display_shop_short_description' ), 5 );
		add_action( 'woocommerce_shop_loop_header', array( __CLASS__, 'open_shop_loop_wrapper' ) );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'open_shop_loop_section' ), 12 );
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'open_shop_loop_header' ), 15 );
		if ( class_exists( 'Chocante_Product_Filters' ) ) {
			add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'output_mobile_filter_trigger' ), 25 );
			add_action( 'chocante_product_filters_header', array( __CLASS__, 'output_mobile_filter_close' ) );
		}
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'close_shop_loop_header' ), 35 );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'close_shop_loop_wrapper' ) );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'close_shop_loop_section' ) );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'display_shop_description' ), 20 );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		$shop_js = include get_theme_file_path() . '/build/shop-scripts.asset.php';

		wp_enqueue_script(
			'chocante-shop-js',
			get_theme_file_uri() . '/build/shop-scripts.js',
			array_merge( $shop_js['dependencies'], array() ),
			$shop_js['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$shop_css = include get_theme_file_path() . '/build/shop.asset.php';

		wp_enqueue_style(
			'chocante-shop-css',
			get_theme_file_uri() . '/build/shop.css',
			$shop_css['dependencies'],
			$shop_css['version'],
		);
	}

	/**
	 * Open loop section
	 */
	public static function open_shop_loop_wrapper() {
		echo '<section class="shop-loop">';
		echo '<div class="shop-loop__container">';
	}

	/**
	 * Close loop section
	 */
	public static function close_shop_loop_wrapper() {
		echo '</div>';
		echo '</section>';
	}

	/**
	 * Open shop loop header
	 */
	public static function open_shop_loop_header() {
		if ( woocommerce_products_will_display() ) {
			echo '<header class="shop-loop__header">';
		}
	}

	/**
	 * Close shop loop header
	 */
	public static function close_shop_loop_header() {
		if ( woocommerce_products_will_display() ) {
			echo '</header>';
		}
	}

	/**
	 * Open shop loop section
	 */
	public static function open_shop_loop_section() {
		echo '<section class="shop-loop__section">';
	}

	/**
	 * Close shop loop section
	 */
	public static function close_shop_loop_section() {
		echo '</section>';
	}

	/**
	 * Display mobile product filters trigger
	 */
	public static function output_mobile_filter_trigger() {
		if ( woocommerce_products_will_display() ) {
			echo '<button id="openMobileFilters">' . esc_html__( 'Filter', 'chocante-product-filters' ) . '</button>';
		}
	}

	/**
	 * Display mobile product filters close button
	 */
	public static function output_mobile_filter_close() {
		get_template_part( 'template-parts/modal-close' );
	}

	/**
	 * Display shop page short description
	 */
	public static function display_shop_short_description() {
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
	public static function display_shop_description() {
		if ( ! is_shop() ) {
			return;
		}

		woocommerce_product_archive_description();
	}
}
