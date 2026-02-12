<?php
/**
 * Caching
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Cache;

defined( 'ABSPATH' ) || exit;

add_action( 'chocante_product_section_ajax_get', __NAMESPACE__ . '\set_public_get_product_section', 1 );
add_action( 'chocante_product_section_cache_flush', __NAMESPACE__ . '\purge_get_product_section' );

/**
 * Set public cache for AJAX get product section.
 */
function set_public_get_product_section() {
	do_action( 'litespeed_control_force_public', 'AJAX product section' );
}

/**
 * Purge AJAX get product section.
 */
function purge_get_product_section() {
	do_action( 'litespeed_purge', 'AJAX.get_product_section' );
}
