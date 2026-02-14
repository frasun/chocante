<?php
/**
 * WooCommerce settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Woo;

defined( 'ABSPATH' ) || exit;

// Mini-cart count.
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\update_mini_cart_count' );

// AJAX add to cart.
add_action( 'woocommerce_ajax_added_to_cart', __NAMESPACE__ . '\make_success_notice_on_add_to_cart' );
add_filter( 'woocommerce_cart_redirect_after_error', __NAMESPACE__ . '\make_error_notice_on_add_to_cart' );
add_filter( 'woocommerce_add_to_cart_fragments', __NAMESPACE__ . '\add_fragments_with_add_to_cart_notices' );

// Modify price display.
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\set_price_display_modify' );
add_action( 'chocante_product_section_loop', __NAMESPACE__ . '\set_price_display_modify' );
add_filter( 'woocommerce_get_price_suffix', __NAMESPACE__ . '\add_price_suffix', 10, 4 );
add_filter( 'woocommerce_format_price_range', __NAMESPACE__ . '\modify_price_range', 10, 3 );

// Post-code validation.
add_action( 'wp_ajax_validate_postcode', __NAMESPACE__ . '\validate_postcode' );
add_action( 'wp_ajax_nopriv_validate_postcode', __NAMESPACE__ . '\validate_postcode' );

// Globkurier.
add_filter( 'woocommerce_shipping_methods', __NAMESPACE__ . '\add_globkurier_shipping_method' );

// EU VAT.
add_filter( 'wp_vat_eu_validator_PL', __NAMESPACE__ . '\validate_nip', 10, 2 );

// Gift wrapper.
add_filter( 'tgpc_wc_gift_wrapper_icon_html', __NAMESPACE__ . '\disable_gift_wrapper_icon_in_admin' );
add_filter( 'tgpc_wc_gift_wrapper_checkout_label', __NAMESPACE__ . '\display_gift_wrapper_label', 10, 3 );

/**
 * Fix PHP notice in widgets page
 *
 * @link https://github.com/WordPress/gutenberg/issues/33576#issuecomment-883690807
 */
remove_filter( 'admin_head', 'wp_check_widget_editor_deps' );

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


/**
 * Add notice on successful add to cart.
 *
 * @param int $product_id ID of added product.
 */
function make_success_notice_on_add_to_cart( $product_id ) {
  // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$quantity = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_POST['quantity'] ) );

	wc_add_to_cart_message( array( $product_id => $quantity ), true );
}

/**
 * Add notice on error add to cart.
 */
function make_error_notice_on_add_to_cart() {
	\WC_AJAX::get_refreshed_fragments();

	return false;
}

/**
 * Include add to cart notices in cart fragments
 *
 * @param array $fragments WC fragments.
 * @return array
 */
function add_fragments_with_add_to_cart_notices( $fragments ) {
	if ( wc_notice_count() > 0 ) {
		$notices_html = wc_print_notices( true );

		if ( ! empty( $notices_html ) ) {
			$fragments['add-to-cart'] = $notices_html;
		}
	}

	return $fragments;
}

/**
 * Set global variable to modify price display
 */
function set_price_display_modify() {
	global $chocante_display_price_modify;
	$chocante_display_price_modify = true;
}

/**
 * Add variation suffix to product price
 *
 * @param html       $suffix System price suffix.
 * @param WC_Product $product Product object.
 */
function add_price_suffix( $suffix, $product ) {
	global $chocante_display_price_modify;

	if ( ! $chocante_display_price_modify ) {
		return $suffix;
	}

	if ( $product instanceof \WC_Product_Variable ) {
		$visible_variations = $product->get_visible_children();

		if ( empty( $visible_variations ) ) {
			return $suffix;
		}

		$variation_id   = reset( $visible_variations );
		$variation_name = get_variation_name( wc_get_product( $variation_id ) );

		if ( $variation_name ) {
			$suffix .= " <small class='woocommerce-price-suffix'>/ {$variation_name}</small>";
		}
	}

	return $suffix;
}

/**
 * Format price range display on product listing
 *
 * @param html   $price Product price html.
 * @param string $from Price range from value.
 */
function modify_price_range( $price, $from ) {
	global $chocante_display_price_modify;

	if ( ! $chocante_display_price_modify ) {
		return $price;
	}

	// translators: Price range from value.
	return sprintf( esc_html__( 'From %1$s', 'chocante' ), is_numeric( $from ) ? wc_price( $from ) : $from );
}

/**
 * Add Globkurier to shipping methods
 *
 * @param array $shipping_methods Shipping methods.
 * @return array
 */
function add_globkurier_shipping_method( $shipping_methods ) {
	$shipping_methods['globkurier'] = 'Globkurier_Shipping';
	return $shipping_methods;
}

/**
 * Non-EU VAT validation for PL
 *
 * @param null   $validator External VAT validator.
 * @param string $tax_id VAT number.
 * @return bool
 */
function validate_nip( $validator, $tax_id ) {
	$weights = array( 6, 5, 7, 2, 3, 4, 5, 6, 7 );
	$sum     = 0;

	for ( $i = 0; $i < 9; $i++ ) {
		$sum += $tax_id[ $i ] * $weights[ $i ];
	}

	if ( ( $sum % 11 ) % 10 === intval( $tax_id[9] ) ) {
		return true;
	}

	return false;
}

/**
 * Disable gift wrapping icon in admin
 *
 * @return string
 */
function disable_gift_wrapper_icon_in_admin() {
	return '';
}

/**
 * Modify gift wrapper checkbox label
 *
 * @param string $label The input label as html.
 * @param string $label_icon The html of the icon.
 * @param string $label_text The escaped text of the label.
 * @return string
 */
function display_gift_wrapper_label( $label, $label_icon, $label_text ) {
	return $label_text;
}

/**
 * Validate postcode format
 */
function validate_postcode() {
	check_ajax_referer( 'chocante' );

	$postcode = isset( $_POST['postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['postcode'] ) ) : null;
	$country  = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : null;

	if ( ! isset( $postcode ) || ! isset( $country ) ) {
		wp_send_json_error();
	}

	$is_valid_postcode = \WC_Validation::is_postcode( $postcode, $country );

	if ( $is_valid_postcode ) {
		wp_send_json_success();
	} else {
		switch ( $country ) {
			case 'IE':
				$response_error = _x( 'Eircode is not valid.', 'checkout postcode validation', 'chocante' );
				break;
			default:
				$response_error = _x( 'Postcode / ZIP is not valid.', 'checkout postcode validation', 'chocante' );
		}

		wp_send_json_success( $response_error );
	}
}

/**
 * Gets display label of the first variation term
 *
 * @param WC_Product_Variation $product Variation product object.
 * @return string|false
 */
function get_variation_name( $product ) {
	if ( ! $product instanceof \WC_Product_Variation ) {
		return false;
	}

	$variation_attributes = $product->get_variation_attributes( false );
	$variation_term       = get_term_by( 'slug', reset( $variation_attributes ), array_key_first( $variation_attributes ) );

	if ( ! $variation_term ) {
		return false;
	}

	return $variation_term->name;
}
