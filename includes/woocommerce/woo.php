<?php
/**
 * WooCommerce settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Woo;

defined( 'ABSPATH' ) || exit;

// Modify price display.
add_action( 'woocommerce_before_shop_loop', __NAMESPACE__ . '\set_price_display_modify' );
add_action( 'chocante_product_section_loop', __NAMESPACE__ . '\set_price_display_modify' );
add_filter( 'woocommerce_get_price_suffix', __NAMESPACE__ . '\add_price_suffix', 10, 4 );
add_filter( 'woocommerce_format_price_range', __NAMESPACE__ . '\modify_price_range', 10, 3 );
add_filter( 'woocommerce_variable_price_html', __NAMESPACE__ . '\add_price_range_prefix', 10, 2 );

// Product search.
add_filter( 'get_product_search_form', __NAMESPACE__ . '\change_product_search_action' );
add_action( 'template_redirect', __NAMESPACE__ . '\redirect_product_search' );
add_filter( 'query_vars', __NAMESPACE__ . '\register_product_search_var' );
add_action( 'parse_query', __NAMESPACE__ . '\use_product_search_var' );
add_filter( 'get_search_query', __NAMESPACE__ . '\use_product_search_in_query' );

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
			$variation_display_name = apply_filters( 'chocante_product_variation_name', $variation_name );
			$suffix                .= " <small class='woocommerce-price-suffix'>/ {$variation_display_name}</small>";
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

	return wc_price( $from );
}

/**
 * Add prefix to price range
 *
 * @param string               $price_html Privce element.
 * @param \WC_Product_Variable $product Product object.
 * @return string
 */
function add_price_range_prefix( $price_html, $product ) {
	global $chocante_display_price_modify;

	if ( ! $chocante_display_price_modify ) {
		return $price_html;
	}

	$prices = $product->get_variation_prices( true );

	if ( empty( $prices['price'] ) ) {
		return $price_html;
	}

	$min_price = current( $prices['price'] );
	$max_price = end( $prices['price'] );

	if ( $min_price === $max_price ) {
		return $price_html;
	}

	return _x( 'From', 'price range prefix', 'chocante' ) . ' ' . $price_html;
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

	$variation_name = apply_filters( 'chocante_product_variation_name', $variation_term->name );

	return $variation_name;
}

/**
 * Additional hook in WooCommerce breadcrumbs
 */
function display_shop_breadcrumbs() {
	ob_start();

	woocommerce_breadcrumb();
	$breadcrumbs = apply_filters( 'chocante_shop_breadcrumbs', ob_get_clean() );

	echo wp_kses_post( $breadcrumbs );
}

/**
 * Display order line item quantity
 *
 * @param \WC_Product $product Product object.
 * @param int         $quantity Quantity.
 * @return string
 */
function get_order_item_quantity( $product, $quantity ) {
	$quantity_label = sprintf( '&times; %s', $quantity );
	$variation_name = get_variation_name( $product );

	if ( $variation_name ) {
		$quantity_label = sprintf( '%s &times; %s', $quantity, $variation_name );
	}

	return '<span class="product-quantity">' . $quantity_label . '</span>';
}

/**
 * Modify product search form - use shop url and filter_query param
 *
 * @param string $form Search form html.
 * @return string
 */
function change_product_search_action( $form ) {
	$shop_url = wc_get_page_permalink( 'shop' );
	$form     = str_replace(
		'action="' . esc_url( home_url( '/' ) ) . '"',
		'action="' . esc_url( $shop_url ) . '"',
		$form
	);

	$form = str_replace(
		'name="s"',
		'name="filter_query"',
		$form
	);

	return $form;
}

/**
 * Redirect product search to shop path
 */
function redirect_product_search() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['s'] ) ) {

		$shop_url = wc_get_page_permalink( 'shop' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );

		$redirect_url = add_query_arg(
			array(
				'filter_query' => rawurlencode( $search_query ),
				'post_type'    => 'product',
			),
			$shop_url
		);

		wp_safe_redirect( $redirect_url, 301 );
		exit;
	}
}

/**
 * Register new query var for product search
 *
 * @param string[] $vars The array of allowed query variable names.
 * @return string[]
 */
function register_product_search_var( $vars ) {
	$vars[] = 'filter_query';
	return $vars;
}

/**
 * Use product search query var to search
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
function use_product_search_var( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
		if ( isset( $query->query_vars['filter_query'] ) && ! empty( $query->query_vars['filter_query'] ) ) {
			$query->set( 's', $query->query_vars['filter_query'] );
			$query->is_search = true;
		}
	}
}

/**
 * Use product search param in search query
 *
 * @param mixed $query Contents of the search query variable.
 * @return mixed
 */
function use_product_search_in_query( $query ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['filter_query'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET['filter_query'] ) );
	}

	return $query;
}
