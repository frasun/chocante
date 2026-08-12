<?php
/**
 * Coupon settings
 *
 * @package WordPress
 * @subpackage Chocante
 */

namespace Chocante\Coupons;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

add_action( 'template_redirect', __NAMESPACE__ . '\apply_coupon_from_url' );

/**
 * Apply coupon from url
 */
function apply_coupon_from_url() {
	if ( ! is_404() ) {
		return;
	}

  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
	$path       = trim( wp_parse_url( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );
	$segments   = explode( '/', $path );
	$maybe_code = end( $segments );

	if ( ! $maybe_code ) {
		return;
	}

	$coupon_id = wc_get_coupon_id_by_code( $maybe_code );

	if ( ! $coupon_id ) {
		return;
	}

	header( 'Cache-Control: no-store, no-cache, must-revalidate', true );
	do_action( 'litespeed_control_set_nocache', 'Apply coupon via url' );

	if ( ! WC()->session ) {
		WC()->initialize_session();
	}

	if ( ! WC()->cart ) {
		WC()->initialize_cart();
	}

	if ( ! WC()->cart->has_discount( $maybe_code ) ) {
		WC()->cart->apply_coupon( $maybe_code );
	}

	/**
	 * Clear notices so page doesn't get cached with a notice
	 *
	 * @todo: use client side notice based on Redux
	 */
	wc_clear_notices();

	WC()->session->set_customer_session_cookie( true );

	array_pop( $segments );
	$clean_path   = implode( '/', $segments );
	$redirect_url = home_url( '/' . $clean_path . ( $clean_path ? '/' : '' ) );

	wp_safe_redirect( $redirect_url, 302 );
	exit;
}
