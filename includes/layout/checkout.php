<?php
/**
 * Layout hooks - checkout
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Checkout;

defined( 'ABSPATH' ) || exit;

use BLPaczka_Shipping;
use function Chocante\Woo\get_order_item_quantity;

const DELIVERY_POINT = 'chocante_delivery_point';

add_action( 'woocommerce_before_checkout_form', __NAMESPACE__ . '\display_page_title', 1 );

// Fix for free shipping notice order.
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

add_filter( 'woocommerce_checkout_cart_item_quantity', __NAMESPACE__ . '\modify_item_quantity', 10, 2 );
add_action( 'woocommerce_review_order_before_payment', __NAMESPACE__ . '\display_payment_title' );
add_action( 'woocommerce_checkout_after_customer_details', __NAMESPACE__ . '\show_back_to_cart' );

// Delivery point.
add_action( 'woocommerce_after_shipping_rate', __NAMESPACE__ . '\display_delivery_point_selection', 10, 2 );
add_action( 'wp_ajax_chocante_delivery_point_save', __NAMESPACE__ . '\ajax_delivery_point_save' );
add_action( 'wp_ajax_nopriv_chocante_delivery_point_save', __NAMESPACE__ . '\ajax_delivery_point_save' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\add_script_data', 30 );
add_action( 'woocommerce_after_checkout_validation', __NAMESPACE__ . '\delivery_point_validate_in_checkout', 10, 2 );
add_action( 'woocommerce_checkout_order_processed', __NAMESPACE__ . '\delivery_point_reset' );
add_action( 'woocommerce_checkout_update_order_meta', __NAMESPACE__ . '\delivery_point_save_in_order' );
add_filter( 'woocommerce_order_get_formatted_shipping_address', __NAMESPACE__ . '\delivery_point_display_in_order', 10, 3 );
add_filter( 'woocommerce_hidden_order_itemmeta', __NAMESPACE__ . '\delivery_point_hide_meta' );

// Thank you.
remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );

/**
 * Display page title
 */
function display_page_title() {
	get_template_part( 'template-parts/page', 'header' );
}

/**
 * Modify order item quantity in order details
 *
 * @param string $quantity_html Order line item quantity HTML.
 * @param array  $cart_item Order line item.
 * @return string
 */
function modify_item_quantity( $quantity_html, $cart_item ) {
	$product  = $cart_item['data'];
	$quantity = $cart_item['quantity'];

	return get_order_item_quantity( $product, $quantity );
}

/**
 * Display payment section title
 */
function display_payment_title() {
	echo '<h6 class="payment__heading">' . esc_html__( 'Payment methods', 'woocommerce' ) . '</h6>';
}

/**
 * Display back to cart button
 */
function show_back_to_cart() {
	echo '<a href="' . esc_url( wc_get_cart_url() ) . '" class="back-to-cart">' . esc_html_x( 'Edit previous step', 'checkout', 'chocante' ) . '</a>';
}

/**
 * Display POD for BL Paczka shpipping method
 *
 * @param \WC_Shipping_Rate $method Shipping method instace.
 */
function display_delivery_point_selection( $method ) {
	if ( ! is_checkout() ) {
		return;
	}

	$method_id = $method->get_method_id();

	if ( BLPaczka_Shipping::METHOD_ID !== $method_id ) {
		return;
	}

	$method_meta = $method->get_meta_data();

	if ( true === (bool) $method_meta['pod'] ) {
		$method_meta = $method->get_meta_data();
		get_template_part(
			'template-parts/delivery',
			'point',
			array(
				'courier'     => $method_meta['courier'],
				'instance_id' => $method->get_instance_id(),
			)
		);
	}
}

/**
 * AJAX handle save delivery point
 */
function ajax_delivery_point_save() {
	check_ajax_referer( 'chocante' );

	$point_id   = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : null;
	$point_info = isset( $_POST['info'] ) ? sanitize_text_field( wp_unslash( $_POST['info'] ) ) : null;
	$courier    = isset( $_POST['courier'] ) ? sanitize_text_field( wp_unslash( $_POST['courier'] ) ) : null;

	if ( isset( $point_id ) && isset( $point_info ) && isset( $courier ) ) {
		delivery_point_save( $point_id, $point_info, $courier );
		wp_die();
	} else {
		delivery_point_reset();
		wp_send_json_error();
	}
}

