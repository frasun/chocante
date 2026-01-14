<?php
/**
 * Chocante WooCommerce cart
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chocante_Cart class.
 */
class Chocante_Cart {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_head', array( __CLASS__, 'preload_assets' ), 1 );

		add_action( 'woocommerce_before_cart', array( __CLASS__, 'display_cart_title' ), 1 );
		add_action( 'woocommerce_after_cart_table', array( __CLASS__, 'display_cart_info' ) );
		add_action( 'woocommerce_cart_totals_before_shipping', array( __CLASS__, 'display_coupon_form_in_cart' ) );
		add_filter( 'woocommerce_cart_item_permalink', array( __CLASS__, 'return_empty_permalink' ) );
		add_action( 'chocante_after_main', array( __CLASS__, 'display_featured_products_in_cart' ) );

		// Cart empty.
		if ( ! is_admin() ) {
			add_filter( 'body_class', array( __CLASS__, 'modify_empty_cart_body_class' ) );
		}
		remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
	}

	/**
	 * Enqueue scripts & styles
	 */
	public static function enqueue_scripts() {
		$cart_js = Chocante::asset( 'cart-scripts' );

		wp_enqueue_script(
			'chocante-cart-js',
			get_theme_file_uri( 'build/cart-scripts.js' ),
			array_merge( $cart_js['dependencies'], array( 'jquery' ) ),
			$cart_js['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$cart_css = Chocante::asset( 'cart' );

		wp_enqueue_style(
			'chocante-cart-css',
			get_theme_file_uri( 'build/cart.css' ),
			$cart_css['dependencies'],
			$cart_css['version'],
		);
	}

	/**
	 * Preload assets.
	 */
	public static function preload_assets() {
		$cart_css = Chocante::asset( 'cart' );
		$css_path = get_theme_file_uri( 'build/cart.css' ) . '?ver=' . $cart_css['version'];
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "<link rel=\"preload\" href=\"{$css_path}\" as=\"style\" />";
	}

	/**
	 * Display additional information about delivery
	 */
	public static function display_cart_info() {
		get_template_part( 'template-parts/info', 'cart' );
	}

	/**
	 * Display coupon form in cart
	 */
	public static function display_coupon_form_in_cart() {
		get_template_part( 'template-parts/cart', 'coupon' );
	}

	/**
	 * Return # as a link to product in cart that chnaged status to out of stock in order to remain HTML structure
	 *
	 * @param string $link Product permalink.
	 * @return string
	 */
	public static function return_empty_permalink( $link ) {
		return empty( $link ) ? '#' : $link;
	}

	/**
	 * Display featured products in cart
	 */
	public static function display_featured_products_in_cart() {
		Chocante_Product_Section::class::display_product_section(
			array(
				'heading'  => _x( 'Featured products', 'product slider', 'chocante' ),
				'featured' => true,
			)
		);
	}

	/**
	 * Display cart title
	 */
	public static function display_cart_title() {
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
	public static function modify_empty_cart_body_class( $body_class ) {
		if ( WC()->cart->is_empty() ) {
			$body_class[] = 'cart-empty';
		}

		return $body_class;
	}
}
