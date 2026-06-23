<?php
/**
 * Stape Conversion Tracking integration:
 * - handles dynamic page caching
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\GTM;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'GTM_Server_Side_Event_ViewItem' ) || ! class_exists( 'GTM_Server_Side_Event_ViewItemList' ) ) {
	return;
}

// View item.
remove_action( 'wp_footer', array( \GTM_Server_Side_Event_ViewItem::instance(), 'wp_footer' ) );
add_action( 'wp_ajax_gtm_view_item', __NAMESPACE__ . '\gtm_view_item' );
add_action( 'wp_ajax_nopriv_gtm_view_item', __NAMESPACE__ . '\gtm_view_item' );
add_action( 'woocommerce_after_add_to_cart_button', __NAMESPACE__ . '\add_item_variant' );

// View item list.
remove_action( 'wp_footer', array( \GTM_Server_Side_Event_ViewItemList::instance(), 'wp_footer' ) );
add_action( 'wp_ajax_gtm_view_item_list', __NAMESPACE__ . '\gtm_view_item_list' );
add_action( 'wp_ajax_nopriv_gtm_view_item_list', __NAMESPACE__ . '\gtm_view_item_list' );
add_action( 'wp_footer', __NAMESPACE__ . '\output_item_list_data' );

// Client-side JS.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\add_script_data', 30 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\fix_gtm_server_side_item_variant', 30 );

/**
 * View item event
 */
function gtm_view_item() {
	check_ajax_referer( 'chocante_gtm', 'nonce' );

	if ( ! class_exists( 'GTM_Server_Side_Helpers' ) || ! class_exists( 'GTM_Server_Side_WC_Helpers' ) ) {
		wp_send_json_error( null, 500 );
	}

	if ( ! \GTM_Server_Side_WC_Helpers::instance()->is_enable_ecommerce() ) {
		wp_send_json_error( null, 400 );
	}

	$product_id = isset( $_GET['products'] ) ? absint( wp_unslash( $_GET['products'] ) ) : false;

	if ( ! $product_id ) {
		wp_send_json_error( null, 400 );
	}

	$product = wc_get_product( $product_id );

	if ( ! ( $product instanceof \WC_Product ) ) {
		wp_send_json_error( null, 400 );
	}

	$data_layer = array(
		'event'          => \GTM_Server_Side_Helpers::get_data_layer_event_name( 'view_item' ),
		'ecomm_pagetype' => 'product',
		'ecommerce'      => array(
			'currency' => esc_attr( get_woocommerce_currency() ),
			'value'    => $product->is_in_stock() ? \GTM_Server_Side_WC_Helpers::instance()->formatted_price( $product->get_price() ) : 0,
			'items'    => array(
				\GTM_Server_Side_WC_Helpers::instance()->get_data_layer_item( $product ),
			),
		),
	);

	gtm_additional_data( $data_layer );

	wp_send_json_success( $data_layer );
}

/**
 * View item list event
 */
function gtm_view_item_list() {
	check_ajax_referer( 'chocante_gtm', 'nonce' );

	if ( ! class_exists( 'GTM_Server_Side_Helpers' ) || ! class_exists( 'GTM_Server_Side_WC_Helpers' ) ) {
		wp_send_json_error( null, 500 );
	}

	if ( ! \GTM_Server_Side_WC_Helpers::instance()->is_enable_ecommerce() ) {
		wp_send_json_error( null, 400 );
	}

	$product_ids    = isset( $_GET['products'] ) ? array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_GET['products'] ) ) ) ) : array();
	$item_list_id   = isset( $_GET['listId'] ) ? absint( wp_unslash( $_GET['listId'] ) ) : null;
	$item_list_name = isset( $_GET['listName'] ) ? sanitize_text_field( wp_unslash( $_GET['listName'] ) ) : null;
	$items          = array();

	if ( ! empty( $product_ids ) ) {
		$args = array(
			'include' => $product_ids,
			'orderby' => 'none',
		);

		$products = wc_get_products( $args );
		usort( $products, fn( $a, $b ) => array_search( $a->get_id(), $product_ids, true ) - array_search( $b->get_id(), $product_ids, true ) );

		foreach ( $products as $index => $product ) {
			$array                   = \GTM_Server_Side_WC_Helpers::instance()->get_data_layer_item( $product );
			$array['item_list_id']   = $item_list_id;
			$array['item_list_name'] = $item_list_name;
			$array['quantity']       = 1;
			$array['index']          = ++$index;

			$items[] = $array;
		}
	}

	$data_layer = array(
		'event'          => \GTM_Server_Side_Helpers::get_data_layer_event_name( 'view_item_list' ),
		'ecomm_pagetype' => 'category',
		'ecommerce'      => array(
			'currency' => esc_attr( get_woocommerce_currency() ),
			'items'    => $items,
		),
	);

	gtm_additional_data( $data_layer );

	wp_send_json_success( $data_layer );
}

/**
 * Add additional data: cart, user
 *
 * @param array $data_layer GTM event data.
 */
function gtm_additional_data( &$data_layer ) {
	if ( \GTM_Server_Side_Helpers::is_enable_data_layer_custom_event_name() ) {
		$data_layer['cart_state'] = \GTM_Server_Side_State_Helpers::instance()->get_cart_data( WC()->cart );
	}

	if ( \GTM_Server_Side_WC_Helpers::instance()->is_enable_user_data() ) {
		$data_layer['user_data'] = \GTM_Server_Side_WC_Helpers::instance()->get_data_layer_user_data();
	}
}

/**
 * Localize client side JS with GTM data fetching info
 */
function add_script_data() {
	if ( ! is_woocommerce() ) {
		return;
	}

	$script_data = array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'ajaxNonce' => wp_create_nonce( 'chocante_gtm' ),
		'gtmAction' => is_product() ? 'gtm_view_item' : 'gtm_view_item_list',
	);
	$handle      = 'chocante-shop';

	if ( is_product() ) {
		$script_data['gtmId'] = get_queried_object_id();
		$handle               = 'chocante-product';
	}

	wp_localize_script(
		$handle,
		'chocanteGtm',
		$script_data
	);
}

/**
 * Add variant input to add to cart form
 */
function add_item_variant() {
	global $product;

	if ( ! $product ) {
		return;
	}
	if ( ! ( $product instanceof \WC_Product ) ) {
		return;
	}
	if ( 'variable' !== $product->get_type() ) {
		return;
	}

	echo '<input type="hidden" name="gtm_item_variant" value="" />';
}

/**
 * Use variation ID as item_variant
 */
function fix_gtm_server_side_item_variant() {
	wp_add_inline_script( 'gtm-server-side', 'window.pluginGtmServerSide.pushVariationProduct = window.pluginGtmServerSide.pushSimpleProduct;' );
}

/**
 * Output item list data according to current query
 */
function output_item_list_data() {
	if ( ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}

	global $wp_query;
	$product_ids = wp_list_pluck( $wp_query->posts, 'ID' );

	if ( is_shop() ) {
		$item_list_id   = wc_get_page_id( 'shop' );
		$item_list_name = get_the_title( wc_get_page_id( 'shop' ) );
	} else {
		$queried_object = get_queried_object();
		$item_list_id   = $queried_object->term_id;
		$item_list_name = $queried_object->name;
	}

	wp_add_inline_script(
		'chocante-shop',
		'window.gtmItems = ' . wp_json_encode(
			array(
				'products' => $product_ids,
				'pageId'   => $item_list_id,
				'pageName' => $item_list_name,
			)
		) . ';',
		'before'
	);
}
