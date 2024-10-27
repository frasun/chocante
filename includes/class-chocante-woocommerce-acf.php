<?php
/**
 * Chocante ACF
 *
 * @package Chocante
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Chocante_WooCommerce class.
 */
class Chocante_Woocommerce_ACF {
	const ACF_PRODUCT_TITLE = 'tekst_przed_tytulem';
	const ACF_PRODUCT_TYPE  = 'tekst_po_tytule';

	/**
	 * Class constructor.
	 */
	public static function init() {
		// Product custom title.
		add_filter( 'woocommerce_cart_item_name', array( self::class, 'get_custom_product_title' ), 10, 2 );

		// Product loop item.
		add_action( 'woocommerce_after_shop_loop_item_title', array( self::class, 'display_loop_item_type' ), 3 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
		add_action( 'woocommerce_shop_loop_item_title', array( self::class, 'modify_loop_item_title' ) );
	}

	/**
	 * Modify product title using ACF fields
	 *
	 * @param string $product_name Product title.
	 * @param array  $cart_item Product in the cart.
	 * @return string
	 */
	public static function get_custom_product_title( $product_name, $cart_item ) {
		$product_id = $cart_item['product_id'];
		$product    = $cart_item['data'];

		if ( 'product' === get_post_type( $product_id ) ) {
			$product_short_name = get_field( self::ACF_PRODUCT_TITLE, $product_id );
			$product_type       = get_field( self::ACF_PRODUCT_TYPE, $product_id );

			if ( $product_short_name ) {
				$product_name = $product_short_name;

				if ( $product_type ) {
					$product_name .= '<small>' . $product_type . '</small>';
				}

				return sprintf( '<a href="%s">%s</a>', esc_url( $product->get_permalink() ), wp_kses_post( $product_name ) );
			}
		}

		return $product_name;
	}

	/**
	 * Display product type ACF field in the loop item.
	 */
	public static function display_loop_item_type() {
		global $product;

		if ( ! isset( $product ) ) {
			return;
		}

		$product_type = get_field( self::ACF_PRODUCT_TYPE, $product->get_id() );

		if ( $product_type ) {
			echo "<span class='woocommerce-loop-product__type'>" . esc_html( $product_type ) . '</span>';
		}
	}

	/**
	 * Display ACF field instead of default product title in the loop item.
	 */
	public static function modify_loop_item_title() {
		global $product;

		if ( ! isset( $product ) ) {
			return;
		}

		$product_name  = get_field( self::ACF_PRODUCT_TITLE, $product->get_id() );
		$product_title = $product_name ? $product_name : get_the_title();

		echo '<h2 class="' . esc_attr( apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' ) ) . '">' . $product_title . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
