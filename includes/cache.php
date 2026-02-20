<?php
/**
 * Caching
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Admin updates.
 */
add_action( 'woocommerce_ajax_save_product_variations', __NAMESPACE__ . '\purge_product' );
/**
 * Purge frontpage when admin marks product as featured
 * Used for purging featured products slider
 *
 * @todo: add cache tag to featured product section.
 */
add_action( 'woocommerce_before_product_object_save', __NAMESPACE__ . '\purge_featured_products' );

/**
 * Product section.
 */
add_action( 'chocante_product_section_ajax_get', __NAMESPACE__ . '\set_public_get_product_section', 1 );
/**
 * Purge all product sections
 *
 * @todo: switch to tagging.
 */
add_action( 'chocante_product_section_cache_flush', __NAMESPACE__ . '\purge_get_product_section' );

/**
 * Set public cache for AJAX get product section
 */
function set_public_get_product_section() {
	do_action( 'litespeed_control_force_public', 'AJAX product section' );
}

/**
 * Purge AJAX get product section
 */
function purge_get_product_section() {
	do_action( 'litespeed_purge', 'AJAX.get_product_section' );
}

/**
 * Purge product by ID
 *
 * @param int $product_id Product ID.
 */
function purge_product( $product_id ) {
	do_action( 'litespeed_purge_post', $product_id );
}

/**
 * Purge frontpage when admin changes featured status of product
 *
 * @param \WC_Product $product Product object.
 */
function purge_featured_products( $product ) {
	$changes = $product->get_changes();

	if ( isset( $changes['featured'] ) ) {
		do_action( 'litespeed_purge_post', get_option( 'page_on_front' ) );
	}
}
