<?php
/**
 * ESI url - product tile
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo apply_filters( 'litespeed_esi_url', 'product_tile', 'chocante - product tile', array( 'id' => $product->get_id() ), 'public' );
