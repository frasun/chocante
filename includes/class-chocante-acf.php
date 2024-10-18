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
class Chocante_ACF {
	const ACF_PRODUCT_TITLE = 'tekst_przed_tytulem';
	const ACF_PRODUCT_TYPE  = 'tekst_po_tytule';

	/**
	 * Class constructor.
	 */
	public static function init() {
		// Product custom title.
		add_filter( 'woocommerce_cart_item_name', array( self::class, 'get_custom_title' ), 10, 2 );
	}

	/**
	 * Modify product title using ACF fields
	 *
	 * @param string $product_name Product title.
	 * @param array  $cart_item Product in the cart.
	 * @return string
	 */
	public static function get_custom_title( $product_name, $cart_item ) {
		$product_id = $cart_item['product_id'];

		if ( 'product' === get_post_type( $product_id ) ) {
			$product_short_name = get_field( self::ACF_PRODUCT_TITLE, $product_id );
			$product_type       = get_field( self::ACF_PRODUCT_TYPE, $product_id );

			if ( $product_short_name ) {
				$product_name = $product_short_name;

				if ( $product_type ) {
					$product_name .= '<small>' . $product_type . '</small>';
				}

				return '<div>' . $product_name . '</div>';
			}
		}

		return $product_name;
	}
}
