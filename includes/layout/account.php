<?php
/**
 * Layout hooks - account pages
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Layout\Account;

use WC_Countries;

defined( 'ABSPATH' ) || exit;

use function Chocante\Assets\icon;
use function Chocante\Woo\get_order_item_quantity;

// Page header.
add_action( 'woocommerce_account_navigation', __NAMESPACE__ . '\display_page_header', 5 );

// Account menu.
add_filter( 'woocommerce_account_menu_items', __NAMESPACE__ . '\hide_account_pages' );

// Account content.
add_action( 'woocommerce_account_content', __NAMESPACE__ . '\display_mobile_header', 4 );

// Dashboard.
remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
add_action( 'woocommerce_account_content', __NAMESPACE__ . '\display_dashboard' );

// Orders.
add_filter( 'woocommerce_account_orders_columns', __NAMESPACE__ . '\manage_orders_table_cols' );
add_action( 'woocommerce_my_account_my_orders_column_order-status-number', __NAMESPACE__ . '\display_order_status_number' );
add_action( 'woocommerce_my_account_my_orders_column_order-total-value', __NAMESPACE__ . '\display_order_total' );
add_filter( 'woocommerce_order_item_quantity_html', __NAMESPACE__ . '\add_variation_to_item_quantity', 10, 2 );
add_filter( 'woocommerce_order_item_name', __NAMESPACE__ . '\display_parent_item_name', 10, 2 );
add_filter( 'woocommerce_order_item_name', __NAMESPACE__ . '\display_item_link', 20, 3 );
add_filter( 'woocommerce_display_item_meta', '__return_false' );
add_filter( 'woocommerce_get_order_item_totals', __NAMESPACE__ . '\display_tax', 10, 2 );

// Login.
add_action( 'woocommerce_before_customer_login_form', __NAMESPACE__ . '\display_login_page_title', 5 );

/**
 * Hide unused account pages
 *
 * @param array $items Account menu items.
 * @return array
 */
function hide_account_pages( $items ) {
	unset( $items['downloads'] );
	unset( $items['payment-methods'] );

	return $items;
}

/**
 * Get menu item icon
 *
 * @param string $endpoint Menu item endpoint.
 */
function get_menu_icon( $endpoint ) {
	switch ( $endpoint ) {
		case 'edit-address':
			$icon = 'address';
			break;
		case 'edit-account':
			$icon = 'settings';
			break;
		case 'customer-logout':
			$icon = 'logout';
			break;
		default:
			$icon = $endpoint;
	}

	ob_start();
	icon( $icon );
	echo wp_kses_post( ob_get_clean() );
}

/**
 * Display mobile header
 */
function display_mobile_header() {
	global $wp;

	$page_titles = array(
		'dashboard'       => __( 'Dashboard', 'woocommerce' ),
		'orders'          => __( 'Orders', 'woocommerce' ),
		'view-order'      => __( 'Orders', 'woocommerce' ),
		'downloads'       => __( 'Downloads', 'woocommerce' ),
		'edit-address'    => _n( 'Address', 'Addresses', ( 1 + (int) wc_shipping_enabled() ), 'woocommerce' ),
		'payment-methods' => __( 'Payment methods', 'woocommerce' ),
		'edit-account'    => __( 'Account details', 'woocommerce' ),
		'customer-logout' => __( 'Log out', 'woocommerce' ),
	);

	$current_page_title = $page_titles['dashboard'];

	if ( ! empty( $wp->query_vars ) ) {
		foreach ( $wp->query_vars as $key => $value ) {
			if ( array_key_exists( $key, $page_titles ) ) {
				$current_page_title = $page_titles[ $key ];
				break;
			}
		}
	}

	get_template_part(
		'template-parts/account',
		'mobile-header',
		array(
			'page_title' => $current_page_title,
		)
	);
}

/**
 * Display page header
 */
function display_page_header() {
	get_template_part( 'template-parts/page', 'header' );
}

/**
 * Modify orders table columns
 *
 * @return array
 */
function manage_orders_table_cols() {
	return array(
		'order-status-number' => __( 'Status', 'woocommerce' ) . ' / ' . __( 'Order', 'woocommerce' ),
		'order-date'          => __( 'Date', 'woocommerce' ),
		'order-total-value'   => __( 'Total', 'woocommerce' ),
		'order-actions'       => __( 'Actions', 'woocommerce' ),
	);
}

/**
 * Display order status and number
 *
 * @param WC_Order|WC_Order_Refund $order Order.
 */