/**
 * Save selected delivery point to session
 *
 * @param string $point_id Number of delivery point.
 * @param string $point_info Delivery point info.
 * @param string $courier Courier code.
 */
function delivery_point_save( $point_id, $point_info, $courier ) {
	$delivery_point = array(
		'id'      => $point_id,
		'info'    => $point_info,
		'courier' => $courier,
		'country' => WC()->customer->get_shipping_country(),
	);

	WC()->session->set( DELIVERY_POINT, $delivery_point );
}

/**
 * Reset delivery point
 */
function delivery_point_reset() {
	WC()->session->__unset( DELIVERY_POINT );
}

/**
 * Add data to JS script
 */
function add_script_data() {
	if ( ! class_exists( 'WooCommerce' ) || ! is_checkout() ) {
		return;
	}

	$i18n = apply_filters(
		'chocante_checkout_i18n',
		array(
			'selected'             => __( 'Selected delivery point:', 'chocante' ),
			'selected_empty'       => __( 'No delivery point selected', 'chocante' ),
			'placeholder'          => __( 'Select delivery point', 'chocante' ),
			'placeholder_fetching' => __( 'Fetching point data...', 'chocante' ),
			'placeholder_empty'    => __( 'No points available', 'chocante' ),
		)
	);

	$script_data = array(
		'apiUrl'    => rest_url( 'chocante/pod' ),
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'ajaxNonce' => wp_create_nonce( 'chocante' ),
		'i18n'      => $i18n,
	);

	$delivery_point = WC()->session->get( DELIVERY_POINT );

	if ( ! empty( $delivery_point ) ) {
		$script_data['deliveryPoint'] = $delivery_point;
	}

	wp_localize_script(
		'chocante-checkout',
		'chocante',
		$script_data
	);
}

/**
 * Validate that the delivery point is set for a shipping method with POD defined
 *
 * @param  array     $data   An array of posted data.
 * @param  \WP_Error $errors Validation errors.
 */
function delivery_point_validate_in_checkout( $data, $errors ) {
	$packages          = WC()->shipping->get_packages();
	$package           = $packages[0];
	$available_methods = $package['rates'];

	foreach ( $data['shipping_method'] as $method ) {
		$rate = $available_methods[ $method ];

		if ( empty( $rate ) ) {
			return;
		}

		$rate_meta = $rate->get_meta_data();

		if ( isset( $rate_meta['pod'] ) && true === (bool) $rate_meta['pod'] ) {
			$delivery_point = WC()->session->get( DELIVERY_POINT );

			if ( ! isset( $delivery_point ) || $delivery_point['courier'] !== $rate_meta['courier'] ) {
				$errors->add( 'shipping', __( 'Please select delivery point.', 'chocante' ) );
			}
		}
	}
}

/**
 * Save delivery point in order
 *
 * @param mixed $order_id Post ID of processed order.
 */
function delivery_point_save_in_order( $order_id ) {
	$delivery_point = WC()->session->get( DELIVERY_POINT );

	if ( isset( $delivery_point ) ) {
		$order = wc_get_order( $order_id );

		$order->update_meta_data( DELIVERY_POINT, $delivery_point );
		$order->save_meta_data();
	}
}

/**
 * Display delivery point in order shipping address
 *
 * @param string    $address Order address.
 * @param array     $raw_address Order address array.
 * @param \WC_Order $order Current order.
 * @return string
 */
function delivery_point_display_in_order( $address, $raw_address, $order ) {
	$delivery_point = $order->get_meta( DELIVERY_POINT );

	if ( ! empty( $delivery_point ) && ! empty( $address ) ) {
		$delivery_point_info = $delivery_point['info'];
		$address            .= '<br /><br />';
		// translators: Delivery point info.
		$address .= esc_html__( 'Delivery Point', 'chocante' ) . ':';
		$address .= '<br />';
		$address .= "{$delivery_point_info}";
	}

	return $address;
}

/**
 * Hide delivery point data from shipping order meta
 *
 * @param string[] $meta Order item meta.
 * @return string[]
 */
function delivery_point_hide_meta( $meta ) {
	$meta[] = 'courier';
	$meta[] = 'pod';
	$meta[] = 'cod';
	return $meta;
}