function display_order_status_number( $order ) {
	echo '<span class="order-status order-status--' . esc_attr( $order->get_status() ) . '">' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span>';
	echo '<span class="order-number">' . esc_html( '#' . $order->get_order_number() ) . '</span>';
}

/**
 * Display order total
 *
 * @param WC_Order|WC_Order_Refund $order Order.
 */
function display_order_total( $order ) {
	echo wp_kses_post( $order->get_formatted_order_total() );
}

/**
 * Display account dashboard
 */
function display_dashboard() {
	global $wp;

	if ( ! empty( $wp->query_vars ) ) {
		foreach ( $wp->query_vars as $key => $value ) {
			// Ignore pagename param.
			if ( 'pagename' === $key ) {
				continue;
			}

			if ( has_action( 'woocommerce_account_' . $key . '_endpoint' ) ) {
				do_action( 'woocommerce_account_' . $key . '_endpoint', $value );
				return;
			}
		}
	}

	$last_order = wc_get_orders(
		array(
			'customer' => get_current_user_id(),
			'limit'    => 1,
		)
	);

	$shipping_address = wc_get_account_formatted_address( 'shipping' );

	get_template_part(
		'template-parts/account',
		'dashboard',
		array(
			'order'   => $last_order,
			'address' => $shipping_address,
		)
	);
}

/**
 * Display page title on login page
 */
function display_login_page_title() {
	echo '<h1 class="page-title">' . esc_html_x( 'Already have an account?', 'login page', 'chocante' ) . '</h1>';
}

/**
 * Add variation to order item quantity in order details
 *
 * @param string        $quantity_html Order line item quantity HTML.
 * @param WC_Order_Item $item Order line item.
 * @return string
 */
function add_variation_to_item_quantity( $quantity_html, $item ) {
	$product = $item->get_product();
	$qty     = $item->get_quantity();

	return get_order_item_quantity( $product, $qty );
}

/**
 * Display parent product name in order line item
 *
 * @param string                 $item_name_html Display name of line item.
 * @param \WC_Order_Item_Product $item Order line item.
 * @return string
 */
function display_parent_item_name( $item_name_html, $item ) {
	$product = $item->get_product();

	if ( ! $product ) {
		return $item_name_html;
	}

	$product_name = $product->get_title();

	return $product_name;
}

/**
 * Display link to visible product in order line item
 *
 * @param string                 $item_name_html Display name of line item.
 * @param \WC_Order_Item_Product $item Order line item.
 * @param bool                   $is_visible Is product visible.
 * @return string
 */
function display_item_link( $item_name_html, $item, $is_visible ) {
	if ( is_admin() ) {
		return $item_name_html;
	}

	$product = $item->get_product();

	if ( $is_visible ) {
		return sprintf( '<a href="%s">%s</a>', $product->get_permalink( $item ), $item_name_html );
	} else {
		return '<strong>' . $item_name_html . '</strong>';
	}
}

/**
 * Conditionally display tax information in order details
 *
 * @param array     $total_rows Order summary rows.
 * @param \WC_Order $order Order object.
 * @return array
 */
function display_tax( $total_rows, $order ) {
	$billing_company = $order->get_billing_company();
	$billing_country = $order->get_billing_country();
	$countries       = new WC_Countries();
	$vat_countries   = $countries->get_european_union_countries( 'eu_vat' );
	$is_vat_country  = in_array( $billing_country, $vat_countries, true );
	$tax_display     = ! empty( $billing_company ) && $is_vat_country ? 'excl' : 'incl';

	$total_rows['cart_subtotal'] = array(
		'type'  => 'subtotal',
		'label' => __( 'Subtotal:', 'woocommerce' ),
		'value' => $order->get_subtotal_to_display( false, $tax_display ),
	);
	$total_rows['order_total']   = array(
		'type'  => 'total',
		'label' => __( 'Total:', 'woocommerce' ),
		'value' => $order->get_formatted_order_total( 'excl' ),
	);

	unset( $total_rows['tax'] );

	if ( $billing_company && $is_vat_country ) {
		$pos = array_search( 'cart_subtotal', array_keys( $total_rows ), true );
		return array_merge(
			array_slice( $total_rows, 0, $pos + 1, true ),
			array(
				'tax' => array(
					'type'  => 'tax',
					'label' => WC()->countries->tax_or_vat() . ':',
					'value' => wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ),
				),
			),
			array_slice( $total_rows, $pos + 1, null, true ),
		);
	}
	return $total_rows;
}
